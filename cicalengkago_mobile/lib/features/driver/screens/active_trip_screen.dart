import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/driver_controller.dart';

class ActiveTripScreen extends StatefulWidget {
  final Map<String, dynamic> trip;
  const ActiveTripScreen({super.key, required this.trip});

  @override
  State<ActiveTripScreen> createState() => _ActiveTripScreenState();
}

class _ActiveTripScreenState extends State<ActiveTripScreen> {
  final _otpCtrl = TextEditingController();

  @override
  void dispose() {
    _otpCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final driverCtrl = context.watch<DriverController>();
    final trip = driverCtrl.activeTrip ?? widget.trip;
    final orderId = int.tryParse(trip['id']?.toString() ?? '0') ?? 0;
    final status = trip['order_status'] ?? 'accepted';
    final orderCode = trip['order_code'] ?? orderId.toString();
    final storeName = trip['store_name'] ?? 'Mitra Resto Cicalengka';
    final storeAddress = trip['store_address'] ?? 'Cicalengka';

    final rawDeliv = trip['delivery_address'];
    String deliveryAddress = 'Cicalengka';
    String customerName = trip['customer_name'] ?? 'Pelanggan Cicalengka';
    String customerPhone = trip['customer_phone'] ?? '';

    if (rawDeliv is Map) {
      deliveryAddress = rawDeliv['address']?.toString() ?? 'Cicalengka';
      if (rawDeliv['contact_person_name'] != null && rawDeliv['contact_person_name'].toString().isNotEmpty) {
        customerName = rawDeliv['contact_person_name'].toString();
      }
      if (rawDeliv['contact_person_number'] != null && rawDeliv['contact_person_number'].toString().isNotEmpty) {
        customerPhone = rawDeliv['contact_person_number'].toString();
      }
    } else if (rawDeliv is String) {
      deliveryAddress = rawDeliv;
    }

    final deliveryCharge = double.tryParse(trip['delivery_charge']?.toString() ?? '0') ?? 0;

    // Items list
    final List items = (trip['items'] as List?) ?? [];
    final List batchOrders = (trip['batch_orders'] as List?) ?? [];
    final hasBatch = batchOrders.isNotEmpty;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Status Banner
          _buildStatusBanner(status, orderCode),
          const SizedBox(height: 16),

          // Komisi chip
          Row(
            children: [
              const Text('Komisi Trip Ini:', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                decoration: BoxDecoration(
                  color: const Color(0xFFD1FAE5),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  CurrencyFormatter.formatRupiah(deliveryCharge),
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF059669)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Batch orders (multiple destinations)
          if (hasBatch) ...[
            _buildBatchOrdersSection(batchOrders),
          ] else ...[
            // Single order
            _buildLocationCard(
              storeName: storeName,
              storeAddress: storeAddress,
              customerName: customerName,
              deliveryAddress: deliveryAddress,
              customerPhone: customerPhone,
              items: items,
              status: status,
            ),
          ],

          const SizedBox(height: 20),

          // Action Buttons
          _buildActionSection(context, driverCtrl, orderId, status, customerPhone),
        ],
      ),
    );
  }

  Widget _buildStatusBanner(String status, String orderCode) {
    final statusInfo = _getStatusInfo(status);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: statusInfo['bgColor'] as Color,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: (statusInfo['color'] as Color).withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              color: (statusInfo['color'] as Color).withValues(alpha: 0.15),
              shape: BoxShape.circle,
            ),
            child: Icon(statusInfo['icon'] as IconData, color: statusInfo['color'] as Color, size: 24),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  statusInfo['label'] as String,
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: statusInfo['color'] as Color),
                ),
                Text(
                  'Order #$orderCode',
                  style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontFamily: 'monospace'),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: (statusInfo['color'] as Color).withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              statusInfo['stepLabel'] as String,
              style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: statusInfo['color'] as Color),
            ),
          ),
        ],
      ),
    );
  }

  Map<String, dynamic> _getStatusInfo(String status) {
    switch (status) {
      case 'accepted':
      case 'pending':
      case 'confirmed':
      case 'processing':
        return {
          'label': 'Menuju ke Toko / Resto',
          'stepLabel': 'Langkah 1/2',
          'icon': Icons.store_rounded,
          'color': const Color(0xFF2563EB),
          'bgColor': const Color(0xFFEFF6FF),
        };
      case 'picked_up':
      case 'handover':
      case 'on_the_way':
        return {
          'label': 'Mengantar ke Pelanggan',
          'stepLabel': 'Langkah 2/2',
          'icon': Icons.delivery_dining_rounded,
          'color': const Color(0xFF059669),
          'bgColor': const Color(0xFFECFDF5),
        };
      default:
        return {
          'label': 'Trip Aktif',
          'stepLabel': 'Aktif',
          'icon': Icons.navigation_rounded,
          'color': AppTheme.primaryRed,
          'bgColor': const Color(0xFFFEF2F2),
        };
    }
  }

  Widget _buildLocationCard({
    required String storeName,
    required String storeAddress,
    required String customerName,
    required String deliveryAddress,
    required String customerPhone,
    required List items,
    required String status,
  }) {
    // Group items per store
    final Map<String, List<dynamic>> itemsByStore = {};
    for (var item in items) {
      final String sName = (item['store_name'] ?? storeName).toString();
      if (!itemsByStore.containsKey(sName)) {
        itemsByStore[sName] = [];
      }
      itemsByStore[sName]!.add(item);
    }
    if (itemsByStore.isEmpty) {
      itemsByStore[storeName] = items;
    }

    final storeEntries = itemsByStore.entries.toList();

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // 1. Render each pickup store step with its items
          ...storeEntries.asMap().entries.map((stepEntry) {
            final int stepIdx = stepEntry.key;
            final String sName = stepEntry.value.key;
            final List sItems = stepEntry.value.value;
            final String sAddr = (sName == storeName) ? storeAddress : 'Cicalengka, Jawa Barat';

            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Step Badge & Store Info Header
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            width: 36,
                            height: 36,
                            decoration: BoxDecoration(
                              color: const Color(0xFFEFF6FF),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Center(
                              child: Text(
                                '${stepIdx + 1}',
                                style: const TextStyle(
                                  color: Color(0xFF2563EB),
                                  fontSize: 14,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Text(
                                      'LANGKAH ${stepIdx + 1}: JEMPUT PESANAN',
                                      style: const TextStyle(
                                        fontSize: 10,
                                        color: Color(0xFF2563EB),
                                        fontWeight: FontWeight.w800,
                                        letterSpacing: 0.5,
                                      ),
                                    ),
                                    const SizedBox(width: 6),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: const Color(0xFFDBEAFE),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text(
                                        '${sItems.length} Menu',
                                        style: const TextStyle(
                                          fontSize: 9.5,
                                          fontWeight: FontWeight.bold,
                                          color: Color(0xFF1D4ED8),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  sName,
                                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                                ),
                                Text(sAddr, style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                              ],
                            ),
                          ),
                          _mapBtn(sAddr),
                        ],
                      ),

                      // Items list belonging strictly to this store step
                      if (sItems.isNotEmpty) ...[
                        const SizedBox(height: 12),
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF8FAFC),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  const Icon(Icons.shopping_bag_rounded, size: 13, color: AppTheme.primaryRed),
                                  const SizedBox(width: 6),
                                  Expanded(
                                    child: Text(
                                      'Item yang diambil di $sName:',
                                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF334155)),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ],
                              ),
                              const Divider(height: 14, color: Color(0xFFE2E8F0)),
                              ...sItems.map((item) {
                                final pName = item['product_name'] ?? item['name'] ?? 'Menu';
                                final qty = item['quantity'] ?? 1;
                                final price = double.tryParse(item['total_price']?.toString() ?? item['price']?.toString() ?? '0') ?? 0;

                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 6),
                                  child: Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFE2E8F0),
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Text(
                                          '${qty}x',
                                          style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF334155)),
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Text(
                                          pName,
                                          style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: Color(0xFF1E293B)),
                                        ),
                                      ),
                                      if (price > 0)
                                        Text(
                                          CurrencyFormatter.formatRupiah(price),
                                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF059669)),
                                        ),
                                    ],
                                  ),
                                );
                              }),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),
                ),

                // Step Connector Line
                Container(
                  margin: const EdgeInsets.symmetric(horizontal: 32),
                  height: 20,
                  child: Column(
                    children: List.generate(3, (_) => Container(
                      margin: const EdgeInsets.only(bottom: 2),
                      width: 2,
                      height: 4,
                      decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(1)),
                    )),
                  ),
                ),
              ],
            );
          }),

          // 2. Customer Destination Step
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: const Color(0xFFECFDF5),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.home_rounded, color: Color(0xFF059669), size: 18),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'LANGKAH TERAKHIR: PENGANTARAN',
                        style: TextStyle(
                          fontSize: 10,
                          color: Color(0xFF059669),
                          fontWeight: FontWeight.w800,
                          letterSpacing: 0.5,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(customerName, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                      Text(deliveryAddress, style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                    ],
                  ),
                ),
                Column(
                  children: [
                    _mapBtn(deliveryAddress),
                    if (customerPhone.isNotEmpty) ...[
                      const SizedBox(height: 6),
                      GestureDetector(
                        onTap: () => launchUrl(Uri.parse('tel:$customerPhone')),
                        child: Container(
                          padding: const EdgeInsets.all(7),
                          decoration: BoxDecoration(
                            color: const Color(0xFFECFDF5),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(Icons.call_rounded, color: Color(0xFF059669), size: 16),
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
    );
  }

  Widget _buildBatchOrdersSection(List batchOrders) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(Icons.layers_rounded, size: 16, color: Color(0xFF7C3AED)),
            const SizedBox(width: 6),
            Text(
              'Batch Trip Gabungan: ${batchOrders.length} Pesanan Multi-Toko',
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF7C3AED)),
            ),
          ],
        ),
        const SizedBox(height: 10),
        ...batchOrders.asMap().entries.map((entry) {
          final i = entry.key;
          final order = entry.value as Map<String, dynamic>;
          final sName = order['store_name'] ?? 'Toko ${i + 1}';
          final cName = order['customer_name'] ?? 'Pelanggan';
          final List bItems = (order['items'] as List?) ?? [];

          return Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFFDDD6FE)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 24,
                          height: 24,
                          decoration: const BoxDecoration(color: Color(0xFF7C3AED), shape: BoxShape.circle),
                          child: Center(child: Text('${i + 1}', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold))),
                        ),
                        const SizedBox(width: 8),
                        Text('Order #${order['order_code'] ?? order['id']}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(color: const Color(0xFFF3E8FF), borderRadius: BorderRadius.circular(8)),
                      child: Text(sName, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF7C3AED))),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text('📍 Resto: $sName → Pelanggan: $cName', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF334155))),
                if (bItems.isNotEmpty) ...[
                  const Divider(height: 16, color: Color(0xFFF1F5F9)),
                  ...bItems.map((it) {
                    final pName = it['product_name'] ?? it['name'] ?? 'Menu';
                    final qty = it['quantity'] ?? 1;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 4),
                      child: Row(
                        children: [
                          const Icon(Icons.circle, size: 5, color: Color(0xFF7C3AED)),
                          const SizedBox(width: 6),
                          Text('${qty}x $pName', style: const TextStyle(fontSize: 11, color: Color(0xFF475569))),
                        ],
                      ),
                    );
                  }),
                ],
              ],
            ),
          );
        }),
      ],
    );
  }

  Widget _buildActionSection(BuildContext context, DriverController driverCtrl, int orderId, String status, String customerPhone) {
    return Column(
      children: [
        // Main action button
        if (status == 'accepted' || status == 'pending' || status == 'confirmed' || status == 'processing') ...[
          _actionButton(
            label: '✅ Sudah Ambil di Toko / Resto',
            color: const Color(0xFF2563EB),
            onTap: () async {
              final ok = await driverCtrl.updateTripStatus(orderId, 'picked_up');
              if (!ok && context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Gagal update status. Coba lagi.')));
              }
            },
          ),
        ] else if (status == 'picked_up' || status == 'handover' || status == 'on_the_way') ...[
          // OTP input for delivery confirmation
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFF0FDF4),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFF86EFAC)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    Icon(Icons.lock_rounded, color: Color(0xFF059669), size: 16),
                    SizedBox(width: 6),
                    Text('Masukkan Kode OTP Pelanggan', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF059669))),
                  ],
                ),
                const SizedBox(height: 4),
                const Text('Minta kode OTP 6 digit dari pelanggan untuk konfirmasi pesanan tiba.',
                    style: TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                const SizedBox(height: 10),
                TextField(
                  controller: _otpCtrl,
                  keyboardType: TextInputType.number,
                  maxLength: 6,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, letterSpacing: 8),
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: InputDecoration(
                    hintText: '------',
                    hintStyle: const TextStyle(letterSpacing: 8),
                    counterText: '',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF86EFAC), width: 2),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF059669), width: 2),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _actionButton(
            label: '📦 Pesanan Sudah Sampai (Selesai)',
            color: const Color(0xFF059669),
            onTap: () async {
              final otp = _otpCtrl.text.trim();
              final ok = await driverCtrl.updateTripStatus(orderId, 'delivered', otpCode: otp.isNotEmpty ? otp : null);
              if (context.mounted) {
                if (ok) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('🎉 Trip selesai! Komisi telah masuk ke dompet.'), backgroundColor: Colors.green),
                  );
                } else {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Gagal selesaikan trip. Periksa OTP kembali.')),
                  );
                }
              }
            },
          ),
        ],

        // Secondary actions
        const SizedBox(height: 12),
        Row(
          children: [
            if (customerPhone.isNotEmpty) ...[
              Expanded(
                child: OutlinedButton.icon(
                  icon: const Icon(Icons.call_rounded, size: 16),
                  label: const Text('Hubungi', style: TextStyle(fontSize: 12)),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    side: const BorderSide(color: Color(0xFF059669)),
                    foregroundColor: const Color(0xFF059669),
                  ),
                  onPressed: () => launchUrl(Uri.parse('tel:$customerPhone')),
                ),
              ),
              const SizedBox(width: 10),
            ],
            Expanded(
              child: OutlinedButton.icon(
                icon: const Icon(Icons.refresh_rounded, size: 16),
                label: const Text('Refresh', style: TextStyle(fontSize: 12)),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  side: const BorderSide(color: Color(0xFF94A3B8)),
                  foregroundColor: const Color(0xFF64748B),
                ),
                onPressed: () => driverCtrl.fetchRadarData(),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _actionButton({required String label, required Color color, required VoidCallback onTap}) {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton(
        style: ElevatedButton.styleFrom(
          backgroundColor: color,
          padding: const EdgeInsets.symmetric(vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          elevation: 2,
        ),
        onPressed: onTap,
        child: Text(label, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white)),
      ),
    );
  }

  Widget _mapBtn(String address) {
    return GestureDetector(
      onTap: () {
        final query = Uri.encodeComponent(address);
        launchUrl(Uri.parse('https://maps.google.com/?q=$query'));
      },
      child: Container(
        padding: const EdgeInsets.all(7),
        decoration: BoxDecoration(
          color: const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(10),
        ),
        child: const Icon(Icons.map_rounded, color: Color(0xFF2563EB), size: 16),
      ),
    );
  }
}
