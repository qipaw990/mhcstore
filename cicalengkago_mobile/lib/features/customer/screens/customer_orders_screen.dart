import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/customer_controller.dart';
import 'order_tracking_screen.dart';

class CustomerOrdersScreen extends StatefulWidget {
  const CustomerOrdersScreen({super.key});

  @override
  State<CustomerOrdersScreen> createState() => _CustomerOrdersScreenState();
}

class _CustomerOrdersScreenState extends State<CustomerOrdersScreen> with SingleTickerProviderStateMixin {
  Timer? _syncTimer;
  int _selectedFilterIndex = 0;

  final List<String> _filters = const ['Semua', 'Berjalan', 'Selesai', 'Dibatalkan'];

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
    final isUnpaid = payMethod == 'midtrans' && payStatus != 'paid' && !isCanceled;

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
      // Berjalan (Active)
      return allOrders.where((o) {
        final st = o['order_status'] ?? '';
        return ['pending', 'confirmed', 'processing', 'handover', 'picked_up', 'on_the_way'].contains(st);
      }).toList();
    } else if (_selectedFilterIndex == 2) {
      // Selesai (Delivered)
      return allOrders.where((o) => (o['order_status'] ?? '') == 'delivered').toList();
    } else if (_selectedFilterIndex == 3) {
      // Dibatalkan (Canceled)
      return allOrders.where((o) => (o['order_status'] ?? '') == 'canceled').toList();
    }
    return allOrders;
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<CustomerController>();
    final allOrders = ctrl.orders;
    final filteredOrders = _filterOrders(allOrders);

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 0,
        title: const Text(
          'Pesanan Saya',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppTheme.inkBlack),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: AppTheme.inkBlack),
            onPressed: () => ctrl.fetchOrders(),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(50),
          child: Column(
            children: [
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: Row(
                  children: List.generate(_filters.length, (idx) {
                    final isSelected = _selectedFilterIndex == idx;
                    return Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: ChoiceChip(
                        label: Text(_filters[idx]),
                        selected: isSelected,
                        onSelected: (val) {
                          if (val) {
                            setState(() {
                              _selectedFilterIndex = idx;
                            });
                          }
                        },
                        selectedColor: AppTheme.inkBlack,
                        backgroundColor: const Color(0xFFF1F5F9),
                        labelStyle: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: isSelected ? Colors.white : const Color(0xFF64748B),
                        ),
                        side: BorderSide.none,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                      ),
                    );
                  }),
                ),
              ),
              Container(height: 1, color: const Color(0xFFE2E8F0)),
            ],
          ),
        ),
      ),
      body: ctrl.isLoading && allOrders.isEmpty
          ? const Center(child: CircularProgressIndicator(color: AppTheme.inkBlack))
          : RefreshIndicator(
              color: AppTheme.inkBlack,
              onRefresh: () async => ctrl.fetchOrders(),
              child: filteredOrders.isEmpty
                  ? SingleChildScrollView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      child: Container(
                        height: MediaQuery.of(context).size.height * 0.6,
                        alignment: Alignment.center,
                        padding: const EdgeInsets.all(32),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              width: 76,
                              height: 76,
                              decoration: const BoxDecoration(
                                color: Color(0xFFF1F5F9),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.receipt_long_rounded, color: AppTheme.inkBlack, size: 36),
                            ),
                            const SizedBox(height: 16),
                            Text(
                              _selectedFilterIndex == 0
                                  ? 'Belum Ada Pesanan'
                                  : 'Tidak ada pesanan (${_filters[_selectedFilterIndex]})',
                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                            ),
                            const SizedBox(height: 6),
                            const Text(
                              'Pesanan makanan dan pengiriman barang Anda akan tampil rapi di sini.',
                              style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                              textAlign: TextAlign.center,
                            ),
                          ],
                        ),
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: filteredOrders.length,
                      itemBuilder: (context, index) {
                        final order = filteredOrders[index] is Map<String, dynamic>
                            ? filteredOrders[index] as Map<String, dynamic>
                            : Map<String, dynamic>.from(filteredOrders[index] as Map);
                        return _buildOrderCard(order, context);
                      },
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
    final isUnpaid = payMethod == 'midtrans' && payStatus != 'paid' && !isCanceled;
    final isActive = ['pending', 'confirmed', 'processing', 'handover', 'picked_up', 'on_the_way'].contains(status);
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

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: () => _openTracking(context, orderCode),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Card Top Header
            Container(
              padding: const EdgeInsets.fromLTRB(14, 12, 14, 10),
              decoration: const BoxDecoration(
                border: Border(bottom: BorderSide(color: Color(0xFFF1F5F9))),
              ),
              child: Row(
                children: [
                  Container(
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(_getStatusIcon(status), color: AppTheme.inkBlack, size: 16),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          storeName,
                          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        if (order['created_at'] != null)
                          Text(
                            _formatDate(order['created_at'].toString()),
                            style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                          ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: statusColor.withValues(alpha: 0.3)),
                    ),
                    child: Text(
                      statusLabel,
                      style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: statusColor),
                    ),
                  ),
                ],
              ),
            ),

            // Card Body (Items Summary)
            Padding(
              padding: const EdgeInsets.all(14.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (items.isEmpty)
                    Text(
                      isParcel ? '1x Pengiriman Paket Parcel' : '1x Pesanan di $storeName',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500, color: Color(0xFF475569)),
                    )
                  else ...[
                    ...items.take(2).map((it) {
                      final name = (it['product_name'] ??
                              it['name'] ??
                              it['title'] ??
                              it['item_name'] ??
                              (it['product'] is Map ? it['product']['name'] : null) ??
                              'Menu Kuliner')
                          .toString();
                      final qty = it['quantity'] ?? 1;
                      final rawImg = it['product_image'] ?? it['image'] ?? (it['product'] is Map ? it['product']['image'] : null);
                      final imgUrl = rawImg != null && rawImg.toString().isNotEmpty
                          ? ApiConstants.formatImageUrl(rawImg.toString())
                          : null;

                      return Padding(
                        padding: const EdgeInsets.only(bottom: 6.0),
                        child: Row(
                          children: [
                            ClipRRect(
                              borderRadius: BorderRadius.circular(6),
                              child: imgUrl != null
                                  ? CachedNetworkImage(
                                      imageUrl: imgUrl,
                                      width: 28,
                                      height: 28,
                                      fit: BoxFit.cover,
                                      errorWidget: (context, url, error) => Container(
                                        width: 28,
                                        height: 28,
                                        color: const Color(0xFFF1F5F9),
                                        child: const Icon(Icons.fastfood_rounded, color: Color(0xFF94A3B8), size: 14),
                                      ),
                                    )
                                  : Container(
                                      width: 28,
                                      height: 28,
                                      decoration: BoxDecoration(
                                        color: const Color(0xFFFEF2F2),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: const Icon(Icons.restaurant_rounded, color: AppTheme.primaryRed, size: 14),
                                    ),
                            ),
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(5),
                              ),
                              child: Text(
                                '${qty}x',
                                style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                name,
                                style: const TextStyle(fontSize: 12, color: Color(0xFF334155), fontWeight: FontWeight.w600),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      );
                    }),
                    if (items.length > 2)
                      Padding(
                        padding: const EdgeInsets.only(top: 2.0),
                        child: Text(
                          '+ ${items.length - 2} menu lainnya',
                          style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontStyle: FontStyle.italic),
                        ),
                      ),
                  ],
                ],
              ),
            ),

            // Card Footer (Total & Button)
            Container(
              padding: const EdgeInsets.fromLTRB(14, 10, 14, 12),
              decoration: const BoxDecoration(
                color: Color(0xFFFAFAFA),
                borderRadius: BorderRadius.vertical(bottom: Radius.circular(18)),
                border: Border(top: BorderSide(color: Color(0xFFF1F5F9))),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Total Pembayaran',
                        style: TextStyle(fontSize: 10, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        CurrencyFormatter.formatRupiah(totalAmount),
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: AppTheme.inkBlack),
                      ),
                    ],
                  ),
                  if (isActive)
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.inkBlack,
                        foregroundColor: Colors.white,
                        elevation: 0,
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                      ),
                      onPressed: () => _openTracking(context, orderCode),
                      icon: Container(
                        width: 8,
                        height: 8,
                        decoration: const BoxDecoration(color: Color(0xFF22C55E), shape: BoxShape.circle),
                      ),
                      label: const Text('Lacak Live', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                    )
                  else if (isUnpaid)
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFF59E0B),
                        foregroundColor: Colors.white,
                        elevation: 0,
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                      ),
                      onPressed: () => _openTracking(context, orderCode),
                      child: const Text('Bayar Sekarang', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                    )
                  else
                    OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppTheme.inkBlack,
                        side: const BorderSide(color: Color(0xFFCBD5E1)),
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                      ),
                      onPressed: () => _openTracking(context, orderCode),
                      child: const Text('Detail Pesanan', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _openTracking(BuildContext context, String orderCode) {
    if (orderCode.isEmpty) return;
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
}
