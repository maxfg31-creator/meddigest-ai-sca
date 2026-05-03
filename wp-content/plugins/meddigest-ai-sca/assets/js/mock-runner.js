(function () {
  window.MedDigestAiSca = window.MedDigestAiSca || {};

  const config = window.mdscaMock || {};
  const state = {
    connectedStation: null,
    peerConnection: null,
    transcriptTurns: [],
    transcriptFlushInFlight: false,
    allowNavigation: false,
  };

  function message(text) {
    const el = document.querySelector(".mdsca-mock-message");
    if (el) {
      el.textContent = text || "";
    }
  }

  async function api(path, options) {
    const response = await fetch((config.restUrl || "").replace(/\/$/, "") + path, {
      method: options && options.method ? options.method : "GET",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": config.nonce || "",
      },
      body: options && options.body ? JSON.stringify(options.body) : undefined,
      credentials: "same-origin",
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data.message || "Request failed");
    }

    return data;
  }

  function setupLaunch() {
    const root = document.querySelector(".mdsca-mock-launch");
    const button = document.querySelector(".mdsca-start-mock");

    if (!root || !button) {
      return;
    }

    const checks = Array.from(document.querySelectorAll(".mdsca-mock-required-check"));
    const serverCanStart = root.getAttribute("data-mdsca-can-start") === "1";
    const update = () => {
      button.disabled = !serverCanStart || !checks.every((check) => check.checked);
    };

    checks.forEach((check) => check.addEventListener("change", update));
    update();

    button.addEventListener("click", async () => {
      button.disabled = true;
      message("Creating your Full Mock SCA...");

      try {
        const data = await api("/mock/start", { method: "POST" });
        await api("/consent", {
          method: "POST",
          body: {
            object_type: "mock_run",
            object_uuid: data.mock_uuid,
          },
        });
        window.location.href = data.run_url || ("/sca-ai/mock/" + data.mock_uuid + "/run/");
      } catch (error) {
        button.disabled = false;
        message(error.message);
      }
    });
  }

  function setupRunner() {
    const root = document.querySelector(".mdsca-mock-run");

    if (!root || !config.mockUuid) {
      return;
    }

    window.addEventListener("beforeunload", (event) => {
      if (state.allowNavigation) {
        return;
      }

      event.preventDefault();
      event.returnValue = "";
    });

    pollStatus();
    window.MedDigestAiSca.mockPollTimer = window.setInterval(pollStatus, 3000);
    window.MedDigestAiSca.mockTranscriptTimer = window.setInterval(flushTranscript, 30000);
  }

  async function pollStatus() {
    try {
      const status = await api("/mock/" + config.mockUuid + "/status");
      renderStatus(status);

      if (status.status === "completed" || status.phase === "results" || status.status === "processing" || status.phase === "processing") {
        await flushTranscript(true);
        closeRealtime();
        state.allowNavigation = true;
        window.location.href = status.results_url;
        return;
      }

      if (status.phase === "live") {
        await ensureRealtime(status.station_number);
      } else {
        await flushTranscript(true);
        closeRealtime();
      }
    } catch (error) {
      message(error.message);
    }
  }

  function renderStatus(status) {
    const phaseLabel = document.querySelector("[data-mdsca-mock-phase-label]");
    const heading = document.querySelector("[data-mdsca-mock-station-heading]");
    const body = document.querySelector("[data-mdsca-mock-phase-body]");
    const timer = document.querySelector("[data-mdsca-mock-timer]");

    if (phaseLabel) {
      phaseLabel.textContent = status.phase ? status.phase.charAt(0).toUpperCase() + status.phase.slice(1) : "";
    }

    if (heading) {
      heading.textContent = "Station " + status.station_number + " of " + status.total_stations;
    }

    if (timer) {
      const remaining = Math.max(0, Number(status.seconds_remaining || 0));
      const minutes = String(Math.floor(remaining / 60)).padStart(2, "0");
      const seconds = String(remaining % 60).padStart(2, "0");
      timer.textContent = minutes + ":" + seconds;
    }

    if (body) {
      if (status.phase === "reading" && status.current_station) {
        body.innerHTML = "";
        const title = document.createElement("h3");
        const brief = document.createElement("p");
        title.textContent = status.current_station.title || "";
        brief.textContent = status.current_station.doctor_brief || "";
        body.appendChild(title);
        body.appendChild(brief);
      } else if (status.phase === "live") {
        body.textContent = "Live AI consultation in progress.";
      } else if (status.phase === "break") {
        body.textContent = "10-minute break after station 6.";
      } else {
        body.textContent = "Your mock is being processed.";
      }
    }
  }

  async function ensureRealtime(stationNumber) {
    if (state.connectedStation === stationNumber && state.peerConnection) {
      return;
    }

    await flushTranscript(true);
    closeRealtime();

    const data = await api("/mock/" + config.mockUuid + "/realtime-token", { method: "POST" });
    const token = data.token && data.token.client_secret ? data.token.client_secret.value : data.token && data.token.value;

    if (!token) {
      throw new Error("Realtime client secret was not returned.");
    }

    await connectRealtime(token);
    state.connectedStation = stationNumber;
    message("Live AI consultation is connected.");
  }

  async function connectRealtime(token) {
    const audioRoot = document.querySelector("[data-mdsca-mock-audio]");
    const pc = new RTCPeerConnection();
    const audio = document.createElement("audio");
    audio.autoplay = true;

    pc.ontrack = (event) => {
      audio.srcObject = event.streams[0];
    };

    if (audioRoot) {
      audioRoot.innerHTML = "";
      audioRoot.appendChild(audio);
    }

    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    pc.addTrack(stream.getTracks()[0]);

    const events = pc.createDataChannel("oai-events");
    events.addEventListener("message", handleRealtimeEvent);

    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);

    const response = await fetch("https://api.openai.com/v1/realtime/calls", {
      method: "POST",
      body: offer.sdp,
      headers: {
        Authorization: "Bearer " + token,
        "Content-Type": "application/sdp",
      },
    });

    if (!response.ok) {
      throw new Error("Realtime WebRTC connection failed.");
    }

    await pc.setRemoteDescription({
      type: "answer",
      sdp: await response.text(),
    });

    state.peerConnection = pc;
  }

  function closeRealtime() {
    if (!state.peerConnection) {
      return;
    }

    state.peerConnection.getSenders().forEach((sender) => {
      if (sender.track) {
        sender.track.stop();
      }
    });
    state.peerConnection.close();
    state.peerConnection = null;
    state.connectedStation = null;
  }

  function handleRealtimeEvent(event) {
    let data;

    try {
      data = JSON.parse(event.data);
    } catch (error) {
      return;
    }

    if (!data || !data.type) {
      return;
    }

    if (data.type === "conversation.item.input_audio_transcription.completed" && data.transcript) {
      addTranscriptTurn("candidate", data.transcript);
    }

    if ((data.type === "response.output_audio_transcript.done" || data.type === "response.audio_transcript.done") && data.transcript) {
      addTranscriptTurn("patient", data.transcript);
    }

    if (data.type === "response.output_text.done" && data.text) {
      addTranscriptTurn("patient", data.text);
    }
  }

  function addTranscriptTurn(speaker, text) {
    const normalized = String(text || "").trim();

    if (!normalized) {
      return;
    }

    state.transcriptTurns.push({
      speaker,
      text: normalized,
      created_at: new Date().toISOString(),
    });
  }

  async function flushTranscript(clearAfterSave) {
    if (!config.mockUuid || state.transcriptFlushInFlight || state.transcriptTurns.length === 0 || !state.connectedStation) {
      return;
    }

    state.transcriptFlushInFlight = true;

    try {
      await api("/mock/" + config.mockUuid + "/transcript", {
        method: "POST",
        body: {
          station_number: state.connectedStation,
          turns: state.transcriptTurns,
        },
      });

      if (clearAfterSave === true) {
        state.transcriptTurns = [];
      }
    } catch (error) {
      // Transcript capture is best-effort and never shown live.
    } finally {
      state.transcriptFlushInFlight = false;
    }
  }

  function setupResultsPolling() {
    const root = document.querySelector("[data-mdsca-mock-results]");

    if (!root || !config.mockUuid) {
      return;
    }

    const initialStatus = root.getAttribute("data-mdsca-results-status");
    const terminal = ["completed", "failed", "requires_openai_configuration"];

    if (terminal.includes(initialStatus)) {
      return;
    }

    window.setInterval(async () => {
      try {
        const payload = await api("/mock/" + config.mockUuid + "/results");
        const status = payload.results && payload.results.status ? payload.results.status : payload.status;
        message(status === "completed" ? "Results ready." : "Generating your final mock results...");

        if (terminal.includes(status)) {
          window.location.reload();
        }
      } catch (error) {
        message(error.message);
      }
    }, 5000);
  }

  document.addEventListener("DOMContentLoaded", () => {
    setupLaunch();
    setupRunner();
    setupResultsPolling();
  });
})();
