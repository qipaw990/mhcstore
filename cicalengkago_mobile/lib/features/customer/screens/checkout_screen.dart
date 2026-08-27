import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:http/http.dart' as http;
import '../../../core/theme/app_theme.dart';
import '../../../core/services/location_service.dart';
import '../controllers/customer_controller.dart';
import 'order_tracking_screen.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _addressController = TextEditingController();
  final _noteController = TextEditingController();
  String _paymentMethod = 'wallet';
  bool _isSubmitting = false;
  bool _isFetchingLocation = true;

  double _userLat = -6.9835;
  double _userLng = 107.8335;
  String _gpsStatusText = 'Mendeteksi lokasi GPS terkini...';

  @override
  void initState() {
    super.initState();
    _detectCurrentLocation();
  }

  Future<void> _detectCurrentLocation() async {
    setState(() {
      _isFetchingLocation = true;
      _gpsStatusText = 'Mencari sinyal GPS terkini...';
    });

    try {
      final pos = await LocationService.getCurrentPosition();
      _userLat = pos.latitude;
      _userLng = pos.longitude;

      // Reverse geocode to get street address via Nominatim OpenStreetMap
      String? addressName;
      try {
        final url = Uri.parse(
            'https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.latitude}&lon=${pos.longitude}&accept-language=id');
        final response = await http.get(url, headers: {'User-Agent': 'CicalengkaGO-Mobile/1.0'}).timeout(const Duration(seconds: 4));
        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          if (data is Map && data['display_name'] != null) {
            addressName = data['display_name'].toString();
          }
        }
      } catch (_) {}

      if (mounted) {
        setState(() {
          _isFetchingLocation = false;
          _gpsStatusText = 'Lokasi GPS Akurat (${_userLat.toStringAsFixed(5)}, ${_userLng.toStringAsFixed(5)})';
          if (addressName != null && addressName.isNotEmpty) {
            _addressController.text = addressName;
          } else if (_addressController.text.isEmpty) {
            _addressController.text = 'Jl. Raya Cicalengka, Kab. Bandung (GPS: ${_userLat.toStringAsFixed(5)}, ${_userLng.toStringAsFixed(5)})';
          }
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _isFetchingLocation = false;
          _gpsStatusText = 'Gagal mendeteksi GPS, menggunakan lokasi default Cicalengka.';
          if (_addressController.text.isEmpty) {
            _addressController.text = 'Jl. Raya Cicalengka No. 88, Kab. Bandung';
          }
        });
      }
    }
  }

  void _handleCheckout() async {
    if (_addressController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Silakan masukkan alamat pengantaran lengkap.'),
          backgroundColor: AppTheme.primaryRed,
        ),
      );
      return;
    }

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
      lat: _userLat,
      lng: _userLng,
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
        elevation: 0.5,
        foregroundColor: const Color(0xFF0F172A),
        title: const Text('Checkout & Pengantaran', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // GPS Location Status Banner
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE2E8F0)),
                boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 8)],
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryRed.withValues(alpha: 0.1),
                      shape: BoxShape.circle,
                    ),
                    child: _isFetchingLocation
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.primaryRed),
                          )
                        : const Icon(Icons.my_location_rounded, color: AppTheme.primaryRed, size: 20),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Lokasi Pengantaran (GPS)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A))),
                        const SizedBox(height: 2),
                        Text(
                          _gpsStatusText,
                          style: const TextStyle(fontSize: 10, color: Color(0xFF64748B)),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    onPressed: _isFetchingLocation ? null : _detectCurrentLocation,
                    icon: const Icon(Icons.refresh_rounded, color: AppTheme.primaryRed, size: 20),
                    tooltip: 'Deteksi ulang GPS',
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            const Text(
              'Alamat Lengkap Pengantaran',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _addressController,
              maxLines: 3,
              style: const TextStyle(fontSize: 12.5),
              decoration: InputDecoration(
                prefixIcon: const Padding(
                  padding: EdgeInsets.only(bottom: 24),
                  child: Icon(Icons.location_on_rounded, color: AppTheme.primaryRed),
                ),
                hintText: 'Alamat lengkap lokasi Anda (bisa disesuaikan)...',
                hintStyle: const TextStyle(fontSize: 11.5),
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppTheme.primaryRed)),
              ),
            ),
            const SizedBox(height: 16),

            const Text(
              'Catatan untuk Kurir / Resto (Opsional)',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _noteController,
              style: const TextStyle(fontSize: 12.5),
              decoration: InputDecoration(
                prefixIcon: const Icon(Icons.note_alt_outlined, color: Color(0xFF64748B)),
                hintText: 'Misal: Pedas sedang, titip di sekuriti / pagar warna hijau',
                hintStyle: const TextStyle(fontSize: 11.5),
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppTheme.primaryRed)),
              ),
            ),
            const SizedBox(height: 20),

            const Text(
              'Metode Pembayaran',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
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
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
                  elevation: 2,
                ),
                onPressed: (_isSubmitting || _isFetchingLocation) ? null : _handleCheckout,
                child: _isSubmitting
                    ? const SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text('BUAT PESANAN SEKARANG', style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Colors.white)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
