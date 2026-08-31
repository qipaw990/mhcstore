import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
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
  String _trendMetric = 'revenue'; // 'revenue', 'profit', 'orders'

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MerchantController>().fetchAnalytics();
      context.read<MerchantController>().fetchSmartInsights(silent: true);
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
    final smartInsights = merchantCtrl.smartInsights;
    final isLoading = merchantCtrl.isAnalyticsLoading;

    // Computed Filter Values
    double netRevenue = 0.0;
    double grossSales = 0.0;
    int ordersCount = 0;
    int canceledCount = int.tryParse(kpi['canceled_count']?.toString() ?? '0') ?? 0;
    double aov = double.tryParse(kpi['avg_order_value']?.toString() ?? '0') ?? 0.0;
    double profit = 0.0;
    double cogs = 0.0;
    double? growthPct;
    String growthLabel = '';

    if (_selectedFilter == 0) {
      // Hari Ini
      netRevenue = double.tryParse(kpi['today_revenue']?.toString() ?? '0') ?? 0.0;
      final todayGross = double.tryParse(kpi['today_gross']?.toString() ?? '0') ?? 0.0;
      grossSales = todayGross > 0 ? todayGross : (netRevenue / 0.90);
      ordersCount = int.tryParse(kpi['today_delivered']?.toString() ?? kpi['today_orders']?.toString() ?? '0') ?? 0;
      profit = double.tryParse(kpi['today_profit']?.toString() ?? '0') ?? 0.0;
      cogs = double.tryParse(kpi['today_cogs']?.toString() ?? '0') ?? 0.0;
      growthPct = double.tryParse(kpi['today_growth_pct']?.toString() ?? '');
      growthLabel = 'vs Kemarin';
    } else if (_selectedFilter == 1) {
      // 7 Hari Terakhir
      netRevenue = double.tryParse(kpi['week_revenue']?.toString() ?? '0') ?? 0.0;
      final weekGross = double.tryParse(kpi['week_gross']?.toString() ?? '0') ?? 0.0;
      grossSales = weekGross > 0 ? weekGross : (netRevenue / 0.90);
      ordersCount = int.tryParse(kpi['week_delivered']?.toString() ?? kpi['week_orders']?.toString() ?? '0') ?? 0;
      profit = double.tryParse(kpi['week_profit']?.toString() ?? '0') ?? 0.0;
      cogs = double.tryParse(kpi['week_cogs']?.toString() ?? '0') ?? 0.0;
      growthPct = double.tryParse(kpi['week_growth_pct']?.toString() ?? '');
      growthLabel = 'vs 7 Hari Lalu';
    } else if (_selectedFilter == 2) {
      // Bulan Ini
      netRevenue = double.tryParse(kpi['month_revenue']?.toString() ?? '0') ?? 0.0;
      final monthGross = double.tryParse(kpi['month_gross']?.toString() ?? '0') ?? 0.0;
      grossSales = monthGross > 0 ? monthGross : (netRevenue / 0.90);
      ordersCount = int.tryParse(kpi['month_delivered']?.toString() ?? kpi['month_orders']?.toString() ?? '0') ?? 0;
      profit = double.tryParse(kpi['month_profit']?.toString() ?? '0') ?? 0.0;
      cogs = double.tryParse(kpi['month_cogs']?.toString() ?? '0') ?? 0.0;
      growthPct = double.tryParse(kpi['month_growth_pct']?.toString() ?? '');
      growthLabel = 'vs Bulan Lalu';
    } else {
      // Semua Waktu
      netRevenue = double.tryParse(kpi['total_net_revenue']?.toString() ?? '0') ?? 0.0;
      grossSales = double.tryParse(kpi['total_gross_sales']?.toString() ?? '0') ?? 0.0;
      ordersCount = int.tryParse(kpi['delivered_count']?.toString() ?? '0') ?? 0;
      profit = double.tryParse(kpi['total_gross_profit']?.toString() ?? '0') ?? 0.0;
      cogs = double.tryParse(kpi['total_cogs']?.toString() ?? '0') ?? 0.0;
      growthPct = null;
    }

    final double marginPct = grossSales > 0 ? ((profit / grossSales) * 100) : 0.0;
    final int uniqueCust = int.tryParse(kpi['unique_customers']?.toString() ?? '0') ?? 0;
    final int repeatCust = int.tryParse(kpi['repeat_customers']?.toString() ?? '0') ?? 0;
    final double repeatRate = double.tryParse(kpi['repeat_rate_pct']?.toString() ?? '0') ?? 0.0;

    final int totalAllAttempts = (ordersCount + canceledCount);
    final double successRate = totalAllAttempts > 0 ? ((ordersCount / totalAllAttempts) * 100) : 100.0;
    final double platformFee = grossSales > netRevenue ? (grossSales - netRevenue) : (grossSales * 0.10);

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
            onPressed: () => _shareAnalyticsWhatsApp(
              storeName: merchantCtrl.store?['name']?.toString() ?? 'Toko Mitra',
              periodName: _filterLabel(_selectedFilter),
              grossSales: grossSales,
              netRevenue: netRevenue,
              profit: profit,
              cogs: cogs,
              ordersCount: ordersCount,
              topProducts: topProducts,
            ),
            icon: const Icon(Icons.share_rounded, color: Color(0xFF16A34A), size: 20),
            tooltip: 'Bagikan Laporan ke WA',
          ),
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
                  // ── TUTUP KASIR HARIAN BANNER BUTTON ──
                  Container(
                    margin: const EdgeInsets.only(bottom: 14),
                    child: ElevatedButton.icon(
                      onPressed: () => _showDailySettlementModal(context),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF16A34A),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        elevation: 0,
                      ),
                      icon: const Icon(Icons.point_of_sale_rounded, size: 18),
                      label: const Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Rekap Kas & Tutup Kasir Hari Ini',
                                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5),
                                ),
                                Text(
                                  'Hitung kas laci, laba & bagikan ke WhatsApp Owner',
                                  style: TextStyle(fontSize: 10, color: Color(0xFFDCFCE7)),
                                ),
                              ],
                            ),
                          ),
                          Icon(Icons.arrow_forward_ios_rounded, size: 12),
                        ],
                      ),
                    ),
                  ),

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
                            Row(
                              children: [
                                if (growthPct != null) ...[
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                                    decoration: BoxDecoration(
                                      color: growthPct >= 0 ? const Color(0xFF16A34A) : Colors.black.withValues(alpha: 0.3),
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Icon(
                                          growthPct >= 0 ? Icons.arrow_upward_rounded : Icons.arrow_downward_rounded,
                                          size: 11,
                                          color: Colors.white,
                                        ),
                                        const SizedBox(width: 2),
                                        Text(
                                          '${growthPct >= 0 ? '+' : ''}${growthPct.toStringAsFixed(1)}%',
                                          style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                                        ),
                                      ],
                                    ),
                                  ),
                                  const SizedBox(width: 6),
                                ],
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(
                                    color: Colors.white.withValues(alpha: 0.2),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Text(
                                    _filterLabel(_selectedFilter),
                                    style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                                  ),
                                ),
                              ],
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
                        if (growthLabel.isNotEmpty && growthPct != null) ...[
                          const SizedBox(height: 3),
                          Text(
                            '$growthLabel (${growthPct >= 0 ? '+' : ''}${growthPct.toStringAsFixed(1)}%)',
                            style: const TextStyle(fontSize: 10.5, color: Color(0xFFFECACA)),
                          ),
                        ],
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

                  // ── FINANCIAL WATERFALL FLOW CARD (ARUS KEUANGAN) ──
                  _buildFinancialFlowCard(
                    grossSales: grossSales,
                    platformFee: platformFee,
                    netRevenue: netRevenue,
                    cogs: cogs,
                    netProfit: profit,
                    marginPct: marginPct,
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
                  const SizedBox(height: 12),

                  // ── CUSTOMER RETENTION & LOYALTY CARD ──
                  if (uniqueCust > 0) ...[
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                        boxShadow: [
                          BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 8),
                        ],
                      ),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: const Color(0xFFEEF2FF),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(Icons.people_alt_rounded, size: 20, color: Color(0xFF4F46E5)),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Pelanggan & Retensi Toko',
                                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF0F172A)),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  '$uniqueCust Pelanggan Unik • $repeatCust Pelanggan Setia (≥2x order)',
                                  style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: const Color(0xFFECFDF5),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: const Color(0xFFA7F3D0)),
                            ),
                            child: Column(
                              children: [
                                Text(
                                  '${repeatRate.toStringAsFixed(0)}%',
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w900, color: Color(0xFF059669)),
                                ),
                                const Text('Repeat Rate', style: TextStyle(fontSize: 8.5, color: Color(0xFF059669), fontWeight: FontWeight.bold)),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),
                  ],

                  // ── PROFIT KPI CARDS ──
                  _buildSectionHeader('Keuntungan & Margin Usaha', Icons.monetization_on_rounded),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _profitCard(
                          label: 'Laba Bersih Riil',
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
                          label: 'Margin Keuntungan',
                          value: '${marginPct.toStringAsFixed(1)}%',
                          sub: 'Dari omzet penjualan',
                          color: const Color(0xFF7E22CE),
                          bgColor: const Color(0xFFF3E8FF),
                          icon: Icons.percent_rounded,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  if (cogs > 0)
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
                              'Total Biaya Modal (HPP): ${CurrencyFormatter.formatRupiah(cogs)}',
                              style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: Color(0xFF92400E)),
                            ),
                          ),
                        ],
                      ),
                    ),
                  const SizedBox(height: 20),

                  // ── 7-DAY SALES & PROFIT BAR CHART (INTERAKTIF TOGGLE) ──
                  if (dailyTrends.isNotEmpty) ...[
                    _buildSectionHeader('Tren Penjualan 7 Hari Terakhir', Icons.bar_chart_rounded),
                    const SizedBox(height: 10),
                    _buildDailyTrendsInteractiveChart(dailyTrends),
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
                  _buildSectionHeader('Menu Terlaris & Kontribusi Omzet', Icons.star_rounded),
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

                  // ── SMART MENU ENGINEERING MATRIX ──
                  _buildMenuEngineeringSection(smartInsights),
                  const SizedBox(height: 22),

                  // ── PEAK HOURS BREAKDOWN ──
                  _buildPeakHoursSection(smartInsights),
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

  String _filterLabel(int filter) {
    switch (filter) {
      case 0:
        return 'Hari Ini';
      case 1:
        return '7 Hari';
      case 2:
        return 'Bulan Ini';
      default:
        return 'Total';
    }
  }

  Widget _buildFinancialFlowCard({
    required double grossSales,
    required double platformFee,
    required double netRevenue,
    required double cogs,
    required double netProfit,
    required double marginPct,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 8),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(8)),
                child: const Icon(Icons.account_balance_wallet_outlined, size: 16, color: Color(0xFF334155)),
              ),
              const SizedBox(width: 8),
              const Text(
                'Arus & Laba Keuangan Transparan',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
              ),
            ],
          ),
          const SizedBox(height: 12),
          _flowItem('1. Total Omzet Kotor Pelanggan (100%)', CurrencyFormatter.formatRupiah(grossSales), const Color(0xFF0F172A), isHeader: true),
          _flowItem('2. Biaya Layanan CicalengkaGO (10%)', '- ${CurrencyFormatter.formatRupiah(platformFee)}', const Color(0xFFDC2626)),
          _flowItem('3. Pendapatan Bersih Mitra (90%)', CurrencyFormatter.formatRupiah(netRevenue), const Color(0xFF2563EB), isBold: true),
          _flowItem('4. Estimasi Biaya Bahan / Modal (HPP)', '- ${CurrencyFormatter.formatRupiah(cogs)}', const Color(0xFFD97706)),
          const Divider(height: 14, color: Color(0xFFE2E8F0)),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Laba Bersih Toko (Net Profit):',
                style: TextStyle(fontWeight: FontWeight.w800, fontSize: 12.5, color: Color(0xFF0F172A)),
              ),
              Text(
                '${CurrencyFormatter.formatRupiah(netProfit)} (${marginPct.toStringAsFixed(1)}%)',
                style: TextStyle(
                  fontWeight: FontWeight.w900,
                  fontSize: 13,
                  color: netProfit >= 0 ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _flowItem(String title, String value, Color color, {bool isHeader = false, bool isBold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 11,
              color: isHeader ? const Color(0xFF334155) : const Color(0xFF64748B),
              fontWeight: (isHeader || isBold) ? FontWeight.bold : FontWeight.normal,
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: (isHeader || isBold) ? FontWeight.w800 : FontWeight.w600,
              color: color,
            ),
          ),
        ],
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
        Text(label, style: const TextStyle(fontSize: 10.5, color: Color(0xFFFCA5A5)), maxLines: 1, overflow: TextOverflow.ellipsis),
        const SizedBox(height: 2),
        FittedBox(
          fit: BoxFit.scaleDown,
          alignment: alignRight ? Alignment.centerRight : Alignment.centerLeft,
          child: Text(
            value,
            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white),
            maxLines: 1,
          ),
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
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
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
                  style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              const SizedBox(width: 4),
              Container(
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(color: bgColor, shape: BoxShape.circle),
                child: Icon(icon, color: iconColor, size: 12),
              ),
            ],
          ),
          const SizedBox(height: 6),
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Text(
              value,
              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
              maxLines: 1,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            subtitle,
            style: const TextStyle(fontSize: 9, color: Color(0xFF94A3B8)),
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

  Widget _buildDailyTrendsInteractiveChart(List<dynamic> dailyTrends) {
    double maxValue = 1.0;
    for (var d in dailyTrends) {
      double val = 0.0;
      if (_trendMetric == 'revenue') {
        val = double.tryParse(d['revenue']?.toString() ?? '0') ?? 0.0;
      } else if (_trendMetric == 'profit') {
        val = double.tryParse(d['profit']?.toString() ?? '0') ?? 0.0;
      } else {
        val = (int.tryParse(d['delivered_orders']?.toString() ?? '0') ?? 0).toDouble();
      }
      if (val > maxValue) maxValue = val;
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
          // Metric Switcher Chips
          Row(
            children: [
              _chartMetricChip('revenue', '💰 Omzet'),
              const SizedBox(width: 6),
              _chartMetricChip('profit', '📈 Laba'),
              const SizedBox(width: 6),
              _chartMetricChip('orders', '📦 Order'),
              const Spacer(),
              Text(
                _trendMetric == 'orders'
                    ? 'Puncak: ${maxValue.toInt()} pesanan'
                    : 'Puncak: ${CurrencyFormatter.formatRupiah(maxValue)}',
                style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
              ),
            ],
          ),
          const SizedBox(height: 16),
          SizedBox(
            height: 135,
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: dailyTrends.map((d) {
                double val = 0.0;
                if (_trendMetric == 'revenue') {
                  val = double.tryParse(d['revenue']?.toString() ?? '0') ?? 0.0;
                } else if (_trendMetric == 'profit') {
                  val = double.tryParse(d['profit']?.toString() ?? '0') ?? 0.0;
                } else {
                  val = (int.tryParse(d['delivered_orders']?.toString() ?? '0') ?? 0).toDouble();
                }

                final dayName = d['day_name']?.toString() ?? '';
                final isToday = d['date'] == DateTime.now().toIso8601String().substring(0, 10);
                final ratio = (val / maxValue).clamp(0.06, 1.0);

                String topLabel = '';
                if (val > 0) {
                  if (_trendMetric == 'orders') {
                    topLabel = '${val.toInt()}';
                  } else {
                    topLabel = '${(val / 1000).toStringAsFixed(0)}k';
                  }
                }

                LinearGradient gradient;
                if (isToday) {
                  gradient = const LinearGradient(
                    colors: [Color(0xFFEF4444), Color(0xFFDC2626)],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  );
                } else if (_trendMetric == 'profit') {
                  gradient = const LinearGradient(
                    colors: [Color(0xFF34D399), Color(0xFF059669)],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  );
                } else if (_trendMetric == 'orders') {
                  gradient = const LinearGradient(
                    colors: [Color(0xFFA78BFA), Color(0xFF7C3AED)],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  );
                } else {
                  gradient = const LinearGradient(
                    colors: [Color(0xFF60A5FA), Color(0xFF2563EB)],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  );
                }

                return Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 3),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        if (topLabel.isNotEmpty)
                          Text(
                            topLabel,
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
                            gradient: gradient,
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

  Widget _chartMetricChip(String metricKey, String label) {
    final isSelected = _trendMetric == metricKey;
    return InkWell(
      onTap: () => setState(() => _trendMetric = metricKey),
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 10.5,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
            color: isSelected ? Colors.white : const Color(0xFF64748B),
          ),
        ),
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
    final contributionPct = double.tryParse(item['contribution_pct']?.toString() ?? '0') ?? 0.0;
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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
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
                    Wrap(
                      crossAxisAlignment: WrapCrossAlignment.center,
                      spacing: 6,
                      children: [
                        Text(
                          CurrencyFormatter.formatRupiah(totalRev),
                          style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF16A34A)),
                        ),
                        if (hasHpp)
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
          if (contributionPct > 0) ...[
            const SizedBox(height: 6),
            Row(
              children: [
                Expanded(
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(3),
                    child: LinearProgressIndicator(
                      value: (contributionPct / 100).clamp(0.01, 1.0),
                      minHeight: 4,
                      backgroundColor: const Color(0xFFF1F5F9),
                      color: const Color(0xFF3B82F6),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Text(
                  '${contributionPct.toStringAsFixed(1)}% dari total omzet',
                  style: const TextStyle(fontSize: 9, color: Color(0xFF94A3B8)),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  void _shareAnalyticsWhatsApp({
    required String storeName,
    required String periodName,
    required double grossSales,
    required double netRevenue,
    required double profit,
    required double cogs,
    required int ordersCount,
    required List<dynamic> topProducts,
  }) async {
    final now = DateTime.now();
    final dateFormatted = '${now.day}/${now.month}/${now.year} ${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')}';

    final buffer = StringBuffer();
    buffer.writeln('📊 *LAPORAN PENJUALAN & LABA TOKO*');
    buffer.writeln('🏢 *Toko*: $storeName');
    buffer.writeln('📅 *Periode*: $periodName');
    buffer.writeln('⏱️ *Waktu Ekspor*: $dateFormatted');
    buffer.writeln('────────────────────────');
    buffer.writeln('💰 *Omzet Kotor (100%)*: ${CurrencyFormatter.formatRupiah(grossSales)}');
    buffer.writeln('💵 *Pendapatan Bersih (90%)*: ${CurrencyFormatter.formatRupiah(netRevenue)}');
    if (cogs > 0) {
      buffer.writeln('📦 *Estimasi Modal (HPP)*: ${CurrencyFormatter.formatRupiah(cogs)}');
    }
    buffer.writeln('📈 *Laba Bersih Riil*: ${CurrencyFormatter.formatRupiah(profit)}');
    buffer.writeln('📦 *Pesanan Berhasil*: $ordersCount Transaksi');
    buffer.writeln('────────────────────────');

    if (topProducts.isNotEmpty) {
      buffer.writeln('⭐ *Top 3 Menu Terlaris*:');
      final maxTop = topProducts.take(3).toList();
      for (int i = 0; i < maxTop.length; i++) {
        final tp = maxTop[i] as Map<String, dynamic>;
        final pName = tp['product_name'] ?? 'Menu';
        final sold = tp['total_sold'] ?? 0;
        final rev = double.tryParse(tp['total_sales_amount']?.toString() ?? '0') ?? 0;
        buffer.writeln('  ${i + 1}. $pName ($sold porsi - ${CurrencyFormatter.formatRupiah(rev)})');
      }
      buffer.writeln('────────────────────────');
    }
    buffer.writeln('_Laporan digenerate otomatis oleh CicalengkaGO Merchant Analytics_');

    final encoded = Uri.encodeComponent(buffer.toString());
    final url = Uri.parse('https://api.whatsapp.com/send?text=$encoded');
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    }
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
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
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
                padding: const EdgeInsets.all(5),
                decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(7)),
                child: Icon(icon, size: 13, color: color),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label,
                  style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Text(
              value,
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: color, letterSpacing: -0.3),
              maxLines: 1,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            sub,
            style: const TextStyle(fontSize: 9, color: Color(0xFF94A3B8)),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
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
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
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
              Icon(icon, size: 14, color: iconColor),
              const SizedBox(width: 5),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          if (items.isEmpty)
            const Text(
              'Belum ada data',
              style: TextStyle(fontSize: 10.5, color: Color(0xFF94A3B8)),
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
                            style: const TextStyle(fontSize: 10, color: Color(0xFF475569)),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 4),
                        Text(
                          '$count (${(percent * 100).toStringAsFixed(0)}%)',
                          style: const TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
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

  void _showDailySettlementModal(BuildContext context) async {
    final ctrl = context.read<MerchantController>();
    ctrl.fetchDailySettlement();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Consumer<MerchantController>(
          builder: (context, c, _) {
            final data = c.dailySettlement;
            final isLoad = c.isSettlementLoading;

            if (isLoad || data == null) {
              return Container(
                padding: const EdgeInsets.all(40),
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
                ),
                child: const Center(child: CircularProgressIndicator(color: Color(0xFF16A34A))),
              );
            }

            final dateStr = data['formatted_date'] ?? 'Hari Ini';
            final storeName = data['store_name'] ?? 'Toko Mitra';
            final grossSales = double.tryParse(data['gross_sales']?.toString() ?? '0') ?? 0.0;
            final netRevenue = double.tryParse(data['net_revenue']?.toString() ?? '0') ?? 0.0;
            final cashAmount = double.tryParse(data['cash_amount']?.toString() ?? '0') ?? 0.0;
            final nonCashAmount = double.tryParse(data['non_cash_amount']?.toString() ?? '0') ?? 0.0;
            final totalCogs = double.tryParse(data['total_cogs']?.toString() ?? '0') ?? 0.0;
            final grossProfit = double.tryParse(data['gross_profit']?.toString() ?? '0') ?? 0.0;
            final marginPct = double.tryParse(data['margin_pct']?.toString() ?? '0') ?? 0.0;
            final completedOrders = data['completed_orders'] ?? 0;
            final canceledOrders = data['canceled_orders'] ?? 0;
            final waMessage = data['wa_message']?.toString() ?? '';

            return Container(
              constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.90),
              padding: const EdgeInsets.all(20),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Center(
                    child: Container(
                      width: 36,
                      height: 4,
                      decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: const Color(0xFF16A34A).withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.point_of_sale_rounded, color: Color(0xFF16A34A), size: 22),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Laporan Tutup Kasir Harian',
                              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                            ),
                            Text(
                              '$storeName • $dateStr',
                              style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                            ),
                          ],
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF64748B)),
                        onPressed: () => Navigator.pop(ctx),
                      ),
                    ],
                  ),
                  const Divider(height: 20, color: Color(0xFFF1F5F9)),
                  Expanded(
                    child: ListView(
                      children: [
                        // Cash vs Non-Cash Highlight Card
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(18),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text(
                                    'Uang Kas di Laci (Tunai)',
                                    style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                                  ),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2.5),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFF16A34A),
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: const Text('Cash in Hand', style: TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.bold)),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Text(
                                CurrencyFormatter.formatRupiah(cashAmount),
                                style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: Colors.white),
                              ),
                              const SizedBox(height: 12),
                              const Divider(height: 1, color: Color(0xFF334155)),
                              const SizedBox(height: 10),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    'Non-Tunai: ${CurrencyFormatter.formatRupiah(nonCashAmount)}',
                                    style: const TextStyle(fontSize: 11, color: Color(0xFFCBD5E1)),
                                  ),
                                  Text(
                                    '$completedOrders Order Berhasil',
                                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF38BDF8)),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 14),

                        // Breakdown Details Card
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF8FAFC),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Ringkasan Finansial',
                                style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFF334155)),
                              ),
                              const SizedBox(height: 10),
                              _settlementRow('Total Omzet Kotor (100%)', CurrencyFormatter.formatRupiah(grossSales), bold: true),
                              _settlementRow('Pendapatan Bersih (90%)', CurrencyFormatter.formatRupiah(netRevenue), color: const Color(0xFF2563EB)),
                              _settlementRow('Total Modal (HPP)', CurrencyFormatter.formatRupiah(totalCogs), color: const Color(0xFFDC2626)),
                              const Divider(height: 14, color: Color(0xFFE2E8F0)),
                              _settlementRow(
                                'Laba Bersih Toko',
                                CurrencyFormatter.formatRupiah(grossProfit),
                                bold: true,
                                color: grossProfit >= 0 ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                                trailingExtra: ' (${marginPct.toStringAsFixed(1)}%)',
                              ),
                              if (canceledOrders > 0) ...[
                                const Divider(height: 14, color: Color(0xFFE2E8F0)),
                                _settlementRow('Pesanan Dibatalkan', '$canceledOrders Transaksi', color: const Color(0xFFDC2626)),
                              ],
                            ],
                          ),
                        ),
                        const SizedBox(height: 20),
                      ],
                    ),
                  ),

                  // WhatsApp Share Button
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF16A34A),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        elevation: 0,
                      ),
                      icon: const Icon(Icons.share_rounded, size: 20),
                      label: const Text(
                        'Kirim Laporan ke WhatsApp Owner',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                      ),
                      onPressed: () async {
                        final encoded = Uri.encodeComponent(waMessage);
                        final url = Uri.parse('https://api.whatsapp.com/send?text=$encoded');
                        if (await canLaunchUrl(url)) {
                          await launchUrl(url, mode: LaunchMode.externalApplication);
                        }
                      },
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Widget _settlementRow(String label, String value, {bool bold = false, Color? color, String? trailingExtra}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 12, color: const Color(0xFF64748B), fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
          RichText(
            text: TextSpan(
              children: [
                TextSpan(
                  text: value,
                  style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: bold ? FontWeight.w800 : FontWeight.w600,
                    color: color ?? const Color(0xFF0F172A),
                  ),
                ),
                if (trailingExtra != null)
                  TextSpan(
                    text: trailingExtra,
                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: color ?? const Color(0xFF0F172A)),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMenuEngineeringSection(Map<String, dynamic>? smartInsights) {
    if (smartInsights == null) return const SizedBox.shrink();

    final stars = (smartInsights['star_products'] as List<dynamic>?) ?? [];
    final potentials = (smartInsights['potential_products'] as List<dynamic>?) ?? [];
    final thinMargins = (smartInsights['thin_margin_products'] as List<dynamic>?) ?? [];

    if (stars.isEmpty && potentials.isEmpty && thinMargins.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionHeader('Matriks Menu Pintar & Rekomendasi', Icons.auto_awesome_rounded),
        const SizedBox(height: 12),

        if (stars.isNotEmpty) ...[
          _buildMatrixCard(
            title: '🌟 Menu Bintang (Laris & Laba Tinggi)',
            subtitle: 'Menu paling menguntungkan. Pertahankan stok & pasang banner promo!',
            color: const Color(0xFFD97706),
            bgColor: const Color(0xFFFFFBEB),
            borderColor: const Color(0xFFFDE68A),
            items: stars,
          ),
          const SizedBox(height: 12),
        ],

        if (potentials.isNotEmpty) ...[
          _buildMatrixCard(
            title: '🚀 Menu Potensial (Margin Tinggi)',
            subtitle: 'Margin tebal tapi order masih rendah. Rekomendasi: Buat promo / flash sale!',
            color: const Color(0xFF2563EB),
            bgColor: const Color(0xFFEFF6FF),
            borderColor: const Color(0xFFBFDBFE),
            items: potentials,
          ),
          const SizedBox(height: 12),
        ],

        if (thinMargins.isNotEmpty) ...[
          _buildMatrixCard(
            title: '⚠️ Menu Margin Tipis (Perlu Evaluasi)',
            subtitle: 'Laris tapi laba tipis (< 20%). Rekomendasi: Naikkan harga sedikit / nego HPP.',
            color: const Color(0xFFDC2626),
            bgColor: const Color(0xFFFEF2F2),
            borderColor: const Color(0xFFFECACA),
            items: thinMargins,
          ),
          const SizedBox(height: 12),
        ],
      ],
    );
  }

  Widget _buildMatrixCard({
    required String title,
    required String subtitle,
    required Color color,
    required Color bgColor,
    required Color borderColor,
    required List<dynamic> items,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: color)),
          const SizedBox(height: 2),
          Text(subtitle, style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B))),
          const SizedBox(height: 10),
          ...items.map((it) {
            final name = it['name']?.toString() ?? 'Menu';
            final sold = it['total_sold'] ?? 0;
            final margin = double.tryParse(it['margin_pct']?.toString() ?? '0') ?? 0.0;
            final profit = double.tryParse(it['total_profit']?.toString() ?? '0') ?? 0.0;
            final tip = it['tip']?.toString() ?? '';

            return Container(
              margin: const EdgeInsets.only(bottom: 6),
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: borderColor.withValues(alpha: 0.6)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          name,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A)),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(6)),
                        child: Text(
                          'Margin ${margin.toStringAsFixed(0)}%',
                          style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: color),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Terjual: $sold porsi', style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B))),
                      Text('Profit: ${CurrencyFormatter.formatRupiah(profit)}', style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: Color(0xFF16A34A))),
                    ],
                  ),
                  if (tip.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text('💡 $tip', style: const TextStyle(fontSize: 9.5, fontStyle: FontStyle.italic, color: Color(0xFF475569))),
                  ],
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildPeakHoursSection(Map<String, dynamic>? smartInsights) {
    if (smartInsights == null) return const SizedBox.shrink();

    final timeBlocks = (smartInsights['time_blocks'] as List<dynamic>?) ?? [];
    final busiest = smartInsights['busiest_period']?.toString() ?? 'Siang';
    final busiestHour = smartInsights['busiest_hour']?.toString() ?? '-';

    if (timeBlocks.isEmpty) return const SizedBox.shrink();

    int totalOrders = 0;
    for (var b in timeBlocks) {
      totalOrders += (b['count'] as int? ?? 0);
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionHeader('Pola Jam Ramai & Distribusi Waktu', Icons.schedule_rounded),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.all(16),
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
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: const Color(0xFF16A34A).withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.local_fire_department_rounded, color: Color(0xFF16A34A), size: 18),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Waktu Puncak: $busiest', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF0F172A))),
                        Text('Jam tersibuk sekitar $busiestHour (siapkan stok bahan & karyawan)', style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B))),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              ...timeBlocks.map((b) {
                final label = b['label']?.toString() ?? '';
                final count = (b['count'] as int? ?? 0);
                final ratio = totalOrders > 0 ? (count / totalOrders) : 0.0;
                final isBusiest = label.contains(busiest);

                return Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            label,
                            style: TextStyle(
                              fontSize: 11.5,
                              fontWeight: isBusiest ? FontWeight.bold : FontWeight.w500,
                              color: isBusiest ? const Color(0xFF0F172A) : const Color(0xFF475569),
                            ),
                          ),
                          Text(
                            '$count Pesanan (${(ratio * 100).toStringAsFixed(0)}%)',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: isBusiest ? FontWeight.bold : FontWeight.w600,
                              color: isBusiest ? const Color(0xFF16A34A) : const Color(0xFF64748B),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(6),
                        child: LinearProgressIndicator(
                          value: ratio,
                          minHeight: 8,
                          backgroundColor: const Color(0xFFF1F5F9),
                          valueColor: AlwaysStoppedAnimation<Color>(
                            isBusiest ? const Color(0xFF16A34A) : const Color(0xFF3B82F6),
                          ),
                        ),
                      ),
                    ],
                  ),
                );
              }),
            ],
          ),
        ),
      ],
    );
  }
}
