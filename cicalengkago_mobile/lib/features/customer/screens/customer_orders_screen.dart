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
import 'store_detail_screen.dart';

class CustomerOrdersScreen extends StatefulWidget {
  const CustomerOrdersScreen({super.key});

  @override
  State<CustomerOrdersScreen> createState() => _CustomerOrdersScreenState();
}

class _CustomerOrdersScreenState extends State<CustomerOrdersScreen> {
  Timer? _syncTimer;
  int _selectedFilterIndex = 0;

  final List<String> _filterLabels = const [
    'Semua',
    'Berjalan',
    'Selesai',
    'Dibatalkan',
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchOrders();
    });
    _syncTimer = Timer.periodic(const Duration(seconds: 5), (_) {
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

  bool _isOrderActive(String status) {
    return [
      'pending',
      'confirmed',
      'processing',
      'handover',
      'picked_up',
      'on_the_way',
    ].contains(status);
  }

  String _getStatusLabel(Map<String, dynamic> order) {
    final status = order['order_status']?.toString() ?? '';
    final payMethod = order['payment_method']?.toString() ?? '';
    final payStatus = order['payment_status']?.toString() ?? '';
    final isCanceled = status == 'canceled';
    final isUnpaid = payMethod == 'doku' && payStatus != 'paid' && !isCanceled;

    if (isCanceled) return 'Dibatalkan';
    if (isUnpaid) return 'Menunggu Bayar';
    if (status == 'pending') return 'Mencari Kurir';
    if (status == 'confirmed') return 'Dikonfirmasi';
    if (status == 'processing') return 'Sedang Dimasak';
    if (['handover', 'picked_up', 'on_the_way'].contains(status)) return 'Sedang Diantar';
    if (status == 'delivered') return 'Selesai';
    return status.toUpperCase();
  }

  Color _getStatusTextColor(Map<String, dynamic> order) {
    final status = order['order_status']?.toString() ?? '';
    final payMethod = order['payment_method']?.toString() ?? '';
    final payStatus = order['payment_status']?.toString() ?? '';
    final isCanceled = status == 'canceled';
    final isUnpaid = payMethod == 'doku' && payStatus != 'paid' && !isCanceled;

    if (isCanceled) return const Color(0xFFDC2626);
    if (isUnpaid) return const Color(0xFFD97706);
    if (status == 'pending') return AppTheme.brandOrange;
    if (status == 'confirmed') return const Color(0xFF0284C7);
    if (status == 'processing') return const Color(0xFFD97706);
    if (['handover', 'picked_up', 'on_the_way'].contains(status)) return const Color(0xFF2563EB);
    if (status == 'delivered') return const Color(0xFF16A34A);
    return const Color(0xFF64748B);
  }

  Color _getStatusBgColor(Map<String, dynamic> order) {
    final status = order['order_status']?.toString() ?? '';
    final payMethod = order['payment_method']?.toString() ?? '';
    final payStatus = order['payment_status']?.toString() ?? '';
    final isCanceled = status == 'canceled';
    final isUnpaid = payMethod == 'doku' && payStatus != 'paid' && !isCanceled;

    if (isCanceled) return const Color(0xFFFEE2E2);
    if (isUnpaid) return const Color(0xFFFEF3C7);
    if (status == 'pending') return const Color(0xFFFFEDD5);
    if (status == 'confirmed') return const Color(0xFFE0F2FE);
    if (status == 'processing') return const Color(0xFFFEF3C7);
    if (['handover', 'picked_up', 'on_the_way'].contains(status)) return const Color(0xFFDBEAFE);
    if (status == 'delivered') return const Color(0xFFDCFCE7);
    return const Color(0xFFF1F5F9);
  }

  IconData _getStatusIcon(String status) {
    if (status == 'canceled') return Icons.cancel_outlined;
    if (status == 'pending') return Icons.radar_rounded;
    if (status == 'confirmed') return Icons.thumb_up_alt_rounded;
    if (status == 'processing') return Icons.soup_kitchen_rounded;
    if (['handover', 'picked_up', 'on_the_way'].contains(status)) return Icons.two_wheeler_rounded;
    if (status == 'delivered') return Icons.check_circle_rounded;
    return Icons.receipt_rounded;
  }

  String _getActivePhaseMessage(String status) {
    switch (status) {
      case 'pending':
        return 'Mencari kurir CicalengkaGO terdekat...';
      case 'confirmed':
        return 'Resto telah menerima & menyetujui pesanan';
      case 'processing':
        return 'Menu favoritmu sedang dimasak oleh resto';
      case 'handover':
      case 'picked_up':
      case 'on_the_way':
        return 'Driver dalam perjalanan mengantarkan pesanan';
      default:
        return 'Pesanan sedang aktif dalam proses';
    }
  }

  List<dynamic> _filterOrders(List<dynamic> allOrders) {
    if (_selectedFilterIndex == 1) {
      return allOrders.where((o) => _isOrderActive(o['order_status']?.toString() ?? '')).toList();
    } else if (_selectedFilterIndex == 2) {
      return allOrders.where((o) => (o['order_status']?.toString() ?? '') == 'delivered').toList();
    } else if (_selectedFilterIndex == 3) {
      return allOrders.where((o) => (o['order_status']?.toString() ?? '') == 'canceled').toList();
    }
    return allOrders;
  }

  int _countForTab(List<dynamic> all, int idx) {
    if (idx == 0) return all.length;
    if (idx == 1) return all.where((o) => _isOrderActive(o['order_status']?.toString() ?? '')).length;
    if (idx == 2) return all.where((o) => (o['order_status']?.toString() ?? '') == 'delivered').length;
    if (idx == 3) return all.where((o) => (o['order_status']?.toString() ?? '') == 'canceled').length;
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
        backgroundColor: const Color(0xFFF8FAFC),
        appBar: AppBar(
          backgroundColor: Colors.white,
          elevation: 0,
          scrolledUnderElevation: 1,
          centerTitle: false,
          title: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Pesanan Saya',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                  color: AppTheme.inkBlack,
                  letterSpacing: -0.3,
                ),
              ),
              const SizedBox(height: 1),
              Text(
                allOrders.isNotEmpty
                    ? '${allOrders.length} total pesanan tercatat'
                    : 'Pantau pesanan & pengiriman',
                style: const TextStyle(
                  fontSize: 11.5,
                  fontWeight: FontWeight.w500,
                  color: Color(0xFF64748B),
                ),
              ),
            ],
          ),
          actions: [
            Padding(
              padding: const EdgeInsets.only(right: 12),
              child: ctrl.isLoading
                  ? const Center(
                      child: SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          valueColor: AlwaysStoppedAnimation<Color>(AppTheme.brandOrange),
                        ),
                      ),
                    )
                  : IconButton(
                      tooltip: 'Perbarui Data',
                      icon: Container(
                        padding: const EdgeInsets.all(7),
                        decoration: const BoxDecoration(
                          color: Color(0xFFF1F5F9),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.refresh_rounded,
                          color: Color(0xFF475569),
                          size: 18,
                        ),
                      ),
                      onPressed: () => ctrl.fetchOrders(),
                    ),
            ),
          ],
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(52),
            child: Container(
              color: Colors.white,
              padding: const EdgeInsets.fromLTRB(16, 6, 16, 10),
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: List.generate(_filterLabels.length, (idx) {
                    final isSelected = _selectedFilterIndex == idx;
                    final count = _countForTab(allOrders, idx);

                    return Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: GestureDetector(
                        onTap: () => setState(() => _selectedFilterIndex = idx),
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 180),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                          decoration: BoxDecoration(
                            gradient: isSelected ? AppTheme.primaryGradient : null,
                            color: isSelected ? null : const Color(0xFFF1F5F9),
                            borderRadius: BorderRadius.circular(20),
                            boxShadow: isSelected
                                ? [
                                    BoxShadow(
                                      color: AppTheme.brandOrange.withValues(alpha: 0.28),
                                      blurRadius: 8,
                                      offset: const Offset(0, 3),
                                    ),
                                  ]
                                : null,
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                _filterLabels[idx],
                                style: TextStyle(
                                  fontSize: 12.5,
                                  fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
                                  color: isSelected ? Colors.white : const Color(0xFF475569),
                                ),
                              ),
                              if (count > 0) ...[
                                const SizedBox(width: 6),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                                  decoration: BoxDecoration(
                                    color: isSelected
                                        ? Colors.white.withValues(alpha: 0.25)
                                        : const Color(0xFFE2E8F0),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Text(
                                    '$count',
                                    style: TextStyle(
                                      fontSize: 10,
                                      fontWeight: FontWeight.w800,
                                      color: isSelected ? Colors.white : const Color(0xFF334155),
                                    ),
                                  ),
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
            ),
          ),
        ),
        body: RefreshIndicator(
          color: AppTheme.brandOrange,
          backgroundColor: Colors.white,
          onRefresh: () async => await ctrl.fetchOrders(),
          child: ctrl.isLoading && allOrders.isEmpty
              ? const Padding(
                  padding: EdgeInsets.all(16),
                  child: ShimmerList(count: 4, cardHeight: 140),
                )
              : filteredOrders.isEmpty
                  ? _buildEmptyState(context)
                  : ListView.builder(
                      physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                      padding: const EdgeInsets.fromLTRB(16, 14, 16, 32),
                      itemCount: filteredOrders.length,
                      itemBuilder: (context, index) {
                        final order = filteredOrders[index] is Map<String, dynamic>
                            ? filteredOrders[index] as Map<String, dynamic>
                            : Map<String, dynamic>.from(filteredOrders[index] as Map);
                        return _buildOrderCard(order, context);
                      },
                    ),
        ),
      ),
    );
  }

  Widget _buildEmptyState(BuildContext context) {
    return Center(
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 48),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 88,
              height: 88,
              decoration: BoxDecoration(
                color: const Color(0xFFFFF7ED),
                shape: BoxShape.circle,
                border: Border.all(color: const Color(0xFFFFEDD5), width: 2),
              ),
              child: const Icon(
                Icons.receipt_long_rounded,
                color: AppTheme.brandOrange,
                size: 40,
              ),
            ),
            const SizedBox(height: 20),
            Text(
              _selectedFilterIndex == 0
                  ? 'Belum Ada Pesanan'
                  : 'Tidak Ada Pesanan ${_filterLabels[_selectedFilterIndex]}',
              style: const TextStyle(
                fontSize: 17,
                fontWeight: FontWeight.w800,
                color: Color(0xFF0F172A),
              ),
            ),
            const SizedBox(height: 8),
            Text(
              _selectedFilterIndex == 0
                  ? 'Pesan makanan atau kirim paket di CicalengkaGO dan pantau statusnya secara realtime di sini.'
                  : 'Tidak ada riwayat pesanan dengan status ${_filterLabels[_selectedFilterIndex].toLowerCase()}.',
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 13,
                color: Color(0xFF64748B),
                height: 1.5,
              ),
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.brandOrange,
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              ),
              onPressed: () {
                if (Navigator.canPop(context)) {
                  Navigator.pop(context);
                }
              },
              icon: const Icon(Icons.shopping_bag_outlined, size: 17),
              label: const Text(
                'Mulai Belanja',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
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
    final isActive = _isOrderActive(status);
    final isDelivered = status == 'delivered';
    final statusLabel = _getStatusLabel(order);
    final statusTextColor = _getStatusTextColor(order);
    final statusBgColor = _getStatusBgColor(order);
    final statusIcon = _getStatusIcon(status);

    final totalAmount = double.tryParse(order['total_amount']?.toString() ?? '0') ?? 0;
    final int? storeId = int.tryParse(order['store_id']?.toString() ?? '');

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

    final String payMethodLabel = payMethod == 'doku'
        ? 'DOKU QRIS'
        : payMethod == 'wallet'
            ? 'Saldo Dompet'
            : 'Tunai (COD)';

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isActive
              ? AppTheme.brandOrange.withValues(alpha: 0.35)
              : const Color(0xFFE2E8F0),
          width: isActive ? 1.4 : 1.0,
        ),
        boxShadow: isActive
            ? [
                BoxShadow(
                  color: AppTheme.brandOrange.withValues(alpha: 0.08),
                  blurRadius: 14,
                  offset: const Offset(0, 4),
                ),
              ]
            : [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.03),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () => _openTracking(context, orderCode),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Top strip for active orders
              if (isActive)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFF7ED),
                    borderRadius: const BorderRadius.vertical(top: Radius.circular(15)),
                    border: Border(
                      bottom: BorderSide(
                        color: AppTheme.brandOrange.withValues(alpha: 0.15),
                      ),
                    ),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 7,
                        height: 7,
                        decoration: const BoxDecoration(
                          color: AppTheme.brandOrange,
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          _getActivePhaseMessage(status),
                          style: const TextStyle(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFFC2410C),
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const Icon(Icons.chevron_right_rounded, size: 16, color: Color(0xFFC2410C)),
                    ],
                  ),
                ),

              // Store Info & Status Header
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 12, 14, 11),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    // Store Thumbnail
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(9),
                        child: (!isParcel && storeLogoUrl != null)
                            ? CachedNetworkImage(
                                imageUrl: storeLogoUrl,
                                fit: BoxFit.cover,
                                errorWidget: (_, __, ___) => _storeFallback(status, isParcel),
                              )
                            : _storeFallback(status, isParcel),
                      ),
                    ),
                    const SizedBox(width: 10),

                    // Store Name & Order Info
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              if (isParcel)
                                Container(
                                  margin: const EdgeInsets.only(right: 6),
                                  padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFEDE9FE),
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: const Text(
                                    'PARCEL',
                                    style: TextStyle(
                                      fontSize: 9,
                                      fontWeight: FontWeight.w800,
                                      color: Color(0xFF6D28D9),
                                    ),
                                  ),
                                ),
                              Expanded(
                                child: Text(
                                  storeName,
                                  style: const TextStyle(
                                    fontSize: 13.5,
                                    fontWeight: FontWeight.w700,
                                    color: Color(0xFF0F172A),
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 3),
                          Row(
                            children: [
                              Text(
                                order['created_at'] != null ? _formatDate(order['created_at'].toString()) : '–',
                                style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                              ),
                              if (orderCode.isNotEmpty) ...[
                                const Text(' • ', style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
                                Text(
                                  '#${orderCode.length > 10 ? orderCode.substring(orderCode.length - 8) : orderCode}',
                                  style: const TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFF64748B),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(width: 8),

                    // Status Badge
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4.5),
                      decoration: BoxDecoration(
                        color: statusBgColor,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: statusTextColor.withValues(alpha: 0.2), width: 1),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(statusIcon, size: 12, color: statusTextColor),
                          const SizedBox(width: 4),
                          Text(
                            statusLabel,
                            style: TextStyle(
                              fontSize: 10.5,
                              fontWeight: FontWeight.w800,
                              color: statusTextColor,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              // Divider
              const Divider(height: 1, thickness: 1, color: Color(0xFFF1F5F9)),

              // Items Preview Section
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 10, 14, 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (items.isEmpty)
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 4),
                        child: Row(
                          children: [
                            Container(
                              width: 36,
                              height: 36,
                              decoration: BoxDecoration(
                                color: const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Icon(
                                isParcel ? Icons.local_shipping_outlined : Icons.restaurant_rounded,
                                color: const Color(0xFF64748B),
                                size: 18,
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                isParcel ? 'Pengiriman Paket Parcel' : '1x Pesanan di $storeName',
                                style: const TextStyle(
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.w600,
                                  color: Color(0xFF334155),
                                ),
                              ),
                            ),
                          ],
                        ),
                      )
                    else ...[
                      ...items.take(2).map((it) {
                        final itMap = it is Map ? it : {};
                        final name = (itMap['product_name'] ??
                                itMap['name'] ??
                                itMap['title'] ??
                                itMap['item_name'] ??
                                (itMap['product'] is Map ? itMap['product']['name'] : null) ??
                                'Menu Kuliner')
                            .toString();
                        final qty = itMap['quantity'] ?? 1;
                        final rawImg = itMap['product_image'] ??
                            itMap['image'] ??
                            (itMap['product'] is Map ? itMap['product']['image'] : null);
                        final imgUrl = rawImg != null && rawImg.toString().isNotEmpty
                            ? ApiConstants.formatImageUrl(rawImg.toString())
                            : null;

                        return Padding(
                          padding: const EdgeInsets.only(bottom: 6),
                          child: InkWell(
                            onTap: () => OrderItemDetailModal.show(
                              context,
                              itMap,
                              storeName: storeName,
                              storeLogo: rawStoreLogo?.toString(),
                            ),
                            borderRadius: BorderRadius.circular(8),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(vertical: 2),
                              child: Row(
                                children: [
                                  ClipRRect(
                                    borderRadius: BorderRadius.circular(8),
                                    child: imgUrl != null
                                        ? CachedNetworkImage(
                                            imageUrl: imgUrl,
                                            width: 36,
                                            height: 36,
                                            fit: BoxFit.cover,
                                            errorWidget: (_, __, ___) => _foodFallback(),
                                          )
                                        : _foodFallback(),
                                  ),
                                  const SizedBox(width: 9),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: AppTheme.brandOrange.withValues(alpha: 0.1),
                                      borderRadius: BorderRadius.circular(5),
                                    ),
                                    child: Text(
                                      '${qty}x',
                                      style: const TextStyle(
                                        fontSize: 10.5,
                                        fontWeight: FontWeight.w800,
                                        color: AppTheme.brandOrange,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      name,
                                      style: const TextStyle(
                                        fontSize: 12.5,
                                        color: Color(0xFF1E293B),
                                        fontWeight: FontWeight.w600,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  const Icon(
                                    Icons.chevron_right_rounded,
                                    size: 16,
                                    color: Color(0xFFCBD5E1),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        );
                      }),
                      if (items.length > 2)
                        Padding(
                          padding: const EdgeInsets.only(top: 2, left: 45),
                          child: Text(
                            '+ ${items.length - 2} menu lainnya',
                            style: const TextStyle(
                              fontSize: 11,
                              color: Color(0xFF64748B),
                              fontWeight: FontWeight.w500,
                              fontStyle: FontStyle.italic,
                            ),
                          ),
                        ),
                    ],
                  ],
                ),
              ),

              // Bottom Total & Action Section
              Container(
                padding: const EdgeInsets.fromLTRB(14, 10, 14, 12),
                decoration: const BoxDecoration(
                  color: Color(0xFFFAFAFA),
                  borderRadius: BorderRadius.vertical(bottom: Radius.circular(15)),
                  border: Border(top: BorderSide(color: Color(0xFFF1F5F9))),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    // Total + Payment Method
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Text(
                              'Total Belanja',
                              style: TextStyle(
                                fontSize: 10.5,
                                color: Color(0xFF64748B),
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            const SizedBox(width: 5),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                              decoration: BoxDecoration(
                                color: const Color(0xFFE2E8F0),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                payMethodLabel,
                                style: const TextStyle(
                                  fontSize: 8.5,
                                  fontWeight: FontWeight.w700,
                                  color: Color(0xFF475569),
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 2),
                        Text(
                          CurrencyFormatter.formatRupiah(totalAmount),
                          style: TextStyle(
                            fontSize: 14.5,
                            fontWeight: FontWeight.w900,
                            color: isActive ? AppTheme.brandOrange : const Color(0xFF0F172A),
                          ),
                        ),
                      ],
                    ),

                    // Action Buttons
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (isActive) ...[
                          _actionPillButton(
                            label: 'Lacak Live',
                            icon: Icons.two_wheeler_rounded,
                            isPrimary: true,
                            onTap: () => _openTracking(context, orderCode),
                          ),
                        ] else if (isUnpaid) ...[
                          _actionPillButton(
                            label: 'Bayar Sekarang',
                            icon: Icons.payment_rounded,
                            color: const Color(0xFFD97706),
                            isPrimary: true,
                            onTap: () => _openTracking(context, orderCode),
                          ),
                        ] else if (isDelivered) ...[
                          if (storeId != null && !isParcel)
                            Padding(
                              padding: const EdgeInsets.only(right: 6),
                              child: OutlinedButton(
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: AppTheme.brandOrange,
                                  side: BorderSide(
                                    color: AppTheme.brandOrange.withValues(alpha: 0.4),
                                  ),
                                  padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
                                  minimumSize: Size.zero,
                                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                ),
                                onPressed: () {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (_) => StoreDetailScreen(storeId: storeId),
                                    ),
                                  );
                                },
                                child: const Text(
                                  'Pesan Lagi',
                                  style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700),
                                ),
                              ),
                            ),
                          OutlinedButton(
                            style: OutlinedButton.styleFrom(
                              foregroundColor: const Color(0xFF334155),
                              side: const BorderSide(color: Color(0xFFCBD5E1)),
                              padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
                              minimumSize: Size.zero,
                              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                            ),
                            onPressed: () => _openTracking(context, orderCode),
                            child: const Text(
                              'Detail',
                              style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700),
                            ),
                          ),
                        ] else ...[
                          OutlinedButton(
                            style: OutlinedButton.styleFrom(
                              foregroundColor: const Color(0xFF334155),
                              side: const BorderSide(color: Color(0xFFCBD5E1)),
                              padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
                              minimumSize: Size.zero,
                              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                            ),
                            onPressed: () => _openTracking(context, orderCode),
                            child: const Text(
                              'Detail Pesanan',
                              style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _actionPillButton({
    required String label,
    required IconData icon,
    required VoidCallback onTap,
    bool isPrimary = false,
    Color? color,
  }) {
    final effectiveColor = color ?? AppTheme.brandOrange;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7.5),
        decoration: BoxDecoration(
          color: isPrimary ? effectiveColor : Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: isPrimary ? null : Border.all(color: effectiveColor),
          boxShadow: isPrimary
              ? [
                  BoxShadow(
                    color: effectiveColor.withValues(alpha: 0.3),
                    blurRadius: 8,
                    offset: const Offset(0, 3),
                  ),
                ]
              : null,
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              size: 14,
              color: isPrimary ? Colors.white : effectiveColor,
            ),
            const SizedBox(width: 5),
            Text(
              label,
              style: TextStyle(
                fontSize: 11.5,
                fontWeight: FontWeight.w800,
                color: isPrimary ? Colors.white : effectiveColor,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _storeFallback(String status, bool isParcel) => Container(
        color: const Color(0xFFFFF7ED),
        child: Center(
          child: Icon(
            isParcel ? Icons.local_shipping_outlined : _getStatusIcon(status),
            color: AppTheme.brandOrange,
            size: 20,
          ),
        ),
      );

  Widget _foodFallback() => Container(
        width: 36,
        height: 36,
        decoration: BoxDecoration(
          color: const Color(0xFFFEF2F2),
          borderRadius: BorderRadius.circular(8),
        ),
        child: const Icon(
          Icons.restaurant_rounded,
          color: AppTheme.primaryRed,
          size: 18,
        ),
      );

  void _openTracking(BuildContext context, String orderCode) {
    if (orderCode.isEmpty) return;
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => OrderTrackingScreen(orderCode: orderCode),
      ),
    );
  }

  String _formatDate(String dateStr) {
    try {
      final dt = DateTime.parse(dateStr).toLocal();
      final months = [
        'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
      ];
      final min = dt.minute.toString().padLeft(2, '0');
      final hr = dt.hour.toString().padLeft(2, '0');
      return '${dt.day} ${months[dt.month - 1]}, $hr:$min';
    } catch (_) {
      return dateStr;
    }
  }
}
