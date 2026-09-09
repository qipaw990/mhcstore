// Web implementation using dart:js and dart:js_util
// ignore: avoid_web_libraries_in_flutter
import 'dart:js' as js;
// ignore: avoid_web_libraries_in_flutter
import 'dart:js_util' as js_util;

void unlockWebAudio() {
  try {
    js.context.callMethod('eval', ["""
      (function() {
        try {
          var AudioCtx = window.AudioContext || window.webkitAudioContext;
          if (AudioCtx) {
            if (!window.__cgo_audio_ctx || window.__cgo_audio_ctx.state === 'closed') {
              window.__cgo_audio_ctx = new AudioCtx();
            }
            if (window.__cgo_audio_ctx.state === 'suspended') {
              window.__cgo_audio_ctx.resume();
            }
          }
        } catch(e) {}
      })()
    """]);
  } catch (_) {}
}

void activateWebRtcAudio(dynamic stream) {
  try {
    // 1. Unhide flutter_webrtc audio element manager div and play all audio tags
    js.context.callMethod('eval', ["""
      (function() {
        try {
          var mgr = document.getElementById('html_webrtc_audio_manager_list');
          if (mgr) {
            mgr.style.display = 'block';
            mgr.style.position = 'fixed';
            mgr.style.bottom = '0px';
            mgr.style.right = '0px';
            mgr.style.width = '1px';
            mgr.style.height = '1px';
            mgr.style.opacity = '0.001';
            mgr.style.pointerEvents = 'none';
          }
          var audios = document.querySelectorAll('audio');
          audios.forEach(function(a) {
            a.muted = false;
            a.volume = 1.0;
            var p = a.play();
            if (p && p.catch) {
              p.catch(function() {});
            }
          });
        } catch(e) {}
      })()
    """]);

    // 2. Also attach directly via JS audio element if jsStream is accessible
    try {
      final jsStream = stream?.jsStream;
      if (jsStream != null) {
        final doc = js.context['document'];
        const elId = 'cgo_remote_audio_direct';
        var audioEl = doc.callMethod('getElementById', [elId]);
        if (audioEl == null) {
          audioEl = doc.callMethod('createElement', ['audio']);
          js_util.setProperty(audioEl, 'id', elId);
          js_util.setProperty(audioEl, 'autoplay', true);
          final style = js_util.getProperty(audioEl, 'style');
          js_util.setProperty(style, 'position', 'fixed');
          js_util.setProperty(style, 'bottom', '0px');
          js_util.setProperty(style, 'right', '0px');
          js_util.setProperty(style, 'width', '1px');
          js_util.setProperty(style, 'height', '1px');
          js_util.setProperty(style, 'opacity', '0.001');
          js_util.setProperty(style, 'pointerEvents', 'none');
          doc['body'].callMethod('appendChild', [audioEl]);
        }
        js_util.setProperty(audioEl, 'muted', false);
        js_util.setProperty(audioEl, 'srcObject', jsStream);
        final playPromise = audioEl.callMethod('play', []);
        if (playPromise != null) {
          js_util.promiseToFuture<void>(playPromise as Object).catchError((_) {});
        }
      }
    } catch (_) {}
  } catch (_) {}
}

void cleanupWebAudio() {
  try {
    js.context.callMethod('eval', ["""
      (function() {
        try {
          var el = document.getElementById('cgo_remote_audio_direct');
          if (el) {
            el.pause();
            el.srcObject = null;
            el.remove();
          }
        } catch(e) {}
      })()
    """]);
  } catch (_) {}
}
