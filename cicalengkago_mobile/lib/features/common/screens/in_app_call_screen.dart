import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_webrtc/flutter_webrtc.dart';
import 'package:http/http.dart' as http;
import 'package:permission_handler/permission_handler.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';

class InAppCallScreen extends StatefulWidget {
  final String orderCode;
  final bool isIncoming;
  final String? callerRole;
  final Map<String, dynamic>? callData;

  const InAppCallScreen({
    super.key,
    required this.orderCode,
    this.isIncoming = false,
    this.callerRole,
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
  String _partnerAvatar = 'assets/images/users/driver.png';

  RTCPeerConnection? _peerConnection;
  MediaStream? _localStream;
  MediaStream? _remoteStream;

  bool _hasSetRemoteAnswer = false;
  bool _hasSetRemoteOffer = false;
  bool _remoteDescriptionSet = false;
  final Set<String> _processedCandidates = {};
  final List<Map<String, dynamic>> _pendingLocalCandidates = [];
  final List<Map<String, dynamic>> _pendingRemoteCandidates = [];
  int _pollCount = 0;

  static const Map<String, dynamic> _rtcConfiguration = {
    'iceServers': [
      {'urls': 'stun:stun.l.google.com:19302'},
      {'urls': 'stun:stun1.l.google.com:19302'},
      {'urls': 'stun:stun2.l.google.com:19302'},
      {'urls': 'stun:stun3.l.google.com:19302'},
      {'urls': 'stun:stun4.l.google.com:19302'},
      {'urls': 'stun:global.stun.twilio.com:3478'},
      {'urls': 'stun:stun.cloudflare.com:3478'},
      {
        'urls': 'turn:openrelay.metered.ca:80',
        'username': 'openrelayproject',
        'credential': 'openrelayproject'
      },
      {
        'urls': 'turn:openrelay.metered.ca:443',
        'username': 'openrelayproject',
        'credential': 'openrelayproject'
      },
      {
        'urls': 'turn:openrelay.metered.ca:443?transport=tcp',
        'username': 'openrelayproject',
        'credential': 'openrelayproject'
      }
    ],
    'sdpSemantics': 'unified-plan',
    'iceCandidatePoolSize': 10
  };

  @override
  void initState() {
    super.initState();

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
          ? (widget.callData!['caller_name'] ?? 'Mitra CicalengkaGO')
          : (widget.callData!['receiver_name'] ?? 'Mitra CicalengkaGO');
      _partnerAvatar = widget.isIncoming
          ? (widget.callData!['caller_avatar'] ?? 'assets/images/users/driver.png')
          : (widget.callData!['receiver_avatar'] ?? 'assets/images/users/driver.png');
      if (widget.callData!['status'] == 'connected') {
        _isConnected = true;
        _statusText = 'Panggilan Berlangsung';
        _startTimer();
      }
    } else if (widget.isIncoming) {
      _statusText = 'Panggilan Masuk...';
    }

    _initNativeWebRTC();
    _startStatusPolling();
  }

  Future<bool> _requestMicrophonePermission() async {
    try {
      var status = await Permission.microphone.status;
      if (!status.isGranted) {
        status = await Permission.microphone.request();
      }
      return status.isGranted;
    } catch (e) {
      debugPrint('[NativeWebRTC] Permission check error: $e');
      return true; // Fallback to let WebRTC plugin prompt
    }
  }

  Future<void> _initNativeWebRTC() async {
    try {
      if (!widget.isIncoming) {
        // Outgoing Call: Setup PeerConnection and create Offer
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

        final setupSuccess = await _setupLocalMediaAndPeerConnection();
        if (!setupSuccess || _peerConnection == null) {
          _handleCallEnded('Gagal memuat media suara');
          return;
        }

        final offer = await _peerConnection!.createOffer({
          'offerToReceiveAudio': true,
          'offerToReceiveVideo': false,
        });
        await _peerConnection!.setLocalDescription(offer);

        final offerSdp = jsonEncode({'type': offer.type ?? 'offer', 'sdp': offer.sdp});

        final res = await http.post(
          Uri.parse('${ApiConstants.baseUrl}/calls/initiate'),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({
            'order_code': widget.orderCode,
            'offer': offerSdp,
            'caller_role': widget.callerRole ?? 'customer',
          }),
        );

        if (res.statusCode == 200) {
          final data = jsonDecode(res.body);
          if (data['success'] == true && data['data'] != null) {
            _callId = int.tryParse(data['data']['call_id']?.toString() ?? '');
            await _flushPendingLocalCandidates();
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
          } else {
            final msg = data['message'] ?? 'Gagal memulai panggilan';
            if (mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(msg), backgroundColor: Colors.red),
              );
            }
            _handleCallEnded(msg);
          }
        } else {
          final msg = 'Gagal menghubungi server (${res.statusCode})';
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(msg), backgroundColor: Colors.red),
            );
          }
          _handleCallEnded(msg);
        }
      }
    } catch (e) {
      debugPrint('[NativeWebRTC] Error initializing call: $e');
      _handleCallEnded('Gagal memulai panggilan');
    }
  }

  Future<bool> _setupLocalMediaAndPeerConnection() async {
    if (_peerConnection != null) return true;

    try {
      _localStream = await navigator.mediaDevices.getUserMedia({
        'audio': {
          'echoCancellation': true,
          'noiseSuppression': true,
          'autoGainControl': true,
        },
        'video': false,
      });

      _peerConnection = await createPeerConnection(_rtcConfiguration);

      _localStream!.getTracks().forEach((track) {
        track.enabled = true;
        _peerConnection!.addTrack(track, _localStream!);
      });

      _peerConnection!.onIceCandidate = (candidate) {
        if (candidate.candidate != null && candidate.candidate!.isNotEmpty) {
          _sendIceCandidate(candidate);
        }
      };

      _peerConnection!.onTrack = (event) {
        if (event.streams.isNotEmpty) {
          _remoteStream = event.streams[0];
          try {
            event.track.enabled = true;
            Helper.setSpeakerphoneOn(_isSpeakerOn);
          } catch (_) {}
        }
      };

      _peerConnection!.onConnectionState = (state) {
        debugPrint('[NativeWebRTC] Connection state: $state');
        if (state == RTCPeerConnectionState.RTCPeerConnectionStateConnected) {
          if (mounted && !_isConnected) {
            setState(() {
              _isConnected = true;
              _statusText = 'Panggilan Berlangsung';
            });
            _startTimer();
          }
        } else if (state == RTCPeerConnectionState.RTCPeerConnectionStateFailed ||
                   state == RTCPeerConnectionState.RTCPeerConnectionStateDisconnected) {
          debugPrint('[NativeWebRTC] Connection state disconnected/failed: $state');
        }
      };

      Helper.setSpeakerphoneOn(_isSpeakerOn);
      return true;
    } catch (e) {
      debugPrint('[NativeWebRTC] Setup error: $e');
      return false;
    }
  }

  Future<void> _sendIceCandidate(RTCIceCandidate candidate) async {
    try {
      final candData = {
        'candidate': candidate.candidate,
        'sdpMid': candidate.sdpMid,
        'sdpMLineIndex': candidate.sdpMLineIndex,
      };

      final payload = {
        'role': widget.isIncoming ? 'receiver' : 'caller',
        'candidate': candData,
      };

      if (_callId == null || _callId == 0) {
        _pendingLocalCandidates.add(payload);
        return;
      }

      await http.post(
        Uri.parse('${ApiConstants.baseUrl}/calls/ice-candidate'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'call_id': _callId,
          'order_code': widget.orderCode,
          'role': widget.isIncoming ? 'receiver' : 'caller',
          'candidate': jsonEncode(candData),
        }),
      );
    } catch (e) {
      debugPrint('[NativeWebRTC] ICE candidate error: $e');
    }
  }

  Future<void> _flushPendingLocalCandidates() async {
    if (_callId == null || _callId == 0 || _pendingLocalCandidates.isEmpty) return;
    final toFlush = List<Map<String, dynamic>>.from(_pendingLocalCandidates);
    _pendingLocalCandidates.clear();

    for (var payload in toFlush) {
      try {
        await http.post(
          Uri.parse('${ApiConstants.baseUrl}/calls/ice-candidate'),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({
            'call_id': _callId,
            'order_code': widget.orderCode,
            'role': payload['role'] ?? (widget.isIncoming ? 'receiver' : 'caller'),
            'candidate': jsonEncode(payload['candidate']),
          }),
        );
      } catch (_) {}
    }
  }

  Future<void> _flushPendingRemoteCandidates() async {
    if (_peerConnection == null || !_remoteDescriptionSet || _pendingRemoteCandidates.isEmpty) return;
    final toFlush = List<Map<String, dynamic>>.from(_pendingRemoteCandidates);
    _pendingRemoteCandidates.clear();

    for (var candMap in toFlush) {
      final candStr = candMap['candidate']?.toString();
      if (candStr == null || candStr.isEmpty) continue;
      final key = jsonEncode(candMap);
      if (!_processedCandidates.contains(key)) {
        _processedCandidates.add(key);
        try {
          final rtcCand = RTCIceCandidate(
            candStr,
            candMap['sdpMid']?.toString() ?? '',
            int.tryParse(candMap['sdpMLineIndex']?.toString() ?? '0') ?? 0,
          );
          await _peerConnection?.addCandidate(rtcCand);
          debugPrint('[NativeWebRTC] Flushed remote ICE candidate successfully');
        } catch (e) {
          debugPrint('[NativeWebRTC] Error adding buffered remote candidate: $e');
        }
      }
    }
  }

  void _startStatusPolling() {
    _statusTimer?.cancel();
    _statusTimer = Timer.periodic(const Duration(milliseconds: 1500), (_) async {
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
            await _flushPendingLocalCandidates();

            if (mounted) {
              setState(() {
                _partnerName = widget.isIncoming
                    ? (call['caller_name'] ?? _partnerName)
                    : (call['receiver_name'] ?? _partnerName);
                _partnerAvatar = widget.isIncoming
                    ? (call['caller_avatar'] ?? _partnerAvatar)
                    : (call['receiver_avatar'] ?? _partnerAvatar);
              });
            }

            // Handle Answer for Outgoing Call
            if (!widget.isIncoming && status == 'connected' && call['answer'] != null && !_hasSetRemoteAnswer) {
              _hasSetRemoteAnswer = true;
              try {
                dynamic ansObj = call['answer'];
                if (ansObj is String) ansObj = jsonDecode(ansObj);
                if (ansObj is Map) {
                  final answerSdp = RTCSessionDescription(ansObj['sdp']?.toString(), ansObj['type']?.toString() ?? 'answer');
                  await _peerConnection?.setRemoteDescription(answerSdp);
                  _remoteDescriptionSet = true;
                  await _flushPendingRemoteCandidates();
                }
              } catch (e) {
                debugPrint('[NativeWebRTC] Error setting remote answer: $e');
              }
            }

            // Process ICE Candidates from remote
            if (call['ice_candidates'] != null) {
              dynamic candList = call['ice_candidates'];
              if (candList is String) {
                try {
                  candList = jsonDecode(candList);
                } catch (_) {
                  candList = [];
                }
              }
              if (candList is List) {
                final myRole = widget.isIncoming ? 'receiver' : 'caller';
                for (var item in candList) {
                  dynamic cMap = item;
                  if (cMap is String) {
                    try { cMap = jsonDecode(cMap); } catch (_) { continue; }
                  }
                  if (cMap == null || cMap is! Map) continue;

                  String? senderRole = cMap['role']?.toString();
                  dynamic actualCand = cMap.containsKey('candidate') ? cMap['candidate'] : cMap;
                  if (actualCand is String) {
                    try { actualCand = jsonDecode(actualCand); } catch (_) {}
                  }
                  if (actualCand == null || actualCand is! Map) continue;

                  // Ignore own candidates
                  if (senderRole == myRole) continue;

                  final keyStr = jsonEncode(actualCand);
                  if (_processedCandidates.contains(keyStr)) continue;

                  final candMap = Map<String, dynamic>.from(actualCand);

                  if (!_remoteDescriptionSet || _peerConnection == null) {
                    _pendingRemoteCandidates.add(candMap);
                  } else {
                    _processedCandidates.add(keyStr);
                    try {
                      final cand = RTCIceCandidate(
                        candMap['candidate']?.toString() ?? '',
                        candMap['sdpMid']?.toString() ?? '',
                        int.tryParse(candMap['sdpMLineIndex']?.toString() ?? '0') ?? 0,
                      );
                      await _peerConnection?.addCandidate(cand);
                    } catch (e) {
                      debugPrint('[NativeWebRTC] Error adding ICE candidate: $e');
                    }
                  }
                }
              }
            }

            if (status == 'connected' && !_isConnected) {
              if (mounted) {
                setState(() {
                  _isConnected = true;
                  _statusText = 'Panggilan Berlangsung';
                });
              }
              _startTimer();
            } else if (status == 'rejected') {
              _handleCallEnded('Panggilan Ditolak');
            } else if (status == 'ended') {
              _handleCallEnded('Panggilan Diakhiri');
            }
          } else {
            if (_isConnected || _pollCount > 25) {
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

      final setupSuccess = await _setupLocalMediaAndPeerConnection();
      if (!setupSuccess || _peerConnection == null) {
        _handleCallEnded('Gagal memuat media suara');
        return;
      }

      // Retrieve Offer from callData or poll
      dynamic offerData;
      if (widget.callData != null && widget.callData!['offer'] != null) {
        offerData = widget.callData!['offer'];
      }
      if (offerData == null) {
        final res = await http.get(Uri.parse('${ApiConstants.baseUrl}/calls/poll?order_code=${widget.orderCode}'));
        if (res.statusCode == 200) {
          final data = jsonDecode(res.body);
          offerData = data['data']?['active_call']?['offer'];
        }
      }

      if (offerData != null && !_hasSetRemoteOffer) {
        _hasSetRemoteOffer = true;
        if (offerData is String) offerData = jsonDecode(offerData);
        if (offerData is Map) {
          final offerSdp = RTCSessionDescription(offerData['sdp']?.toString(), offerData['type']?.toString() ?? 'offer');
          await _peerConnection!.setRemoteDescription(offerSdp);
          _remoteDescriptionSet = true;
          await _flushPendingRemoteCandidates();
        }
      }

      final answer = await _peerConnection!.createAnswer({
        'offerToReceiveAudio': true,
        'offerToReceiveVideo': false,
      });
      await _peerConnection!.setLocalDescription(answer);

      final answerSdpStr = jsonEncode({'type': answer.type ?? 'answer', 'sdp': answer.sdp});

      await _flushPendingLocalCandidates();

      if (_callId != null) {
        await http.post(
          Uri.parse('${ApiConstants.baseUrl}/calls/answer'),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({
            'call_id': _callId,
            'answer': answerSdpStr,
          }),
        );
      }

      if (mounted) {
        setState(() {
          _isConnected = true;
          _statusText = 'Panggilan Berlangsung';
        });
      }
      _startTimer();
    } catch (e) {
      debugPrint('[NativeWebRTC] Answer error: $e');
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
      Helper.setMicrophoneMute(_isMuted);
      if (_localStream != null) {
        for (var track in _localStream!.getAudioTracks()) {
          track.enabled = !_isMuted;
        }
      }
    } catch (e) {
      debugPrint('[NativeWebRTC] Toggle mute error: $e');
    }
  }

  void _toggleSpeaker() {
    setState(() {
      _isSpeakerOn = !_isSpeakerOn;
    });
    try {
      Helper.setSpeakerphoneOn(_isSpeakerOn);
    } catch (e) {
      debugPrint('[NativeWebRTC] Toggle speaker error: $e');
    }
  }

  void _handleCallEnded(String message) {
    if (_isEnded) return;
    _isEnded = true;
    _statusTimer?.cancel();
    _durationTimer?.cancel();

    _cleanupWebRTC();

    if (mounted) {
      setState(() {
        _statusText = message;
      });
      Future.delayed(const Duration(milliseconds: 1200), () {
        if (mounted) Navigator.of(context).pop();
      });
    }
  }

  void _cleanupWebRTC() {
    try {
      _localStream?.getTracks().forEach((track) => track.stop());
      _localStream?.dispose();
      _remoteStream?.dispose();
      _peerConnection?.close();
      _peerConnection?.dispose();
    } catch (_) {}
    _localStream = null;
    _remoteStream = null;
    _peerConnection = null;
  }

  String _formatDuration(int seconds) {
    final m = seconds ~/ 60;
    final s = seconds % 60;
    return '${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')}';
  }

  @override
  void dispose() {
    _statusTimer?.cancel();
    _durationTimer?.cancel();
    _pulseController.dispose();
    _cleanupWebRTC();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final formattedAvatar = ApiConstants.formatImageUrl(_partnerAvatar);

    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
          child: Column(
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
                    Text(
                      'Panggilan Suara Native CicalengkaGO',
                      style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),

              const Spacer(),

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
                      backgroundImage: formattedAvatar.isNotEmpty ? NetworkImage(formattedAvatar) : null,
                      child: formattedAvatar.isEmpty ? const Icon(Icons.person, size: 50, color: Colors.white) : null,
                    ),
                  ),
                ),
              ),

              const SizedBox(height: 24),

              // Partner Name
              Text(
                _partnerName,
                style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 6),

              // Status or Duration
              if (_isConnected)
                Text(
                  _formatDuration(_callDurationSeconds),
                  style: const TextStyle(color: Color(0xFF10B981), fontSize: 28, fontWeight: FontWeight.w900, fontFamily: 'monospace'),
                )
              else
                Text(
                  _statusText,
                  style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 14, fontWeight: FontWeight.w600),
                ),

              const Spacer(),

              // Action Buttons Section
              if (widget.isIncoming && !_isConnected) ...[
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    // Reject Button
                    _callActionButton(
                      icon: Icons.call_end_rounded,
                      label: 'Tolak',
                      color: const Color(0xFFEF4444),
                      onTap: _rejectCall,
                    ),
                    // Answer Button
                    _callActionButton(
                      icon: Icons.call_rounded,
                      label: 'Jawab',
                      color: const Color(0xFF10B981),
                      onTap: _answerCall,
                    ),
                  ],
                ),
              ] else ...[
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    // Mute Button
                    _callActionButton(
                      icon: _isMuted ? Icons.mic_off_rounded : Icons.mic_rounded,
                      label: _isMuted ? 'Unmute' : 'Mute',
                      color: _isMuted ? const Color(0xFFF59E0B) : Colors.white.withValues(alpha: 0.2),
                      iconColor: Colors.white,
                      onTap: _toggleMute,
                    ),
                    // Speaker Button
                    _callActionButton(
                      icon: _isSpeakerOn ? Icons.volume_up_rounded : Icons.volume_off_rounded,
                      label: _isSpeakerOn ? 'Speaker On' : 'Speaker Off',
                      color: _isSpeakerOn ? const Color(0xFF3B82F6) : Colors.white.withValues(alpha: 0.2),
                      iconColor: Colors.white,
                      onTap: _toggleSpeaker,
                    ),
                    // End Call Button
                    _callActionButton(
                      icon: Icons.call_end_rounded,
                      label: 'Akhiri',
                      color: const Color(0xFFEF4444),
                      onTap: _endCall,
                    ),
                  ],
                ),
              ],

              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _callActionButton({
    required IconData icon,
    required String label,
    required Color color,
    Color iconColor = Colors.white,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: color,
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(color: color.withValues(alpha: 0.4), blurRadius: 16, offset: const Offset(0, 6)),
              ],
            ),
            child: Icon(icon, color: iconColor, size: 28),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }
}
