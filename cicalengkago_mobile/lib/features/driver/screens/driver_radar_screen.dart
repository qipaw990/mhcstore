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
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.red[50],
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.power_settings_new, size: 64, color: Colors.red),
            ),
            const SizedBox(height: 16),
            const Text(
              'Anda Sedang OFFLINE',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            const Text(
              'Aktifkan tombol online di atas untuk menerima orderan pengantaran',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey),
            ),
          ],
        ),
      );
    }

    if (driverCtrl.activeTrip != null) {
      return ActiveTripScreen(trip: driverCtrl.activeTrip!);
    }

    return Column(
      children: [
        // Live Radar Map View
        Expanded(
          flex: 4,
          child: FlutterMap(
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
                  Marker(
                    point: driverCtrl.currentLocation,
                    width: 50,
                    height: 50,
                    child: Container(
                      decoration: const BoxDecoration(
                        color: Colors.green,
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(color: Colors.greenAccent, blurRadius: 12),
                        ],
                      ),
                      child: const Icon(Icons.navigation, color: Colors.white, size: 28),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),

        // Available Orders Sheet
        Expanded(
          flex: 5,
          child: Container(
            padding: const EdgeInsets.all(16),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 8)],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Orderan Masuk (${driverCtrl.availableOrders.length})',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    IconButton(
                      icon: const Icon(Icons.refresh),
                      onPressed: () => driverCtrl.fetchRadarData(),
                    ),
                  ],
                ),
                const SizedBox(height: 8),

                if (driverCtrl.availableOrders.isEmpty)
                  const Expanded(
                    child: Center(
                      child: Text('Mencari pesanan terdekat di Cicalengka...'),
                    ),
                  )
                else
                  Expanded(
                    child: ListView.builder(
                      itemCount: driverCtrl.availableOrders.length,
                      itemBuilder: (context, index) {
                        final order = driverCtrl.availableOrders[index];
                        final fee = double.tryParse(order['delivery_charge']?.toString() ?? '5000') ?? 5000.0;

                        return Card(
                          margin: const EdgeInsets.only(bottom: 12),
                          child: Padding(
                            padding: const EdgeInsets.all(14),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      'Order #${order['order_code'] ?? order['id']}',
                                      style: const TextStyle(fontWeight: FontWeight.bold),
                                    ),
                                    Text(
                                      CurrencyFormatter.formatRupiah(fee),
                                      style: const TextStyle(
                                        color: Colors.green,
                                        fontWeight: FontWeight.bold,
                                        fontSize: 16,
                                      ),
                                    ),
                                  ],
                                ),
                                const Divider(),
                                Text(
                                  'Toko: ${order['store_name'] ?? 'Mitra Resto'}',
                                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                                ),
                                Text(
                                  'Tujuan: ${order['delivery_address'] ?? 'Cicalengka'}',
                                  style: const TextStyle(fontSize: 12, color: Colors.grey),
                                ),
                                const SizedBox(height: 12),
                                SizedBox(
                                  width: double.infinity,
                                  child: ElevatedButton(
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.green,
                                    ),
                                    onPressed: () async {
                                      final success = await driverCtrl.acceptOrder(
                                        int.parse(order['id'].toString()),
                                      );
                                      if (success && context.mounted) {
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          const SnackBar(content: Text('Orderan berhasil diambil! Selamat bertugas.')),
                                        );
                                      }
                                    },
                                    child: const Text('TERIMA ORDERAN INI'),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
