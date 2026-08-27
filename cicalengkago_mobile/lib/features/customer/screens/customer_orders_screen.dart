import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/customer_controller.dart';
import 'order_tracking_screen.dart';

class CustomerOrdersScreen extends StatefulWidget {
  const CustomerOrdersScreen({super.key});

  @override
  State<CustomerOrdersScreen> createState() => _CustomerOrdersScreenState();
}

class _CustomerOrdersScreenState extends State<CustomerOrdersScreen> {
  Timer? _syncTimer;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchOrders();
    });
    _syncTimer = Timer.periodic(const Duration(seconds: 4), (_) {
      if (mounted) {
        context.read<CustomerController>().fetchOrders();
      }
    });
  }

  @override
  void dispose() {
    _syncTimer?.cancel();
    super.dispose();
  }

  String _getStatusLabel(Map<String, dynamic> order) {
    final status = order['order_status'] ?? '';
    final payMethod = order['payment_method'] ?? '';
    final payStatus = order['payment_status'] ?? '';
    final isCanceled = status == 'canceled';
    final isUnpaid = payMethod == 'midtrans' && payStatus != 'paid' && !isCanceled;

    if (isCanceled) return 'Dibatalkan';
    if (isUnpaid) return 'Menunggu Bayar';
    if (status == 'pending') return 'Menunggu';
    if (status == 'confirmed') return 'Dikonfirmasi';
    if (status == 'processing') return 'Diproses Resto';
    if (['handover', 'picked_up', 'on_the_way'].contains(status)) return 'Sedang Diantar';
    if (status == 'delivered') return 'Selesai';
    return status;
  }

  Color _getStatusColor(Map<String, dynamic> order) {
    final status = order['order_status'] ?? '';
    final payMethod = order['payment_method'] ?? '';
    final payStatus = order['payment_status'] ?? '';
    final isCanceled = status == 'canceled';
    final isUnpaid = payMethod == 'midtrans' && payStatus != 'paid' && !isCanceled;

    if (isCanceled) return const Color(0xFFDC2626);
    if (isUnpaid) return const Color(0xFFD97706);
    if (status == 'confirmed') return const Color(0xFF0284C7);
    if (status == 'processing') return const Color(0xFFD97706);
    if (['handover', 'picked_up', 'on_the_way'].contains(status)) return const Color(0xFF2563EB);
    if (status == 'delivered') return const Color(0xFF059669);
    return const Color(0xFF64748B);
  }

  IconData _getStatusIcon(String status) {
    if (status == 'canceled') return Icons.cancel_outlined;
    if (status == 'pending') return Icons.hourglass_empty_rounded;
    if (status == 'confirmed') return Icons.check_circle_outline_rounded;
    if (status == 'processing') return Icons.restaurant_rounded;
    if (['handover', 'picked_up', 'on_the_way'].contains(status)) return Icons.delivery_dining_rounded;
    if (status == 'delivered') return Icons.task_alt_rounded;
    return Icons.receipt_rounded;
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<CustomerController>();
    final orders = ctrl.orders;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            pinned: true,
            backgroundColor: Colors.white,
            elevation: 0,
            title: Row(
              children: [
                const Text(
                  'Pesanan Saya',
                  style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
                const SizedBox(width: 10),
                if (orders.isNotEmpty)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: [Color(0xFF262626), Color(0xFF000000)]),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      '${orders.length}',
                      style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                    ),
                  ),
              ],
            ),
            actions: [
              IconButton(
                icon: const Icon(Icons.refresh_rounded, color: AppTheme.primaryRed),
                onPressed: () => ctrl.fetchOrders(),
              ),
            ],
            bottom: PreferredSize(
              preferredSize: const Size.fromHeight(1),
              child: Container(height: 1, color: const Color(0xFFF1F5F9)),
            ),
          ),

          if (ctrl.isLoading && orders.isEmpty)
            const SliverFillRemaining(
              child: Center(child: CircularProgressIndicator(color: AppTheme.primaryRed)),
            )
          else if (orders.isEmpty)
            SliverFillRemaining(
              child: Center(
                child: Padding(
                  padding: const EdgeInsets.all(40),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        width: 80,
                        height: 80,
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEE2E2),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.receipt_long_rounded, color: AppTheme.primaryRed, size: 38),
                      ),
                      const SizedBox(height: 20),
                      const Text(
                        'Belum Ada Riwayat Pesanan',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Pesanan kuliner & kirim paket\nAnda akan tersimpan di sini.',
                        style: TextStyle(fontSize: 13, color: Color(0xFF64748B)),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                ),
              ),
            )
          else
            SliverPadding(
              padding: const EdgeInsets.all(16),
              sliver: SliverList(
                delegate: SliverChildBuilderDelegate(
                  (context, index) {
                    final order = orders[index];
                    return _buildOrderCard(order, context);
                  },
                  childCount: orders.length,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildOrderCard(Map<String, dynamic> order, BuildContext context) {
    final orderCode = order['order_code'] ?? '';
    final status = order['order_status'] ?? '';
    final payMethod = order['payment_method'] ?? '';
    final payStatus = order['payment_status'] ?? '';
    final isCanceled = status == 'canceled';
    final isUnpaid = payMethod == 'midtrans' && payStatus != 'paid' && !isCanceled;
    final isDelivered = status == 'delivered';
    final isActive = ['handover', 'picked_up', 'on_the_way'].contains(status);
    final statusLabel = _getStatusLabel(order);
    final statusColor = _getStatusColor(order);
    final totalAmount = double.tryParse(order['total_amount']?.toString() ?? '0') ?? 0;

    // Items list
    final List items = (order['items'] as List?) ?? [];
    final storeName = order['store_name'] ?? 'Toko Cicalengka';

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          // Header
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Brand & Order Code & Status
                Row(
                  children: [
                    Container(
                      width: 28,
                      height: 28,
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(colors: [Color(0xFF262626), Color(0xFF000000)]),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Icon(_getStatusIcon(status), color: Colors.white, size: 14),
                    ),
                    const SizedBox(width: 8),
                    const Expanded(
                      child: Text(
                        'CicalengkaGO',
                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        '#$orderCode',
                        style: const TextStyle(fontSize: 10, fontFamily: 'monospace', color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: statusColor.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: statusColor.withValues(alpha: 0.3)),
                      ),
                      child: Text(
                        statusLabel,
                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: statusColor),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 10),

                // Store name & date
                Row(
                  children: [
                    const Icon(Icons.store_rounded, size: 14, color: Color(0xFF94A3B8)),
                    const SizedBox(width: 5),
                    Expanded(
                      child: Text(
                        storeName,
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    if (order['created_at'] != null) ...[
                      Text(
                        '• ${_formatDate(order['created_at'])}',
                        style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                      ),
                    ],
                  ],
                ),

                const SizedBox(height: 10),

                // Items
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFF1F5F9)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (items.isEmpty)
                        const Text('Pesanan CicalengkaGO', style: TextStyle(fontSize: 11, color: Color(0xFF64748B)))
                      else
                        ...items.take(3).map((item) => Padding(
                          padding: const EdgeInsets.only(bottom: 2),
                          child: Row(
                            children: [
                              const Icon(Icons.circle, size: 6, color: AppTheme.primaryRed),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  '${item['quantity']}x ${item['product_name'] ?? item['name'] ?? 'Menu'}',
                                  style: const TextStyle(fontSize: 11, color: Color(0xFF334155)),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        )),
                      if (items.length > 3)
                        Text(
                          '+ ${items.length - 3} menu lainnya',
                          style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8), fontStyle: FontStyle.italic),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Footer: Total & Action
          Container(
            padding: const EdgeInsets.fromLTRB(14, 10, 14, 14),
            decoration: const BoxDecoration(
              border: Border(top: BorderSide(color: Color(0xFFF1F5F9))),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      isUnpaid ? 'Total Tagihan' : 'Total Pembayaran',
                      style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                    ),
                    Text(
                      CurrencyFormatter.formatRupiah(totalAmount),
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w900,
                        color: isUnpaid ? AppTheme.primaryRed : const Color(0xFF0F172A),
                      ),
                    ),
                  ],
                ),
                if (isUnpaid)
                  _actionButton(
                    icon: Icons.credit_card_rounded,
                    label: 'Bayar',
                    color: AppTheme.inkBlack,
                    onTap: () => _openTracking(context, orderCode),
                  )
                else if (isCanceled)
                  _outlineButton(label: 'Pesan Lagi', onTap: () {})
                else if (isDelivered)
                  _outlineButton(label: 'Rincian', onTap: () => _openTracking(context, orderCode))
                else if (isActive)
                  _actionButton(
                    icon: Icons.location_on_rounded,
                    label: 'Lacak Live',
                    color: AppTheme.inkBlack,
                    onTap: () => _openTracking(context, orderCode),
                  )
                else
                  _actionButton(
                    icon: Icons.search_rounded,
                    label: 'Detail',
                    color: AppTheme.inkBlack,
                    onTap: () => _openTracking(context, orderCode),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _openTracking(BuildContext context, String orderCode) {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => OrderTrackingScreen(orderCode: orderCode)),
    );
  }

  String _formatDate(String dateStr) {
    try {
      final dt = DateTime.parse(dateStr);
      final months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
      return '${dt.day} ${months[dt.month - 1]}, ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return dateStr;
    }
  }

  Widget _actionButton({required IconData icon, required String label, required Color color, required VoidCallback onTap}) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(999),
          boxShadow: [BoxShadow(color: color.withValues(alpha: 0.15), blurRadius: 6, offset: const Offset(0, 2))],
        ),
        child: Row(
          children: [
            Icon(icon, color: Colors.white, size: 13),
            const SizedBox(width: 5),
            Text(label, style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }

  Widget _outlineButton({required String label, required VoidCallback onTap}) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: const Color(0xFFCBD5E1)),
        ),
        child: Text(label, style: const TextStyle(color: Color(0xFF334155), fontSize: 12, fontWeight: FontWeight.w600)),
      ),
    );
  }
}
