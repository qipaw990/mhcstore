import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/merchant_controller.dart';

class MerchantAnalyticsScreen extends StatefulWidget {
  const MerchantAnalyticsScreen({super.key});

  @override
  State<MerchantAnalyticsScreen> createState() => _MerchantAnalyticsScreenState();
}

class _MerchantAnalyticsScreenState extends State<MerchantAnalyticsScreen> {
  int _selectedFilter = 1; // 0: Hari Ini, 1: 7 Hari Terakhir, 2: Bulan Ini, 3: Semua Waktu

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MerchantController>().fetchAnalytics();
    });
  }

  @override
  Widget build(BuildContext context) {
    final merchantCtrl = context.watch<MerchantController>();
    final kpi = merchantCtrl.analyticsKpi ?? {};
    final dailyTrends = merchantCtrl.analyticsDailyTrends;
    final topProducts = merchantCtrl.analyticsTopProducts;
    final paymentBreakdown = merchantCtrl.analyticsPaymentBreakdown;
    final deliveryBreakdown = merchantCtrl.analyticsDeliveryBreakdown;
    final recentOrders = merchantCtrl.analyticsRecentOrders;
    final isLoading = merchantCtrl.isAnalyticsLoading;

    // Computed Filter Values
    double netRevenue = 0.0;
    double grossSales = 0.0;
    int ordersCount = 0;
    int canceledCount = int.tryParse(kpi['canceled_count']?.toString() ?? '0') ?? 0;
    double aov = double.tryParse(kpi['avg_order_value']?.toString() ?? '0') ?? 0.0;

    if (_selectedFilter == 0) {
      // Hari Ini
      netRevenue = double.tryParse(kpi['today_revenue']?.toString() ?? '0') ?? 0.0;
      ordersCount = int.tryParse(kpi['today_orders']?.toString() ?? '0') ?? 0;
      grossSales = netRevenue / 0.90;
    } else if (_selectedFilter == 1) {
      // 7 Hari Terakhir
      netRevenue = double.tryParse(kpi['week_revenue']?.toString() ?? '0') ?? 0.0;
      ordersCount = int.tryParse(kpi['week_orders']?.toString() ?? '0') ?? 0;
      grossSales = netRevenue / 0.90;
    } else if (_selectedFilter == 2) {
      // Bulan Ini
      netRevenue = double.tryParse(kpi['month_revenue']?.toString() ?? '0') ?? 0.0;
      ordersCount = int.tryParse(kpi['month_orders']?.toString() ?? '0') ?? 0;
      grossSales = netRevenue / 0.90;
    } else {
      // Semua Waktu
      netRevenue = double.tryParse(kpi['total_net_revenue']?.toString() ?? '0') ?? 0.0;
      grossSales = double.tryParse(kpi['total_gross_sales']?.toString() ?? '0') ?? 0.0;
      ordersCount = int.tryParse(kpi['delivered_count']?.toString() ?? '0') ?? 0;
    }

    // Profit based on filter
    double profit = 0.0;
    double marginPct = 0.0;
    if (_selectedFilter == 0) {
      profit = double.tryParse(kpi['today_profit']?.toString() ?? '0') ?? 0.0;
    } else if (_selectedFilter == 1) {
      profit = double.tryParse(kpi['week_profit']?.toString() ?? '0') ?? 0.0;
    } else if (_selectedFilter == 2) {
      profit = double.tryParse(kpi['month_profit']?.toString() ?? '0') ?? 0.0;
    } else {
      profit = double.tryParse(kpi['total_gross_profit']?.toString() ?? '0') ?? 0.0;
    }
    marginPct = double.tryParse(kpi['avg_margin_pct']?.toString() ?? '0') ?? 0.0;
    final totalCogs = double.tryParse(kpi['total_cogs']?.toString() ?? '0') ?? 0.0;

    final int totalAllAttempts = (ordersCount + canceledCount);
    final double successRate = totalAllAttempts > 0 ? ((ordersCount / totalAllAttempts) * 100) : 100.0;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0.5,
        title: const Text(
          'Laporan & Insight Penjualan',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
        ),
        actions: [
          IconButton(
            onPressed: () => merchantCtrl.fetchAnalytics(),
            icon: const Icon(Icons.refresh_rounded, color: AppTheme.primaryRed),
            tooltip: 'Segarkan Data',
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => merchantCtrl.fetchAnalytics(),
        child: isLoading && kpi.isEmpty
            ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryRed))
            : ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // ── TIME FILTER SELECTOR ──
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _filterChip(0, 'Hari Ini'),
                        const SizedBox(width: 8),
                        _filterChip(1, '7 Hari Terakhir'),
                        const SizedBox(width: 8),
                        _filterChip(2, 'Bulan Ini'),
                        const SizedBox(width: 8),
                        _filterChip(3, 'Semua Waktu'),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // ── PRIMARY REVENUE HERO CARD ──
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFFDC2626), Color(0xFF991B1B)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [
                        BoxShadow(
                          color: AppTheme.primaryRed.withValues(alpha: 0.3),
                          blurRadius: 15,
                          offset: const Offset(0, 5),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Pendapatan Bersih Toko (90%)',
                              style: TextStyle(fontSize: 13, color: Color(0xFFFCA5A5), fontWeight: FontWeight.w600),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.2),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Text(
                                _selectedFilter == 0
                                    ? 'Hari Ini'
                                    : (_selectedFilter == 1
                                        ? '7 Hari'
                                        : (_selectedFilter == 2 ? 'Bulan Ini' : 'Total')),
                                style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Text(
                          CurrencyFormatter.formatRupiah(netRevenue),
                          style: const TextStyle(
                            fontSize: 26,
                            fontWeight: FontWeight.w900,
                            color: Colors.white,
                            letterSpacing: -0.5,
                          ),
                        ),
                        const SizedBox(height: 14),
                        const Divider(height: 1, color: Color(0xFFEF4444)),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: _heroSubStat('Omzet Kotor (100%)', CurrencyFormatter.formatRupiah(grossSales)),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: _heroSubStat('Pesanan Berhasil', '$ordersCount Transaksi', alignRight: true),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // ── SECONDARY KPI SUMMARY GRID ──
                  Row(
                    children: [
                      Expanded(
                        child: _kpiCard(
                          title: 'Rata-rata Order (AOV)',
                          value: CurrencyFormatter.formatRupiah(aov),
                          subtitle: 'Per transaksi belanja',
                          icon: Icons.trending_up_rounded,
                          iconColor: const Color(0xFF2563EB),
                          bgColor: const Color(0xFFEFF6FF),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _kpiCard(
                          title: 'Tingkat Sukses',
                          value: '${successRate.toStringAsFixed(1)}%',
                          subtitle: '$ordersCount Selesai, $canceledCount Batal',
                          icon: Icons.check_circle_outline_rounded,
                          iconColor: const Color(0xFF16A34A),
                          bgColor: const Color(0xFFDCFCE7),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),

                  // ── PROFIT KPI CARDS ──
                  _buildSectionHeader('Keuntungan & Margin Usaha', Icons.monetization_on_rounded),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _profitCard(
                          label: 'Gross Profit',
                          value: CurrencyFormatter.formatRupiah(profit),
                          sub: profit > 0 ? '💚 Untung' : (profit < 0 ? '🔴 Rugi' : 'Belum ada data HPP'),
                          color: profit >= 0 ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                          bgColor: profit >= 0 ? const Color(0xFFDCFCE7) : const Color(0xFFFEE2E2),
                          icon: Icons.trending_up_rounded,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _profitCard(
                          label: 'Rata-rata Margin',
                          value: '${marginPct.toStringAsFixed(1)}%',
                          sub: 'Dari total penjualan',
                          color: const Color(0xFF7E22CE),
                          bgColor: const Color(0xFFF3E8FF),
                          icon: Icons.percent_rounded,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  if (totalCogs > 0)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFFBEB),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFFDE68A)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.inventory_2_outlined, size: 15, color: Color(0xFFD97706)),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Total Modal (HPP): ${CurrencyFormatter.formatRupiah(totalCogs)}',
                              style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: Color(0xFF92400E)),
                            ),
                          ),
                        ],
                      ),
                    ),
                  const SizedBox(height: 20),

                  // ── 7-DAY SALES BAR CHART ──
                  if (dailyTrends.isNotEmpty) ...[
                    _buildSectionHeader('Tren Penjualan 7 Hari Terakhir', Icons.bar_chart_rounded),
                    const SizedBox(height: 12),
                    _buildDailySalesChart(dailyTrends),
                    const SizedBox(height: 22),
                  ],

                  // ── RIWAYAT TRANSAKSI + PROFIT PER ORDER ──
                  if (recentOrders.isNotEmpty) ...[
                    _buildSectionHeader('Riwayat Transaksi & Profit', Icons.receipt_long_rounded),
                    const SizedBox(height: 12),
                    _buildRecentOrdersWithProfit(recentOrders),
                    const SizedBox(height: 22),
                  ],

                  // ── TOP SELLING PRODUCTS ──
                  _buildSectionHeader('Menu Terlaris & Paling Diminati', Icons.star_rounded),
                  const SizedBox(height: 12),
                  if (topProducts.isEmpty)
                    Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      alignment: Alignment.center,
                      child: const Text(
                        'Belum ada data penjualan menu untuk periode ini.',
                        style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                      ),
                    )
                  else
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.03),
                            blurRadius: 10,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: ListView.separated(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: topProducts.length,
                        separatorBuilder: (c, i) => const Divider(height: 1, color: Color(0xFFF1F5F9)),
                        itemBuilder: (context, index) {
                          final item = topProducts[index] as Map<String, dynamic>;
                          return _buildTopProductItem(item, index + 1);
                        },
                      ),
                    ),
                  const SizedBox(height: 22),

                  // ── PAYMENT & DELIVERY BREAKDOWN ──
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: _buildBreakdownCard(
                          title: 'Metode Bayar',
                          icon: Icons.payments_rounded,
                          iconColor: const Color(0xFF0284C7),
                          items: paymentBreakdown,
                          isPayment: true,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildBreakdownCard(
                          title: 'Pengantaran',
                          icon: Icons.moped_rounded,
                          iconColor: const Color(0xFFD97706),
                          items: deliveryBreakdown,
                          isPayment: false,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                ],
              ),
      ),
    );
  }

  Widget _filterChip(int index, String label) {
    final isSelected = _selectedFilter == index;
    return InkWell(
      onTap: () => setState(() => _selectedFilter = index),
      borderRadius: BorderRadius.circular(20),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? AppTheme.primaryRed : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? AppTheme.primaryRed : const Color(0xFFE2E8F0),
          ),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: AppTheme.primaryRed.withValues(alpha: 0.25),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ]
              : null,
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
            color: isSelected ? Colors.white : const Color(0xFF475569),
          ),
        ),
      ),
    );
  }

  Widget _heroSubStat(String label, String value, {bool alignRight = false}) {
    return Column(
      crossAxisAlignment: alignRight ? CrossAxisAlignment.end : CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 11, color: Color(0xFFFCA5A5)), maxLines: 1, overflow: TextOverflow.ellipsis),
        const SizedBox(height: 2),
        Text(
          value,
          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
      ],
    );
  }

  Widget _kpiCard({
    required String title,
    required String value,
    required String subtitle,
    required IconData icon,
    required Color iconColor,
    required Color bgColor,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 8,
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: 4),
              Container(
                padding: const EdgeInsets.all(5),
                decoration: BoxDecoration(color: bgColor, shape: BoxShape.circle),
                child: Icon(icon, color: iconColor, size: 13),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),
          Text(
            subtitle,
            style: const TextStyle(fontSize: 9.5, color: Color(0xFF94A3B8)),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title, IconData icon) {
    return Row(
      children: [
        Icon(icon, size: 18, color: const Color(0xFF334155)),
        const SizedBox(width: 6),
        Text(
          title,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
        ),
      ],
    );
  }

  Widget _buildDailySalesChart(List<dynamic> dailyTrends) {
    double maxRevenue = 1.0;
    for (var d in dailyTrends) {
      final rev = double.tryParse(d['revenue']?.toString() ?? '0') ?? 0.0;
      if (rev > maxRevenue) maxRevenue = rev;
    }

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Pendapatan Bersih Harian',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF64748B)),
              ),
              Text(
                'Maks: ${CurrencyFormatter.formatRupiah(maxRevenue)}',
                style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
              ),
            ],
          ),
          const SizedBox(height: 18),
          SizedBox(
            height: 130,
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: dailyTrends.map((d) {
                final rev = double.tryParse(d['revenue']?.toString() ?? '0') ?? 0.0;
                final dayName = d['day_name']?.toString() ?? '';
                final isToday = d['date'] == DateTime.now().toIso8601String().substring(0, 10);
                final ratio = (rev / maxRevenue).clamp(0.06, 1.0);

                return Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 3),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        if (rev > 0)
                          Text(
                            '${(rev / 1000).toStringAsFixed(0)}k',
                            style: TextStyle(
                              fontSize: 8.5,
                              fontWeight: FontWeight.bold,
                              color: isToday ? AppTheme.primaryRed : const Color(0xFF64748B),
                            ),
                          )
                        else
                          const SizedBox(height: 11),
                        const SizedBox(height: 4),
                        AnimatedContainer(
                          duration: const Duration(milliseconds: 300),
                          height: 80 * ratio,
                          decoration: BoxDecoration(
                            gradient: isToday
                                ? const LinearGradient(
                                    colors: [Color(0xFFEF4444), Color(0xFFDC2626)],
                                    begin: Alignment.topCenter,
                                    end: Alignment.bottomCenter,
                                  )
                                : const LinearGradient(
                                    colors: [Color(0xFF60A5FA), Color(0xFF3B82F6)],
                                    begin: Alignment.topCenter,
                                    end: Alignment.bottomCenter,
                                  ),
                            borderRadius: BorderRadius.circular(6),
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          dayName,
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: isToday ? FontWeight.bold : FontWeight.w500,
                            color: isToday ? AppTheme.primaryRed : const Color(0xFF64748B),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTopProductItem(Map<String, dynamic> item, int rank) {
    final name = item['product_name']?.toString() ?? 'Menu Kuliner';
    final rawImg = item['product_image']?.toString();
    final imgUrl = ApiConstants.formatImageUrl(rawImg);
    final sold = int.tryParse(item['total_sold']?.toString() ?? '0') ?? 0;
    final totalRev = double.tryParse(item['total_sales_amount']?.toString() ?? '0') ?? 0.0;
    final totalProfit = double.tryParse(item['total_profit']?.toString() ?? '0') ?? 0.0;
    final marginPct = double.tryParse(item['margin_pct']?.toString() ?? '0') ?? 0.0;
    final hasHpp = totalProfit != 0 || (item['product_hpp'] != null && double.tryParse(item['product_hpp'].toString()) != 0);

    Color badgeColor = const Color(0xFF94A3B8);
    Color badgeBg = const Color(0xFFF1F5F9);
    if (rank == 1) {
      badgeColor = const Color(0xFFD97706);
      badgeBg = const Color(0xFFFEF3C7);
    } else if (rank == 2) {
      badgeColor = const Color(0xFF475569);
      badgeBg = const Color(0xFFE2E8F0);
    } else if (rank == 3) {
      badgeColor = const Color(0xFFB45309);
      badgeBg = const Color(0xFFFFEDD5);
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      child: Row(
        children: [
          Container(
            width: 24,
            height: 24,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: badgeBg, shape: BoxShape.circle),
            child: Text('#$rank', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: badgeColor)),
          ),
          const SizedBox(width: 10),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: CachedNetworkImage(
              imageUrl: imgUrl,
              width: 42,
              height: 42,
              fit: BoxFit.cover,
              errorWidget: (context, url, error) => Container(
                width: 42,
                height: 42,
                color: const Color(0xFFF1F5F9),
                child: const Icon(Icons.restaurant_rounded, size: 20, color: Color(0xFF94A3B8)),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF0F172A)),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 2),
                Row(
                  children: [
                    Text(
                      CurrencyFormatter.formatRupiah(totalRev),
                      style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF16A34A)),
                    ),
                    if (hasHpp) ...[
                      const SizedBox(width: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                        decoration: BoxDecoration(
                          color: totalProfit >= 0 ? const Color(0xFFDCFCE7) : const Color(0xFFFEE2E2),
                          borderRadius: BorderRadius.circular(5),
                        ),
                        child: Text(
                          'Profit ${marginPct.toStringAsFixed(0)}%',
                          style: TextStyle(
                            fontSize: 9,
                            fontWeight: FontWeight.bold,
                            color: totalProfit >= 0 ? const Color(0xFF15803D) : const Color(0xFFDC2626),
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
                if (hasHpp)
                  Text(
                    'Untung: ${CurrencyFormatter.formatRupiah(totalProfit)}',
                    style: TextStyle(
                      fontSize: 9.5,
                      color: totalProfit >= 0 ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                    ),
                  ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: const Color(0xFFEFF6FF),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: const Color(0xFFBFDBFE)),
            ),
            child: Text(
              '$sold Terjual',
              style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF2563EB)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _profitCard({
    required String label,
    required String value,
    required String sub,
    required Color color,
    required Color bgColor,
    required IconData icon,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withValues(alpha: 0.25)),
        boxShadow: [
          BoxShadow(color: color.withValues(alpha: 0.08), blurRadius: 10, offset: const Offset(0, 2)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(8)),
                child: Icon(icon, size: 14, color: color),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(label, style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w600)),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            value,
            style: TextStyle(fontSize: 17, fontWeight: FontWeight.w900, color: color, letterSpacing: -0.3),
          ),
          const SizedBox(height: 2),
          Text(sub, style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
        ],
      ),
    );
  }

  Widget _buildRecentOrdersWithProfit(List<dynamic> orders) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 10, offset: const Offset(0, 2)),
        ],
      ),
      child: ListView.separated(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        itemCount: orders.length,
        separatorBuilder: (_, _) => const Divider(height: 1, color: Color(0xFFF1F5F9)),
        itemBuilder: (context, index) {
          final ord = orders[index] as Map<String, dynamic>;
          final code = ord['order_code']?.toString() ?? '#${ord['id']}';
          final amount = double.tryParse(ord['order_amount']?.toString() ?? '0') ?? 0.0;
          final orderProfit = double.tryParse(ord['order_profit']?.toString() ?? '0') ?? 0.0;
          final marginPct = double.tryParse(ord['margin_pct']?.toString() ?? '0') ?? 0.0;
          final status = ord['order_status']?.toString() ?? 'pending';
          final createdAt = ord['created_at']?.toString() ?? '';
          final hasHpp = orderProfit != 0;

          // Format tanggal
          String dateStr = '';
          try {
            final dt = DateTime.parse(createdAt);
            dateStr = '${dt.day}/${dt.month} ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
          } catch (_) {
            dateStr = createdAt.length > 10 ? createdAt.substring(5, 16).replaceAll('-', '/') : createdAt;
          }

          Color statusColor = const Color(0xFFD97706);
          if (status == 'delivered') statusColor = const Color(0xFF16A34A);
          if (status == 'canceled') statusColor = const Color(0xFFDC2626);

          return Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(
                    status == 'delivered' ? Icons.check_circle_rounded : (status == 'canceled' ? Icons.cancel_rounded : Icons.access_time_rounded),
                    color: statusColor,
                    size: 18,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        code,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A)),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        dateStr,
                        style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                      ),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      CurrencyFormatter.formatRupiah(amount),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A)),
                    ),
                    const SizedBox(height: 2),
                    if (status == 'delivered' && hasHpp)
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            orderProfit >= 0 ? Icons.arrow_upward_rounded : Icons.arrow_downward_rounded,
                            size: 10,
                            color: orderProfit >= 0 ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                          ),
                          const SizedBox(width: 2),
                          Text(
                            '${CurrencyFormatter.formatRupiah(orderProfit)} (${marginPct.toStringAsFixed(0)}%)',
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              color: orderProfit >= 0 ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                            ),
                          ),
                        ],
                      )
                    else
                      Text(
                        status == 'delivered' ? 'Set HPP untuk lihat profit' : status,
                        style: TextStyle(fontSize: 9.5, color: statusColor),
                      ),
                  ],
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildBreakdownCard({
    required String title,
    required IconData icon,
    required Color iconColor,
    required List<dynamic> items,
    required bool isPayment,
  }) {
    int totalCount = 0;
    for (var it in items) {
      totalCount += int.tryParse(it['count']?.toString() ?? '0') ?? 0;
    }

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 8,
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 15, color: iconColor),
              const SizedBox(width: 6),
              Text(
                title,
                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (items.isEmpty)
            const Text(
              'Belum ada data',
              style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
            )
          else
            ...items.map((it) {
              final count = int.tryParse(it['count']?.toString() ?? '0') ?? 0;
              final double percent = totalCount > 0 ? (count / totalCount) : 0.0;
              String label = '';
              if (isPayment) {
                final m = it['payment_method']?.toString().toLowerCase() ?? 'cod';
                label = (m == 'cod') ? 'Tunai (COD)' : (m == 'wallet' ? 'CicalengkaPay' : 'Online');
              } else {
                final d = it['delivery_type']?.toString().toLowerCase() ?? 'driver';
                label = (d == 'merchant') ? 'Diantar Toko' : 'Kurir Resmi';
              }

              return Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            label,
                            style: const TextStyle(fontSize: 10.5, color: Color(0xFF475569)),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 4),
                        Text(
                          '$count (${(percent * 100).toStringAsFixed(0)}%)',
                          style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: percent,
                        backgroundColor: const Color(0xFFF1F5F9),
                        color: isPayment ? const Color(0xFF0284C7) : const Color(0xFFD97706),
                        minHeight: 4,
                      ),
                    ),
                  ],
                ),
              );
            }),
        ],
      ),
    );
  }
}
