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
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton(
                          style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                          onPressed: () {},
                          child: const Text('PROSES PESANAN'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
