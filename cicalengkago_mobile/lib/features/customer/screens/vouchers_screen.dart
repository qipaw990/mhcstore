import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/app_alert.dart';
import '../controllers/customer_controller.dart';

class VouchersScreen extends StatefulWidget {
  final bool isSelectMode;
  final double? orderSubtotal;

  const VouchersScreen({
    super.key,
    this.isSelectMode = false,
    this.orderSubtotal,
  });

  @override
  State<VouchersScreen> createState() => _VouchersScreenState();
}

class _VouchersScreenState extends State<VouchersScreen> {
  final _voucherCodeController = TextEditingController();
  String _selectedCategory = 'all'; // 'all', 'discount', 'shipping', 'cashback'
  bool _isValidating = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchCoupons();
    });
  }

  @override
  void dispose() {
    _voucherCodeController.dispose();
    super.dispose();
  }

  Future<void> _checkAndApplyManualVoucher() async {
    final code = _voucherCodeController.text.trim();
    if (code.isEmpty) {
      AppAlert.showWarning(
        context,
        title: 'Kode Voucher Kosong',
        message: 'Masukkan kode voucher terlebih dahulu.',
      );
      return;
    }

    setState(() => _isValidating = true);
    final ctrl = context.read<CustomerController>();
    final res = await ctrl.validateCoupon(code, widget.orderSubtotal ?? 30000.0);
    setState(() => _isValidating = false);

    if (!mounted) return;

    if (res['success'] == true && res['data'] != null) {
      if (widget.isSelectMode) {
        Navigator.pop(context, code);
      } else {
        Clipboard.setData(ClipboardData(text: code));
        AppAlert.showSuccess(
          context,
          title: 'Voucher Valid! 🎉',
          message: 'Kode "${code.toUpperCase()}" berhasil disalin ke papan klip.',
        );
      }
    } else {
      AppAlert.showError(
        context,
        title: 'Voucher Tidak Valid',
        message: res['message'] ?? 'Kode voucher salah atau syarat belum terpenuhi.',
      );
    }
  }

  void _useCoupon(String code) {
    if (widget.isSelectMode) {
      Navigator.pop(context, code);
    } else {
      Clipboard.setData(ClipboardData(text: code));
      AppAlert.showSuccess(
        context,
        title: 'Kode Voucher Disalin! 📋',
        message: 'Kode "$code" berhasil disalin. Tempelkan saat checkout pesanan.',
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<CustomerController>();
    final rawCoupons = ctrl.coupons;

    // Filter coupons by category
    final coupons = rawCoupons.where((c) {
      if (_selectedCategory == 'all') return true;
      final code = (c['code'] ?? '').toString().toUpperCase();
      final title = (c['title'] ?? '').toString().toLowerCase();
      final type = (c['discount_type'] ?? '').toString().toLowerCase();

      if (_selectedCategory == 'shipping') {
        return code.contains('SHIP') || code.contains('ONGKIR') || title.contains('ongkir');
      } else if (_selectedCategory == 'cashback') {
        return code.contains('CASH') || title.contains('cashback') || title.contains('cicalengkapay');
      } else if (_selectedCategory == 'discount') {
        return type == 'amount' || type == 'percent' || title.contains('diskon') || title.contains('hemat');
      }
      return true;
    }).toList();

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0.5,
        foregroundColor: const Color(0xFF0F172A),
        title: Text(
          widget.isSelectMode ? 'Pilih Voucher Promo' : 'Voucher & Promo CicalengkaGO',
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: AppTheme.primaryRed),
            onPressed: () => ctrl.fetchCoupons(),
            tooltip: 'Segarkan Voucher',
          ),
        ],
      ),
      body: CustomScrollView(
        slivers: [
          // 1. Promo Hero Banner
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFFE11D48), Color(0xFF9F1239)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: AppTheme.primaryRed.withOpacity(0.3),
                      blurRadius: 15,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.2),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Text(
                              'PROMO SPESIAL HARI INI',
                              style: TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.w900, letterSpacing: 0.5),
                            ),
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            'Makin Hemat Kulineran di CicalengkaGO! 🎉',
                            style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w900, height: 1.2),
                          ),
                          const SizedBox(height: 6),
                          const Text(
                            'Gunakan kode voucher di bawah ini untuk dapat diskon & gratis ongkir pengantaran.',
                            style: TextStyle(color: Colors.white70, fontSize: 11),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.15),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.confirmation_number_rounded, color: Colors.white, size: 36),
                    ),
                  ],
                ),
              ),
            ),
          ),

          // 2. Manual Voucher Code Input Box
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                  boxShadow: [
                    BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 8, offset: const Offset(0, 2)),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.discount_rounded, color: AppTheme.primaryRed, size: 18),
                        SizedBox(width: 8),
                        Text(
                          'Punya Kode Promo Khusus?',
                          style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Expanded(
                          child: SizedBox(
                            height: 44,
                            child: TextField(
                              controller: _voucherCodeController,
                              textCapitalization: TextCapitalization.characters,
                              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, letterSpacing: 0.5),
                              decoration: InputDecoration(
                                hintText: 'Masukkan kode voucher',
                                hintStyle: const TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8), fontWeight: FontWeight.normal),
                                filled: true,
                                fillColor: const Color(0xFFF8FAFC),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                                enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                                focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppTheme.primaryRed)),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        SizedBox(
                          height: 44,
                          child: ElevatedButton(
                            onPressed: _isValidating ? null : _checkAndApplyManualVoucher,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppTheme.primaryRed,
                              foregroundColor: Colors.white,
                              elevation: 0,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              padding: const EdgeInsets.symmetric(horizontal: 16),
                            ),
                            child: _isValidating
                                ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                : Text(widget.isSelectMode ? 'Pilih' : 'Terapkan', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),

          const SliverToBoxAdapter(child: SizedBox(height: 16)),

          // 3. Category Filter Chips
          SliverToBoxAdapter(
            child: SizedBox(
              height: 38,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                children: [
                  _filterChip('all', 'Semua Promo', Icons.local_offer_rounded),
                  const SizedBox(width: 8),
                  _filterChip('discount', 'Diskon Makanan', Icons.fastfood_rounded),
                  const SizedBox(width: 8),
                  _filterChip('shipping', 'Gratis Ongkir', Icons.delivery_dining_rounded),
                  const SizedBox(width: 8),
                  _filterChip('cashback', 'Cashback Saldo', Icons.account_balance_wallet_rounded),
                ],
              ),
            ),
          ),

          const SliverToBoxAdapter(child: SizedBox(height: 16)),

          // 4. Voucher Tickets List
          if (coupons.isEmpty)
            SliverToBoxAdapter(
              child: Container(
                margin: const EdgeInsets.all(16),
                padding: const EdgeInsets.all(40),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: const Column(
                  children: [
                    Icon(Icons.confirmation_number_outlined, size: 48, color: Color(0xFF94A3B8)),
                    SizedBox(height: 12),
                    Text('Belum Ada Voucher di Kategori Ini', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A))),
                    SizedBox(height: 4),
                    Text('Nantikan promo menarik lainnya dari merchant CicalengkaGO!', style: TextStyle(fontSize: 11, color: Color(0xFF64748B)), textAlign: TextAlign.center),
                  ],
                ),
              ),
            )
          else
            SliverPadding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              sliver: SliverList(
                delegate: SliverChildBuilderDelegate(
                  (context, index) {
                    final item = coupons[index] is Map ? (coupons[index] as Map) : {};
                    return _buildVoucherTicket(item);
                  },
                  childCount: coupons.length,
                ),
              ),
            ),

          const SliverPadding(padding: EdgeInsets.only(bottom: 40)),
        ],
      ),
    );
  }

  Widget _filterChip(String key, String label, IconData icon) {
    final isSelected = _selectedCategory == key;
    return InkWell(
      onTap: () => setState(() => _selectedCategory = key),
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? AppTheme.primaryRed : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: isSelected ? AppTheme.primaryRed : const Color(0xFFE2E8F0)),
          boxShadow: isSelected
              ? [BoxShadow(color: AppTheme.primaryRed.withOpacity(0.2), blurRadius: 6, offset: const Offset(0, 2))]
              : null,
        ),
        child: Row(
          children: [
            Icon(icon, size: 14, color: isSelected ? Colors.white : const Color(0xFF64748B)),
            const SizedBox(width: 6),
            Text(
              label,
              style: TextStyle(
                fontSize: 11.5,
                fontWeight: FontWeight.bold,
                color: isSelected ? Colors.white : const Color(0xFF334155),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildVoucherTicket(Map<dynamic, dynamic> item) {
    final code = (item['code'] ?? 'PROMO').toString().toUpperCase();
    final title = (item['title'] ?? 'Voucher Promo CicalengkaGO').toString();
    final discountType = (item['discount_type'] ?? 'amount').toString();
    final discountVal = double.tryParse(item['discount']?.toString() ?? '0') ?? 0.0;
    final minPurchase = double.tryParse(item['min_purchase']?.toString() ?? '0') ?? 0.0;
    final expireDate = item['expire_date']?.toString() ?? '-';

    String discountBadge = discountType == 'percent'
        ? '${discountVal.toInt()}%'
        : CurrencyFormatter.formatRupiah(discountVal);

    bool isShipping = code.contains('SHIP') || code.contains('ONGKIR');
    Color themeColor = isShipping ? const Color(0xFF059669) : AppTheme.primaryRed;

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        children: [
          // Ticket Main Section
          Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Left Icon / Badge
                Container(
                  width: 56,
                  height: 56,
                  decoration: BoxDecoration(
                    color: themeColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: themeColor.withOpacity(0.2)),
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(isShipping ? Icons.delivery_dining_rounded : Icons.confirmation_number_rounded, color: themeColor, size: 22),
                      const SizedBox(height: 2),
                      Text(
                        discountBadge,
                        style: TextStyle(color: themeColor, fontSize: 9.5, fontWeight: FontWeight.w900),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),

                // Center Information
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF1F5F9),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              code,
                              style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 11, color: Color(0xFF0F172A), letterSpacing: 0.5),
                            ),
                          ),
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                            decoration: BoxDecoration(
                              color: const Color(0xFFDCFCE7),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: const Text('AKTIF', style: TextStyle(color: Color(0xFF15803D), fontSize: 8.5, fontWeight: FontWeight.bold)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 5),
                      Text(
                        title,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A), height: 1.2),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        minPurchase > 0
                            ? 'Min. belanja ${CurrencyFormatter.formatRupiah(minPurchase)}'
                            : 'Tanpa minimum pembelian',
                        style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Dashed Divider Line with Ticket Cutouts
          Stack(
            alignment: Alignment.center,
            children: [
              const Divider(height: 1, color: Color(0xFFF1F5F9)),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Container(
                    width: 10,
                    height: 16,
                    decoration: const BoxDecoration(
                      color: Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.horizontal(right: Radius.circular(10)),
                    ),
                  ),
                  Container(
                    width: 10,
                    height: 16,
                    decoration: const BoxDecoration(
                      color: Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.horizontal(left: Radius.circular(10)),
                    ),
                  ),
                ],
              ),
            ],
          ),

          // Ticket Footer Actions
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(Icons.access_time_rounded, size: 12, color: Color(0xFF94A3B8)),
                    const SizedBox(width: 4),
                    Text(
                      'Berlaku s.d $expireDate',
                      style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                    ),
                  ],
                ),
                InkWell(
                  onTap: () => _useCoupon(code),
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                    decoration: BoxDecoration(
                      color: themeColor,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      widget.isSelectMode ? 'Pakai Voucher' : 'Salin Kode',
                      style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
