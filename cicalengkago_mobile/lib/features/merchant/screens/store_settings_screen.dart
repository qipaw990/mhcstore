import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../controllers/merchant_controller.dart';

class StoreSettingsScreen extends StatelessWidget {
  const StoreSettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final merchantCtrl = context.watch<MerchantController>();
    final store = merchantCtrl.store ?? {};

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Profil Resto / Toko', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  const Divider(height: 20),
                  Text('Nama Resto: ${store['name'] ?? '-'}'),
                  const SizedBox(height: 6),
                  Text('Alamat: ${store['address'] ?? 'Cicalengka'}'),
                  const SizedBox(height: 6),
                  Text('No. HP / WA: ${store['phone'] ?? '-'}'),
                  const SizedBox(height: 6),
                  Text('Jam Operasional: ${store['opening_time'] ?? '08:00'} - ${store['closing_time'] ?? '22:00'}'),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          Card(
            child: ListTile(
              leading: const Icon(Icons.account_balance_wallet, color: AppTheme.primaryRed),
              title: const Text('Saldo Hasil Penjualan Toko'),
              subtitle: const Text('Tarik saldo ke rekening bank terdaftar'),
              trailing: const Icon(Icons.chevron_right),
              onTap: () {},
            ),
          ),
        ],
      ),
    );
  }
}
