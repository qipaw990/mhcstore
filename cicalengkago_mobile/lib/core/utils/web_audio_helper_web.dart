// Web-only implementation — hybrid approach:
//   dart:js_interop + package:web  → to properly access MediaStreamWeb.jsStream
//   dart:js                        → to manage AudioContext (no extension types needed)
//
// ignore: avoid_web_libraries_in_flutter
import 'dart:async';
// ignore: avoid_web_libraries_in_flutter
import 'dart:js' as js;
// ignore: avoid_web_libraries_in_flutter
import 'dart:js_interop';

import 'package:web/web.dart' as web;
import 'package:dart_webrtc/dart_webrtc.dart' show MediaStreamWeb;

// ---------------------------------------------------------------------------
// Unlock the browser AudioContext (call from a user-gesture handler)
// ---------------------------------------------------------------------------
void unlockWebAudio() {
  try {
    js.context.callMethod('eval', ["""
      (function() {
        try {
          var AudioCtx = window.AudioContext || window.webkitAudioContext;
          if (!AudioCtx) return;
          if (!window.__cgo_audio_ctx || window.__cgo_audio_ctx.state === 'closed') {
            window.__cgo_audio_ctx = new AudioCtx();
          }
          if (window.__cgo_audio_ctx.state === 'suspended') {
            window.__cgo_audio_ctx.resume();
          }
          // Play a silent 1-sample buffer to permanently unlock
          try {
            var buf = window.__cgo_audio_ctx.createBuffer(1, 1, 22050);
            var src = window.__cgo_audio_ctx.createBufferSource();
            src.buffer = buf;
            src.connect(window.__cgo_audio_ctx.destination);
            src.start(0);
          } catch(e) {}
        } catch(e) {}
      })()
    """]);
  } catch (_) {}
}

// ---------------------------------------------------------------------------
// Activate remote audio after WebRTC connection is established
// ---------------------------------------------------------------------------
void activateWebRtcAudio(dynamic stream) {
  try {
    // 0. Ensure AudioContext is ready now (will be used by gain wiring)
    unlockWebAudio();

    // 1. Force-play all existing <audio> elements
    _nudgeAllAudioElements();

    // 2. Extract native JS MediaStream from flutter_webrtc's Dart wrapper
    if (stream is MediaStreamWeb) {
      final jsStream = stream.jsStream; // web.MediaStream (dart:js_interop)

      // Enable all audio tracks aggressively
      try {
        final tracks = jsStream.getAudioTracks().toDart;
        for (final track in tracks) {
          try { track.enabled = true; } catch (_) {}
          // Also re-apply every 500ms for 3s (Chrome sometimes silently disables)
          for (var i = 1; i <= 6; i++) {
            Future.delayed(Duration(milliseconds: 500 * i), () {
              try { track.enabled = true; } catch (_) {}
            });
          }
        }
      } catch (_) {}

      _attachStreamToAudioElement(jsStream);
    } else {
      _nudgeAudioElementsWithStream();
    }

    // 3. Retry everything once after 2s (catches race where RTCVideoView hasn't attached yet)
    Future.delayed(const Duration(seconds: 2), () {
      try {
        _nudgeAllAudioElements();
        if (stream is MediaStreamWeb) {
          _wireWebAudioGainViaJsRecheck();
        }
      } catch (_) {}
    });
  } catch (_) {}
}

// ---------------------------------------------------------------------------
// Create / get dedicated off-screen <audio> element and wire stream to it
// ---------------------------------------------------------------------------
void _attachStreamToAudioElement(web.MediaStream jsStream) {
  try {
    const elId = 'cgo_remote_audio_direct';

    // Get or create the audio element
    web.HTMLAudioElement? audioEl =
        web.document.getElementById(elId) as web.HTMLAudioElement?;
    if (audioEl == null) {
      audioEl = web.HTMLAudioElement();
      audioEl.id = elId;
      audioEl.autoplay = true;
      audioEl.muted = false;
      audioEl.setAttribute('playsinline', '');
      audioEl.setAttribute('webkit-playsinline', '');
      audioEl.setAttribute('x-webkit-airplay', 'allow');
      // Keep it at least 2x2 px (Safari/Chrome on Android treat <2x2 as invisible -> no play)
      audioEl.style.position = 'fixed';
      audioEl.style.bottom = '0px';
      audioEl.style.right = '0px';
      audioEl.style.width = '2px';
      audioEl.style.height = '2px';
      audioEl.style.opacity = '0.001';
      audioEl.style.pointerEvents = 'none';
      audioEl.style.display = 'block';
      audioEl.style.visibility = 'visible';
      audioEl.style.zIndex = '-1';
      web.document.body?.appendChild(audioEl);
    }

    // Clear src attribute only if present (don't set .src='' — breaks Firefox/Safari)
    try {
      if (audioEl.hasAttribute('src')) {
        audioEl.removeAttribute('src');
      }
    } catch (_) {}

    audioEl.muted = false;
    audioEl.volume = 1.0;
    try { audioEl.defaultMuted = false; } catch (_) {}
    audioEl.srcObject = jsStream;

    // Unhide flutter_webrtc's internal audio manager (if present)
    try {
      final mgr = web.document.getElementById('html_webrtc_audio_manager_list');
      if (mgr != null) {
        final mgrHtml = mgr as web.HTMLElement;
        mgrHtml.style.position = 'fixed';
        mgrHtml.style.bottom = '0px';
        mgrHtml.style.right = '0px';
        mgrHtml.style.width = '2px';
        mgrHtml.style.height = '2px';
        mgrHtml.style.opacity = '0.001';
        mgrHtml.style.pointerEvents = 'none';
        mgrHtml.style.display = 'block';
        mgrHtml.style.visibility = 'visible';
        // Force all child audio elements inside the manager: unmute + play
        try {
          final mgrAudios = mgr.querySelectorAll('audio');
          for (var i = 0; i < mgrAudios.length; i++) {
            try {
              final inner = mgrAudios.item(i) as web.HTMLAudioElement;
              inner.muted = false;
              inner.volume = 1.0;
              inner.style.display = 'block';
              inner.style.visibility = 'visible';
              inner.play().toDart.catchError((_) {});
            } catch (_) {}
          }
        } catch (_) {}
      }
    } catch (_) {}

    // Start playback with aggressive retries
    final captured = audioEl;

    // Inline retry function with 8 attempts
    void attemptPlay(int attempt) {
      if (attempt > 10) {
        // Final fallback: schedule user-interaction play forever
        _scheduleRetryPlay(captured);
        return;
      }
      try {
        captured.play().toDart.then((_) {
          // Success! Wire WebAudio gain pipeline now
          _wireWebAudioGainViaJs(captured);
        }).catchError((_) {
          Future.delayed(Duration(milliseconds: 250), () => attemptPlay(attempt + 1));
        });
      } catch (_) {
        Future.delayed(Duration(milliseconds: 250), () => attemptPlay(attempt + 1));
      }
    }

    attemptPlay(0);
  } catch (_) {}
}

// ---------------------------------------------------------------------------
// Re-check & re-wire WebAudio gain pipeline (called after 2s delay)
// ---------------------------------------------------------------------------
void _wireWebAudioGainViaJsRecheck() {
  try {
    js.context.callMethod('eval', ["""
      (function() {
        try {
          var el = document.getElementById('cgo_remote_audio_direct');
          if (!el || !el.srcObject) return;
          if (el.paused || el.muted || el.volume < 0.5) {
            el.muted = false;
            el.volume = 1.0;
            el.play().catch(function(){});
            console.log('[CGO Recheck] Audio element was paused — forced play');
          }
        } catch(e) { console.warn('[CGO Recheck] error:', e); }
      })()
    """]);
  } catch (_) {}
}

// ---------------------------------------------------------------------------
// Wire remote audio through a WebAudio GainNode (2.2x boost + DYNAMIC COMPRESSOR) via dart:js eval
// ---------------------------------------------------------------------------
void _wireWebAudioGainViaJs(web.HTMLAudioElement audioEl) {
  try {
    // Guard: only wire once
    if (audioEl.getAttribute('data-cgo-wired') == 'true') return;
    audioEl.setAttribute('data-cgo-wired', 'true');

    js.context.callMethod('eval', ["""
      (function() {
        try {
          var el = document.getElementById('cgo_remote_audio_direct');
          if (!el || !el.srcObject) return;
          var AudioCtx = window.AudioContext || window.webkitAudioContext;
          if (!window.__cgo_audio_ctx || window.__cgo_audio_ctx.state === 'closed') {
            window.__cgo_audio_ctx = new AudioCtx();
          }
          var ctx = window.__cgo_audio_ctx;
          if (ctx.state === 'suspended') ctx.resume();

          // 1. Resume on user gesture (for browsers that require it)
          var doResume = function() {
            try {
              if (ctx.state === 'suspended') ctx.resume();
              if (el && el.srcObject && el.paused) {
                el.muted = false;
                el.volume = 1.0;
                el.play().catch(function(){});
              }
            } catch(_){}
            document.removeEventListener('click', doResume);
            document.removeEventListener('touchstart', doResume);
            document.removeEventListener('keydown', doResume);
          };
          document.addEventListener('click', doResume);
          document.addEventListener('touchstart', doResume);
          document.addEventListener('keydown', doResume);

          // 2. Build audio graph: Source -> Gain(2.2x) -> Compressor -> Destination
          var src = ctx.createMediaStreamSource(el.srcObject);

          var gain = ctx.createGain();
          try { gain.gain.setValueAtTime(2.2, ctx.currentTime); }
          catch(_) { gain.gain.value = 2.2; }

          // Dynamic range compressor prevents clipping and normalizes quiet speech
          try {
            var comp = ctx.createDynamicsCompressor();
            comp.threshold.value = -26;
            comp.knee.value      = 30;
            comp.ratio.value     = 14;
            comp.attack.value    = 0.003;
            comp.release.value   = 0.25;
            src.connect(gain);
            gain.connect(comp);
            comp.connect(ctx.destination);
            console.log('[CGO] WebAudio pipeline OK: Gain=2.2x + Compressor + Speaker');
          } catch(nocomp) {
            src.connect(gain);
            gain.connect(ctx.destination);
            console.log('[CGO] WebAudio pipeline OK (no compressor): Gain=2.2x');
          }
        } catch(e) {
          console.warn('[CGO] WebAudio gain wiring error:', e);
        }
      })()
    """]);
  } catch (_) {}
}

// ---------------------------------------------------------------------------
// Retry play() after user interaction (bypass autoplay block)
// ---------------------------------------------------------------------------
void _scheduleRetryPlay(web.HTMLAudioElement audioEl) {
  try {
    js.context.callMethod('eval', ["""
      (function() {
        function retryPlay() {
          var el = document.getElementById('cgo_remote_audio_direct');
          if (el && el.srcObject) {
            el.muted = false;
            el.volume = 1.0;
            el.play().catch(function(){});
          }
          document.removeEventListener('click', retryPlay);
          document.removeEventListener('touchstart', retryPlay);
        }
        document.addEventListener('click', retryPlay);
        document.addEventListener('touchstart', retryPlay);
      })()
    """]);
  } catch (_) {}
}

// ---------------------------------------------------------------------------
// Force-play ALL <audio> elements in the DOM
// ---------------------------------------------------------------------------
void _nudgeAllAudioElements() {
  try {
    final audios = web.document.querySelectorAll('audio');
    for (var i = 0; i < audios.length; i++) {
      try {
        final audio = audios.item(i) as web.HTMLAudioElement;
        audio.muted = false;
        audio.volume = 1.0;
        audio.play().toDart.catchError((_) {});
      } catch (_) {}
    }
  } catch (_) {}
}

// ---------------------------------------------------------------------------
// Nudge audio elements that already have a srcObject attached
// ---------------------------------------------------------------------------
void _nudgeAudioElementsWithStream() {
  try {
    final audios = web.document.querySelectorAll('audio');
    for (var i = 0; i < audios.length; i++) {
      try {
        final audio = audios.item(i) as web.HTMLAudioElement;
        if (audio.srcObject != null) {
          audio.muted = false;
          audio.volume = 1.0;
          audio.play().toDart.catchError((_) {});
        }
      } catch (_) {}
    }
  } catch (_) {}
}

// ---------------------------------------------------------------------------
// Cleanup on call end
// ---------------------------------------------------------------------------
void cleanupWebAudio() {
  try {
    final el = web.document.getElementById('cgo_remote_audio_direct')
        as web.HTMLAudioElement?;
    if (el != null) {
      el.pause();
      el.srcObject = null;
      el.removeAttribute('data-cgo-wired');
      el.remove();
    }
  } catch (_) {}
}
