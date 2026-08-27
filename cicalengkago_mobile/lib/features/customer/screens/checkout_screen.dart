import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../controllers/customer_controller.dart';
import 'order_tracking_screen.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _addressController = TextEditingController(text: 'Jl. Raya Cicalengka No. 88, Kab. Bandung');
  final _noteController = TextEditingController();
  String _paymentMethod = 'wallet';
  bool _isSubmitting = false;

  void _handleCheckout() async {
    final customerCtrl = context.read<CustomerController>();
    final cart = customerCtrl.cart;
    final stores = (cart?['stores'] as List<dynamic>?) ?? [];
    final storeId = int.tryParse(stores.isNotEmpty ? stores[0]['store_id']?.toString() ?? '1' : '1') ?? 1;

    setState(() {
      _isSubmitting = true;
    });

    final res = await customerCtrl.placeOrder(
      storeId: storeId,
      deliveryAddress: _addressController.text.trim(),
      lat: -6.9835,
      lng: 107.8335,
      paymentMethod: _paymentMethod,
      note: _noteController.text.trim().isNotEmpty ? _noteController.text.trim() : null,
    );

    setState(() {
      _isSubmitting = false;
    });

    if (mounted) {
      if (res['success'] == true) {
        final orderCode = res['data']?['order_code'] ?? res['data']?['order_id']?.toString() ?? res['order_code'] ?? '';

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('🎉 Pesanan Anda telah berhasil dibuat!'),
            backgroundColor: Colors.green,
          ),
        );

        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(
            builder: (_) => OrderTrackingScreen(orderCode: orderCode),
          ),
          (route) => route.isFirst,
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message'] ?? 'Gagal membuat pesanan. Silakan coba lagi.'),
            backgroundColor: AppTheme.primaryRed,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        foregroundColor: const Color(0xFF0F172A),
        title: const Text('Checkout & Pengantaran', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Alamat Pengantaran',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _addressController,
              maxLines: 2,
              decoration: InputDecoration(
                prefixIcon: const Icon(Icons.location_on_rounded, color: AppTheme.primaryRed),
                labelText: 'Alamat Lengkap Cicalengka',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
            const SizedBox(height: 16),

            const Text(
              'Catatan untuk Kurir / Resto (Opsional)',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _noteController,
              decoration: InputDecoration(
                prefixIcon: const Icon(Icons.note_alt_outlined),
                hintText: 'Misal: Pedas sedang, pagar warna hijau',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
            const SizedBox(height: 20),

            const Text(
              'Metode Pembayaran',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 8),

            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                children: [
                  RadioListTile<String>(
                    value: 'wallet',
                    groupValue: _paymentMethod,
                    title: const Text('CicalengkaPay (Saldo E-Wallet)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    subtitle: const Text('Bebas biaya penanganan', style: TextStyle(fontSize: 11)),
                    secondary: const Icon(Icons.account_balance_wallet_rounded, color: AppTheme.primaryRed),
                    onChanged: (val) => setState(() => _paymentMethod = val!),
                  ),
                  const Divider(height: 1),
                  RadioListTile<String>(
                    value: 'cod',
                    groupValue: _paymentMethod,
                    title: const Text('Bayar Tunai / COD', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    subtitle: const Text('Bayar langsung ke Kurir saat sampai', style: TextStyle(fontSize: 11)),
                    secondary: const Icon(Icons.payments_rounded, color: Color(0xFF059669)),
                    onChanged: (val) => setState(() => _paymentMethod = val!),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 30),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppTheme.primaryRed,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  elevation: 2,
                ),
                onPressed: _isSubmitting ? null : _handleCheckout,
                child: _isSubmitting
                    ? const SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text('BUAT PESANAN SEKARANG', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
