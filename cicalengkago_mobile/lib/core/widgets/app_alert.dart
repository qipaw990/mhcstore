import 'package:flutter/material.dart';

class AppAlert {
  static void showSuccess(
    BuildContext context, {
    required String title,
    String? message,
    Duration duration = const Duration(seconds: 3),
  }) {
    _showFloatingBanner(
      context,
      title: title,
      message: message,
      icon: Icons.check_circle_rounded,
      accentColor: const Color(0xFF10B981),
      bgGradient: const LinearGradient(
        colors: [Color(0xFF064E3B), Color(0xFF047857)],
      ),
      duration: duration,
    );
  }

  static void showError(
    BuildContext context, {
    required String title,
    String? message,
    Duration duration = const Duration(seconds: 4),
  }) {
    _showFloatingBanner(
      context,
      title: title,
      message: message,
      icon: Icons.error_rounded,
      accentColor: const Color(0xFFEF4444),
      bgGradient: const LinearGradient(
        colors: [Color(0xFF7F1D1D), Color(0xFFB91C1C)],
      ),
      duration: duration,
    );
  }

  static void showWarning(
    BuildContext context, {
    required String title,
    String? message,
    Duration duration = const Duration(seconds: 4),
  }) {
    _showFloatingBanner(
      context,
      title: title,
      message: message,
      icon: Icons.warning_amber_rounded,
      accentColor: const Color(0xFFF59E0B),
      bgGradient: const LinearGradient(
        colors: [Color(0xFF78350F), Color(0xFFB45309)],
      ),
      duration: duration,
    );
  }

  static void showInfo(
    BuildContext context, {
    required String title,
    String? message,
    Duration duration = const Duration(seconds: 3),
  }) {
    _showFloatingBanner(
      context,
      title: title,
      message: message,
      icon: Icons.info_rounded,
      accentColor: const Color(0xFF3B82F6),
      bgGradient: const LinearGradient(
        colors: [Color(0xFF1E3A8A), Color(0xFF1D4ED8)],
      ),
      duration: duration,
    );
  }

  static void showCartAdded(
    BuildContext context, {
    required String productName,
    required int quantity,
  }) {
    _showFloatingBanner(
      context,
      title: 'Masuk Keranjang! 🛒',
      message: '$quantityx $productName telah ditambahkan ke keranjang belanja.',
      icon: Icons.shopping_bag_rounded,
      accentColor: const Color(0xFF10B981),
      bgGradient: const LinearGradient(
        colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
      ),
      duration: const Duration(seconds: 3),
    );
  }

  static void _showFloatingBanner(
    BuildContext context, {
    required String title,
    String? message,
    required IconData icon,
    required Color accentColor,
    required LinearGradient bgGradient,
    required Duration duration,
  }) {
    final scaffold = ScaffoldMessenger.maybeOf(context);
    if (scaffold != null) {
      scaffold.hideCurrentSnackBar();
      scaffold.showSnackBar(
        SnackBar(
          elevation: 0,
          behavior: SnackBarBehavior.floating,
          backgroundColor: Colors.transparent,
          padding: EdgeInsets.zero,
          margin: const EdgeInsets.only(bottom: 24, left: 16, right: 16),
          duration: duration,
          content: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            decoration: BoxDecoration(
              gradient: bgGradient,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.25),
                  blurRadius: 20,
                  offset: const Offset(0, 8),
                ),
                BoxShadow(
                  color: accentColor.withValues(alpha: 0.3),
                  blurRadius: 10,
                  offset: const Offset(0, 2),
                ),
              ],
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.15),
                width: 1,
              ),
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: accentColor.withValues(alpha: 0.2),
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: accentColor.withValues(alpha: 0.4),
                      width: 1.5,
                    ),
                  ),
                  child: Icon(icon, color: Colors.white, size: 22),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 13.5,
                          letterSpacing: -0.2,
                        ),
                      ),
                      if (message != null && message.isNotEmpty) ...[
                        const SizedBox(height: 3),
                        Text(
                          message,
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.85),
                            fontSize: 11.5,
                            height: 1.3,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }
  }
}
