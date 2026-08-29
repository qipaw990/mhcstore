import 'dart:async';
import 'dart:convert';
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
import '../../../core/services/location_service.dart';
import '../../../core/services/route_service.dart';
import '../../common/screens/in_app_chat_modal.dart';
import '../../auth/controllers/auth_controller.dart';
import '../widgets/order_item_detail_modal.dart';
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
  Timer? _tickerTimer;
  int _currentRemainingSeconds = 60;
  final MapController _mapController = MapController();
  LatLng? _previousDriverPos;
  double _driverBearing = 0.0;

  // Real-time Road Polyline Routing State
  List<LatLng> _liveCustomerRoadPoints = [];
  String? _lastCustomerRouteKey;

  void _syncCustomerRoadRoute(LatLng start, LatLng end) {
    final key = '${start.latitude.toStringAsFixed(3)},${start.longitude.toStringAsFixed(3)}->${end.latitude.toStringAsFixed(3)},${end.longitude.toStringAsFixed(3)}';
    if (_lastCustomerRouteKey == key) return;
    _lastCustomerRouteKey = key;

    RouteService.getRoadRoute(start, end).then((points) {
      if (mounted && points.isNotEmpty) {
        setState(() {
          _liveCustomerRoadPoints = points;
        });
      }
    }).catchError((_) {});
  }

  @override
  void initState() {
    super.initState();
    _fetchFullOrderDetails();
    _pollLiveTracking();
    // Real-time polling every 3 seconds matching order_tracking.php
    _refreshTimer = Timer.periodic(const Duration(seconds: 3), (_) => _pollLiveTracking());
    
    // Ticker timer for smooth 1-second countdown
    _tickerTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted && _currentRemainingSeconds > 0) {
        setState(() {
          _currentRemainingSeconds--;
        });
        if (_currentRemainingSeconds <= 0) {
          _pollLiveTracking();
        }
      }
    });
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    _tickerTimer?.cancel();
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
          final driverMap = live['driver'] is Map ? (live['driver'] as Map) : {};
          final driverLat = double.tryParse(driverMap['lat']?.toString() ?? '');
          final driverLng = double.tryParse(driverMap['lng']?.toString() ?? '');

          if (driverLat != null && driverLng != null && driverLat != 0 && driverLng != 0) {
            final newDriverPos = LatLng(driverLat, driverLng);
            if (_previousDriverPos != null &&
                (_previousDriverPos!.latitude != newDriverPos.latitude || _previousDriverPos!.longitude != newDriverPos.longitude)) {
              final computedBearing = LocationService.calculateBearing(_previousDriverPos!, newDriverPos);
              if (computedBearing > 0) {
                _driverBearing = computedBearing;
              }
            }
            _previousDriverPos = newDriverPos;
            try {
              _mapController.move(newDriverPos, _mapController.camera.zoom);
            } catch (_) {}
          }

          setState(() {
            _liveData = live;
            _isLoading = false;
            if (live['remaining_seconds'] != null) {
              _currentRemainingSeconds = (live['remaining_seconds'] as num).toInt();
            }
          });

          // If order is completed or canceled, immediately stop polling to protect driver privacy
          final currentStatus = live['order_status']?.toString() ?? '';
          if (currentStatus == 'delivered' || currentStatus == 'canceled' || currentStatus == 'refunded' || currentStatus == 'failed') {
            _refreshTimer?.cancel();
            _tickerTimer?.cancel();
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
    final remainingSeconds = (_liveData != null ? _currentRemainingSeconds : ((live['remaining_seconds'] as num?)?.toInt() ?? 60)).clamp(0, 60);
    final otpCode = live['otp']?.toString() ?? order['otp']?.toString() ?? '----';
    final unreadChats = (live['unread_chats'] as num?)?.toInt() ?? 0;
    final cancellationReason = live['cancellation_reason']?.toString() ?? order['cancellation_reason']?.toString() ?? '';

    final driverMap = live['driver'] is Map ? (live['driver'] as Map) : {};
    final bool isDelivered = status == 'delivered';
    final bool isDriverValid = !isDelivered && (status != 'canceled') &&
        ((driverMap['assigned'] == true && driverMap['lat'] != null && driverMap['lng'] != null) ||
        (order['delivery_man_id'] != null && ['processing', 'handover', 'on_the_way'].contains(status)));

    final bool isCanceled = status == 'canceled' || (remainingSeconds <= 0 && !isDriverValid && !['processing', 'handover', 'on_the_way', 'delivered'].contains(status));
    final bool isUnpaidOnline = paymentMethod == 'midtrans' && paymentStatus != 'paid' && !isCanceled;

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
                _buildPaymentBreakdownCard(order, live),

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
          _buildPaymentBreakdownCard(order, live),

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

    final List batchStores = (batchInfo != null && batchInfo['stores'] is List && (batchInfo['stores'] as List).isNotEmpty)
        ? (batchInfo['stores'] as List)
        : (order['batch_stores'] is List && (order['batch_stores'] as List).isNotEmpty)
            ? (order['batch_stores'] as List)
            : [];

    if (isDelivered) {
      return SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            // 1. Delivered Header Card (Privacy Protected, No GPS Radar)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF065F46), Color(0xFF047857)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF047857).withValues(alpha: 0.25),
                    blurRadius: 16,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: const BoxDecoration(
                      color: Colors.white24,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.check_circle_rounded, color: Colors.white, size: 40),
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    'Pesanan Selesai Diantar! 🎉',
                    style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Terima kasih telah berbelanja di CicalengkaGO.',
                    style: TextStyle(color: Colors.white70, fontSize: 11.5),
                  ),
                  const SizedBox(height: 14),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: Colors.white24),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.shield_rounded, color: Color(0xFF86EFAC), size: 14),
                        SizedBox(width: 6),
                        Text(
                          'Radar Lokasi Dinonaktifkan (Privasi Kurir Terlindungi)',
                          style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // 2. Driver Profile Summary Card (Without Live GPS)
            if (driverName.isNotEmpty)
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(24),
                      child: CachedNetworkImage(
                        imageUrl: ApiConstants.formatImageUrl(driverAvatar),
                        width: 44,
                        height: 44,
                        fit: BoxFit.cover,
                        errorWidget: (context, url, error) => Container(
                          width: 44,
                          height: 44,
                          color: const Color(0xFFE2E8F0),
                          child: const Icon(Icons.motorcycle_rounded, color: Color(0xFF64748B), size: 22),
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
                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            '$vehicleType ${vehiclePlate.isNotEmpty ? "• $vehiclePlate" : ""}',
                            style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                          ),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFDCFCE7),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Text('Kurir Mitra', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF15803D))),
                    ),
                  ],
                ),
              ),
            const SizedBox(height: 16),

            // 3. Store & Purchased Items Breakdown
            _buildStoreAndItemsCard(order, live),
            const SizedBox(height: 16),

            // 4. Payment Breakdown Receipt
            _buildPaymentBreakdownCard(order, live),
            const SizedBox(height: 24),
          ],
        ),
      );
    }

    final bool isDeliveringToCustomer = (status == 'on_the_way');
    final LatLng driverPos = LatLng(driverLat, driverLng);
    final LatLng custPos = LatLng(custLat, custLng);
    final LatLng storePos = LatLng(storeLat, storeLng);

    if (isDriverValid) {
      final LatLng activeTarget = isDeliveringToCustomer ? custPos : storePos;
      _syncCustomerRoadRoute(driverPos, activeTarget);
    }

    final List<Polyline> polylines = [];
    if (isDriverValid) {
      final roadPoints = _liveCustomerRoadPoints.isNotEmpty
          ? _liveCustomerRoadPoints
          : [driverPos, (isDeliveringToCustomer ? custPos : storePos)];
      polylines.add(
        Polyline(
          points: roadPoints,
          strokeWidth: 4.8,
          color: isDeliveringToCustomer ? const Color(0xFF059669) : const Color(0xFF2563EB),
        ),
      );
    }

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
                    errorTileCallback: (tile, error, stackTrace) {
                      // Silent handling for aborted/cancelled tile requests
                    },
                    evictErrorTileStrategy: EvictErrorTileStrategy.none,
                  ),
                  // Turn-by-Turn Road Polylines
                  PolylineLayer(polylines: polylines),
                  MarkerLayer(
                    markers: [
                      // Store Pin (Multi-Store or Single Store)
                      if (batchStores.length > 1)
                        ...batchStores.asMap().entries.map((entry) {
                          final idx = entry.key;
                          final st = entry.value is Map ? (entry.value as Map) : {};
                          final sLat = double.tryParse(st['lat']?.toString() ?? '') ?? storeLat;
                          final sLng = double.tryParse(st['lng']?.toString() ?? '') ?? storeLng;
                          return Marker(
                            point: LatLng(sLat, sLng),
                            width: 44,
                            height: 44,
                            child: Stack(
                              alignment: Alignment.center,
                              children: [
                                Container(
                                  width: 38,
                                  height: 38,
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF16A34A),
                                    shape: BoxShape.circle,
                                    boxShadow: [BoxShadow(color: const Color(0xFF16A34A).withValues(alpha: 0.4), blurRadius: 10)],
                                  ),
                                  child: const Icon(Icons.storefront_rounded, color: Colors.white, size: 20),
                                ),
                                Positioned(
                                  top: 0,
                                  right: 0,
                                  child: Container(
                                    padding: const EdgeInsets.all(4),
                                    decoration: const BoxDecoration(color: AppTheme.primaryRed, shape: BoxShape.circle),
                                    child: Text('${idx + 1}', style: const TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.bold)),
                                  ),
                                ),
                              ],
                            ),
                          );
                        })
                      else
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
                      // Driver Pin with Real-time Moving Coordinate Aura
                      if (isDriverValid)
                        Marker(
                          point: LatLng(driverLat, driverLng),
                          width: 80,
                          height: 70,
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF1E293B),
                                  borderRadius: BorderRadius.circular(8),
                                  boxShadow: const [BoxShadow(color: Colors.black26, blurRadius: 4)],
                                ),
                                child: Text(
                                  driverName.isNotEmpty ? driverName : 'Kurir',
                                  style: const TextStyle(color: Colors.white, fontSize: 8.5, fontWeight: FontWeight.bold),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Stack(
                                alignment: Alignment.center,
                                children: [
                                  Container(
                                    width: 44,
                                    height: 44,
                                    decoration: BoxDecoration(
                                      color: const Color(0xFF2563EB).withValues(alpha: 0.2),
                                      shape: BoxShape.circle,
                                    ),
                                  ),
                                  Container(
                                    width: 36,
                                    height: 36,
                                    decoration: const BoxDecoration(
                                      color: Color(0xFF2563EB),
                                      shape: BoxShape.circle,
                                      boxShadow: [BoxShadow(color: Color(0x662563EB), blurRadius: 10)],
                                    ),
                                    child: Transform.rotate(
                                      angle: _driverBearing * (3.141592653589793 / 180.0),
                                      child: const Icon(Icons.navigation_rounded, color: Colors.white, size: 20),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),
                ],
              ),

              // Top Floating HUD (Live Radar Status & Dynamic Moving Coordinates)
              Positioned(
                top: 10,
                left: 12,
                right: 12,
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.8),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.radar_rounded, color: Color(0xFF4ADE80), size: 14),
                          const SizedBox(width: 6),
                          if (isDriverValid) ...[
                            Text(
                              '📍 ${driverLat.toStringAsFixed(6)}, ${driverLng.toStringAsFixed(6)}',
                              style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold, fontFamily: 'monospace'),
                            ),
                          ] else ...[
                            const Text('Live GPS Radar: Mencari Kurir...', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                          ],
                        ],
                      ),
                    ),
                    if (isDriverValid)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFFDCFCE7),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFF86EFAC)),
                        ),
                        child: const Row(
                          children: [
                            Icon(Icons.directions_bike_rounded, color: Color(0xFF16A34A), size: 12),
                            SizedBox(width: 4),
                            Text('Kurir Bergerak', style: TextStyle(color: Color(0xFF16A34A), fontSize: 9.5, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                  ],
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
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.04),
                            blurRadius: 10,
                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFFEF2F2),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: const Icon(Icons.shield_rounded, color: Color(0xFFDC2626), size: 20),
                                ),
                                const SizedBox(width: 10),
                                const Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'KODE OTP PENERIMAAN',
                                        style: TextStyle(
                                          color: Color(0xFF64748B),
                                          fontSize: 9.5,
                                          fontWeight: FontWeight.w800,
                                          letterSpacing: 0.5,
                                        ),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      SizedBox(height: 2),
                                      Text(
                                        'Berikan kode ini kepada kurir',
                                        style: TextStyle(
                                          color: Color(0xFF0F172A),
                                          fontSize: 11,
                                          fontWeight: FontWeight.w600,
                                        ),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            decoration: BoxDecoration(
                              color: const Color(0xFF0F172A),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              otpCode,
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w900,
                                color: Colors.white,
                                letterSpacing: 3,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                  ],

                  // Live Stage Log Timeline (Real-time progress progression)
                  _buildStageLogTimelineCard(
                    status: status,
                    isDriverValid: isDriverValid,
                    driverName: driverName,
                    storeName: (batchStores.isNotEmpty && batchStores[0] is Map ? (batchStores[0]['name'] ?? batchStores[0]['store_name']) : null) ?? 'Resto Mitra',
                    batchStores: batchStores,
                  ),

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
                                    Flexible(
                                      child: Text(
                                        driverName,
                                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
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
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              // Voice Call Button
                              IconButton(
                                constraints: const BoxConstraints(minWidth: 34, minHeight: 34),
                                padding: const EdgeInsets.all(6),
                                onPressed: () {
                                  GlobalCallService.instance.openCallScreen(
                                    context,
                                    orderCode: widget.orderCode,
                                    isIncoming: false,
                                    callerRole: 'customer',
                                  );
                                },
                                icon: const Icon(Icons.phone_in_talk_rounded, color: Color(0xFF2563EB), size: 18),
                              ),
                              // In-App Chat Button
                              Stack(
                                children: [
                                  IconButton(
                                    constraints: const BoxConstraints(minWidth: 34, minHeight: 34),
                                    padding: const EdgeInsets.all(6),
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
                                    icon: const Icon(Icons.chat_bubble_rounded, color: AppTheme.primaryRed, size: 18),
                                  ),
                                  if (unreadChats > 0)
                                    Positioned(
                                      right: 2,
                                      top: 2,
                                      child: Container(
                                        padding: const EdgeInsets.all(3),
                                        decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                                        child: Text('$unreadChats', style: const TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold)),
                                      ),
                                    ),
                                ],
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

                  // Detailed Payment Breakdown Card
                  _buildPaymentBreakdownCard(order, live),

                  // Customer Rating & Review Result Card
                  if (isDelivered) _buildReviewSection(order, live),

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
                          label: const Text('Rating & Ulasan', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
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

  Widget _buildSingleItemTile(Map item, {required String storeName, String? rawStoreLogo}) {
    final Map<String, dynamic> itemMap = Map<String, dynamic>.from(item);
    final name = (item['product_name'] ??
            item['name'] ??
            item['title'] ??
            item['item_name'] ??
            (item['product'] is Map ? item['product']['name'] : null) ??
            'Menu Kuliner')
        .toString();
    final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;
    final price = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
    final itemTotal = price * qty;
    final variantText = item['variant']?.toString() ??
        item['variation_name']?.toString() ??
        '';

    final rawImg = item['product_image'] ?? item['image'] ?? (item['product'] is Map ? item['product']['image'] : null);
    final imgUrl = rawImg != null && rawImg.toString().isNotEmpty
        ? ApiConstants.formatImageUrl(rawImg.toString())
        : null;

    final itemStore = item['store_name']?.toString() ?? storeName;

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFF1F5F9)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 4,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(12),
        child: InkWell(
          borderRadius: BorderRadius.circular(12),
          onTap: () => OrderItemDetailModal.show(
            context,
            itemMap,
            storeName: itemStore,
            storeLogo: rawStoreLogo,
          ),
          child: Padding(
            padding: const EdgeInsets.all(8),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: imgUrl != null
                      ? CachedNetworkImage(
                          imageUrl: imgUrl,
                          width: 44,
                          height: 44,
                          fit: BoxFit.cover,
                          errorWidget: (context, url, error) => Container(
                            width: 44,
                            height: 44,
                            color: const Color(0xFFF8FAFC),
                            child: const Icon(Icons.fastfood_rounded, color: Color(0xFF94A3B8), size: 20),
                          ),
                        )
                      : Container(
                          width: 44,
                          height: 44,
                          decoration: BoxDecoration(
                            color: const Color(0xFFFEF2F2),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Icon(Icons.restaurant_rounded, color: AppTheme.primaryRed, size: 20),
                        ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                            decoration: BoxDecoration(
                              color: AppTheme.inkBlack,
                              borderRadius: BorderRadius.circular(5),
                            ),
                            child: Text(
                              '${qty}x',
                              style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                            ),
                          ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              name,
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                      if (variantText.isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 2),
                          child: Text(
                            'Varian: $variantText',
                            style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                          ),
                        ),
                      if (itemStore.isNotEmpty && itemStore != storeName)
                        Padding(
                          padding: const EdgeInsets.only(top: 2),
                          child: Text(
                            'dari $itemStore',
                            style: const TextStyle(fontSize: 9.5, color: AppTheme.primaryRed, fontWeight: FontWeight.w600),
                          ),
                        ),
                    ],
                  ),
                ),
                const SizedBox(width: 6),
                if (price > 0)
                  Text(
                    CurrencyFormatter.formatRupiah(itemTotal),
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppTheme.inkBlack),
                  ),
                const SizedBox(width: 4),
                const Icon(Icons.chevron_right_rounded, size: 16, color: Color(0xFF94A3B8)),
              ],
            ),
          ),
        ),
      ),
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

    final Map<String, dynamic>? batchInfo = live['batch_info'] is Map
        ? Map<String, dynamic>.from(live['batch_info'] as Map)
        : null;

    final List batchStores = (batchInfo != null && batchInfo['stores'] is List && (batchInfo['stores'] as List).isNotEmpty)
        ? (batchInfo['stores'] as List)
        : (order['batch_stores'] is List && (order['batch_stores'] as List).isNotEmpty)
            ? (order['batch_stores'] as List)
            : [];

    final List batchSubOrders = (batchInfo != null && batchInfo['sub_orders'] is List && (batchInfo['sub_orders'] as List).isNotEmpty)
        ? (batchInfo['sub_orders'] as List)
        : (order['batch_sub_orders'] is List && (order['batch_sub_orders'] as List).isNotEmpty)
            ? (order['batch_sub_orders'] as List)
            : [];

    final bool isMultiStore = batchStores.length > 1 || (order['is_multi_store_batch'] == true);

    final List rawItems = (live['items'] is List && (live['items'] as List).isNotEmpty)
        ? (live['items'] as List)
        : (order['items'] is List && (order['items'] as List).isNotEmpty)
            ? (order['items'] as List)
            : (order['batch_sub_orders'] is List && (order['batch_sub_orders'] as List).isNotEmpty)
                ? (order['batch_sub_orders'] as List).expand((sub) => (sub['items'] as List? ?? [])).toList()
                : [];
    final orderNotes = (live['order_notes'] ?? order['order_notes'])?.toString() ?? '';
    final orderType = (live['order_type'] ?? order['order_type'])?.toString() ?? 'delivery';
    final parcelDetails = live['parcel_details'] is Map
        ? (live['parcel_details'] as Map)
        : (order['parcel_details'] is Map ? (order['parcel_details'] as Map) : {});

    final rawStoreLogo = liveStore['logo']?.toString() ??
        order['store_logo']?.toString() ??
        order['logo']?.toString();
    final storeLogoUrl = (rawStoreLogo != null && rawStoreLogo.isNotEmpty)
        ? ApiConstants.formatImageUrl(rawStoreLogo)
        : null;

    final isParcel = orderType == 'parcel' || parcelDetails.isNotEmpty;

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
          if (isMultiStore && batchStores.isNotEmpty) ...[
            Container(
              padding: const EdgeInsets.all(12),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
                border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Row(
                          children: [
                            const Icon(Icons.storefront_rounded, color: AppTheme.primaryRed, size: 18),
                            const SizedBox(width: 6),
                            Expanded(
                              child: Text(
                                'Titik Penjemputan (${batchStores.length} Toko)',
                                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEF3C7),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: const Color(0xFFFDE68A)),
                        ),
                        child: const Text(
                          'Multi-Toko',
                          style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: Color(0xFFB45309)),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  ...batchStores.asMap().entries.map((entry) {
                    final idx = entry.key;
                    final st = entry.value is Map ? (entry.value as Map) : {};
                    final sName = st['name']?.toString() ?? 'Toko Cicalengka';
                    final sAddress = st['address']?.toString() ?? 'Cicalengka, Bandung';
                    final sPhone = st['phone']?.toString() ?? '';

                    return Container(
                      margin: const EdgeInsets.only(bottom: 8),
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 24,
                            height: 24,
                            decoration: const BoxDecoration(
                              color: AppTheme.primaryRed,
                              shape: BoxShape.circle,
                            ),
                            child: Center(
                              child: Text(
                                '${idx + 1}',
                                style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  sName,
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  sAddress,
                                  style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ],
                            ),
                          ),
                          if (sPhone.isNotEmpty)
                            IconButton(
                              onPressed: () => launchUrl(Uri.parse('tel:$sPhone')),
                              icon: const Icon(Icons.call_rounded, color: Color(0xFF16A34A), size: 18),
                              padding: EdgeInsets.zero,
                              constraints: const BoxConstraints(),
                            ),
                        ],
                      ),
                    );
                  }),
                ],
              ),
            ),
          ] else ...[
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
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(
                      color: AppTheme.inkBlack,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: (!isParcel && storeLogoUrl != null)
                          ? CachedNetworkImage(
                              imageUrl: storeLogoUrl,
                              width: 38,
                              height: 38,
                              fit: BoxFit.cover,
                              errorWidget: (context, url, error) => Container(
                                color: AppTheme.inkBlack,
                                child: const Icon(Icons.storefront_rounded, color: Colors.white, size: 20),
                              ),
                            )
                          : Icon(isParcel ? Icons.local_shipping_rounded : Icons.storefront_rounded, color: Colors.white, size: 20),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isParcel ? (parcelDetails['pickup_address'] ?? 'Pengiriman Paket CicalengkaSend') : storeName,
                          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 2),
                        Text(
                          isParcel ? 'Kurir CicalengkaGO' : storeAddress,
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
          ],

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
                      isParcel ? 'RINCIAN PAKET' : 'RINCIAN ITEM (${rawItems.isEmpty ? 1 : rawItems.length})',
                      style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Color(0xFF64748B), letterSpacing: 0.5),
                    ),
                    Icon(isParcel ? Icons.inventory_2_outlined : Icons.shopping_bag_outlined, size: 14, color: const Color(0xFF64748B)),
                  ],
                ),
                const SizedBox(height: 8),

                if (isParcel) ...[
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                          decoration: BoxDecoration(color: AppTheme.inkBlack, borderRadius: BorderRadius.circular(6)),
                          child: const Text('1x', style: TextStyle(color: Colors.white, fontSize: 10.5, fontWeight: FontWeight.bold)),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Pengiriman Paket Kilat (${parcelDetails['item_type'] ?? parcelDetails['package_type'] ?? 'Barang'})',
                                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                              ),
                              if (parcelDetails['receiver_name'] != null)
                                Text(
                                  'Penerima: ${parcelDetails['receiver_name']} (${parcelDetails['receiver_phone'] ?? '-'})',
                                  style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ] else if (isMultiStore && batchSubOrders.isNotEmpty) ...[
                  ...batchSubOrders.map((subOrdMap) {
                    final subOrd = subOrdMap is Map ? subOrdMap : {};
                    final subStoreName = subOrd['store_name']?.toString() ?? 'Toko';
                    final subCode = subOrd['order_code']?.toString() ?? '';
                    final subItems = subOrd['items'] is List ? (subOrd['items'] as List) : [];

                    return Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Row(
                                  children: [
                                    const Icon(Icons.storefront_rounded, size: 14, color: AppTheme.primaryRed),
                                    const SizedBox(width: 4),
                                    Expanded(
                                      child: Text(
                                        subStoreName,
                                        style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              if (subCode.isNotEmpty) ...[
                                const SizedBox(width: 6),
                                Text('#$subCode', style: const TextStyle(fontSize: 9.5, color: Color(0xFF64748B), fontFamily: 'monospace')),
                              ],
                            ],
                          ),
                          const Divider(height: 12, color: Color(0xFFF1F5F9)),
                          ...subItems.map((item) {
                            final Map it = item is Map ? item : {};
                            return _buildSingleItemTile(it, storeName: subStoreName, rawStoreLogo: rawStoreLogo);
                          }),
                        ],
                      ),
                    );
                  })
                ] else if (rawItems.isEmpty) ...[
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4),
                    child: Text('1x Pesanan di $storeName', style: const TextStyle(fontSize: 12, color: AppTheme.inkBlack, fontWeight: FontWeight.w600)),
                  )
                ] else ...[
                  ...rawItems.map((item) {
                    final Map it = item is Map ? item : {};
                    return _buildSingleItemTile(it, storeName: storeName, rawStoreLogo: rawStoreLogo);
                  }),
                ],

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

  Widget _buildPaymentBreakdownCard(Map<String, dynamic> order, Map<String, dynamic> live) {
    final List rawItems = (live['items'] is List && (live['items'] as List).isNotEmpty)
        ? (live['items'] as List)
        : (order['items'] is List && (order['items'] as List).isNotEmpty)
            ? (order['items'] as List)
            : (order['batch_sub_orders'] is List && (order['batch_sub_orders'] as List).isNotEmpty)
                ? (order['batch_sub_orders'] as List).expand((sub) => (sub['items'] as List? ?? [])).toList()
                : [];

    double itemsSubtotal = 0.0;
    for (var item in rawItems) {
      if (item is Map) {
        final price = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
        final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;
        itemsSubtotal += price * qty;
      }
    }

    double deliveryCharge = double.tryParse(live['delivery_charge']?.toString() ?? order['delivery_charge']?.toString() ?? '0') ?? 0.0;
    final couponDiscount = double.tryParse(live['coupon_discount']?.toString() ?? order['coupon_discount']?.toString() ?? '0') ?? 0.0;
    final taxAmount = double.tryParse(live['tax_amount']?.toString() ?? order['tax_amount']?.toString() ?? '0') ?? 0.0;

    double totalAmount = double.tryParse(live['total_amount']?.toString() ?? order['total_amount']?.toString() ?? order['order_amount']?.toString() ?? '0') ?? 0.0;

    if (deliveryCharge == 0.0 && totalAmount > itemsSubtotal && itemsSubtotal > 0) {
      deliveryCharge = (totalAmount - itemsSubtotal + couponDiscount - taxAmount).clamp(0.0, double.infinity);
    }
    if (itemsSubtotal == 0.0 && totalAmount > 0.0) {
      itemsSubtotal = (totalAmount - deliveryCharge + couponDiscount - taxAmount).clamp(0.0, double.infinity);
    }
    if (totalAmount == 0.0) {
      totalAmount = itemsSubtotal + deliveryCharge + taxAmount - couponDiscount;
    }

    final paymentMethod = (live['payment_method'] ?? order['payment_method'] ?? 'cod').toString();
    final paymentStatus = (live['payment_status'] ?? order['payment_status'] ?? 'unpaid').toString();

    String methodLabel = 'Tunai (COD)';
    IconData methodIcon = Icons.payments_rounded;

    if (paymentMethod == 'wallet' || paymentMethod == 'cicalengkapay') {
      methodLabel = 'CicalengkaPay';
      methodIcon = Icons.account_balance_wallet_rounded;
    } else if (paymentMethod == 'midtrans' || paymentMethod == 'online' || paymentMethod == 'qris') {
      methodLabel = 'Midtrans QRIS / VA';
      methodIcon = Icons.qr_code_2_rounded;
    }

    final isPaid = paymentStatus == 'paid';

    return Container(
      margin: const EdgeInsets.only(top: 14),
      padding: const EdgeInsets.all(14),
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
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Expanded(
                child: Row(
                  children: [
                    Icon(Icons.receipt_long_rounded, size: 18, color: AppTheme.inkBlack),
                    SizedBox(width: 6),
                    Flexible(
                      child: Text(
                        'RINCIAN PEMBAYARAN',
                        style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w800, color: AppTheme.inkBlack, letterSpacing: 0.3),
                        maxLines: 1,
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
                    color: isPaid ? const Color(0xFFDCFCE7) : const Color(0xFFFEF3C7),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(methodIcon, size: 11, color: isPaid ? const Color(0xFF15803D) : const Color(0xFFB45309)),
                      const SizedBox(width: 4),
                      Flexible(
                        child: Text(
                          isPaid ? 'LUNAS ($methodLabel)' : 'BELUM DIBAYAR ($methodLabel)',
                          style: TextStyle(
                            fontSize: 9,
                            fontWeight: FontWeight.bold,
                            color: isPaid ? const Color(0xFF15803D) : const Color(0xFFB45309),
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          const SizedBox(height: 10),

          // Subtotal Items
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Subtotal Pesanan', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
              Text(CurrencyFormatter.formatRupiah(itemsSubtotal), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF1E293B))),
            ],
          ),
          const SizedBox(height: 6),

          // Delivery Fee
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Ongkos Kirim (Delivery)', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
              Text(CurrencyFormatter.formatRupiah(deliveryCharge), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF1E293B))),
            ],
          ),

          // Tax / Service Fee if > 0
          if (taxAmount > 0) ...[
            const SizedBox(height: 6),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Biaya Layanan / Pajak', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                Text(CurrencyFormatter.formatRupiah(taxAmount), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF1E293B))),
              ],
            ),
          ],

          // Coupon Discount if > 0
          if (couponDiscount > 0) ...[
            const SizedBox(height: 6),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Diskon Kupon Promo', style: TextStyle(fontSize: 12, color: Color(0xFF16A34A), fontWeight: FontWeight.w600)),
                Text('-${CurrencyFormatter.formatRupiah(couponDiscount)}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF16A34A))),
              ],
            ),
          ],

          const SizedBox(height: 10),
          const Divider(height: 1, color: Color(0xFFE2E8F0)),
          const SizedBox(height: 10),

          // Total Amount Row
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Total Pembayaran', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppTheme.inkBlack)),
              Text(
                CurrencyFormatter.formatRupiah(totalAmount),
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: AppTheme.primaryRed),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildReviewSection(Map<String, dynamic> order, Map<String, dynamic> live) {
    final Map? reviewInfo = (live['review_info'] is Map ? live['review_info'] : null) ??
        (order['review_info'] is Map ? order['review_info'] : null);
    final bool hasReviewed = reviewInfo != null && reviewInfo['has_reviewed'] == true;

    if (hasReviewed) {
      final storeReview = reviewInfo['store_review'] is Map ? (reviewInfo['store_review'] as Map) : null;
      final List storeReviews = reviewInfo['store_reviews'] is List ? (reviewInfo['store_reviews'] as List) : [];
      final dmReview = reviewInfo['dm_review'] is Map ? (reviewInfo['dm_review'] as Map) : null;

      return Container(
        margin: const EdgeInsets.only(top: 14),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFFFFFBEB),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFFDE68A)),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFFD97706).withValues(alpha: 0.06),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Row(
                  children: [
                    Icon(Icons.stars_rounded, color: Color(0xFFD97706), size: 20),
                    SizedBox(width: 8),
                    Text(
                      'Ulasan & Rating Anda',
                      style: TextStyle(
                        fontSize: 13.5,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF92400E),
                      ),
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFFD1FAE5),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFA7F3D0)),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.check_circle_rounded, color: Color(0xFF059669), size: 12),
                      SizedBox(width: 4),
                      Text(
                        'Sudah Diulas',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF059669),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Store Reviews
            if (storeReviews.isNotEmpty) ...[
              ...storeReviews.map((sr) {
                final sMap = sr is Map ? sr : {};
                final sName = sMap['store_name']?.toString() ?? 'Toko Mitra';
                final rating = int.tryParse(sMap['rating']?.toString() ?? '5') ?? 5;
                final comment = sMap['comment']?.toString() ?? '';

                return _buildSingleReviewCardItem(
                  icon: Icons.storefront_rounded,
                  iconColor: AppTheme.primaryRed,
                  title: sName,
                  rating: rating,
                  comment: comment,
                );
              }),
            ] else if (storeReview != null) ...[
              _buildSingleReviewCardItem(
                icon: Icons.storefront_rounded,
                iconColor: AppTheme.primaryRed,
                title: storeReview['store_name']?.toString() ?? order['store_name']?.toString() ?? 'Toko Mitra',
                rating: int.tryParse(storeReview['rating']?.toString() ?? '5') ?? 5,
                comment: storeReview['comment']?.toString() ?? '',
              ),
            ],

            // Courier / Driver Review
            if (dmReview != null) ...[
              const SizedBox(height: 8),
              _buildSingleReviewCardItem(
                icon: Icons.two_wheeler_rounded,
                iconColor: const Color(0xFF2563EB),
                title: 'Kurir: ${dmReview['dm_name'] ?? order['dm_name'] ?? 'Mitra Kurir CicalengkaGO'}',
                rating: int.tryParse(dmReview['rating']?.toString() ?? '5') ?? 5,
                comment: dmReview['comment']?.toString() ?? '',
              ),
            ],

            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFF92400E),
                  side: const BorderSide(color: Color(0xFFFCD34D)),
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  backgroundColor: Colors.white,
                ),
                onPressed: () => _showReviewDialog(context, order),
                icon: const Icon(Icons.edit_note_rounded, size: 16),
                label: const Text(
                  'Ubah Rating & Ulasan',
                  style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ],
        ),
      );
    }

    // If not reviewed yet
    return Container(
      margin: const EdgeInsets.only(top: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFFDE68A)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: const BoxDecoration(
              color: Color(0xFFFEF3C7),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.star_rounded, color: Color(0xFFD97706), size: 24),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Bagaimana Pesanan Anda?',
                  style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF92400E),
                  ),
                ),
                const SizedBox(height: 2),
                const Text(
                  'Beri rating & ulasan untuk toko dan kurir pengantar.',
                  style: TextStyle(fontSize: 10.5, color: Color(0xFFB45309)),
                ),
                const SizedBox(height: 8),
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFD97706),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: () => _showReviewDialog(context, order),
                  icon: const Icon(Icons.rate_review_rounded, size: 14),
                  label: const Text(
                    'Beri Rating Sekarang',
                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSingleReviewCardItem({
    required IconData icon,
    required Color iconColor,
    required String title,
    required int rating,
    required String comment,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFF1F5F9)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 15, color: iconColor),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              Row(
                children: List.generate(5, (i) => Icon(
                  i < rating ? Icons.star_rounded : Icons.star_outline_rounded,
                  color: Colors.amber,
                  size: 16,
                )),
              ),
              const SizedBox(width: 4),
              Text(
                '$rating.0',
                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w900, color: Color(0xFFD97706)),
              ),
            ],
          ),
          if (comment.trim().isNotEmpty) ...[
            const SizedBox(height: 6),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                '“$comment”',
                style: const TextStyle(fontSize: 11, fontStyle: FontStyle.italic, color: Color(0xFF475569)),
              ),
            ),
          ],
        ],
      ),
    );
  }

  void _showReviewDialog(BuildContext context, Map<String, dynamic> order) {
    final Map? reviewInfo = (_liveData != null && _liveData!['review_info'] is Map ? _liveData!['review_info'] : null) ??
        (_orderData != null && _orderData!['review_info'] is Map ? _orderData!['review_info'] : null) ??
        (order['review_info'] is Map ? order['review_info'] : null);

    final List batchStores = (order['batch_stores'] is List && (order['batch_stores'] as List).isNotEmpty)
        ? (order['batch_stores'] as List)
        : (_liveData != null && _liveData!['batch_info'] is Map && _liveData!['batch_info']['stores'] is List)
            ? (_liveData!['batch_info']['stores'] as List)
            : [];

    final bool isMulti = batchStores.length > 1 || (order['is_multi_store_batch'] == true);

    // Initial values from existing review
    final existingStoreReview = reviewInfo != null && reviewInfo['store_review'] is Map ? (reviewInfo['store_review'] as Map) : null;
    final List existingStoreReviews = reviewInfo != null && reviewInfo['store_reviews'] is List ? (reviewInfo['store_reviews'] as List) : [];
    final existingDmReview = reviewInfo != null && reviewInfo['dm_review'] is Map ? (reviewInfo['dm_review'] as Map) : null;

    int singleStoreRating = existingStoreReview != null ? (int.tryParse(existingStoreReview['rating']?.toString() ?? '5') ?? 5) : 5;
    final singleStoreCommentCtrl = TextEditingController(text: existingStoreReview?['comment']?.toString() ?? '');

    final Map<int, int> multiRatings = {};
    final Map<int, TextEditingController> multiComments = {};

    if (isMulti) {
      for (var st in batchStores) {
        if (st is Map) {
          final sId = int.tryParse(st['store_id']?.toString() ?? '0') ?? 0;
          if (sId > 0) {
            final matchRev = existingStoreReviews.firstWhere(
              (r) => r is Map && int.tryParse(r['store_id']?.toString() ?? '0') == sId,
              orElse: () => null,
            );
            multiRatings[sId] = matchRev != null ? (int.tryParse(matchRev['rating']?.toString() ?? '5') ?? 5) : 5;
            multiComments[sId] = TextEditingController(text: matchRev?['comment']?.toString() ?? '');
          }
        }
      }
    }

    int driverRating = existingDmReview != null ? (int.tryParse(existingDmReview['rating']?.toString() ?? '5') ?? 5) : 5;
    final driverCommentCtrl = TextEditingController(text: existingDmReview?['comment']?.toString() ?? '');

    final driverName = (existingDmReview != null && existingDmReview['dm_name'] != null)
        ? existingDmReview['dm_name'].toString()
        : (_liveData != null && _liveData!['driver'] is Map && _liveData!['driver']['name'] != null && _liveData!['driver']['name'] != 'Mencari Kurir...')
            ? _liveData!['driver']['name'].toString()
            : (order['dm_name']?.toString() ?? 'Mitra Kurir CicalengkaGO');

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

                if (isMulti && batchStores.isNotEmpty) ...[
                  ...batchStores.map((st) {
                    final Map sMap = st is Map ? st : {};
                    final sId = int.tryParse(sMap['store_id']?.toString() ?? '0') ?? 0;
                    final sName = sMap['name']?.toString() ?? 'Toko Mitra';
                    final currentRating = multiRatings[sId] ?? 5;
                    final ctrl = multiComments[sId] ?? TextEditingController();

                    return Container(
                      margin: const EdgeInsets.only(bottom: 16),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Icon(Icons.storefront_rounded, size: 16, color: AppTheme.primaryRed),
                              const SizedBox(width: 6),
                              Text(sName, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppTheme.inkBlack)),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: List.generate(5, (i) => GestureDetector(
                              onTap: () => setModalState(() => multiRatings[sId] = i + 1),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(horizontal: 3),
                                child: Icon(
                                  i < currentRating ? Icons.star_rounded : Icons.star_outline_rounded,
                                  color: Colors.amber,
                                  size: 30,
                                ),
                              ),
                            )),
                          ),
                          const SizedBox(height: 8),
                          TextField(
                            controller: ctrl,
                            maxLines: 2,
                            decoration: InputDecoration(
                              hintText: 'Ulasan untuk $sName...',
                              hintStyle: const TextStyle(fontSize: 11.5),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                              contentPadding: const EdgeInsets.all(10),
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                ] else ...[
                  const Text('Rating Toko & Makanan', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(5, (i) => GestureDetector(
                      onTap: () => setModalState(() => singleStoreRating = i + 1),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 4),
                        child: Icon(
                          i < singleStoreRating ? Icons.star_rounded : Icons.star_outline_rounded,
                          color: Colors.amber,
                          size: 34,
                        ),
                      ),
                    )),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: singleStoreCommentCtrl,
                    maxLines: 2,
                    decoration: InputDecoration(
                      hintText: 'Tulis ulasan makanan & toko...',
                      hintStyle: const TextStyle(fontSize: 12),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                      contentPadding: const EdgeInsets.all(12),
                    ),
                  ),
                ],

                // Driver Review
                const SizedBox(height: 16),
                const Divider(height: 1, color: Color(0xFFE2E8F0)),
                const SizedBox(height: 14),
                Row(
                  children: [
                    const Icon(Icons.two_wheeler_rounded, size: 18, color: Color(0xFF2563EB)),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        'Rating Kurir ($driverName)',
                        style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: List.generate(5, (i) => GestureDetector(
                    onTap: () => setModalState(() => driverRating = i + 1),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                      child: Icon(
                        i < driverRating ? Icons.star_rounded : Icons.star_outline_rounded,
                        color: Colors.amber,
                        size: 34,
                      ),
                    ),
                  )),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: driverCommentCtrl,
                  maxLines: 2,
                  decoration: InputDecoration(
                    hintText: 'Tulis ulasan pelayanan kurir...',
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
                    Map<String, String> bodyPayload = {
                      'order_code': widget.orderCode,
                      'dm_rating': driverRating.toString(),
                      'dm_comment': driverCommentCtrl.text,
                    };

                    if (isMulti && multiRatings.isNotEmpty) {
                      final multiReviews = batchStores.map((st) {
                        final Map sMap = st is Map ? st : {};
                        final sId = int.tryParse(sMap['store_id']?.toString() ?? '0') ?? 0;
                        final oId = int.tryParse(sMap['order_id']?.toString() ?? '0') ?? int.tryParse(order['id']?.toString() ?? '0') ?? 0;
                        return {
                          'store_id': sId,
                          'order_id': oId,
                          'rating': multiRatings[sId] ?? 5,
                          'comment': multiComments[sId]?.text ?? '',
                        };
                      }).toList();
                      bodyPayload['multi_store_reviews'] = jsonEncode(multiReviews);
                    } else {
                      bodyPayload['rating'] = singleStoreRating.toString();
                      bodyPayload['comment'] = singleStoreCommentCtrl.text;
                    }

                    final res = await ApiService.postForm('${ApiConstants.orders}/review', bodyPayload);
                    if (res['success'] == true && res['data'] != null && res['data']['review_info'] != null) {
                      if (mounted) {
                        setState(() {
                          if (_liveData != null) {
                            _liveData!['review_info'] = res['data']['review_info'];
                          }
                          if (_orderData != null) {
                            _orderData!['review_info'] = res['data']['review_info'];
                          }
                        });
                      }
                    }
                    _fetchFullOrderDetails();
                    _pollLiveTracking();

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

  Widget _buildStageLogTimelineCard({
    required String status,
    required bool isDriverValid,
    required String driverName,
    required String storeName,
    required List batchStores,
  }) {
    // Determine completed and active steps
    final bool isDelivered = (status == 'delivered');
    final bool isOnTheWay = (status == 'on_the_way') || isDelivered;
    final bool isPickedUp = (status == 'picked_up') || (status == 'handover') || isOnTheWay;
    final bool isDriverAssigned = isDriverValid || isPickedUp;
    final bool isMulti = batchStores.length > 1;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: const BoxDecoration(
                      color: Color(0xFFEFF6FF),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.alt_route_rounded, color: Color(0xFF2563EB), size: 16),
                  ),
                  const SizedBox(width: 8),
                  const Text(
                    'Tahapan Perjalanan Pesanan',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
              if (isMulti)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEF3C7),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFFDE68A)),
                  ),
                  child: Text(
                    'Multi-Toko (${batchStores.length} Resto)',
                    style: const TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: Color(0xFFB45309)),
                  ),
                ),
            ],
          ),
          const Divider(height: 18, color: Color(0xFFF1F5F9)),

          // 1. Render Each Store Step in Order
          if (isMulti) ...[
            for (int i = 0; i < batchStores.length; i++) ...[
              Builder(builder: (context) {
                final st = batchStores[i] is Map ? (batchStores[i] as Map) : {};
                final String sName = (st['name'] ?? st['store_name'] ?? 'Toko ${i + 1}').toString();
                final String sAddr = (st['address'] ?? st['store_address'] ?? 'Cicalengka').toString();
                final bool stepFinished = isPickedUp;
                final bool stepActive = isDriverAssigned && !isPickedUp && (i == 0);

                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _timelineStepItem(
                      isCompleted: stepFinished,
                      isActive: stepActive,
                      stepNum: '${i + 1}',
                      title: stepFinished
                          ? 'Toko ${i + 1}: $sName (Selesai Diambil ✅)'
                          : (stepActive
                              ? 'Toko ${i + 1}: $sName (Kurir Sedang Menjemput 📍)'
                              : 'Toko ${i + 1}: $sName (Menunggu Penjemputan)'),
                      desc: sAddr,
                      icon: Icons.storefront_rounded,
                    ),
                    _timelineConnector(isCompleted: stepFinished),
                  ],
                );
              }),
            ],
          ] else ...[
            _timelineStepItem(
              isCompleted: isPickedUp,
              isActive: isDriverAssigned && !isPickedUp,
              stepNum: '1',
              title: isPickedUp
                  ? 'Pesanan Selesai Diambil Kurir ✅'
                  : (isDriverAssigned ? 'Kurir Sedang Menuju ke Resto 📍' : 'Resto Menyiapkan Pesanan'),
              desc: storeName.isNotEmpty ? storeName : 'Resto Mitra Cicalengka',
              icon: Icons.storefront_rounded,
            ),
            _timelineConnector(isCompleted: isPickedUp),
          ],

          // 2. Final Customer Destination Delivery Step
          _timelineStepItem(
            isCompleted: isDelivered,
            isActive: isOnTheWay && !isDelivered,
            stepNum: isMulti ? '${batchStores.length + 1}' : '2',
            title: isDelivered
                ? 'Telah Sampai di Lokasi Anda ✅'
                : (isOnTheWay ? 'Kurir Sedang Menuju ke Alamat Anda 🛵' : 'Pengantaran ke Alamat Rumah Anda'),
            desc: isOnTheWay
                ? 'Rute navigasi aktif langsung menuju ke rumah Anda'
                : 'Menunggu semua pesanan selesai diambil kurir',
            icon: Icons.home_rounded,
          ),
        ],
      ),
    );
  }

  Widget _timelineStepItem({
    required bool isCompleted,
    required bool isActive,
    required String stepNum,
    required String title,
    required String desc,
    required IconData icon,
  }) {
    final Color badgeColor = isCompleted
        ? const Color(0xFF16A34A)
        : (isActive ? const Color(0xFF2563EB) : const Color(0xFF94A3B8));

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 28,
          height: 28,
          decoration: BoxDecoration(
            color: isCompleted
                ? const Color(0xFFDCFCE7)
                : (isActive ? const Color(0xFFEFF6FF) : const Color(0xFFF1F5F9)),
            shape: BoxShape.circle,
            border: Border.all(
              color: badgeColor,
              width: isActive ? 2 : 1,
            ),
          ),
          child: Center(
            child: isCompleted
                ? const Icon(Icons.check_rounded, color: Color(0xFF16A34A), size: 16)
                : Icon(icon, color: badgeColor, size: 14),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(
                  fontSize: 11.5,
                  fontWeight: isActive || isCompleted ? FontWeight.bold : FontWeight.w600,
                  color: isCompleted
                      ? const Color(0xFF0F172A)
                      : (isActive ? const Color(0xFF2563EB) : const Color(0xFF64748B)),
                ),
              ),
              const SizedBox(height: 1),
              Text(
                desc,
                style: const TextStyle(fontSize: 10, color: Color(0xFF64748B)),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _timelineConnector({required bool isCompleted}) {
    return Container(
      margin: const EdgeInsets.only(left: 13, top: 2, bottom: 2),
      height: 16,
      width: 2,
      color: isCompleted ? const Color(0xFF16A34A) : const Color(0xFFE2E8F0),
    );
  }
}
