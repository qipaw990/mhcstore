import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
import '../controllers/auth_controller.dart';
import '../../driver/controllers/driver_controller.dart';
import '../../driver/screens/driver_dashboard_screen.dart';
import '../../merchant/screens/merchant_dashboard_screen.dart';
import '../../customer/screens/customer_home_screen.dart';
import '../../../core/services/global_call_service.dart';
import '../../../main.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with SingleTickerProviderStateMixin {
  late AnimationController _animController;
  late Animation<double> _scaleAnimation;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();

    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    );

    _scaleAnimation = CurvedAnimation(
      parent: _animController,
      curve: Curves.easeOutBack,
    );

    _fadeAnimation = CurvedAnimation(
      parent: _animController,
      curve: Curves.easeIn,
    );

    _animController.forward();
    _initializeAppAndNavigate();
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  Future<void> _initializeAppAndNavigate() async {
    // Wait for at least 2.2 seconds for a smooth branded splash experience
    await Future.delayed(const Duration(milliseconds: 2200));

    if (!mounted) return;

    final authCtrl = context.read<AuthController>();

    // If auth is still loading, wait until it's done
    while (!authCtrl.isInitialized && mounted) {
      await Future.delayed(const Duration(milliseconds: 100));
    }

    if (!mounted) return;

    final uId = int.tryParse(authCtrl.user?['id']?.toString() ?? '0');
    GlobalCallService.instance.updateContext(context);
    if (uId != null && uId > 0) {
      GlobalCallService.instance.setUserAndOrder(userId: uId);
    }

    final role = authCtrl.role?.toLowerCase() ?? '';
    if (role == 'delivery_man' || role == 'driver') {
      context.read<DriverController>().fetchRadarData();
    }

    Widget targetScreen;
    if (!authCtrl.isLoggedIn) {
      targetScreen = const CustomerHomeScreen();
    } else {
      if (role == 'delivery_man' || role == 'driver') {
        targetScreen = const DriverDashboardScreen();
      } else if (role == 'vendor' || role == 'merchant' || role == 'store') {
        targetScreen = const MerchantDashboardScreen();
      } else {
        targetScreen = const CustomerHomeScreen();
      }
    }

    Navigator.pushReplacement(
      context,
      PageRouteBuilder(
        transitionDuration: const Duration(milliseconds: 600),
        pageBuilder: (_, __, ___) => LocationGuard(child: targetScreen),
        transitionsBuilder: (_, animation, __, child) {
          return FadeTransition(opacity: animation, child: child);
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Stack(
        children: [
          // Background Gradient Accent
          Positioned(
            top: -100,
            right: -100,
            child: Container(
              width: 250,
              height: 250,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AppTheme.primaryRed.withValues(alpha: 0.05),
              ),
            ),
          ),
          Positioned(
            bottom: -80,
            left: -80,
            child: Container(
              width: 220,
              height: 220,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: const Color(0xFF2563EB).withValues(alpha: 0.04),
              ),
            ),
          ),

          // Main Logo & Branding
          Center(
            child: FadeTransition(
              opacity: _fadeAnimation,
              child: ScaleTransition(
                scale: _scaleAnimation,
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    // Branded Logo
                    const CicalengkaGoLogo(size: 96, borderRadius: 24, showShadow: true),
                    const SizedBox(height: 22),

                    // App Title
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: const [
                        Text(
                          'Cicalengka',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.w900,
                            color: Color(0xFF0F172A),
                            letterSpacing: -0.6,
                          ),
                        ),
                        Text(
                          'GO',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.w900,
                            color: AppTheme.primaryRed,
                            letterSpacing: -0.6,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),

                    // Tagline
                    const Text(
                      'Pesan Antar & Belanja Praktis Cicalengka',
                      style: TextStyle(
                        fontSize: 12,
                        color: Color(0xFF64748B),
                        fontWeight: FontWeight.w500,
                        letterSpacing: 0.2,
                      ),
                    ),
                    const SizedBox(height: 36),

                    // Loading Indicator
                    const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                        strokeWidth: 2.2,
                        valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primaryRed),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),

          // Version Footer
          Positioned(
            bottom: 24,
            left: 0,
            right: 0,
            child: Center(
              child: Text(
                'v2.4.0 • Made with ❤️ in Cicalengka',
                style: TextStyle(
                  fontSize: 10.5,
                  color: Colors.grey.shade400,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
