import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:geolocator/geolocator.dart';
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
      home: const LocationGuard(child: RoleRouter()),
    );
  }
}

/// Mandatory Location Permission Guard
/// Enforces location permission and GPS enabled status on app startup.
class LocationGuard extends StatefulWidget {
  final Widget child;
  const LocationGuard({super.key, required this.child});

  @override
  State<LocationGuard> createState() => _LocationGuardState();
}

class _LocationGuardState extends State<LocationGuard> with WidgetsBindingObserver {
  bool _isPopupOpen = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) => _checkPermission());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Re-check permission automatically when user returns from phone settings
    if (state == AppLifecycleState.resumed) {
      _checkPermission();
    }
  }

  Future<void> _checkPermission() async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    LocationPermission permission = await Geolocator.checkPermission();

    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }

    final bool isGranted = serviceEnabled &&
        (permission == LocationPermission.always || permission == LocationPermission.whileInUse);

    if (mounted) {
      if (!isGranted) {
        if (!_isPopupOpen) {
          _showMandatoryLocationPopup(serviceEnabled, permission);
        }
      } else {
        if (_isPopupOpen && Navigator.of(context, rootNavigator: true).canPop()) {
          Navigator.of(context, rootNavigator: true).pop();
          setState(() => _isPopupOpen = false);
        }
      }
    }
  }

  void _showMandatoryLocationPopup(bool serviceEnabled, LocationPermission permission) {
    setState(() => _isPopupOpen = true);

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => PopScope(
        canPop: false,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: const Row(
            children: [
              Icon(Icons.location_off_rounded, color: AppTheme.primaryRed, size: 26),
              SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Izin Lokasi Wajib',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                ),
              ),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Aplikasi CicalengkaGO memerlukan akses lokasi GPS untuk mendeteksi posisi Anda, menampilkan merchant terdekat, dan menghitung rute pengantaran presisi.',
                style: TextStyle(fontSize: 12, color: Color(0xFF475569), height: 1.4),
              ),
              const SizedBox(height: 14),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF2F2),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFFCA5A5)),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.warning_amber_rounded, color: AppTheme.primaryRed, size: 18),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Silakan berikan izin lokasi untuk dapat melanjutkan ke dalam aplikasi.',
                        style: TextStyle(fontSize: 10.5, color: AppTheme.primaryRed, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          actions: [
            ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primaryRed,
                foregroundColor: Colors.white,
                minimumSize: const Size(double.infinity, 44),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
                elevation: 2,
              ),
              onPressed: () async {
                if (Navigator.of(ctx, rootNavigator: true).canPop()) {
                  Navigator.of(ctx, rootNavigator: true).pop();
                }
                setState(() => _isPopupOpen = false);

                if (!serviceEnabled) {
                  await Geolocator.openLocationSettings();
                } else if (permission == LocationPermission.deniedForever) {
                  await Geolocator.openAppSettings();
                } else {
                  await Geolocator.requestPermission();
                }
                _checkPermission();
              },
              icon: const Icon(Icons.my_location_rounded, size: 18),
              label: Text(
                !serviceEnabled
                    ? 'Aktifkan Layanan GPS HP'
                    : (permission == LocationPermission.deniedForever ? 'Buka Pengaturan Aplikasi' : 'Izinkan Akses Lokasi'),
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return widget.child;
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
