import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/customer_controller.dart';
import 'package:provider/provider.dart';

class OrderTrackingScreen extends StatefulWidget {
  final String orderCode;
  const OrderTrackingScreen({super.key, required this.orderCode});

  @override
  State<OrderTrackingScreen> createState() => _OrderTrackingScreenState();
}

class _OrderTrackingScreenState extends State<OrderTrackingScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _orderData;
  Timer? _refreshTimer;

  final List<Map<String, dynamic>> _statusSteps = [
    {'key': 'pending', 'label': 'Pesanan Diterima', 'icon': Icons.receipt_rounded},
    {'key': 'confirmed', 'label': 'Dikonfirmasi', 'icon': Icons.check_circle_rounded},
    {'key': 'processing', 'label': 'Diproses Resto', 'icon': Icons.restaurant_rounded},
    {'key': 'handover', 'label': 'Diserahkan ke Kurir', 'icon': Icons.handshake_rounded},
    {'key': 'on_the_way', 'label': 'Sedang Diantar', 'icon': Icons.delivery_dining_rounded},
    {'key': 'delivered', 'label': 'Pesanan Tiba', 'icon': Icons.task_alt_rounded},
  ];

  @override
  void initState() {
    super.initState();
    _fetchTracking();
    _refreshTimer = Timer.periodic(const Duration(seconds: 5), (_) => _fetchTracking(silent: true));
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _fetchTracking({bool silent = false}) async {
    if (!silent) setState(() => _isLoading = true);
    try {
      // Try tracking endpoint first, fallback to order detail
      final res = await ApiService.get('${ApiConstants.orders}/${widget.orderCode}');
      if (res['success'] == true && res['data'] != null) {
        setState(() {
          _orderData = res['data'] as Map<String, dynamic>;
          _isLoading = false;
        });
      } else {
        setState(() => _isLoading = false);
      }
    } catch (_) {
      setState(() => _isLoading = false);
    }
  }

  int _getCurrentStepIndex(String status) {
    for (int i = 0; i < _statusSteps.length; i++) {
      if (_statusSteps[i]['key'] == status) return i;
    }
    if (['picked_up'].contains(status)) return 3;
    return 0;
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        backgroundColor: const Color(0xFFF8FAFC),
        appBar: AppBar(
          title: const Text('Lacak Pesanan'),
          backgroundColor: Colors.white,
          foregroundColor: const Color(0xFF0F172A),
        ),
        body: const Center(child: CircularProgressIndicator(color: AppTheme.primaryRed)),
      );
    }

    final order = _orderData ?? {};
    final status = order['order_status'] ?? 'pending';
    final currentStep = _getCurrentStepIndex(status);
    final isDelivered = status == 'delivered';

    // Map positions
    final driverLat = double.tryParse(order['driver_lat']?.toString() ?? '') ?? -6.9840;
    final driverLng = double.tryParse(order['driver_lng']?.toString() ?? '') ?? 107.8340;
    final custLat = double.tryParse(order['delivery_lat']?.toString() ?? '') ?? -6.9855;
    final custLng = double.tryParse(order['delivery_lng']?.toString() ?? '') ?? 107.8350;
    final LatLng mapCenter = LatLng((driverLat + custLat) / 2, (driverLng + custLng) / 2);

    final driverName = order['delivery_man_name'] ?? 'Kurir CicalengkaGO';
    final driverPhone = order['delivery_man_phone'] ?? '';
    final driverRating = double.tryParse(order['delivery_man_rating']?.toString() ?? '5.0') ?? 5.0;
    final totalAmount = double.tryParse(order['total_amount']?.toString() ?? '0') ?? 0;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: Column(
        children: [
          // Map (expandable)
          Expanded(
            flex: 4,
            child: Stack(
              children: [
                FlutterMap(
                  options: MapOptions(
                    initialCenter: mapCenter,
                    initialZoom: 14.5,
                  ),
                  children: [
                    TileLayer(
                      urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                      userAgentPackageName: 'com.cicalengkago.mobile',
                    ),
                    MarkerLayer(
                      markers: [
                        // Customer pin
                        Marker(
                          point: LatLng(custLat, custLng),
                          width: 44,
                          height: 44,
                          child: Container(
                            decoration: BoxDecoration(
                              color: AppTheme.primaryRed,
                              shape: BoxShape.circle,
                              boxShadow: [BoxShadow(color: AppTheme.primaryRed.withOpacity(0.4), blurRadius: 12)],
                            ),
                            child: const Icon(Icons.home_rounded, color: Colors.white, size: 22),
                          ),
                        ),
                        // Driver pin (only show if driver is assigned)
                        if (order['delivery_man_id'] != null)
                          Marker(
                            point: LatLng(driverLat, driverLng),
                            width: 44,
                            height: 44,
                            child: Container(
                              decoration: const BoxDecoration(
                                color: Color(0xFF2563EB),
                                shape: BoxShape.circle,
                                boxShadow: [BoxShadow(color: Color(0x662563EB), blurRadius: 12)],
                              ),
                              child: const Icon(Icons.delivery_dining_rounded, color: Colors.white, size: 22),
                            ),
                          ),
                      ],
                    ),
                  ],
                ),

                // Back button
                Positioned(
                  top: MediaQuery.of(context).padding.top + 8,
                  left: 12,
                  child: GestureDetector(
                    onTap: () => Navigator.pop(context),
                    child: Container(
                      width: 38,
                      height: 38,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.15), blurRadius: 8)],
                      ),
                      child: const Icon(Icons.arrow_back_rounded, size: 20, color: Color(0xFF0F172A)),
                    ),
                  ),
                ),

                // Order code badge top right
                Positioned(
                  top: MediaQuery.of(context).padding.top + 8,
                  right: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 8)],
                    ),
                    child: Text(
                      '#${widget.orderCode}',
                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.primaryRed, fontFamily: 'monospace'),
                    ),
                  ),
                ),
              ],
            ),
          ),

          // Details card (scrollable)
          Expanded(
            flex: 6,
            child: Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 10, offset: Offset(0, -4))],
              ),
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(16, 20, 16, 24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Status stepper
                    _buildStatusStepper(currentStep, isDelivered),

                    const SizedBox(height: 20),
                    const Divider(height: 1, color: Color(0xFFF1F5F9)),
                    const SizedBox(height: 16),

                    // Driver info (only if assigned)
                    if (order['delivery_man_id'] != null) ...[
                      Row(
                        children: [
                          Container(
                            width: 48,
                            height: 48,
                            decoration: BoxDecoration(
                              color: const Color(0xFFF1F5F9),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.motorcycle_rounded, color: AppTheme.darkSlate, size: 24),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  driverName,
                                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                                ),
                                Row(
                                  children: [
                                    const Icon(Icons.star_rounded, color: Colors.amber, size: 14),
                                    const SizedBox(width: 3),
                                    Text(
                                      driverRating.toStringAsFixed(1),
                                      style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                                    ),
                                    const Text(' • Kurir Terverifikasi', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          if (driverPhone.isNotEmpty) ...[
                            _iconBtn(
                              Icons.chat_bubble_outline_rounded,
                              const Color(0xFF2563EB),
                              () {},
                            ),
                            const SizedBox(width: 8),
                            _iconBtn(
                              Icons.call_rounded,
                              const Color(0xFF059669),
                              () => launchUrl(Uri.parse('tel:$driverPhone')),
                            ),
                          ],
                        ],
                      ),

                      const SizedBox(height: 16),
                      const Divider(height: 1, color: Color(0xFFF1F5F9)),
                      const SizedBox(height: 16),
                    ],

                    // Order summary
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Total Pembayaran', style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600)),
                            Text(
                              CurrencyFormatter.formatRupiah(totalAmount),
                              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                            ),
                          ],
                        ),
                        if (isDelivered)
                          GestureDetector(
                            onTap: () => _showReviewDialog(context, order),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
                              decoration: BoxDecoration(
                                gradient: const LinearGradient(colors: [Color(0xFFEE2737), Color(0xFFC61524)]),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: const Row(
                                children: [
                                  Icon(Icons.star_rounded, color: Colors.amber, size: 16),
                                  SizedBox(width: 6),
                                  Text('Beri Rating', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
                                ],
                              ),
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatusStepper(int currentStep, bool isDelivered) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          isDelivered ? '✅ Pesanan Selesai!' : '🚀 Pesanan Dalam Proses',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: isDelivered ? const Color(0xFF059669) : const Color(0xFF0F172A),
          ),
        ),
        const SizedBox(height: 16),
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
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      color: isDone
                          ? (isActive ? AppTheme.primaryRed : const Color(0xFF059669))
                          : const Color(0xFFF1F5F9),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      step['icon'] as IconData,
                      color: isDone ? Colors.white : const Color(0xFFCBD5E1),
                      size: 16,
                    ),
                  ),
                  if (i < _statusSteps.length - 1)
                    Container(
                      width: 2,
                      height: 24,
                      color: i < currentStep ? const Color(0xFF059669) : const Color(0xFFF1F5F9),
                    ),
                ],
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.only(top: 6),
                  child: Text(
                    step['label'] as String,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: isActive ? FontWeight.bold : FontWeight.normal,
                      color: isDone ? (isActive ? AppTheme.primaryRed : const Color(0xFF059669)) : const Color(0xFFCBD5E1),
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
    int driverRating = 5;
    final storeCommentCtrl = TextEditingController();
    final driverCommentCtrl = TextEditingController();
    final hasDriver = order['delivery_man_id'] != null;

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
                const Text('⭐ Beri Rating & Ulasan', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                const SizedBox(height: 4),
                Text('Pesanan #${order['order_code']}', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                const SizedBox(height: 20),

                // Store rating
                const Text('Rating Toko & Makanan', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
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
                        size: 36,
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

                if (hasDriver) ...[
                  const SizedBox(height: 16),
                  const Text('Rating Kurir & Pengantaran', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
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
                          size: 36,
                        ),
                      ),
                    )),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: driverCommentCtrl,
                    maxLines: 2,
                    decoration: InputDecoration(
                      hintText: 'Tulis ulasan kurir (opsional)...',
                      hintStyle: const TextStyle(fontSize: 12),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                      contentPadding: const EdgeInsets.all(12),
                    ),
                  ),
                ],

                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primaryRed,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    onPressed: () async {
                      Navigator.pop(ctx);
                      final ctrl = context.read<CustomerController>();
                      await ctrl.submitOrderReview(
                        orderId: int.tryParse(order['id']?.toString() ?? '0') ?? 0,
                        orderCode: order['order_code'] ?? '',
                        storeRating: storeRating,
                        storeComment: storeCommentCtrl.text,
                        driverRating: hasDriver ? driverRating : null,
                        driverComment: hasDriver ? driverCommentCtrl.text : null,
                      );
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Terima kasih atas ulasan Anda! 🎉'), backgroundColor: Colors.green),
                        );
                      }
                    },
                    child: const Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.send_rounded, size: 16),
                        SizedBox(width: 8),
                        Text('Kirim Penilaian', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _iconBtn(IconData icon, Color color, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 40,
        height: 40,
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          shape: BoxShape.circle,
          border: Border.all(color: color.withOpacity(0.2)),
        ),
        child: Icon(icon, color: color, size: 20),
      ),
    );
  }
}
