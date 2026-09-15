import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  // ─── Brand Core ────────────────────────────────────────────────────────────
  static const Color inkBlack       = Color(0xFF1A1110); // warm black
  static const Color onPrimary      = Color(0xFFFFFFFF);
  static const Color primaryRed     = Color(0xFFE8400C); // warm orange-red brand
  static const Color primaryDarkRed = Color(0xFFBF3008);

  // ─── Warm Food Palette ─────────────────────────────────────────────────────
  static const Color brandOrange    = Color(0xFFFF6B2C); // vibrant food orange
  static const Color brandAmber     = Color(0xFFF59E0B); // golden amber
  static const Color brandCream     = Color(0xFFFFF8F2); // warm cream background
  static const Color brandCoral     = Color(0xFFFF8C61); // soft coral accent
  static const Color brandBrown     = Color(0xFF7C4A1E); // warm brown text
  static const Color successGreen   = Color(0xFF16A34A);
  static const Color secondaryEmerald = Color(0xFF059669);
  static const Color warningAmber   = Color(0xFFF59E0B);
  static const Color dangerRed      = Color(0xFFEF4444);
  static const Color infoBlu        = Color(0xFF2563EB);

  // ─── Surfaces ──────────────────────────────────────────────────────────────
  static const Color canvas         = Color(0xFFFFFFFF);
  static const Color canvasSoft     = Color(0xFFFFF5EE); // warm off-white
  static const Color canvasSofter   = Color(0xFFFFF8F2); // brand cream
  static const Color surfacePressed = Color(0xFFFFE5D4);
  static const Color blackElevated  = Color(0xFF282828);
  static const Color darkSlate      = Color(0xFF0F172A);
  static const Color cardBorder     = Color(0xFFFFE5D0);

  // ─── Typography Colors ─────────────────────────────────────────────────────
  static const Color textInk        = Color(0xFF1A1110);
  static const Color textBody       = Color(0xFF6B5147);
  static const Color textMute       = Color(0xFFAFA09A);
  static const Color hairlineMid    = Color(0xFF4B4B4B);

  // ─── Radii ─────────────────────────────────────────────────────────────────
  static const double radiusPill    = 999.0;
  static const double radiusPillTab = 36.0;
  static const double radiusCard    = 20.0;
  static const double radiusLg      = 14.0;
  static const double radiusInput   = 12.0;

  // ─── Spacing ───────────────────────────────────────────────────────────────
  static const double spaceXxs = 4.0;
  static const double spaceXs  = 6.0;
  static const double spaceSm  = 8.0;
  static const double spaceMd  = 12.0;
  static const double spaceLg  = 16.0;
  static const double spaceXl  = 20.0;
  static const double space2xl = 24.0;
  static const double space3xl = 32.0;

  // ─── Gradients ─────────────────────────────────────────────────────────────
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFFFF6B2C), Color(0xFFE8400C)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient warmGradient = LinearGradient(
    colors: [Color(0xFFFF8C61), Color(0xFFFF6B2C)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient heroGradient = LinearGradient(
    colors: [Color(0xFFFF6B2C), Color(0xFFBF3008)],
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
  );

  static const LinearGradient walletGradient = LinearGradient(
    colors: [Color(0xFF7C4A1E), Color(0xFF1A1110)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient goldGradient = LinearGradient(
    colors: [Color(0xFFF59E0B), Color(0xFFD97706)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient greenGradient = LinearGradient(
    colors: [Color(0xFF16A34A), Color(0xFF059669)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  // ─── Shadows ───────────────────────────────────────────────────────────────
  static List<BoxShadow> get cardShadow => [
    BoxShadow(
      color: const Color(0xFFFF6B2C).withValues(alpha: 0.08),
      blurRadius: 20,
      offset: const Offset(0, 4),
      spreadRadius: 0,
    ),
    BoxShadow(
      color: Colors.black.withValues(alpha: 0.04),
      blurRadius: 8,
      offset: const Offset(0, 2),
    ),
  ];

  static List<BoxShadow> get floatShadow => [
    BoxShadow(
      color: const Color(0xFFFF6B2C).withValues(alpha: 0.30),
      blurRadius: 24,
      offset: const Offset(0, 8),
      spreadRadius: -4,
    ),
  ];

  static List<BoxShadow> get deepShadow => [
    BoxShadow(
      color: Colors.black.withValues(alpha: 0.12),
      blurRadius: 32,
      offset: const Offset(0, 8),
    ),
  ];

  // ─── Font Helper ───────────────────────────────────────────────────────────
  static TextStyle _font({
    double? fontSize,
    FontWeight? fontWeight,
    Color? color,
    double? height,
  }) {
    if (kIsWeb) {
      return TextStyle(
        fontFamily: 'Plus Jakarta Sans',
        fontFamilyFallback: const ['Roboto', 'Segoe UI', 'Arial', 'sans-serif'],
        fontSize: fontSize,
        fontWeight: fontWeight,
        color: color,
        height: height,
      );
    }
    return GoogleFonts.plusJakartaSans(
      fontSize: fontSize,
      fontWeight: fontWeight,
      color: color,
      height: height,
      textStyle: const TextStyle(
        fontFamilyFallback: ['Roboto', 'Segoe UI', 'Arial', 'sans-serif'],
      ),
    );
  }

  // ─── Theme ─────────────────────────────────────────────────────────────────
  static ThemeData get lightTheme {
    final baseTextTheme = kIsWeb
        ? ThemeData.light().textTheme.apply(
            fontFamily: 'Plus Jakarta Sans',
            fontFamilyFallback: const ['Roboto', 'Segoe UI', 'Arial', 'sans-serif'],
          )
        : GoogleFonts.plusJakartaSansTextTheme();

    return ThemeData(
      useMaterial3: true,
      scaffoldBackgroundColor: canvasSofter,
      primaryColor: brandOrange,
      colorScheme: ColorScheme.fromSeed(
        seedColor: brandOrange,
        primary: brandOrange,
        onPrimary: onPrimary,
        secondary: brandAmber,
        surface: canvas,
        error: dangerRed,
      ),
      textTheme: baseTextTheme.copyWith(
        displayLarge: _font(fontSize: 36, fontWeight: FontWeight.w800, color: textInk, height: 1.22),
        displayMedium: _font(fontSize: 24, fontWeight: FontWeight.w700, color: textInk, height: 1.25),
        displaySmall: _font(fontSize: 20, fontWeight: FontWeight.w700, color: textInk, height: 1.3),
        headlineMedium: _font(fontSize: 22, fontWeight: FontWeight.w700, color: textInk),
        titleLarge: _font(fontSize: 18, fontWeight: FontWeight.w700, color: textInk),
        bodyLarge: _font(fontSize: 16, fontWeight: FontWeight.w400, color: textBody),
        bodyMedium: _font(fontSize: 14, fontWeight: FontWeight.w400, color: textBody),
        labelLarge: _font(fontSize: 16, fontWeight: FontWeight.w500, color: textInk),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: canvas,
        elevation: 0,
        scrolledUnderElevation: 0.5,
        centerTitle: false,
        iconTheme: const IconThemeData(color: textInk),
        titleTextStyle: _font(fontSize: 18, fontWeight: FontWeight.w700, color: textInk),
      ),
      cardTheme: CardThemeData(
        color: canvas,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusCard),
          side: const BorderSide(color: cardBorder, width: 1),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: brandOrange,
          foregroundColor: onPrimary,
          elevation: 0,
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 20),
          shape: const StadiumBorder(),
          textStyle: _font(fontSize: 16, fontWeight: FontWeight.w600),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: brandOrange,
          side: const BorderSide(color: brandOrange, width: 1.5),
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 20),
          shape: const StadiumBorder(),
          textStyle: _font(fontSize: 16, fontWeight: FontWeight.w600),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: canvasSoft,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusInput),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusInput),
          borderSide: const BorderSide(color: cardBorder, width: 1),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusInput),
          borderSide: const BorderSide(color: brandOrange, width: 1.5),
        ),
      ),
    );
  }
}
