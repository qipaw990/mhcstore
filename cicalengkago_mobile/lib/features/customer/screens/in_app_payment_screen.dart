import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';

// WebView hanya diimport di platform native — di web akan null/error sehingga
// kita pisahkan di class terpisah yang hanya diinstansiasi ketika !kIsWeb
export 'in_app_payment_screen_native.dart'
    if (dart.library.html) 'in_app_payment_screen_web.dart';

class InAppPaymentScreen extends StatefulWidget {
  final String paymentUrl;
  final String orderId;
  final double amount;
  final String title;
  final VoidCallback onPaymentComplete;

  const InAppPaymentScreen({
    super.key,
    required this.paymentUrl,
    required this.orderId,
    required this.amount,
    this.title = 'Pembayaran',
    required this.onPaymentComplete,
  });

  @override
  State<InAppPaymentScreen> createState() => _InAppPaymentScreenState();
}

class _InAppPaymentScreenState extends State<InAppPaymentScreen> {
  bool _isLoading = false;
  bool _isProcessingFinish = false;
  bool _hasOpened = false;

  @override
  void initState() {
    super.initState();
    if (kIsWeb) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _openPaymentUrl();
      });
    }
  }

  Future<void> _openPaymentUrl() async {
    try {
      final uri = Uri.parse(widget.paymentUrl);
      await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (mounted) {
        setState(() => _hasOpened = true);
      }
    } catch (e) {
      debugPrint('Gagal membuka URL pembayaran: $e');
    }
  }

  Future<void> _handlePaymentSuccess() async {
    if (_isProcessingFinish) return;
    setState(() => _isProcessingFinish = true);

    try {
      await ApiService.post(ApiConstants.paymentVerify, {
        'order_id': widget.orderId,
      });
    } catch (_) {}

    if (!mounted) return;
    widget.onPaymentComplete();
    Navigator.pop(context, true);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('🎉 Pembayaran ${CurrencyFormatter.formatRupiah(widget.amount)} Berhasil!'),
        backgroundColor: Colors.green.shade700,
        duration: const Duration(seconds: 4),
      ),
    );
  }

  Future<void> _simulateSandboxPayment() async {
    setState(() => _isLoading = true);
    try {
      final res = await ApiService.post(ApiConstants.paymentSimulate, {
        'order_id': widget.orderId,
        'amount': widget.amount,
        'payment_type': 'midtrans_sandbox_inapp',
      });
      if (res['success'] == true && mounted) {
        widget.onPaymentComplete();
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('🎉 Pembayaran ${CurrencyFormatter.formatRupiah(widget.amount)} Berhasil (Sandbox)!'),
            backgroundColor: Colors.green.shade700,
            duration: const Duration(seconds: 4),
          ),
        );
        return;
      }
    } catch (e) {
      debugPrint('Sandbox payment error: $e');
    }
    if (mounted) setState(() => _isLoading = false);
  }

  @override
  Widget build(BuildContext context) {
    // Di Flutter Web: tampilkan halaman konfirmasi dengan tombol buka tab baru
    if (kIsWeb) {
      return _buildWebPaymentPage(context);
    }

    // Di mobile (Android/iOS): render native WebView lewat file terpisah
    return buildNativePaymentScreen(
      context: context,
      paymentUrl: widget.paymentUrl,
      orderId: widget.orderId,
      amount: widget.amount,
      title: widget.title,
      onPaymentComplete: widget.onPaymentComplete,
    );
  }

  Widget _buildWebPaymentPage(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        backgroundColor: AppTheme.primaryRed,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(widget.title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            Text(
              CurrencyFormatter.formatRupiah(widget.amount),
              style: const TextStyle(fontSize: 11, color: Colors.white70),
            ),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(color: AppTheme.primaryRed),
                  SizedBox(height: 16),
                  Text('Memproses...', style: TextStyle(color: Colors.grey)),
                ],
              ),
            )
          : SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  const SizedBox(height: 16),
                  Container(
                    width: 90,
                    height: 90,
                    decoration: BoxDecoration(
                      color: AppTheme.primaryRed.withOpacity(0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.open_in_browser_rounded, size: 48, color: AppTheme.primaryRed),
                  ),
                  const SizedBox(height: 20),
                  Text(
                    _hasOpened ? 'Halaman Pembayaran Telah Dibuka' : 'Mengarahkan ke Pembayaran...',
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1A1A2E)),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 10),
                  Text(
                    _hasOpened
                        ? 'Tab pembayaran sudah terbuka. Selesaikan di sana, lalu konfirmasi di bawah.'
                        : 'Halaman pembayaran akan terbuka di tab baru...',
                    style: const TextStyle(fontSize: 13, color: Colors.grey, height: 1.5),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 28),

                  // Jumlah
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.grey.shade200),
                      boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 10, offset: const Offset(0, 4))],
                    ),
                    child: Column(
                      children: [
                        const Text('Total Pembayaran', style: TextStyle(fontSize: 13, color: Colors.grey)),
                        const SizedBox(height: 4),
                        Text(
                          CurrencyFormatter.formatRupiah(widget.amount),
                          style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                        ),
                        const SizedBox(height: 2),
                        Text('ID: ${widget.orderId}', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Buka / buka ulang
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.primaryRed,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 0,
                      ),
                      onPressed: _openPaymentUrl,
                      icon: const Icon(Icons.open_in_browser),
                      label: Text(
                        _hasOpened ? 'Buka Ulang Halaman Pembayaran' : 'Buka Halaman Pembayaran',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),

                  // Tombol konfirmasi (muncul setelah dibuka)
                  if (_hasOpened) ...[
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.green,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          elevation: 0,
                        ),
                        onPressed: _handlePaymentSuccess,
                        icon: const Icon(Icons.check_circle),
                        label: const Text(
                          'Saya Sudah Selesai Membayar ✓',
                          style: TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                  ],

                  // Sandbox mode
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFFBEB),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.amber.shade300),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.flash_on, color: Colors.amber, size: 20),
                        const SizedBox(width: 10),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Mode Pengujian (Sandbox)',
                                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF92400E))),
                              Text('Simulasi bayar tanpa membuka Midtrans.',
                                  style: TextStyle(fontSize: 11, color: Color(0xFFB45309))),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.amber.shade700,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                            elevation: 0,
                          ),
                          onPressed: _simulateSandboxPayment,
                          child: const Text('Bayar Instant', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  TextButton(
                    onPressed: () => Navigator.pop(context, false),
                    child: const Text('Batalkan', style: TextStyle(color: Colors.grey)),
                  ),
                ],
              ),
            ),
    );
  }
}
