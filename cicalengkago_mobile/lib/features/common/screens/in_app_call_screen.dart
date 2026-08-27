import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';

class InAppCallScreen extends StatefulWidget {
  final String orderCode;
  final bool isIncoming;
  final Map<String, dynamic>? callData;

  const InAppCallScreen({
    super.key,
    required this.orderCode,
    this.isIncoming = false,
    this.callData,
  });

  @override
  State<InAppCallScreen> createState() => _InAppCallScreenState();
}

class _InAppCallScreenState extends State<InAppCallScreen> with TickerProviderStateMixin {
  late WebViewController _webViewController;
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
      _partnerName = widget.callData!['caller_name'] ?? widget.callData!['receiver_name'] ?? 'Mitra CicalengkaGO';
      _partnerAvatar = widget.callData!['caller_avatar'] ?? widget.callData!['receiver_avatar'] ?? 'assets/images/users/driver.png';
      if (widget.callData!['status'] == 'connected') {
        _isConnected = true;
        _statusText = 'Panggilan Berlangsung';
        _startTimer();
      }
    } else if (widget.isIncoming) {
      _statusText = 'Panggilan Masuk...';
    }

    _initWebView();
    _startStatusPolling();
  }

  int _pollCount = 0;

  void _initWebView() {
    final webUrl = '${ApiConstants.baseUrl}/orders/${widget.orderCode}/tracking?app_call=1';
    
    _webViewController = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(const Color(0xFF0F172A))
      ..setOnConsoleMessage((message) {
        debugPrint('[VoiceCallWebView] ${message.message}');
      })
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageFinished: (url) {
            if (widget.isIncoming) {
              _webViewController.runJavaScript('if(window.CCGCall) window.CCGCall.init("${widget.orderCode}");');
            } else {
              _webViewController.runJavaScript('if(window.CCGCall) { window.CCGCall.init("${widget.orderCode}"); window.CCGCall.makeCall("${widget.orderCode}"); }');
            }
          },
        ),
      );

    if (_webViewController.platform is AndroidWebViewController) {
      (_webViewController.platform as AndroidWebViewController).setOnPlatformPermissionRequest(
        (request) {
          debugPrint('[VoiceCallWebView] Granting Android mic permission: ${request.types}');
          request.grant();
        },
      );
    }

    _webViewController.loadRequest(Uri.parse(webUrl));
  }

  void _startStatusPolling() {
    _statusTimer?.cancel();
    _pollCount = 0;
    _statusTimer = Timer.periodic(const Duration(seconds: 2), (_) async {
      if (_isEnded) return;
      _pollCount++;
      try {
        final res = await http.get(Uri.parse('${ApiConstants.baseUrl}/calls/poll?order_code=${widget.orderCode}'));
        if (res.statusCode == 200) {
          final data = jsonDecode(res.body);
          if (data['success'] == true && data['data'] != null && data['data']['active_call'] != null) {
            final call = data['data']['active_call'] as Map<String, dynamic>;
            _callId = call['id'];
            final st = call['status'];

            if (mounted) {
              setState(() {
                _partnerName = call['caller_name'] ?? call['receiver_name'] ?? _partnerName;
              });
            }

            if (st == 'connected' && !_isConnected) {
              if (mounted) {
                setState(() {
                  _isConnected = true;
                  _statusText = 'Terhubung';
                });
              }
              _startTimer();
            } else if (st == 'rejected' || st == 'ended') {
              _handleCallEnded('Panggilan Diakhiri');
            }
          } else {
            // Only end call if it was previously connected or after at least 5 poll attempts (10s) if callId was established
            if (_isConnected || (_callId != null && _pollCount > 5)) {
              _handleCallEnded('Panggilan Selesai');
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

  void _answerCall() {
    _webViewController.runJavaScript('if(window.CCGCall) window.CCGCall.answerCall();');
    if (mounted) {
      setState(() {
        _isConnected = true;
        _statusText = 'Terhubung';
      });
    }
    _startTimer();
  }

  void _rejectCall() {
    _webViewController.runJavaScript('if(window.CCGCall) window.CCGCall.rejectCall();');
    _handleCallEnded('Panggilan Ditolak');
  }

  void _endCall() {
    _webViewController.runJavaScript('if(window.CCGCall) window.CCGCall.endCall();');
    _handleCallEnded('Panggilan Diakhiri');
  }

  void _toggleMute() {
    setState(() {
      _isMuted = !_isMuted;
    });
    _webViewController.runJavaScript('if(window.CCGCall) window.CCGCall.toggleMute();');
  }

  void _handleCallEnded(String message) {
    if (_isEnded) return;
    _isEnded = true;
    _statusTimer?.cancel();
    _durationTimer?.cancel();

    if (mounted) {
      setState(() {
        _statusText = message;
      });
      Future.delayed(const Duration(milliseconds: 1200), () {
        if (mounted) Navigator.of(context).pop();
      });
    }
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
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final formattedAvatar = ApiConstants.formatImageUrl(_partnerAvatar);

    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      body: Stack(
        children: [
          // Hidden WebRTC WebViewController engine running in background of overlay
          Positioned(
            left: -999,
            top: -999,
            width: 1,
            height: 1,
            child: WebViewWidget(controller: _webViewController),
          ),

          // Main Call Overlay
          SafeArea(
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
                          'Panggilan Suara CicalengkaGO',
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
                          backgroundImage: NetworkImage(formattedAvatar),
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
        ],
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
