<?php
// validate.php
// Field-by-field validation + sanitization helpers and per-type validators.

// --------------------
// Generic helpers
// --------------------
function v_is_assoc_array($v): bool {
  return is_array($v);
}

// strings
function v_str($v, int $maxLen = 255): ?string {
  if (!is_string($v)) return null;
  $v = trim($v);
  if ($v === '') return null;
  if (strlen($v) > $maxLen) $v = substr($v, 0, $maxLen);
  return $v;
}

function v_url($v, int $maxLen = 2048): ?string {
  $v = v_str($v, $maxLen);
  if ($v === null) return null;
  if (!preg_match('#^https?://#i', $v)) return null;
  return filter_var($v, FILTER_VALIDATE_URL) ? $v : null;
}

function v_lang($v, int $maxLen = 32): ?string {
  $v = v_str($v, $maxLen);
  if ($v === null) return null;
  // Basic BCP47-ish
  if (!preg_match('/^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})*$/', $v)) return null;
  return $v;
}

function v_timezone($v, int $maxLen = 64): ?string {
  $v = v_str($v, $maxLen);
  if ($v === null) return null;
  return in_array($v, timezone_identifiers_list(), true) ? $v : null;
}

// numbers
function v_int($v, int $min, int $max): ?int {
  if (is_int($v)) $i = $v;
  else if (is_string($v) && preg_match('/^-?\d+$/', $v)) $i = (int)$v;
  else return null;

  if ($i < $min) $i = $min;
  if ($i > $max) $i = $max;
  return $i;
}

function v_float($v, float $min, float $max): ?float {
  if (is_float($v) || is_int($v)) $f = (float)$v;
  else if (is_string($v) && is_numeric($v)) $f = (float)$v;
  else return null;

  if ($f < $min) $f = $min;
  if ($f > $max) $f = $max;
  return $f;
}

// booleans
function v_bool($v): ?bool {
  if (is_bool($v)) return $v;
  if (is_int($v)) return $v !== 0;
  if (is_string($v)) {
    $t = strtolower(trim($v));
    if ($t === 'true' || $t === '1') return true;
    if ($t === 'false' || $t === '0') return false;
  }
  return null;
}

// --------------------
// STATIC validator
// --------------------
// Matches your collector static payload keys:
// url,title,userAgent,language,referrer,platform,screenWidth,screenHeight,
// viewportWidth,viewportHeight,timestamp,timezone,cssEnabled,imageAllowed,jsEnabled,
// technology{effectiveType,downlink,rtt,saveData},cookieEnabled,cores,deviceMemory

function validate_static_payload($p): ?array {
  if (!v_is_assoc_array($p)) return null;

  // Require url for static
  $url = v_url($p['url'] ?? null, 2048);
  if ($url === null) return null;

  $out = [];
  $out['url']       = $url;
  $out['title']     = v_str($p['title'] ?? null, 512);
  $out['userAgent'] = v_str($p['userAgent'] ?? null, 512);
  $out['language']  = v_lang($p['language'] ?? null, 32);
  $out['referrer']  = v_url($p['referrer'] ?? null, 2048); // may be null
  $out['platform']  = v_str($p['platform'] ?? null, 128);

  // Timestamp + timezone (keep timestamp as string; could be ISO)
  $out['timestamp'] = v_str($p['timestamp'] ?? null, 64);
  $out['timezone']  = v_timezone($p['timezone'] ?? null, 64);

  // booleans
  $out['cookieEnabled'] = v_bool($p['cookieEnabled'] ?? null);
  $out['jsEnabled']     = v_bool($p['jsEnabled'] ?? null);
  $out['cssEnabled']    = v_bool($p['cssEnabled'] ?? null);
  $out['imageAllowed']  = v_bool($p['imageAllowed'] ?? null);

  // dimensions (clamp reasonable)
  $out['screenWidth']    = v_int($p['screenWidth'] ?? null, 0, 32767);
  $out['screenHeight']   = v_int($p['screenHeight'] ?? null, 0, 32767);
  $out['viewportWidth']  = v_int($p['viewportWidth'] ?? null, 0, 32767);
  $out['viewportHeight'] = v_int($p['viewportHeight'] ?? null, 0, 32767);

  // hardware-ish
  $out['cores']        = v_int($p['cores'] ?? null, 0, 256);
  $out['deviceMemory'] = v_float($p['deviceMemory'] ?? null, 0.0, 2048.0);

  // network info object
  $tech = $p['technology'] ?? null;
  if (is_array($tech)) {
    $out['technology'] = [
      'effectiveType' => v_str($tech['effectiveType'] ?? null, 32),
      'downlink'      => v_float($tech['downlink'] ?? null, 0.0, 10000.0),
      'rtt'           => v_int($tech['rtt'] ?? null, 0, 600000),
      'saveData'      => v_bool($tech['saveData'] ?? null),
    ];
  } else {
    $out['technology'] = null;
  }

  return $out;
}


function validate_performance_payload($p): ?array {
  if (!v_is_assoc_array($p)) return null;

  $out = [];

  // ---- Core numeric metrics ----
  // All timings are ms; clamp to sane ranges.
  $out['dnslookupTime']     = v_float($p['dnslookupTime'] ?? null, 0.0, 600000.0);
  $out['tcpConnectionTime'] = v_float($p['tcpConnectionTime'] ?? null, 0.0, 600000.0);
  $out['ttfb']              = v_float($p['ttfb'] ?? null, 0.0, 600000.0);

  // transferSize is bytes; can be 0 with caching; cap high.
  $out['transferSize']      = v_int($p['transferSize'] ?? null, 0, 1000000000);

  // Navigation timing anchors (ms since timeOrigin, usually floats)
  $out['pageLoadStart']     = v_float($p['pageLoadStart'] ?? null, 0.0, 36000000.0); // up to 10 hours
  $out['pageLoadEnd']       = v_float($p['pageLoadEnd'] ?? null, 0.0, 36000000.0);
  $out['pageLoadTime']      = v_float($p['pageLoadTime'] ?? null, 0.0, 36000000.0);

  // Require at least pageLoadStart/end to consider it valid
  if ($out['pageLoadStart'] === null || $out['pageLoadEnd'] === null) return null;

  // Optional consistency clamp: if end < start, null out pageLoadTime (don’t trust it)
  if ($out['pageLoadEnd'] < $out['pageLoadStart']) {
    $out['pageLoadTime'] = null;
  }

  // ---- timingObject ----
  // This can be large. We do not trust arbitrary keys.
  // Keep only a whitelist of common NavigationTiming fields if present.
  $timing = $p['timingObject'] ?? null;
  if (is_array($timing)) {
    $allowedTimingKeys = [
      'type','startTime',
      'fetchStart','domainLookupStart','domainLookupEnd',
      'connectStart','secureConnectionStart','connectEnd',
      'requestStart','responseStart','responseEnd',
      'domInteractive','domContentLoadedEventStart','domContentLoadedEventEnd',
      'domComplete','loadEventStart','loadEventEnd',
      'transferSize','encodedBodySize','decodedBodySize',
      'nextHopProtocol','workerStart','redirectStart','redirectEnd'
    ];

    $cleanTiming = [];
    foreach ($allowedTimingKeys as $k) {
      if (!array_key_exists($k, $timing)) continue;

      $v = $timing[$k];
      // numeric timing fields
      if (is_int($v) || is_float($v) || (is_string($v) && is_numeric($v))) {
        $cleanTiming[$k] = (float)$v;
        continue;
      }
      // small strings
      if (is_string($v)) {
        $cleanTiming[$k] = v_str($v, 64);
        continue;
      }
      // allow null
      if ($v === null) {
        $cleanTiming[$k] = null;
      }
    }

    $out['timingObject'] = $cleanTiming;
  } else {
    $out['timingObject'] = null;
  }

  // ---- webVitals ----
  $wv = $p['webVitals'] ?? null;
  if (is_array($wv)) {
    $cleanWV = [];

    // LCP ms, CLS unitless, INP ms
    $cleanWV['lcp'] = v_float($wv['lcp'] ?? null, 0.0, 36000000.0);
    $cleanWV['cls'] = v_float($wv['cls'] ?? null, 0.0, 100.0);
    $cleanWV['inp'] = v_float($wv['inp'] ?? null, 0.0, 36000000.0);

    // lcpEntry (small, optional)
    $lcpEntry = $wv['lcpEntry'] ?? null;
    if (is_array($lcpEntry)) {
      $cleanWV['lcpEntry'] = [
        'startTime' => v_float($lcpEntry['startTime'] ?? null, 0.0, 36000000.0),
        'element'   => v_str($lcpEntry['element'] ?? null, 64),
        'url'       => v_url($lcpEntry['url'] ?? null, 2048),
        'size'      => v_float($lcpEntry['size'] ?? null, 0.0, 1000000000.0),
      ];
    } else {
      $cleanWV['lcpEntry'] = null;
    }

    // inpEntry (small, optional)
    $inpEntry = $wv['inpEntry'] ?? null;
    if (is_array($inpEntry)) {
      $cleanWV['inpEntry'] = [
        'name'      => v_str($inpEntry['name'] ?? null, 64),      // click/keydown etc
        'duration'  => v_float($inpEntry['duration'] ?? null, 0.0, 36000000.0),
        'startTime' => v_float($inpEntry['startTime'] ?? null, 0.0, 36000000.0),
      ];
    } else {
      $cleanWV['inpEntry'] = null;
    }

    $out['webVitals'] = $cleanWV;
  } else {
    $out['webVitals'] = null;
  }

  return $out;
}

// --------------------
// ACTIVITY validator
// --------------------
// Matches activityLogger.getPayload() keys:
// page, enteredAtMs, leftAtMs, leftReason, timeOnPageMs,
// idlePeriods[]{endedAt, durationMs},
// mouseSamples[]{t, x, y},
// clickEvents[]{t, x, y, button},
// scrollSamples[]{t, scrollX, scrollY},
// keyEvents[]{type, t, key, code, ctrlKey, altKey, shiftKey, metaKey, repeat?},
// errorEvents[]{type, ts, ...},
// eventCounts{...}  <- recomputed server-side, not trusted from client

// Max ms timestamp: ~year 2286, avoids PHP_INT_MAX float precision issues
const TS_MAX_MS = 9999999999999.0;
// Max reasonable session duration: 7 days in ms
const SESSION_MAX_MS = 3600000.0; // 1 hour in ms

function validate_activity_payload($p): ?array {
  if (!v_is_assoc_array($p)) {
    return null;
  }

  $out = [];

  // ---- Page + timing ----
  $out['page']        = v_url($p['page'] ?? null, 2048);
  $out['enteredAtMs'] = v_float($p['enteredAtMs'] ?? null, 0.0, TS_MAX_MS);
  $out['leftAtMs']    = v_float($p['leftAtMs'] ?? null, 0.0, TS_MAX_MS);

  // Require these three to consider the record valid
  if ($out['page'] === null || $out['enteredAtMs'] === null || $out['leftAtMs'] === null) return null;

  $out['leftReason']   = v_str($p['leftReason'] ?? null, 64);

  // Recompute timeOnPageMs server-side for consistency; don't trust client value
  if ($out['leftAtMs'] >= $out['enteredAtMs']) {
    $out['timeOnPageMs'] = $out['leftAtMs'] - $out['enteredAtMs'];
    // Clamp to max session duration (7 days)
    if ($out['timeOnPageMs'] > SESSION_MAX_MS) $out['timeOnPageMs'] = SESSION_MAX_MS;
  } else {
    $out['timeOnPageMs'] = null;
  }

  // ---- Idle periods ----
  // JS has NO cap on idlePeriods, so we use a generous limit here
  $idleRaw = $p['idlePeriods'] ?? [];
  $idlePeriods = [];
  if (is_array($idleRaw)) {
    foreach (array_slice($idleRaw, 0, 10000) as $entry) {
      if (!is_array($entry)) continue;
      $endedAt    = v_float($entry['endedAt'] ?? null, 0.0, TS_MAX_MS);
      $durationMs = v_float($entry['durationMs'] ?? null, 0.0, SESSION_MAX_MS);
      if ($endedAt === null || $durationMs === null) continue;
      $idlePeriods[] = ['endedAt' => $endedAt, 'durationMs' => $durationMs];
    }
  }
  $out['idlePeriods'] = $idlePeriods;

  // ---- Mouse samples (JS cap: MAX_MOUSE_SAMPLES = 200) ----
  $mouseRaw = $p['mouseSamples'] ?? [];
  $mouseSamples = [];
  if (is_array($mouseRaw)) {
    foreach (array_slice($mouseRaw, 0, 200) as $entry) {
      if (!is_array($entry)) continue;
      $t = v_float($entry['t'] ?? null, 0.0, TS_MAX_MS);
      $x = v_int($entry['x'] ?? null, -32767, 32767);
      $y = v_int($entry['y'] ?? null, -32767, 32767);
      if ($t === null) continue;
      $mouseSamples[] = ['t' => $t, 'x' => $x, 'y' => $y];
    }
  }
  $out['mouseSamples'] = $mouseSamples;

  // ---- Click events (JS cap: MAX_CLICKS = 200) ----
  $clickRaw = $p['clickEvents'] ?? [];
  $clickEvents = [];
  if (is_array($clickRaw)) {
    foreach (array_slice($clickRaw, 0, 200) as $entry) {
      if (!is_array($entry)) continue;
      $t      = v_float($entry['t'] ?? null, 0.0, TS_MAX_MS);
      $x      = v_int($entry['x'] ?? null, -32767, 32767);
      $y      = v_int($entry['y'] ?? null, -32767, 32767);
      $button = v_int($entry['button'] ?? null, 0, 4); // MouseEvent.button: 0-4
      if ($t === null) continue;
      $clickEvents[] = ['t' => $t, 'x' => $x, 'y' => $y, 'button' => $button];
    }
  }
  $out['clickEvents'] = $clickEvents;

  // ---- Scroll samples (JS cap: MAX_SCROLL_SAMPLES = 200) ----
  $scrollRaw = $p['scrollSamples'] ?? [];
  $scrollSamples = [];
  if (is_array($scrollRaw)) {
    foreach (array_slice($scrollRaw, 0, 200) as $entry) {
      if (!is_array($entry)) continue;
      $t       = v_float($entry['t'] ?? null, 0.0, TS_MAX_MS);
      $scrollX = v_int($entry['scrollX'] ?? null, 0, 1000000);
      $scrollY = v_int($entry['scrollY'] ?? null, 0, 1000000);
      if ($t === null) continue;
      $scrollSamples[] = ['t' => $t, 'scrollX' => $scrollX, 'scrollY' => $scrollY];
    }
  }
  $out['scrollSamples'] = $scrollSamples;

  // ---- Key events (JS cap: MAX_KEY_EVENTS = 200) ----
  // JS sanitizes single chars to "CHAR"; named keys like "Enter", "ArrowDown" pass through as-is.
  $keyRaw = $p['keyEvents'] ?? [];
  $keyEvents = [];
  $allowedKeyTypes = ['keydown', 'keyup'];
  if (is_array($keyRaw)) {
    foreach (array_slice($keyRaw, 0, 200) as $entry) {
      if (!is_array($entry)) continue;

      $type = v_str($entry['type'] ?? null, 16);
      if (!in_array($type, $allowedKeyTypes, true)) continue;

      $t = v_float($entry['t'] ?? null, 0.0, TS_MAX_MS);
      if ($t === null) continue;

      $key = v_str($entry['key'] ?? null, 64);
      // Enforce sanitization contract: single-char values must be exactly "CHAR"
      if ($key !== null && mb_strlen($key) === 1 && $key !== 'CHAR') {
        $key = 'CHAR'; // re-sanitize if client missed it
      }

      $keyEvents[] = [
        'type'     => $type,
        't'        => $t,
        'key'      => $key,
        'code'     => v_str($entry['code'] ?? null, 64),
        'ctrlKey'  => v_bool($entry['ctrlKey'] ?? null) ?? false,
        'altKey'   => v_bool($entry['altKey'] ?? null) ?? false,
        'shiftKey' => v_bool($entry['shiftKey'] ?? null) ?? false,
        'metaKey'  => v_bool($entry['metaKey'] ?? null) ?? false,
        // 'repeat' only present on keydown in JS; default false for keyup
        'repeat'   => v_bool($entry['repeat'] ?? null) ?? false,
      ];
    }
  }
  $out['keyEvents'] = $keyEvents;

  // ---- Error events (JS cap: MAX_ERROR_EVENTS = 100) ----
  $errorRaw = $p['errorEvents'] ?? [];
  $errorEvents = [];
  $allowedErrorTypes = ['js_error', 'unhandledrejection', 'resource_error'];
  if (is_array($errorRaw)) {
    foreach (array_slice($errorRaw, 0, 100) as $entry) {
      if (!is_array($entry)) continue;

      $type = v_str($entry['type'] ?? null, 32);
      if (!in_array($type, $allowedErrorTypes, true)) continue;

      $ts = v_float($entry['ts'] ?? null, 0.0, TS_MAX_MS);
      if ($ts === null) continue;

      $clean = ['type' => $type, 'ts' => $ts];

      if ($type === 'resource_error') {
        $clean['tag'] = v_str($entry['tag'] ?? null, 32);
        // resource URLs may be data:, blob:, or relative — use v_str not v_url
        $clean['url'] = v_str($entry['url'] ?? null, 2048);
      } else {
        // js_error or unhandledrejection
        $clean['message'] = v_str($entry['message'] ?? null, 1024);
        // Stack traces can be long; 16384 to avoid truncating useful debug info
        $clean['stack']   = v_str($entry['stack'] ?? null, 16384);

        if ($type === 'js_error') {
          // event.filename can be blob:, file:, or just "inline" — use v_str not v_url
          $clean['source'] = v_str($entry['source'] ?? null, 2048);
          $clean['lineno'] = v_int($entry['lineno'] ?? null, 0, 1000000);
          $clean['colno']  = v_int($entry['colno'] ?? null, 0, 1000000);
        }
      }

      $errorEvents[] = $clean;
    }
  }
  $out['errorEvents'] = $errorEvents;

  // ---- Event counts: recomputed server-side from validated arrays ----
  // Never trust the client-reported counts; derive from what was actually accepted.
  $out['eventCounts'] = [
    'mousemove_samples' => count($out['mouseSamples']),
    'scroll_samples'    => count($out['scrollSamples']),
    'clicks'            => count($out['clickEvents']),
    'key_events'        => count($out['keyEvents']),
    'idle_periods'      => count($out['idlePeriods']),
    'error_events'      => count($out['errorEvents']),
  ];

  return $out;
}

?>

