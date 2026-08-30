import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/driver_controller.dart';

class DriverOrderHistoryScreen extends StatefulWidget {
  final Function(int)? onNavigateTab;

  const DriverOrderHistoryScreen({super.key, this.onNavigateTab});

  @override
  State<DriverOrderHistoryScreen> createState() => _DriverOrderHistoryScreenState();
}

class _DriverOrderHistoryScreenState extends State<DriverOrderHistoryScreen> {
  String _selectedFilter = 'all';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DriverController>().fetchOrderHistory(status: _selectedFilter);
    });
  }

  void _onFilterChanged(String filter) {
    if (_selectedFilter == filter) return;
    setState(() => _selectedFilter = filter);
    context.read<DriverController>().fetchOrderHistory(status: filter);
  }

  @override
  Widget build(BuildContext context) {
    final driverCtrl = context.watch<DriverController>();
    final orders = driverCtrl.orderHistory;

    return Scaffold(
      backgroundColor: const Color(0xFF090D16),
      body: RefreshIndicator(
        color: const Color(0xFFEF4444),
        backgroundColor: const Color(0xFF0F172A),
        onRefresh: () => driverCtrl.fetchOrderHistory(status: _selectedFilter),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            // 1. Top Summary Banner
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Title
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Row(
                          children: [
                            Icon(Icons.history_rounded, color: Color(0xFFEF4444), size: 22),
                            SizedBox(width: 8),
                            Text(
                              'Riwayat Antaran Driver',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w900,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                        if (driverCtrl.isLoadingHistory)
                          const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFFEF4444)),
                          ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Metrics Row (3 columns)
                    Row(
                      children: [
                        // Total Selesai Card
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: const Color(0xFF0F172A),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: const Color(0xFF1E293B)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Text('Selesai', style: TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
                                    Container(
                                      padding: const EdgeInsets.all(3),
                                      decoration: const BoxDecoration(color: Color(0xFF064E3B), shape: BoxShape.circle),
                                      child: const Icon(Icons.check_circle_rounded, color: Color(0xFF34D399), size: 11),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '${driverCtrl.historyDeliveredCount}',
                                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: Color(0xFF34D399)),
                                ),
                                const Text('Trip', style: TextStyle(fontSize: 9.5, color: Color(0xFF64748B))),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),

                        // Total KM Card
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: const Color(0xFF0F172A),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: const Color(0xFF1E293B)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Text('Total Jarak', style: TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
                                    Container(
                                      padding: const EdgeInsets.all(3),
                                      decoration: const BoxDecoration(color: Color(0xFF1E3A8A), shape: BoxShape.circle),
                                      child: const Icon(Icons.route_rounded, color: Color(0xFF60A5FA), size: 11),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  driverCtrl.historyTotalKm.toStringAsFixed(1),
                                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: Color(0xFF60A5FA)),
                                ),
                                const Text('km', style: TextStyle(fontSize: 9.5, color: Color(0xFF64748B))),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),

                        // Total Komisi Card
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: const Color(0xFF0F172A),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: const Color(0xFF1E293B)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Text('Komisi', style: TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
                                    Container(
                                      padding: const EdgeInsets.all(3),
                                      decoration: const BoxDecoration(color: Color(0xFF451A03), shape: BoxShape.circle),
                                      child: const Icon(Icons.monetization_on_rounded, color: Color(0xFFFBBF24), size: 11),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  CurrencyFormatter.formatRupiah(driverCtrl.historyTotalEarnings),
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w900, color: Color(0xFFFBBF24)),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                const Text('total', style: TextStyle(fontSize: 9.5, color: Color(0xFF64748B))),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Filter Chips Row
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          _buildFilterChip('all', 'Semua (${orders.length})'),
                          const SizedBox(width: 8),
                          _buildFilterChip('completed', 'Selesai Diantar (${driverCtrl.historyDeliveredCount})'),
                          const SizedBox(width: 8),
                          _buildFilterChip('canceled', 'Dibatalkan (${driverCtrl.historyCanceledCount})'),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // 2. Order History List
            if (driverCtrl.isLoadingHistory && orders.isEmpty)
              const SliverFillRemaining(
                child: Center(
                  child: CircularProgressIndicator(color: Color(0xFFEF4444)),
                ),
              )
            else if (orders.isEmpty)
              SliverFillRemaining(
                hasScrollBody: false,
                child: Padding(
                  padding: const EdgeInsets.all(32),
                  child: Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            color: const Color(0xFF0F172A),
                            shape: BoxShape.circle,
                            border: Border.all(color: const Color(0xFF1E293B)),
                          ),
                          child: const Icon(Icons.receipt_long_rounded, size: 48, color: Color(0xFF64748B)),
                        ),
                        const SizedBox(height: 16),
                        const Text(
                          'Belum Ada Riwayat Pesanan',
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Semua orderan yang telah selesai kamu antarkan akan tercatat di sini.',
                          style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                ),
              )
            else
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 30),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (ctx, idx) {
                      final order = orders[idx];
                      if (order is! Map) return const SizedBox.shrink();
                      return _buildHistoryOrderCard(context, Map<String, dynamic>.from(order));
                    },
                    childCount: orders.length,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip(String key, String label) {
    final isSelected = _selectedFilter == key;
    return GestureDetector(
      onTap: () => _onFilterChanged(key),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFFEF4444) : const Color(0xFF0F172A),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? const Color(0xFFEF4444) : const Color(0xFF1E293B),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
            color: isSelected ? Colors.white : const Color(0xFF94A3B8),
          ),
        ),
      ),
    );
  }

  Widget _buildHistoryOrderCard(BuildContext context, Map<String, dynamic> order) {
    final orderCode = order['order_code'] ?? order['id'] ?? '-';
    final orderStatus = (order['order_status']?.toString() ?? 'delivered').toLowerCase();
    final isDelivered = orderStatus == 'delivered' || orderStatus == 'completed';
    final isCanceled = orderStatus == 'canceled';

    final fee = double.tryParse((order['driver_earning'] ?? order['delivery_charge'])?.toString() ?? '5000') ?? 5000.0;
    final double distKm = double.tryParse(order['distance_km']?.toString() ?? '0') ?? 0.0;
    final createdAt = order['created_at']?.toString() ?? '';
    final customerName = order['customer_name']?.toString() ?? 'Pelanggan';
    final storeName = order['store_name']?.toString() ?? 'Mitra Resto';

    final deliveryAddress = (order['delivery_address'] is Map)
        ? (order['delivery_address']['address'] ?? 'Cicalengka')
        : (order['delivery_address']?.toString() ?? 'Cicalengka');

    final rawItems = (order['items'] is List) ? (order['items'] as List) : [];
    final bool isMultiStore = (order['batch_stores'] is List && (order['batch_stores'] as List).isNotEmpty) ||
        (order['batch_sub_orders'] is List && (order['batch_sub_orders'] as List).isNotEmpty);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E293B)),
        boxShadow: const [
          BoxShadow(color: Colors.black26, blurRadius: 8, offset: Offset(0, 2)),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () => _showOrderDetailModal(context, order),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header: Order Code, Date, & Status Badge
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                          decoration: BoxDecoration(
                            color: const Color(0xFF1E293B),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            '#$orderCode',
                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white),
                          ),
                        ),
                        if (createdAt.isNotEmpty) ...[
                          const SizedBox(width: 8),
                          Text(
                            createdAt.length > 16 ? createdAt.substring(0, 16) : createdAt,
                            style: const TextStyle(fontSize: 10, color: Color(0xFF64748B)),
                          ),
                        ],
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: isDelivered
                            ? const Color(0xFF064E3B)
                            : (isCanceled ? const Color(0xFF450A0A) : const Color(0xFF1E293B)),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        isDelivered ? '✓ Selesai' : (isCanceled ? 'Dibatalkan' : 'Dalam Proses'),
                        style: TextStyle(
                          fontSize: 9.5,
                          fontWeight: FontWeight.bold,
                          color: isDelivered
                              ? const Color(0xFF34D399)
                              : (isCanceled ? const Color(0xFFF87171) : const Color(0xFF94A3B8)),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                const Divider(height: 1, color: Color(0xFF1E293B)),
                const SizedBox(height: 10),

                // Store info
                Row(
                  children: [
                    const Icon(Icons.storefront_rounded, size: 15, color: Color(0xFFFBBF24)),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        isMultiStore ? 'Multi-Toko ($storeName)' : storeName,
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),

                // Customer info & Address
                Row(
                  children: [
                    const Icon(Icons.location_on_rounded, size: 15, color: Color(0xFF22C55E)),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        '$customerName • $deliveryAddress',
                        style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),

                // Distance & Payment method row
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1E3A8A).withValues(alpha: 0.5),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: const Color(0xFF1E3A8A)),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.route_rounded, size: 10, color: Color(0xFF60A5FA)),
                          const SizedBox(width: 4),
                          Text(
                            distKm > 0 ? '${distKm.toStringAsFixed(1)} km' : 'N/A',
                            style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF60A5FA)),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

                // Items summary
                if (rawItems.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B).withValues(alpha: 0.6),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      rawItems.take(2).map((it) {
                        if (it is! Map) return '';
                        final iName = it['product_name'] ?? it['item_name'] ?? it['name'] ?? 'Menu';
                        final iQty = it['quantity'] ?? '1';
                        return '${iQty}x $iName';
                      }).where((s) => s.isNotEmpty).join(', ') +
                          (rawItems.length > 2 ? ' (+${rawItems.length - 2} lainnya)' : ''),
                      style: const TextStyle(fontSize: 10.5, color: Color(0xFFCBD5E1)),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],

                const SizedBox(height: 10),
                // Bottom row: Earnings + KM + Action
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Text('Komisi: ', style: TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
                            Text(
                              CurrencyFormatter.formatRupiah(fee),
                              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w900, color: Color(0xFF4ADE80)),
                            ),
                          ],
                        ),
                        if (distKm > 0)
                          Text(
                            '⟳ Jarak: ${distKm.toStringAsFixed(1)} km',
                            style: const TextStyle(fontSize: 9.5, color: Color(0xFF60A5FA)),
                          ),
                      ],
                    ),
                    const Row(
                      children: [
                        Text('Rincian', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFFFCA5A5))),
                        Icon(Icons.chevron_right_rounded, size: 16, color: Color(0xFFFCA5A5)),
                      ],
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _showOrderDetailModal(BuildContext context, Map<String, dynamic> initialOrder) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _DriverOrderDetailSheet(initialOrder: initialOrder),
    );
  }
}

class _DriverOrderDetailSheet extends StatefulWidget {
  final Map<String, dynamic> initialOrder;

  const _DriverOrderDetailSheet({required this.initialOrder});

  @override
  State<_DriverOrderDetailSheet> createState() => _DriverOrderDetailSheetState();
}

class _DriverOrderDetailSheetState extends State<_DriverOrderDetailSheet> {
  Map<String, dynamic>? _order;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _order = widget.initialOrder;
    _fetchDetail();
  }

  Future<void> _fetchDetail() async {
    final orderId = _order?['id'] ?? _order?['order_code'];
    if (orderId == null) {
      setState(() => _loading = false);
      return;
    }
    final detail = await context.read<DriverController>().fetchOrderDetail(orderId);
    if (mounted) {
      setState(() {
        if (detail != null) _order = detail;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final order = _order ?? widget.initialOrder;
    final orderCode = order['order_code'] ?? order['id'] ?? '-';
    final status = (order['order_status']?.toString() ?? 'delivered').toLowerCase();
    final isDelivered = status == 'delivered' || status == 'completed';
    final isCanceled = status == 'canceled';

    final double deliveryFee = double.tryParse((order['delivery_charge'] ?? order['driver_earning'])?.toString() ?? '5000') ?? 5000.0;
    final double driverEarning = double.tryParse((order['driver_earning'] ?? order['delivery_charge'])?.toString() ?? '5000') ?? deliveryFee;
    final double orderAmount = double.tryParse(order['order_amount']?.toString() ?? '0') ?? 0.0;
    final double couponDiscount = double.tryParse(order['coupon_discount']?.toString() ?? '0') ?? 0.0;
    final double taxAmount = double.tryParse(order['tax_amount']?.toString() ?? '0') ?? 0.0;
    final double totalAmount = double.tryParse(order['total_amount']?.toString() ?? '') ?? (orderAmount + deliveryFee - couponDiscount + taxAmount);
    final double distKm = double.tryParse(order['distance_km']?.toString() ?? '0') ?? 0.0;
    final String customerName = order['customer_name']?.toString() ?? 'Pelanggan';
    final String customerPhone = order['customer_phone']?.toString() ?? '';
    final String createdAt = order['created_at']?.toString() ?? '';
    final String paymentMethod = (order['payment_method']?.toString().toUpperCase() == 'CASH' ||
            order['payment_method']?.toString().toUpperCase() == 'COD')
        ? 'COD (Tunai)'
        : (order['payment_method']?.toString().toUpperCase() ?? 'Non-Tunai');

    final deliveryAddress = (order['delivery_address'] is Map)
        ? (order['delivery_address']['address'] ?? 'Cicalengka')
        : (order['delivery_address']?.toString() ?? 'Cicalengka');

    final List rawItems = (order['items'] is List) ? (order['items'] as List) : [];

    // Group items by store
    final Map<String, List<dynamic>> storeGroups = {};
    for (var it in rawItems) {
      if (it is Map) {
        final sName = (it['store_name'] ?? order['store_name'] ?? 'Mitra Resto').toString();
        if (!storeGroups.containsKey(sName)) storeGroups[sName] = [];
        storeGroups[sName]!.add(it);
      }
    }
    if (storeGroups.isEmpty && rawItems.isNotEmpty) {
      storeGroups[order['store_name'] ?? 'Mitra Resto'] = rawItems;
    }

    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: const BoxDecoration(
        color: Color(0xFF0F172A),
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        border: Border(top: BorderSide(color: Color(0xFF1E293B))),
      ),
      child: Column(
        children: [
          // Drag Handle
          Container(
            width: 40,
            height: 4,
            margin: const EdgeInsets.only(top: 12, bottom: 8),
            decoration: BoxDecoration(
              color: const Color(0xFF334155),
              borderRadius: BorderRadius.circular(2),
            ),
          ),

          // Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Text(
                          'Rincian Pesanan ',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                        Text(
                          '#$orderCode',
                          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFFEF4444)),
                        ),
                      ],
                    ),
                    if (createdAt.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(createdAt, style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
                    ],
                  ],
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded, color: Colors.white70),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFF1E293B)),

          // Content
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFFEF4444)))
                : SingleChildScrollView(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Status & Commission Banner
                        Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: isDelivered ? const Color(0xFF064E3B) : (isCanceled ? const Color(0xFF450A0A) : const Color(0xFF1E293B)),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: isDelivered ? const Color(0xFF059669) : (isCanceled ? const Color(0xFF7F1D1D) : const Color(0xFF334155)),
                            ),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  Icon(
                                    isDelivered ? Icons.check_circle_rounded : Icons.info_outline_rounded,
                                    color: isDelivered ? const Color(0xFF34D399) : const Color(0xFF94A3B8),
                                    size: 22,
                                  ),
                                  const SizedBox(width: 10),
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        isDelivered ? 'Pengantaran Berhasil' : (isCanceled ? 'Pesanan Dibatalkan' : 'Status Aktif'),
                                        style: TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 13,
                                          color: isDelivered ? const Color(0xFF34D399) : Colors.white,
                                        ),
                                      ),
                                      Text(
                                        'Metode: $paymentMethod',
                                        style: const TextStyle(fontSize: 10.5, color: Color(0xFFCBD5E1)),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  const Text('Komisi Driver', style: TextStyle(fontSize: 10, color: Color(0xFFCBD5E1))),
                                  Text(
                                    CurrencyFormatter.formatRupiah(driverEarning),
                                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: Color(0xFF4ADE80)),
                                  ),
                                  if (distKm > 0) ...[
                                    const SizedBox(height: 2),
                                    Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        const Icon(Icons.route_rounded, size: 11, color: Color(0xFF60A5FA)),
                                        const SizedBox(width: 3),
                                        Text(
                                          '${distKm.toStringAsFixed(1)} km',
                                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF60A5FA)),
                                        ),
                                      ],
                                    ),
                                  ],
                                ],
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 12),

                        // Stats detail row (km + tarif + komisi)
                        if (distKm > 0)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                            decoration: BoxDecoration(
                              color: const Color(0xFF1E293B),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: const Color(0xFF334155)),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceAround,
                              children: [
                                _detailStatTile(Icons.route_rounded, '${distKm.toStringAsFixed(1)} km', 'Jarak Antar', const Color(0xFF60A5FA)),
                                Container(width: 1, height: 32, color: const Color(0xFF334155)),
                                _detailStatTile(
                                  Icons.local_shipping_rounded,
                                  CurrencyFormatter.formatRupiah(deliveryFee),
                                  'Ongkir User',
                                  const Color(0xFFFBBF24),
                                ),
                                Container(width: 1, height: 32, color: const Color(0xFF334155)),
                                _detailStatTile(Icons.payments_rounded, CurrencyFormatter.formatRupiah(driverEarning), 'Komisi Bersih', const Color(0xFF4ADE80)),
                              ],
                            ),
                          ),
                        const SizedBox(height: 8),

                        // Customer Info Section
                        Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: const Color(0xFF1E293B),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: const Color(0xFF334155)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Row(
                                children: [
                                  Icon(Icons.person_pin_circle_rounded, color: Color(0xFF22C55E), size: 18),
                                  SizedBox(width: 8),
                                  Text('Tujuan Pelanggan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Colors.white)),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text(customerName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.white)),
                              const SizedBox(height: 2),
                              Text(deliveryAddress, style: const TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8))),
                              if (customerPhone.isNotEmpty) ...[
                                const SizedBox(height: 8),
                                Row(
                                  children: [
                                    GestureDetector(
                                      onTap: () => launchUrl(Uri.parse('tel:$customerPhone')),
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFF064E3B),
                                          borderRadius: BorderRadius.circular(8),
                                          border: Border.all(color: const Color(0xFF059669)),
                                        ),
                                        child: Row(
                                          children: [
                                            const Icon(Icons.phone_rounded, size: 12, color: Color(0xFF34D399)),
                                            const SizedBox(width: 6),
                                            Text(customerPhone, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF34D399))),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),

                        // Ordered Items Grouped by Store
                        const Text(
                          'Daftar Menu yang Diantar',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Colors.white),
                        ),
                        const SizedBox(height: 10),

                        ...storeGroups.entries.map((entry) {
                          final sName = entry.key;
                          final sItems = entry.value;

                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: const Color(0xFF1E293B),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: const Color(0xFF334155)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    const Icon(Icons.storefront_rounded, size: 15, color: Color(0xFFFBBF24)),
                                    const SizedBox(width: 6),
                                    Expanded(
                                      child: Text(
                                        sName,
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFFFDE68A)),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                const Divider(height: 1, color: Color(0xFF334155)),
                                const SizedBox(height: 8),
                                ...sItems.map((it) {
                                  if (it is! Map) return const SizedBox.shrink();
                                  final name = it['product_name'] ?? it['item_name'] ?? it['name'] ?? 'Menu';
                                  final qty = it['quantity'] ?? '1';
                                  final price = double.tryParse(it['price']?.toString() ?? '0') ?? 0.0;
                                  final subtotal = double.tryParse(it['subtotal']?.toString() ?? '') ?? (price * (int.tryParse(qty.toString()) ?? 1));
                                  final notes = it['notes']?.toString();

                                  return Padding(
                                    padding: const EdgeInsets.only(bottom: 6),
                                    child: Row(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFEF4444),
                                            borderRadius: BorderRadius.circular(4),
                                          ),
                                          child: Text(
                                            '${qty}x',
                                            style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.white),
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text(name.toString(), style: const TextStyle(fontSize: 12, color: Colors.white, fontWeight: FontWeight.w600)),
                                              if (notes != null && notes.trim().isNotEmpty) ...[
                                                Text('Catatan: $notes', style: const TextStyle(fontSize: 10, color: Color(0xFFFBBF24), fontStyle: FontStyle.italic)),
                                              ],
                                            ],
                                          ),
                                        ),
                                        if (subtotal > 0)
                                          Text(
                                            CurrencyFormatter.formatRupiah(subtotal),
                                            style: const TextStyle(fontSize: 11.5, color: Color(0xFFCBD5E1), fontWeight: FontWeight.bold),
                                          ),
                                      ],
                                    ),
                                  );
                                }),
                              ],
                            ),
                          );
                        }),

                        const SizedBox(height: 8),
                        // Total Payment Summary
                        Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: const Color(0xFF1E293B),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: const Color(0xFF334155)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Row(
                                children: [
                                  Icon(Icons.receipt_long_rounded, color: Color(0xFF38BDF8), size: 16),
                                  SizedBox(width: 6),
                                  Text(
                                    'Rincian Pembayaran & Ongkir',
                                    style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Colors.white),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text('Subtotal Belanja', style: TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8))),
                                  Text(CurrencyFormatter.formatRupiah(orderAmount), style: const TextStyle(fontSize: 11.5, color: Colors.white, fontWeight: FontWeight.w600)),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text('Ongkos Kirim (Dibayar User)', style: TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8))),
                                  Text(CurrencyFormatter.formatRupiah(deliveryFee), style: const TextStyle(fontSize: 11.5, color: Color(0xFF4ADE80), fontWeight: FontWeight.bold)),
                                ],
                              ),
                              if (couponDiscount > 0) ...[
                                const SizedBox(height: 6),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Text('Diskon Voucher', style: TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8))),
                                    Text('-${CurrencyFormatter.formatRupiah(couponDiscount)}', style: const TextStyle(fontSize: 11.5, color: Color(0xFFF87171), fontWeight: FontWeight.w600)),
                                  ],
                                ),
                              ],
                              if (taxAmount > 0) ...[
                                const SizedBox(height: 6),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Text('Pajak / Biaya Layanan', style: TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8))),
                                    Text(CurrencyFormatter.formatRupiah(taxAmount), style: const TextStyle(fontSize: 11.5, color: Colors.white, fontWeight: FontWeight.w600)),
                                  ],
                                ),
                              ],
                              const Divider(height: 16, color: Color(0xFF334155)),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text('Total Tagihan Pelanggan', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
                                  Text(
                                    CurrencyFormatter.formatRupiah(totalAmount),
                                    style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Color(0xFF38BDF8)),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF064E3B).withValues(alpha: 0.6),
                                  borderRadius: BorderRadius.circular(10),
                                  border: Border.all(color: const Color(0xFF059669)),
                                ),
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Row(
                                      children: [
                                        Icon(Icons.account_balance_wallet_rounded, color: Color(0xFF34D399), size: 14),
                                        SizedBox(width: 6),
                                        Text(
                                          'Saldo Masuk ke Dompet Driver',
                                          style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF34D399)),
                                        ),
                                      ],
                                    ),
                                    Text(
                                      CurrencyFormatter.formatRupiah(driverEarning),
                                      style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w900, color: Color(0xFF4ADE80)),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _detailStatTile(IconData icon, String value, String label, Color color) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 16, color: color),
        const SizedBox(height: 3),
        Text(
          value,
          style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w900, color: color),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        Text(label, style: const TextStyle(fontSize: 9.5, color: Color(0xFF64748B))),
      ],
    );
  }
}
