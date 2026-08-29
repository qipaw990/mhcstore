import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../theme/app_theme.dart';
import 'cicalengkago_logo.dart';
import 'uber_pill_button.dart';
import '../../features/auth/controllers/auth_controller.dart';
import '../../features/auth/screens/login_screen.dart';

class RequireAuthWidget extends StatelessWidget {
  final String title;
  final String subtitle;
  final IconData icon;
  final Widget child;

  const RequireAuthWidget({
    super.key,
    required this.title,
    required this.subtitle,
    this.icon = Icons.lock_outline_rounded,
    required this.child,
  });

  /// Helper static method to check auth before performing an action
  static bool check(BuildContext context, {VoidCallback? onAuthenticated}) {
    final authCtrl = context.read<AuthController>();
    if (!authCtrl.isLoggedIn) {
      Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
      );
      return false;
    }
    if (onAuthenticated != null) {
      onAuthenticated();
    }
    return true;
  }

  @override
  Widget build(BuildContext context) {
    final authCtrl = context.watch<AuthController>();

    if (authCtrl.isLoggedIn) {
      return child;
    }

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 28),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const CicalengkaGoLogo(height: 48),
              const SizedBox(height: 32),

              Container(
                padding: const EdgeInsets.all(22),
                decoration: BoxDecoration(
                  color: AppTheme.primaryRed.withValues(alpha: 0.08),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  icon,
                  size: 56,
                  color: AppTheme.primaryRed,
                ),
              ),
              const SizedBox(height: 24),

              Text(
                title,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF0F172A),
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 10),

              Text(
                subtitle,
                style: const TextStyle(
                  fontSize: 13,
                  color: Color(0xFF64748B),
                  height: 1.5,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 32),

              UberPillButton(
                label: 'Masuk / Daftar Akun',
                icon: Icons.login_rounded,
                fullWidth: true,
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const LoginScreen()),
                  );
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}
