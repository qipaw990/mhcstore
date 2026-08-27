import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/driver_controller.dart';

class ActiveTripScreen extends StatelessWidget {
  final Map<String, dynamic> trip;
  const ActiveTripScreen({super.key, required this.trip});

  @override
  Widget build(BuildContext context) {
    final driverCtrl = context.watch<DriverController>();
    final orderId = int.parse(trip['id'].toString());
    final status = trip['order_status'] ?? 'accepted';

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.green[50],
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.green),
            ),
            child: Row(
              children: [
                const Icon(Icons.delivery_dining, color: Colors.green, size: 32),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('ORDERAN SELESAI DIAMBIL', style: TextStyle(fontWeight: FontWeight.bold)),
                    Text('Order #${trip['order_code'] ?? orderId}', style: const TextStyle(fontSize: 12)),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          Card(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Lokasi Pengambilan (Toko)', style: TextStyle(color: Colors.grey, fontSize: 12)),
                  Text(
                    trip['store_name'] ?? 'Mitra Resto',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                  ),
                  Text(trip['store_address'] ?? 'Cicalengka'),
                  const Divider(height: 24),
                  const Text('Lokasi Pengantaran (Pelanggan)', style: TextStyle(color: Colors.grey, fontSize: 12)),
                  Text(
                    trip['customer_name'] ?? 'Pelanggan',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                  ),
                  Text(trip['delivery_address'] ?? 'Cicalengka'),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // Action Stepper Buttons
          if (status == 'accepted' || status == 'pending')
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: Colors.blue),
                onPressed: () => driverCtrl.updateTripStatus(orderId, 'picked_up'),
                child: const Text('SAYA SUDAH AMBIL MAKANAN DI RESTO'),
              ),
            )
          else if (status == 'picked_up' || status == 'handover')
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                onPressed: () => driverCtrl.updateTripStatus(orderId, 'delivered'),
                child: const Text('PESANAN SUDAH SAMPAI KE PELANGGAN (SELESAI)'),
              ),
            ),
        ],
      ),
    );
  }
}
