import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:geolocator/geolocator.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/controllers/auth_controller.dart';
import 'features/auth/screens/splash_screen.dart';
import 'features/customer/controllers/customer_controller.dart';
import 'features/customer/screens/customer_home_screen.dart';
import 'features/driver/controllers/driver_controller.dart';
import 'features/driver/screens/driver_dashboard_screen.dart';
import 'features/merchant/controllers/merchant_controller.dart';
import 'features/merchant/screens/merchant_dashboard_screen.dart';
import 'core/services/global_call_service.dart';
import 'core/services/zone_guard_service.dart';

import 'package:google_fonts/google_fonts.dart';
import 'core/widgets/cicalengkago_logo.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  if (kIsWeb) {
    GoogleFonts.config.allowRuntimeFetching = false;
  }
  ErrorWidget.builder = (FlutterErrorDetails details) {
    return Material(
      color: Colors.white,
      child: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    Icon(Icons.error_outline, color: Colors.red, size: 28),
                    SizedBox(width: 10),
                    Text('Terjadi Kesalahan Tampilan', style: TextStyle(color: Colors.red, fontSize: 16, fontWeight: FontWeight.bold)),
                  ],
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(color: const Color(0xFFFEE2E2), borderRadius: BorderRadius.circular(8)),
                  child: Text(
                    details.exceptionAsString(),
                    style: const TextStyle(color: Color(0xFF991B1B), fontSize: 13, fontWeight: FontWeight.w600),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  details.stack?.toString() ?? '',
                  style: const TextStyle(color: Color(0xFF475569), fontSize: 11, fontFamily: 'monospace'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  };
  GlobalCallService.instance.startPolling();
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

final GlobalKey<NavigatorState> rootNavigatorKey = GlobalKey<NavigatorState>();
final RouteObserver<ModalRoute<void>> appRouteObserver = RouteObserver<ModalRoute<void>>();

class CicalengkaGoApp extends StatelessWidget {
  const CicalengkaGoApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: rootNavigatorKey,
      navigatorObservers: [appRouteObserver],
      title: 'CicalengkaGO Mobile',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: const SplashScreen(),
    );
  }
}

/// Mandatory Location Guard — Full Screen Gatekeeper
/// Step 1: GPS permission must be granted and service enabled.
/// Step 2: User's current position must be inside one of the active service zones.
class LocationGuard extends StatefulWidget {
  final Widget child;
  const LocationGuard({super.key, required this.child});

  @override
  State<LocationGuard> createState() => _LocationGuardState();
}

enum _GuardState {
  checking,
  permissionDenied,
  outsideZone,
  allowed,
}

class _LocationGuardState extends State<LocationGuard> with WidgetsBindingObserver {
  _GuardState _state = _GuardState.checking;
  bool _serviceEnabled = true;
  LocationPermission _permission = LocationPermission.denied;
  String _outsideZoneMessage = 'Lokasi Anda berada di luar area layanan CicalengkaGO.';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _runFullCheck();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _runFullCheck();
    }
  }

  Future<void> _runFullCheck() async {
    if (!mounted) return;
    setState(() => _state = _GuardState.checking);

    // ── Web: skip all checks ─────────────────────────────────────────────────
    if (kIsWeb) {
      if (mounted) setState(() => _state = _GuardState.allowed);
      return;
    }

    // ── Step 1: Check GPS permission ─────────────────────────────────────────
    bool serviceEnabled = false;
    LocationPermission permission = LocationPermission.denied;

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
    } catch (_) {
      permission = LocationPermission.denied;
    }

    final bool permissionGranted = serviceEnabled &&
        (permission == LocationPermission.always ||
            permission == LocationPermission.whileInUse);

    if (!permissionGranted) {
      if (mounted) {
        setState(() {
          _serviceEnabled = serviceEnabled;
          _permission = permission;
          _state = _GuardState.permissionDenied;
        });
      }
      return;
    }

    // ── Step 2: Get current position & check zone ────────────────────────────
    try {
      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: const Duration(seconds: 10),
      );

      final result = await ZoneGuardService.checkUserInZone(position);

      if (!mounted) return;
      if (result.isInsideZone) {
        setState(() => _state = _GuardState.allowed);
      } else {
        setState(() {
          _outsideZoneMessage =
              result.errorMessage ?? 'Lokasi Anda berada di luar area layanan CicalengkaGO.';
          _state = _GuardState.outsideZone;
        });
      }
    } catch (_) {
      // If we can't get position at all, let the user in (fail open)
      if (mounted) setState(() => _state = _GuardState.allowed);
    }
  }

  @override
  Widget build(BuildContext context) {
    switch (_state) {
      case _GuardState.checking:
        return const _CheckingScreen();
      case _GuardState.permissionDenied:
        return LocationPermissionScreen(
          serviceEnabled: _serviceEnabled,
          permission: _permission,
          onRetry: _runFullCheck,
        );
      case _GuardState.outsideZone:
        return OutsideZoneScreen(
          message: _outsideZoneMessage,
          onRetry: _runFullCheck,
        );
      case _GuardState.allowed:
        return widget.child;
    }
  }
}

/// Loading screen shown while checking GPS & zone status.
class _CheckingScreen extends StatelessWidget {
  const _CheckingScreen();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircularProgressIndicator(color: AppTheme.primaryRed),
            SizedBox(height: 16),
            Text(
              'Memeriksa lokasi Anda...',
              style: TextStyle(
                fontSize: 13,
                color: Color(0xFF64748B),
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Full-Screen warning shown when user is outside all active service zones.
class OutsideZoneScreen extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const OutsideZoneScreen({
    super.key,
    required this.message,
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
                width: 110,
                height: 110,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      const Color(0xFFF97316).withValues(alpha: 0.15),
                      const Color(0xFFEF4444).withValues(alpha: 0.10),
                    ],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  shape: BoxShape.circle,
                ),
                child: const Center(
                  child: Icon(
                    Icons.location_city_rounded,
                    size: 56,
                    color: Color(0xFFF97316),
                  ),
                ),
              ),
              const SizedBox(height: 28),

              const Text(
                'Di Luar Area Layanan',
                style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF0F172A),
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 12),

              Text(
                message,
                style: const TextStyle(
                  fontSize: 14,
                  color: Color(0xFF64748B),
                  height: 1.6,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 28),

              // Info Card
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFFED7AA)),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.04),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(
                      Icons.info_outline_rounded,
                      color: Color(0xFFF97316),
                      size: 22,
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Text(
                        'CicalengkaGO saat ini hanya tersedia di area Cicalengka dan sekitarnya. Pastikan Anda berada di wilayah yang terdaftar sebagai area layanan kami.',
                        style: TextStyle(
                          fontSize: 12,
                          color: Color(0xFF78350F),
                          height: 1.5,
                        ),
                      ),
                    ),
                  ],
                ),
              ),

              const Spacer(),

              // Retry Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFF97316),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(28),
                    ),
                    elevation: 3,
                  ),
                  onPressed: onRetry,
                  icon: const Icon(Icons.refresh_rounded, size: 20),
                  label: const Text(
                    'CEK LOKASI ULANG',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      letterSpacing: 0.5,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 12),

              Text(
                'Jika Anda merasa ini adalah kesalahan,\nhubungi support CicalengkaGO.',
                style: TextStyle(
                  fontSize: 11,
                  color: Colors.grey.shade500,
                  height: 1.5,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
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

      final role = authCtrl.role?.toLowerCase() ?? '';
      if (role == 'delivery_man' || role == 'driver') {
        context.read<DriverController>().fetchRadarData();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final authCtrl = context.watch<AuthController>();

    // Show branded splash screen until stored session is read
    if (!authCtrl.isInitialized) {
      return const _AppSplashView();
    }

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

class _AppSplashView extends StatelessWidget {
  const _AppSplashView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const CicalengkaGoLogo(size: 88, borderRadius: 24, showShadow: true),
            const SizedBox(height: 20),
            const Text(
              'CicalengkaGO',
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w900,
                color: Color(0xFF0F172A),
                letterSpacing: -0.5,
              ),
            ),
            const SizedBox(height: 4),
            const Text(
              'Pesan Antar Makanan & Belanja Praktis',
              style: TextStyle(
                fontSize: 12,
                color: Color(0xFF64748B),
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 32),
            const SizedBox(
              width: 24,
              height: 24,
              child: CircularProgressIndicator(
                strokeWidth: 2.5,
                valueColor: AlwaysStoppedAnimation<Color>(AppTheme.primaryRed),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
