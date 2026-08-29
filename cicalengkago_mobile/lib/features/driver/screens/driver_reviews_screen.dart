import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../controllers/driver_controller.dart';

class DriverReviewsScreen extends StatefulWidget {
  const DriverReviewsScreen({super.key});

  @override
  State<DriverReviewsScreen> createState() => _DriverReviewsScreenState();
}

class _DriverReviewsScreenState extends State<DriverReviewsScreen> {
  int _selectedFilter = 0; // 0: Semua, 1: Bintang 5, 2: Bintang 4, 3: Bintang <=3, 4: Dengan Komentar

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DriverController>().fetchProfile();
      context.read<DriverController>().fetchEarnings();
    });
  }

  @override
  Widget build(BuildContext context) {
    final driverCtrl = context.watch<DriverController>();
    final List<dynamic> rawReviews = driverCtrl.reviews;
    final double rating = driverCtrl.rating;
    final int totalReviews = rawReviews.length;

    // Filter reviews based on user selection
    final List<dynamic> filteredReviews = rawReviews.where((item) {
      if (item is! Map) return false;
      final double r = double.tryParse(item['rating']?.toString() ?? '5') ?? 5.0;
      final String comment = (item['comment'] ?? item['review'] ?? '').toString().trim();

      if (_selectedFilter == 1) return r >= 4.8;
      if (_selectedFilter == 2) return r >= 3.8 && r < 4.8;
      if (_selectedFilter == 3) return r < 3.8;
      if (_selectedFilter == 4) return comment.isNotEmpty;
      return true; // 0: Semua
    }).toList();

    // Calculate rating counts for breakdown
    int star5 = 0, star4 = 0, star3 = 0, star2 = 0, star1 = 0;
    for (final rev in rawReviews) {
      if (rev is Map) {
        final double r = double.tryParse(rev['rating']?.toString() ?? '5') ?? 5.0;
        if (r >= 4.8) star5++;
        else if (r >= 3.8) star4++;
        else if (r >= 2.8) star3++;
        else if (r >= 1.8) star2++;
        else star1++;
      }
    }

    return Scaffold(
      backgroundColor: const Color(0xFF090D16),
      appBar: AppBar(
        title: const Row(
          children: [
            Icon(Icons.star_rounded, color: Colors.amber, size: 22),
            SizedBox(width: 8),
            Text(
              'Ulasan Pelanggan',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
            ),
          ],
        ),
        backgroundColor: const Color(0xFF0F172A),
        elevation: 0,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(height: 1, color: const Color(0xFF1E293B)),
        ),
      ),
      body: RefreshIndicator(
        color: AppTheme.primaryRed,
        onRefresh: () async {
          await Future.wait([
            driverCtrl.fetchProfile(),
            driverCtrl.fetchEarnings(),
          ]);
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            // 1. Rating Summary Header Card
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
                child: _buildRatingSummaryCard(
                  rating: rating,
                  totalReviews: totalReviews,
                  star5: star5,
                  star4: star4,
                  star3: star3,
                  star2: star2,
                  star1: star1,
                ),
              ),
            ),

            // 2. Filter Chips
            SliverToBoxAdapter(
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                child: Row(
                  children: [
                    _buildFilterChip(0, 'Semua ($totalReviews)'),
                    const SizedBox(width: 8),
                    _buildFilterChip(1, '⭐ 5 ($star5)'),
                    const SizedBox(width: 8),
                    _buildFilterChip(2, '⭐ 4 ($star4)'),
                    const SizedBox(width: 8),
                    _buildFilterChip(3, '⭐ 1-3 (${star1 + star2 + star3})'),
                    const SizedBox(width: 8),
                    _buildFilterChip(4, '💬 Dengan Komentar'),
                  ],
                ),
              ),
            ),

            // 3. Reviews List or Empty State
            if (filteredReviews.isEmpty)
              SliverFillRemaining(
                hasScrollBody: false,
                child: Center(
                  child: Padding(
                    padding: const EdgeInsets.all(32),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          width: 80,
                          height: 80,
                          decoration: BoxDecoration(
                            color: Colors.amber.withValues(alpha: 0.12),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.rate_review_outlined, color: Colors.amber, size: 40),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          _selectedFilter == 0
                              ? 'Belum Ada Ulasan'
                              : 'Tidak Ada Ulasan Sesuai Filter',
                          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Tetap pertahankan layanan ramah, cepat, dan amanah untuk mendapatkan bintang 5 dari pelanggan!',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8), height: 1.4),
                        ),
                      ],
                    ),
                  ),
                ),
              )
            else
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (context, index) => _buildReviewCard(filteredReviews[index]),
                    childCount: filteredReviews.length,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip(int id, String label) {
    final bool isSelected = (_selectedFilter == id);
    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: () => setState(() => _selectedFilter = id),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFFEF4444) : const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? const Color(0xFFEF4444) : const Color(0xFF334155),
          ),
          boxShadow: isSelected
              ? [BoxShadow(color: const Color(0xFFEF4444).withValues(alpha: 0.3), blurRadius: 6)]
              : null,
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11.5,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
            color: isSelected ? Colors.white : const Color(0xFF94A3B8),
          ),
        ),
      ),
    );
  }

  Widget _buildRatingSummaryCard({
    required double rating,
    required int totalReviews,
    required int star5,
    required int star4,
    required int star3,
    required int star2,
    required int star1,
  }) {
    final int safeTotal = totalReviews > 0 ? totalReviews : 1;

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFF1E293B)),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 10,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Left: Big Rating Score
          Expanded(
            flex: 4,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  rating.toStringAsFixed(1),
                  style: const TextStyle(
                    fontSize: 42,
                    fontWeight: FontWeight.w900,
                    color: Colors.white,
                    height: 1.0,
                  ),
                ),
                const SizedBox(height: 6),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: List.generate(5, (index) {
                    final double starVal = index + 1.0;
                    return Icon(
                      starVal <= rating
                          ? Icons.star_rounded
                          : (starVal - rating <= 0.5 ? Icons.star_half_rounded : Icons.star_outline_rounded),
                      color: Colors.amber,
                      size: 16,
                    );
                  }),
                ),
                const SizedBox(height: 6),
                Text(
                  '$totalReviews Ulasan Total',
                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF94A3B8)),
                ),
              ],
            ),
          ),

          Container(width: 1, height: 90, color: const Color(0xFF1E293B), margin: const EdgeInsets.symmetric(horizontal: 14)),

          // Right: Star Distribution Bars
          Expanded(
            flex: 6,
            child: Column(
              children: [
                _buildStarBar(5, star5, star5 / safeTotal),
                const SizedBox(height: 4),
                _buildStarBar(4, star4, star4 / safeTotal),
                const SizedBox(height: 4),
                _buildStarBar(3, star3, star3 / safeTotal),
                const SizedBox(height: 4),
                _buildStarBar(2, star2, star2 / safeTotal),
                const SizedBox(height: 4),
                _buildStarBar(1, star1, star1 / safeTotal),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStarBar(int star, int count, double progress) {
    return Row(
      children: [
        Text('$star', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF94A3B8))),
        const SizedBox(width: 2),
        const Icon(Icons.star_rounded, color: Colors.amber, size: 11),
        const SizedBox(width: 6),
        Expanded(
          child: ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: progress.clamp(0.0, 1.0),
              minHeight: 6,
              backgroundColor: const Color(0xFF1E293B),
              valueColor: AlwaysStoppedAnimation<Color>(
                star >= 4 ? const Color(0xFF22C55E) : (star == 3 ? Colors.amber : const Color(0xFFEF4444)),
              ),
            ),
          ),
        ),
        const SizedBox(width: 6),
        SizedBox(
          width: 18,
          child: Text(
            '$count',
            textAlign: TextAlign.right,
            style: const TextStyle(fontSize: 9.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
          ),
        ),
      ],
    );
  }

  Widget _buildReviewCard(dynamic rev) {
    final Map<String, dynamic> data = rev is Map<String, dynamic> ? rev : (rev is Map ? Map<String, dynamic>.from(rev) : {});
    final double r = double.tryParse(data['rating']?.toString() ?? '5') ?? 5.0;
    final String comment = (data['comment'] ?? data['review'] ?? '').toString().trim();
    final String customerName = (data['customer_name'] ?? data['user_name'] ?? 'Pelanggan Cicalengka').toString();
    final String date = (data['created_at'] ?? data['date'] ?? '').toString();
    final String orderCode = (data['order_code'] ?? '').toString();

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E293B)),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 6,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header: Avatar, Name, Rating Chip, Date
          Row(
            children: [
              CircleAvatar(
                radius: 18,
                backgroundColor: const Color(0xFF064E3B),
                child: Text(
                  customerName.isNotEmpty ? customerName[0].toUpperCase() : 'P',
                  style: const TextStyle(color: Color(0xFF34D399), fontWeight: FontWeight.bold, fontSize: 13),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      customerName,
                      style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Colors.white),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (date.isNotEmpty)
                      Text(
                        _formatDate(date),
                        style: const TextStyle(fontSize: 10, color: Color(0xFF64748B)),
                      ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF451A03),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFF78350F)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.star_rounded, color: Color(0xFFFBBF24), size: 14),
                    const SizedBox(width: 3),
                    Text(
                      r.toStringAsFixed(1),
                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFFFDE68A)),
                    ),
                  ],
                ),
              ),
            ],
          ),

          // Order Tag if available
          if (orderCode.isNotEmpty) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                'Pesanan #$orderCode',
                style: const TextStyle(fontSize: 9.5, fontWeight: FontWeight.w600, color: Color(0xFF94A3B8)),
              ),
            ),
          ],

          // Comment Quote Bubble
          if (comment.isNotEmpty) ...[
            const SizedBox(height: 10),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFF334155)),
              ),
              child: Text(
                '“$comment”',
                style: const TextStyle(fontSize: 11.5, color: Color(0xFFE2E8F0), fontStyle: FontStyle.italic, height: 1.3),
              ),
            ),
          ],
        ],
      ),
    );
  }

  String _formatDate(String dateStr) {
    try {
      final dt = DateTime.parse(dateStr);
      final months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
      return '${dt.day} ${months[dt.month - 1]} ${dt.year} • ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return dateStr;
    }
  }
}
