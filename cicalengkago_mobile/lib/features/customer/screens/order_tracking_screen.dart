import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/services/global_call_service.dart';
import '../../common/screens/in_app_chat_modal.dart';
import '../../auth/controllers/auth_controller.dart';
import 'customer_wallet_screen.dart';

class OrderTrackingScreen extends StatefulWidget {
  final String orderCode;
  const OrderTrackingScreen({super.key, required this.orderCode});

  @override
  State<OrderTrackingScreen> createState() => _OrderTrackingScreenState();
}

class _OrderTrackingScreenState extends State<OrderTrackingScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _orderData;
  Map<String, dynamic>? _liveData;
  String? _snapUrl;
  Timer? _refreshTimer;
  final MapController _mapController = MapController();

  @override
  void initState() {
    super.initState();
    _fetchFullOrderDetails();
    _pollLiveTracking();
    // Real-time polling every 3 seconds matching order_tracking.php
    _refreshTimer = Timer.periodic(const Duration(seconds: 3), (_) => _pollLiveTracking());
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _fetchFullOrderDetails() async {
    try {
      final res = await ApiService.get('${ApiConstants.orders}/${widget.orderCode}');
      if (res['success'] == true && res['data'] != null) {
        final data = res['data'];
        final orderMap = data['order'] is Map<String, dynamic>
            ? (data['order'] as Map<String, dynamic>)
            : (data is Map<String, dynamic> ? data : <String, dynamic>{});

        if (mounted) {
          setState(() {
            _orderData = orderMap;
            _snapUrl = data['snap_url']?.toString();
          });
        }
      }
    } catch (_) {}
  }

  Future<void> _pollLiveTracking() async {
    try {
      final res = await ApiService.get('${ApiConstants.orders}/${widget.orderCode}/live-tracking');
      if (res['success'] == true && res['data'] != null) {
        final live = res['data'] as Map<String, dynamic>;
        if (mounted) {
          setState(() {
            _liveData = live;
            _isLoading = false;
          });

          // Smoothly animate map camera to driver position if driver assigned
          final driverMap = live['driver'] is Map ? (live['driver'] as Map) : {};
          final driverLat = double.tryParse(driverMap['lat']?.toString() ?? '');
          final driverLng = double.tryParse(driverMap['lng']?.toString() ?? '');
          if (driverLat != null && driverLng != null && driverLat != 0 && driverLng != 0) {
            try {
              _mapController.move(LatLng(driverLat, driverLng), _mapController.camera.zoom);
            } catch (_) {}
          }
        }
      } else {
        if (mounted && _isLoading) {
          setState(() => _isLoading = false);
        }
      }
    } catch (_) {
      if (mounted && _isLoading) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _cancelUnpaidOrder() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Batalkan Pesanan?'),
        content: const Text('Apakah Anda yakin ingin membatalkan pesanan yang belum dibayar ini?'),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primaryRed, foregroundColor: Colors.white),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Ya, Batalkan'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      final res = await ApiService.postForm('${ApiConstants.orders}/cancel-unpaid', {
        'order_code': widget.orderCode,
      });
      if (res['success'] == true) {
        _pollLiveTracking();
        _fetchFullOrderDetails();
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(res['message'] ?? 'Gagal membatalkan pesanan')),
          );
        }
      }
    }
  }

  Future<void> _payNow() async {
    final url = _snapUrl ?? '${ApiConstants.domainUrl}/orders/${widget.orderCode}/tracking';
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Tidak dapat membuka halaman pembayaran')),
        );
      }
    }
  }

  Future<void> _openGoogleMapsNav(double destLat, double destLng) async {
    final url = 'https://www.google.com/maps/dir/?api=1&destination=$destLat,$destLng';
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        backgroundColor: const Color(0xFFF8FAFC),
        appBar: AppBar(
          title: const Text('Lacak Pesanan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          backgroundColor: Colors.white,
          foregroundColor: const Color(0xFF0F172A),
          elevation: 0,
        ),
        body: const Center(child: CircularProgressIndicator(color: AppTheme.primaryRed)),
      );
    }

    final order = _orderData ?? {};
    final live = _liveData ?? {};

    // Live synced state fields matching order_tracking.php
    final status = live['order_status']?.toString() ?? order['order_status']?.toString() ?? 'pending';
    final paymentMethod = live['payment_method']?.toString() ?? order['payment_method']?.toString() ?? 'cod';
    final paymentStatus = live['payment_status']?.toString() ?? order['payment_status']?.toString() ?? 'unpaid';
    final remainingSeconds = (live['remaining_seconds'] as num?)?.toInt() ?? 60;
    final otpCode = live['otp']?.toString() ?? order['otp']?.toString() ?? '----';
    final unreadChats = (live['unread_chats'] as num?)?.toInt() ?? 0;
    final cancellationReason = live['cancellation_reason']?.toString() ?? order['cancellation_reason']?.toString() ?? '';

    final bool isCanceled = status == 'canceled';
    final bool isUnpaidOnline = paymentMethod == 'midtrans' && paymentStatus != 'paid' && !isCanceled;
    final bool isDelivered = status == 'delivered';

    final driverMap = live['driver'] is Map ? (live['driver'] as Map) : {};
    final bool isDriverValid = (driverMap['assigned'] == true) ||
        (order['delivery_man_id'] != null && !isCanceled && ['processing', 'handover', 'on_the_way', 'delivered'].contains(status));

    // Map Coordinates
    final storeMap = live['store'] is Map ? (live['store'] as Map) : {};
    final destMap = live['destination'] is Map ? (live['destination'] as Map) : {};

    final storeLat = double.tryParse(storeMap['lat']?.toString() ?? order['store_lat']?.toString() ?? '') ?? -6.9835;
    final storeLng = double.tryParse(storeMap['lng']?.toString() ?? order['store_lng']?.toString() ?? '') ?? 107.8335;

    final custLat = double.tryParse(destMap['lat']?.toString() ?? order['delivery_lat']?.toString() ?? '') ?? -6.9855;
    final custLng = double.tryParse(destMap['lng']?.toString() ?? order['delivery_lng']?.toString() ?? '') ?? 107.8350;

    final driverLat = double.tryParse(driverMap['lat']?.toString() ?? order['dm_lat']?.toString() ?? '') ?? storeLat;
    final driverLng = double.tryParse(driverMap['lng']?.toString() ?? order['dm_lng']?.toString() ?? '') ?? storeLng;

    final LatLng centerPoint = isDriverValid
        ? LatLng((driverLat + custLat) / 2, (driverLng + custLng) / 2)
        : LatLng((storeLat + custLat) / 2, (storeLng + custLng) / 2);

    final totalAmount = double.tryParse(
      live['total_amount']?.toString() ??
      order['total_amount']?.toString() ??
      order['order_amount']?.toString() ??
      order['grand_total']?.toString() ??
      (live['batch_info'] is Map ? (live['batch_info'] as Map)['total_amount']?.toString() : null) ??
      '0'
    ) ?? 0.0;
    final driverName = driverMap['name']?.toString() ?? order['dm_name']?.toString() ?? 'Mitra Kurir Cicalengka';
    final driverPhone = driverMap['phone']?.toString() ?? order['dm_phone']?.toString() ?? '';
    final driverAvatar = driverMap['avatar']?.toString() ?? order['dm_avatar']?.toString();
    final vehicleType = driverMap['vehicle']?.toString() ?? order['vehicle_type']?.toString() ?? 'Motor';
    final vehiclePlate = driverMap['plate']?.toString() ?? order['vehicle_number']?.toString() ?? '';

    final Map<String, dynamic>? batchInfo = live['batch_info'] is Map
        ? Map<String, dynamic>.from(live['batch_info'] as Map)
        : (order['batch_stores'] != null ? <String, dynamic>{'is_multi_pickup': true} : null);

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF0F172A),
        elevation: 0.5,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              isUnpaidOnline ? 'Pembayaran Pesanan' : (isCanceled ? 'Pesanan Dibatalkan' : 'Lacak Pengantaran'),
              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
            ),
            Text(
              '#${widget.orderCode}',
              style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontFamily: 'monospace'),
            ),
          ],
        ),
        actions: [
          Center(
            child: Padding(
              padding: const EdgeInsets.only(right: 14),
              child: _buildStatusBadge(status, isUnpaidOnline, isCanceled),
            ),
          ),
        ],
      ),
      body: isCanceled
          ? _buildCanceledView(order, live, cancellationReason, paymentMethod, paymentStatus, totalAmount)
          : (isUnpaidOnline
              ? _buildUnpaidView(order, live, totalAmount)
              : _buildActiveTrackingView(
                  centerPoint: centerPoint,
                  storeLat: storeLat,
                  storeLng: storeLng,
                  custLat: custLat,
                  custLng: custLng,
                  driverLat: driverLat,
                  driverLng: driverLng,
                  isDriverValid: isDriverValid,
                  isDelivered: isDelivered,
                  driverAvatar: driverAvatar,
                  driverName: driverName,
                  vehicleType: vehicleType,
                  vehiclePlate: vehiclePlate,
                  driverPhone: driverPhone,
                  status: status,
                  otpCode: otpCode,
                  unreadChats: unreadChats,
                  remainingSeconds: remainingSeconds,
                  totalAmount: totalAmount,
                  batchInfo: batchInfo,
                  order: order,
                  live: live,
                )),
    );
  }

  Widget _buildStatusBadge(String status, bool isUnpaid, bool isCanceled) {
    if (isCanceled) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(12)),
        child: const Text('DIBATALKAN', style: TextStyle(color: AppTheme.primaryRed, fontSize: 9.5, fontWeight: FontWeight.w900)),
      );
    }
    if (isUnpaid) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(color: const Color(0xFFFEF3C7), borderRadius: BorderRadius.circular(12)),
        child: const Text('MENUNGGU BAYAR', style: TextStyle(color: Color(0xFFD97706), fontSize: 9.5, fontWeight: FontWeight.w900)),
      );
    }
    final labels = {
      'pending': 'Mencari Driver',
      'confirmed': 'Dikonfirmasi Resto',
      'processing': 'Sedang Disiapkan',
      'handover': 'Diserahkan ke Kurir',
      'picked_up': 'Pesanan Diambil Kurir',
      'on_the_way': 'Kurir Menuju Lokasi',
      'delivered': 'Pesanan Selesai',
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: status == 'delivered' ? const Color(0xFFDCFCE7) : const Color(0xFFDBEAFE),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        labels[status] ?? status.toUpperCase(),
        style: TextStyle(
          color: status == 'delivered' ? const Color(0xFF15803D) : const Color(0xFF1D4ED8),
          fontSize: 9.5,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }

  Widget _buildUnpaidView(Map<String, dynamic> order, Map<String, dynamic> live, double totalAmount) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 16)],
            ),
            child: Column(
              children: [
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: const BoxDecoration(color: Color(0xFFFEF3C7), shape: BoxShape.circle),
                  child: const Icon(Icons.credit_card_rounded, size: 36, color: Color(0xFFD97706)),
                ),
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(color: const Color(0xFFFFFBEB), borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0xFFFDE68A))),
                  child: const Text('MENUNGGU PEMBAYARAN', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: Color(0xFFB45309))),
                ),
                const SizedBox(height: 12),
                const Text('Selesaikan Pembayaran', style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                const SizedBox(height: 4),
                const Text('Total Tagihan Pembayaran:', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                const SizedBox(height: 10),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    children: [
                      const Text('TOTAL NOMINAL', style: TextStyle(fontSize: 9.5, color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                      const SizedBox(height: 4),
                      Text(
                        CurrencyFormatter.formatRupiah(totalAmount),
                        style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: AppTheme.primaryRed),
                      ),
                      const SizedBox(height: 4),
                      const Text('Midtrans QRIS / VA / E-Wallet', style: TextStyle(fontSize: 10, color: Color(0xFF16A34A), fontWeight: FontWeight.w700)),
                    ],
                  ),
                ),
                const SizedBox(height: 14),

                // Store & Items Card
                _buildStoreAndItemsCard(order, live),

                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(color: const Color(0xFFFFFBEB), borderRadius: BorderRadius.circular(12)),
                  child: const Row(
                    children: [
                      Icon(Icons.warning_amber_rounded, color: Color(0xFFD97706), size: 18),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Pesanan belum diproses. Silakan klik tombol Bayar Sekarang di bawah.',
                          style: TextStyle(fontSize: 10.5, color: Color(0xFFB45309), fontWeight: FontWeight.w500),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primaryRed,
                    foregroundColor: Colors.white,
                    minimumSize: const Size(double.infinity, 46),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
                  ),
                  onPressed: _payNow,
                  icon: const Icon(Icons.wallet_rounded, size: 18),
                  label: const Text('Bayar Sekarang (Midtrans)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 10),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                        ),
                        onPressed: () => _pollLiveTracking(),
                        icon: const Icon(Icons.refresh_rounded, size: 16),
                        label: const Text('Cek Status', style: TextStyle(fontSize: 11.5)),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppTheme.primaryRed,
                          side: const BorderSide(color: AppTheme.primaryRed),
                          padding: const EdgeInsets.symmetric(vertical: 10),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                        ),
                        onPressed: _cancelUnpaidOrder,
                        icon: const Icon(Icons.close_rounded, size: 16),
                        label: const Text('Batalkan', style: TextStyle(fontSize: 11.5)),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCanceledView(
    Map<String, dynamic> order,
    Map<String, dynamic> live,
    String cancellationReason,
    String paymentMethod,
    String paymentStatus,
    double totalAmount,
  ) {
    final bool isRefundable = ['wallet', 'midtrans', 'online', 'qris', 'va', 'credit_card'].contains(paymentMethod) && paymentStatus == 'refunded';

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          // Red Header Banner
          Container(
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF262626), Color(0xFF000000)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.2), blurRadius: 12)],
            ),
            padding: const EdgeInsets.all(18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: const BoxDecoration(color: Colors.white24, shape: BoxShape.circle),
                      child: const Icon(Icons.cancel_rounded, color: Colors.white, size: 22),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Pesanan Dibatalkan Otomatis', style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold)),
                          Text('Tidak mendapatkan kurir dalam waktu 1 menit', style: TextStyle(color: Colors.white70, fontSize: 10.5)),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)),
                  child: Text(
                    cancellationReason.isNotEmpty ? cancellationReason : 'Batal Otomatis: Tidak mendapatkan driver dalam waktu 1 menit',
                    style: const TextStyle(color: Colors.white70, fontSize: 10.5, height: 1.4),
                  ),
                ),
              ],
            ),
          ),

          // Store & Purchased Items Card
          _buildStoreAndItemsCard(order, live),

          const SizedBox(height: 14),

          // Refund Status Body
          Container(
            padding: const EdgeInsets.all(18),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(bottom: Radius.circular(20)),
            ),
            child: Column(
              children: [
                if (isRefundable)
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFDCFCE7),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFBBF7D0)),
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(6),
                          decoration: const BoxDecoration(color: Color(0xFF16A34A), shape: BoxShape.circle),
                          child: const Icon(Icons.account_balance_wallet_rounded, color: Colors.white, size: 16),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Dana Berhasil Dikembalikan 💚', style: TextStyle(color: Color(0xFF15803D), fontSize: 11.5, fontWeight: FontWeight.bold)),
                              Text('${CurrencyFormatter.formatRupiah(totalAmount)} telah masuk ke CicalengkaPay Anda', style: const TextStyle(fontSize: 10, color: Color(0xFF475569))),
                            ],
                          ),
                        ),
                        TextButton(
                          onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const CustomerWalletScreen())),
                          child: const Text('Lihat Mutasi', style: TextStyle(color: Color(0xFF16A34A), fontWeight: FontWeight.bold, fontSize: 10.5)),
                        ),
                      ],
                    ),
                  )
                else
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.info_outline_rounded, color: Color(0xFF64748B), size: 18),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            paymentMethod == 'cod' ? 'Metode COD: tidak ada dana yang telah keluar.' : 'Pesanan belum dibayar. Tidak ada dana yang dipotong.',
                            style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                          ),
                        ),
                      ],
                    ),
                  ),

                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppTheme.primaryRed,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
                        ),
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.refresh_rounded, size: 16),
                        label: const Text('Pesan Lagi', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActiveTrackingView({
    required LatLng centerPoint,
    required double storeLat,
    required double storeLng,
    required double custLat,
    required double custLng,
    required double driverLat,
    required double driverLng,
    required bool isDriverValid,
    required bool isDelivered,
    required String? driverAvatar,
    required String driverName,
    required String vehicleType,
    required String vehiclePlate,
    required String driverPhone,
    required String status,
    required String otpCode,
    required int unreadChats,
    required int remainingSeconds,
    required double totalAmount,
    required Map<String, dynamic>? batchInfo,
    required Map<String, dynamic> order,
    required Map<String, dynamic> live,
  }) {
    // Calculate live distance and estimated ETA
    final distKm = const Distance().as(LengthUnit.Kilometer, LatLng(driverLat, driverLng), LatLng(custLat, custLng));
    final etaMin = (distKm * 3 + 5).round();

    return Column(
      children: [
        // Live Map Container with Floating HUD
        Expanded(
          flex: 4,
          child: Stack(
            children: [
              FlutterMap(
                mapController: _mapController,
                options: MapOptions(
                  initialCenter: centerPoint,
                  initialZoom: 14.5,
                ),
                children: [
                  TileLayer(
                    urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                    userAgentPackageName: 'com.cicalengkago.mobile',
                  ),
                  MarkerLayer(
                    markers: [
                      // Store Pin
                      Marker(
                        point: LatLng(storeLat, storeLng),
                        width: 42,
                        height: 42,
                        child: Container(
                          decoration: BoxDecoration(
                            color: const Color(0xFF16A34A),
                            shape: BoxShape.circle,
                            boxShadow: [BoxShadow(color: const Color(0xFF16A34A).withValues(alpha: 0.4), blurRadius: 10)],
                          ),
                          child: const Icon(Icons.storefront_rounded, color: Colors.white, size: 20),
                        ),
                      ),
                      // Customer Pin
                      Marker(
                        point: LatLng(custLat, custLng),
                        width: 42,
                        height: 42,
                        child: Container(
                          decoration: BoxDecoration(
                            color: AppTheme.primaryRed,
                            shape: BoxShape.circle,
                            boxShadow: [BoxShadow(color: AppTheme.primaryRed.withValues(alpha: 0.4), blurRadius: 10)],
                          ),
                          child: const Icon(Icons.home_rounded, color: Colors.white, size: 20),
                        ),
                      ),
                      // Driver Pin
                      if (isDriverValid)
                        Marker(
                          point: LatLng(driverLat, driverLng),
                          width: 44,
                          height: 44,
                          child: Container(
                            decoration: BoxDecoration(
                              color: const Color(0xFF2563EB),
                              shape: BoxShape.circle,
                              boxShadow: [BoxShadow(color: const Color(0xFF2563EB).withValues(alpha: 0.4), blurRadius: 12)],
                            ),
                            child: const Icon(Icons.delivery_dining_rounded, color: Colors.white, size: 22),
                          ),
                        ),
                    ],
                  ),
                ],
              ),

              // Top Floating HUD (Live Radar Status)
              Positioned(
                top: 10,
                left: 12,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.75),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.radar_rounded, color: Colors.greenAccent, size: 14),
                      SizedBox(width: 6),
                      Text('Live GPS Radar', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ),

              // Bottom Floating Telemetry HUD (ETA & Controls)
              Positioned(
                bottom: 12,
                left: 12,
                right: 12,
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 8)]),
                          child: Row(
                            children: [
                              const Icon(Icons.pin_drop_rounded, color: AppTheme.primaryRed, size: 14),
                              const SizedBox(width: 4),
                              Text('${distKm.toStringAsFixed(1)} km', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                          decoration: BoxDecoration(color: AppTheme.primaryRed, borderRadius: BorderRadius.circular(16), boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 8)]),
                          child: Row(
                            children: [
                              const Icon(Icons.timer_rounded, color: Colors.white, size: 14),
                              const SizedBox(width: 4),
                              Text('Est. Tiba ~$etaMin min', style: const TextStyle(color: Colors.white, fontSize: 10.5, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        if (isDriverValid)
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.white,
                              foregroundColor: AppTheme.primaryRed,
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                              elevation: 2,
                            ),
                            onPressed: () {
                              _mapController.move(LatLng(driverLat, driverLng), 15.5);
                            },
                            icon: const Icon(Icons.my_location_rounded, size: 14),
                            label: const Text('Fokus Kurir', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                          ),
                        const SizedBox(width: 8),
                        ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppTheme.primaryRed,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                            elevation: 2,
                          ),
                          onPressed: () => _openGoogleMapsNav(custLat, custLng),
                          icon: const Icon(Icons.navigation_rounded, size: 14),
                          label: const Text('Maps Nav', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),

        // Tracking Details Bottom Sheet
        Expanded(
          flex: 6,
          child: Container(
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
              boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 12, offset: Offset(0, -4))],
            ),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Order Delivered Celebration Card
                  if (isDelivered) ...[
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(colors: [Color(0xFF10B981), Color(0xFF047857)]),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.check_circle_rounded, color: Colors.white, size: 30),
                          SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Pesanan Selesai Diantar! 🎉', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                                Text('Terima kasih telah memesan melalui CicalengkaGO.', style: TextStyle(color: Colors.white70, fontSize: 10.5)),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                  ],

                  // OTP Code Banner Card
                  if (!isDelivered) ...[
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(colors: [Color(0xFF101820), Color(0xFF1E293B)]),
                        borderRadius: BorderRadius.circular(16),
                        border: const Border(left: BorderSide(color: AppTheme.primaryRed, width: 4)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('KODE OTP PENERIMAAN', style: TextStyle(color: Colors.white54, fontSize: 9, fontWeight: FontWeight.bold, letterSpacing: 0.5)),
                              SizedBox(height: 2),
                              Text('Berikan 4-digit ini kepada kurir', style: TextStyle(color: Colors.white70, fontSize: 10)),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                            decoration: BoxDecoration(color: AppTheme.primaryRed.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(10)),
                            child: Text(
                              otpCode,
                              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: AppTheme.primaryRed, letterSpacing: 3),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                  ],

                  // Multi-Store Batch Pickup Notice Card
                  if (batchInfo != null && batchInfo['is_multi_pickup'] == true) ...[
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFFBEB),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: const Color(0xFFFCD34D)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.store_rounded, color: Color(0xFFD97706), size: 22),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Penjemputan Multi-Toko (Ke-${batchInfo['pickup_sequence'] ?? 1})', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11.5, color: Color(0xFF92400E))),
                                const Text('Kurir mengambil pesanan dari beberapa toko dalam satu rute.', style: TextStyle(fontSize: 10, color: Color(0xFFB45309))),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                  ],

                  // Driver Card (Assigned or Searching)
                  if (isDriverValid)
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Row(
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(24),
                            child: CachedNetworkImage(
                              imageUrl: ApiConstants.formatImageUrl(driverAvatar),
                              width: 46,
                              height: 46,
                              fit: BoxFit.cover,
                              errorWidget: (context, url, error) => Container(
                                width: 46,
                                height: 46,
                                color: const Color(0xFFE2E8F0),
                                child: const Icon(Icons.motorcycle_rounded, color: Color(0xFF64748B), size: 24),
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Text(driverName, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0F172A))),
                                    const SizedBox(width: 4),
                                    const Icon(Icons.verified_rounded, color: AppTheme.primaryRed, size: 14),
                                  ],
                                ),
                                const SizedBox(height: 2),
                                Text('$vehicleType ${vehiclePlate.isNotEmpty ? "• $vehiclePlate" : ""}', style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600)),
                              ],
                            ),
                          ),
                          Row(
                            children: [
                              // Voice Call Button
                              IconButton(
                                onPressed: () {
                                  GlobalCallService.instance.openCallScreen(
                                    context,
                                    orderCode: widget.orderCode,
                                    isIncoming: false,
                                  );
                                },
                                icon: const Icon(Icons.phone_in_talk_rounded, color: Color(0xFF2563EB), size: 20),
                              ),
                              // In-App Chat Button
                              Stack(
                                children: [
                                  IconButton(
                                    onPressed: () {
                                      final authCtrl = context.read<AuthController>();
                                      final uid = int.tryParse(authCtrl.user?['id']?.toString() ?? '0') ?? 0;
                                      InAppChatModal.show(
                                        context,
                                        orderCode: widget.orderCode,
                                        currentUserId: uid,
                                        currentUserRole: 'customer',
                                      );
                                    },
                                    icon: const Icon(Icons.chat_bubble_rounded, color: AppTheme.primaryRed, size: 20),
                                  ),
                                  if (unreadChats > 0)
                                    Positioned(
                                      right: 6,
                                      top: 6,
                                      child: Container(
                                        padding: const EdgeInsets.all(4),
                                        decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                                        child: Text('$unreadChats', style: const TextStyle(color: Colors.white, fontSize: 8.5, fontWeight: FontWeight.bold)),
                                      ),
                                    ),
                                ],
                              ),
                              if (driverPhone.isNotEmpty)
                                IconButton(
                                  onPressed: () => launchUrl(Uri.parse('tel:$driverPhone')),
                                  icon: const Icon(Icons.call_rounded, color: Color(0xFF16A34A), size: 20),
                                ),
                            ],
                          ),
                        ],
                      ),
                    )
                  else if (!isDelivered)
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 10)],
                      ),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(strokeWidth: 2.5, color: AppTheme.primaryRed),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text('Mencari Kurir Terdekat...', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                                    Text('Sisa waktu pencarian: $remainingSeconds detik', style: const TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                                  ],
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(10)),
                                child: Text('00:${remainingSeconds.toString().padLeft(2, '0')}', style: const TextStyle(color: AppTheme.primaryRed, fontWeight: FontWeight.w800, fontSize: 11)),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          ClipRRect(
                            borderRadius: BorderRadius.circular(4),
                            child: LinearProgressIndicator(
                              value: (remainingSeconds / 60).clamp(0.0, 1.0),
                              backgroundColor: const Color(0xFFF1F5F9),
                              color: AppTheme.primaryRed,
                              minHeight: 4,
                            ),
                          ),
                        ],
                      ),
                    ),

                  const SizedBox(height: 16),

                  // Delivery Stepper Progress (4 Steps matching order_tracking.php)
                  _buildFourStepStepper(status),

                  // Store & Purchased Items Details Card
                  _buildStoreAndItemsCard(order, live),

                  const SizedBox(height: 16),
                  const Divider(height: 1, color: Color(0xFFF1F5F9)),
                  const SizedBox(height: 14),

                  // Order Summary & Rating Row
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Total Biaya', style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600)),
                          Text(
                            CurrencyFormatter.formatRupiah(totalAmount),
                            style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                          ),
                        ],
                      ),
                      if (isDelivered)
                        ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppTheme.primaryRed,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                          ),
                          onPressed: () => _showReviewDialog(context, order),
                          icon: const Icon(Icons.star_rounded, color: Colors.amber, size: 16),
                          label: const Text('Beri Rating', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildFourStepStepper(String status) {
    final bool isStep1Done = true;
    final bool isStep2Done = ['processing', 'handover', 'picked_up', 'on_the_way', 'delivered'].contains(status);
    final bool isStep2Active = status == 'confirmed';

    final bool isStep3Done = ['on_the_way', 'delivered'].contains(status);
    final bool isStep3Active = ['processing', 'handover', 'picked_up'].contains(status);

    final bool isStep4Done = status == 'delivered';
    final bool isStep4Active = status == 'on_the_way';

    final steps = [
      {
        'title': 'Pesanan Dikonfirmasi',
        'sub': 'Resto/Mitra menerima pesanan Anda',
        'done': isStep1Done,
        'active': false,
        'icon': Icons.receipt_long_rounded
      },
      {
        'title': 'Diproses & Disiapkan',
        'sub': 'Makanan sedang dimasak / dikemas',
        'done': isStep2Done,
        'active': isStep2Active,
        'icon': Icons.soup_kitchen_rounded
      },
      {
        'title': 'Kurir Menuju Lokasi Anda',
        'sub': 'Kurir dalam perjalanan pengantaran',
        'done': isStep3Done,
        'active': isStep3Active,
        'icon': Icons.two_wheeler_rounded
      },
      {
        'title': 'Pesanan Selesai',
        'sub': 'Barang sampai dengan aman',
        'done': isStep4Done,
        'active': isStep4Active,
        'icon': Icons.task_alt_rounded
      },
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Status Pengantaran', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0F172A))),
        const SizedBox(height: 12),
        ...List.generate(steps.length, (i) {
          final s = steps[i];
          final bool done = s['done'] as bool;
          final bool active = s['active'] as bool;

          return Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Column(
                children: [
                  Container(
                    width: 26,
                    height: 26,
                    decoration: BoxDecoration(
                      color: done
                          ? const Color(0xFF16A34A)
                          : (active ? AppTheme.primaryRed : const Color(0xFFF1F5F9)),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      done ? Icons.check_rounded : (s['icon'] as IconData),
                      color: (done || active) ? Colors.white : const Color(0xFFCBD5E1),
                      size: 13,
                    ),
                  ),
                  if (i < steps.length - 1)
                    Container(
                      width: 2,
                      height: 22,
                      color: done ? const Color(0xFF16A34A) : const Color(0xFFF1F5F9),
                    ),
                ],
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.only(top: 2),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        s['title'] as String,
                        style: TextStyle(
                          fontSize: 11.5,
                          fontWeight: (done || active) ? FontWeight.bold : FontWeight.w500,
                          color: (done || active) ? const Color(0xFF0F172A) : const Color(0xFF94A3B8),
                        ),
                      ),
                      Text(
                        s['sub'] as String,
                        style: const TextStyle(fontSize: 9.5, color: Color(0xFF64748B)),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          );
        }),
      ],
    );
  }

  Widget _buildStoreAndItemsCard(Map<String, dynamic> order, Map<String, dynamic> live) {
    final liveStore = live['store'] is Map ? (live['store'] as Map) : {};
    final storeName = liveStore['name']?.toString() ??
        order['store_name']?.toString() ??
        'Mitra Resto CicalengkaGO';
    final storeAddress = liveStore['address']?.toString() ??
        order['store_address']?.toString() ??
        'Cicalengka, Kab. Bandung';
    final storePhone = order['store_phone']?.toString() ?? '';

    final List items = (order['items'] as List?) ?? [];
    final orderNotes = order['order_notes']?.toString() ?? '';

    return Container(
      margin: const EdgeInsets.only(top: 14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Store Header
          Container(
            padding: const EdgeInsets.all(12),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
              border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
            ),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: AppTheme.inkBlack,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.storefront_rounded, color: Colors.white, size: 20),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        storeName,
                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        storeAddress,
                        style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                if (storePhone.isNotEmpty)
                  IconButton(
                    onPressed: () => launchUrl(Uri.parse('tel:$storePhone')),
                    icon: const Icon(Icons.call_rounded, color: Color(0xFF16A34A), size: 18),
                    tooltip: 'Hubungi Toko',
                  ),
              ],
            ),
          ),

          // Items Purchased List
          Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'RINCIAN ITEM (${items.isEmpty ? 1 : items.length})',
                      style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Color(0xFF64748B), letterSpacing: 0.5),
                    ),
                    const Icon(Icons.shopping_bag_outlined, size: 14, color: Color(0xFF64748B)),
                  ],
                ),
                const SizedBox(height: 8),

                if (items.isEmpty)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 4),
                    child: Text('1x Pesanan CicalengkaGO', style: TextStyle(fontSize: 12, color: AppTheme.inkBlack, fontWeight: FontWeight.w600)),
                  )
                else
                  ...items.map((item) {
                    final name = item['product_name'] ?? item['name'] ?? 'Menu Kuliner';
                    final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;
                    final price = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
                    final itemTotal = price * qty;

                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                            decoration: BoxDecoration(
                              color: AppTheme.inkBlack,
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              '${qty}x',
                              style: const TextStyle(color: Colors.white, fontSize: 10.5, fontWeight: FontWeight.bold),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  name.toString(),
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                                ),
                                if (item['variant'] != null && item['variant'].toString().isNotEmpty)
                                  Text(
                                    'Varian: ${item['variant']}',
                                    style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                                  ),
                              ],
                            ),
                          ),
                          if (price > 0)
                            Text(
                              CurrencyFormatter.formatRupiah(itemTotal),
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppTheme.inkBlack),
                            ),
                        ],
                      ),
                    );
                  }),

                if (orderNotes.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFFBEB),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: const Color(0xFFFCD34D)),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.edit_note_rounded, size: 16, color: Color(0xFFD97706)),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'Catatan: $orderNotes',
                            style: const TextStyle(fontSize: 11, color: Color(0xFF92400E), fontWeight: FontWeight.w500),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showReviewDialog(BuildContext context, Map<String, dynamic> order) {
    int storeRating = 5;
    final storeCommentCtrl = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) => Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: EdgeInsets.only(
            left: 20, right: 20, top: 20,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 20,
          ),
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(width: 40, height: 4, decoration: BoxDecoration(color: const Color(0xFFE2E8F0), borderRadius: BorderRadius.circular(2))),
                ),
                const SizedBox(height: 16),
                const Text('⭐ Beri Rating & Ulasan', style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                const SizedBox(height: 4),
                Text('Pesanan #${order['order_code']}', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                const SizedBox(height: 18),

                const Text('Rating Toko & Makanan', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: List.generate(5, (i) => GestureDetector(
                    onTap: () => setModalState(() => storeRating = i + 1),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                      child: Icon(
                        i < storeRating ? Icons.star_rounded : Icons.star_outline_rounded,
                        color: Colors.amber,
                        size: 34,
                      ),
                    ),
                  )),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: storeCommentCtrl,
                  maxLines: 2,
                  decoration: InputDecoration(
                    hintText: 'Tulis ulasan makanan & toko...',
                    hintStyle: const TextStyle(fontSize: 12),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                    contentPadding: const EdgeInsets.all(12),
                  ),
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primaryRed,
                    foregroundColor: Colors.white,
                    minimumSize: const Size(double.infinity, 44),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                  ),
                  onPressed: () async {
                    Navigator.pop(ctx);
                    final res = await ApiService.postForm('${ApiConstants.orders}/review', {
                      'order_code': widget.orderCode,
                      'rating': storeRating.toString(),
                      'comment': storeCommentCtrl.text,
                    });
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text(res['message'] ?? 'Terima kasih atas ulasan Anda!')),
                      );
                    }
                  },
                  child: const Text('Kirim Ulasan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
