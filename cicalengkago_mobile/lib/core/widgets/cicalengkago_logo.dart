import 'dart:math' as math;
import 'package:flutter/material.dart';

class CicalengkaGoLogo extends StatelessWidget {
  final double size;
  final double borderRadius;
  final bool showShadow;

  const CicalengkaGoLogo({
    super.key,
    this.size = 80,
    this.borderRadius = 22,
    this.showShadow = true,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(borderRadius),
        boxShadow: showShadow
            ? [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.15),
                  blurRadius: size * 0.2,
                  offset: Offset(0, size * 0.08),
                ),
              ]
            : null,
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(borderRadius),
        child: Image.asset(
          'assets/images/app_logo.png',
          width: size,
          height: size,
          fit: BoxFit.cover,
          errorBuilder: (context, error, stackTrace) {
            return Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(borderRadius),
                gradient: const LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    Color(0xFF262626),
                    Color(0xFF000000),
                  ],
                ),
              ),
              child: CustomPaint(
                size: Size(size, size),
                painter: _CicalengkaGoLogoPainter(),
              ),
            );
          },
        ),
      ),
    );
  }
}

class _CicalengkaGoLogoPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width * 0.27;

    // 1. Subtle Speed Arc Line
    final speedArcPaint = Paint()
      ..color = Colors.white.withValues(alpha: 0.25)
      ..style = PaintingStyle.stroke
      ..strokeWidth = size.width * 0.035
      ..strokeCap = StrokeCap.round;

    final speedArcRect = Rect.fromCircle(center: center, radius: radius * 1.05);
    canvas.drawArc(speedArcRect, math.pi * 0.9, math.pi * 0.65, false, speedArcPaint);

    // 2. Main Monogram 'C' Ring
    final cRingPaint = Paint()
      ..color = Colors.white
      ..style = PaintingStyle.stroke
      ..strokeWidth = size.width * 0.11
      ..strokeCap = StrokeCap.round;

    final cRingRect = Rect.fromCircle(center: center, radius: radius);
    canvas.drawArc(cRingRect, math.pi * -0.35, math.pi * 1.45, false, cRingPaint);

    // 3. Forward Speed Arrow on top wing
    final arrowPaint = Paint()
      ..color = Colors.white
      ..style = PaintingStyle.fill;

    final arrowPath = Path();
    final arrowScale = size.width / 120.0;
    arrowPath.moveTo(78 * arrowScale, 25 * arrowScale);
    arrowPath.lineTo(96 * arrowScale, 34 * arrowScale);
    arrowPath.lineTo(78 * arrowScale, 43 * arrowScale);
    arrowPath.close();

    canvas.drawPath(arrowPath, arrowPaint);

    // 4. Center Core Radar Dot
    final coreDotPaint = Paint()
      ..color = const Color(0xFFFFF1F2)
      ..style = PaintingStyle.fill;

    canvas.drawCircle(center, size.width * 0.09, coreDotPaint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
