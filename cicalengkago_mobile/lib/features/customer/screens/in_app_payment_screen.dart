import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';

// Import conditional: native WebView di mobile, stub kosong di web
import 'in_app_payment_screen_native.dart'
    if (dart.library.js_interop) 'in_app_payment_screen_web.dart'
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
    this.title = 'Pembayaran DOKU',
    required this.onPaymentComplete,
  });

  @override
  State<InAppPaymentScreen> createState() => _InAppPaymentScreenState();
}

class _InAppPaymentScreenState extends State<InAppPaymentScreen> {
  bool _isPolling = false;
  bool _hasOpened = false;
  bool _isProcessingFinish = false;

  @override
  void initState() {
    super.initState();
  }

  Future<void> _openPaymentUrl() async {
    try {
      final uri = Uri.parse(widget.paymentUrl);
      final launched = await launchUrl(uri, webOnlyWindowName: '_self');
      if (mounted) {
        setState(() => _hasOpened = true);
      }
      if (!launched) {
        debugPrint('Browser menolak membuka popup URL');
      }
    } catch (e) {
      debugPrint('Gagal membuka URL pembayaran: $e');
    }
  }

  /// Polling status dari server setelah user klik "Sudah Bayar"
  Future<void> _handlePaymentDoneClick() async {
    if (_isProcessingFinish) return;
    setState(() {
      _isProcessingFinish = true;
      _isPolling = true;
    });

    bool isPaid = false;
    String finalStatus = 'pending';

    // Poll sampai 5x (tiap 2 detik) menunggu webhook DOKU
    for (int attempt = 0; attempt < 5; attempt++) {
      await Future.delayed(const Duration(seconds: 2));
      if (!mounted) return;

      try {
        final res = await ApiService.post(ApiConstants.paymentVerify, {
          'order_id': widget.orderId,
        });

        if (res['success'] == true) {
          isPaid = res['data']?['is_paid'] == true ||
              res['data']?['payment_status'] == 'paid' ||
              res['data']?['status'] == 'settled' ||
              res['data']?['status'] == 'success';
          finalStatus = res['data']?['status']?.toString() ?? 'pending';
          if (isPaid) break;
        }
      } catch (e) {
        debugPrint('Polling attempt $attempt: $e');
      }
    }

    if (!mounted) return;

    setState(() {
      _isPolling = false;
    });

    widget.onPaymentComplete();
    Navigator.pop(context, isPaid);

    final messenger = ScaffoldMessenger.of(context);
    if (isPaid) {
      messenger.showSnackBar(
        SnackBar(
          content: Text('🎉 Pembayaran ${CurrencyFormatter.formatRupiah(widget.amount)} Berhasil Dikonfirmasi!'),
          backgroundColor: Colors.green.shade700,
          duration: const Duration(seconds: 4),
        ),
      );
    } else {
      messenger.showSnackBar(
        SnackBar(
          content: Text(
            finalStatus == 'pending'
                ? '⏳ Pembayaran belum terkonfirmasi. Saldo/pesanan akan diperbarui otomatis jika pembayaran berhasil.'
                : 'Status: $finalStatus. Hubungi CS jika ada kendala.',
          ),
          backgroundColor: Colors.orange.shade700,
          duration: const Duration(seconds: 5),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    // Di Flutter Web: tampilkan halaman konfirmasi dengan tombol buka tab baru
    if (kIsWeb) {
      return _buildWebPaymentPage(context);
    }

    // Di mobile (Android/iOS): render native WebView
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
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        if (!_isPolling) {
          _cancelAndExit(context);
        }
      },
      child: Scaffold(
        backgroundColor: const Color(0xFFF8F9FA),
        appBar: AppBar(
          backgroundColor: AppTheme.primaryRed,
          foregroundColor: Colors.white,
          elevation: 0,
          title: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(widget.title,
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              Text(
                CurrencyFormatter.formatRupiah(widget.amount),
                style: const TextStyle(fontSize: 11, color: Colors.white70),
              ),
            ],
          ),
          bottom: _isPolling
              ? const PreferredSize(
                  preferredSize: Size.fromHeight(3.0),
                  child: LinearProgressIndicator(
                    color: Colors.green,
                    backgroundColor: Colors.white24,
                  ),
                )
              : null,
        ),
        body: _isPolling
            ? const Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    CircularProgressIndicator(color: AppTheme.primaryRed),
                    SizedBox(height: 16),
                    Text(
                      'Mengkonfirmasi Pembayaran...',
                      style: TextStyle(
                          color: Color(0xFF1A1A2E),
                          fontSize: 16,
                          fontWeight: FontWeight.bold),
                    ),
                    SizedBox(height: 8),
                    Text(
                      'Sedang menghubungi server DOKU untuk verifikasi.\nMohon tunggu sebentar...',
                      style: TextStyle(fontSize: 13, color: Colors.grey, height: 1.5),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              )
            : SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    const SizedBox(height: 16),

                    // Icon
                    Container(
                      width: 90,
                      height: 90,
                      decoration: BoxDecoration(
                        color: _hasOpened
                            ? Colors.green.withValues(alpha: 0.1)
                            : AppTheme.primaryRed.withValues(alpha: 0.1),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        _hasOpened
                            ? Icons.check_circle_outline_rounded
                            : Icons.open_in_browser_rounded,
                        size: 48,
                        color: _hasOpened ? Colors.green : AppTheme.primaryRed,
                      ),
                    ),
                    const SizedBox(height: 20),

                    Text(
                      _hasOpened
                          ? 'Halaman Pembayaran Sudah Terbuka'
                          : 'Mengarahkan ke Pembayaran DOKU...',
                      style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF1A1A2E)),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 10),
                    Text(
                      _hasOpened
                          ? 'Selesaikan pembayaran di halaman DOKU, lalu kembali dan konfirmasi di bawah.'
                          : 'Halaman pembayaran akan terbuka. Selesaikan di sana.',
                      style:
                          const TextStyle(fontSize: 13, color: Colors.grey, height: 1.5),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 28),

                    // Kartu info pembayaran
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.04),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Total Tagihan',
                                  style: TextStyle(
                                      color: Colors.grey, fontSize: 13)),
                              Text(
                                CurrencyFormatter.formatRupiah(widget.amount),
                                style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFF1A1A2E)),
                              ),
                            ],
                          ),
                          const Divider(height: 24),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Nomor Transaksi',
                                  style: TextStyle(
                                      color: Colors.grey, fontSize: 13)),
                              Text(
                                widget.orderId,
                                style: const TextStyle(
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFF1A1A2E),
                                    fontSize: 12),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          const Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text('Metode Pembayaran',
                                  style: TextStyle(
                                      color: Colors.grey, fontSize: 13)),
                              Text(
                                'DOKU Checkout Resmi',
                                style: TextStyle(
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFF1A1A2E),
                                    fontSize: 12),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Tombol Buka Halaman DOKU
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppTheme.primaryRed,
                          side: const BorderSide(color: AppTheme.primaryRed),
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: _openPaymentUrl,
                        icon: const Icon(Icons.open_in_browser_rounded),
                        label: Text(
                          _hasOpened
                              ? 'Buka Ulang Halaman DOKU'
                              : 'Buka Halaman Pembayaran DOKU',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),

                    // Tombol Konfirmasi Sudah Bayar (muncul setelah membuka DOKU)
                    if (_hasOpened) ...[
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green.shade600,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12)),
                            elevation: 0,
                          ),
                          onPressed: _handlePaymentDoneClick,
                          icon: const Icon(Icons.check_circle_rounded),
                          label: const Text(
                            'Saya Sudah Selesai Membayar',
                            style: TextStyle(fontWeight: FontWeight.bold),
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),

                      // Info: status diperbarui otomatis
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF0FDF4),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.green.shade200),
                        ),
                        child: const Row(
                          children: [
                            Icon(Icons.info_outline_rounded,
                                color: Colors.green, size: 20),
                            SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                'Status saldo/pesanan akan diperbarui otomatis melalui notifikasi server DOKU. Tidak perlu khawatir jika belum langsung berubah.',
                                style: TextStyle(
                                    fontSize: 11,
                                    color: Color(0xFF14532D),
                                    height: 1.4),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),
                    ],

                    TextButton(
                      onPressed: () => _cancelAndExit(context),
                      child: const Text('Batalkan & Kembali',
                          style: TextStyle(color: Colors.grey)),
                    ),
                  ],
                ),
              ),
      ),
    );
  }

  void _cancelAndExit(BuildContext context) {
    if (widget.orderId.startsWith('TOPUP-')) {
      try {
        ApiService.post('/payment/topup-update-status', {
          'order_id': widget.orderId,
          'status': 'canceled',
          'notes': 'Sesi pembayaran ditutup oleh pengguna',
        });
      } catch (_) {}
    }
    Navigator.pop(context, false);
  }
}
