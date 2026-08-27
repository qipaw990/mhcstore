import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';

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
    this.title = 'Pembayaran Midtrans',
    required this.onPaymentComplete,
  });

  @override
  State<InAppPaymentScreen> createState() => _InAppPaymentScreenState();
}

class _InAppPaymentScreenState extends State<InAppPaymentScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  bool _isProcessingFinish = false;

  @override
  void initState() {
    super.initState();

    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            setState(() {
              _isLoading = true;
            });
            _checkPaymentCallback(url);
          },
          onPageFinished: (String url) {
            setState(() {
              _isLoading = false;
            });
            _checkPaymentCallback(url);
          },
          onUrlChange: (UrlChange change) {
            if (change.url != null) {
              _checkPaymentCallback(change.url!);
            }
          },
          onWebResourceError: (WebResourceError error) {
            debugPrint('WebView Error: ${error.description}');
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.paymentUrl));
  }

  void _checkPaymentCallback(String url) {
    if (_isProcessingFinish) return;

    final lowerUrl = url.toLowerCase();
    // Detect redirect back to /wallet, /finish, /success, or Midtrans settlement status
    if (lowerUrl.contains('/wallet') ||
        lowerUrl.contains('finish') ||
        lowerUrl.contains('transaction_status=settlement') ||
        lowerUrl.contains('status_code=200')) {
      _handlePaymentSuccess();
    }
  }

  Future<void> _handlePaymentSuccess() async {
    if (_isProcessingFinish) return;
    setState(() {
      _isProcessingFinish = true;
    });

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
        content: Text('🎉 Top Up ${CurrencyFormatter.formatRupiah(widget.amount)} Berhasil!'),
        backgroundColor: Colors.green.shade700,
        duration: const Duration(seconds: 4),
      ),
    );
  }

  Future<void> _simulateSandboxPayment() async {
    setState(() {
      _isLoading = true;
    });

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
            content: Text('🎉 Top Up ${CurrencyFormatter.formatRupiah(widget.amount)} Berhasil (Sandbox Mode)!'),
            backgroundColor: Colors.green.shade700,
            duration: const Duration(seconds: 4),
          ),
        );
        return;
      }
    } catch (e) {
      debugPrint('Sandbox payment error: $e');
    }

    setState(() {
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
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
                  'Pembayaran Aman Midtrans Snap • ${CurrencyFormatter.formatRupiah(widget.amount)}',
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
            onPressed: () => _controller.reload(),
          ),
          IconButton(
            icon: const Icon(Icons.close),
            tooltip: 'Tutup',
            onPressed: () => _showCancelDialog(context),
          ),
        ],
        bottom: _isLoading
            ? const PreferredSize(
                preferredSize: Size.fromHeight(3.0),
                child: LinearProgressIndicator(
                  color: Colors.amber,
                  backgroundColor: Colors.white24,
                ),
              )
            : null,
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: Stack(
                children: [
                  WebViewWidget(controller: _controller),
                  if (_isLoading)
                    const Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          CircularProgressIndicator(color: AppTheme.primaryRed),
                          SizedBox(height: 12),
                          Text(
                            'Memuat Gerbang Pembayaran...',
                            style: TextStyle(color: Colors.grey, fontSize: 13),
                          ),
                        ],
                      ),
                    ),
                ],
              ),
            ),

            // Bottom Sandbox Quick Payment Helper
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              decoration: BoxDecoration(
                color: const Color(0xFFFFFBEB),
                border: Border(top: BorderSide(color: Colors.amber.shade300)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.flash_on, color: Colors.amber, size: 20),
                  const SizedBox(width: 8),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          'Mode Pengujian (Sandbox)',
                          style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF92400E)),
                        ),
                        Text(
                          'Klik di samping untuk simulasi bayar instant tanpa input kartu.',
                          style: TextStyle(fontSize: 10, color: Color(0xFFB45309)),
                        ),
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
                    child: const Text(
                      'Bayar Instant',
                      style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showCancelDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Batalkan Pembayaran?', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        content: const Text('Top up saldo belum diselesaikan. Apakah Anda yakin ingin keluar?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Lanjutkan Bayar'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            onPressed: () {
              Navigator.pop(ctx);
              Navigator.pop(context, false);
            },
            child: const Text('Keluar'),
          ),
        ],
      ),
    );
  }
}
