import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../controllers/customer_controller.dart';
import 'order_tracking_screen.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _addressController = TextEditingController(text: 'Jl. Alun-Alun Cicalengka No. 12, Kab. Bandung');
  final _noteController = TextEditingController();
  String _paymentMethod = 'cicalengkapay';
  bool _isSubmitting = false;

  void _handleCheckout() async {
    setState(() {
      _isSubmitting = true;
    });

    final res = await ApiService.post(ApiConstants.checkout, {
      'address': _addressController.text,
      'payment_method': _paymentMethod,
      'order_note': _noteController.text,
      'latitude': '-6.9835',
      'longitude': '107.8335',
    });

    setState(() {
      _isSubmitting = false;
    });

    if (res['success'] == true && mounted) {
      final orderCode = res['data']?['order_code'] ?? res['data']?['order_id']?.toString() ?? '';
      
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pesanan Anda telah berhasil dibuat!')),
      );

      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(
          builder: (_) => OrderTrackingScreen(orderCode: orderCode),
        ),
        (route) => route.isFirst,
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['message'] ?? 'Gagal membuat pesanan. Silakan coba lagi.'),
          backgroundColor: Colors.redAccent,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Checkout & Pengantaran'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Alamat Pengantaran',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _addressController,
              maxLines: 2,
              decoration: const InputDecoration(
                prefixIcon: Icon(Icons.location_on, color: AppTheme.primaryRed),
                labelText: 'Alamat Lengkap Cicalengka',
              ),
            ),
            const SizedBox(height: 16),

            const Text(
              'Catatan untuk Kurir / Resto (Opsional)',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _noteController,
              decoration: const InputDecoration(
                prefixIcon: Icon(Icons.note_alt_outlined),
                hintText: 'Misal: Pedas sedang, pagar warna hijau',
              ),
            ),
            const SizedBox(height: 24),

            const Text(
              'Metode Pembayaran',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
            ),
            const SizedBox(height: 8),

            RadioListTile<String>(
              value: 'cicalengkapay',
              groupValue: _paymentMethod,
              title: const Text('CicalengkaPay (Saldo Otomatis)'),
              subtitle: const Text('Bebas biaya penanganan'),
              secondary: const Icon(Icons.account_balance_wallet, color: AppTheme.primaryRed),
              onChanged: (val) {
                setState(() {
                  _paymentMethod = val!;
                });
              },
            ),

            RadioListTile<String>(
              value: 'cash_on_delivery',
              groupValue: _paymentMethod,
              title: const Text('Bayar Tunai / COD'),
              subtitle: const Text('Bayar langsung ke Kurir saat sampai'),
              secondary: const Icon(Icons.payments, color: Colors.green),
              onChanged: (val) {
                setState(() {
                  _paymentMethod = val!;
                });
              },
            ),

            const SizedBox(height: 30),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _isSubmitting ? null : _handleCheckout,
                child: _isSubmitting
                    ? const SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text('BUAT PESANAN SEKARANG'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
