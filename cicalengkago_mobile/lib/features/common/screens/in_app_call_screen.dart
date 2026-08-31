import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_webrtc/flutter_webrtc.dart';
import 'package:http/http.dart' as http;
import 'package:permission_handler/permission_handler.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:audioplayers/audioplayers.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/global_call_service.dart';
import '../../../core/theme/app_theme.dart';

class InAppCallScreen extends StatefulWidget {
  final String orderCode;
  final bool isIncoming;
  final String? callerRole;
  final String? initialPartnerName;
  final String? initialPartnerAvatar;
  final Map<String, dynamic>? callData;

  const InAppCallScreen({
    super.key,
    required this.orderCode,
    this.isIncoming = false,
    this.callerRole,
    this.initialPartnerName,
    this.initialPartnerAvatar,
    this.callData,
  });

  @override
  State<InAppCallScreen> createState() => _InAppCallScreenState();
}

class _InAppCallScreenState extends State<InAppCallScreen> with TickerProviderStateMixin {
  late AnimationController _pulseController;
  late Animation<double> _pulseAnimation;

  Timer? _statusTimer;
  Timer? _durationTimer;
  int _callDurationSeconds = 0;
  bool _isConnected = false;
  bool _isEnded = false;
  bool _isMuted = false;
  bool _isSpeakerOn = true;
  String _statusText = 'Memanggil...';
  int? _callId;

  String _partnerName = 'Pengguna';
  String _partnerAvatar = '';
  String _partnerPhone = '';

  // Audio Player for Ringing Sound
  AudioPlayer? _audioPlayer;

  // Native WebRTC Engine (In-House, 0 Third Party)
  final RTCVideoRenderer _remoteRenderer = RTCVideoRenderer();
  RTCPeerConnection? _peerConnection;
  MediaStream? _localStream;
  final Set<String> _sentCandidateKeys = {};
  final Set<String> _addedCandidateKeys = {};
  int _pollCount = 0;
  dynamic _pendingOffer;

  @override
  void initState() {
    super.initState();

    if (widget.initialPartnerName != null && widget.initialPartnerName!.isNotEmpty) {
      _partnerName = widget.initialPartnerName!;
    }
    if (widget.initialPartnerAvatar != null && widget.initialPartnerAvatar!.isNotEmpty) {
      _partnerAvatar = widget.initialPartnerAvatar!;
    }

    _remoteRenderer.initialize();

    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    )..repeat(reverse: true);

    _pulseAnimation = Tween<double>(begin: 1.0, end: 1.25).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );

    if (widget.callData != null) {
      _callId = int.tryParse(widget.callData!['id']?.toString() ?? '');
      _partnerName = widget.isIncoming
          ? (widget.callData!['caller_name'] ?? _partnerName)
          : (widget.callData!['receiver_name'] ?? _partnerName);
      _partnerAvatar = widget.isIncoming
          ? (widget.callData!['caller_avatar'] ?? _partnerAvatar)
          : (widget.callData!['receiver_avatar'] ?? widget.callData!['store_logo'] ?? _partnerAvatar);
      _partnerPhone = widget.callData!['partner_phone'] ?? widget.callData!['phone'] ?? widget.callData!['customer_phone'] ?? widget.callData!['dm_phone'] ?? '';
      _pendingOffer = widget.callData!['offer'];

      if (widget.callData!['status'] == 'connected') {
        _isConnected = true;
        _statusText = 'Panggilan Berlangsung';
        _startTimer();
      }
    } else if (widget.isIncoming) {
      _statusText = 'Panggilan Masuk...';
    }

    GlobalCallService.instance.setCallScreenOpen(true);
    debugPrint('📱 [InAppCallScreen] Opened (order: ${widget.orderCode}, isIncoming: ${widget.isIncoming}, role: ${widget.callerRole}, callId: $_callId, partner: $_partnerName)');

    if (!_isConnected) {
      _startRingtone();
    }

    _initCallSession();
    _startStatusPolling();
  }

  Future<void> _startRingtone() async {
    try {
      _audioPlayer = AudioPlayer(playerId: 'cicalengkago_call_ringtone');
      
      // Set audio context to force loud speaker output on Android/iOS (Native only)
      if (!kIsWeb) {
        await _audioPlayer!.setAudioContext(
          AudioContext(
            android: const AudioContextAndroid(
              isSpeakerphoneOn: true,
              stayAwake: true,
              contentType: AndroidContentType.sonification,
              usageType: AndroidUsageType.voiceCommunicationSignalling,
              audioFocus: AndroidAudioFocus.gainTransientMayDuck,
            ),
            iOS: AudioContextIOS(
              category: AVAudioSessionCategory.playback,
              options: const {
                AVAudioSessionOptions.mixWithOthers,
                AVAudioSessionOptions.defaultToSpeaker,
              },
            ),
          ),
        );
      }

      await _audioPlayer!.setReleaseMode(ReleaseMode.loop);
      await _audioPlayer!.setVolume(1.0);

      final assetPath = widget.isIncoming ? 'audio/ringtone.mp3' : 'audio/outgoing.wav';
      final remoteUrl = widget.isIncoming
          ? '${ApiConstants.baseUrl}/assets/audio/ringtone.mp3'
          : '${ApiConstants.baseUrl}/assets/audio/outgoing.wav';

      try {
        debugPrint('[InAppCall] Playing local ringtone asset: $assetPath');
        await _audioPlayer!.play(AssetSource(assetPath));
      } catch (assetErr) {
        debugPrint('[InAppCall] Asset playback fallback to URL ($assetErr): $remoteUrl');
        await _audioPlayer!.play(UrlSource(remoteUrl));
      }
    } catch (e) {
      debugPrint('[InAppCall] Error playing ringtone: $e');
    }
  }

  Future<void> _stopRingtone() async {
    try {
      if (_audioPlayer != null) {
        await _audioPlayer!.stop();
        await _audioPlayer!.dispose();
        _audioPlayer = null;
      }
    } catch (_) {}
  }

  Future<bool> _requestMicrophonePermission() async {
    if (kIsWeb) return true;
    try {
      var status = await Permission.microphone.status;
      if (!status.isGranted) {
        status = await Permission.microphone.request();
      }
      return status.isGranted;
    } catch (e) {
      debugPrint('[WebRTC] Permission check error: $e');
      return true;
    }
  }

  Future<void> _initCallSession() async {
    try {
      final hasPermission = await _requestMicrophonePermission();
      if (!hasPermission) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Izin mikrofon diperlukan untuk melakukan panggilan.'),
              backgroundColor: Colors.red,
            ),
          );
        }
        _handleCallEnded('Izin mikrofon ditolak');
        return;
      }

      await _setupWebRtcEngine();

      if (!widget.isIncoming) {
        // Outgoing call: create WebRTC SDP offer
        final offer = await _peerConnection!.createOffer({
          'offerToReceiveAudio': 1,
          'offerToReceiveVideo': 0,
        });
        await _peerConnection!.setLocalDescription(offer);

        debugPrint('📤 [InAppCallScreen] Sending POST /calls/initiate for order ${widget.orderCode} (callerRole: ${widget.callerRole})...');
        final res = await http.post(
          Uri.parse('${ApiConstants.baseUrl}/calls/initiate'),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({
            'order_code': widget.orderCode,
            'caller_role': widget.callerRole ?? 'customer',
            'offer': {
              'sdp': offer.sdp,
              'type': offer.type,
            },
          }),
        );

        debugPrint('📥 [InAppCallScreen] /calls/initiate response [${res.statusCode}]: ${res.body}');

        if (res.statusCode == 200) {
          final data = jsonDecode(res.body);
          if (data['success'] == true && data['data'] != null) {
            _callId = int.tryParse(data['data']['call_id']?.toString() ?? '');
            debugPrint('✅ [InAppCallScreen] Call initiated successfully! (Call ID: $_callId, receiverId: ${data['data']['receiver_id']})');
            if (data['data']['partner_name'] != null && mounted) {
              setState(() {
                _partnerName = data['data']['partner_name'];
              });
            }
            if (data['data']['partner_avatar'] != null && mounted) {
              setState(() {
                _partnerAvatar = data['data']['partner_avatar'];
              });
            }
            if (data['data']['partner_phone'] != null && mounted) {
              setState(() {
                _partnerPhone = data['data']['partner_phone'].toString();
              });
            }
          } else {
            final msg = data['message'] ?? 'Gagal memulai panggilan';
            debugPrint('❌ [InAppCallScreen] Initiate failed: $msg');
            if (mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(msg), backgroundColor: Colors.red),
              );
            }
            _handleCallEnded(msg);
            return;
          }
        }
      }
    } catch (e) {
      debugPrint('❌ [InAppCallScreen] Error initializing call session: $e');
    }
  }

  Future<void> _setupWebRtcEngine() async {
    if (_peerConnection != null) return;

    try {
      final Map<String, dynamic> configuration = {
        'iceServers': [
          {'urls': 'stun:stun.l.google.com:19302'},
          {'urls': 'stun:stun1.l.google.com:19302'},
          {'urls': 'stun:stun2.l.google.com:19302'},
          {'urls': 'stun:stun3.l.google.com:19302'},
          {'urls': 'stun:stun4.l.google.com:19302'},
        ],
        'sdpSemantics': 'unified-plan',
      };

      final Map<String, dynamic> loopbackConstraints = {
        'mandatory': {},
        'optional': [
          {'DtlsSrtpKeyAgreement': true},
        ],
      };

      _peerConnection = await createPeerConnection(configuration, loopbackConstraints);

      _peerConnection!.onIceCandidate = (RTCIceCandidate candidate) {
        if (candidate.candidate == null) return;
        _sendLocalIceCandidate(candidate);
      };

      _peerConnection!.onConnectionState = (RTCPeerConnectionState state) {
        debugPrint('[WebRTC] Connection state changed: $state');
        if (state == RTCPeerConnectionState.RTCPeerConnectionStateConnected) {
          _stopRingtone();
          if (mounted && !_isConnected) {
            setState(() {
              _isConnected = true;
              _statusText = 'Panggilan Berlangsung';
            });
            _startTimer();
            _setSpeakerphone(_isSpeakerOn);
          }
        } else if (state == RTCPeerConnectionState.RTCPeerConnectionStateDisconnected ||
                   state == RTCPeerConnectionState.RTCPeerConnectionStateFailed ||
                   state == RTCPeerConnectionState.RTCPeerConnectionStateClosed) {
          _handleCallEnded('Panggilan Diakhiri');
        }
      };

      _peerConnection!.onTrack = (RTCTrackEvent event) {
        debugPrint('[WebRTC] Received remote track: ${event.track.kind}');
        if (event.track.kind == 'audio') {
          if (event.streams.isNotEmpty) {
            _remoteRenderer.srcObject = event.streams[0];
          }
          _stopRingtone();
          if (mounted && !_isConnected) {
            setState(() {
              _isConnected = true;
              _statusText = 'Panggilan Berlangsung';
            });
            _startTimer();
            _setSpeakerphone(_isSpeakerOn);
          }
        }
      };

      // Capture local microphone
      final Map<String, dynamic> mediaConstraints = {
        'audio': {
          'echoCancellation': true,
          'noiseSuppression': true,
          'autoGainControl': true,
        },
        'video': false,
      };

      _localStream = await navigator.mediaDevices.getUserMedia(mediaConstraints);
      for (var track in _localStream!.getAudioTracks()) {
        await _peerConnection!.addTrack(track, _localStream!);
      }

      _setSpeakerphone(_isSpeakerOn);
    } catch (e) {
      debugPrint('[WebRTC] Setup peer connection error: $e');
    }
  }

  void _sendLocalIceCandidate(RTCIceCandidate candidate) async {
    final candKey = '${candidate.candidate}_${candidate.sdpMid}_${candidate.sdpMLineIndex}';
    if (_sentCandidateKeys.contains(candKey)) return;
    _sentCandidateKeys.add(candKey);

    try {
      await http.post(
        Uri.parse('${ApiConstants.baseUrl}/calls/ice-candidate'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'call_id': _callId,
          'order_code': widget.orderCode,
          'role': widget.callerRole ?? (widget.isIncoming ? 'callee' : 'caller'),
          'candidate': {
            'candidate': candidate.candidate,
            'sdpMid': candidate.sdpMid,
            'sdpMLineIndex': candidate.sdpMLineIndex,
          },
        }),
      );
    } catch (_) {}
  }

  void _setSpeakerphone(bool enable) {
    if (kIsWeb) return;
    try {
      Helper.setSpeakerphoneOn(enable);
    } catch (e) {
      debugPrint('[WebRTC] Set speaker error: $e');
    }
  }

  void _startStatusPolling() {
    _statusTimer?.cancel();
    _statusTimer = Timer.periodic(const Duration(milliseconds: 1200), (_) async {
      if (_isEnded) return;
      _pollCount++;
      try {
        final url = '${ApiConstants.baseUrl}/calls/poll?order_code=${widget.orderCode}';
        final res = await http.get(Uri.parse(url));

        if (res.statusCode == 200) {
          final data = jsonDecode(res.body);
          if (data['success'] == true && data['data'] != null && data['data']['active_call'] != null) {
            final call = data['data']['active_call'] as Map<String, dynamic>;
            final status = call['status'];
            _callId = int.tryParse(call['id']?.toString() ?? '');

            if (call['offer'] != null) {
              _pendingOffer = call['offer'];
            }

            if (mounted) {
              setState(() {
                _partnerName = widget.isIncoming
                    ? (call['caller_name'] ?? _partnerName)
                    : (call['receiver_name'] ?? _partnerName);
                _partnerAvatar = widget.isIncoming
                    ? (call['caller_avatar'] ?? _partnerAvatar)
                    : (call['receiver_avatar'] ?? call['store_logo'] ?? _partnerAvatar);
                _partnerPhone = call['partner_phone'] ?? call['phone'] ?? _partnerPhone;
              });
            }

            // If caller: process incoming answer from callee
            if (!widget.isIncoming && call['answer'] != null && _peerConnection != null) {
              final remoteDesc = await _peerConnection!.getRemoteDescription();
              if (remoteDesc == null) {
                dynamic ans = call['answer'];
                if (ans is String) {
                  try { ans = jsonDecode(ans); } catch (_) {}
                }
                if (ans is Map && ans['sdp'] != null) {
                  await _peerConnection!.setRemoteDescription(
                    RTCSessionDescription(ans['sdp'], ans['type'] ?? 'answer'),
                  );
                }
              }
            }

            // Process remote ICE candidates
            if (call['ice_candidates'] != null && _peerConnection != null) {
              dynamic cands = call['ice_candidates'];
              if (cands is String) {
                try { cands = jsonDecode(cands); } catch (_) { cands = []; }
              }
              if (cands is List) {
                for (var item in cands) {
                  Map<String, dynamic>? candObj;
                  if (item is Map) {
                    if (item['candidate'] is Map) {
                      candObj = Map<String, dynamic>.from(item['candidate']);
                    } else if (item['candidate'] is String) {
                      try { candObj = jsonDecode(item['candidate']); } catch (_) {
                        candObj = {
                          'candidate': item['candidate'],
                          'sdpMid': item['sdpMid'],
                          'sdpMLineIndex': item['sdpMLineIndex'],
                        };
                      }
                    }
                  }
                  if (candObj != null && candObj['candidate'] != null) {
                    final key = '${candObj['candidate']}_${candObj['sdpMid']}_${candObj['sdpMLineIndex']}';
                    if (!_addedCandidateKeys.contains(key)) {
                      _addedCandidateKeys.add(key);
                      try {
                        await _peerConnection!.addCandidate(
                          RTCIceCandidate(
                            candObj['candidate']?.toString(),
                            candObj['sdpMid']?.toString(),
                            int.tryParse(candObj['sdpMLineIndex']?.toString() ?? '0') ?? 0,
                          ),
                        );
                      } catch (_) {}
                    }
                  }
                }
              }
            }

            if (status == 'connected' && !_isConnected) {
              _stopRingtone();
              if (mounted) {
                setState(() {
                  _isConnected = true;
                  _statusText = 'Panggilan Berlangsung';
                });
              }
              _startTimer();
              _setSpeakerphone(_isSpeakerOn);
            } else if (status == 'rejected') {
              _handleCallEnded('Panggilan Ditolak');
            } else if (status == 'ended') {
              _handleCallEnded('Panggilan Diakhiri');
            }
          } else {
            if (_isConnected || _pollCount > 30) {
              _handleCallEnded('Panggilan Diakhiri');
            }
          }
        }
      } catch (_) {}
    });
  }

  void _startTimer() {
    _durationTimer?.cancel();
    _durationTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) {
        setState(() {
          _callDurationSeconds++;
        });
      }
    });
  }

  Future<void> _answerCall() async {
    try {
      await _stopRingtone();
      final hasPermission = await _requestMicrophonePermission();
      if (!hasPermission) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Izin mikrofon diperlukan untuk menerima panggilan.'),
              backgroundColor: Colors.red,
            ),
          );
        }
        _handleCallEnded('Izin mikrofon ditolak');
        return;
      }

      await _setupWebRtcEngine();

      dynamic offerData = _pendingOffer;
      if (offerData is String) {
        try {
          offerData = jsonDecode(offerData);
          if (offerData is String) {
            offerData = jsonDecode(offerData);
          }
        } catch (_) {}
      }

      if ((offerData == null || (offerData is Map && offerData['sdp'] == null)) && _callId != null) {
        try {
          final pollRes = await http.get(Uri.parse('${ApiConstants.baseUrl}/calls/poll?order_code=${widget.orderCode}'));
          if (pollRes.statusCode == 200) {
            final pollJson = jsonDecode(pollRes.body);
            final rawOff = pollJson['data']?['active_call']?['offer'];
            if (rawOff != null) {
              offerData = rawOff is String ? jsonDecode(rawOff) : rawOff;
              if (offerData is String) offerData = jsonDecode(offerData);
            }
          }
        } catch (_) {}
      }

      if (offerData is Map && offerData['sdp'] != null && _peerConnection != null) {
        debugPrint('📥 [InAppCallScreen] Setting remote SDP offer...');
        await _peerConnection!.setRemoteDescription(
          RTCSessionDescription(offerData['sdp'], offerData['type'] ?? 'offer'),
        );

        final answer = await _peerConnection!.createAnswer({
          'offerToReceiveAudio': 1,
          'offerToReceiveVideo': 0,
        });
        await _peerConnection!.setLocalDescription(answer);

        if (_callId != null) {
          await http.post(
            Uri.parse('${ApiConstants.baseUrl}/calls/answer'),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'call_id': _callId,
              'answer': {
                'sdp': answer.sdp,
                'type': answer.type,
              },
            }),
          );
        }
      } else {
        if (_callId != null) {
          await http.post(
            Uri.parse('${ApiConstants.baseUrl}/calls/answer'),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'call_id': _callId,
            }),
          );
        }
      }

      if (mounted) {
        setState(() {
          _isConnected = true;
          _statusText = 'Panggilan Berlangsung';
        });
      }
      _startTimer();
      _setSpeakerphone(_isSpeakerOn);
    } catch (e) {
      debugPrint('[WebRTC] Answer error: $e');
      _handleCallEnded('Gagal menjawab panggilan');
    }
  }

  void _rejectCall() async {
    try {
      await http.post(
        Uri.parse('${ApiConstants.baseUrl}/calls/reject'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'call_id': _callId, 'order_code': widget.orderCode}),
      );
    } catch (_) {}
    _handleCallEnded('Panggilan Ditolak');
  }

  void _endCall() async {
    try {
      await http.post(
        Uri.parse('${ApiConstants.baseUrl}/calls/end'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'call_id': _callId, 'order_code': widget.orderCode}),
      );
    } catch (_) {}
    _handleCallEnded('Panggilan Diakhiri');
  }

  void _toggleMute() {
    setState(() {
      _isMuted = !_isMuted;
    });
    try {
      _localStream?.getAudioTracks().forEach((track) {
        track.enabled = !_isMuted;
      });
    } catch (e) {
      debugPrint('[WebRTC] Toggle mute error: $e');
    }
  }

  void _toggleSpeaker() {
    setState(() {
      _isSpeakerOn = !_isSpeakerOn;
    });
    _setSpeakerphone(_isSpeakerOn);
  }

  void _handleCallEnded(String message) {
    if (_isEnded) return;
    _isEnded = true;
    _stopRingtone();
    _statusTimer?.cancel();
    _durationTimer?.cancel();

    _cleanupWebRtc();

    if (mounted) {
      setState(() {
        _statusText = message;
      });
      Future.delayed(const Duration(milliseconds: 1200), () {
        if (mounted) Navigator.of(context).pop();
      });
    }
  }

  Future<void> _cleanupWebRtc() async {
    try {
      _localStream?.getTracks().forEach((track) => track.stop());
      await _localStream?.dispose();
      _localStream = null;

      await _peerConnection?.close();
      await _peerConnection?.dispose();
      _peerConnection = null;

      try {
        _remoteRenderer.srcObject = null;
        await _remoteRenderer.dispose();
      } catch (_) {}
    } catch (_) {}
  }

  String _formatDuration(int seconds) {
    final m = seconds ~/ 60;
    final s = seconds % 60;
    return '${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')}';
  }

  @override
  void dispose() {
    _stopRingtone();
    _statusTimer?.cancel();
    _durationTimer?.cancel();
    _pulseController.dispose();
    _cleanupWebRtc();
    GlobalCallService.instance.setCallScreenClosed();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rawAvatar = _partnerAvatar.trim();
    final isStaticDefault = rawAvatar.contains('driver.png') || rawAvatar.contains('customer.png') || rawAvatar.contains('default.png');
    final formattedAvatar = (!isStaticDefault && rawAvatar.isNotEmpty) ? ApiConstants.formatImageUrl(rawAvatar) : '';
    final isStore = _partnerName.toLowerCase().contains('warung') ||
                    _partnerName.toLowerCase().contains('toko') ||
                    _partnerName.toLowerCase().contains('resto') ||
                    _partnerName.toLowerCase().contains('kedai') ||
                    _partnerName.toLowerCase().contains('cafe') ||
                    _partnerName.toLowerCase().contains('mart') ||
                    widget.callerRole == 'customer';

    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) {
            return SingleChildScrollView(
              physics: const BouncingScrollPhysics(),
              child: ConstrainedBox(
                constraints: BoxConstraints(minHeight: constraints.maxHeight),
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Header badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.phone_in_talk_rounded, color: AppTheme.primaryRed, size: 14),
                            SizedBox(width: 6),
                            Flexible(
                              child: Text(
                                'Panggilan Suara In-App CicalengkaGO',
                                style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(height: 32),

                      // Avatar & Animated Ring Pulse
                      ScaleTransition(
                        scale: _isConnected ? const AlwaysStoppedAnimation(1.0) : _pulseAnimation,
                        child: Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: AppTheme.primaryRed.withValues(alpha: 0.15),
                          ),
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: const BoxDecoration(
                              shape: BoxShape.circle,
                              color: AppTheme.primaryRed,
                            ),
                            child: CircleAvatar(
                              radius: 54,
                              backgroundColor: const Color(0xFF1E293B),
                              backgroundImage: formattedAvatar.isNotEmpty ? CachedNetworkImageProvider(formattedAvatar) : null,
                              child: formattedAvatar.isEmpty
                                  ? Icon(
                                      isStore ? Icons.storefront_rounded : Icons.person_rounded,
                                      size: 52,
                                      color: Colors.white,
                                    )
                                  : null,
                            ),
                          ),
                        ),
                      ),

                      const SizedBox(height: 20),

                      // Partner Name
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: Text(
                          _partnerName,
                          style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold),
                          textAlign: TextAlign.center,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const SizedBox(height: 6),

                      // Status or Duration
                      if (_isConnected)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                          decoration: BoxDecoration(
                            color: const Color(0xFF16A34A).withValues(alpha: 0.2),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFF16A34A).withValues(alpha: 0.4)),
                          ),
                          child: Text(
                            _formatDuration(_callDurationSeconds),
                            style: const TextStyle(color: Color(0xFF4ADE80), fontSize: 14, fontWeight: FontWeight.bold, letterSpacing: 1),
                          ),
                        )
                      else
                        Text(
                          _statusText,
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.7),
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                          ),
                        ),

                      const SizedBox(height: 48),

                      // Action Buttons Row
                      if (widget.isIncoming && !_isConnected)
                        // Incoming call controls (Reject & Answer)
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                          children: [
                            _buildCircleButton(
                              icon: Icons.call_end_rounded,
                              color: const Color(0xFFEF4444),
                              label: 'Tolak',
                              onPressed: _rejectCall,
                            ),
                            _buildCircleButton(
                              icon: Icons.call_rounded,
                              color: const Color(0xFF10B981),
                              label: 'Terima',
                              onPressed: _answerCall,
                            ),
                          ],
                        )
                      else
                        // Connected or Outgoing call controls (Mute, Speaker, End)
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                          children: [
                            _buildCircleButton(
                              icon: _isMuted ? Icons.mic_off_rounded : Icons.mic_rounded,
                              color: _isMuted ? Colors.white : Colors.white.withValues(alpha: 0.2),
                              iconColor: _isMuted ? const Color(0xFF0F172A) : Colors.white,
                              label: _isMuted ? 'Muted' : 'Mute',
                              onPressed: _toggleMute,
                            ),
                            _buildCircleButton(
                              icon: _isSpeakerOn ? Icons.volume_up_rounded : Icons.volume_down_rounded,
                              color: _isSpeakerOn ? const Color(0xFF3B82F6) : Colors.white.withValues(alpha: 0.2),
                              iconColor: Colors.white,
                              label: _isSpeakerOn ? 'Speaker On' : 'Speaker Off',
                              onPressed: _toggleSpeaker,
                            ),
                            _buildCircleButton(
                              icon: Icons.call_end_rounded,
                              color: const Color(0xFFEF4444),
                              label: 'Akhiri',
                              onPressed: _endCall,
                            ),
                          ],
                        ),
                    ],
                  ),
                ),
              ),
            );
          },
        ),
      ),
    );
  }

  Widget _buildCircleButton({
    required IconData icon,
    required Color color,
    Color iconColor = Colors.white,
    required String label,
    required VoidCallback onPressed,
  }) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Material(
          color: color,
          shape: const CircleBorder(),
          elevation: 4,
          child: InkWell(
            onTap: onPressed,
            customBorder: const CircleBorder(),
            child: Padding(
              padding: const EdgeInsets.all(18),
              child: Icon(icon, color: iconColor, size: 28),
            ),
          ),
        ),
        const SizedBox(height: 8),
        Text(
          label,
          style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
        ),
      ],
    );
  }
}
