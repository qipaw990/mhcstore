import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/widgets/require_auth_widget.dart';
import '../../../core/widgets/app_shimmer.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/customer_controller.dart';
import '../widgets/order_item_detail_modal.dart';
import 'order_tracking_screen.dart';

class CustomerOrdersScreen extends StatefulWidget {
  const CustomerOrdersScreen({super.key});

  @override
  State<CustomerOrdersScreen> createState() => _CustomerOrdersScreenState();
}

class _CustomerOrdersScreenState extends State<CustomerOrdersScreen>
    with SingleTickerProviderStateMixin {
  Timer? _syncTimer;
  int _selectedFilterIndex = 0;

  final List<String> _filterLabels = const ['Semua', 'Berjalan', 'Selesai', 'Dibatalkan'];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchOrders();
    });
    _syncTimer = Timer.periodic(const Duration(seconds: 4), (_) {
      if (mounted) context.read<CustomerController>().fetchOrders();
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
    final isUnpaid = payMethod == 'doku' && payStatus != 'paid' && !isCanceled;
    if (isCanceled) return 'Dibatalkan';
    if (isUnpaid) return 'Menunggu Bayar';
    if (status == 'pending') return 'Mencari Kurir';
    if (status == 'confirmed') return 'Dikonfirmasi Resto';
    if (status == 'processing') return 'Sedang Dimasak';
    if (['handover', 'picked_up', 'on_the_way'].contains(status)) return 'Sedang Diantar';
    if (status == 'delivered') return 'Pesanan Selesai';
    return status;
  }

  Color _getStatusColor(Map<String, dynamic> order) {
    final status = order['order_status'] ?? '';
    final payMethod = order['payment_method'] ?? '';
    final payStatus = order['payment_status'] ?? '';
    final isCanceled = status == 'canceled';
    final isUnpaid = payMethod == 'doku' && payStatus != 'paid' && !isCanceled;
    if (isCanceled) return const Color(0xFFEF4444);
    if (isUnpaid) return const Color(0xFFF59E0B);
    if (status == 'confirmed') return const Color(0xFF0284C7);
    if (status == 'processing') return const Color(0xFFD97706);
    if (['handover', 'picked_up', 'on_the_way'].contains(status)) return const Color(0xFF2563EB);
    if (status == 'delivered') return const Color(0xFF10B981);
    return const Color(0xFF64748B);
  }

  IconData _getStatusIcon(String status) {
    if (status == 'canceled') return Icons.cancel_rounded;
    if (status == 'pending') return Icons.radar_rounded;
    if (status == 'confirmed') return Icons.check_circle_rounded;
    if (status == 'processing') return Icons.soup_kitchen_rounded;
    if (['handover', 'picked_up', 'on_the_way'].contains(status)) return Icons.two_wheeler_rounded;
    if (status == 'delivered') return Icons.task_alt_rounded;
    return Icons.receipt_rounded;
  }

  List<dynamic> _filterOrders(List<dynamic> allOrders) {
    if (_selectedFilterIndex == 1) {
      return allOrders.where((o) {
        final st = o['order_status'] ?? '';
        return ['pending', 'confirmed', 'processing', 'handover', 'picked_up', 'on_the_way'].contains(st);
      }).toList();
    } else if (_selectedFilterIndex == 2) {
      return allOrders.where((o) => (o['order_status'] ?? '') == 'delivered').toList();
    } else if (_selectedFilterIndex == 3) {
      return allOrders.where((o) => (o['order_status'] ?? '') == 'canceled').toList();
    }
    return allOrders;
  }

  int _countForTab(List<dynamic> all, int idx) {
    if (idx == 0) return all.length;
    if (idx == 1) return all.where((o) => ['pending', 'confirmed', 'processing', 'handover', 'picked_up', 'on_the_way'].contains(o['order_status'] ?? '')).length;
    if (idx == 2) return all.where((o) => o['order_status'] == 'delivered').length;
    if (idx == 3) return all.where((o) => o['order_status'] == 'canceled').length;
    return 0;
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<CustomerController>();
    final allOrders = ctrl.orders;
    final filteredOrders = _filterOrders(allOrders);

    return RequireAuthWidget(
      title: 'Pesanan Saya',
      subtitle: 'Silakan masuk ke akun CicalengkaGO Anda untuk melihat daftar pesanan aktif dan riwayat belanja.',
      icon: Icons.receipt_long_rounded,
      child: Scaffold(
        backgroundColor: AppTheme.canvasSofter,
        body: CustomScrollView(
          slivers: [
            SliverAppBar(
              pinned: true,
              expandedHeight: 116,
              backgroundColor: AppTheme.brandOrange,
              foregroundColor: Colors.white,
              elevation: 0,
              flexibleSpace: FlexibleSpaceBar(
                background: Container(
                  decoration: const BoxDecoration(gradient: AppTheme.heroGradient),
                  child: SafeArea(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          const Row(
                            children: [
                              Icon(Icons.receipt_long_rounded, color: Colors.white, size: 20),
                              SizedBox(width: 8),
                              Text('Pesanan Saya',
                                  style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: -0.3)),
                            ],
                          ),
                          const SizedBox(height: 3),
                          Text(
                            allOrders.isNotEmpty ? '\${allOrders.length} total transaksi' : 'Riwayat belanja & status pengiriman',
                            style: TextStyle(fontSize: 12, color: Colors.white.withValues(alpha: 0.85), fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
              actions: [
                if (ctrl.isLoading)
                  const Padding(
                    padding: EdgeInsets.all(16),
                    child: SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)),
                  )
                else
                  IconButton(
                    icon: const Icon(Icons.refresh_rounded, color: Colors.white),
                    onPressed: () => ctrl.fetchOrders(),
                  ),
              ],
              bottom: PreferredSize(
                preferredSize: const Size.fromHeight(54),
                child: Container(
                  color: Colors.white,
                  child: Column(
                    children: [
                      SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        padding: const EdgeInsets.fromLTRB(12, 8, 12, 4),
                        child: Row(
                          children: List.generate(_filterLabels.length, (idx) {
                            final isSelected = _selectedFilterIndex == idx;
                            final count = _countForTab(allOrders, idx);
                            return Padding(
                              padding: const EdgeInsets.only(right: 8),
                              child: GestureDetector(
                                onTap: () => setState(() => _selectedFilterIndex = idx),
                                child: AnimatedContainer(
                                  duration: const Duration(milliseconds: 200),
                                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                                  decoration: BoxDecoration(
                                    gradient: isSelected ? AppTheme.primaryGradient : null,
                                    color: isSelected ? null : const Color(0xFFF3F4F6),
                                    borderRadius: BorderRadius.circular(20),
                                    boxShadow: isSelected ? AppTheme.floatShadow : null,
                                  ),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Text(
                                        _filterLabels[idx],
                                        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700,
                                            color: isSelected ? Colors.white : AppTheme.textBody),
                                      ),
                                      if (count > 0) ...[
                                        const SizedBox(width: 5),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                                          decoration: BoxDecoration(
                                            color: isSelected
                                                ? Colors.white.withValues(alpha: 0.28)
                                                : AppTheme.brandOrange.withValues(alpha: 0.15),
                                            borderRadius: BorderRadius.circular(10),
                                          ),
                                          child: Text('\$count',
                                              style: TextStyle(
                                                  fontSize: 9.5,
                                                  fontWeight: FontWeight.w900,
                                                  color: isSelected ? Colors.white : AppTheme.brandOrange)),
                                        ),
                                      ],
                                    ],
                                  ),
                                ),
                              ),
                            );
                          }),
                        ),
                      ),
                      Container(height: 1, color: const Color(0xFFF0F0F0)),
                    ],
                  ),
                ),
              ),
            ),

            if (ctrl.isLoading && allOrders.isEmpty)
              const SliverToBoxAdapter(
                child: Padding(padding: EdgeInsets.all(16), child: ShimmerList(count: 4, cardHeight: 150)),
              )
            else if (filteredOrders.isEmpty)
              SliverFillRemaining(
                hasScrollBody: false,
                child: Center(
                  child: Padding(
                    padding: const EdgeInsets.all(40),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          width: 100, height: 100,
                          decoration: BoxDecoration(gradient: AppTheme.warmGradient, shape: BoxShape.circle, boxShadow: AppTheme.floatShadow),
                          child: const Icon(Icons.receipt_long_rounded, color: Colors.white, size: 46),
                        ),
                        const SizedBox(height: 22),
                        Text(
                          _selectedFilterIndex == 0 ? 'Belum Ada Pesanan' : 'Tidak Ada Pesanan \${_filterLabels[_selectedFilterIndex]}',
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppTheme.textInk),
                        ),
                        const SizedBox(height: 8),
                        const Text('Pesanan makanan dan pengiriman barang\nAnda akan tampil rapi di sini.',
                            style: TextStyle(fontSize: 13, color: AppTheme.textMute, height: 1.6), textAlign: TextAlign.center),
                      ],
                    ),
                  ),
                ),
              )
            else
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (context, index) {
                      final order = filteredOrders[index] is Map<String, dynamic>
                          ? filteredOrders[index] as Map<String, dynamic>
                          : Map<String, dynamic>.from(filteredOrders[index] as Map);
                      return _buildOrderCard(order, context);
                    },
                    childCount: filteredOrders.length,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildOrderCard(Map<String, dynamic> order, BuildContext context) {
    final orderCode = order['order_code']?.toString() ?? '';
    final status = order['order_status']?.toString() ?? '';
    final payMethod = order['payment_method']?.toString() ?? '';
    final payStatus = order['payment_status']?.toString() ?? '';
    final isCanceled = status == 'canceled';
    final isUnpaid = payMethod == 'doku' && payStatus != 'paid' && !isCanceled;
    final isActive = ['pending', 'confirmed', 'processing', 'handover', 'picked_up', 'on_the_way'].contains(status);
    final isDelivered = status == 'delivered';
    final statusLabel = _getStatusLabel(order);
    final statusColor = _getStatusColor(order);
    final totalAmount = double.tryParse(order['total_amount']?.toString() ?? '0') ?? 0;

    final List items = (order['items'] is List && (order['items'] as List).isNotEmpty)
        ? (order['items'] as List)
        : (order['all_items'] is List && (order['all_items'] as List).isNotEmpty)
            ? (order['all_items'] as List)
            : [];
    final storeName = order['store_name']?.toString() ?? 'Mitra Resto CicalengkaGO';
    final bool isParcel = order['order_type']?.toString() == 'parcel';
    final rawStoreLogo = order['store_logo'] ?? order['logo'] ?? (order['store'] is Map ? order['store']['logo'] : null);
    final storeLogoUrl = (rawStoreLogo != null && rawStoreLogo.toString().isNotEmpty)
        ? ApiConstants.formatImageUrl(rawStoreLogo.toString())
        : null;

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(
          color: isActive
              ? AppTheme.brandOrange.withValues(alpha: 0.25)
              : isDelivered
                  ? const Color(0xFF10B981).withValues(alpha: 0.18)
                  : const Color(0xFFEEEEEE),
          width: isActive ? 1.5 : 1.0,
        ),
        boxShadow: isActive
            ? [BoxShadow(color: AppTheme.brandOrange.withValues(alpha: 0.12), blurRadius: 18, offset: const Offset(0, 6))]
            : AppTheme.cardShadow,
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(22),
        onTap: () => _openTracking(context, orderCode),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (isActive)
              Container(
                height: 4,
                decoration: const BoxDecoration(
                  gradient: AppTheme.primaryGradient,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
                ),
              ),

            Padding(
              padding: EdgeInsets.fromLTRB(14, isActive ? 12 : 14, 14, 10),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Container(
                    width: 46, height: 46,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(13),
                      border: Border.all(color: const Color(0xFFEEEEEE)),
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(13),
                      child: (!isParcel && storeLogoUrl != null)
                          ? CachedNetworkImage(imageUrl: storeLogoUrl, fit: BoxFit.cover, errorWidget: (_, __, ___) => _storeFallback(status))
                          : _storeFallback(status),
                    ),
                  ),
                  const SizedBox(width: 11),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (isParcel)
                          Container(
                            margin: const EdgeInsets.only(bottom: 3),
                            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                            decoration: BoxDecoration(color: const Color(0xFFEDE9FE), borderRadius: BorderRadius.circular(5)),
                            child: const Text('PARCEL', style: TextStyle(fontSize: 8.5, fontWeight: FontWeight.w900, color: Color(0xFF7C3AED))),
                          ),
                        Text(storeName,
                            style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800, color: AppTheme.inkBlack),
                            maxLines: 1, overflow: TextOverflow.ellipsis),
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            const Icon(Icons.access_time_rounded, size: 11, color: AppTheme.textMute),
                            const SizedBox(width: 3),
                            Text(
                              order['created_at'] != null ? _formatDate(order['created_at'].toString()) : '–',
                              style: const TextStyle(fontSize: 11, color: AppTheme.textMute),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: statusColor.withValues(alpha: 0.25), width: 1.5),
                    ),
                    child: Text(statusLabel, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: statusColor)),
                  ),
                ],
              ),
            ),

            Container(height: 1, color: const Color(0xFFF4F4F4)),

            Padding(
              padding: const EdgeInsets.fromLTRB(14, 12, 14, 10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (items.isEmpty)
                    Row(
                      children: [
                        Container(
                          width: 36, height: 36,
                          decoration: BoxDecoration(color: const Color(0xFFFEF2F2), borderRadius: BorderRadius.circular(10)),
                          child: const Icon(Icons.restaurant_rounded, color: AppTheme.primaryRed, size: 18),
                        ),
                        const SizedBox(width: 10),
                        Text(isParcel ? '1x Pengiriman Paket Parcel' : '1x Pesanan di $storeName',
                            style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Color(0xFF475569))),
                      ],
                    )
                  else ...[
                    ...items.take(2).map((it) {
                      final name = (it['product_name'] ?? it['name'] ?? it['title'] ?? it['item_name'] ??
                              (it['product'] is Map ? it['product']['name'] : null) ?? 'Menu Kuliner').toString();
                      final qty = it['quantity'] ?? 1;
                      final rawImg = it['product_image'] ?? it['image'] ?? (it['product'] is Map ? it['product']['image'] : null);
                      final imgUrl = rawImg != null && rawImg.toString().isNotEmpty
                          ? ApiConstants.formatImageUrl(rawImg.toString()) : null;

                      return Padding(
                        padding: const EdgeInsets.only(bottom: 7),
                        child: InkWell(
                          onTap: () => OrderItemDetailModal.show(context, it is Map ? it : {},
                              storeName: storeName, storeLogo: rawStoreLogo?.toString()),
                          borderRadius: BorderRadius.circular(10),
                          child: Row(
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(9),
                                child: imgUrl != null
                                    ? CachedNetworkImage(imageUrl: imgUrl, width: 36, height: 36, fit: BoxFit.cover,
                                        errorWidget: (_, __, ___) => _foodFallback())
                                    : _foodFallback(),
                              ),
                              const SizedBox(width: 10),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                decoration: BoxDecoration(
                                  color: AppTheme.brandOrange.withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text('${qty}x',
                                    style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w900, color: AppTheme.brandOrange)),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(name,
                                    style: const TextStyle(fontSize: 12.5, color: Color(0xFF1E293B), fontWeight: FontWeight.w600),
                                    maxLines: 1, overflow: TextOverflow.ellipsis),
                              ),
                              const Icon(Icons.chevron_right_rounded, size: 16, color: Color(0xFFCBD5E1)),
                            ],
                          ),
                        ),
                      );
                    }),
                    if (items.length > 2)
                      Padding(
                        padding: const EdgeInsets.only(top: 2, left: 46),
                        child: Text('+ ${items.length - 2} menu lainnya',
                            style: const TextStyle(fontSize: 11, color: AppTheme.textMute, fontStyle: FontStyle.italic)),
                      ),
                  ],
                ],
              ),
            ),

            Container(
              padding: const EdgeInsets.fromLTRB(14, 10, 14, 14),
              decoration: BoxDecoration(
                color: isActive ? AppTheme.brandOrange.withValues(alpha: 0.04) : const Color(0xFFFAFAFA),
                borderRadius: const BorderRadius.vertical(bottom: Radius.circular(22)),
                border: const Border(top: BorderSide(color: Color(0xFFF0F0F0))),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Total Pembayaran',
                          style: TextStyle(fontSize: 10.5, color: AppTheme.textMute, fontWeight: FontWeight.w600)),
                      const SizedBox(height: 2),
                      Text(
                        CurrencyFormatter.formatRupiah(totalAmount),
                        style: TextStyle(fontSize: 15, fontWeight: FontWeight.w900,
                            color: isActive ? AppTheme.brandOrange : AppTheme.inkBlack),
                      ),
                    ],
                  ),
                  if (isActive)
                    _gradientBtn(label: '🛵  Lacak Live', onTap: () => _openTracking(context, orderCode), gradient: AppTheme.primaryGradient)
                  else if (isUnpaid)
                    _gradientBtn(
                      label: '💳  Bayar Sekarang',
                      onTap: () => _openTracking(context, orderCode),
                      gradient: const LinearGradient(colors: [Color(0xFFF59E0B), Color(0xFFD97706)]),
                    )
                  else if (isDelivered)
                    OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(
                        foregroundColor: const Color(0xFF10B981),
                        side: const BorderSide(color: Color(0xFF10B981), width: 1.5),
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                      ),
                      onPressed: () => _openTracking(context, orderCode),
                      icon: const Icon(Icons.receipt_long_rounded, size: 14),
                      label: const Text('Detail', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w800)),
                    )
                  else
                    OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppTheme.textBody,
                        side: const BorderSide(color: Color(0xFFDDE1E7)),
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                      ),
                      onPressed: () => _openTracking(context, orderCode),
                      child: const Text('Detail Pesanan', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700)),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _gradientBtn({required String label, required VoidCallback onTap, required Gradient gradient}) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
        decoration: BoxDecoration(gradient: gradient, borderRadius: BorderRadius.circular(20), boxShadow: AppTheme.floatShadow),
        child: Text(label, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w800, color: Colors.white)),
      ),
    );
  }

  Widget _storeFallback(String status) => Container(
        color: const Color(0xFFFFF5F0),
        child: Center(child: Icon(_getStatusIcon(status), color: AppTheme.brandOrange, size: 22)),
      );

  Widget _foodFallback() => Container(
        width: 36, height: 36,
        decoration: BoxDecoration(color: const Color(0xFFFEF2F2), borderRadius: BorderRadius.circular(9)),
        child: const Icon(Icons.restaurant_rounded, color: AppTheme.primaryRed, size: 18),
      );

  void _openTracking(BuildContext context, String orderCode) {
    if (orderCode.isEmpty) return;
    Navigator.push(context, MaterialPageRoute(builder: (_) => OrderTrackingScreen(orderCode: orderCode)));
  }

  String _formatDate(String dateStr) {
    try {
      final dt = DateTime.parse(dateStr).toLocal();
      final months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
      return '${dt.day} ${months[dt.month - 1]}, ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return dateStr;
    }
  }
}
