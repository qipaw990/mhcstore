import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../theme/app_theme.dart';

enum UberPillVariant { primary, secondary, subtle, accent }

class UberPillButton extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;
  final UberPillVariant variant;
  final IconData? icon;
  final bool fullWidth;
  final double paddingVertical;
  final double paddingHorizontal;
  final Color? bgColor;
  final Color? backgroundColor;
  final Color? textColor;
  final Color? fgColor;

  const UberPillButton({
    super.key,
    required this.label,
    this.onPressed,
    this.variant = UberPillVariant.primary,
    this.icon,
    this.fullWidth = false,
    this.paddingVertical = 14,
    this.paddingHorizontal = 20,
    this.bgColor,
    this.backgroundColor,
    this.textColor,
    this.fgColor,
  });

  @override
  Widget build(BuildContext context) {
    Color bg;
    Color fg;
    BorderSide border = BorderSide.none;
    List<BoxShadow>? shadows;

    switch (variant) {
      case UberPillVariant.primary:
        bg = AppTheme.inkBlack;
        fg = AppTheme.onPrimary;
        break;
      case UberPillVariant.secondary:
        bg = AppTheme.canvas;
        fg = AppTheme.textInk;
        border = const BorderSide(color: AppTheme.surfacePressed, width: 1.5);
        shadows = [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ];
        break;
      case UberPillVariant.subtle:
        bg = AppTheme.canvasSoft;
        fg = AppTheme.textInk;
        break;
      case UberPillVariant.accent:
        bg = AppTheme.primaryRed;
        fg = AppTheme.onPrimary;
        break;
    }

    bg = bgColor ?? backgroundColor ?? bg;
    fg = textColor ?? fgColor ?? fg;

    final childWidget = Row(
      mainAxisSize: fullWidth ? MainAxisSize.max : MainAxisSize.min,
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        if (icon != null) ...[
          Icon(icon, size: 18, color: fg),
          const SizedBox(width: 8),
        ],
        Flexible(
          child: Text(
            label,
            style: GoogleFonts.plusJakartaSans(
              color: fg,
              fontSize: 15,
              fontWeight: FontWeight.w600,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(AppTheme.radiusPill),
        boxShadow: shadows,
      ),
      child: Material(
        color: bg,
        borderRadius: BorderRadius.circular(AppTheme.radiusPill),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onPressed,
          borderRadius: BorderRadius.circular(AppTheme.radiusPill),
          child: Container(
            decoration: BoxDecoration(
              border: border != BorderSide.none ? Border.all(color: border.color, width: border.width) : null,
              borderRadius: BorderRadius.circular(AppTheme.radiusPill),
            ),
            padding: EdgeInsets.symmetric(
              vertical: paddingVertical,
              horizontal: paddingHorizontal,
            ),
            child: childWidget,
          ),
        ),
      ),
    );
  }
}
