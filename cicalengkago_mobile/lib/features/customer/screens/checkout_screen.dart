import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:http/http.dart' as http;
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/uber_pill_button.dart';
import '../../../core/widgets/app_alert.dart';
import '../../../core/services/location_service.dart';
import '../../../core/widgets/location_picker_modal.dart';
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
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        context.read<CustomerController>().fetchWallet();
      }
    });
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
      AppAlert.showWarning(
        context,
        title: 'Alamat Belum Lengkap',
        message: 'Silakan masukkan alamat pengantaran lengkap Anda.',
      );
      return;
    }

    final customerCtrl = context.read<CustomerController>();
    final cart = customerCtrl.cart;
    final stores = (cart?['stores'] as List<dynamic>?) ?? [];
    final storeId = int.tryParse(stores.isNotEmpty ? stores[0]['store_id']?.toString() ?? '1' : '1') ?? 1;

    // Check CicalengkaPay wallet balance if wallet payment selected
    if (_paymentMethod == 'wallet') {
      double subtotal = customerCtrl.cartSubtotal;
      double deliveryFee = 0.0;
      if (cart?['grand_delivery'] != null) {
        deliveryFee = double.tryParse(cart!['grand_delivery'].toString()) ?? 5000.0;
      } else if (stores.isNotEmpty) {
        for (var s in stores) {
          deliveryFee += double.tryParse(s['delivery_fee']?.toString() ?? '5000') ?? 5000.0;
        }
      } else {
        deliveryFee = 5000.0;
      }
      if (deliveryFee <= 0) deliveryFee = 5000.0;
      final double grandTotal = subtotal + deliveryFee + 1000.0;

      final walletMap = customerCtrl.wallet;
      final double walletBalance = double.tryParse(walletMap?['balance']?.toString() ?? '0') ?? 0.0;

      if (walletBalance < grandTotal) {
        AppAlert.showError(
          context,
          title: 'Saldo CicalengkaPay Kurang',
          message: 'Saldo Anda (${CurrencyFormatter.formatRupiah(walletBalance)}) kurang dari total tagihan (${CurrencyFormatter.formatRupiah(grandTotal)}). Gunakan COD / Top Up.',
        );
        return;
      }
    }

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

        AppAlert.showSuccess(
          context,
          title: 'Pesanan Berhasil Dibuat! 🎉',
          message: 'Mencari driver terdekat untuk mengantar pesanan Anda.',
        );

        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(
            builder: (_) => OrderTrackingScreen(orderCode: orderCode),
          ),
          (route) => route.isFirst,
        );
      } else {
        AppAlert.showError(
          context,
          title: 'Gagal Membuat Pesanan',
          message: res['message'] ?? 'Silakan periksa kembali rincian pesanan Anda.',
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final customerCtrl = context.watch<CustomerController>();
    final cart = customerCtrl.cart;
    final stores = (cart?['stores'] as List<dynamic>?) ?? [];

    double subtotal = customerCtrl.cartSubtotal;
    double deliveryFee = 0.0;
    if (cart?['grand_delivery'] != null) {
      deliveryFee = double.tryParse(cart!['grand_delivery'].toString()) ?? 5000.0;
    } else if (stores.isNotEmpty) {
      for (var s in stores) {
        deliveryFee += double.tryParse(s['delivery_fee']?.toString() ?? '5000') ?? 5000.0;
      }
    } else {
      deliveryFee = 5000.0;
    }
    if (deliveryFee <= 0) deliveryFee = 5000.0;

    const double serviceFee = 1000.0;
    final double grandTotal = subtotal + deliveryFee + serviceFee;

    final walletMap = customerCtrl.wallet;
    final double walletBalance = double.tryParse(walletMap?['balance']?.toString() ?? '0') ?? 0.0;
    final bool isWalletInsufficient = walletBalance < grandTotal;

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

            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Alamat Lengkap Pengantaran',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                ),
                InkWell(
                  onTap: () async {
                    final result = await LocationPickerModal.show(
                      context,
                      initialLat: _userLat,
                      initialLng: _userLng,
                    );
                    if (result != null && mounted) {
                      setState(() {
                        _userLat = (result['lat'] as num).toDouble();
                        _userLng = (result['lng'] as num).toDouble();
                        _addressController.text = result['address']?.toString() ?? '';
                        _gpsStatusText = 'Titik Peta Terpilih (${_userLat.toStringAsFixed(5)}, ${_userLng.toStringAsFixed(5)})';
                      });
                    }
                  },
                  borderRadius: BorderRadius.circular(8),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEE2E2),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: AppTheme.primaryRed.withValues(alpha: 0.3)),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: const [
                        Icon(Icons.map_rounded, color: AppTheme.primaryRed, size: 14),
                        SizedBox(width: 4),
                        Text(
                          'Pilih dari Peta',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.primaryRed,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
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

            // 1. Ringkasan Menu Pesanan Card
            _buildOrderSummaryCard(customerCtrl),

            const SizedBox(height: 16),

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
                    // ignore: deprecated_member_use
                    groupValue: _paymentMethod,
                    title: const Text('CicalengkaPay (Saldo Digital)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    subtitle: Text(
                      isWalletInsufficient
                          ? 'Saldo: ${CurrencyFormatter.formatRupiah(walletBalance)} (Saldo Kurang)'
                          : 'Saldo: ${CurrencyFormatter.formatRupiah(walletBalance)} • Bebas Biaya',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: isWalletInsufficient ? AppTheme.primaryRed : const Color(0xFF16A34A),
                      ),
                    ),
                    secondary: Icon(
                      Icons.account_balance_wallet_rounded,
                      color: isWalletInsufficient ? Colors.grey : AppTheme.primaryRed,
                    ),
                    // ignore: deprecated_member_use
                    onChanged: (val) {
                      if (isWalletInsufficient) {
                        AppAlert.showError(
                          context,
                          title: 'Saldo CicalengkaPay Kurang',
                          message: 'Saldo Anda (${CurrencyFormatter.formatRupiah(walletBalance)}) kurang dari total tagihan (${CurrencyFormatter.formatRupiah(grandTotal)}). Silakan gunakan Bayar Tunai (COD).',
                        );
                        return;
                      }
                      setState(() => _paymentMethod = val!);
                    },
                  ),
                  const Divider(height: 1),
                  RadioListTile<String>(
                    value: 'cod',
                    // ignore: deprecated_member_use
                    groupValue: _paymentMethod,
                    title: const Text('Bayar Tunai / COD', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    subtitle: const Text('Bayar langsung ke Kurir saat sampai', style: TextStyle(fontSize: 11)),
                    secondary: const Icon(Icons.payments_rounded, color: Color(0xFF059669)),
                    // ignore: deprecated_member_use
                    onChanged: (val) => setState(() => _paymentMethod = val!),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            // 2. Rincian Pembayaran Card
            _buildPaymentDetailCard(subtotal, deliveryFee, serviceFee, grandTotal),

            const SizedBox(height: 40),
          ],
        ),
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 16,
              offset: const Offset(0, -4),
            ),
          ],
        ),
        child: SafeArea(
          child: Row(
            children: [
              Expanded(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Total Pembayaran',
                      style: TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      CurrencyFormatter.formatRupiah(grandTotal),
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w900,
                        color: AppTheme.inkBlack,
                      ),
                    ),
                  ],
                ),
              ),
              UberPillButton(
                label: _isSubmitting ? 'Memproses...' : 'Buat Pesanan',
                icon: Icons.check_circle_rounded,
                onPressed: (_isSubmitting || _isFetchingLocation) ? null : _handleCheckout,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildOrderSummaryCard(CustomerController customerCtrl) {
    final cart = customerCtrl.cart;
    final stores = (cart?['stores'] as List<dynamic>?) ?? [];
    final rawItems = customerCtrl.cartItems;

    return Container(
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
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                Icon(Icons.receipt_long_rounded, color: AppTheme.primaryRed, size: 20),
                SizedBox(width: 8),
                Text(
                  'Ringkasan Pesanan',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),

          if (stores.isNotEmpty) ...[
            ...stores.map((s) {
              final items = (s['items'] as List<dynamic>?) ?? [];
              final rawLogo = s['logo'] ?? s['store_logo'];
              final storeLogoUrl = (rawLogo != null && rawLogo.toString().isNotEmpty)
                  ? ApiConstants.formatImageUrl(rawLogo.toString())
                  : null;
              final storeName = s['store_name'] ?? s['name'] ?? 'Mitra Resto Cicalengka';

              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    color: const Color(0xFFF8FAFC),
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    child: Row(
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: Container(
                            width: 28,
                            height: 28,
                            color: Colors.white,
                            child: storeLogoUrl != null
                                ? CachedNetworkImage(
                                    imageUrl: storeLogoUrl,
                                    fit: BoxFit.cover,
                                    errorWidget: (context, url, error) => const Icon(Icons.storefront_rounded, size: 16, color: AppTheme.inkBlack),
                                  )
                                : const Icon(Icons.storefront_rounded, size: 16, color: AppTheme.inkBlack),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            storeName,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppTheme.inkBlack),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),
                  ...items.map((item) => _buildCheckoutItemTile(item)),
                ],
              );
            }),
          ] else ...[
            if (rawItems.isNotEmpty)
              ...rawItems.map((item) => _buildCheckoutItemTile(item)),
          ],
        ],
      ),
    );
  }

  Widget _buildCheckoutItemTile(Map<String, dynamic> item) {
    final name = (item['product_name'] ?? item['name'] ?? 'Menu Kuliner').toString();
    final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;
    final price = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
    final notes = item['item_notes'] ?? item['notes'];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              '${qty}x',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppTheme.inkBlack),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: AppTheme.inkBlack),
                ),
                if (notes != null && notes.toString().trim().isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(
                    'Catatan: ${notes.toString().trim()}',
                    style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontStyle: FontStyle.italic),
                  ),
                ],
                const SizedBox(height: 2),
                Text(
                  CurrencyFormatter.formatRupiah(price),
                  style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                ),
              ],
            ),
          ),
          Text(
            CurrencyFormatter.formatRupiah(price * qty),
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppTheme.inkBlack),
          ),
        ],
      ),
    );
  }

  Widget _buildPaymentDetailCard(double subtotal, double deliveryFee, double serviceFee, double grandTotal) {
    return Container(
      padding: const EdgeInsets.all(16),
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
          const Row(
            children: [
              Icon(Icons.account_balance_wallet_rounded, color: AppTheme.primaryRed, size: 20),
              SizedBox(width: 8),
              Text(
                'Rincian Pembayaran',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
              ),
            ],
          ),
          const SizedBox(height: 14),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          const SizedBox(height: 12),

          // Subtotal produk
          _buildPaymentRow('Subtotal Pesanan', CurrencyFormatter.formatRupiah(subtotal)),
          const SizedBox(height: 10),

          // Ongkir
          _buildPaymentRow(
            'Ongkos Kirim (Pengantaran)',
            CurrencyFormatter.formatRupiah(deliveryFee),
            subtitle: 'Jarak GPS Terdeteksi',
          ),
          const SizedBox(height: 10),

          // Biaya Layanan
          _buildPaymentRow(
            'Biaya Layanan & Sistem',
            CurrencyFormatter.formatRupiah(serviceFee),
          ),
          const SizedBox(height: 14),

          const Divider(height: 1, color: Color(0xFFE2E8F0)),
          const SizedBox(height: 14),

          // Grand Total
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Total Pembayaran',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppTheme.inkBlack),
                  ),
                  SizedBox(height: 2),
                  Text(
                    'Termasuk seluruh pajak & biaya',
                    style: TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: AppTheme.primaryRed.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: AppTheme.primaryRed.withValues(alpha: 0.2)),
                ),
                child: Text(
                  CurrencyFormatter.formatRupiah(grandTotal),
                  style: const TextStyle(
                    fontWeight: FontWeight.w900,
                    fontSize: 16,
                    color: AppTheme.primaryRed,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildPaymentRow(String label, String value, {String? subtitle, bool isBold = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label,
              style: TextStyle(
                fontSize: 12.5,
                fontWeight: isBold ? FontWeight.bold : FontWeight.w500,
                color: const Color(0xFF475569),
              ),
            ),
            if (subtitle != null) ...[
              const SizedBox(height: 2),
              Text(
                subtitle,
                style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
              ),
            ],
          ],
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: 13,
            fontWeight: isBold ? FontWeight.bold : FontWeight.w600,
            color: AppTheme.inkBlack,
          ),
        ),
      ],
    );
  }
}
