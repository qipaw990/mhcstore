// Web-only implementation — hybrid approach:
//   dart:js_interop + package:web  → to properly access MediaStreamWeb.jsStream
//   dart:js                        → to manage AudioContext (no extension types needed)
//
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
    // 1. Force-play all existing <audio> elements
    _nudgeAllAudioElements();

    // 2. Extract native JS MediaStream from flutter_webrtc's Dart wrapper
    if (stream is MediaStreamWeb) {
      final jsStream = stream.jsStream; // web.MediaStream (dart:js_interop)

      // Enable all audio tracks
      try {
        final tracks = jsStream.getAudioTracks().toDart;
        for (final track in tracks) {
          track.enabled = true;
        }
      } catch (_) {}

      _attachStreamToAudioElement(jsStream);
    } else {
      _nudgeAudioElementsWithStream();
    }
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
      audioEl.setAttribute('playsinline', '');
      audioEl.style.position = 'fixed';
      audioEl.style.bottom = '0px';
      audioEl.style.right = '0px';
      audioEl.style.width = '1px';
      audioEl.style.height = '1px';
      audioEl.style.opacity = '0.001';
      audioEl.style.pointerEvents = 'none';
      audioEl.style.display = 'block';
      web.document.body?.appendChild(audioEl);
    }

    audioEl.muted = false;
    audioEl.volume = 1.0;
    audioEl.srcObject = jsStream;

    // Unhide flutter_webrtc's internal audio manager (if present)
    try {
      final mgr = web.document.getElementById('html_webrtc_audio_manager_list');
      if (mgr != null) {
        final mgrHtml = mgr as web.HTMLElement;
        mgrHtml.style.position = 'fixed';
        mgrHtml.style.bottom = '0px';
        mgrHtml.style.right = '0px';
        mgrHtml.style.width = '1px';
        mgrHtml.style.height = '1px';
        mgrHtml.style.opacity = '0.001';
        mgrHtml.style.pointerEvents = 'none';
        mgrHtml.style.display = 'block';
      }
    } catch (_) {}

    // Start playback
    final captured = audioEl;
    audioEl.play().toDart.then((_) {
      // Success: now pipe through WebAudio gain node for volume boost
      _wireWebAudioGainViaJs(captured);
    }).catchError((_) {
      // Autoplay blocked — retry on user interaction
      _scheduleRetryPlay(captured);
    });
  } catch (_) {}
}

// ---------------------------------------------------------------------------
// Wire remote audio through a WebAudio GainNode (1.5x boost) via dart:js eval
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
          var src = ctx.createMediaStreamSource(el.srcObject);
          var gain = ctx.createGain();
          gain.gain.setValueAtTime(1.5, ctx.currentTime);
          src.connect(gain);
          gain.connect(ctx.destination);
          console.log('[CGO] Remote audio wired through WebAudio gain node (1.5x)');
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
