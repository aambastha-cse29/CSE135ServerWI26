// =====================================================
// CONFIG
// =====================================================
const LOG_ENDPOINT = "https://collector.cse135wi2026.site/collect.php";
const SESSION_KEY = "cse135_sid";

// Activity sampling caps (prevents huge DB payloads)
const MOUSE_SAMPLE_MS = 100;
const SCROLL_SAMPLE_MS = 100;
const MAX_MOUSE_SAMPLES = 200;
const MAX_SCROLL_SAMPLES = 200;
const MAX_CLICKS = 200;
const MAX_KEY_EVENTS = 200;
const MAX_ERROR_EVENTS = 100;

// Idle threshold
const IDLE_THRESHOLD_MS = 2000;


// =====================================================
// SESSION MANAGEMENT (localStorage-based)
// =====================================================

// Strong random ID fallback (in case randomUUID missing)
function generateId() {
  if (crypto?.randomUUID) return crypto.randomUUID();
  const arr = crypto.getRandomValues(new Uint8Array(16));
  return [...arr].map(b => b.toString(16).padStart(2, "0")).join("");
}

// Only used as fallback if server session init fails
function getOrCreateLocalSid() {
  let sid = null;
  try { sid = localStorage.getItem(SESSION_KEY); } catch {}

  if (!sid) {
    sid = generateId();
    try { localStorage.setItem(SESSION_KEY, sid); } catch {}
  }
  return sid;
}

// Main: ask server to validate/refresh/rotate sid
async function initSidFromServer() {
  let existingSid = null;
  try { existingSid = localStorage.getItem(SESSION_KEY); } catch {}

  try {
    const res = await fetch("https://collector.cse135wi2026.site/session.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      // send current sid (or null) so server can refresh or rotate
      body: JSON.stringify({ sid: existingSid }),
      credentials: "omit", // fine since we're not using cookies for sessioning
      cache: "no-store"
    });

    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const sidFromHeader = res.headers.get("X-CSE135-SID");

    if (sidFromHeader) {
      try { localStorage.setItem(SESSION_KEY, sidFromHeader); } catch {}
      return sidFromHeader;
    }

    throw new Error("No sid in response");
  } catch (e) {
    console.warn("Failed to initialize sid from server; falling back:", e);
    return getOrCreateLocalSid();
  }
}


let sid = null;
let sidReady = null;

function ensureSid() {
  if (!sidReady) {
    sidReady = (async () => {
      sid = await initSidFromServer();
      return sid;
    })();
  }
  return sidReady;
}

ensureSid();


// =====================================================
// SEND HELPER (one row per type)
// =====================================================
async function sendType(type, payload) {
  const ensuredSid = sid || await ensureSid();
  const body = {
    ensuredSid,
    ts: Date.now(),        // client timestamp (ms since epoch)
    page: location.href,   // "which page the user was on"
    [type]: payload
  };

  const json = JSON.stringify(body);

  if (navigator.sendBeacon) {
    navigator.sendBeacon(LOG_ENDPOINT, new Blob([json], { type: "application/json" }));
  } 
  
  else {
    fetch(LOG_ENDPOINT, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: json,
      keepalive: true,
      credentials: "omit"
    }).catch(() => {});
  }
}


// =====================================================
// LOAD BARRIER (ensures loadEventEnd is populated)
// =====================================================
function afterPageLoad() {
  return new Promise((resolve) => {
    const run = () => setTimeout(resolve, 0);
    if (document.readyState === "complete") run();
    else window.addEventListener("load", run, { once: true });
  });
}


// =====================================================
// STATIC DATA
// =====================================================
function getNetworkInfo() {
  const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
  if (!conn) return null;
  return {
    effectiveType: conn.effectiveType ?? null,
    downlink: conn.downlink ?? null,
    rtt: conn.rtt ?? null,
    saveData: conn.saveData ?? null
  };
}

// NOTE: CSS "enabled" isn't a direct permission; this checks whether CSS rules apply.
// Make sure your page has a CSS rule for .css-probe in your stylesheet:
// .css-probe { position: absolute; left: -9999px; }
function isCSSenabled() {
  const el = document.createElement("div");
  el.className = "css-probe";
  document.body.appendChild(el);
  const applied = window.getComputedStyle(el).position === "absolute";
  el.remove();
  return applied;
}

function isImageAllowed() {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => resolve(true);
    img.onerror = () => resolve(false);
    img.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";
  });
}

async function collectStaticData() {
  await afterPageLoad(); // "static collected after the page has loaded"

 const staticData = {
        url: window.location.href,
        title: document.title,
        userAgent: navigator.userAgent,
        language: navigator.language,
        referrer: document.referrer,
        platform: navigator.platform,
        screenWidth: window.screen.width,
        screenHeight: window.screen.height,
        viewportWidth: window.innerWidth,
        viewportHeight: window.innerHeight,
        timestamp: new Date().toISOString(),
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        cssEnabled: isCSSenabled(),
        imageAllowed: await isImageAllowed(),
        jsEnabled: true, // if this script runs, JS is enabled
        technology:  getNetworkInfo(),
        cookieEnabled: navigator.cookieEnabled,
        cores: navigator.hardwareConcurrency || 0,
        deviceMemory: navigator.deviceMemory || 0
    };

    return staticData;
}


// =====================================================
// CORE WEB VITALS via PerformanceObserver (LCP, CLS, INP)
// =====================================================
function startWebVitals() {
  const vitals = {
    lcp: null,            // ms
    cls: 0,               // unitless
    inp: null,            // ms
    lcpEntry: null,
    inpEntry: null
  };

  let clsSessionValue = 0;
  let clsSessionEntries = [];
  let clsSessionStart = 0;
  let clsSessionLast = 0;

  // ---- LCP ----
  let lcpObserver = null;
  try {
    lcpObserver = new PerformanceObserver((list) => {
      const entries = list.getEntries();
      const last = entries[entries.length - 1];
      if (!last) return;

      // LCP is last render candidate; ignore if page hidden (per spec guidance)
      vitals.lcp = last.startTime;
      vitals.lcpEntry = {
        startTime: last.startTime,
        element: last.element ? last.element.tagName : null,
        url: last.url || null,
        size: last.size || null
      };
    });
    lcpObserver.observe({ type: "largest-contentful-paint", buffered: true });
  } catch (_) {}

  // ---- CLS ----
  let clsObserver = null;
  try {
    clsObserver = new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        // Ignore shifts from user input
        if (entry.hadRecentInput) continue;

        const time = entry.startTime;

        // Session window: max 1s gap, max 5s total window
        const withinGap = (time - clsSessionLast) <= 1000;
        const withinWindow = (time - clsSessionStart) <= 5000;

        if (!clsSessionEntries.length || (withinGap && withinWindow)) {
          // same session
          clsSessionEntries.push(entry);
          clsSessionLast = time;
          if (!clsSessionStart) clsSessionStart = time;
          clsSessionValue += entry.value;
        } else {
          // new session
          clsSessionEntries = [entry];
          clsSessionStart = time;
          clsSessionLast = time;
          clsSessionValue = entry.value;
        }

        vitals.cls = Math.max(vitals.cls, clsSessionValue);
      }
    });
    clsObserver.observe({ type: "layout-shift", buffered: true });
  } catch (_) {}

  // ---- INP ----
  // INP uses PerformanceEventTiming with duration; we take the max duration over interactions.
  // Some browsers might not support "event" entries or INP yet.
  let inpObserver = null;
  let maxINP = 0;

  try {
    inpObserver = new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        // entry is PerformanceEventTiming
        // duration is processing time; startTime marks event time.
        // We want worst-case (max) duration as proxy for INP.
        const d = entry.duration || 0;
        if (d > maxINP) {
          maxINP = d;
          vitals.inp = d;
          vitals.inpEntry = {
            name: entry.name || null,         // e.g., "click", "keydown"
            duration: d,
            startTime: entry.startTime
          };
        }
      }
    });

    // durationThreshold reduces spam; pick 16ms-ish (one frame) or higher.
    inpObserver.observe({ type: "event", buffered: true, durationThreshold: 16 });
  } catch (_) {
    // If not supported, vitals.inp stays null.
  }

  function finalize() {
    // Stop observers (best practice)
    try { lcpObserver?.disconnect(); } catch (_) {}
    try { clsObserver?.disconnect(); } catch (_) {}
    try { inpObserver?.disconnect(); } catch (_) {}

    return {
      lcp: vitals.lcp,     // ms
      cls: vitals.cls,     // unitless
      inp: vitals.inp,     // ms
      lcpEntry: vitals.lcpEntry,
      inpEntry: vitals.inpEntry
    };
  }

  return { finalize };
}


// =====================================================
// PERFORMANCE DATA
// =====================================================
async function collectPerformanceData() {
    await afterPageLoad(); // Ensure loadEventEnd is populated

    const nav = performance.getEntriesByType("navigation")[0];
    if (!nav) return {};

    const ttfb = (nav.requestStart && nav.requestStart > 0) ? (nav.responseStart - nav.requestStart) : (nav.responseStart - nav.fetchStart);

    const performanceData = {
        "dnslookupTime": nav.domainLookupEnd - nav.domainLookupStart,
        "tcpConnectionTime": nav.connectEnd - nav.connectStart,
        "ttfb": ttfb,
        "transferSize": nav.transferSize,
        "pageLoadStart": nav.fetchStart,
        "pageLoadEnd": nav.loadEventEnd,
        "pageLoadTime": nav.loadEventEnd - nav.fetchStart,
        "timingObject": nav.toJSON() ? nav.toJSON() : { ...nav }
    };

    return performanceData;
}


// =====================================================
// ACTIVITY LOGGER (continuous collection, summarized payload)
// Includes:
// - cursor positions (sampled)
// - clicks + button
// - scroll coordinates (sampled)
// - keydown/keyup (sanitized + capped)
// - idle breaks >= 2s: record when break ended + durationMs
// - page entered + left + time on page
// - JS errors + unhandledrejection + resource failures
// =====================================================
function startActivityLogger() {
  const pageEnterMs = Date.now();

  let leftAtMs = null;
  let leftReason = null;

  let lastActivityAt = pageEnterMs;
  let idleStartAt = null;

  // Samples / events we will persist
  const mouseSamples = [];
  const scrollSamples = [];
  const clickEvents = [];
  const keyEvents = [];     // capped
  const errorEvents = [];   // capped
  const idlePeriods = [];

  // For sampling
  let lastMouseSampleAt = 0;
  let lastScrollSampleAt = 0;

  const now = () => Date.now();

  function markActivity() {
    const t = now();

    // If currently idle, close the break when any activity resumes
    if (idleStartAt !== null) {
      const endedAt = t;
      const durationMs = endedAt - idleStartAt;
      idlePeriods.push({ endedAt, durationMs });
      idleStartAt = null;
    }

    lastActivityAt = t;
  }

  // Idle detection timer
  const idleInterval = setInterval(() => {
    const t = now();
    if (idleStartAt === null && (t - lastActivityAt) >= IDLE_THRESHOLD_MS) {
      // Break starts right after last activity
      idleStartAt = lastActivityAt;
    }
  }, 250);

  // ========= Errors + Resource failures =========
  window.addEventListener("error", (event) => {
    // Resource load failure (needs capture phase)
    if (!(event instanceof ErrorEvent)) {
      const t = event.target;
      if (errorEvents.length < MAX_ERROR_EVENTS) {
        errorEvents.push({
          type: "resource_error",
          ts: now(),
          tag: t?.tagName || null,
          url: t?.src || t?.href || null
        });
      }
      return;
    }

    // JS runtime error
    if (errorEvents.length < MAX_ERROR_EVENTS) {
      errorEvents.push({
        type: "js_error",
        ts: now(),
        message: event.message || "Unknown error",
        source: event.filename || null,
        lineno: event.lineno || null,
        colno: event.colno || null,
        stack: event.error?.stack ? String(event.error.stack) : null
      });
    }
  }, true); // CAPTURE PHASE REQUIRED

  window.addEventListener("unhandledrejection", (ev) => {
    const r = ev.reason;
    if (errorEvents.length < MAX_ERROR_EVENTS) {
      errorEvents.push({
        type: "unhandledrejection",
        ts: now(),
        message: r?.message ? String(r.message) : String(r),
        stack: r?.stack ? String(r.stack) : null
      });
    }
  });

  // ========= Mouse =========
  window.addEventListener("mousemove", (e) => {
    markActivity();
    const t = now();
    if (t - lastMouseSampleAt < MOUSE_SAMPLE_MS) return;
    lastMouseSampleAt = t;

    if (mouseSamples.length < MAX_MOUSE_SAMPLES) {
      mouseSamples.push({ t, x: e.clientX, y: e.clientY });
    }
  }, { passive: true });

  window.addEventListener("click", (e) => {
    markActivity();
    const t = now();

    if (clickEvents.length < MAX_CLICKS) {
      clickEvents.push({ t, x: e.clientX, y: e.clientY, button: e.button });
    }
  }, { passive: true });

  // ========= Scroll =========
  window.addEventListener("scroll", () => {
    markActivity();
    const t = now();
    if (t - lastScrollSampleAt < SCROLL_SAMPLE_MS) return;
    lastScrollSampleAt = t;

    if (scrollSamples.length < MAX_SCROLL_SAMPLES) {
      scrollSamples.push({ t, scrollX: window.scrollX, scrollY: window.scrollY });
    }
  }, { passive: true });

  // ========= Keyboard (sanitized) =========
  const sanitizeKey = (e) => (e.key && e.key.length === 1 ? "CHAR" : (e.key || ""));

  window.addEventListener("keydown", (e) => {
    markActivity();
    if (keyEvents.length < MAX_KEY_EVENTS) {
      keyEvents.push({
        type: "keydown",
        t: now(),
        key: sanitizeKey(e),
        code: e.code,
        ctrlKey: !!e.ctrlKey,
        altKey: !!e.altKey,
        shiftKey: !!e.shiftKey,
        metaKey: !!e.metaKey,
        repeat: !!e.repeat
      });
    }
  });

  window.addEventListener("keyup", (e) => {
    markActivity();
    if (keyEvents.length < MAX_KEY_EVENTS) {
      keyEvents.push({
        type: "keyup",
        t: now(),
        key: sanitizeKey(e),
        code: e.code,
        ctrlKey: !!e.ctrlKey,
        altKey: !!e.altKey,
        shiftKey: !!e.shiftKey,
        metaKey: !!e.metaKey,
        repeat: !!e.repeat
      });
    }
  });

  // ========= Leave tracking =========
  function markLeave(reason) {
    if (leftAtMs !== null) return; // already left
    leftAtMs = now();
    leftReason = reason;

    // If idle when leaving, close that break
    if (idleStartAt !== null) {
      const endedAt = leftAtMs;
      const durationMs = endedAt - idleStartAt;
      idlePeriods.push({ endedAt, durationMs });
      idleStartAt = null;
    }

    clearInterval(idleInterval);
  }

  window.addEventListener("pagehide", () => markLeave("pagehide"), { once: true });

  // Optional extra signal (helps if pagehide doesn't fire in some weird cases)
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "hidden") markLeave("visibility_hidden");
  });

  return {
    getPayload() {
      // Ensure leave time exists (e.g., if someone calls manually)
      if (leftAtMs === null) 
        markLeave("manual_finalize");

      // Counts are helpful too
      const eventCounts = {
        mousemove_samples: mouseSamples.length,
        scroll_samples: scrollSamples.length,
        clicks: clickEvents.length,
        key_events: keyEvents.length,
        idle_periods: idlePeriods.length,
        error_events: errorEvents.length
      };

      return {
        page: location.href,          // which page
        enteredAtMs: pageEnterMs,     // when user entered
        leftAtMs,                    // when user left
        leftReason,                  // why we think they left
        timeOnPageMs: leftAtMs - pageEnterMs,

        // required idle details
        idlePeriods, // each has endedAt + durationMs

        // required mouse details
        mouseSamples,   // cursor positions
        clickEvents,    // clicks + button
        scrollSamples,  // scroll coordinates

        // keyboard requirement
        keyEvents,      // keydown/keyup (sanitized)

        // thrown errors requirement
        errorEvents,    // js_error, unhandledrejection, resource_error

        // helpful summary
        eventCounts
      };
    }
  };
}


// =====================================================
// WIRING
// =====================================================

(async () => {
  await ensureSid(); // ensure sid is initialized before any sends
  const activityLogger = startActivityLogger();

  // STATIC: after load (one-time)
  collectStaticData().then((staticData) => {
    sendType("static", staticData);
  });

  // PERFORMANCE: after load (one-time)
  const webVitals = startWebVitals();
  collectPerformanceData().then((perfData) => {
     const vitals = webVitals.finalize();
     sendType("performance", { ...perfData, webVitals: vitals });
  });

  // ACTIVITY: on leave (pagehide) send one final payload
  window.addEventListener("pagehide", () => {
     sendType("activity", activityLogger.getPayload());
  }, { once: true });

})();




