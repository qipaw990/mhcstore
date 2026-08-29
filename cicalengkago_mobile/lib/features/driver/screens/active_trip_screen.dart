import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/services/global_call_service.dart';
import '../../../core/services/location_service.dart';
import '../../../core/services/route_service.dart';
import '../../common/screens/in_app_chat_modal.dart';
import '../../auth/controllers/auth_controller.dart';
import '../controllers/driver_controller.dart';

class ActiveTripScreen extends StatefulWidget {
  final Map<String, dynamic> trip;
  const ActiveTripScreen({super.key, required this.trip});

  @override
  State<ActiveTripScreen> createState() => _ActiveTripScreenState();
}

class _ActiveTripScreenState extends State<ActiveTripScreen> {
  final _otpCtrl = TextEditingController();
  final MapController _mapController = MapController();
  bool _autoFollow = true;
  LatLng? _lastCenteredLocation;

  // Real-time Road Routing & Sequential Multi-Store Pickup State
  List<LatLng> _liveRoadPoints = [];
  String? _lastRouteTargetKey;
  final Set<int> _pickedStoreIndices = {};

  int _getCurrentPickupIndex(int totalStores) {
    for (int i = 0; i < totalStores; i++) {
      if (!_pickedStoreIndices.contains(i)) return i;
    }
    return totalStores;
  }

  bool _isAllStoresPicked(int totalStores) {
    return totalStores > 0 && _pickedStoreIndices.length >= totalStores;
  }

  @override
  void dispose() {
    _otpCtrl.dispose();
    super.dispose();
  }

  void _syncRoadRoute(LatLng start, LatLng end) {
    final key = '${start.latitude.toStringAsFixed(3)},${start.longitude.toStringAsFixed(3)}->${end.latitude.toStringAsFixed(3)},${end.longitude.toStringAsFixed(3)}';
    if (_lastRouteTargetKey == key) return;
    _lastRouteTargetKey = key;

    RouteService.getRoadRoute(start, end).then((points) {
      if (mounted && points.isNotEmpty) {
        setState(() {
          _liveRoadPoints = points;
        });
      }
    }).catchError((_) {});
  }

  @override
  Widget build(BuildContext context) {
    final driverCtrl = context.watch<DriverController>();
    final trip = driverCtrl.activeTrip ?? widget.trip;
    final orderId = int.tryParse(trip['id']?.toString() ?? '0') ?? 0;
    final status = trip['order_status'] ?? 'accepted';
    final orderCode = trip['order_code'] ?? orderId.toString();
    final storeName = trip['store_name'] ?? 'Mitra Resto Cicalengka';
    final storeAddress = trip['store_address'] ?? 'Cicalengka';

    final rawDeliv = trip['delivery_address'];
    String deliveryAddress = 'Cicalengka';
    String customerName = trip['customer_name'] ?? 'Pelanggan Cicalengka';
    String customerPhone = trip['customer_phone'] ?? '';

    if (rawDeliv is Map) {
      deliveryAddress = rawDeliv['address']?.toString() ?? 'Cicalengka';
      if (rawDeliv['contact_person_name'] != null && rawDeliv['contact_person_name'].toString().isNotEmpty) {
        customerName = rawDeliv['contact_person_name'].toString();
      }
      if (rawDeliv['contact_person_number'] != null && rawDeliv['contact_person_number'].toString().isNotEmpty) {
        customerPhone = rawDeliv['contact_person_number'].toString();
      }
    } else if (rawDeliv is String) {
      deliveryAddress = rawDeliv;
    }

    double deliveryCharge = double.tryParse(trip['delivery_charge']?.toString() ?? '0') ?? 0;

    // Extract all pickup stores for multi-store trip
    final List<Map<String, dynamic>> pickupStores = _extractPickupStores(trip, storeName, storeAddress);

    if (pickupStores.length > 1) {
      final estCom = double.tryParse(trip['est_commission']?.toString() ??
          (trip['batch_info'] is Map ? trip['batch_info']['est_commission']?.toString() : null) ?? '0') ?? 0;
      if (estCom > 0) {
        deliveryCharge = estCom;
      } else if (trip['batch_orders'] is List && (trip['batch_orders'] as List).isNotEmpty) {
        final sumBatch = (trip['batch_orders'] as List).fold<double>(
          0.0,
          (sum, bo) => sum + (double.tryParse((bo is Map ? bo['delivery_charge'] : null)?.toString() ?? '0') ?? 0),
        );
        if (sumBatch > 0) deliveryCharge = sumBatch;
      }
    }

    // Extract customer coordinates
    final double? custLat = double.tryParse((rawDeliv is Map ? (rawDeliv['lat'] ?? rawDeliv['latitude']) : trip['dest_lat'])?.toString() ?? '');
    final double? custLng = double.tryParse((rawDeliv is Map ? (rawDeliv['lng'] ?? rawDeliv['longitude']) : trip['dest_lng'])?.toString() ?? '');
    final LatLng custPosition = (custLat != null && custLng != null && custLat != 0 && custLng != 0)
        ? LatLng(custLat, custLng)
        : const LatLng(-6.9855, 107.8350);

    // Auto-follow camera movement
    if (_autoFollow && _lastCenteredLocation != driverCtrl.currentLocation) {
      _lastCenteredLocation = driverCtrl.currentLocation;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _mapController.move(driverCtrl.currentLocation, 15.5);
        }
      });
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Status Banner
          _buildStatusBanner(status, orderCode, pickupStores.length),
          const SizedBox(height: 14),

          // Komisi chip
          Row(
            children: [
              const Text('Komisi Trip Ini:', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                decoration: BoxDecoration(
                  color: const Color(0xFFD1FAE5),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  CurrencyFormatter.formatRupiah(deliveryCharge),
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF059669)),
                ),
              ),
              if (pickupStores.length > 1) ...[
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEF3C7),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: const Color(0xFFFDE68A)),
                  ),
                  child: Text(
                    'Multi-Toko (${pickupStores.length} Resto)',
                    style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFFB45309)),
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(height: 14),

          // Multi-Store Radar Map with Live Route Polylines
          _buildTripRadarMap(
            driverCtrl: driverCtrl,
            pickupStores: pickupStores,
            custPosition: custPosition,
            customerName: customerName,
            status: status,
          ),
          const SizedBox(height: 16),

          // Sequential Itinerary Card (Multi-Store Pickups + Final Customer Destination)
          _buildLocationCard(
            pickupStores: pickupStores,
            customerName: customerName,
            deliveryAddress: deliveryAddress,
            customerPhone: customerPhone,
            status: status,
          ),

          const SizedBox(height: 20),

          // Action Buttons (Sequential 1-by-1 Store Confirmation & Final Customer Delivery)
          _buildActionSection(
            context: context,
            driverCtrl: driverCtrl,
            trip: trip,
            orderId: orderId,
            status: status,
            customerName: customerName,
            storeName: storeName,
            customerPhone: customerPhone,
            orderCode: orderCode,
            deliveryCharge: deliveryCharge,
            pickupStores: pickupStores,
          ),
        ],
      ),
    );
  }

  Widget _buildTripRadarMap({
    required DriverController driverCtrl,
    required List<Map<String, dynamic>> pickupStores,
    required LatLng custPosition,
    required String customerName,
    required String status,
  }) {
    final int currentPickupIdx = _getCurrentPickupIndex(pickupStores.length);
    final bool allStoresPicked = _isAllStoresPicked(pickupStores.length);
    final bool isDeliveringToCustomer = (status == 'on_the_way') || (status == 'picked_up') || allStoresPicked;

    // 1. Build List of Store Coordinates
    final List<LatLng> storePositions = [];
    for (int i = 0; i < pickupStores.length; i++) {
      final s = pickupStores[i];
      final sLat = double.tryParse(s['lat']?.toString() ?? '');
      final sLng = double.tryParse(s['lng']?.toString() ?? '');
      if (sLat != null && sLng != null && sLat != 0 && sLng != 0) {
        storePositions.add(LatLng(sLat, sLng));
      } else {
        // Fallback staggered from driver location
        storePositions.add(LatLng(
          driverCtrl.currentLocation.latitude + (0.003 * (i + 1)),
          driverCtrl.currentLocation.longitude + (0.002 * (i + 1)),
        ));
      }
    }

    // 2. Determine target coordinate for current stage
    final LatLng currentStageTarget = isDeliveringToCustomer
        ? custPosition
        : (currentPickupIdx < storePositions.length ? storePositions[currentPickupIdx] : custPosition);

    // Synchronize road route dynamically
    _syncRoadRoute(driverCtrl.currentLocation, currentStageTarget);

    // 3. Build Polyline Route Segments based on Stage
    final List<Polyline> polylines = [];
    final activeRoadPoints = _liveRoadPoints.isNotEmpty ? _liveRoadPoints : [driverCtrl.currentLocation, currentStageTarget];

    if (isDeliveringToCustomer) {
      // Stage 2: Delivering to customer (GREEN ROAD ROUTE)
      polylines.add(
        Polyline(
          points: activeRoadPoints,
          strokeWidth: 5.0,
          color: const Color(0xFF059669),
        ),
      );
    } else {
      // Stage 1: Heading to Current Unpicked Store (BLUE ROAD ROUTE)
      polylines.add(
        Polyline(
          points: activeRoadPoints,
          strokeWidth: 5.0,
          color: const Color(0xFF2563EB),
        ),
      );

      // Remaining Stores Route (Amber for subsequent multi-stores)
      for (int i = currentPickupIdx; i < storePositions.length - 1; i++) {
        polylines.add(
          Polyline(
            points: [storePositions[i], storePositions[i + 1]],
            strokeWidth: 4.0,
            color: const Color(0xFFD97706),
          ),
        );
      }
    }

    // 4. Build Markers for current stage
    final List<Marker> markers = [
      // Driver Marker (Live compass rotation)
      Marker(
        point: driverCtrl.currentLocation,
        width: 50,
        height: 50,
        child: Stack(
          alignment: Alignment.center,
          children: [
            Container(
              width: 50,
              height: 50,
              decoration: BoxDecoration(
                color: const Color(0xFF16A34A).withValues(alpha: 0.15),
                shape: BoxShape.circle,
              ),
            ),
            Transform.rotate(
              angle: (driverCtrl.heading) * (math.pi / 180.0),
              child: Container(
                width: 34,
                height: 34,
                decoration: const BoxDecoration(
                  color: Color(0xFF16A34A),
                  shape: BoxShape.circle,
                  boxShadow: [BoxShadow(color: Color(0x6616A34A), blurRadius: 10)],
                ),
                child: const Icon(Icons.navigation_rounded, color: Colors.white, size: 20),
              ),
            ),
          ],
        ),
      ),
    ];

    if (!isDeliveringToCustomer) {
      // Add Store Markers during Pickup Stage
      for (int i = 0; i < storePositions.length; i++) {
        final String sName = (i < pickupStores.length ? pickupStores[i]['name'] : 'Toko') ?? 'Toko';
        final bool isPicked = _pickedStoreIndices.contains(i);
        final bool isCurrent = (i == currentPickupIdx);

        markers.add(
          Marker(
            point: storePositions[i],
            width: 80,
            height: 60,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                  decoration: BoxDecoration(
                    color: isPicked
                        ? const Color(0xFF065F46)
                        : (isCurrent ? const Color(0xFF1E293B) : const Color(0xFF64748B)),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    sName,
                    style: const TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                const SizedBox(height: 2),
                Stack(
                  alignment: Alignment.center,
                  children: [
                    Container(
                      width: 34,
                      height: 34,
                      decoration: BoxDecoration(
                        color: isPicked
                            ? const Color(0xFF059669)
                            : (isCurrent ? const Color(0xFF2563EB) : const Color(0xFFD97706)),
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: (isPicked
                                    ? const Color(0xFF059669)
                                    : (isCurrent ? const Color(0xFF2563EB) : const Color(0xFFD97706)))
                                .withValues(alpha: 0.4),
                            blurRadius: 8,
                          ),
                        ],
                      ),
                      child: Icon(
                        isPicked ? Icons.check_circle_rounded : Icons.storefront_rounded,
                        color: Colors.white,
                        size: 17,
                      ),
                    ),
                    Positioned(
                      top: 0,
                      right: 0,
                      child: Container(
                        padding: const EdgeInsets.all(3),
                        decoration: BoxDecoration(
                          color: isPicked ? const Color(0xFF16A34A) : AppTheme.primaryRed,
                          shape: BoxShape.circle,
                        ),
                        child: Text('${i + 1}', style: const TextStyle(color: Colors.white, fontSize: 8.5, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      }
    } else {
      // Add Customer Destination Marker during Delivery Stage
      markers.add(
        Marker(
          point: custPosition,
          width: 80,
          height: 60,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                decoration: BoxDecoration(
                  color: const Color(0xFF047857),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  customerName.isNotEmpty ? customerName : 'Pelanggan',
                  style: const TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(height: 2),
              Container(
                width: 34,
                height: 34,
                decoration: BoxDecoration(
                  color: const Color(0xFF059669),
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(color: const Color(0xFF059669).withValues(alpha: 0.4), blurRadius: 8),
                  ],
                ),
                child: const Icon(Icons.home_rounded, color: Colors.white, size: 18),
              ),
            ],
          ),
        ),
      );
    }

    final String stageHeaderTitle = isDeliveringToCustomer
        ? 'Panduan Rute: Ke Pelanggan'
        : 'Panduan Rute: Ke Toko ${currentPickupIdx + 1} (${pickupStores.isNotEmpty && currentPickupIdx < pickupStores.length ? pickupStores[currentPickupIdx]['name'] : ''})';

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          // Radar Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Container(
                      width: 30,
                      height: 30,
                      decoration: const BoxDecoration(
                        color: AppTheme.primaryRed,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.radar_rounded, color: Colors.white, size: 16),
                    ),
                    const SizedBox(width: 8),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Text(
                              stageHeaderTitle,
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A)),
                            ),
                            if (_autoFollow) ...[
                              const SizedBox(width: 6),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFDCFCE7),
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: const Color(0xFF86EFAC)),
                                ),
                                child: const Text('Live Jalan', style: TextStyle(fontSize: 8.5, fontWeight: FontWeight.bold, color: Color(0xFF16A34A))),
                              ),
                            ],
                          ],
                        ),
                        Row(
                          children: [
                            Container(
                              width: 6,
                              height: 6,
                              margin: const EdgeInsets.only(right: 5),
                              decoration: const BoxDecoration(color: Color(0xFF16A34A), shape: BoxShape.circle),
                            ),
                            Text(
                              '${driverCtrl.currentLocation.latitude.toStringAsFixed(6)}, ${driverCtrl.currentLocation.longitude.toStringAsFixed(6)}',
                              style: const TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: Color(0xFF047857), fontFamily: 'monospace'),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: isDeliveringToCustomer ? const Color(0xFFDCFCE7) : const Color(0xFFEFF6FF),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: isDeliveringToCustomer ? const Color(0xFF86EFAC) : const Color(0xFFBFDBFE)),
                  ),
                  child: Text(
                    isDeliveringToCustomer ? 'Antar Pesanan' : 'Ambil di Resto',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                      color: isDeliveringToCustomer ? const Color(0xFF16A34A) : const Color(0xFF2563EB),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),

          // Map Container
          SizedBox(
            height: 240,
            child: ClipRRect(
              borderRadius: const BorderRadius.vertical(bottom: Radius.circular(16)),
              child: Stack(
                children: [
                  FlutterMap(
                    mapController: _mapController,
                    options: MapOptions(
                      initialCenter: driverCtrl.currentLocation,
                      initialZoom: 15.0,
                      onPositionChanged: (camera, hasGesture) {
                        if (hasGesture && _autoFollow) {
                          setState(() => _autoFollow = false);
                        }
                      },
                    ),
                    children: [
                      TileLayer(
                        urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                        userAgentPackageName: 'com.cicalengkago.mobile',
                        errorTileCallback: (tile, error, stackTrace) {},
                        evictErrorTileStrategy: EvictErrorTileStrategy.none,
                      ),
                      // Turn-by-turn road polyline
                      PolylineLayer(polylines: polylines),
                      MarkerLayer(markers: markers),
                    ],
                  ),

                  // Floating Re-center GPS Button
                  Positioned(
                    bottom: 10,
                    right: 10,
                    child: Material(
                      elevation: 3,
                      shape: const CircleBorder(),
                      color: _autoFollow ? const Color(0xFF16A34A) : Colors.white,
                      child: InkWell(
                        customBorder: const CircleBorder(),
                        onTap: () async {
                          setState(() => _autoFollow = true);
                          await driverCtrl.refreshLocation();
                          _mapController.move(driverCtrl.currentLocation, 15.5);
                          if (mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('📍 Mode Ikuti GPS Driver Aktif!'),
                                duration: Duration(seconds: 1),
                                backgroundColor: Color(0xFF16A34A),
                              ),
                            );
                          }
                        },
                        child: Padding(
                          padding: const EdgeInsets.all(10),
                          child: Icon(
                            Icons.my_location_rounded,
                            color: _autoFollow ? Colors.white : AppTheme.primaryRed,
                            size: 20,
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  List<Map<String, dynamic>> _extractPickupStores(Map<String, dynamic> trip, String defaultStoreName, String defaultStoreAddress) {
    final List<Map<String, dynamic>> result = [];
    final rawBatchStores = trip['batch_stores'];
    final rawBatchSubOrders = trip['batch_sub_orders'];
    final rawBatchOrders = trip['batch_orders'];

    if (rawBatchStores is List && rawBatchStores.isNotEmpty) {
      for (var s in rawBatchStores) {
        if (s is Map) {
          final sMap = Map<String, dynamic>.from(s);
          result.add({
            'name': (sMap['name'] ?? sMap['store_name'] ?? defaultStoreName).toString(),
            'address': (sMap['address'] ?? sMap['store_address'] ?? defaultStoreAddress).toString(),
            'phone': (sMap['phone'] ?? sMap['store_phone'] ?? '').toString(),
            'lat': double.tryParse((sMap['lat'] ?? sMap['latitude'] ?? sMap['store_lat'] ?? trip['store_lat'])?.toString() ?? ''),
            'lng': double.tryParse((sMap['lng'] ?? sMap['longitude'] ?? sMap['store_lng'] ?? trip['store_lng'])?.toString() ?? ''),
            'items': (sMap['items'] is List) ? (sMap['items'] as List) : [],
          });
        }
      }
    } else if (rawBatchSubOrders is List && rawBatchSubOrders.isNotEmpty) {
      for (var sub in rawBatchSubOrders) {
        if (sub is Map) {
          final subMap = Map<String, dynamic>.from(sub);
          result.add({
            'name': (subMap['store_name'] ?? defaultStoreName).toString(),
            'address': (subMap['store_address'] ?? defaultStoreAddress).toString(),
            'phone': (subMap['store_phone'] ?? '').toString(),
            'lat': double.tryParse((subMap['lat'] ?? subMap['latitude'] ?? subMap['store_lat'] ?? trip['store_lat'])?.toString() ?? ''),
            'lng': double.tryParse((subMap['lng'] ?? subMap['longitude'] ?? subMap['store_lng'] ?? trip['store_lng'])?.toString() ?? ''),
            'items': (subMap['items'] is List) ? (subMap['items'] as List) : [],
          });
        }
      }
    } else if (rawBatchOrders is List && rawBatchOrders.isNotEmpty) {
      for (var bo in rawBatchOrders) {
        if (bo is Map) {
          final boMap = Map<String, dynamic>.from(bo);
          result.add({
            'name': (boMap['store_name'] ?? defaultStoreName).toString(),
            'address': (boMap['store_address'] ?? defaultStoreAddress).toString(),
            'phone': (boMap['store_phone'] ?? '').toString(),
            'lat': double.tryParse((boMap['lat'] ?? boMap['latitude'] ?? boMap['store_lat'] ?? trip['store_lat'])?.toString() ?? ''),
            'lng': double.tryParse((boMap['lng'] ?? boMap['longitude'] ?? boMap['store_lng'] ?? trip['store_lng'])?.toString() ?? ''),
            'items': (boMap['items'] is List) ? (boMap['items'] as List) : [],
          });
        }
      }
    }

    if (result.isEmpty) {
      final List rawItems = (trip['items'] as List?) ?? [];
      final Map<String, List<dynamic>> itemsByStore = {};
      for (var item in rawItems) {
        if (item is Map) {
          final String sName = (item['store_name'] ?? defaultStoreName).toString();
          if (!itemsByStore.containsKey(sName)) {
            itemsByStore[sName] = [];
          }
          itemsByStore[sName]!.add(item);
        }
      }
      if (itemsByStore.isEmpty) {
        result.add({
          'name': defaultStoreName,
          'address': defaultStoreAddress,
          'phone': trip['store_phone'] ?? '',
          'lat': double.tryParse(trip['store_lat']?.toString() ?? ''),
          'lng': double.tryParse(trip['store_lng']?.toString() ?? ''),
          'items': rawItems,
        });
      } else {
        itemsByStore.forEach((sName, sItems) {
          result.add({
            'name': sName,
            'address': (sName == defaultStoreName) ? defaultStoreAddress : 'Cicalengka, Jawa Barat',
            'phone': (sName == defaultStoreName) ? (trip['store_phone'] ?? '') : '',
            'lat': double.tryParse(trip['store_lat']?.toString() ?? ''),
            'lng': double.tryParse(trip['store_lng']?.toString() ?? ''),
            'items': sItems,
          });
        });
      }
    }
    return result;
  }

  Widget _buildStatusBanner(String status, String orderCode, int storeCount) {
    final statusInfo = _getStatusInfo(status, storeCount);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: statusInfo['bgColor'] as Color,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: (statusInfo['color'] as Color).withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: (statusInfo['color'] as Color).withValues(alpha: 0.2),
              shape: BoxShape.circle,
            ),
            child: Icon(statusInfo['icon'] as IconData, color: statusInfo['color'] as Color, size: 24),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  statusInfo['label'] as String,
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: statusInfo['color'] as Color,
                  ),
                ),
                Text(
                  'Order #$orderCode',
                  style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B), fontFamily: 'monospace'),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: (statusInfo['color'] as Color).withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              statusInfo['stepLabel'] as String,
              style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: statusInfo['color'] as Color),
            ),
          ),
        ],
      ),
    );
  }

  Map<String, dynamic> _getStatusInfo(String status, int storeCount) {
    final totalSteps = storeCount + 1;
    switch (status) {
      case 'accepted':
      case 'pending':
      case 'confirmed':
      case 'processing':
        return {
          'label': storeCount > 1 ? 'Jemput di $storeCount Toko / Resto' : 'Menuju ke Toko / Resto',
          'stepLabel': 'Langkah 1/$totalSteps',
          'icon': Icons.store_rounded,
          'color': const Color(0xFF2563EB),
          'bgColor': const Color(0xFFEFF6FF),
        };
      case 'picked_up':
      case 'handover':
      case 'on_the_way':
        return {
          'label': 'Mengantar ke Pelanggan',
          'stepLabel': 'Langkah $totalSteps/$totalSteps',
          'icon': Icons.delivery_dining_rounded,
          'color': const Color(0xFF059669),
          'bgColor': const Color(0xFFECFDF5),
        };
      default:
        return {
          'label': 'Trip Aktif',
          'stepLabel': 'Aktif',
          'icon': Icons.navigation_rounded,
          'color': AppTheme.primaryRed,
          'bgColor': const Color(0xFFFEF2F2),
        };
    }
  }

  Widget _buildLocationCard({
    required List<Map<String, dynamic>> pickupStores,
    required String customerName,
    required String deliveryAddress,
    required String customerPhone,
    required String status,
  }) {
    final int currentPickupIdx = _getCurrentPickupIndex(pickupStores.length);
    final bool allStoresPicked = _isAllStoresPicked(pickupStores.length);
    final bool isDeliveringToCustomer = (status == 'on_the_way') || (status == 'picked_up') || allStoresPicked;

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // 1. Render each pickup store step with its items and status badge
          ...pickupStores.asMap().entries.map((stepEntry) {
            final int stepIdx = stepEntry.key;
            final Map<String, dynamic> storeData = stepEntry.value;
            final String sName = storeData['name'] ?? 'Toko Mitra';
            final String sAddr = storeData['address'] ?? 'Cicalengka, Jawa Barat';
            final List sItems = (storeData['items'] is List) ? (storeData['items'] as List) : [];
            final bool isThisPicked = _pickedStoreIndices.contains(stepIdx) || isDeliveringToCustomer;
            final bool isThisCurrent = (stepIdx == currentPickupIdx) && !isDeliveringToCustomer;

            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Step Badge & Store Info Header
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            width: 36,
                            height: 36,
                            decoration: BoxDecoration(
                              color: isThisPicked
                                  ? const Color(0xFFDCFCE7)
                                  : (isThisCurrent ? const Color(0xFFEFF6FF) : const Color(0xFFF1F5F9)),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Center(
                              child: isThisPicked
                                  ? const Icon(Icons.check_circle_rounded, color: Color(0xFF16A34A), size: 20)
                                  : Text(
                                      '${stepIdx + 1}',
                                      style: TextStyle(
                                        color: isThisCurrent ? const Color(0xFF2563EB) : const Color(0xFF64748B),
                                        fontSize: 14,
                                        fontWeight: FontWeight.bold,
                                      ),
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
                                    Expanded(
                                      child: Text(
                                        isThisPicked
                                            ? 'LANGKAH ${stepIdx + 1}: SUDAH DIAMBIL'
                                            : (isThisCurrent
                                                ? 'LANGKAH ${stepIdx + 1}: JEMPUT SEKARANG'
                                                : 'LANGKAH ${stepIdx + 1}: JEMPUT BERIKUTNYA'),
                                        style: TextStyle(
                                          fontSize: 10,
                                          color: isThisPicked
                                              ? const Color(0xFF16A34A)
                                              : (isThisCurrent ? const Color(0xFF2563EB) : const Color(0xFF64748B)),
                                          fontWeight: FontWeight.w800,
                                          letterSpacing: 0.5,
                                        ),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                    if (sItems.isNotEmpty) ...[
                                      const SizedBox(width: 6),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFDBEAFE),
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Text(
                                          '${sItems.length} Menu',
                                          style: const TextStyle(
                                            fontSize: 9.5,
                                            fontWeight: FontWeight.bold,
                                            color: Color(0xFF1D4ED8),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  sName,
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.bold,
                                    color: isThisPicked ? const Color(0xFF64748B) : const Color(0xFF0F172A),
                                    decoration: isThisPicked ? TextDecoration.lineThrough : null,
                                  ),
                                ),
                                Text(
                                  sAddr,
                                  style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),
                          _mapBtn(sAddr),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            );
          }),

          // 2. Final Customer Destination Step
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: const Color(0xFFECFDF5),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.home_rounded, color: Color(0xFF059669), size: 18),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'LANGKAH AKHIR: ANTAR KE PELANGGAN',
                        style: const TextStyle(
                          fontSize: 10,
                          color: Color(0xFF059669),
                          fontWeight: FontWeight.w800,
                          letterSpacing: 0.5,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        customerName,
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                      ),
                      Text(
                        deliveryAddress,
                        style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      if (customerPhone.isNotEmpty)
                        Text(
                          'Telp: $customerPhone',
                          style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                        ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                _mapBtn(deliveryAddress),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionSection({
    required BuildContext context,
    required DriverController driverCtrl,
    required Map<String, dynamic> trip,
    required int orderId,
    required String status,
    required String customerName,
    required String storeName,
    required String customerPhone,
    required String orderCode,
    required double deliveryCharge,
    required List<Map<String, dynamic>> pickupStores,
  }) {
    final int storeCount = pickupStores.length;
    final int currentPickupIdx = _getCurrentPickupIndex(storeCount);
    final bool allStoresPicked = _isAllStoresPicked(storeCount);
    final bool isDeliveringToCustomer = (status == 'on_the_way') || (status == 'picked_up') || allStoresPicked;

    return Column(
      children: [
        // Sequential Store Pickup Action Buttons
        if (!isDeliveringToCustomer) ...[
          if (currentPickupIdx < storeCount) ...[
            Builder(builder: (context) {
              final currentStoreName = pickupStores[currentPickupIdx]['name'] ?? 'Toko ${currentPickupIdx + 1}';
              return _actionButton(
                icon: Icons.check_circle_rounded,
                label: storeCount > 1
                    ? 'Sudah Ambil di Toko ${currentPickupIdx + 1}'
                    : 'Sudah Ambil di Toko',
                sublabel: currentStoreName,
                color: const Color(0xFF2563EB),
                onTap: () async {
                  setState(() {
                    _pickedStoreIndices.add(currentPickupIdx);
                  });

                  if (_isAllStoresPicked(storeCount)) {
                    final ok = await driverCtrl.updateTripStatus(orderId, 'picked_up');
                    if (context.mounted) {
                      if (ok) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('🎉 Semua toko selesai diambil! Rute otomatis diarahkan ke rumah pelanggan.'),
                            backgroundColor: Color(0xFF059669),
                          ),
                        );
                      } else {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Gagal update status. Coba lagi.')),
                        );
                      }
                    }
                  } else {
                    if (context.mounted) {
                      final nextStoreName = (currentPickupIdx + 1 < storeCount)
                          ? pickupStores[currentPickupIdx + 1]['name']
                          : 'Toko Berikutnya';
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text('✅ Toko ${currentPickupIdx + 1} selesai! Rute sekarang dialihkan ke Toko ${currentPickupIdx + 2} ($nextStoreName).'),
                          backgroundColor: const Color(0xFF2563EB),
                        ),
                      );
                    }
                  }
                },
              );
            }),
          ],
        ] else if (status == 'picked_up' || status == 'handover' || status == 'on_the_way' || allStoresPicked) ...[
          // OTP input for final delivery confirmation to customer
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFF0FDF4),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFF86EFAC)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    Icon(Icons.lock_rounded, color: Color(0xFF059669), size: 16),
                    SizedBox(width: 6),
                    Text('Masukkan Kode OTP Pelanggan', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF059669))),
                  ],
                ),
                const SizedBox(height: 4),
                const Text('Minta kode OTP 6 digit dari pelanggan untuk konfirmasi pesanan tiba.',
                    style: TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                const SizedBox(height: 10),
                TextField(
                  controller: _otpCtrl,
                  keyboardType: TextInputType.number,
                  maxLength: 6,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, letterSpacing: 8),
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: InputDecoration(
                    hintText: '------',
                    hintStyle: const TextStyle(letterSpacing: 8),
                    counterText: '',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF86EFAC), width: 2),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF059669), width: 2),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _actionButton(
            icon: Icons.done_all_rounded,
            label: 'Pesanan Selesai Diantar',
            sublabel: 'Konfirmasi Serah Terima ke $customerName',
            color: const Color(0xFF059669),
            onTap: () async {
              final otp = _otpCtrl.text.trim();
              final ok = await driverCtrl.updateTripStatus(orderId, 'delivered', otpCode: otp.isNotEmpty ? otp : null);
              if (context.mounted) {
                if (ok) {
                  _showCompletionSuccessDialog(
                    context,
                    orderCode: orderCode,
                    commission: deliveryCharge,
                    customerName: customerName,
                    storeName: storeName,
                    newWalletBalance: driverCtrl.walletBalance,
                  );
                } else {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(driverCtrl.lastErrorMessage ?? 'Gagal selesaikan trip. Periksa OTP kembali.'),
                      backgroundColor: const Color(0xFFDC2626),
                    ),
                  );
                }
              }
            },
          ),
        ],

        // Secondary actions (Voice Call, Chat)
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: ElevatedButton.icon(
                icon: const Icon(Icons.phone_in_talk_rounded, size: 16),
                label: const Text('Voice Call', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF2563EB),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  shape: const StadiumBorder(),
                  elevation: 0,
                ),
                onPressed: () {
                  GlobalCallService.instance.openCallScreen(
                    context,
                    orderCode: orderCode,
                    isIncoming: false,
                    callerRole: 'delivery_man',
                  );
                },
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: ElevatedButton.icon(
                icon: const Icon(Icons.chat_bubble_rounded, size: 16),
                label: const Text('Chat', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppTheme.primaryRed,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  shape: const StadiumBorder(),
                  elevation: 0,
                ),
                onPressed: () {
                  final authCtrl = context.read<AuthController>();
                  final uid = int.tryParse(authCtrl.user?['id']?.toString() ?? '0') ?? 0;
                  InAppChatModal.show(
                    context,
                    orderCode: orderCode,
                    currentUserId: uid,
                    currentUserRole: 'delivery_man',
                  );
                },
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _actionButton({
    required IconData icon,
    required String label,
    String? sublabel,
    required Color color,
    required VoidCallback onTap,
  }) {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton(
        style: ElevatedButton.styleFrom(
          backgroundColor: color,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          elevation: 2,
          shadowColor: color.withValues(alpha: 0.35),
        ),
        onPressed: onTap,
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 20, color: Colors.white),
            const SizedBox(width: 8),
            Flexible(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    label,
                    style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Colors.white),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                  ),
                  if (sublabel != null && sublabel.isNotEmpty) ...[
                    const SizedBox(height: 1.5),
                    Text(
                      sublabel,
                      style: const TextStyle(fontSize: 10.5, color: Colors.white70),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _mapBtn(String address) {
    return GestureDetector(
      onTap: () {
        final query = Uri.encodeComponent(address);
        launchUrl(Uri.parse('https://maps.google.com/?q=$query'));
      },
      child: Container(
        padding: const EdgeInsets.all(7),
        decoration: BoxDecoration(
          color: const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(10),
        ),
        child: const Icon(Icons.map_rounded, color: Color(0xFF2563EB), size: 16),
      ),
    );
  }

  void _showCompletionSuccessDialog(
    BuildContext context, {
    required String orderCode,
    required double commission,
    required String customerName,
    required String storeName,
    required double newWalletBalance,
  }) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        backgroundColor: Colors.white,
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Celebration Icon Animation
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  color: const Color(0xFFDCFCE7),
                  shape: BoxShape.circle,
                  border: Border.all(color: const Color(0xFF86EFAC), width: 2),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF16A34A).withValues(alpha: 0.2),
                      blurRadius: 16,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: const Icon(Icons.check_circle_rounded, color: Color(0xFF16A34A), size: 44),
              ),
              const SizedBox(height: 16),

              const Text(
                'Pesanan Selesai! 🎉',
                style: TextStyle(fontSize: 19, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 4),
              Text(
                'Pesanan #$orderCode berhasil diantar ke pelanggan.',
                style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 18),

              // Commission Box
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFF0FDF4),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFBBF7D0)),
                ),
                child: Column(
                  children: [
                    const Text(
                      'KOMISI TRIP BERHASIL MASUK',
                      style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: Color(0xFF15803D), letterSpacing: 0.5),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '+ ${CurrencyFormatter.formatRupiah(commission)}',
                      style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Color(0xFF16A34A)),
                    ),
                    const SizedBox(height: 6),
                    const Divider(height: 1, color: Color(0xFFDCFCE7)),
                    const SizedBox(height: 6),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Saldo Dompet Sekarang:', style: TextStyle(fontSize: 11, color: Color(0xFF475569))),
                        Text(
                          CurrencyFormatter.formatRupiah(newWalletBalance),
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),

              // Info summary
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.person_rounded, size: 14, color: Color(0xFF64748B)),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'Pelanggan: $customerName',
                            style: const TextStyle(fontSize: 11, color: Color(0xFF334155), fontWeight: FontWeight.w600),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        const Icon(Icons.storefront_rounded, size: 14, color: Color(0xFF64748B)),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'Toko: $storeName',
                            style: const TextStyle(fontSize: 11, color: Color(0xFF334155), fontWeight: FontWeight.w600),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Action button
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppTheme.primaryRed,
                  foregroundColor: Colors.white,
                  minimumSize: const Size(double.infinity, 46),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
                  elevation: 2,
                ),
                onPressed: () {
                  Navigator.of(ctx).pop();
                },
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.radar_rounded, size: 18),
                    SizedBox(width: 8),
                    Text('Siap Terima Orderan Baru 🚀', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
