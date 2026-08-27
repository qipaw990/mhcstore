import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';

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

  final List<Map<String, dynamic>> _statusSteps = [
    {'key': 'pending', 'label': 'Pesanan Diterima', 'icon': Icons.receipt_long_rounded},
    {'key': 'confirmed', 'label': 'Dikonfirmasi Resto', 'icon': Icons.verified_rounded},
    {'key': 'processing', 'label': 'Sedang Diproses', 'icon': Icons.soup_kitchen_rounded},
    {'key': 'handover', 'label': 'Diserahkan ke Kurir', 'icon': Icons.handshake_rounded},
    {'key': 'picked_up', 'label': 'Diambil Kurir', 'icon': Icons.two_wheeler_rounded},
    {'key': 'on_the_way', 'label': 'Kurir Menuju Lokasi Anda', 'icon': Icons.delivery_dining_rounded},
    {'key': 'delivered', 'label': 'Pesanan Tiba', 'icon': Icons.task_alt_rounded},
  ];

  @override
  void initState() {
    super.initState();
    _fetchFullOrderDetails();
    _pollLiveTracking();
    // Live polling every 3 seconds for real-time driver & status updates
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

          // Move map to driver location if driver is assigned & lat/lng updated
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

  int _getCurrentStepIndex(String status) {
    for (int i = 0; i < _statusSteps.length; i++) {
      if (_statusSteps[i]['key'] == status) return i;
    }
    if (status == 'picked_up') return 4;
    return 0;
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

    // Live synced values from server live-tracking endpoint
    final status = live['order_status']?.toString() ?? order['order_status']?.toString() ?? 'pending';
    final paymentMethod = live['payment_method']?.toString() ?? order['payment_method']?.toString() ?? 'cod';
    final paymentStatus = live['payment_status']?.toString() ?? order['payment_status']?.toString() ?? 'unpaid';
    final remainingSeconds = (live['remaining_seconds'] as num?)?.toInt() ?? 60;

    final bool isCanceled = status == 'canceled';
    final bool isUnpaidOnline = paymentMethod == 'midtrans' && paymentStatus != 'paid' && !isCanceled;
    final bool isDelivered = status == 'delivered';

    final driverMap = live['driver'] is Map ? (live['driver'] as Map) : {};
    final bool hasDriver = (driverMap['assigned'] == true) || (order['delivery_man_id'] != null && !isCanceled);

    final currentStep = _getCurrentStepIndex(status);

    // Map Coordinates live sync
    final storeMap = live['store'] is Map ? (live['store'] as Map) : {};
    final destMap = live['destination'] is Map ? (live['destination'] as Map) : {};

    final storeLat = double.tryParse(storeMap['lat']?.toString() ?? order['store_lat']?.toString() ?? '') ?? -6.9835;
    final storeLng = double.tryParse(storeMap['lng']?.toString() ?? order['store_lng']?.toString() ?? '') ?? 107.8335;

    final custLat = double.tryParse(destMap['lat']?.toString() ?? order['delivery_lat']?.toString() ?? '') ?? -6.9855;
    final custLng = double.tryParse(destMap['lng']?.toString() ?? order['delivery_lng']?.toString() ?? '') ?? 107.8350;

    final driverLat = double.tryParse(driverMap['lat']?.toString() ?? order['dm_lat']?.toString() ?? '') ?? storeLat;
    final driverLng = double.tryParse(driverMap['lng']?.toString() ?? order['dm_lng']?.toString() ?? '') ?? storeLng;

    final LatLng centerPoint = hasDriver
        ? LatLng((driverLat + custLat) / 2, (driverLng + custLng) / 2)
        : LatLng((storeLat + custLat) / 2, (storeLng + custLng) / 2);

    final totalAmount = double.tryParse(order['total_amount']?.toString() ?? '0') ?? 0.0;
    final driverName = driverMap['name']?.toString() ?? order['dm_name']?.toString() ?? 'Kurir CicalengkaGO';
    final driverPhone = driverMap['phone']?.toString() ?? order['dm_phone']?.toString() ?? '';
    final driverAvatar = driverMap['avatar']?.toString() ?? order['dm_avatar']?.toString();
    final vehicleType = driverMap['vehicle']?.toString() ?? order['vehicle_type']?.toString() ?? 'Motor';
    final vehiclePlate = driverMap['plate']?.toString() ?? order['vehicle_number']?.toString() ?? '';

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
          ? _buildCanceledView()
          : (isUnpaidOnline
              ? _buildUnpaidView(totalAmount)
              : _buildLiveTrackingView(
                  centerPoint: centerPoint,
                  storeLat: storeLat,
                  storeLng: storeLng,
                  custLat: custLat,
                  custLng: custLng,
                  driverLat: driverLat,
                  driverLng: driverLng,
                  hasDriver: hasDriver,
                  isDelivered: isDelivered,
                  driverAvatar: driverAvatar,
                  driverName: driverName,
                  vehicleType: vehicleType,
                  vehiclePlate: vehiclePlate,
                  driverPhone: driverPhone,
                  currentStep: currentStep,
                  remainingSeconds: remainingSeconds,
                  totalAmount: totalAmount,
                  order: order,
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
      'confirmed': 'Dikonfirmasi',
      'processing': 'Diproses Resto',
      'handover': 'Diserahkan ke Kurir',
      'picked_up': 'Pesanan Diambil',
      'on_the_way': 'Dalam Pengantaran',
      'delivered': 'Pesanan Tiba',
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

  Widget _buildCanceledView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: const BoxDecoration(color: Color(0xFFFEE2E2), shape: BoxShape.circle),
              child: const Icon(Icons.cancel_rounded, size: 48, color: AppTheme.primaryRed),
            ),
            const SizedBox(height: 16),
            const Text('Pesanan Telah Dibatalkan', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
            const SizedBox(height: 6),
            const Text('Pesanan ini tidak lagi diproses.', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
            const SizedBox(height: 24),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primaryRed,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              ),
              onPressed: () => Navigator.pop(context),
              child: const Text('Pesan Kembali', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildUnpaidView(double totalAmount) {
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

  Widget _buildLiveTrackingView({
    required LatLng centerPoint,
    required double storeLat,
    required double storeLng,
    required double custLat,
    required double custLng,
    required double driverLat,
    required double driverLng,
    required bool hasDriver,
    required bool isDelivered,
    required String? driverAvatar,
    required String driverName,
    required String vehicleType,
    required String vehiclePlate,
    required String driverPhone,
    required int currentStep,
    required int remainingSeconds,
    required double totalAmount,
    required Map<String, dynamic> order,
  }) {
    return Column(
      children: [
        // Live Map Header
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
                      if (hasDriver)
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

              // Driver Search Radar Overlay
              if (!hasDriver && !isDelivered)
                Positioned(
                  bottom: 12,
                  left: 14,
                  right: 14,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 12)],
                    ),
                    child: Row(
                      children: [
                        const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2.5, color: AppTheme.primaryRed),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Text(
                                'Mencari Driver Terdekat...',
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF0F172A)),
                              ),
                              Text(
                                'Sisa waktu pencarian: $remainingSeconds detik',
                                style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
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
                  // Driver Information Card
                  if (hasDriver) ...[
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
                              errorWidget: (_, __, ___) => Container(
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
                                Text(
                                  driverName,
                                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  '$vehicleType ${vehiclePlate.isNotEmpty ? "• $vehiclePlate" : ""}',
                                  style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                                ),
                              ],
                            ),
                          ),
                          if (driverPhone.isNotEmpty) ...[
                            InkWell(
                              onTap: () => launchUrl(Uri.parse('tel:$driverPhone')),
                              borderRadius: BorderRadius.circular(20),
                              child: Container(
                                padding: const EdgeInsets.all(9),
                                decoration: const BoxDecoration(
                                  color: Color(0xFFDCFCE7),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.call_rounded, color: Color(0xFF16A34A), size: 18),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Status Stepper
                  _buildStatusStepper(currentStep, isDelivered),

                  const SizedBox(height: 16),
                  const Divider(height: 1, color: Color(0xFFF1F5F9)),
                  const SizedBox(height: 14),

                  // Order Summary Row
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

  Widget _buildStatusStepper(int currentStep, bool isDelivered) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          isDelivered ? '✅ Pesanan Selesai!' : '🚀 Status Pengantaran Live',
          style: TextStyle(
            fontSize: 14.5,
            fontWeight: FontWeight.w800,
            color: isDelivered ? const Color(0xFF16A34A) : const Color(0xFF0F172A),
          ),
        ),
        const SizedBox(height: 12),
        ...List.generate(_statusSteps.length, (i) {
          final step = _statusSteps[i];
          final isDone = i <= currentStep;
          final isActive = i == currentStep;

          return Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Column(
                children: [
                  Container(
                    width: 28,
                    height: 28,
                    decoration: BoxDecoration(
                      color: isDone
                          ? (isActive ? AppTheme.primaryRed : const Color(0xFF16A34A))
                          : const Color(0xFFF1F5F9),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      step['icon'] as IconData,
                      color: isDone ? Colors.white : const Color(0xFFCBD5E1),
                      size: 14,
                    ),
                  ),
                  if (i < _statusSteps.length - 1)
                    Container(
                      width: 2,
                      height: 20,
                      color: i < currentStep ? const Color(0xFF16A34A) : const Color(0xFFF1F5F9),
                    ),
                ],
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Text(
                    step['label'] as String,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: isActive ? FontWeight.bold : FontWeight.w500,
                      color: isDone ? (isActive ? AppTheme.primaryRed : const Color(0xFF16A34A)) : const Color(0xFF94A3B8),
                    ),
                  ),
                ),
              ),
            ],
          );
        }),
      ],
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
