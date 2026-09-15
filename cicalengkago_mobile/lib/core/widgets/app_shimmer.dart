import 'package:flutter/material.dart';

/// Reusable shimmer loading placeholder.
/// Tidak memerlukan package tambahan — menggunakan AnimationController.
class ShimmerBox extends StatefulWidget {
  final double width;
  final double height;
  final double borderRadius;
  final EdgeInsetsGeometry? margin;

  const ShimmerBox({
    super.key,
    this.width = double.infinity,
    this.height = 16,
    this.borderRadius = 8,
    this.margin,
  });

  @override
  State<ShimmerBox> createState() => _ShimmerBoxState();
}

class _ShimmerBoxState extends State<ShimmerBox>
    with SingleTickerProviderStateMixin {
  late AnimationController _ctrl;
  late Animation<double> _anim;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    )..repeat();
    _anim = Tween<double>(begin: -1.5, end: 1.5).animate(
      CurvedAnimation(parent: _ctrl, curve: Curves.easeInOutSine),
    );
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _anim,
      builder: (_, __) => Container(
        width: widget.width,
        height: widget.height,
        margin: widget.margin,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(widget.borderRadius),
          gradient: LinearGradient(
            begin: Alignment(_anim.value - 1, 0),
            end: Alignment(_anim.value + 1, 0),
            colors: const [
              Color(0xFFFFE8D6),
              Color(0xFFFFF0E8),
              Color(0xFFFFE8D6),
            ],
          ),
        ),
      ),
    );
  }
}

/// Shimmer card placeholder
class ShimmerCard extends StatelessWidget {
  final double height;
  final EdgeInsetsGeometry? margin;

  const ShimmerCard({super.key, this.height = 100, this.margin});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: height,
      margin: margin ?? const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            ShimmerBox(width: 64, height: 64, borderRadius: 14),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const ShimmerBox(height: 14, borderRadius: 6),
                  const SizedBox(height: 8),
                  ShimmerBox(width: MediaQuery.of(context).size.width * 0.4, height: 12, borderRadius: 6),
                  const SizedBox(height: 8),
                  const ShimmerBox(height: 10, borderRadius: 6),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Shimmer grid item placeholder
class ShimmerGridItem extends StatelessWidget {
  const ShimmerGridItem({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8),
        ],
      ),
      child: const Column(
        children: [
          Expanded(child: ShimmerBox(borderRadius: 16)),
          Padding(
            padding: EdgeInsets.all(8),
            child: Column(
              children: [
                ShimmerBox(height: 12, borderRadius: 4),
                SizedBox(height: 4),
                ShimmerBox(height: 10, borderRadius: 4),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Renders a list of ShimmerCard placeholders
class ShimmerList extends StatelessWidget {
  final int count;
  final double cardHeight;

  const ShimmerList({super.key, this.count = 4, this.cardHeight = 100});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: List.generate(
        count,
        (i) => ShimmerCard(height: cardHeight, margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6)),
      ),
    );
  }
}
