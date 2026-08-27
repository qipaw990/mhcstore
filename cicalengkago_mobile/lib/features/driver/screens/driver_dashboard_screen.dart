import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
import '../../auth/controllers/auth_controller.dart';
import '../controllers/driver_controller.dart';
import 'driver_radar_screen.dart';
import 'driver_earnings_screen.dart';
import 'driver_profile_screen.dart';

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
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: Row(
          children: [
            const CicalengkaGoLogo(size: 34, borderRadius: 10, showShadow: false),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Driver ${user?['name'] ?? ''}',
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    overflow: TextOverflow.ellipsis,
                  ),
                  Row(
                    children: [
                      Container(
                        width: 7,
                        height: 7,
                        decoration: BoxDecoration(
                          color: driverCtrl.isOnline ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 5),
                      Text(
                        driverCtrl.isOnline ? 'ONLINE — Siap Terima Order' : 'OFFLINE',
                        style: TextStyle(
                          fontSize: 10,
                          color: driverCtrl.isOnline ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          // Online toggle
          Row(
            children: [
              Text(
                driverCtrl.isOnline ? 'ON' : 'OFF',
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                  color: driverCtrl.isOnline ? const Color(0xFF16A34A) : const Color(0xFF94A3B8),
                ),
              ),
              Switch(
                value: driverCtrl.isOnline,
                activeColor: const Color(0xFF16A34A),
                onChanged: (val) => driverCtrl.toggleOnline(val),
              ),
            ],
          ),

          // Wallet balance mini chip
          if (driverCtrl.walletBalance > 0)
            Container(
              margin: const EdgeInsets.only(right: 8),
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: const Color(0xFFD1FAE5),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Row(
                children: [
                  const Icon(Icons.account_balance_wallet_rounded, color: Color(0xFF059669), size: 12),
                  const SizedBox(width: 4),
                  Text(
                    CurrencyFormatter.formatRupiah(driverCtrl.walletBalance),
                    style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF059669)),
                  ),
                ],
              ),
            ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(height: 1, color: const Color(0xFFF1F5F9)),
        ),
      ),

      body: IndexedStack(
        index: _currentIndex,
        children: const [
          DriverRadarScreen(),
          DriverEarningsScreen(),
          DriverProfileScreen(),
        ],
      ),

      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 15, offset: const Offset(0, -4))],
        ),
        child: BottomNavigationBar(
          currentIndex: _currentIndex,
          selectedItemColor: AppTheme.primaryRed,
          unselectedItemColor: const Color(0xFF94A3B8),
          selectedLabelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11),
          unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w500, fontSize: 11),
          type: BottomNavigationBarType.fixed,
          elevation: 0,
          backgroundColor: Colors.white,
          onTap: (idx) {
            setState(() => _currentIndex = idx);
            // Load earnings data when tab is selected
            if (idx == 1) {
              context.read<DriverController>().fetchEarnings();
            } else if (idx == 2) {
              context.read<DriverController>().fetchProfile();
            }
          },
          items: const [
            BottomNavigationBarItem(
              icon: Icon(Icons.radar_rounded),
              activeIcon: Icon(Icons.radar_rounded, color: AppTheme.primaryRed),
              label: 'Radar Order',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.account_balance_wallet_outlined),
              activeIcon: Icon(Icons.account_balance_wallet_rounded, color: AppTheme.primaryRed),
              label: 'Pendapatan',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.person_outline_rounded),
              activeIcon: Icon(Icons.person_rounded, color: AppTheme.primaryRed),
              label: 'Profil Driver',
            ),
          ],
        ),
      ),
    );
  }
}
