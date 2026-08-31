import 'dart:convert';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:http/http.dart' as http;
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/app_alert.dart';
import '../../../core/widgets/require_auth_widget.dart';
import '../../../core/services/location_service.dart';
import '../../../core/widgets/location_picker_modal.dart';
import '../controllers/customer_controller.dart';
import 'order_tracking_screen.dart';
import 'in_app_payment_screen.dart';
import 'vouchers_screen.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _addressController = TextEditingController();
  final _noteController = TextEditingController();
  final _voucherController = TextEditingController();

  String _paymentMethod = 'wallet';
  String _deliveryType = 'driver'; // 'driver' or 'merchant'
  bool _isSubmitting = false;
  bool _isFetchingLocation = true;
  bool _isValidatingVoucher = false;

  String? _appliedVoucherCode;
  String? _appliedVoucherTitle;
  double _voucherDiscount = 0.0;

  double _userLat = -6.9835;
  double _userLng = 107.8335;
  String _gpsStatusText = 'Mendeteksi lokasi GPS terkini...';

  double _calculateDistanceKm(double sLat, double sLng, double uLat, double uLng) {
    if (sLat == 0 || sLng == 0 || uLat == 0 || uLng == 0) return 1.5;
    const double p = 0.017453292519943295; // Math.PI / 180
    final double a = 0.5 -
        math.cos((uLat - sLat) * p) / 2 +
        math.cos(sLat * p) * math.cos(uLat * p) * (1 - math.cos((uLng - sLng) * p)) / 2;
    final double dist = 12742 * math.asin(math.sqrt(a)); // 2 * R; R = 6371 km
    return double.parse(dist.toStringAsFixed(2));
  }

  double _calcZoneDeliveryFee(double distanceKm, {double minFee = 5000, double perKm = 2500}) {
    if (distanceKm <= 2.0) return minFee;
    return (minFee + (distanceKm - 2.0) * perKm).roundToDouble();
  }

  @override
  void initState() {
    super.initState();
    _detectCurrentLocation();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        context.read<CustomerController>().fetchWallet();
        context.read<CustomerController>().fetchZoneConfig();
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
        final customerCtrl = context.read<CustomerController>();
        final cart = customerCtrl.cart;
        final stores = (cart?['stores'] as List<dynamic>?) ?? [];
        if (stores.isNotEmpty) {
          final double sLat = double.tryParse(stores[0]['latitude']?.toString() ?? '0') ?? 0.0;
          final double sLng = double.tryParse(stores[0]['longitude']?.toString() ?? '0') ?? 0.0;
          if (sLat != 0 && sLng != 0) {
            final dist = _calculateDistanceKm(sLat, sLng, _userLat, _userLng);
            if (dist <= 0.30) {
              _deliveryType = 'merchant';
            }
          }
        }

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

    // Calculate real distance
    double realDistKm = 1.5;
    if (stores.isNotEmpty) {
      final double sLat = double.tryParse(stores[0]['latitude']?.toString() ?? '-6.9835') ?? -6.9835;
      final double sLng = double.tryParse(stores[0]['longitude']?.toString() ?? '107.8335') ?? 107.8335;
      if (sLat != 0 && sLng != 0 && _userLat != 0 && _userLng != 0) {
        realDistKm = _calculateDistanceKm(sLat, sLng, _userLat, _userLng);
      }
    }

    final bool isCloseProximity = (realDistKm <= 0.30);
    final String chosenDeliveryType = (isCloseProximity && _deliveryType == 'merchant') ? 'merchant' : 'driver';

    // Compute totals for checkout
    final double subtotal = customerCtrl.cartSubtotal;
    final double dynamicDeliveryFee = _calcZoneDeliveryFee(
      realDistKm,
      minFee: customerCtrl.zoneMinDeliveryCharge,
      perKm: customerCtrl.zonePerKmDeliveryCharge,
    );
    final double deliveryFee = (chosenDeliveryType == 'merchant') ? 0.0 : dynamicDeliveryFee;
    final double grandTotal = (subtotal + deliveryFee + 1000.0 - _voucherDiscount).clamp(0.0, double.infinity);

    // Check CicalengkaPay wallet balance if wallet payment selected
    if (_paymentMethod == 'wallet') {
      final walletMap = customerCtrl.wallet;
      final double walletBalance = double.tryParse(walletMap?['balance']?.toString() ?? '0') ?? 0.0;

      if (walletBalance < grandTotal) {
        AppAlert.showError(
          context,
          title: 'Saldo CicalengkaPay Kurang',
          message: 'Saldo Anda (${CurrencyFormatter.formatRupiah(walletBalance)}) kurang dari total tagihan (${CurrencyFormatter.formatRupiah(grandTotal)}). Gunakan QRIS / COD.',
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
      couponCode: _appliedVoucherCode,
      deliveryType: chosenDeliveryType,
      distanceKm: realDistKm,
    );

    setState(() {
      _isSubmitting = false;
    });

    if (mounted) {
      if (res['success'] == true) {
        final orderCode = res['data']?['order_code'] ?? res['data']?['order_id']?.toString() ?? res['order_code'] ?? '';
        
        final snapToken = res['data']?['snap_token'];
        String? redirectUrl = res['data']?['redirect_url'] ?? res['redirect_url'];
        if ((redirectUrl == null || redirectUrl.isEmpty) && snapToken != null) {
          redirectUrl = 'https://app.sandbox.midtrans.com/snap/v2/vtweb/$snapToken';
        }

        // If Midtrans is chosen and Snap URL is available, open InAppPaymentScreen
        if (_paymentMethod == 'midtrans' && redirectUrl != null && redirectUrl.isNotEmpty) {
          await Navigator.push<bool>(
            context,
            MaterialPageRoute(
              builder: (_) => InAppPaymentScreen(
                paymentUrl: redirectUrl!,
                orderId: orderCode,
                amount: grandTotal,
                title: 'Pembayaran Pesanan Midtrans',
                onPaymentComplete: () {
                  context.read<CustomerController>().fetchOrders();
                },
              ),
            ),
          );
        } else {
          AppAlert.showSuccess(
            context,
            title: 'Pesanan Berhasil Dibuat! 🎉',
            message: chosenDeliveryType == 'merchant'
                ? 'Pesanan diteruskan ke Mitra Toko untuk dimasak & diantar langsung.'
                : 'Pesanan diteruskan ke Mitra Toko. Driver akan segera ditugaskan setelah pesanan siap.',
          );
        }

        if (mounted) {
          Navigator.pushAndRemoveUntil(
            context,
            MaterialPageRoute(
              builder: (_) => OrderTrackingScreen(orderCode: orderCode),
            ),
            (route) => route.isFirst,
          );
        }
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

    // Calculate dynamic distance from store to GPS location
    double calculatedDistKm = 1.5;
    if (stores.isNotEmpty) {
      final double sLat = double.tryParse(stores[0]['latitude']?.toString() ?? '-6.9835') ?? -6.9835;
      final double sLng = double.tryParse(stores[0]['longitude']?.toString() ?? '107.8335') ?? 107.8335;
      if (sLat != 0 && sLng != 0 && _userLat != 0 && _userLng != 0) {
        calculatedDistKm = _calculateDistanceKm(sLat, sLng, _userLat, _userLng);
      }
    }

    final bool isCloseProximity = (calculatedDistKm <= 0.30); // Jarak < 300 meter
    final double zoneMinFee = customerCtrl.zoneMinDeliveryCharge;
    final double zonePerKm = customerCtrl.zonePerKmDeliveryCharge;
    final String zoneName = customerCtrl.zoneName;
    final double dynamicDeliveryFee = _calcZoneDeliveryFee(calculatedDistKm, minFee: zoneMinFee, perKm: zonePerKm);

    double subtotal = customerCtrl.cartSubtotal;
    double deliveryFee = (isCloseProximity && _deliveryType == 'merchant') ? 0.0 : dynamicDeliveryFee;

    const double serviceFee = 1000.0;
    final double grandTotal = (subtotal + deliveryFee + serviceFee - _voucherDiscount).clamp(0.0, double.infinity);

    final walletMap = customerCtrl.wallet;
    final double walletBalance = double.tryParse(walletMap?['balance']?.toString() ?? '0') ?? 0.0;
    final bool isWalletInsufficient = walletBalance < grandTotal;

    return RequireAuthWidget(
      title: 'Checkout Pesanan',
      subtitle: 'Masuk ke akun CicalengkaGO Anda untuk melanjutkan proses pengantaran dan pembayaran.',
      icon: Icons.local_shipping_outlined,
      child: Scaffold(
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
            const SizedBox(height: 14),

            // Pilihan Pengantaran jika radius < 300 meter
            if (isCloseProximity)
              _buildDeliveryTypeSelectorCard(calculatedDistKm, dynamicDeliveryFee),

            // Skema Tarif Zona Cicalengka Card (Transparan)
            _buildZoneTariffCard(calculatedDistKm, zoneMinFee, zonePerKm, deliveryFee, zoneName),

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

            // 2. Voucher Promo Card
            _buildVoucherCard(subtotal),

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
                  // 1. CicalengkaPay (Saldo Dompet)
                  RadioListTile<String>(
                    value: 'wallet',
                    groupValue: _paymentMethod,
                    title: const Text('CicalengkaPay (Saldo Digital)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    subtitle: Text(
                      isWalletInsufficient
                          ? 'Saldo: ${CurrencyFormatter.formatRupiah(walletBalance)} (Saldo Kurang)'
                          : 'Saldo: ${CurrencyFormatter.formatRupiah(walletBalance)} • Otomatis & Bebas Biaya',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: isWalletInsufficient ? AppTheme.primaryRed : const Color(0xFF16A34A),
                      ),
                    ),
                    secondary: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: (isWalletInsufficient ? Colors.grey : AppTheme.primaryRed).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(
                        Icons.account_balance_wallet_rounded,
                        color: isWalletInsufficient ? Colors.grey : AppTheme.primaryRed,
                        size: 20,
                      ),
                    ),
                    onChanged: (val) {
                      if (isWalletInsufficient) {
                        AppAlert.showError(
                          context,
                          title: 'Saldo CicalengkaPay Kurang',
                          message: 'Saldo Anda (${CurrencyFormatter.formatRupiah(walletBalance)}) kurang dari total tagihan (${CurrencyFormatter.formatRupiah(grandTotal)}). Silakan gunakan QRIS/Transfer Bank atau Bayar Tunai (COD).',
                        );
                        return;
                      }
                      setState(() => _paymentMethod = val!);
                    },
                  ),
                  const Divider(height: 1),

                  // 2. Midtrans Payment Gateway (QRIS, VA Bank, E-Wallet)
                  RadioListTile<String>(
                    value: 'midtrans',
                    groupValue: _paymentMethod,
                    title: const Text('Transfer Bank / QRIS (Midtrans)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    subtitle: const Text(
                      'QRIS, BCA, BRI, Mandiri, BNI, ShopeePay, Indomaret',
                      style: TextStyle(fontSize: 11, color: Color(0xFF2563EB), fontWeight: FontWeight.w600),
                    ),
                    secondary: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: const Color(0xFF2563EB).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(
                        Icons.qr_code_2_rounded,
                        color: Color(0xFF2563EB),
                        size: 20,
                      ),
                    ),
                    onChanged: (val) => setState(() => _paymentMethod = val!),
                  ),
                  const Divider(height: 1),

                  // 3. Bayar Tunai / COD
                  RadioListTile<String>(
                    value: 'cod',
                    groupValue: _paymentMethod,
                    title: const Text('Bayar Tunai / COD', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    subtitle: const Text('Bayar langsung ke Kurir saat pesanan sampai', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                    secondary: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: const Color(0xFF059669).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(
                        Icons.payments_rounded,
                        color: Color(0xFF059669),
                        size: 20,
                      ),
                    ),
                    onChanged: (val) => setState(() => _paymentMethod = val!),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            // 3. Rincian Pembayaran Card
            _buildPaymentDetailCard(subtotal, deliveryFee, serviceFee, grandTotal),

            const SizedBox(height: 40),
          ],
        ),
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
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
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFEF4444),
                  foregroundColor: Colors.white,
                  elevation: 2,
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                icon: _isSubmitting
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      )
                    : const Icon(Icons.check_circle_rounded, size: 18),
                label: Text(
                  _isSubmitting ? 'Memproses...' : 'Pesan Sekarang',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                ),
                onPressed: (_isSubmitting || _isFetchingLocation) ? null : _handleCheckout,
              ),
            ],
          ),
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

  String _getFoodImage(Map<String, dynamic> item) {
    final rawImg = item['product_image']?.toString() ??
        item['image']?.toString() ??
        item['image_url']?.toString() ??
        item['photo']?.toString() ??
        item['product_photo']?.toString() ??
        item['cover_photo']?.toString() ??
        item['thumbnail']?.toString() ??
        (item['product'] is Map ? (item['product']['image'] ?? item['product']['product_image'])?.toString() : null);
    if (rawImg != null && rawImg.isNotEmpty && !rawImg.contains('null')) {
      final formatted = ApiConstants.formatImageUrl(rawImg);
      if (formatted.isNotEmpty) return formatted;
    }
    return '';
  }

  Widget _buildCheckoutItemTile(Map<String, dynamic> item) {
    final name = (item['product_name'] ?? item['name'] ?? 'Menu Kuliner').toString();
    final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;
    final price = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
    final notes = item['item_notes'] ?? item['notes'];
    final imageUrl = _getFoodImage(item);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          // Product Image Thumbnail
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: SizedBox(
              width: 48,
              height: 48,
              child: (imageUrl.isNotEmpty)
                  ? CachedNetworkImage(
                      imageUrl: imageUrl,
                      fit: BoxFit.cover,
                      placeholder: (context, url) => Container(
                        color: const Color(0xFFF1F5F9),
                        child: const Center(
                          child: SizedBox(
                            width: 14,
                            height: 14,
                            child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.inkBlack),
                          ),
                        ),
                      ),
                      errorWidget: (context, url, error) => Container(
                        color: const Color(0xFFF1F5F9),
                        child: const Icon(Icons.fastfood_rounded, color: Color(0xFF94A3B8), size: 20),
                      ),
                    )
                  : Container(
                      color: const Color(0xFFF1F5F9),
                      child: const Icon(Icons.fastfood_rounded, color: Color(0xFF94A3B8), size: 20),
                    ),
            ),
          ),
          const SizedBox(width: 12),
          // Quantity Badge
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(6),
            ),
            child: Text(
              '${qty}x',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: AppTheme.inkBlack),
            ),
          ),
          const SizedBox(width: 10),
          // Details
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: AppTheme.inkBlack),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                if (notes != null && notes.toString().trim().isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(
                    'Catatan: ${notes.toString().trim()}',
                    style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontStyle: FontStyle.italic),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
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
          const SizedBox(width: 8),
          Text(
            CurrencyFormatter.formatRupiah(price * qty),
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppTheme.inkBlack),
          ),
        ],
      ),
    );
  }

  Future<void> _handleApplyVoucher(double subtotal) async {
    final code = _voucherController.text.trim();
    if (code.isEmpty) {
      AppAlert.showWarning(context, title: 'Kode Voucher Kosong', message: 'Masukkan kode voucher / promo terlebih dahulu.');
      return;
    }

    setState(() {
      _isValidatingVoucher = true;
    });

    final customerCtrl = context.read<CustomerController>();
    final res = await customerCtrl.validateCoupon(code, subtotal);

    setState(() {
      _isValidatingVoucher = false;
    });

    if (mounted) {
      if (res['success'] == true && res['data'] != null) {
        final data = res['data'];
        final disc = double.tryParse(data['calculated_discount']?.toString() ?? '0') ?? 0.0;
        setState(() {
          _appliedVoucherCode = data['code']?.toString() ?? code;
          _appliedVoucherTitle = data['title']?.toString() ?? 'Voucher Promo';
          _voucherDiscount = disc;
        });
        AppAlert.showSuccess(
          context,
          title: 'Voucher Berhasil Diterapkan! 🎉',
          message: '${data['title'] ?? 'Diskon'}: Potongan ${CurrencyFormatter.formatRupiah(disc)}',
        );
      } else {
        AppAlert.showError(
          context,
          title: 'Gagal Menggunakan Voucher',
          message: res['message'] ?? 'Kode voucher tidak valid atau minimal pembelian belum terpenuhi.',
        );
      }
    }
  }

  void _removeAppliedVoucher() {
    setState(() {
      _appliedVoucherCode = null;
      _appliedVoucherTitle = null;
      _voucherDiscount = 0.0;
      _voucherController.clear();
    });
  }

  Widget _buildVoucherCard(double subtotal) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: _appliedVoucherCode != null ? const Color(0xFF16A34A) : const Color(0xFFE2E8F0),
          width: _appliedVoucherCode != null ? 1.5 : 1.0,
        ),
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
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: _appliedVoucherCode != null ? const Color(0xFFDCFCE7) : const Color(0xFFFEF3C7),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  Icons.confirmation_number_rounded,
                  color: _appliedVoucherCode != null ? const Color(0xFF16A34A) : const Color(0xFFD97706),
                  size: 18,
                ),
              ),
              const SizedBox(width: 10),
              const Expanded(
                child: Text(
                  'Voucher & Kode Promo',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                ),
              ),
              InkWell(
                onTap: () async {
                  final selectedCode = await Navigator.push<String>(
                    context,
                    MaterialPageRoute(
                      builder: (_) => VouchersScreen(
                        isSelectMode: true,
                        orderSubtotal: subtotal,
                      ),
                    ),
                  );
                  if (selectedCode != null && selectedCode.isNotEmpty) {
                    _voucherController.text = selectedCode;
                    _handleApplyVoucher(subtotal);
                  }
                },
                child: const Row(
                  children: [
                    Text(
                      'Lihat Promo',
                      style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                    ),
                    Icon(Icons.chevron_right_rounded, size: 16, color: AppTheme.primaryRed),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),

          if (_appliedVoucherCode != null) ...[
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF0FDF4),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFBBF7D0)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.check_circle_rounded, color: Color(0xFF16A34A), size: 22),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Text(
                              _appliedVoucherCode!,
                              style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 13, color: Color(0xFF15803D), letterSpacing: 0.5),
                            ),
                            const SizedBox(width: 6),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: const Color(0xFF16A34A),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                '-${CurrencyFormatter.formatRupiah(_voucherDiscount)}',
                                style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ],
                        ),
                        if (_appliedVoucherTitle != null) ...[
                          const SizedBox(height: 2),
                          Text(
                            _appliedVoucherTitle!,
                            style: const TextStyle(fontSize: 11, color: Color(0xFF166534)),
                          ),
                        ],
                      ],
                    ),
                  ),
                  TextButton.icon(
                    onPressed: _removeAppliedVoucher,
                    icon: const Icon(Icons.close_rounded, size: 14, color: AppTheme.primaryRed),
                    label: const Text('Hapus', style: TextStyle(fontSize: 11, color: AppTheme.primaryRed, fontWeight: FontWeight.bold)),
                    style: TextButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      minimumSize: Size.zero,
                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    ),
                  ),
                ],
              ),
            ),
          ] else ...[
            Row(
              children: [
                Expanded(
                  child: SizedBox(
                    height: 44,
                    child: TextField(
                      controller: _voucherController,
                      style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600),
                      textCapitalization: TextCapitalization.characters,
                      decoration: InputDecoration(
                        hintText: 'Masukkan kode voucher (cth: CICAHEBAT)',
                        hintStyle: const TextStyle(fontSize: 11, fontWeight: FontWeight.normal, color: Color(0xFF94A3B8)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppTheme.primaryRed)),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                SizedBox(
                  height: 44,
                  child: ElevatedButton(
                    onPressed: _isValidatingVoucher ? null : () => _handleApplyVoucher(subtotal),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primaryRed,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                    ),
                    child: _isValidatingVoucher
                        ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Text('Terapkan', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
            ),
          ],
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

          if (_voucherDiscount > 0) ...[
            const SizedBox(height: 10),
            _buildPaymentRow(
              'Potongan Voucher (${_appliedVoucherCode ?? 'Promo'})',
              '-${CurrencyFormatter.formatRupiah(_voucherDiscount)}',
              isGreen: true,
            ),
          ],
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

  Widget _buildPaymentRow(String label, String value, {String? subtitle, bool isBold = false, bool isGreen = false}) {
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
                fontWeight: isBold || isGreen ? FontWeight.bold : FontWeight.w500,
                color: isGreen ? const Color(0xFF15803D) : const Color(0xFF475569),
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
            fontWeight: isBold || isGreen ? FontWeight.bold : FontWeight.w600,
            color: isGreen ? const Color(0xFF16A34A) : AppTheme.inkBlack,
          ),
        ),
      ],
    );
  }

  Widget _buildDeliveryTypeSelectorCard(double distKm, double dynamicDeliveryFee) {
    final int meters = (distKm * 1000).round();
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF0FDF4),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF86EFAC), width: 1.5),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF16A34A).withValues(alpha: 0.06),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: const BoxDecoration(
                  color: Color(0xFF16A34A),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.near_me_rounded, color: Colors.white, size: 14),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Pilihan Pengantaran Radius Dekat 🎯',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF14532D)),
                    ),
                    Text(
                      'Jarak ke resto hanya $meters meter (kurang dari 300m).',
                      style: const TextStyle(fontSize: 10.5, color: Color(0xFF166534)),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Option 1: Diantar Merchant Langsung (Gratis Rp 0)
          InkWell(
            onTap: () {
              setState(() {
                _deliveryType = 'merchant';
              });
            },
            borderRadius: BorderRadius.circular(12),
            child: Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: _deliveryType == 'merchant' ? Colors.white : Colors.transparent,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: _deliveryType == 'merchant' ? const Color(0xFF16A34A) : const Color(0xFFCBD5E1),
                  width: _deliveryType == 'merchant' ? 2 : 1,
                ),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Radio<String>(
                    value: 'merchant',
                    groupValue: _deliveryType,
                    activeColor: const Color(0xFF16A34A),
                    materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    visualDensity: VisualDensity.compact,
                    onChanged: (val) {
                      if (val != null) setState(() => _deliveryType = val);
                    },
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Flexible(
                              child: Text(
                                'Diantar Merchant',
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF0F172A)),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            const SizedBox(width: 4),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                              decoration: BoxDecoration(
                                color: const Color(0xFFDCFCE7),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: const Text(
                                'GRATIS',
                                style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: Color(0xFF15803D)),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 2),
                        const Text(
                          'Staff merchant akan mengantar langsung ke lokasi Anda.',
                          style: TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 6),
                  const Text(
                    'Rp 0',
                    style: TextStyle(fontWeight: FontWeight.w900, fontSize: 13, color: Color(0xFF16A34A)),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 8),
          // Option 2: Diantar Driver CicalengkaGO
          InkWell(
            onTap: () {
              setState(() {
                _deliveryType = 'driver';
              });
            },
            borderRadius: BorderRadius.circular(12),
            child: Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: _deliveryType == 'driver' ? Colors.white : Colors.transparent,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: _deliveryType == 'driver' ? AppTheme.primaryRed : const Color(0xFFCBD5E1),
                  width: _deliveryType == 'driver' ? 2 : 1,
                ),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Radio<String>(
                    value: 'driver',
                    groupValue: _deliveryType,
                    activeColor: AppTheme.primaryRed,
                    materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    visualDensity: VisualDensity.compact,
                    onChanged: (val) {
                      if (val != null) setState(() => _deliveryType = val);
                    },
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Diantar Driver CicalengkaGO',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF0F172A)),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 2),
                        const Text(
                          'Dilelang ke mitra pengemudi resmi CicalengkaGO.',
                          style: TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    CurrencyFormatter.formatRupiah(dynamicDeliveryFee),
                    style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 12.5, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildZoneTariffCard(double distKm, double minFee, double perKm, double totalOngkir, String zoneName) {
    final bool isBase = distKm <= 2.0;
    final double extraKm = isBase ? 0.0 : (distKm - 2.0);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        gradient: const LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Colors.white, Color(0xFFF8FAFC)],
        ),
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
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Expanded(
                child: Row(
                  children: [
                    Icon(Icons.info_outline_rounded, color: Color(0xFF2563EB), size: 16),
                    SizedBox(width: 6),
                    Flexible(
                      child: Text(
                        'Skema Tarif Zona',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A)),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 6),
              Flexible(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEFF6FF),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFBFDBFE)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.shield_rounded, color: Color(0xFF2563EB), size: 10),
                      const SizedBox(width: 3),
                      Flexible(
                        child: Text(
                          zoneName,
                          style: const TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: Color(0xFF1D4ED8)),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Tarif Dasar (≤ 2.0 Km)', style: TextStyle(fontSize: 9.5, color: Color(0xFF64748B))),
                      const SizedBox(height: 2),
                      Text(
                        CurrencyFormatter.formatRupiah(minFee),
                        style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w900, color: Color(0xFF16A34A)),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Tarif per Km (> 2 Km)', style: TextStyle(fontSize: 9.5, color: Color(0xFF64748B))),
                      const SizedBox(height: 2),
                      Text(
                        '${CurrencyFormatter.formatRupiah(perKm)}/Km',
                        style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w900, color: Color(0xFF2563EB)),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(Icons.alt_route_rounded, color: AppTheme.primaryRed, size: 14),
                    const SizedBox(width: 5),
                    const Text('Jarak Rute: ', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                    Text(
                      '${distKm.toStringAsFixed(1)} Km',
                      style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: isBase ? const Color(0xFFDCFCE7) : const Color(0xFFEFF6FF),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: isBase ? const Color(0xFF86EFAC) : const Color(0xFF93C5FD)),
                  ),
                  child: Text(
                    isBase
                        ? 'Tarif Dasar ${CurrencyFormatter.formatRupiah(minFee)}'
                        : 'Dasar + (${extraKm.toStringAsFixed(1)} km × ${CurrencyFormatter.formatRupiah(perKm)}) = ${CurrencyFormatter.formatRupiah(totalOngkir)}',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                      color: isBase ? const Color(0xFF15803D) : const Color(0xFF1D4ED8),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
