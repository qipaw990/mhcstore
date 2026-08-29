import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/driver_controller.dart';
import 'active_trip_screen.dart';

class DriverRadarScreen extends StatefulWidget {
  final Function(int)? onNavigateTab;
  const DriverRadarScreen({super.key, this.onNavigateTab});

  @override
  State<DriverRadarScreen> createState() => _DriverRadarScreenState();
}

class _DriverRadarScreenState extends State<DriverRadarScreen> {
  final MapController _mapController = MapController();
  bool _autoFollow = true;
  LatLng? _lastCenteredLocation;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DriverController>().refreshLocation();
    });
  }

  @override
  Widget build(BuildContext context) {
    final driverCtrl = context.watch<DriverController>();

    if (driverCtrl.activeTrip != null) {
      return ActiveTripScreen(trip: driverCtrl.activeTrip!);
    }

    if (_autoFollow && _lastCenteredLocation != driverCtrl.currentLocation) {
      _lastCenteredLocation = driverCtrl.currentLocation;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _mapController.move(driverCtrl.currentLocation, 15.5);
        }
      });
    }

    if (driverCtrl.isLoading && driverCtrl.driverProfile == null) {
      return const Scaffold(
        backgroundColor: Color(0xFFF8FAFC),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircularProgressIndicator(color: AppTheme.primaryRed),
              SizedBox(height: 12),
              Text(
                'Memeriksa status driver & pesanan aktif...',
                style: TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      color: AppTheme.primaryRed,
      onRefresh: () async {
        await driverCtrl.refreshLocation();
        await driverCtrl.fetchRadarData();
      },
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // 1. Status Driver Online / Offline Switch Card
              _buildStatusCard(driverCtrl),
              const SizedBox(height: 10),

              // 2. Rating & Feedback Header Card
              _buildRatingCard(context, driverCtrl),
              const SizedBox(height: 10),

              // 3. Quick Stats Metric Cards Row
              _buildMetricsRow(driverCtrl),
              const SizedBox(height: 12),

              // 4. Interactive Radar Map Card Header
              Container(
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
                  children: [
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
                                      const Text(
                                        'Radar Peta GPS Cicalengka',
                                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF0F172A)),
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
                                          child: const Text('Live Ikuti GPS', style: TextStyle(fontSize: 8.5, fontWeight: FontWeight.bold, color: Color(0xFF16A34A))),
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
                                        'Lat: ${driverCtrl.currentLocation.latitude.toStringAsFixed(6)} | Lng: ${driverCtrl.currentLocation.longitude.toStringAsFixed(6)}',
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
                              color: const Color(0xFFFEF2F2),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: const Color(0xFFFCA5A5)),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.my_location_rounded, color: AppTheme.primaryRed, size: 12),
                                const SizedBox(width: 4),
                                Text(
                                  '${driverCtrl.availableOrders.length} Order',
                                  style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const Divider(height: 1, color: Color(0xFFF1F5F9)),

                    // Map Container
                    SizedBox(
                      height: 230,
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
                                  errorTileCallback: (tile, error, stackTrace) {
                                    // Silent handling for aborted/cancelled tile requests
                                  },
                                  evictErrorTileStrategy: EvictErrorTileStrategy.none,
                                ),
                                MarkerLayer(
                                  markers: [
                                    // Driver location marker with live compass rotation
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
                                            angle: (driverCtrl.heading) * (3.141592653589793 / 180),
                                            child: Container(
                                              width: 32,
                                              height: 32,
                                              decoration: const BoxDecoration(
                                                color: Color(0xFF16A34A),
                                                shape: BoxShape.circle,
                                                boxShadow: [BoxShadow(color: Color(0x6616A34A), blurRadius: 10)],
                                              ),
                                              child: const Icon(Icons.navigation_rounded, color: Colors.white, size: 18),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),

                                    // Available Order Store Markers
                                    ...driverCtrl.availableOrders.map((ord) {
                                      final lat = double.tryParse(ord['store_lat']?.toString() ?? '') ?? driverCtrl.currentLocation.latitude + 0.002;
                                      final lng = double.tryParse(ord['store_lng']?.toString() ?? '') ?? driverCtrl.currentLocation.longitude + 0.002;
                                      return Marker(
                                        point: LatLng(lat, lng),
                                        width: 38,
                                        height: 38,
                                        child: Container(
                                          decoration: BoxDecoration(
                                            color: AppTheme.primaryRed,
                                            shape: BoxShape.circle,
                                            boxShadow: [
                                              BoxShadow(color: AppTheme.primaryRed.withValues(alpha: 0.4), blurRadius: 8),
                                            ],
                                          ),
                                          child: const Icon(Icons.store_rounded, color: Colors.white, size: 18),
                                        ),
                                      );
                                    }),
                                  ],
                                ),
                              ],
                            ),

                            // Top Map HUD (Live GPS Coordinate Stream Bar)
                            Positioned(
                              top: 8,
                              left: 8,
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: Colors.black.withValues(alpha: 0.75),
                                  borderRadius: BorderRadius.circular(10),
                                  border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const Icon(Icons.satellite_alt_rounded, color: Color(0xFF4ADE80), size: 12),
                                    const SizedBox(width: 5),
                                    Text(
                                      '${driverCtrl.currentLocation.latitude.toStringAsFixed(6)}, ${driverCtrl.currentLocation.longitude.toStringAsFixed(6)}',
                                      style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.white, fontFamily: 'monospace'),
                                    ),
                                    if (driverCtrl.heading > 0) ...[
                                      const SizedBox(width: 5),
                                      Text(
                                        '(${driverCtrl.heading.toInt()}°)',
                                        style: const TextStyle(fontSize: 9, color: Color(0xFF86EFAC)),
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                            ),

                            // Floating Re-center GPS Button with Auto-Follow Indicator
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
              ),
              const SizedBox(height: 16),

              // 5. Available Orders Header & List
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.inventory_2_rounded, color: AppTheme.primaryRed, size: 18),
                      SizedBox(width: 6),
                      Text(
                        'Daftar Orderan Siap Ambil',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Color(0xFF0F172A)),
                      ),
                    ],
                  ),
                  if (driverCtrl.isLoading)
                    const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.primaryRed)),
                ],
              ),
              const SizedBox(height: 8),

              if (!driverCtrl.isOnline)
                _buildOfflineWarning()
              else if (driverCtrl.availableOrders.isEmpty)
                _buildEmptyOrdersState()
              else
                ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: driverCtrl.availableOrders.length,
                  separatorBuilder: (ctx, index) => const SizedBox(height: 10),
                  itemBuilder: (ctx, idx) => _buildOrderCard(ctx, driverCtrl, driverCtrl.availableOrders[idx]),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusCard(DriverController driverCtrl) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: driverCtrl.isOnline ? const Color(0xFFDCFCE7) : const Color(0xFFF1F5F9),
              shape: BoxShape.circle,
            ),
            child: Icon(
              driverCtrl.isOnline ? Icons.sensors_rounded : Icons.pause_circle_rounded,
              color: driverCtrl.isOnline ? const Color(0xFF16A34A) : const Color(0xFF64748B),
              size: 22,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Text('Status Kurir: ', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A))),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: driverCtrl.isOnline ? const Color(0xFF16A34A) : const Color(0xFF64748B),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        driverCtrl.isOnline ? '● ONLINE (SIAP ANTAR)' : '● OFFLINE (ISTIRAHAT)',
                        style: const TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  driverCtrl.isOnline ? 'GPS aktif: Memindai orderan Cicalengka' : 'Aktifkan status untuk menerima pesanan',
                  style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                ),
              ],
            ),
          ),
          Switch(
            value: driverCtrl.isOnline,
            activeTrackColor: const Color(0xFF16A34A).withValues(alpha: 0.5),
            activeThumbColor: const Color(0xFF16A34A),
            onChanged: (val) => driverCtrl.toggleOnline(val),
          ),
        ],
      ),
    );
  }

  Widget _buildRatingCard(BuildContext context, DriverController driverCtrl) {
    final rating = driverCtrl.driverRating;
    final reviewsCount = driverCtrl.reviews.length;

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: const Color(0xFFFEF3C7),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.star_rounded, color: Color(0xFFF59E0B), size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Text('Rating Driver Anda', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF0F172A))),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFEF3C7),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: const Color(0xFFFCD34D)),
                      ),
                      child: Text(
                        '⭐ ${rating.toStringAsFixed(1)} / 5.0',
                        style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF92400E)),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  'Total $reviewsCount ulasan kepuasan pengantaran',
                  style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                ),
              ],
            ),
          ),
          OutlinedButton(
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              side: const BorderSide(color: Color(0xFFFBBF24)),
              backgroundColor: const Color(0xFFFFFBEB),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            ),
            onPressed: () => widget.onNavigateTab?.call(2),
            child: const Row(
              children: [
                Text('Rincian', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF92400E))),
                Icon(Icons.chevron_right_rounded, size: 14, color: Color(0xFF92400E)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMetricsRow(DriverController driverCtrl) {
    return Row(
      children: [
        // Dompet Card
        Expanded(
          child: GestureDetector(
            onTap: () => widget.onNavigateTab?.call(1),
            child: Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Dompet', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF64748B))),
                      Container(
                        width: 20,
                        height: 20,
                        decoration: const BoxDecoration(color: Color(0xFFFEE2E2), shape: BoxShape.circle),
                        child: const Icon(Icons.account_balance_wallet_rounded, color: AppTheme.primaryRed, size: 11),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    CurrencyFormatter.formatRupiah(driverCtrl.walletBalance),
                    style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  const Row(
                    children: [
                      Text('Saldo ', style: TextStyle(fontSize: 9.5, color: Color(0xFF64748B))),
                      Icon(Icons.chevron_right_rounded, size: 10, color: AppTheme.primaryRed),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
        const SizedBox(width: 8),

        // Selesai Card
        Expanded(
          child: Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Selesai', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF64748B))),
                    Container(
                      width: 20,
                      height: 20,
                      decoration: const BoxDecoration(color: Color(0xFFDCFCE7), shape: BoxShape.circle),
                      child: const Icon(Icons.check_circle_rounded, color: Color(0xFF16A34A), size: 11),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Text('${driverCtrl.totalOrders} ', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                    const Text('Order', style: TextStyle(fontSize: 9.5, color: Color(0xFF64748B))),
                  ],
                ),
                const SizedBox(height: 2),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                  decoration: BoxDecoration(
                    color: const Color(0xFFDCFCE7),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Text('100% Selesai', style: TextStyle(fontSize: 8.5, fontWeight: FontWeight.bold, color: Color(0xFF16A34A))),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(width: 8),

        // Rating Card
        Expanded(
          child: Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Rating', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF64748B))),
                    Container(
                      width: 20,
                      height: 20,
                      decoration: const BoxDecoration(color: Color(0xFFFEF3C7), shape: BoxShape.circle),
                      child: const Icon(Icons.star_rounded, color: Color(0xFFF59E0B), size: 11),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Text('${driverCtrl.driverRating.toStringAsFixed(1)} ', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                    const Text('★', style: TextStyle(fontSize: 11, color: Color(0xFFF59E0B))),
                  ],
                ),
                const SizedBox(height: 2),
                Text('${driverCtrl.reviews.length} Ulasan', style: const TextStyle(fontSize: 9.5, color: Color(0xFF64748B))),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildOfflineWarning() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: const Center(
        child: Column(
          children: [
            Icon(Icons.pause_circle_filled_rounded, size: 48, color: Color(0xFF94A3B8)),
            SizedBox(height: 10),
            Text(
              'Status Kurir Sedang Offline',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Color(0xFF0F172A)),
            ),
            SizedBox(height: 4),
            Text(
              'Aktifkan sakelar ONLINE di atas untuk mulai menerima orderan baru.',
              style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyOrdersState() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: const Center(
        child: Column(
          children: [
            Icon(Icons.search_off_rounded, size: 44, color: Color(0xFF94A3B8)),
            SizedBox(height: 10),
            Text(
              'Mencari Orderan Terdekat...',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
            ),
            SizedBox(height: 4),
            Text(
              'Belum ada orderan baru yang masuk di zona Cicalengka saat ini. Tetap online!',
              style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildOrderCard(BuildContext context, DriverController driverCtrl, Map<String, dynamic> order) {
    final fee = double.tryParse(order['delivery_charge']?.toString() ?? '5000') ?? 5000.0;
    final distance = order['distance']?.toString() ?? '~2';
    final orderCode = order['order_code'] ?? order['id'];
    final storeName = order['store_name'] ?? 'Mitra Resto';
    final storeAddress = order['store_address'] ?? 'Cicalengka';
    final deliveryAddress = (order['delivery_address'] is Map)
        ? (order['delivery_address']['address'] ?? 'Cicalengka')
        : (order['delivery_address'] ?? 'Cicalengka');
    final orderId = int.tryParse(order['id']?.toString() ?? '0') ?? 0;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Order Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEE2E2),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '#$orderCode',
                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                ),
              ),
              Row(
                children: [
                  const Icon(Icons.near_me_rounded, size: 12, color: Color(0xFF64748B)),
                  const SizedBox(width: 4),
                  Text('$distance km', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF64748B))),
                  const SizedBox(width: 10),
                  Text(
                    CurrencyFormatter.formatRupiah(fee),
                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: Color(0xFF16A34A)),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 10),

          // Pickup Location
          Row(
            children: [
              const Icon(Icons.storefront_rounded, color: AppTheme.primaryRed, size: 16),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(storeName, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                    Text(storeAddress, style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)), overflow: TextOverflow.ellipsis),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),

          // Dropoff Location
          Row(
            children: [
              const Icon(Icons.location_on_rounded, color: Color(0xFF16A34A), size: 16),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Alamat Pengantaran', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                    Text(deliveryAddress.toString(), style: const TextStyle(fontSize: 11, color: Color(0xFF0F172A)), overflow: TextOverflow.ellipsis),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Accept Order Button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.inkBlack,
                foregroundColor: AppTheme.onPrimary,
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: const StadiumBorder(),
                elevation: 0,
              ),
              onPressed: () async {
                final ok = await driverCtrl.acceptOrder(orderId);
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(ok
                          ? '✅ Orderan berhasil diterima! Segera meluncur.'
                          : (driverCtrl.lastErrorMessage ?? 'Gagal mengambil orderan.')),
                      backgroundColor: ok ? Colors.green : Colors.red,
                    ),
                  );
                }
              },
              icon: const Icon(Icons.two_wheeler_rounded, size: 18),
              label: const Text('AMBIL ORDERAN SEKARANG', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
            ),
          ),
        ],
      ),
    );
  }
}
