import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
import '../../auth/controllers/auth_controller.dart';
import '../controllers/merchant_controller.dart';
import 'merchant_orders_screen.dart';
import 'product_management_screen.dart';
import 'store_settings_screen.dart';

class MerchantDashboardScreen extends StatefulWidget {
  const MerchantDashboardScreen({super.key});

  @override
  State<MerchantDashboardScreen> createState() => _MerchantDashboardScreenState();
}

class _MerchantDashboardScreenState extends State<MerchantDashboardScreen> {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MerchantController>().fetchDashboardData();
    });
  }

  @override
  Widget build(BuildContext context) {
    final merchantCtrl = context.watch<MerchantController>();
    final authCtrl = context.watch<AuthController>();
    final store = merchantCtrl.store;

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            const CicalengkaGoLogo(size: 34, borderRadius: 10, showShadow: false),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  store?['name'] ?? 'Mitra Resto',
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                ),
                Text(
                  merchantCtrl.isOpen ? '🟢 Toko BUKA (Bisa Terima Pesanan)' : '🔴 Toko TUTUP',
                  style: TextStyle(
                    fontSize: 11,
                    color: merchantCtrl.isOpen ? Colors.green : Colors.red,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ],
        ),
        actions: [
          Switch(
            value: merchantCtrl.isOpen,
            activeThumbColor: Colors.green,
            onChanged: (val) => merchantCtrl.toggleStoreOpenStatus(val),
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => authCtrl.logout(),
          ),
        ],
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: const [
          MerchantOrdersScreen(),
          ProductManagementScreen(),
          StoreSettingsScreen(),
        ],
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        selectedItemColor: AppTheme.primaryRed,
        onTap: (idx) {
          setState(() {
            _currentIndex = idx;
          });
        },
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.receipt_long), label: 'Pesanan Masuk'),
          BottomNavigationBarItem(icon: Icon(Icons.restaurant_menu), label: 'Kelola Menu'),
          BottomNavigationBarItem(icon: Icon(Icons.settings), label: 'Pengaturan Resto'),
        ],
      ),
    );
  }
}
