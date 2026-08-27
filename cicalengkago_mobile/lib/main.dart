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
import 'core/services/global_call_service.dart';

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

/// Mandatory Location Guard — Full Screen Gatekeeper
/// Prevents any access to the app until Location Permission is granted & GPS is active.
class LocationGuard extends StatefulWidget {
  final Widget child;
  const LocationGuard({super.key, required this.child});

  @override
  State<LocationGuard> createState() => _LocationGuardState();
}

class _LocationGuardState extends State<LocationGuard> with WidgetsBindingObserver {
  bool _isChecking = true;
  bool _isGranted = false;
  bool _serviceEnabled = true;
  LocationPermission _permission = LocationPermission.denied;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _checkLocationStatus();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Re-check permission automatically when user switches back from phone settings
    if (state == AppLifecycleState.resumed) {
      _checkLocationStatus();
    }
  }

  Future<void> _checkLocationStatus() async {
    setState(() => _isChecking = true);

    bool serviceEnabled = false;
    LocationPermission permission = LocationPermission.denied;
    bool granted = false;

    try {
      serviceEnabled = await Geolocator.isLocationServiceEnabled().timeout(
        const Duration(seconds: 3),
        onTimeout: () => false,
      );
      permission = await Geolocator.checkPermission().timeout(
        const Duration(seconds: 3),
        onTimeout: () => LocationPermission.denied,
      );

      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission().timeout(
          const Duration(seconds: 5),
          onTimeout: () => LocationPermission.denied,
        );
      }

      granted = serviceEnabled &&
          (permission == LocationPermission.always || permission == LocationPermission.whileInUse);
    } catch (_) {
      granted = false;
    } finally {
      if (mounted) {
        setState(() {
          _serviceEnabled = serviceEnabled;
          _permission = permission;
          _isGranted = granted;
          _isChecking = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isChecking) {
      return const Scaffold(
        backgroundColor: Colors.white,
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircularProgressIndicator(color: AppTheme.primaryRed),
              SizedBox(height: 16),
              Text(
                'Memeriksa Izin Lokasi GPS...',
                style: TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
              ),
            ],
          ),
        ),
      );
    }

    if (!_isGranted) {
      return LocationPermissionScreen(
        serviceEnabled: _serviceEnabled,
        permission: _permission,
        onRetry: _checkLocationStatus,
      );
    }

    return widget.child;
  }
}

/// Full-Screen Mandatory Location Permission Screen
class LocationPermissionScreen extends StatelessWidget {
  final bool serviceEnabled;
  final LocationPermission permission;
  final VoidCallback onRetry;

  const LocationPermissionScreen({
    super.key,
    required this.serviceEnabled,
    required this.permission,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Spacer(),
              // Icon Badge
              Container(
                width: 100,
                height: 100,
                decoration: BoxDecoration(
                  color: AppTheme.primaryRed.withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                child: const Center(
                  child: Icon(
                    Icons.location_off_rounded,
                    size: 52,
                    color: AppTheme.primaryRed,
                  ),
                ),
              ),
              const SizedBox(height: 28),

              const Text(
                'Izin Lokasi Wajib',
                style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF0F172A),
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 12),

              const Text(
                'Aplikasi CicalengkaGO memerlukan akses lokasi GPS yang aktif untuk menemukan merchant terdekat, mengkalkulasi ongkos kirim presisi, dan melacak kurir secara real-time.',
                style: TextStyle(
                  fontSize: 13,
                  color: Color(0xFF64748B),
                  height: 1.5,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),

              // Status Container
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.03),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    const Icon(Icons.warning_amber_rounded, color: AppTheme.primaryRed, size: 24),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Status Akses Lokasi',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A)),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            !serviceEnabled
                                ? 'GPS HP Anda belum diaktifkan.'
                                : (permission == LocationPermission.deniedForever
                                    ? 'Izin ditolak permanen di HP Anda.'
                                    : 'Akses izin lokasi belum diberikan.'),
                            style: const TextStyle(fontSize: 11, color: AppTheme.primaryRed),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              const Spacer(),

              // Primary Action Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primaryRed,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
                    elevation: 3,
                  ),
                  onPressed: () async {
                    if (!serviceEnabled) {
                      await Geolocator.openLocationSettings();
                    } else if (permission == LocationPermission.deniedForever) {
                      await Geolocator.openAppSettings();
                    } else {
                      await Geolocator.requestPermission();
                    }
                    onRetry();
                  },
                  icon: const Icon(Icons.my_location_rounded, size: 20),
                  label: Text(
                    !serviceEnabled
                        ? 'AKTIFKAN GPS SEKARANG'
                        : (permission == LocationPermission.deniedForever ? 'BUKA PENGATURAN HP' : 'IZINKAN AKSES LOKASI'),
                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, letterSpacing: 0.5),
                  ),
                ),
              ),
              const SizedBox(height: 12),

              TextButton(
                onPressed: onRetry,
                child: const Text(
                  'Saya Sudah Mengaktifkan GPS / Mengizinkan',
                  style: TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class RoleRouter extends StatefulWidget {
  const RoleRouter({super.key});

  @override
  State<RoleRouter> createState() => _RoleRouterState();
}

class _RoleRouterState extends State<RoleRouter> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final authCtrl = context.read<AuthController>();
      final uId = int.tryParse(authCtrl.user?['id']?.toString() ?? '0');
      GlobalCallService.instance.init(context, userId: uId);
    });
  }

  @override
  Widget build(BuildContext context) {
    final authCtrl = context.watch<AuthController>();
    final uId = int.tryParse(authCtrl.user?['id']?.toString() ?? '0');
    GlobalCallService.instance.updateContext(context);
    if (uId != null && uId > 0) {
      GlobalCallService.instance.setUserAndOrder(userId: uId);
    }

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
