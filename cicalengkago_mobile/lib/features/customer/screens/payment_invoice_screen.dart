import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/app_alert.dart';
import '../controllers/customer_controller.dart';

class PaymentInvoiceScreen extends StatefulWidget {
  final Map<String, dynamic> invoiceData;

  const PaymentInvoiceScreen({super.key, required this.invoiceData});

  @override
  State<PaymentInvoiceScreen> createState() => _PaymentInvoiceScreenState();
}

class _PaymentInvoiceScreenState extends State<PaymentInvoiceScreen> {
  late Map<String, dynamic> _invoice;
  Timer? _pollingTimer;
  Timer? _countdownTimer;
  Duration _remainingDuration = const Duration(hours: 2);
  bool _isChecking = false;
  bool _isSimulating = false;
  bool _isPaid = false;

  @override
  void initState() {
    super.initState();
    _invoice = Map<String, dynamic>.from(widget.invoiceData);

    _initCountdown();
    _startPolling();
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    _countdownTimer?.cancel();
    super.dispose();
  }

  void _initCountdown() {
    final expiresAtStr = _invoice['expires_at']?.toString();
    if (expiresAtStr != null) {
      try {
        final expiresAt = DateTime.parse(expiresAtStr);
        final diff = expiresAt.difference(DateTime.now());
        if (diff.isNegative) {
          _remainingDuration = Duration.zero;
        } else {
          _remainingDuration = diff;
        }
      } catch (_) {}
    }

    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      if (_remainingDuration.inSeconds > 0) {
        setState(() {
          _remainingDuration -= const Duration(seconds: 1);
        });
      } else {
        timer.cancel();
      }
    });
  }

  void _startPolling() {
    _pollingTimer?.cancel();
    _pollingTimer = Timer.periodic(const Duration(seconds: 4), (_) {
      _checkPaymentStatus(silent: true);
    });
  }

  Future<void> _checkPaymentStatus({bool silent = false}) async {
    final code = _invoice['invoice_code']?.toString() ?? '';
    if (code.isEmpty || _isPaid) return;

    if (!silent) {
      setState(() => _isChecking = true);
    }

    try {
      final res = await ApiService.get('${ApiConstants.paymentCheckInvoice}?code=$code');
      if (res['success'] == true && res['data'] != null) {
        final status = res['data']['status']?.toString();
        if (status == 'paid') {
          _pollingTimer?.cancel();
          _countdownTimer?.cancel();
          setState(() {
            _isPaid = true;
            _invoice['status'] = 'paid';
          });

          if (mounted) {
            context.read<CustomerController>().fetchWallet();
            _showSuccessDialog();
          }
        } else if (!silent && mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Belum ada dana masuk. Sistem terus mengecek otomatis...'),
              backgroundColor: Color(0xFF0F172A),
              duration: Duration(seconds: 2),
            ),
          );
        }
      }
    } catch (_) {}

    if (mounted && !silent) {
      setState(() => _isChecking = false);
    }
  }

  Future<void> _simulateAutoPay() async {
    final code = _invoice['invoice_code']?.toString() ?? '';
    if (code.isEmpty) return;

    setState(() => _isSimulating = true);

    try {
      final res = await ApiService.post(ApiConstants.paymentSimulatePay, {
        'invoice_code': code,
      });

      if (res['success'] == true) {
        _pollingTimer?.cancel();
        _countdownTimer?.cancel();
        setState(() {
          _isPaid = true;
          _invoice['status'] = 'paid';
        });

        if (mounted) {
          context.read<CustomerController>().fetchWallet();
          _showSuccessDialog();
        }
      } else if (mounted) {
        AppAlert.showError(context, title: 'Gagal', message: res['message'] ?? 'Gagal simulasi');
      }
    } catch (e) {
      if (mounted) {
        AppAlert.showError(context, title: 'Error', message: e.toString());
      }
    }

    if (mounted) {
      setState(() => _isSimulating = false);
    }
  }

  void _showSuccessDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: const BoxDecoration(
                color: Color(0xFFDCFCE7),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.check_circle_rounded, color: Color(0xFF16A34A), size: 48),
            ),
            const SizedBox(height: 16),
            const Text(
              'Pembayaran Berhasil! 🎉',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 8),
            Text(
              'Dana sebesar ${CurrencyFormatter.formatRupiah(double.tryParse(_invoice['base_amount']?.toString() ?? '0') ?? 0)} telah berhasil diverifikasi otomatis oleh sistem.',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 12.5, color: Color(0xFF64748B), height: 1.4),
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF16A34A),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
                onPressed: () {
                  Navigator.pop(ctx); // Close dialog
                  Navigator.pop(context, true); // Close invoice screen with true
                },
                child: const Text('Kembali ke Dompet', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatDuration(Duration d) {
    String twoDigits(int n) => n.toString().padLeft(2, '0');
    final hours = twoDigits(d.inHours);
    final minutes = twoDigits(d.inMinutes.remainder(60));
    final seconds = twoDigits(d.inSeconds.remainder(60));
    return '$hours:$minutes:$seconds';
  }

  void _copyToClipboard(String text, String label) {
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('$label berhasil disalin!'),
        backgroundColor: const Color(0xFF0F172A),
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 1),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final invoiceCode = _invoice['invoice_code']?.toString() ?? '-';
    final bankName = _invoice['bank_name']?.toString() ?? 'Bank Transfer';
    final accNum = _invoice['account_number']?.toString() ?? '-';
    final accName = _invoice['account_name']?.toString() ?? 'CICALENGKA MEDIA SOLUSI';
    final baseAmount = double.tryParse(_invoice['base_amount']?.toString() ?? '0') ?? 0.0;
    final uniqueCode = int.tryParse(_invoice['unique_code']?.toString() ?? '0') ?? 0;
    final totalAmount = double.tryParse(_invoice['total_amount']?.toString() ?? (baseAmount + uniqueCode).toString()) ?? (baseAmount + uniqueCode);
    final qrisQrUrl = _invoice['qris_qr_url']?.toString();
    final isQris = bankName.toUpperCase().contains('QRIS') || accNum.contains('NMID');

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0.5,
        foregroundColor: const Color(0xFF0F172A),
        title: const Text(
          'Instruksi Pembayaran',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Status & Countdown Card
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: _isPaid ? const Color(0xFFDCFCE7) : const Color(0xFFFFFBEB),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: _isPaid ? const Color(0xFF86EFAC) : const Color(0xFFFDE68A),
                ),
              ),
              child: Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: _isPaid ? const Color(0xFF16A34A) : const Color(0xFFD97706),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      _isPaid ? Icons.check_rounded : Icons.timer_outlined,
                      color: Colors.white,
                      size: 24,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _isPaid ? 'Pembayaran LUNAS' : 'Menunggu Transfer',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: _isPaid ? const Color(0xFF166534) : const Color(0xFF92400E),
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          _isPaid
                              ? 'Transaksi telah diselesaikan'
                              : 'Selesaikan dalam: ${_formatDuration(_remainingDuration)}',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: _isPaid ? const Color(0xFF15803D) : const Color(0xFFB45309),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // Total Amount Card with Highlighted Unique Code
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.03),
                    blurRadius: 10,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'TOTAL YANG HARUS DITRANSFER',
                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF64748B), letterSpacing: 0.5),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        CurrencyFormatter.formatRupiah(totalAmount),
                        style: const TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.w900,
                          color: Color(0xFFEF4444),
                        ),
                      ),
                      InkWell(
                        onTap: () => _copyToClipboard(totalAmount.toStringAsFixed(0), 'Total nominal'),
                        borderRadius: BorderRadius.circular(8),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFEF2F2),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: const Color(0xFFFECACA)),
                          ),
                          child: const Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.copy_rounded, size: 14, color: Color(0xFFEF4444)),
                              SizedBox(width: 4),
                              Text('Salin', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFFEF4444))),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  const Divider(height: 1),
                  const SizedBox(height: 10),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Nominal Top Up', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                      Text(CurrencyFormatter.formatRupiah(baseAmount), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Kode Unik Transaksi', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                      Text('+$uniqueCode', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF16A34A))),
                    ],
                  ),
                  const SizedBox(height: 14),

                  // Important Note Alert
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEF2F2),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFFCA5A5)),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: const [
                        Icon(Icons.warning_amber_rounded, size: 18, color: Color(0xFFEF4444)),
                        SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'PENTING: Mohon transfer tepat hingga 3 digit terakhir agar sistem dapat memverifikasi & menambah saldo Anda secara instan otomatis tanpa jeda.',
                            style: TextStyle(fontSize: 11, color: Color(0xFF991B1B), height: 1.4, fontWeight: FontWeight.w500),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // Destination Card (QRIS / Bank)
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Icon(
                          isQris ? Icons.qr_code_2_rounded : Icons.account_balance_rounded,
                          color: const Color(0xFF0F172A),
                          size: 20,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              bankName,
                              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                            ),
                            Text(
                              isQris ? 'Scan QRIS untuk pembayaran instan' : 'Rekening Resmi CicalengkaGO',
                              style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),

                  if (isQris && qrisQrUrl != null) ...[
                    // Display Dynamic QRIS Code
                    Center(
                      child: Column(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: const Color(0xFFE2E8F0), width: 1.5),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.05),
                                  blurRadius: 10,
                                ),
                              ],
                            ),
                            child: CachedNetworkImage(
                              imageUrl: qrisQrUrl,
                              width: 200,
                              height: 200,
                              placeholder: (_, __) => const SizedBox(
                                width: 200,
                                height: 200,
                                child: Center(child: CircularProgressIndicator(color: Color(0xFFEF4444))),
                              ),
                              errorWidget: (_, __, ___) => Container(
                                width: 200,
                                height: 200,
                                color: Colors.grey[100],
                                child: const Center(child: Icon(Icons.qr_code, size: 60, color: Colors.grey)),
                              ),
                            ),
                          ),
                          const SizedBox(height: 10),
                          const Text(
                            'Mendukung GoPay, OVO, DANA, BCA, Mandiri, BRI, ShopeePay',
                            style: TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    ),
                  ] else ...[
                    // Bank Account Number Card
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Nomor Rekening', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                          const SizedBox(height: 4),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                accNum,
                                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF0F172A), letterSpacing: 1),
                              ),
                              InkWell(
                                onTap: () => _copyToClipboard(accNum, 'Nomor rekening'),
                                child: const Padding(
                                  padding: EdgeInsets.all(4.0),
                                  child: Icon(Icons.copy_rounded, size: 18, color: Color(0xFFEF4444)),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Text(
                            'Atas Nama: $accName',
                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF475569)),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),

            const SizedBox(height: 20),

            // Action Buttons
            SizedBox(
              width: double.infinity,
              height: 46,
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFEF4444),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 1,
                ),
                onPressed: _isChecking ? null : () => _checkPaymentStatus(silent: false),
                icon: _isChecking
                    ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Icon(Icons.sync_rounded, size: 18),
                label: Text(
                  _isChecking ? 'Mengecek Mutasi...' : 'Saya Sudah Transfer / Cek Status',
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
                ),
              ),
            ),

            const SizedBox(height: 10),

            // Demo Simulator Button (for testing & immediate approval)
            SizedBox(
              width: double.infinity,
              height: 42,
              child: OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFF16A34A),
                  side: const BorderSide(color: Color(0xFF16A34A)),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                onPressed: _isSimulating ? null : _simulateAutoPay,
                icon: _isSimulating
                    ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Color(0xFF16A34A), strokeWidth: 2))
                    : const Icon(Icons.bolt_rounded, size: 18),
                label: const Text('⚡ Simulasi Bayar Otomatis (Demo)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
              ),
            ),

            const SizedBox(height: 16),

            // WhatsApp Support Link
            Center(
              child: TextButton.icon(
                onPressed: () async {
                  final text = 'Halo Admin CicalengkaGO, saya ingin konfirmasi pembayaran invoice $invoiceCode sebesar ${CurrencyFormatter.formatRupiah(totalAmount)}';
                  final uri = Uri.parse('https://wa.me/62895333190888?text=${Uri.encodeComponent(text)}');
                  if (await canLaunchUrl(uri)) {
                    await launchUrl(uri, mode: LaunchMode.externalApplication);
                  }
                },
                icon: const Icon(Icons.chat_bubble_outline_rounded, size: 16, color: Color(0xFF16A34A)),
                label: const Text(
                  'Butuh Bantuan? Hubungi Admin WhatsApp',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF16A34A)),
                ),
              ),
            ),

            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }
}
