import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../auth/controllers/auth_controller.dart';
import '../../common/screens/in_app_chat_modal.dart';
import '../controllers/merchant_controller.dart';

class MerchantOrdersScreen extends StatefulWidget {
  const MerchantOrdersScreen({super.key});

  @override
  State<MerchantOrdersScreen> createState() => _MerchantOrdersScreenState();
}

class _MerchantOrdersScreenState extends State<MerchantOrdersScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final List<String> _filters = ['Semua', 'Baru', 'Dimasak', 'Siap Driver', 'Selesai', 'Batal'];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: _filters.length, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MerchantController>().fetchOrders();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  List<dynamic> _filterOrders(List<dynamic> orders, int tabIndex) {
    if (tabIndex == 0) return orders;
    final filter = _filters[tabIndex];
    return orders.where((o) {
      final status = (o['order_status'] ?? '').toString().toLowerCase();
      if (filter == 'Baru') return status == 'pending' || status == 'confirmed';
      if (filter == 'Dimasak') return status == 'processing';
      if (filter == 'Siap Driver') return status == 'handover' || status == 'picked_up' || status == 'on_the_way';
      if (filter == 'Selesai') return status == 'delivered';
      if (filter == 'Batal') return status == 'canceled';
      return true;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final merchantCtrl = context.watch<MerchantController>();
    final allOrders = merchantCtrl.orders;

    return Column(
      children: [
        // Tab Bar Filter
        Container(
          color: Colors.white,
          child: TabBar(
            controller: _tabController,
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            indicatorColor: AppTheme.primaryRed,
            labelColor: AppTheme.primaryRed,
            unselectedLabelColor: const Color(0xFF64748B),
            labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
            unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.normal, fontSize: 13),
            indicatorWeight: 3,
            tabs: _filters.map((f) {
              final count = _filterOrders(allOrders, _filters.indexOf(f)).length;
              return Tab(
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(f),
                    if (count > 0) ...[
                      const SizedBox(width: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: (f == 'Baru' && count > 0) ? AppTheme.primaryRed : const Color(0xFFE2E8F0),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          '$count',
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                            color: (f == 'Baru' && count > 0) ? Colors.white : const Color(0xFF475569),
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              );
            }).toList(),
          ),
        ),

        // Order List
        Expanded(
          child: TabBarView(
            controller: _tabController,
            children: _filters.asMap().entries.map((entry) {
              final filtered = _filterOrders(allOrders, entry.key);

              if (merchantCtrl.isLoading) {
                return const Center(child: CircularProgressIndicator(color: AppTheme.primaryRed));
              }

              if (filtered.isEmpty) {
                return RefreshIndicator(
                  onRefresh: () => merchantCtrl.fetchOrders(),
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: [
                      SizedBox(height: MediaQuery.of(context).size.height * 0.2),
                      Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              padding: const EdgeInsets.all(20),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF1F5F9),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.receipt_long_rounded, size: 48, color: Color(0xFF94A3B8)),
                            ),
                            const SizedBox(height: 14),
                            Text(
                              'Tidak ada pesanan di kategori "${entry.value}"',
                              style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Color(0xFF64748B)),
                            ),
                            const SizedBox(height: 4),
                            const Text(
                              'Tarik ke bawah untuk memuat ulang daftar pesanan',
                              style: TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                );
              }

              return RefreshIndicator(
                onRefresh: () => merchantCtrl.fetchOrders(),
                child: ListView.builder(
                  padding: const EdgeInsets.all(14),
                  itemCount: filtered.length,
                  itemBuilder: (context, index) {
                    final order = filtered[index] is Map ? (filtered[index] as Map<String, dynamic>) : <String, dynamic>{};
                    return _buildOrderCard(context, merchantCtrl, order);
                  },
                ),
              );
            }).toList(),
          ),
        ),
      ],
    );
  }

  Widget _buildOrderCard(BuildContext context, MerchantController ctrl, Map<String, dynamic> order) {
    final orderCode = order['order_code']?.toString() ?? order['id']?.toString() ?? '-';
    final status = (order['order_status'] ?? 'pending').toString().toLowerCase();
    final total = double.tryParse(order['order_amount']?.toString() ?? '0') ?? 0.0;
    final vendorEarning = total * 0.90;
    final customerName = order['customer_name']?.toString() ?? 'Pelanggan';
    final items = order['items'] is List ? (order['items'] as List) : [];
    final orderNotes = order['order_notes']?.toString() ?? '';
    final createdAt = order['created_at']?.toString() ?? '';

    Color statusBgColor = const Color(0xFFFEF3C7);
    Color statusTextColor = const Color(0xFFB45309);
    String statusLabel = 'Menunggu Konfirmasi';

    if (status == 'processing') {
      statusBgColor = const Color(0xFFEFF6FF);
      statusTextColor = const Color(0xFF1D4ED8);
      statusLabel = 'Sedang Dimasak';
    } else if (status == 'handover') {
      statusBgColor = const Color(0xFFF3E8FF);
      statusTextColor = const Color(0xFF7E22CE);
      statusLabel = 'Siap Diambil Driver';
    } else if (status == 'picked_up' || status == 'on_the_way') {
      statusBgColor = const Color(0xFFE0F2FE);
      statusTextColor = const Color(0xFF0369A1);
      statusLabel = 'Diantar Driver';
    } else if (status == 'delivered') {
      statusBgColor = const Color(0xFFDCFCE7);
      statusTextColor = const Color(0xFF15803D);
      statusLabel = 'Selesai';
    } else if (status == 'canceled') {
      statusBgColor = const Color(0xFFFEE2E2);
      statusTextColor = const Color(0xFFB91C1C);
      statusLabel = 'Dibatalkan';
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 8, offset: const Offset(0, 2)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header Card
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: const BoxDecoration(
              color: Color(0xFFF8FAFC),
              borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
              border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(Icons.receipt_outlined, size: 16, color: AppTheme.primaryRed),
                    const SizedBox(width: 6),
                    Text(
                      '#$orderCode',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
                  decoration: BoxDecoration(
                    color: statusBgColor,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    statusLabel,
                    style: TextStyle(color: statusTextColor, fontSize: 10.5, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
          ),

          // Body Card
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Customer & Time Info
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.person_rounded, size: 14, color: Color(0xFF64748B)),
                        const SizedBox(width: 4),
                        Text(
                          customerName,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1E293B)),
                        ),
                      ],
                    ),
                    if (createdAt.isNotEmpty)
                      Text(
                        createdAt.length > 16 ? createdAt.substring(11, 16) : createdAt,
                        style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                      ),
                  ],
                ),
                const SizedBox(height: 10),

                // Items list
                if (items.isNotEmpty) ...[
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFFF1F5F9)),
                    ),
                    child: Column(
                      children: items.map((it) {
                        final iMap = it is Map ? it : {};
                        final name = iMap['product_name'] ?? iMap['name'] ?? 'Menu';
                        final qty = iMap['quantity'] ?? iMap['qty'] ?? 1;
                        final itemPrice = double.tryParse(iMap['price']?.toString() ?? '0') ?? 0.0;
                        return Padding(
                          padding: const EdgeInsets.symmetric(vertical: 2.5),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Text(
                                  '${qty}x $name',
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              Text(
                                CurrencyFormatter.formatRupiah(itemPrice * qty),
                                style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                              ),
                            ],
                          ),
                        );
                      }).toList(),
                    ),
                  ),
                  const SizedBox(height: 10),
                ],

                // Order Notes
                if (orderNotes.isNotEmpty) ...[
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEF3C7),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.note_alt_outlined, size: 14, color: Color(0xFFB45309)),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'Catatan: $orderNotes',
                            style: const TextStyle(fontSize: 11, color: Color(0xFF92400E), fontWeight: FontWeight.w500),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 10),
                ],

                // Total Earning
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Pendapatan Bersih Toko (90%):', style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B))),
                    Text(
                      CurrencyFormatter.formatRupiah(vendorEarning),
                      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Color(0xFF16A34A)),
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Action Buttons
                _buildActionButtons(context, ctrl, order, status),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionButtons(BuildContext context, MerchantController ctrl, Map<String, dynamic> order, String status) {
    final orderId = int.tryParse(order['id']?.toString() ?? '0') ?? 0;
    final orderCode = order['order_code']?.toString() ?? order['id']?.toString() ?? '';

    return Row(
      children: [
        // Chat Customer In-App
        OutlinedButton.icon(
          onPressed: () {
            final authCtrl = context.read<AuthController>();
            final uid = int.tryParse(authCtrl.user?['id']?.toString() ?? '0') ?? 0;
            InAppChatModal.show(
              context,
              orderCode: orderCode,
              currentUserId: uid,
              currentUserRole: 'vendor',
            );
          },
          style: OutlinedButton.styleFrom(
            foregroundColor: const Color(0xFF475569),
            side: const BorderSide(color: Color(0xFFCBD5E1)),
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
          icon: const Icon(Icons.chat_bubble_outline_rounded, size: 14),
          label: const Text('Chat', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold)),
        ),
        const SizedBox(width: 8),

        // Main Workflow Action
        Expanded(
          child: _buildWorkflowButton(context, ctrl, orderId, status),
        ),
      ],
    );
  }

  Widget _buildWorkflowButton(BuildContext context, MerchantController ctrl, int orderId, String status) {
    if (status == 'pending' || status == 'confirmed') {
      return ElevatedButton.icon(
        onPressed: () async {
          final ok = await ctrl.updateOrderStatus(orderId, 'processing');
          if (ok && context.mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Pesanan diterima & mulai dimasak!'), backgroundColor: Color(0xFF2563EB)),
            );
          }
        },
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFF2563EB),
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 10),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          elevation: 0,
        ),
        icon: const Icon(Icons.restaurant_rounded, size: 15),
        label: const Text('Terima & Masak', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
      );
    } else if (status == 'processing') {
      return ElevatedButton.icon(
        onPressed: () async {
          final ok = await ctrl.updateOrderStatus(orderId, 'handover');
          if (ok && context.mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Pesanan siap! Driver telah dinotifikasi untuk mengambil makanan.'), backgroundColor: Color(0xFF16A34A)),
            );
          }
        },
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFF16A34A),
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 10),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          elevation: 0,
        ),
        icon: const Icon(Icons.check_circle_outline_rounded, size: 15),
        label: const Text('Siap Diambil Driver', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
      );
    } else if (status == 'handover') {
      return Container(
        padding: const EdgeInsets.symmetric(vertical: 8),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: const Color(0xFFF3E8FF),
          borderRadius: BorderRadius.circular(10),
        ),
        child: const Text(
          '⏳ Menunggu Penjemputan Driver',
          style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF7E22CE)),
        ),
      );
    } else if (status == 'delivered') {
      return Container(
        padding: const EdgeInsets.symmetric(vertical: 8),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: const Color(0xFFDCFCE7),
          borderRadius: BorderRadius.circular(10),
        ),
        child: const Text(
          '✅ Pesanan Selesai Terkirim',
          style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF15803D)),
        ),
      );
    } else {
      return Container(
        padding: const EdgeInsets.symmetric(vertical: 8),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(10),
        ),
        child: const Text(
          'Pesanan Ditutup',
          style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B), fontWeight: FontWeight.bold),
        ),
      );
    }
  }
}
