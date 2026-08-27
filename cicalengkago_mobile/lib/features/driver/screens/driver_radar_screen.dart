import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/driver_controller.dart';
import 'active_trip_screen.dart';

class DriverRadarScreen extends StatelessWidget {
  const DriverRadarScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final driverCtrl = context.watch<DriverController>();

    if (!driverCtrl.isOnline) {
      return _buildOfflineView();
    }

    if (driverCtrl.activeTrip != null) {
      return ActiveTripScreen(trip: driverCtrl.activeTrip!);
    }

    return Column(
      children: [
        // Live radar map
        Expanded(
          flex: 4,
          child: Stack(
            children: [
              FlutterMap(
                options: MapOptions(
                  initialCenter: driverCtrl.currentLocation,
                  initialZoom: 15.0,
                ),
                children: [
                  TileLayer(
                    urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                    userAgentPackageName: 'com.cicalengkago.mobile',
                  ),
                  MarkerLayer(
                    markers: [
                      // Driver position
                      Marker(
                        point: driverCtrl.currentLocation,
                        width: 54,
                        height: 54,
                        child: Stack(
                          alignment: Alignment.center,
                          children: [
                            // Pulse ring
                            Container(
                              width: 54,
                              height: 54,
                              decoration: BoxDecoration(
                                color: const Color(0xFF16A34A).withOpacity(0.15),
                                shape: BoxShape.circle,
                              ),
                            ),
                            Container(
                              width: 36,
                              height: 36,
                              decoration: const BoxDecoration(
                                color: Color(0xFF16A34A),
                                shape: BoxShape.circle,
                                boxShadow: [BoxShadow(color: Color(0x6616A34A), blurRadius: 12)],
                              ),
                              child: const Icon(Icons.navigation_rounded, color: Colors.white, size: 20),
                            ),
                          ],
                        ),
                      ),

                      // Order markers on map
                      ...driverCtrl.availableOrders.map((order) {
                        final lat = double.tryParse(order['store_lat']?.toString() ?? '') ?? driverCtrl.currentLocation.latitude + 0.002;
                        final lng = double.tryParse(order['store_lng']?.toString() ?? '') ?? driverCtrl.currentLocation.longitude + 0.002;
                        return Marker(
                          point: LatLng(lat, lng),
                          width: 44,
                          height: 44,
                          child: Container(
                            decoration: BoxDecoration(
                              color: AppTheme.primaryRed,
                              shape: BoxShape.circle,
                              boxShadow: [BoxShadow(color: AppTheme.primaryRed.withOpacity(0.4), blurRadius: 10)],
                            ),
                            child: const Icon(Icons.restaurant_rounded, color: Colors.white, size: 22),
                          ),
                        );
                      }),
                    ],
                  ),
                ],
              ),

              // Map overlay: Order count badge
              Positioned(
                top: 12,
                right: 12,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 10)],
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.local_dining_rounded, color: AppTheme.primaryRed, size: 14),
                      const SizedBox(width: 5),
                      Text(
                        '${driverCtrl.availableOrders.length} Order Dekat',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),

        // Available orders list
        Expanded(
          flex: 5,
          child: Container(
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
              boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 12, offset: Offset(0, -4))],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Handle bar + header
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                  child: Column(
                    children: [
                      Center(
                        child: Container(
                          width: 40,
                          height: 4,
                          decoration: BoxDecoration(
                            color: const Color(0xFFE2E8F0),
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(6),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFFEE2E2),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(Icons.radar_rounded, color: AppTheme.primaryRed, size: 16),
                              ),
                              const SizedBox(width: 8),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Orderan Masuk (${driverCtrl.availableOrders.length})',
                                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                                  ),
                                  const Text(
                                    'Orderan terdekat di Cicalengka',
                                    style: TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                                  ),
                                ],
                              ),
                            ],
                          ),
                          Row(
                            children: [
                              if (driverCtrl.isLoading)
                                const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.primaryRed)),
                              const SizedBox(width: 8),
                              GestureDetector(
                                onTap: () => driverCtrl.fetchRadarData(),
                                child: Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF1F5F9),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: const Icon(Icons.refresh_rounded, size: 18, color: Color(0xFF64748B)),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ],
                  ),
                ),

                // Order list
                Expanded(
                  child: driverCtrl.availableOrders.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Container(
                                width: 64,
                                height: 64,
                                decoration: const BoxDecoration(color: Color(0xFFF1F5F9), shape: BoxShape.circle),
                                child: const Icon(Icons.search_rounded, color: Color(0xFF94A3B8), size: 30),
                              ),
                              const SizedBox(height: 12),
                              const Text('Mencari Pesanan Terdekat...', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                              const SizedBox(height: 4),
                              const Text('Pastikan Anda berada di area Cicalengka', style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
                            ],
                          ),
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 20),
                          itemCount: driverCtrl.availableOrders.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 10),
                          itemBuilder: (ctx, i) => _buildOrderCard(ctx, driverCtrl, driverCtrl.availableOrders[i]),
                        ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildOrderCard(BuildContext context, DriverController driverCtrl, Map<String, dynamic> order) {
    final fee = double.tryParse(order['delivery_charge']?.toString() ?? '5000') ?? 5000.0;
    final distance = order['distance']?.toString() ?? '~2';
    final items = (order['items'] as List?) ?? [];
    final itemCount = items.isNotEmpty ? items.length : (int.tryParse(order['items_count']?.toString() ?? '1') ?? 1);
    final orderCode = order['order_code'] ?? order['id'];
    final storeName = order['store_name'] ?? 'Mitra Resto';
    final deliveryAddress = order['delivery_address'] ?? 'Cicalengka';

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header row
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 32,
                          height: 32,
                          decoration: BoxDecoration(
                            color: const Color(0xFFFEE2E2),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Icon(Icons.receipt_rounded, color: AppTheme.primaryRed, size: 16),
                        ),
                        const SizedBox(width: 8),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('#$orderCode', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                            Text('$itemCount item', style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
                          ],
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(colors: [Color(0xFF059669), Color(0xFF047857)]),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        CurrencyFormatter.formatRupiah(fee),
                        style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Store & destination
                _locationRow(Icons.storefront_rounded, storeName, const Color(0xFF2563EB)),
                const SizedBox(height: 6),
                _locationRow(Icons.home_rounded, deliveryAddress, const Color(0xFF059669)),

                // Distance
                const SizedBox(height: 8),
                Row(
                  children: [
                    const Icon(Icons.directions_rounded, size: 13, color: Color(0xFF94A3B8)),
                    const SizedBox(width: 4),
                    Text('$distance km dari posisi Anda', style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
                  ],
                ),
              ],
            ),
          ),

          // Accept button
          Container(
            decoration: const BoxDecoration(
              border: Border(top: BorderSide(color: Color(0xFFF1F5F9))),
            ),
            child: Padding(
              padding: const EdgeInsets.fromLTRB(14, 10, 14, 14),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primaryRed,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 0,
                  ),
                  onPressed: () async {
                    final orderId = int.tryParse(order['id']?.toString() ?? '0') ?? 0;
                    final ok = await driverCtrl.acceptOrder(orderId);
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(ok ? '✅ Orderan berhasil diambil!' : '❌ Gagal ambil orderan.'),
                          backgroundColor: ok ? Colors.green : Colors.red,
                        ),
                      );
                    }
                  },
                  child: const Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.check_circle_outline_rounded, size: 18, color: Colors.white),
                      SizedBox(width: 8),
                      Text('TERIMA ORDERAN INI', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white)),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _locationRow(IconData icon, String label, Color color) {
    return Row(
      children: [
        Container(
          width: 24,
          height: 24,
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: color, size: 13),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            label,
            style: const TextStyle(fontSize: 12, color: Color(0xFF334155), fontWeight: FontWeight.w500),
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }

  Widget _buildOfflineView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 100,
              height: 100,
              decoration: const BoxDecoration(
                color: Color(0xFFFEE2E2),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.power_settings_new_rounded, size: 50, color: AppTheme.primaryRed),
            ),
            const SizedBox(height: 24),
            const Text(
              'Anda Sedang OFFLINE',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 8),
            const Text(
              'Aktifkan tombol online di atas\nuntuk mulai menerima orderan pengantaran.',
              style: TextStyle(fontSize: 13, color: Color(0xFF64748B)),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: const Column(
                children: [
                  _TipRow(icon: Icons.location_on_rounded, text: 'Pastikan GPS aktif'),
                  SizedBox(height: 8),
                  _TipRow(icon: Icons.wifi_rounded, text: 'Pastikan koneksi internet stabil'),
                  SizedBox(height: 8),
                  _TipRow(icon: Icons.area_chart_rounded, text: 'Berada di wilayah Cicalengka & sekitarnya'),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TipRow extends StatelessWidget {
  final IconData icon;
  final String text;
  const _TipRow({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 28,
          height: 28,
          decoration: BoxDecoration(color: const Color(0xFFF1F5F9), shape: BoxShape.circle),
          child: Icon(icon, color: const Color(0xFF64748B), size: 14),
        ),
        const SizedBox(width: 10),
        Text(text, style: const TextStyle(fontSize: 12, color: Color(0xFF334155))),
      ],
    );
  }
}
