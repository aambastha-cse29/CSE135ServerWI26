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
        url: window.location.href,  // redundant with "page" in payload, but useful to have in static for easy querying
        title: document.title,   // "which page the user was on" (redundant with payload.page but useful for querying)
        userAgent: navigator.userAgent,  // "what browser + version + OS"
        language: navigator.language,  // "user's preferred language"
        referrer: document.referrer,  // "where they came from (if anywhere)" 
        platform: navigator.platform,  // "user's platform (e.g., Win32, MacIntel)" 
        screenWidth: window.screen.width,  // "total screen width in pixels"
        screenHeight: window.screen.height,  // "total screen height in pixels" 
        viewportWidth: window.innerWidth,  // "viewport width in pixels (excludes devtools, scrollbar, etc.)"
        viewportHeight: window.innerHeight,  // "viewport height in pixels (excludes devtools, scrollbar, etc.)"
        timestamp: new Date().toISOString(),
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone, // "user's timezone"
        cssEnabled: isCSSenabled(),  // "are CSS rules applying (proxy for CSS support and permissions)"
        imageAllowed: await isImageAllowed(),  // "can we load images (proxy for image permissions and blockers)"
        jsEnabled: true, // if this script runs, JS is enabled
        technology:  getNetworkInfo(), // "network info (effectiveType, downlink, rtt, saveData)"
        cookieEnabled: navigator.cookieEnabled,
        cores: navigator.hardwareConcurrency || 0, // number of logical CPU cores
        deviceMemory: navigator.deviceMemory || 0  // in GB, rounded down
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
  let clsObserver = null; // CLS is sum of layout shifts in "session windows" (max 1s gap, max 5s total window), excluding shifts from user input.
  try {
    clsObserver = new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        // Ignore shifts from user input
        if (entry.hadRecentInput) continue;

        const time = entry.startTime;

        // Session window: max 1s gap, max 5s total window
        const withinGap = (time - clsSessionLast) <= 1000; //     1s from last shift in session
        const withinWindow = (time - clsSessionStart) <= 5000; // 5s from first shift in session

        if (!clsSessionEntries.length || (withinGap && withinWindow)) {
          // same session
          clsSessionEntries.push(entry); // we could store these details if needed for debugging, but for now we just keep the running total
          clsSessionLast = time; // update last shift time
          if (!clsSessionStart) clsSessionStart = time;
          clsSessionValue += entry.value; // accumulate shift value for session
        } else {
          // new session
          clsSessionEntries = [entry];
          clsSessionStart = time;
          clsSessionLast = time;
          clsSessionValue = entry.value;
        }

        vitals.cls = Math.max(vitals.cls, clsSessionValue); // CLS is max session value
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

    inpObserver.observe({ type: "event", buffered: true, durationThreshold: 1});
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
      lcpEntry: vitals.lcpEntry, // details about the LCP element (tag, url, size)
      inpEntry: vitals.inpEntry // details about the worst INP interaction (name, duration, startTime)
    };
  }

  return { finalize }; // call this after page load to get final vitals values
}


// =====================================================
// PERFORMANCE DATA
// =====================================================
async function collectPerformanceData() {
    await afterPageLoad(); // Ensure loadEventEnd is populated

    const nav = performance.getEntriesByType("navigation")[0];
    if (!nav) return {};

    const ttfb = (nav.requestStart && nav.requestStart > 0) ? (nav.responseStart - nav.requestStart) : (nav.responseStart - nav.fetchStart);
    const timingObj = nav.toJSON() ? nav.toJSON() : { ...nav };
    delete timingObj.nextHopProtocol;

    const performanceData = {
        "dnslookupTime": nav.domainLookupEnd - nav.domainLookupStart, // ms, 0 if cached
        "tcpConnectionTime": nav.connectEnd - nav.connectStart, // ms, 0 if cached or connection reused
        "ttfb": ttfb, // ms, time to first byte
        "transferSize": nav.transferSize,  // bytes, 0 if cached, includes headers
        "pageLoadStart": nav.fetchStart,  // ms, when navigation started
        "pageLoadEnd": nav.loadEventEnd, // ms, when load event finished
        "pageLoadTime": nav.loadEventEnd - nav.fetchStart,  // ms, total time from navigation start to load event end
        "timingObject": timingObj  // full navigation timing object (minus nextHopProtocol which can be large and isn't critical for analysis)
    };

    return performanceData; // "performance collected after the page has loaded, includes navigation timing details"
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
  const mouseSamples = []; // each has t + coordinates
  const scrollSamples = []; // each has t + coordinates
  const clickEvents = [];  // capped
  const keyEvents = [];     // capped
  const errorEvents = [];   // capped
  const idlePeriods = [];  // each has endedAt + durationMs

  // For sampling
  let lastMouseSampleAt = 0;  // ms
  let lastScrollSampleAt = 0; // ms

  const now = () => Date.now(); // helper for easier testing/mocking

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
  const idleInterval = setInterval(() => { // check for idle every 250ms (can be tuned)
    const t = now();
    if (idleStartAt === null && (t - lastActivityAt) >= IDLE_THRESHOLD_MS) {
      // Break starts right after last activity
      idleStartAt = lastActivityAt;
    }
  }, 250);

  // ========= Errors + Resource failures =========
  window.addEventListener("error", (event) => { // This captures both JS errors and resource load failures, but we can distinguish by checking event instanceof ErrorEvent (JS error) vs. event.target (resource error).
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
        type: "js_error", // includes syntax errors, runtime errors, etc.
        ts: now(),
        message: event.message || "Unknown error",
        source: event.filename || null,
        lineno: event.lineno || null,
        colno: event.colno || null,
        stack: event.error?.stack ? String(event.error.stack) : null
      });
    }
  }, true); // CAPTURE PHASE REQUIRED

  window.addEventListener("unhandledrejection", (ev) => { // Unhandled promise rejections can be as important as regular errors, and often indicate missing error handling for async code.
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
  window.addEventListener("mousemove", (e) => { // mousemove can be very frequent, so we sample
    markActivity();
    const t = now();
    if (t - lastMouseSampleAt < MOUSE_SAMPLE_MS) return;
    lastMouseSampleAt = t;

    if (mouseSamples.length < MAX_MOUSE_SAMPLES) {
      mouseSamples.push({ t, x: e.clientX, y: e.clientY });
    }
  }, { passive: true });

  window.addEventListener("click", (e) => { // clicks are discrete enough that we can capture all without sampling
    markActivity();
    const t = now();

    if (clickEvents.length < MAX_CLICKS) {
      clickEvents.push({ t, x: e.clientX, y: e.clientY, button: e.button });
    }
  }, { passive: true });

  // ========= Scroll =========
  window.addEventListener("scroll", () => { // scroll events can be very frequent, so we sample
    markActivity();
    const t = now();
    if (t - lastScrollSampleAt < SCROLL_SAMPLE_MS) return;
    lastScrollSampleAt = t;

    if (scrollSamples.length < MAX_SCROLL_SAMPLES) {
      scrollSamples.push({ t, scrollX: window.scrollX, scrollY: window.scrollY });
    }
  }, { passive: true });

  // ========= Keyboard (sanitized) =========
  const sanitizeKey = (e) => (e.key && e.key.length === 1 ? "CHAR" : (e.key || "")); // "CHAR" for any single character (prevents sensitive data), otherwise use the key name (e.g., "Enter", "Backspace"), or empty string if missing

  window.addEventListener("keydown", (e) => { // key events can be very frequent, so we cap them; also sanitize to prevent sensitive data leaks
    markActivity();
    if (keyEvents.length < MAX_KEY_EVENTS) {
      keyEvents.push({
        type: "keydown", // keydown vs. keyup
        t: now(),
        key: sanitizeKey(e), // sanitized key value
        code: e.code,
        ctrlKey: !!e.ctrlKey, //  using !! to ensure boolean values (in case of undefined)
        altKey: !!e.altKey, // using !! to ensure boolean values (in case of undefined)
        shiftKey: !!e.shiftKey, //  using !! to ensure boolean values (in case of undefined)
        metaKey: !!e.metaKey, // using !! to ensure boolean values (in case of undefined)
        repeat: !!e.repeat // using !! to ensure boolean values (in case of undefined)
      });
    }
  });

  window.addEventListener("keyup", (e) => { // key events can be very frequent, so we cap them; also sanitize to prevent sensitive data leaks
    markActivity();
    if (keyEvents.length < MAX_KEY_EVENTS) {
      keyEvents.push({
        type: "keyup", // keyup vs. keydown
        t: now(),
        key: sanitizeKey(e),// sanitized key value
        code: e.code,
        ctrlKey: !!e.ctrlKey, // using !! to ensure boolean values (in case of undefined)
        altKey: !!e.altKey, // using !! to ensure boolean values (in case of undefined)
        shiftKey: !!e.shiftKey, // using !! to ensure boolean values (in case of undefined)
        metaKey: !!e.metaKey, // using !! to ensure boolean values (in case of undefined)
        repeat: !!e.repeat // using !! to ensure boolean values (in case of undefined)
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
        mousemove_samples: mouseSamples.length, // number of mousemove samples collected
        scroll_samples: scrollSamples.length, // number of scroll samples collected
        clicks: clickEvents.length,  // number of click events collected
        key_events: keyEvents.length, // number of keydown/keyup events collected 
        idle_periods: idlePeriods.length, // number of idle breaks detected
        error_events: errorEvents.length // number of error events collected (JS errors, unhandled rejections, resource failures)
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
    setTimeout(() => { // ensure this runs after the main thread is free (e.g., after load event handlers) so we capture final web vitals values
     const vitals = webVitals.finalize();
     sendType("performance", { ...perfData, webVitals: vitals });
    }, 5000); // delay can be tuned; we want to capture any late CLS shifts or INP interactions that happen shortly after load, but we don't want to delay too long
  });

  // ACTIVITY: on leave (pagehide) send one final payload
  window.addEventListener("pagehide", () => {
     sendType("activity", activityLogger.getPayload());
  }, { once: true });

})();




