import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/merchant_controller.dart';

class MerchantOrdersScreen extends StatelessWidget {
  const MerchantOrdersScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final merchantCtrl = context.watch<MerchantController>();

    if (merchantCtrl.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (merchantCtrl.orders.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.inbox, size: 64, color: Colors.grey),
            SizedBox(height: 12),
            Text('Belum ada pesanan masuk untuk toko Anda'),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => merchantCtrl.fetchDashboardData(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: merchantCtrl.orders.length,
        itemBuilder: (context, index) {
          final order = merchantCtrl.orders[index];
          final total = double.tryParse(order['order_amount']?.toString() ?? '0') ?? 0.0;

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Pesanan #${order['order_code'] ?? order['id']}',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.orange[50],
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          order['order_status'] ?? 'pending',
                          style: const TextStyle(
                            color: Colors.orange,
                            fontWeight: FontWeight.bold,
                            fontSize: 11,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const Divider(height: 20),
                  Text('Pemesan: ${order['customer_name'] ?? 'Pelanggan'}'),
                  Text(
                    'Total Nilai: ${CurrencyFormatter.formatRupiah(total)}',
                    style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                  ),
                  const SizedBox(height: 12),
                  _buildActionButton(context, merchantCtrl, order),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildActionButton(BuildContext context, MerchantController ctrl, Map<String, dynamic> order) {
    final status = order['order_status']?.toString().toLowerCase() ?? 'pending';
    final orderId = int.tryParse(order['id']?.toString() ?? '0') ?? 0;

    if (status == 'pending' || status == 'confirmed') {
      return SizedBox(
        width: double.infinity,
        child: ElevatedButton.icon(
          style: ElevatedButton.styleFrom(
            backgroundColor: Colors.amber[700],
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 12),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
          onPressed: () async {
            final success = await ctrl.updateOrderStatus(orderId, 'processing');
            if (context.mounted && success) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Pesanan berhasil diterima dan dalam proses masak!')),
              );
            }
          },
          icon: const Icon(Icons.soup_kitchen, size: 18),
          label: const Text('Terima & Masak Pesanan', style: TextStyle(fontWeight: FontWeight.bold)),
        ),
      );
    } else if (status == 'processing') {
      return SizedBox(
        width: double.infinity,
        child: ElevatedButton.icon(
          style: ElevatedButton.styleFrom(
            backgroundColor: Colors.blue[600],
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 12),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
          onPressed: () async {
            final success = await ctrl.updateOrderStatus(orderId, 'handover');
            if (context.mounted && success) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Pesanan disiapkan! Menunggu penjemputan kurir.')),
              );
            }
          },
          icon: const Icon(Icons.takeout_dining, size: 18),
          label: const Text('Pesanan Siap Diambil Kurir', style: TextStyle(fontWeight: FontWeight.bold)),
        ),
      );
    } else if (status == 'handover' || status == 'picked_up') {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: Colors.blue[50],
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: Colors.blue[200]!),
        ),
        child: const Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.two_wheeler, color: Colors.blue, size: 18),
            SizedBox(width: 6),
            Text('Menunggu kurir mengantar pesanan', style: TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 12)),
          ],
        ),
      );
    } else if (status == 'delivered') {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: Colors.green[50],
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: Colors.green[200]!),
        ),
        child: const Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.check_circle, color: Colors.green, size: 18),
            SizedBox(width: 6),
            Text('Pesanan Selesai (Saldo Diterima)', style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 12)),
          ],
        ),
      );
    }

    return const SizedBox.shrink();
  }
}
