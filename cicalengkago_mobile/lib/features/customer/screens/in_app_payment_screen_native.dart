import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';

/// Dipanggil hanya di platform native (Android/iOS)
Widget buildNativePaymentScreen({
  required BuildContext context,
  required String paymentUrl,
  required String orderId,
  required double amount,
  required String title,
  required VoidCallback onPaymentComplete,
}) {
  return _NativePaymentWebView(
    paymentUrl: paymentUrl,
    orderId: orderId,
    amount: amount,
    title: title,
    onPaymentComplete: onPaymentComplete,
  );
}

class _NativePaymentWebView extends StatefulWidget {
  final String paymentUrl;
  final String orderId;
  final double amount;
  final String title;
  final VoidCallback onPaymentComplete;

  const _NativePaymentWebView({
    required this.paymentUrl,
    required this.orderId,
    required this.amount,
    required this.title,
    required this.onPaymentComplete,
  });

  @override
  State<_NativePaymentWebView> createState() => _NativePaymentWebViewState();
}

class _NativePaymentWebViewState extends State<_NativePaymentWebView> {
  late final WebViewController _controller;
  bool _isLoading = true;
  bool _isProcessingFinish = false;
  bool _isPolling = false;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..addJavaScriptChannel(
        'PaymentChannel',
        onMessageReceived: (JavaScriptMessage message) {
          debugPrint('PaymentChannel received: ${message.message}');
          try {
            final msg = message.message.toLowerCase();
            if (msg.contains('success') ||
                msg.contains('settlement') ||
                msg.contains('paid')) {
              _handlePaymentCallback();
            }
          } catch (_) {}
        },
      )
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            if (mounted) setState(() => _isLoading = true);
            _checkPaymentCallbackUrl(url);
          },
          onPageFinished: (String url) {
            if (mounted) setState(() => _isLoading = false);
            _checkPaymentCallbackUrl(url);
          },
          onUrlChange: (UrlChange change) {
            if (change.url != null) {
              _checkPaymentCallbackUrl(change.url!);
            }
          },
          onWebResourceError: (WebResourceError error) {
            debugPrint('WebView Error: ${error.description}');
            if (mounted) setState(() => _isLoading = false);
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.paymentUrl));
  }

  /// Deteksi apakah user sudah diredirect ke callback URL DOKU production
  /// URL callback kita: /payment/doku/callback?status=...&order=...
  void _checkPaymentCallbackUrl(String url) {
    if (_isProcessingFinish) return;

    final lowerUrl = url.toLowerCase();

    // Deteksi URL callback DOKU kita sendiri
    if (lowerUrl.contains('/payment/doku/callback')) {
      // Ambil status dari query parameter
      final uri = Uri.tryParse(url);
      final status = uri?.queryParameters['status']?.toUpperCase() ?? '';
      final resultCode = uri?.queryParameters['result_code'] ?? '';

      if (status == 'SUCCESS' ||
          status == 'COMPLETED' ||
          status == 'PAID' ||
          resultCode == '00') {
        // Pembayaran berhasil → poll status lalu keluar
        _handlePaymentCallback(confirmedSuccess: true);
      } else if (status == 'PENDING' || status == 'PROCESS') {
        // Masih menunggu konfirmasi server
        _handlePaymentCallback(confirmedSuccess: false);
      } else if (status.isNotEmpty) {
        // Status lain (FAILED, EXPIRED, CANCEL) → tutup saja
        _handlePaymentCallback(confirmedSuccess: false);
      } else {
        // Callback URL terbuka tanpa status jelas — anggap pending
        _handlePaymentCallback(confirmedSuccess: false);
      }
      return;
    }

    // Deteksi redirect ke halaman /wallet atau /orders kita (fallback lama)
    if (lowerUrl.contains(RegExp(r'cicago\.store/(wallet|orders)')) ||
        lowerUrl.contains('/wallet') && lowerUrl.contains('cicago')) {
      _handlePaymentCallback(confirmedSuccess: false);
    }
  }

  /// Handle ketika DOKU redirect ke callback URL kita
  /// [confirmedSuccess] true jika DOKU melaporkan sukses lewat redirect
  Future<void> _handlePaymentCallback({bool confirmedSuccess = false}) async {
    if (_isProcessingFinish) return;
    setState(() {
      _isProcessingFinish = true;
      _isPolling = true;
    });

    // Poll status dari server kita (menunggu webhook DOKU mengupdate DB)
    String finalStatus = 'pending';
    bool isPaid = false;

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

          if (isPaid) break; // Status sudah paid, tidak perlu polling lagi
        }
      } catch (e) {
        debugPrint('Polling error (attempt $attempt): $e');
      }
    }

    if (!mounted) return;

    setState(() => _isPolling = false);
    widget.onPaymentComplete();
    Navigator.pop(context, isPaid);

    if (isPaid) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('🎉 Pembayaran ${CurrencyFormatter.formatRupiah(widget.amount)} Berhasil Dikonfirmasi!'),
          backgroundColor: Colors.green.shade700,
          duration: const Duration(seconds: 4),
        ),
      );
    } else if (confirmedSuccess) {
      // DOKU melaporkan sukses tapi webhook belum masuk
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('⏳ Pembayaran diterima DOKU, konfirmasi saldo/pesanan akan segera diperbarui otomatis.'),
          backgroundColor: Colors.orange,
          duration: Duration(seconds: 5),
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Status pembayaran: $finalStatus. Saldo/pesanan akan diperbarui otomatis jika pembayaran berhasil.'),
          backgroundColor: Colors.grey.shade700,
          duration: const Duration(seconds: 4),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        if (!_isProcessingFinish) {
          _showCancelDialog(context);
        }
      },
      child: Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(
          backgroundColor: AppTheme.primaryRed,
          foregroundColor: Colors.white,
          elevation: 1,
          title: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                widget.title,
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
              Row(
                children: [
                  const Icon(Icons.lock, size: 10, color: Colors.white70),
                  const SizedBox(width: 4),
                  Text(
                    'Pembayaran Aman via DOKU • ${CurrencyFormatter.formatRupiah(widget.amount)}',
                    style: const TextStyle(fontSize: 10, color: Colors.white70),
                  ),
                ],
              ),
            ],
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.refresh),
              tooltip: 'Muat Ulang',
              onPressed: _isProcessingFinish ? null : () => _controller.reload(),
            ),
            IconButton(
              icon: const Icon(Icons.close),
              tooltip: 'Tutup',
              onPressed: _isProcessingFinish ? null : () => _showCancelDialog(context),
            ),
          ],
          bottom: (_isLoading || _isPolling)
              ? PreferredSize(
                  preferredSize: const Size.fromHeight(3.0),
                  child: LinearProgressIndicator(
                    color: _isPolling ? Colors.green : Colors.amber,
                    backgroundColor: Colors.white24,
                  ),
                )
              : null,
        ),
        body: SafeArea(
          child: Stack(
            children: [
              WebViewWidget(controller: _controller),

              // Overlay polling (menunggu konfirmasi server)
              if (_isPolling)
                Container(
                  color: Colors.black54,
                  child: const Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        CircularProgressIndicator(color: Colors.white),
                        SizedBox(height: 16),
                        Text(
                          'Mengkonfirmasi Pembayaran...',
                          style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold),
                        ),
                        SizedBox(height: 6),
                        Text(
                          'Mohon tunggu, sedang menghubungi server DOKU',
                          style: TextStyle(color: Colors.white70, fontSize: 12),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                ),

              // Loading overlay
              if (_isLoading && !_isPolling)
                const Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      CircularProgressIndicator(color: AppTheme.primaryRed),
                      SizedBox(height: 12),
                      Text(
                        'Memuat Gerbang Pembayaran DOKU...',
                        style: TextStyle(color: Colors.grey, fontSize: 13),
                      ),
                    ],
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  void _showCancelDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Batalkan Pembayaran?',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        content: const Text(
          'Jika Anda sudah membayar di DOKU, saldo/pesanan akan diperbarui otomatis dalam beberapa menit. Yakin ingin keluar?',
          style: TextStyle(fontSize: 13),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Lanjutkan Bayar'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red, foregroundColor: Colors.white),
            onPressed: () {
              Navigator.pop(ctx);
              // Jika ini topup, update status log jadi canceled di backend agar tidak bisa dipakai lagi
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
            },
            child: const Text('Keluar'),
          ),
        ],
      ),
    );
  }
}
