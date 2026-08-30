import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/constants/zone_constants.dart';
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
        backgroundColor: Color(0xFF090D16),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircularProgressIndicator(color: AppTheme.primaryRed),
              SizedBox(height: 12),
              Text(
                'Memeriksa status driver & pesanan aktif...',
                style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
      );
    }

    return Container(
      color: const Color(0xFF090D16),
      child: RefreshIndicator(
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
                    color: const Color(0xFF0F172A),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFF1E293B)),
                    boxShadow: const [
                      BoxShadow(
                        color: Colors.black26,
                        blurRadius: 10,
                        offset: Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Column(
                    children: [
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        child: Row(
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
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Wrap(
                                    crossAxisAlignment: WrapCrossAlignment.center,
                                    spacing: 6,
                                    children: [
                                      const Text(
                                        'Radar Peta GPS',
                                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.white),
                                      ),
                                      if (_autoFollow)
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFF064E3B),
                                            borderRadius: BorderRadius.circular(8),
                                            border: Border.all(color: const Color(0xFF059669)),
                                          ),
                                          child: const Text('Live GPS', style: TextStyle(fontSize: 8, fontWeight: FontWeight.bold, color: Color(0xFF34D399))),
                                        ),
                                    ],
                                  ),
                                  Row(
                                    children: [
                                      Container(
                                        width: 6,
                                        height: 6,
                                        margin: const EdgeInsets.only(right: 4),
                                        decoration: const BoxDecoration(color: Color(0xFF22C55E), shape: BoxShape.circle),
                                      ),
                                      Expanded(
                                        child: Text(
                                          'Lat: ${driverCtrl.currentLocation.latitude.toStringAsFixed(4)} | Lng: ${driverCtrl.currentLocation.longitude.toStringAsFixed(4)}',
                                          style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Color(0xFF34D399), fontFamily: 'monospace'),
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: const Color(0xFF450A0A),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: const Color(0xFF7F1D1D)),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.my_location_rounded, color: Color(0xFFFCA5A5), size: 12),
                                  const SizedBox(width: 4),
                                  Text(
                                    '${driverCtrl.availableOrders.length} Order',
                                    style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFFFCA5A5)),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Divider(height: 1, color: Color(0xFF1E293B)),

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
                                  minZoom: 11.0,
                                  maxZoom: 18.0,
                                  onPositionChanged: (pos, hasGesture) {
                                    if (hasGesture && _autoFollow) {
                                      setState(() => _autoFollow = false);
                                    }
                                  },
                                ),
                                children: [
                                  TileLayer(
                                    urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                                    userAgentPackageName: 'com.cicalengkago.mobile',
                                    maxZoom: 19,
                                    evictErrorTileStrategy: EvictErrorTileStrategy.none,
                                  ),
                                  // Cakupan Wilayah Operasional Zona Cicalengka Raya
                                  ZoneConstants.buildZonePolygonLayer(
                                    label: 'Zona Cicalengka Raya',
                                    fillColor: const Color(0x143B82F6),
                                    borderColor: const Color(0xFF3B82F6),
                                    strokeWidth: 2.0,
                                  ),
                                  CircleLayer(
                                    circles: [
                                      CircleMarker(
                                        point: driverCtrl.currentLocation,
                                        radius: 55,
                                        useRadiusInMeter: false,
                                        color: const Color(0xFF16A34A).withValues(alpha: 0.15),
                                        borderColor: const Color(0xFF16A34A),
                                        borderStrokeWidth: 1.5,
                                      ),
                                    ],
                                  ),
                                  MarkerLayer(
                                    markers: [
                                      // Driver location marker with Bogo helmet mascot & live compass rotation
                                      Marker(
                                        point: driverCtrl.currentLocation,
                                        width: 52,
                                        height: 52,
                                        child: Stack(
                                          alignment: Alignment.center,
                                          children: [
                                            Container(
                                              width: 52,
                                              height: 52,
                                              decoration: BoxDecoration(
                                                color: const Color(0xFF16A34A).withValues(alpha: 0.2),
                                                shape: BoxShape.circle,
                                              ),
                                            ),
                                            Transform.rotate(
                                              angle: (driverCtrl.heading) * (3.141592653589793 / 180),
                                              child: Container(
                                                width: 42,
                                                height: 42,
                                                decoration: const BoxDecoration(
                                                  color: Colors.white,
                                                  shape: BoxShape.circle,
                                                  boxShadow: [
                                                    BoxShadow(color: Color(0x4416A34A), blurRadius: 8, spreadRadius: 1),
                                                  ],
                                                ),
                                                child: ClipOval(
                                                  child: Image.asset(
                                                    'assets/images/driver_bogo_marker.png',
                                                    fit: BoxFit.cover,
                                                    errorBuilder: (context, error, stackTrace) => Container(
                                                      color: const Color(0xFF16A34A),
                                                      child: const Icon(Icons.navigation_rounded, color: Colors.white, size: 18),
                                                    ),
                                                  ),
                                                ),
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
                                          width: 70,
                                          height: 45,
                                          child: Column(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFF1E293B),
                                                  borderRadius: BorderRadius.circular(6),
                                                ),
                                                child: Text(
                                                  ord['store_name'] ?? 'Toko',
                                                  style: const TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold),
                                                  maxLines: 1,
                                                  overflow: TextOverflow.ellipsis,
                                                ),
                                              ),
                                              const SizedBox(height: 2),
                                              Container(
                                                width: 22,
                                                height: 22,
                                                decoration: const BoxDecoration(
                                                  color: AppTheme.primaryRed,
                                                  shape: BoxShape.circle,
                                                  boxShadow: [BoxShadow(color: Colors.black26, blurRadius: 4)],
                                                ),
                                                child: const Icon(Icons.storefront_rounded, color: Colors.white, size: 12),
                                              ),
                                            ],
                                          ),
                                        );
                                      }),
                                    ],
                                  ),
                                ],
                              ),

                              // Radar Status Pill HUD
                              Positioned(
                                top: 10,
                                left: 10,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF0F172A).withValues(alpha: 0.85),
                                    borderRadius: BorderRadius.circular(16),
                                    border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
                                  ),
                                  child: Row(
                                    children: [
                                      Container(
                                        width: 6,
                                        height: 6,
                                        decoration: BoxDecoration(
                                          color: driverCtrl.isOnline ? const Color(0xFF22C55E) : const Color(0xFFEF4444),
                                          shape: BoxShape.circle,
                                        ),
                                      ),
                                      const SizedBox(width: 5),
                                      Text(
                                        driverCtrl.isOnline ? 'Radar Scan Aktif' : 'GPS Offline',
                                        style: TextStyle(
                                          color: driverCtrl.isOnline ? const Color(0xFF4ADE80) : const Color(0xFFF87171),
                                          fontSize: 9.5,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),

                              // Dynamic Real-time Coordinates HUD
                              Positioned(
                                top: 10,
                                right: 10,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF0F172A).withValues(alpha: 0.85),
                                    borderRadius: BorderRadius.circular(14),
                                    border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
                                  ),
                                  child: Row(
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
                                  color: _autoFollow ? const Color(0xFF16A34A) : const Color(0xFF1E293B),
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
                                        color: _autoFollow ? Colors.white : const Color(0xFF94A3B8),
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
                        Icon(Icons.inventory_2_rounded, color: Color(0xFFEF4444), size: 18),
                        SizedBox(width: 6),
                        Text(
                          'Daftar Orderan Siap Ambil',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Colors.white),
                        ),
                      ],
                    ),
                    if (driverCtrl.isLoading)
                      const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFFEF4444))),
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
      ),
    );
  }

  Widget _buildStatusCard(DriverController driverCtrl) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E293B)),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 8,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: driverCtrl.isOnline ? const Color(0xFF064E3B) : const Color(0xFF1E293B),
              shape: BoxShape.circle,
            ),
            child: Icon(
              driverCtrl.isOnline ? Icons.sensors_rounded : Icons.pause_circle_rounded,
              color: driverCtrl.isOnline ? const Color(0xFF34D399) : const Color(0xFF94A3B8),
              size: 22,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Wrap(
                  crossAxisAlignment: WrapCrossAlignment.center,
                  spacing: 6,
                  children: [
                    const Text('Status Kurir:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.white)),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: driverCtrl.isOnline ? const Color(0xFF16A34A) : const Color(0xFF334155),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        driverCtrl.isOnline ? 'ONLINE' : 'OFFLINE',
                        style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  driverCtrl.isOnline ? 'GPS aktif memindai orderan' : 'Aktifkan untuk terima pesanan',
                  style: const TextStyle(fontSize: 10.5, color: Color(0xFF94A3B8)),
                  overflow: TextOverflow.ellipsis,
                  maxLines: 1,
                ),
              ],
            ),
          ),
          Switch(
            value: driverCtrl.isOnline,
            activeTrackColor: const Color(0xFF22C55E).withValues(alpha: 0.5),
            activeThumbColor: const Color(0xFF22C55E),
            inactiveThumbColor: const Color(0xFF64748B),
            inactiveTrackColor: const Color(0xFF1E293B),
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
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E293B)),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 8,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: const BoxDecoration(
              color: Color(0xFF451A03),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.star_rounded, color: Color(0xFFFBBF24), size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Wrap(
                  crossAxisAlignment: WrapCrossAlignment.center,
                  spacing: 6,
                  children: [
                    const Text('Rating Driver', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.white)),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                      decoration: BoxDecoration(
                        color: const Color(0xFF78350F),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: const Color(0xFFB45309)),
                      ),
                      child: Text(
                        '⭐ ${rating.toStringAsFixed(1)}',
                        style: const TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: Color(0xFFFDE68A)),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  '$reviewsCount ulasan kepuasan',
                  style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                  overflow: TextOverflow.ellipsis,
                  maxLines: 1,
                ),
              ],
            ),
          ),
          OutlinedButton(
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              side: const BorderSide(color: Color(0xFFB45309)),
              backgroundColor: const Color(0xFF451A03),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            ),
            onPressed: () => widget.onNavigateTab?.call(3),
            child: const Row(
              children: [
                Text('Rincian', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFFFDE68A))),
                Icon(Icons.chevron_right_rounded, size: 14, color: Color(0xFFFDE68A)),
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
            onTap: () => widget.onNavigateTab?.call(2),
            child: Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFF1E293B)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Dompet', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF94A3B8))),
                      Container(
                        width: 20,
                        height: 20,
                        decoration: const BoxDecoration(color: Color(0xFF450A0A), shape: BoxShape.circle),
                        child: const Icon(Icons.account_balance_wallet_rounded, color: Color(0xFFF87171), size: 11),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    CurrencyFormatter.formatRupiah(driverCtrl.walletBalance),
                    style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFFF87171)),
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  const Row(
                    children: [
                      Text('Saldo ', style: TextStyle(fontSize: 9.5, color: Color(0xFF94A3B8))),
                      Icon(Icons.chevron_right_rounded, size: 10, color: Color(0xFFF87171)),
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
          child: GestureDetector(
            onTap: () => widget.onNavigateTab?.call(1),
            child: Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFF1E293B)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Selesai', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF94A3B8))),
                      Container(
                        width: 20,
                        height: 20,
                        decoration: const BoxDecoration(color: Color(0xFF064E3B), shape: BoxShape.circle),
                        child: const Icon(Icons.check_circle_rounded, color: Color(0xFF34D399), size: 11),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Text('${driverCtrl.totalOrders} ', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Colors.white)),
                      const Text('Order', style: TextStyle(fontSize: 9.5, color: Color(0xFF94A3B8))),
                    ],
                  ),
                  const SizedBox(height: 2),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                    decoration: BoxDecoration(
                      color: const Color(0xFF064E3B),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Text('Lihat Riwayat', style: TextStyle(fontSize: 8.5, fontWeight: FontWeight.bold, color: Color(0xFF34D399))),
                  ),
                ],
              ),
            ),
          ),
        ),
        const SizedBox(width: 8),

        // Rating Card
        Expanded(
          child: Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: const Color(0xFF0F172A),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFF1E293B)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Rating', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF94A3B8))),
                    Container(
                      width: 20,
                      height: 20,
                      decoration: const BoxDecoration(color: Color(0xFF451A03), shape: BoxShape.circle),
                      child: const Icon(Icons.star_rounded, color: Color(0xFFFBBF24), size: 11),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Text('${driverCtrl.driverRating.toStringAsFixed(1)} ', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Colors.white)),
                    const Text('★', style: TextStyle(fontSize: 11, color: Color(0xFFFBBF24))),
                  ],
                ),
                const SizedBox(height: 2),
                Text('${driverCtrl.reviews.length} Ulasan', style: const TextStyle(fontSize: 9.5, color: Color(0xFF94A3B8))),
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
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E293B)),
      ),
      child: const Center(
        child: Column(
          children: [
            Icon(Icons.pause_circle_filled_rounded, size: 48, color: Color(0xFF64748B)),
            SizedBox(height: 10),
            Text(
              'Status Kurir Sedang Offline',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Colors.white),
            ),
            SizedBox(height: 4),
            Text(
              'Aktifkan sakelar ONLINE di atas untuk mulai menerima orderan baru.',
              style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
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
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E293B)),
      ),
      child: const Center(
        child: Column(
          children: [
            Icon(Icons.search_off_rounded, size: 44, color: Color(0xFF64748B)),
            SizedBox(height: 10),
            Text(
              'Mencari Orderan Terdekat...',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.white),
            ),
            SizedBox(height: 4),
            Text(
              'Belum ada orderan baru yang masuk di zona Cicalengka saat ini. Tetap online!',
              style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
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
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E293B)),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 8,
            offset: Offset(0, 2),
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
                  color: const Color(0xFF450A0A),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '#$orderCode',
                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFFFCA5A5)),
                ),
              ),
              Row(
                children: [
                  const Icon(Icons.near_me_rounded, size: 12, color: Color(0xFF94A3B8)),
                  const SizedBox(width: 4),
                  Text('$distance km', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF94A3B8))),
                  const SizedBox(width: 10),
                  Text(
                    CurrencyFormatter.formatRupiah(fee),
                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: Color(0xFF4ADE80)),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 8),

          // Zone & Tariff Info Chip
          Builder(
            builder: (context) {
              final double zMin = double.tryParse(order['min_delivery_charge']?.toString() ?? '5000') ?? 5000.0;
              final double zPerKm = double.tryParse(order['per_km_delivery_charge']?.toString() ?? '2500') ?? 2500.0;
              final String zName = order['zone_name']?.toString() ?? 'Zona Cicalengka Raya';

              return Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E293B),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: const Color(0xFF334155)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.shield_outlined, color: Color(0xFF60A5FA), size: 12),
                        const SizedBox(width: 4),
                        Text(
                          zName,
                          style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF93C5FD)),
                        ),
                      ],
                    ),
                    Text(
                      'Dasar ${CurrencyFormatter.formatRupiah(zMin)} + ${CurrencyFormatter.formatRupiah(zPerKm)}/km',
                      style: const TextStyle(fontSize: 9.5, color: Color(0xFF94A3B8)),
                    ),
                  ],
                ),
              );
            },
          ),
          const SizedBox(height: 8),

          // Pickup Location
          Row(
            children: [
              const Icon(Icons.storefront_rounded, color: Color(0xFFEF4444), size: 16),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(storeName, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
                    Text(storeAddress, style: const TextStyle(fontSize: 10.5, color: Color(0xFF94A3B8)), overflow: TextOverflow.ellipsis),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),

          // Dropoff Location
          Row(
            children: [
              const Icon(Icons.location_on_rounded, color: Color(0xFF22C55E), size: 16),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Alamat Pengantaran', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF94A3B8))),
                    Text(deliveryAddress.toString(), style: const TextStyle(fontSize: 11, color: Colors.white), overflow: TextOverflow.ellipsis),
                  ],
                ),
              ),
            ],
          ),

          // Items Preview List with Multi-Store Grouping
          if (order['items'] is List && (order['items'] as List).isNotEmpty ||
              order['all_items'] is List && (order['all_items'] as List).isNotEmpty) ...[
            () {
              final List rawList = ((order['items'] ?? order['all_items']) as List);
              final Map<String, List<dynamic>> grouped = {};
              for (var it in rawList) {
                if (it is Map) {
                  final s = (it['store_name'] ?? order['store_name'] ?? 'Mitra Resto').toString();
                  if (!grouped.containsKey(s)) grouped[s] = [];
                  grouped[s]!.add(it);
                }
              }
              final bool isMulti = grouped.length > 1;

              return Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1E293B),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFF334155)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(
                            isMulti ? Icons.store_mall_directory_rounded : Icons.shopping_bag_outlined,
                            size: 13,
                            color: const Color(0xFFFBBF24),
                          ),
                          const SizedBox(width: 5),
                          Text(
                            isMulti ? 'Pesanan (${grouped.length} Toko • ${rawList.length} Menu):' : 'Pesanan (${rawList.length} Menu):',
                            style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFFFDE68A)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      ...grouped.entries.map((entry) {
                        final gStoreName = entry.key;
                        final gItems = entry.value;

                        return Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Padding(
                              padding: const EdgeInsets.only(top: 4, bottom: 3),
                              child: Row(
                                children: [
                                  const Icon(Icons.storefront_rounded, size: 12, color: Color(0xFF93C5FD)),
                                  const SizedBox(width: 4),
                                  Expanded(
                                    child: Text(
                                      gStoreName,
                                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF93C5FD)),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            ...gItems.take(2).map((item) {
                              if (item is! Map) return const SizedBox.shrink();
                              final iName = item['product_name'] ?? item['item_name'] ?? item['name'] ?? 'Menu';
                              final iQty = item['quantity'] ?? '1';
                              return Padding(
                                padding: const EdgeInsets.only(left: 4, bottom: 2),
                                child: Text(
                                  '• ${iQty}x $iName',
                                  style: const TextStyle(fontSize: 11, color: Color(0xFFE2E8F0)),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              );
                            }),
                            if (gItems.length > 2) ...[
                              Padding(
                                padding: const EdgeInsets.only(left: 4, bottom: 2),
                                child: Text(
                                  '+ ${gItems.length - 2} menu lainnya...',
                                  style: const TextStyle(fontSize: 9.5, color: Color(0xFF94A3B8), fontStyle: FontStyle.italic),
                                ),
                              ),
                            ],
                          ],
                        );
                      }),
                    ],
                  ),
                ),
              );
            }(),
          ],

          const SizedBox(height: 12),

          // Accept Order Button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFEF4444),
                foregroundColor: Colors.white,
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
