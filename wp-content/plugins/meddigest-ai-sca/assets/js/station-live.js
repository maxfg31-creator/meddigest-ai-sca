(function () {
  window.MedDigestAiSca = window.MedDigestAiSca || {};

  const config = window.mdscaStation || {};
  const transcriptTurns = [];
  let stationEnding = false;
  let transcriptFlushInFlight = false;

  function message(text) {
    const el = document.querySelector(".mdsca-station-message");
    if (el) {
      el.textContent = text;
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

  function setupStartButton() {
    const button = document.querySelector(".mdsca-start-station");
    const root = document.querySelector(".mdsca-station-setup");

    if (!button || !root) {
      return;
    }

    const checks = Array.from(document.querySelectorAll(".mdsca-required-check"));
    const serverCanBegin = root.getAttribute("data-mdsca-can-begin") === "1";
    const update = () => {
      button.disabled = !serverCanBegin || !checks.every((check) => check.checked);
    };

    checks.forEach((check) => check.addEventListener("change", () => {
      update();
    }));

    update();

    button.addEventListener("click", async () => {
      button.disabled = true;
      message("Creating your AI station...");

      try {
        const data = await api("/station/start", {
          method: "POST",
          body: {
            case_post_id: root.getAttribute("data-mdsca-case-id"),
          },
        });

        if (data.attempt_uuid) {
          window.location.href = "/sca-ai/station/" + data.attempt_uuid + "/live/";
        }
      } catch (error) {
        button.disabled = false;
        message(error.message);
      }
    });
  }

  function setupTimer() {
    const timer = document.querySelector(".mdsca-timer");

    if (!timer) {
      return;
    }

    const hardStop = timer.getAttribute("data-mdsca-hard-stop");
    window.MedDigestAiSca.timerEndTime = hardStop ? new Date(hardStop.replace(" ", "T") + "Z").getTime() : Date.now() + 12 * 60 * 1000;

    setInterval(() => {
      const remaining = Math.max(0, Math.floor((window.MedDigestAiSca.timerEndTime - Date.now()) / 1000));
      const minutes = String(Math.floor(remaining / 60)).padStart(2, "0");
      const seconds = String(remaining % 60).padStart(2, "0");
      timer.textContent = minutes + ":" + seconds;

      if (remaining === 0 && document.querySelector(".mdsca-station-live")) {
        endStation(true);
      }
    }, 500);
  }

  function setTimerHardStop(hardStop) {
    if (!hardStop) {
      return;
    }

    window.MedDigestAiSca.timerEndTime = new Date(hardStop.replace(" ", "T") + "Z").getTime();
  }

  async function setupLive() {
    const live = document.querySelector(".mdsca-station-live");

    if (!live || !config.attemptUuid) {
      return;
    }

    try {
      const data = await api("/station/" + config.attemptUuid + "/realtime-token", { method: "POST" });
      const token = data.token && data.token.client_secret ? data.token.client_secret.value : data.token && data.token.value;

      if (!token) {
        throw new Error("Realtime client secret was not returned.");
      }

      if (data.attempt && data.attempt.hard_stop_at) {
        setTimerHardStop(data.attempt.hard_stop_at);
      }

      await connectRealtime(token);
      window.MedDigestAiSca.transcriptFlushTimer = window.setInterval(flushTranscript, 30000);

      const status = document.querySelector(".mdsca-audio-status");
      if (status) {
        status.textContent = "Live AI consultation is connected.";
      }
    } catch (error) {
      message(error.message);
    }
  }

  async function connectRealtime(token) {
    const liveAudio = document.querySelector(".mdsca-live-audio");
    const pc = new RTCPeerConnection();
    const audio = document.createElement("audio");
    audio.autoplay = true;

    pc.ontrack = (event) => {
      audio.srcObject = event.streams[0];
    };

    if (liveAudio) {
      liveAudio.appendChild(audio);
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

    window.MedDigestAiSca.peerConnection = pc;
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

    transcriptTurns.push({
      speaker,
      text: normalized,
      created_at: new Date().toISOString(),
    });
  }

  async function flushTranscript() {
    if (!config.attemptUuid || transcriptFlushInFlight || transcriptTurns.length === 0) {
      return;
    }

    transcriptFlushInFlight = true;

    try {
      await api("/station/" + config.attemptUuid + "/transcript", {
        method: "POST",
        body: {
          turns: transcriptTurns,
        },
      });
    } catch (error) {
      // Transcript capture is best-effort and never shown live.
    } finally {
      transcriptFlushInFlight = false;
    }
  }

  function setupEndButton() {
    const button = document.querySelector(".mdsca-end-station");

    if (!button || !config.attemptUuid) {
      return;
    }

    button.addEventListener("click", async () => {
      if (!window.confirm("End this consultation now?")) {
        return;
      }

      endStation(true);
    });
  }

  function setupFeedbackPolling() {
    const feedback = document.querySelector(".mdsca-station-feedback");

    if (!feedback || !config.attemptUuid) {
      return;
    }

    const terminalStatuses = ["completed", "requires_openai_configuration", "failed"];
    const initialStatus = feedback.getAttribute("data-mdsca-feedback-status");

    if (terminalStatuses.includes(initialStatus)) {
      return;
    }

    setInterval(async () => {
      try {
        const data = await api("/station/" + config.attemptUuid + "/feedback");
        const status = data.feedback && data.feedback.processing_status ? data.feedback.processing_status : "pending";

        message(status === "retrying" ? "Feedback generation is retrying..." : "Generating feedback...");

        if (terminalStatuses.includes(status)) {
          window.location.reload();
        }
      } catch (error) {
        message(error.message);
      }
    }, 5000);
  }

  async function endStation(redirect) {
    if (stationEnding || !config.attemptUuid) {
      return;
    }

    stationEnding = true;

    const button = document.querySelector(".mdsca-end-station");
    if (button) {
      button.disabled = true;
    }

    try {
      await flushTranscript();
      if (window.MedDigestAiSca.transcriptFlushTimer) {
        window.clearInterval(window.MedDigestAiSca.transcriptFlushTimer);
      }

      await api("/station/" + config.attemptUuid + "/end", { method: "POST" });

      if (redirect !== false) {
        window.location.href = "/sca-ai/station/" + config.attemptUuid + "/feedback/";
      }
    } catch (error) {
      stationEnding = false;

      if (button) {
        button.disabled = false;
      }

      message(error.message);
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    setupStartButton();
    setupTimer();
    setupLive();
    setupEndButton();
    setupFeedbackPolling();
  });
})();
