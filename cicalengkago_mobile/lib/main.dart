import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/controllers/auth_controller.dart';
import 'features/customer/controllers/customer_controller.dart';
import 'features/customer/screens/customer_home_screen.dart';
import 'features/driver/controllers/driver_controller.dart';
import 'features/driver/screens/driver_dashboard_screen.dart';
import 'features/merchant/controllers/merchant_controller.dart';
import 'features/merchant/screens/merchant_dashboard_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthController()..checkSavedSession()),
        ChangeNotifierProvider(create: (_) => CustomerController()),
        ChangeNotifierProvider(create: (_) => DriverController()),
        ChangeNotifierProvider(create: (_) => MerchantController()),
      ],
      child: const CicalengkaGoApp(),
    ),
  );
}

class CicalengkaGoApp extends StatelessWidget {
  const CicalengkaGoApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'CicalengkaGO Mobile',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: const RoleRouter(),
    );
  }
}

class RoleRouter extends StatelessWidget {
  const RoleRouter({super.key});

  @override
  Widget build(BuildContext context) {
    final authCtrl = context.watch<AuthController>();

    // If not logged in, open CustomerHomeScreen directly in Guest Mode (matching Web PWA)
    if (!authCtrl.isLoggedIn) {
      return const CustomerHomeScreen();
    }

    final role = authCtrl.role?.toLowerCase() ?? 'customer';

    if (role == 'delivery_man' || role == 'driver') {
      return const DriverDashboardScreen();
    } else if (role == 'vendor' || role == 'merchant' || role == 'store') {
      return const MerchantDashboardScreen();
    } else {
      return const CustomerHomeScreen();
    }
  }
}
