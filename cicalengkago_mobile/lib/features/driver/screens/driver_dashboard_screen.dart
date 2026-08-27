import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/controllers/auth_controller.dart';
import '../controllers/driver_controller.dart';
import 'driver_radar_screen.dart';
import 'driver_earnings_screen.dart';

class DriverDashboardScreen extends StatefulWidget {
  const DriverDashboardScreen({super.key});

  @override
  State<DriverDashboardScreen> createState() => _DriverDashboardScreenState();
}

class _DriverDashboardScreenState extends State<DriverDashboardScreen> {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DriverController>().fetchRadarData();
    });
  }

  @override
  Widget build(BuildContext context) {
    final driverCtrl = context.watch<DriverController>();
    final authCtrl = context.watch<AuthController>();
    final user = authCtrl.user;

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.amber[100],
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.two_wheeler, color: Colors.amber, size: 24),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Driver ${user?['name'] ?? ''}',
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                ),
                Text(
                  driverCtrl.isOnline ? '🟢 Status: ONLINE (Siap Order)' : '🔴 Status: OFFLINE',
                  style: TextStyle(
                    fontSize: 11,
                    color: driverCtrl.isOnline ? Colors.green : Colors.red,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ],
        ),
        actions: [
          Switch(
            value: driverCtrl.isOnline,
            activeColor: Colors.green,
            onChanged: (val) => driverCtrl.toggleOnline(val),
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => authCtrl.logout(),
          ),
        ],
      ),
      body: _currentIndex == 0 ? const DriverRadarScreen() : const DriverEarningsScreen(),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        selectedItemColor: AppTheme.primaryRed,
        onTap: (idx) {
          setState(() {
            _currentIndex = idx;
          });
        },
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.radar), label: 'Radar Order'),
          BottomNavigationBarItem(icon: Icon(Icons.account_balance_wallet), label: 'Pendapatan & Rating'),
        ],
      ),
    );
  }
}
