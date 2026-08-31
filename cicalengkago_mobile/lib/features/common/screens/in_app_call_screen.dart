import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:agora_rtc_engine/agora_rtc_engine.dart';
import 'package:http/http.dart' as http;
import 'package:permission_handler/permission_handler.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
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

  RtcEngine? _agoraEngine;
  bool _isAgoraInitialized = false;
  int _pollCount = 0;

  @override
  void initState() {
    super.initState();

    if (widget.initialPartnerName != null && widget.initialPartnerName!.isNotEmpty) {
      _partnerName = widget.initialPartnerName!;
    }
    if (widget.initialPartnerAvatar != null && widget.initialPartnerAvatar!.isNotEmpty) {
      _partnerAvatar = widget.initialPartnerAvatar!;
    }

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
      if (widget.callData!['status'] == 'connected') {
        _isConnected = true;
        _statusText = 'Panggilan Berlangsung';
        _startTimer();
      }
    } else if (widget.isIncoming) {
      _statusText = 'Panggilan Masuk...';
    }

    _initCallSession();
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
      debugPrint('[AgoraVoice] Permission check error: $e');
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

      await _setupAgoraEngine();

      if (!widget.isIncoming) {
        // Outgoing call: inform backend
        final res = await http.post(
          Uri.parse('${ApiConstants.baseUrl}/calls/initiate'),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({
            'order_code': widget.orderCode,
            'caller_role': widget.callerRole ?? 'customer',
          }),
        );

        if (res.statusCode == 200) {
          final data = jsonDecode(res.body);
          if (data['success'] == true && data['data'] != null) {
            _callId = int.tryParse(data['data']['call_id']?.toString() ?? '');
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
            if (mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(msg), backgroundColor: Colors.red),
              );
            }
            _handleCallEnded(msg);
            return;
          }
        }
        // Join Agora voice channel immediately for outgoing call
        await _joinAgoraChannel();
      }
    } catch (e) {
      debugPrint('[AgoraVoice] Error initializing call: $e');
    }
  }

  Future<void> _setupAgoraEngine() async {
    if (_isAgoraInitialized && _agoraEngine != null) return;

    try {
      _agoraEngine = createAgoraRtcEngine();
      await _agoraEngine!.initialize(const RtcEngineContext(
        appId: ApiConstants.agoraAppId,
        channelProfile: ChannelProfileType.channelProfileCommunication,
      ));

      _agoraEngine!.registerEventHandler(
        RtcEngineEventHandler(
          onJoinChannelSuccess: (RtcConnection connection, int elapsed) {
            debugPrint('[AgoraVoice] Local user joined channel: ${connection.channelId}');
            _safeSetSpeakerphone(_isSpeakerOn);
          },
          onUserJoined: (RtcConnection connection, int remoteUid, int elapsed) {
            debugPrint('[AgoraVoice] Remote user joined channel: $remoteUid');
            if (mounted) {
              setState(() {
                _isConnected = true;
                _statusText = 'Panggilan Berlangsung';
              });
              _startTimer();
              _safeSetSpeakerphone(_isSpeakerOn);
            }
          },
          onUserOffline: (RtcConnection connection, int remoteUid, UserOfflineReasonType reason) {
            debugPrint('[AgoraVoice] Remote user left channel: $remoteUid');
            _handleCallEnded('Panggilan Diakhiri');
          },
          onError: (ErrorCodeType err, String msg) {
            debugPrint('[AgoraVoice] Agora error: $err, $msg');
          },
        ),
      );

      await _agoraEngine!.enableAudio();
      await _agoraEngine!.setAudioProfile(
        profile: AudioProfileType.audioProfileSpeechStandard,
        scenario: AudioScenarioType.audioScenarioGameStreaming,
      );
      try {
        await _agoraEngine!.setDefaultAudioRouteToSpeakerphone(_isSpeakerOn);
      } catch (_) {}
      await _safeSetSpeakerphone(_isSpeakerOn);

      _isAgoraInitialized = true;
    } catch (e) {
      debugPrint('[AgoraVoice] Setup engine error: $e');
    }
  }

  Future<void> _safeSetSpeakerphone(bool enable) async {
    try {
      await _agoraEngine?.setEnableSpeakerphone(enable);
    } catch (e) {
      debugPrint('[AgoraVoice] setEnableSpeakerphone safe catch: $e');
    }
  }

  Future<void> _joinAgoraChannel() async {
    if (_agoraEngine == null) return;
    try {
      final cleanChannel = widget.orderCode.replaceAll(RegExp(r'[^a-zA-Z0-9_\-]'), '');
      final channelName = cleanChannel.isNotEmpty ? cleanChannel : 'cicalengkago_call';

      debugPrint('[AgoraVoice] Joining Agora Channel: $channelName');
      await _agoraEngine!.joinChannel(
        token: '',
        channelId: channelName,
        uid: 0,
        options: const ChannelMediaOptions(
          clientRoleType: ClientRoleType.clientRoleBroadcaster,
          autoSubscribeAudio: true,
          publishMicrophoneTrack: true,
        ),
      );
    } catch (e) {
      debugPrint('[AgoraVoice] Join channel error: $e');
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

            if (mounted) {
              setState(() {
                _partnerName = widget.isIncoming
                    ? (call['caller_name'] ?? _partnerName)
                    : (call['receiver_name'] ?? _partnerName);
                _partnerAvatar = widget.isIncoming
                    ? (call['caller_avatar'] ?? _partnerAvatar)
                    : (call['receiver_avatar'] ?? _partnerAvatar);
                _partnerPhone = call['partner_phone'] ?? call['phone'] ?? _partnerPhone;
              });
            }

            if (status == 'connected' && !_isConnected) {
              if (mounted) {
                setState(() {
                  _isConnected = true;
                  _statusText = 'Panggilan Berlangsung';
                });
              }
              _startTimer();
              _safeSetSpeakerphone(_isSpeakerOn);
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

      await _setupAgoraEngine();
      await _joinAgoraChannel();

      if (_callId != null) {
        await http.post(
          Uri.parse('${ApiConstants.baseUrl}/calls/answer'),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({
            'call_id': _callId,
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
      _safeSetSpeakerphone(_isSpeakerOn);
    } catch (e) {
      debugPrint('[AgoraVoice] Answer error: $e');
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
      _agoraEngine?.muteLocalAudioStream(_isMuted);
    } catch (e) {
      debugPrint('[AgoraVoice] Toggle mute error: $e');
    }
  }

  void _toggleSpeaker() {
    setState(() {
      _isSpeakerOn = !_isSpeakerOn;
    });
    _safeSetSpeakerphone(_isSpeakerOn);
  }

  void _handleCallEnded(String message) {
    if (_isEnded) return;
    _isEnded = true;
    _statusTimer?.cancel();
    _durationTimer?.cancel();

    _cleanupAgora();

    if (mounted) {
      setState(() {
        _statusText = message;
      });
      Future.delayed(const Duration(milliseconds: 1200), () {
        if (mounted) Navigator.of(context).pop();
      });
    }
  }

  Future<void> _cleanupAgora() async {
    try {
      await _agoraEngine?.leaveChannel();
      await _agoraEngine?.release();
    } catch (_) {}
    _agoraEngine = null;
    _isAgoraInitialized = false;
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
    _cleanupAgora();
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
                        Text(
                          _formatDuration(_callDurationSeconds),
                          style: const TextStyle(color: Color(0xFF10B981), fontSize: 28, fontWeight: FontWeight.w900, fontFamily: 'monospace'),
                        )
                      else
                        Text(
                          _statusText,
                          style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 14, fontWeight: FontWeight.w600),
                        ),

                      const SizedBox(height: 32),

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

                      const SizedBox(height: 24),
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

