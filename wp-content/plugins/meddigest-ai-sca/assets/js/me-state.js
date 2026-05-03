/* global wpApiSettings */
(function () {
  window.MedDigestAiSca = window.MedDigestAiSca || {};

  const config = window.mdscaState || {};

  async function fetchState() {
    if (!config.loggedIn || !config.nonce || !config.restUrl) {
      return null;
    }

    const response = await fetch(config.restUrl.replace(/\/$/, "") + "/me/state", {
      credentials: "same-origin",
      headers: {
        "X-WP-Nonce": config.nonce,
      },
    });

    if (!response.ok) {
      return null;
    }

    return response.json();
  }

  function hydrateFullMockStrip(state) {
    const strip = document.querySelector("[data-mdsca-full-mock-strip]");

    if (!strip || !state || !state.cta || !state.cta.full_mock) {
      return;
    }

    const cta = state.cta.full_mock;
    const button = strip.querySelector("[data-mdsca-full-mock-cta]");
    const note = strip.querySelector("[data-mdsca-full-mock-note]");

    strip.setAttribute("data-mdsca-state", cta.state || "");

    if (button) {
      button.textContent = cta.label || button.textContent;
      button.setAttribute("href", cta.target || button.getAttribute("href"));
    }

    if (note) {
      if (cta.note) {
        note.textContent = cta.note;
      } else if (state.credits && typeof state.credits.available === "number" && cta.state === "start_mock") {
        note.textContent = "You have " + state.credits.available + " AI credits available. Full Mock SCA uses 12 credits.";
      } else if (cta.state === "resume_mock") {
        note.textContent = "You have an active Full Mock SCA in progress.";
      }
    }
  }

  document.addEventListener("DOMContentLoaded", async () => {
    const state = await fetchState();
    hydrateFullMockStrip(state);
  });
})();
