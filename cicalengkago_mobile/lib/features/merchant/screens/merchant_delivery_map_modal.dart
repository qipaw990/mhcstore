import 'dart:convert';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:http/http.dart' as http;
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/services/global_call_service.dart';
import '../../auth/controllers/auth_controller.dart';
import '../../common/screens/in_app_chat_modal.dart';
import '../controllers/merchant_controller.dart';

class MerchantDeliveryMapModal extends StatefulWidget {
  final Map<String, dynamic> order;

  const MerchantDeliveryMapModal({
    super.key,
    required this.order,
  });

  static Future<void> show(BuildContext context, Map<String, dynamic> order) {
    return Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => MerchantDeliveryMapModal(order: order),
      ),
    );
  }

  @override
  State<MerchantDeliveryMapModal> createState() => _MerchantDeliveryMapModalState();
}

class _MerchantDeliveryMapModalState extends State<MerchantDeliveryMapModal> {
  late final MapController _mapController;
  List<LatLng> _routePoints = [];
  bool _isLoadingRoute = true;
  double _distanceMeters = 0;
  int _etaMinutes = 1;
  bool _isDelivering = false;

  @override
  void initState() {
    super.initState();
    _mapController = MapController();
    _fetchRoadRoute();
  }

  LatLng _getStorePosition() {
    final sLat = double.tryParse(widget.order['store_lat']?.toString() ?? '') ??
        double.tryParse(widget.order['store_latitude']?.toString() ?? '') ??
        -6.9835;
    final sLng = double.tryParse(widget.order['store_lng']?.toString() ?? '') ??
        double.tryParse(widget.order['store_longitude']?.toString() ?? '') ??
        107.8335;
    return LatLng(sLat, sLng);
  }

  LatLng _getCustomerPosition() {
    final delivAddr = widget.order['delivery_address'] is Map ? widget.order['delivery_address'] as Map : {};
    final cLat = double.tryParse(delivAddr['lat']?.toString() ?? '') ??
        double.tryParse(delivAddr['latitude']?.toString() ?? '') ??
        double.tryParse(widget.order['customer_lat']?.toString() ?? '') ??
        -6.9845;
    final cLng = double.tryParse(delivAddr['lng']?.toString() ?? '') ??
        double.tryParse(delivAddr['longitude']?.toString() ?? '') ??
        double.tryParse(widget.order['customer_lng']?.toString() ?? '') ??
        107.8345;
    return LatLng(cLat, cLng);
  }

  Future<void> _fetchRoadRoute() async {
    final start = _getStorePosition();
    final end = _getCustomerPosition();

    final straightDistMeters = const Distance().as(LengthUnit.Meter, start, end);
    _distanceMeters = straightDistMeters;
    _etaMinutes = math.max(1, (straightDistMeters / 80).round()); // ~80m per minute walking/riding

    try {
      final url = Uri.parse(
        'https://router.project-osrm.org/route/v1/driving/'
        '${start.longitude},${start.latitude};${end.longitude},${end.latitude}'
        '?overview=full&geometries=geojson',
      );

      final response = await http.get(url).timeout(const Duration(seconds: 5));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['routes'] != null && (data['routes'] as List).isNotEmpty) {
          final route = data['routes'][0];
          final coordinates = route['geometry']['coordinates'] as List;
          final parsedRoute = coordinates.map((c) => LatLng(c[1] as double, c[0] as double)).toList();

          if (mounted) {
            setState(() {
              _routePoints = parsedRoute;
              _distanceMeters = double.tryParse(route['distance']?.toString() ?? '') ?? straightDistMeters;
              _etaMinutes = math.max(1, ((_distanceMeters / 1000) * 3 + 1).round());
              _isLoadingRoute = false;
            });
            _fitMapBounds();
            return;
          }
        }
      }
    } catch (_) {}

    // Fallback straight line
    if (mounted) {
      setState(() {
        _routePoints = [start, end];
        _isLoadingRoute = false;
      });
      _fitMapBounds();
    }
  }

  void _fitMapBounds() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final start = _getStorePosition();
      final end = _getCustomerPosition();
      final minLat = math.min(start.latitude, end.latitude);
      final maxLat = math.max(start.latitude, end.latitude);
      final minLng = math.min(start.longitude, end.longitude);
      final maxLng = math.max(start.longitude, end.longitude);

      try {
        _mapController.fitCamera(
          CameraFit.bounds(
            bounds: LatLngBounds(LatLng(minLat, minLng), LatLng(maxLat, maxLng)),
            padding: const EdgeInsets.fromLTRB(40, 100, 40, 320),
          ),
        );
      } catch (_) {}
    });
  }

  Future<void> _openGoogleMapsNav() async {
    final dest = _getCustomerPosition();
    final googleNavUrl = Uri.parse('google.navigation:q=${dest.latitude},${dest.longitude}&mode=d');
    final webMapsUrl = Uri.parse('https://www.google.com/maps/dir/?api=1&destination=${dest.latitude},${dest.longitude}&travelmode=driving');

    try {
      if (await canLaunchUrl(googleNavUrl)) {
        await launchUrl(googleNavUrl, mode: LaunchMode.externalApplication);
      } else if (await canLaunchUrl(webMapsUrl)) {
        await launchUrl(webMapsUrl, mode: LaunchMode.externalApplication);
      }
    } catch (_) {}
  }

  Future<void> _callCustomer(String phone) async {
    if (phone.isEmpty || phone == '-') return;
    final uri = Uri.parse('tel:$phone');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  @override
  Widget build(BuildContext context) {
    final order = widget.order;
    final orderId = int.tryParse(order['id']?.toString() ?? '0') ?? 0;
    final orderCode = order['order_code']?.toString() ?? order['id']?.toString() ?? '';
    final customerName = order['customer_name']?.toString() ?? 'Pelanggan';
    final customerPhone = order['customer_phone']?.toString() ?? '';
    final delivAddr = order['delivery_address'] is Map ? order['delivery_address'] as Map : {};
    final addressText = delivAddr['address']?.toString() ?? delivAddr['contact_address']?.toString() ?? 'Cicalengka';
    final items = order['items'] is List ? (order['items'] as List) : [];
    final totalAmount = double.tryParse(order['order_amount']?.toString() ?? '0') ?? 0.0;

    final startPos = _getStorePosition();
    final endPos = _getCustomerPosition();
    final centerPos = LatLng(
      (startPos.latitude + endPos.latitude) / 2,
      (startPos.longitude + endPos.longitude) / 2,
    );

    return Scaffold(
      body: Stack(
        children: [
          // 1. Live Interactive Map
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(
              initialCenter: centerPos,
              initialZoom: 16.5,
              minZoom: 12,
              maxZoom: 19,
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'com.cicalengkago.mobile',
              ),

              // Road Route Polyline
              if (_routePoints.isNotEmpty)
                PolylineLayer(
                  polylines: [
                    Polyline(
                      points: _routePoints,
                      strokeWidth: 5.0,
                      color: const Color(0xFF16A34A),
                    ),
                  ],
                ),

              // Markers Layer
              MarkerLayer(
                markers: [
                  // Store Location Marker
                  Marker(
                    point: startPos,
                    width: 50,
                    height: 50,
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(6),
                          decoration: const BoxDecoration(
                            color: Color(0xFF16A34A),
                            shape: BoxShape.circle,
                            boxShadow: [BoxShadow(color: Colors.black26, blurRadius: 6)],
                          ),
                          child: const Icon(Icons.storefront_rounded, color: Colors.white, size: 20),
                        ),
                      ],
                    ),
                  ),

                  // Customer House Marker
                  Marker(
                    point: endPos,
                    width: 60,
                    height: 60,
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(6),
                          decoration: const BoxDecoration(
                            color: AppTheme.primaryRed,
                            shape: BoxShape.circle,
                            boxShadow: [BoxShadow(color: Colors.black26, blurRadius: 6)],
                          ),
                          child: const Icon(Icons.home_rounded, color: Colors.white, size: 22),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),

          // 2. Top Header Overlay (Back Button + Distance Badge)
          Positioned(
            top: MediaQuery.of(context).padding.top + 10,
            left: 16,
            right: 16,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                // Back Button
                Container(
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                    boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 8)],
                  ),
                  child: IconButton(
                    icon: const Icon(Icons.arrow_back_rounded, color: Color(0xFF0F172A)),
                    onPressed: () => Navigator.pop(context),
                  ),
                ),

                // Distance & ETA Floating Badge
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFF0F172A),
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: const [BoxShadow(color: Colors.black26, blurRadius: 8, offset: Offset(0, 2))],
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.directions_walk_rounded, color: Color(0xFF4ADE80), size: 18),
                      const SizedBox(width: 6),
                      Text(
                        _distanceMeters < 1000
                            ? '${_distanceMeters.round()} m • ~$_etaMinutes Menit'
                            : '${(_distanceMeters / 1000).toStringAsFixed(1)} km • ~$_etaMinutes Menit',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ),

                // Recenter Map Button
                Container(
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                    boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 8)],
                  ),
                  child: IconButton(
                    icon: const Icon(Icons.my_location_rounded, color: Color(0xFF16A34A)),
                    onPressed: _fitMapBounds,
                  ),
                ),
              ],
            ),
          ),

          // 3. Bottom Slide-Up Delivery Info Panel
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: EdgeInsets.fromLTRB(18, 18, 18, MediaQuery.of(context).padding.bottom + 14),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                boxShadow: [
                  BoxShadow(color: Colors.black12, blurRadius: 20, offset: Offset(0, -4)),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Order Tag & Header
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: const Color(0xFFDCFCE7),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Text('Kurir Toko', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF15803D))),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            '#$orderCode',
                            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13, color: Color(0xFF0F172A)),
                          ),
                        ],
                      ),
                      Text(
                        CurrencyFormatter.formatRupiah(totalAmount),
                        style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: Color(0xFF16A34A)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Customer Name & Contact Actions
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: const BoxDecoration(
                          color: Color(0xFFF1F5F9),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.person_pin_circle_rounded, color: AppTheme.primaryRed, size: 22),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              customerName,
                              style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 2),
                            Text(
                              addressText,
                              style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),

                      // In-App Voice Call Customer Button
                      IconButton(
                        onPressed: () {
                          final cAvatar = order['customer_avatar']?.toString() ?? order['customer']?['avatar']?.toString() ?? '';
                          GlobalCallService.instance.openCallScreen(
                            context,
                            orderCode: orderCode,
                            isIncoming: false,
                            callerRole: 'vendor',
                            initialPartnerName: customerName,
                            initialPartnerAvatar: cAvatar,
                          );
                        },
                        icon: const Icon(Icons.phone_in_talk_rounded, color: Color(0xFF16A34A), size: 22),
                        tooltip: 'Telepon In-App',
                      ),

                      // Chat Customer Button
                      IconButton(
                        onPressed: () {
                          final authCtrl = context.read<AuthController>();
                          final uid = int.tryParse(authCtrl.user?['id']?.toString() ?? '0') ?? 0;
                          InAppChatModal.show(
                            context,
                            orderCode: orderCode,
                            currentUserId: uid,
                            currentUserRole: 'vendor',
                          );
                        },
                        icon: const Icon(Icons.chat_bubble_rounded, color: Color(0xFF2563EB), size: 22),
                        tooltip: 'Chat',
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Items Preview Tag
                  if (items.isNotEmpty)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: const Color(0xFFF1F5F9)),
                      ),
                      child: Text(
                        'Item: ${items.map((it) => '${it['quantity'] ?? 1}x ${it['product_name'] ?? it['name'] ?? ''}').join(', ')}',
                        style: const TextStyle(fontSize: 11, color: Color(0xFF475569), fontWeight: FontWeight.w500),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),

                  const SizedBox(height: 14),

                  // Action Buttons: Open Maps Navigation & Complete Delivery
                  Row(
                    children: [
                      // Google Maps Nav Button
                      Expanded(
                        flex: 4,
                        child: OutlinedButton.icon(
                          onPressed: _openGoogleMapsNav,
                          style: OutlinedButton.styleFrom(
                            foregroundColor: const Color(0xFF2563EB),
                            side: const BorderSide(color: Color(0xFFBFDBFE)),
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                          icon: const Icon(Icons.navigation_rounded, size: 16),
                          label: const Text('Google Maps', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold)),
                        ),
                      ),
                      const SizedBox(width: 8),

                      // Selesai Diantar Button
                      Expanded(
                        flex: 6,
                        child: ElevatedButton.icon(
                          onPressed: _isDelivering
                              ? null
                              : () async {
                                  setState(() => _isDelivering = true);
                                  final messenger = ScaffoldMessenger.of(context);
                                  final navigator = Navigator.of(context);
                                  final ok = await context.read<MerchantController>().updateOrderStatus(
                                        orderId,
                                        'delivered',
                                        deliveryType: 'merchant',
                                      );
                                  if (!mounted) return;
                                  setState(() => _isDelivering = false);
                                  if (ok) {
                                    messenger.showSnackBar(
                                      const SnackBar(
                                        content: Text('Pesanan berhasil diserahkan ke pelanggan! 🎉'),
                                        backgroundColor: Color(0xFF16A34A),
                                      ),
                                    );
                                    navigator.pop();
                                  }
                                },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF16A34A),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            elevation: 0,
                          ),
                          icon: _isDelivering
                              ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                              : const Icon(Icons.check_circle_rounded, size: 16),
                          label: const Text('Konfirmasi Sampai', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
