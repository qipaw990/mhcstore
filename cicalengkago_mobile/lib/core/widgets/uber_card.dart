import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

enum UberCardVariant { light, soft, dark, elevated }

class UberCard extends StatelessWidget {
  final Widget child;
  final UberCardVariant variant;
  final EdgeInsetsGeometry? padding;
  final VoidCallback? onTap;
  final double? width;
  final double? height;

  const UberCard({
    super.key,
    required this.child,
    this.variant = UberCardVariant.light,
    this.padding,
    this.onTap,
    this.width,
    this.height,
  });

  @override
  Widget build(BuildContext context) {
    Color bg;
    BorderSide? border;
    List<BoxShadow>? shadows;

    switch (variant) {
      case UberCardVariant.light:
        bg = AppTheme.canvas;
        shadows = null;
        border = const BorderSide(color: AppTheme.cardBorder, width: 1);
        break;
      case UberCardVariant.soft:
        bg = AppTheme.canvasSoft;
        shadows = null;
        border = null;
        break;
      case UberCardVariant.dark:
        bg = AppTheme.inkBlack;
        shadows = null;
        border = null;
        break;
      case UberCardVariant.elevated:
        bg = AppTheme.canvas;
        shadows = [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.12),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ];
        border = null;
        break;
    }

    Widget content = Container(
      width: width,
      height: height,
      padding: padding ?? const EdgeInsets.all(AppTheme.space2xl),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppTheme.radiusCard),
        border: border != null ? Border.all(color: border.color, width: border.width) : null,
        boxShadow: shadows,
      ),
      child: child,
    );

    if (onTap != null) {
      return Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(AppTheme.radiusCard),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(AppTheme.radiusCard),
          child: content,
        ),
      );
    }

    return content;
  }
}
