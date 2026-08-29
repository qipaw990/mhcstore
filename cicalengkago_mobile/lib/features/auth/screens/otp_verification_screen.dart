import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../main.dart';
import '../../driver/controllers/driver_controller.dart';
import '../controllers/auth_controller.dart';

class OtpVerificationScreen extends StatefulWidget {
  final String phoneMasked;
  final String channel;

  const OtpVerificationScreen({
    super.key,
    required this.phoneMasked,
    this.channel = 'whatsapp',
  });

  @override
  State<OtpVerificationScreen> createState() => _OtpVerificationScreenState();
}

class _OtpVerificationScreenState extends State<OtpVerificationScreen> {
  final List<TextEditingController> _controllers = List.generate(6, (_) => TextEditingController());
  final List<FocusNode> _focusNodes = List.generate(6, (_) => FocusNode());

  int _resendCooldown = 60;
  Timer? _cooldownTimer;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _startCooldownTimer();
  }

  void _startCooldownTimer() {
    _cooldownTimer?.cancel();
    setState(() => _resendCooldown = 60);
    _cooldownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_resendCooldown > 0) {
        setState(() => _resendCooldown--);
      } else {
        timer.cancel();
      }
    });
  }

  @override
  void dispose() {
    _cooldownTimer?.cancel();
    for (final c in _controllers) {
      c.dispose();
    }
    for (final f in _focusNodes) {
      f.dispose();
    }
    super.dispose();
  }

  String get _currentOtp => _controllers.map((c) => c.text).join();

  Future<void> _handleVerify() async {
    final otp = _currentOtp;
    if (otp.length < 6) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Row(
            children: [
              Icon(Icons.warning_amber_rounded, color: Colors.white, size: 20),
              SizedBox(width: 8),
              Text('Masukkan 6 digit kode OTP verifikasi'),
            ],
          ),
          backgroundColor: Colors.orange.shade800,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final authCtrl = context.read<AuthController>();
    final success = await authCtrl.verifyOtp(otp);
    setState(() => _isSubmitting = false);

    if (!mounted) return;

    if (success) {
      final role = authCtrl.role?.toLowerCase() ?? 'customer';
      if (role == 'delivery_man' || role == 'driver') {
        context.read<DriverController>().fetchRadarData();
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Row(
            children: [
              const Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Verifikasi Berhasil! Selamat datang, ${authCtrl.user?['name'] ?? ''}',
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          backgroundColor: const Color(0xFF16A34A),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          duration: const Duration(seconds: 2),
        ),
      );

      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const RoleRouter()),
        (route) => false,
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Row(
            children: [
              const Icon(Icons.error_outline_rounded, color: Colors.white, size: 20),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  authCtrl.errorMessage ?? 'Kode OTP tidak sesuai. Silakan coba lagi.',
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          backgroundColor: const Color(0xFFDC2626),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );
    }
  }

  Future<void> _handleResend() async {
    if (_resendCooldown > 0) return;

    final authCtrl = context.read<AuthController>();
    final ok = await authCtrl.resendOtp();

    if (!mounted) return;

    if (ok) {
      _startCooldownTimer();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Row(
            children: [
              Icon(Icons.mark_chat_read_rounded, color: Colors.white, size: 20),
              SizedBox(width: 8),
              Expanded(child: Text('Kode OTP baru telah dikirimkan via WhatsApp!')),
            ],
          ),
          backgroundColor: const Color(0xFF16A34A),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(authCtrl.errorMessage ?? 'Gagal mengirim ulang OTP'),
          backgroundColor: const Color(0xFFDC2626),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isWa = widget.channel.toLowerCase() != 'email';

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: AppTheme.inkBlack),
          onPressed: () {
            context.read<AuthController>().resetOtpState();
            Navigator.pop(context);
          },
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
          physics: const BouncingScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              const SizedBox(height: 12),

              // WhatsApp Branding Icon Circle
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  color: isWa ? const Color(0xFFDCFCE7) : const Color(0xFFFEE2E2),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  isWa ? Icons.chat_bubble_rounded : Icons.mark_email_read_rounded,
                  size: 36,
                  color: isWa ? const Color(0xFF16A34A) : AppTheme.primaryRed,
                ),
              ),

              const SizedBox(height: 20),

              Text(
                isWa ? 'Verifikasi Kode OTP WhatsApp' : 'Verifikasi Kode OTP',
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: AppTheme.inkBlack,
                ),
                textAlign: TextAlign.center,
              ),

              const SizedBox(height: 8),

              RichText(
                textAlign: TextAlign.center,
                text: TextSpan(
                  style: const TextStyle(fontSize: 13, color: Color(0xFF64748B), height: 1.4),
                  children: [
                    const TextSpan(text: 'Kode verifikasi 6 digit telah dikirimkan via '),
                    TextSpan(
                      text: isWa ? 'WhatsApp' : 'Email',
                      style: TextStyle(fontWeight: FontWeight.bold, color: isWa ? const Color(0xFF16A34A) : AppTheme.primaryRed),
                    ),
                    const TextSpan(text: ' ke:\n'),
                    TextSpan(
                      text: widget.phoneMasked,
                      style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.inkBlack, fontSize: 14),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 32),

              // 6 PIN Input Boxes
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: List.generate(6, (index) {
                  return SizedBox(
                    width: 46,
                    height: 54,
                    child: TextFormField(
                      controller: _controllers[index],
                      focusNode: _focusNodes[index],
                      keyboardType: TextInputType.number,
                      textAlign: TextAlign.center,
                      maxLength: 1,
                      inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                      style: const TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.inkBlack,
                      ),
                      decoration: InputDecoration(
                        counterText: '',
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        contentPadding: EdgeInsets.zero,
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: const BorderSide(color: Color(0xFFCBD5E1)),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: const BorderSide(color: Color(0xFF16A34A), width: 2),
                        ),
                      ),
                      onChanged: (value) {
                        if (value.isNotEmpty) {
                          if (index < 5) {
                            _focusNodes[index + 1].requestFocus();
                          } else {
                            _focusNodes[index].unfocus();
                            _handleVerify();
                          }
                        } else {
                          if (index > 0) {
                            _focusNodes[index - 1].requestFocus();
                          }
                        }
                      },
                    ),
                  );
                }),
              ),

              const SizedBox(height: 28),

              // Verify Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF16A34A),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    elevation: 2,
                  ),
                  onPressed: _isSubmitting ? null : _handleVerify,
                  child: _isSubmitting
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.shield_rounded, size: 18),
                            SizedBox(width: 8),
                            Text(
                              'Konfirmasi & Masuk',
                              style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                ),
              ),

              const SizedBox(height: 24),

              // Resend OTP Section
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text(
                    'Tidak menerima kode? ',
                    style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                  ),
                  if (_resendCooldown > 0)
                    Text(
                      'Kirim Ulang (${_resendCooldown}s)',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF94A3B8),
                      ),
                    )
                  else
                    GestureDetector(
                      onTap: _handleResend,
                      child: const Text(
                        'Kirim Ulang OTP',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF16A34A),
                        ),
                      ),
                    ),
                ],
              ),

              const SizedBox(height: 16),

              TextButton.icon(
                onPressed: () {
                  context.read<AuthController>().resetOtpState();
                  Navigator.pop(context);
                },
                icon: const Icon(Icons.arrow_back_rounded, size: 14, color: Color(0xFF64748B)),
                label: const Text(
                  'Ganti Nomor / Kembali ke Login',
                  style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
