import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
import '../../auth/controllers/auth_controller.dart';
import '../../auth/screens/register_merchant_screen.dart';
import '../controllers/merchant_controller.dart';
import 'merchant_orders_screen.dart';
import 'product_management_screen.dart';
import 'store_settings_screen.dart';

class MerchantDashboardScreen extends StatefulWidget {
  const MerchantDashboardScreen({super.key});

  @override
  State<MerchantDashboardScreen> createState() => _MerchantDashboardScreenState();
}

class _MerchantDashboardScreenState extends State<MerchantDashboardScreen> {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MerchantController>().fetchDashboardData();
    });
  }

  @override
  Widget build(BuildContext context) {
    final merchantCtrl = context.watch<MerchantController>();
    final authCtrl = context.watch<AuthController>();
    final store = merchantCtrl.store;
    final isOpen = merchantCtrl.isOpen;
    final rawLogo = store?['logo']?.toString();
    final logoUrl = ApiConstants.formatImageUrl(rawLogo);

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 1,
        titleSpacing: 16,
        title: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: (logoUrl.isNotEmpty)
                  ? CachedNetworkImage(
                      imageUrl: logoUrl,
                      width: 38,
                      height: 38,
                      fit: BoxFit.cover,
                      errorWidget: (_, __, ___) => const CicalengkaGoLogo(size: 38, borderRadius: 10, showShadow: false),
                    )
                  : const CicalengkaGoLogo(size: 38, borderRadius: 10, showShadow: false),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    store?['name'] ?? 'Mitra Resto',
                    style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Row(
                    children: [
                      Container(
                        width: 7,
                        height: 7,
                        decoration: BoxDecoration(
                          color: isOpen ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 5),
                      Text(
                        isOpen ? 'Toko BUKA' : 'Toko TUTUP',
                        style: TextStyle(
                          fontSize: 11,
                          color: isOpen ? const Color(0xFF15803D) : const Color(0xFFB91C1C),
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                isOpen ? 'Buka' : 'Tutup',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: isOpen ? const Color(0xFF15803D) : const Color(0xFF94A3B8),
                ),
              ),
              const SizedBox(width: 4),
              Switch(
                value: isOpen,
                activeThumbColor: const Color(0xFF16A34A),
                activeTrackColor: const Color(0xFFDCFCE7),
                inactiveThumbColor: const Color(0xFF94A3B8),
                inactiveTrackColor: const Color(0xFFF1F5F9),
                onChanged: (val) => merchantCtrl.toggleStoreOpenStatus(val),
              ),
            ],
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: [
          _MerchantOverviewTab(onNavigateToTab: (idx) => setState(() => _currentIndex = idx)),
          const MerchantOrdersScreen(),
          const ProductManagementScreen(),
          const StoreSettingsScreen(),
        ],
      ),
      bottomNavigationBar: Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
        ),
        child: BottomNavigationBar(
          currentIndex: _currentIndex,
          selectedItemColor: AppTheme.primaryRed,
          unselectedItemColor: const Color(0xFF94A3B8),
          selectedLabelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11),
          unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.normal, fontSize: 11),
          backgroundColor: Colors.white,
          elevation: 0,
          type: BottomNavigationBarType.fixed,
          onTap: (idx) {
            setState(() {
              _currentIndex = idx;
            });
          },
          items: const [
            BottomNavigationBarItem(
              icon: Icon(Icons.dashboard_outlined),
              activeIcon: Icon(Icons.dashboard_rounded),
              label: 'Ringkasan',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.receipt_long_outlined),
              activeIcon: Icon(Icons.receipt_long_rounded),
              label: 'Pesanan',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.restaurant_menu_outlined),
              activeIcon: Icon(Icons.restaurant_menu_rounded),
              label: 'Menu',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.storefront_outlined),
              activeIcon: Icon(Icons.storefront_rounded),
              label: 'Resto Saya',
            ),
          ],
        ),
      ),
    );
  }
}

// ── TAB 1: RINGKASAN / OVERVIEW TOKO ──
class _MerchantOverviewTab extends StatelessWidget {
  final Function(int) onNavigateToTab;
  const _MerchantOverviewTab({required this.onNavigateToTab});

  @override
  Widget build(BuildContext context) {
    final merchantCtrl = context.watch<MerchantController>();
    final stats = merchantCtrl.stats ?? {};
    final wallet = merchantCtrl.wallet ?? {};
    final balance = double.tryParse(wallet['balance']?.toString() ?? '0') ?? 0.0;
    final todayRevenue = double.tryParse(stats['today_revenue']?.toString() ?? '0') ?? 0.0;
    final todayOrders = int.tryParse(stats['today_orders']?.toString() ?? '0') ?? 0;
    final pendingCount = int.tryParse(stats['pending_count']?.toString() ?? '0') ?? 0;
    final processingCount = int.tryParse(stats['processing_count']?.toString() ?? '0') ?? 0;
    final handoverCount = int.tryParse(stats['on_the_way_count']?.toString() ?? '0') ?? 0;
    final deliveredCount = int.tryParse(stats['delivered_count']?.toString() ?? '0') ?? 0;
    final store = merchantCtrl.store;
    final storeStatus = store?['status']?.toString().toLowerCase();
    final recentOrders = merchantCtrl.orders.take(5).toList();

    return RefreshIndicator(
      onRefresh: () => merchantCtrl.fetchDashboardData(),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // ── BANNER JIKA TOKO BELUM DIDAFTARKAN ──
          if (store == null) ...[
            Container(
              padding: const EdgeInsets.all(16),
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                color: const Color(0xFFEFF6FF),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFF93C5FD)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: const [
                      Icon(Icons.storefront_rounded, color: Color(0xFF2563EB), size: 22),
                      SizedBox(width: 8),
                      Text(
                        'Toko Belum Didaftarkan',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF1E40AF)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Lengkapi data toko dan lokasi usaha Anda untuk mulai menerima pesanan pelanggan di CicalengkaGO.',
                    style: TextStyle(fontSize: 12, color: Color(0xFF3B82F6), height: 1.35),
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => const RegisterMerchantScreen()),
                        );
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF2563EB),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 10),
                      ),
                      icon: const Icon(Icons.add_business_rounded, size: 16),
                      label: const Text('Daftarkan Toko Sekarang', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                    ),
                  ),
                ],
              ),
            ),
          ] else if (storeStatus == 'pending') ...[
            Container(
              padding: const EdgeInsets.all(14),
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                color: const Color(0xFFFFFBEB),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFFDE68A)),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: const BoxDecoration(
                      color: Color(0xFFF59E0B),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.hourglass_top_rounded, color: Colors.white, size: 16),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Pendaftaran ${store['name'] ?? 'Toko'} Sedang Ditinjau',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF92400E)),
                        ),
                        const SizedBox(height: 2),
                        const Text(
                          'Tim Admin CicalengkaGO sedang memverifikasi data usaha Anda.',
                          style: TextStyle(fontSize: 11, color: Color(0xFFB45309)),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],

          // ── KARTU STATISTIK KEUANGAN HARI INI ──
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFFDC2626), Color(0xFF991B1B)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: AppTheme.primaryRed.withValues(alpha: 0.25),
                  blurRadius: 12,
                  offset: const Offset(0, 4),
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
                      'Pendapatan Hari Ini',
                      style: TextStyle(fontSize: 12.5, color: Color(0xFFFCA5A5), fontWeight: FontWeight.w600),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        '$todayOrders Pesanan Hari Ini',
                        style: const TextStyle(color: Colors.white, fontSize: 10.5, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  CurrencyFormatter.formatRupiah(todayRevenue),
                  style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: -0.5),
                ),
                const SizedBox(height: 14),
                const Divider(height: 1, color: Color(0xFFEF4444)),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Saldo Dompet Aktif', style: TextStyle(fontSize: 11, color: Color(0xFFFCA5A5))),
                        const SizedBox(height: 2),
                        Text(
                          CurrencyFormatter.formatRupiah(balance),
                          style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                      ],
                    ),
                    InkWell(
                      onTap: () => onNavigateToTab(3), // Navigate to StoreSettings/Wallet Tab
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              'Kelola Saldo',
                              style: TextStyle(color: AppTheme.primaryRed, fontSize: 11.5, fontWeight: FontWeight.bold),
                            ),
                            SizedBox(width: 4),
                            Icon(Icons.arrow_forward_ios_rounded, size: 10, color: AppTheme.primaryRed),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // ── QUICK STATUS PIPELINE ──
          Row(
            children: [
              Expanded(
                child: _statusBox(
                  icon: Icons.pending_actions_rounded,
                  color: const Color(0xFFD97706),
                  bgColor: const Color(0xFFFEF3C7),
                  count: pendingCount,
                  label: 'Menunggu',
                  onTap: () => onNavigateToTab(1),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _statusBox(
                  icon: Icons.restaurant_rounded,
                  color: const Color(0xFF2563EB),
                  bgColor: const Color(0xFFEFF6FF),
                  count: processingCount,
                  label: 'Dimasak',
                  onTap: () => onNavigateToTab(1),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _statusBox(
                  icon: Icons.delivery_dining_rounded,
                  color: const Color(0xFF7E22CE),
                  bgColor: const Color(0xFFF3E8FF),
                  count: handoverCount,
                  label: 'Siap Driver',
                  onTap: () => onNavigateToTab(1),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _statusBox(
                  icon: Icons.check_circle_rounded,
                  color: const Color(0xFF15803D),
                  bgColor: const Color(0xFFDCFCE7),
                  count: deliveredCount,
                  label: 'Selesai',
                  onTap: () => onNavigateToTab(1),
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),

          // ── QUICK SHORTCUTS ──
          Row(
            children: [
              Expanded(
                child: _shortcutButton(
                  icon: Icons.add_circle_outline_rounded,
                  color: const Color(0xFF2563EB),
                  label: 'Tambah Menu Baru',
                  onTap: () => onNavigateToTab(2),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _shortcutButton(
                  icon: Icons.receipt_long_rounded,
                  color: const Color(0xFF16A34A),
                  label: 'Lihat Semua Pesanan',
                  onTap: () => onNavigateToTab(1),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // ── PESANAN TERBARU ──
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Pesanan Terbaru',
                style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
              ),
              TextButton(
                onPressed: () => onNavigateToTab(1),
                child: const Text(
                  'Lihat Semua >',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),

          if (recentOrders.isEmpty)
            Container(
              padding: const EdgeInsets.all(24),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                children: const [
                  Icon(Icons.inbox_outlined, size: 36, color: Color(0xFF94A3B8)),
                  SizedBox(height: 8),
                  Text('Belum ada pesanan masuk', style: TextStyle(fontSize: 13, color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                ],
              ),
            )
          else
            ...recentOrders.map((o) {
              final oMap = o is Map ? (o as Map<String, dynamic>) : <String, dynamic>{};
              final code = oMap['order_code']?.toString() ?? oMap['id']?.toString() ?? '-';
              final customer = oMap['customer_name']?.toString() ?? 'Pelanggan';
              final total = double.tryParse(oMap['order_amount']?.toString() ?? '0') ?? 0.0;
              final status = (oMap['order_status'] ?? 'pending').toString().toLowerCase();

              return Container(
                margin: const EdgeInsets.only(bottom: 8),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.receipt_outlined, color: AppTheme.primaryRed, size: 18),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('#$code • $customer', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF0F172A))),
                          const SizedBox(height: 2),
                          Text(CurrencyFormatter.formatRupiah(total * 0.90), style: const TextStyle(fontSize: 11.5, color: Color(0xFF16A34A), fontWeight: FontWeight.bold)),
                        ],
                      ),
                    ),
                    InkWell(
                      onTap: () => onNavigateToTab(1),
                      borderRadius: BorderRadius.circular(8),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: Text(
                          status == 'pending' ? 'Respon >' : status,
                          style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF475569)),
                        ),
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

  Widget _statusBox({
    required IconData icon,
    required Color color,
    required Color bgColor,
    required int count,
    required String label,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 6),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0xFFE2E8F0)),
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 4, offset: const Offset(0, 1)),
          ],
        ),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(color: bgColor, shape: BoxShape.circle),
              child: Icon(icon, color: color, size: 16),
            ),
            const SizedBox(height: 6),
            Text(
              '$count',
              style: TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: count > 0 ? color : const Color(0xFF64748B)),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: const TextStyle(fontSize: 10, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  Widget _shortcutButton({
    required IconData icon,
    required Color color,
    required String label,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(10)),
              child: Icon(icon, color: color, size: 18),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                label,
                style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                maxLines: 2,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
