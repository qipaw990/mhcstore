import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/barcode_scanner_modal.dart';
import '../../../core/services/receipt_printer_service.dart';
import '../controllers/merchant_controller.dart';

class MerchantPosScreen extends StatefulWidget {
  const MerchantPosScreen({super.key});

  @override
  State<MerchantPosScreen> createState() => _MerchantPosScreenState();
}

class _MerchantPosScreenState extends State<MerchantPosScreen> {
  final TextEditingController _searchCtrl = TextEditingController();
  final TextEditingController _customerNameCtrl = TextEditingController(text: 'Pelanggan Langsung');
  final TextEditingController _notesCtrl = TextEditingController();
  final TextEditingController _discountCtrl = TextEditingController(text: '0');
  final TextEditingController _cashGivenCtrl = TextEditingController();

  String _searchQuery = '';
  String _paymentMethod = 'cash'; // cash, qris, transfer

  // POS Cart State: Map of cartKey -> { ... }
  final Map<String, Map<String, dynamic>> _cart = {};

  bool _isProcessing = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MerchantController>().fetchProducts();
    });
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _customerNameCtrl.dispose();
    _notesCtrl.dispose();
    _discountCtrl.dispose();
    _cashGivenCtrl.dispose();
    super.dispose();
  }

  void _addToCartDirect(Map<String, dynamic> product, {
    int? variationId,
    String? variationName,
    double? variationPrice,
    List<Map<String, dynamic>>? selectedAddons,
    int quantity = 1,
    String notes = '',
  }) {
    final id = int.tryParse(product['id']?.toString() ?? '0') ?? 0;
    if (id <= 0) return;

    final basePrice = double.tryParse(product['price']?.toString() ?? '0') ?? 0.0;
    final effVarPrice = (variationPrice != null && variationPrice > 0) ? variationPrice : basePrice;

    double addonsTotal = 0.0;
    List<String> addonNames = [];
    if (selectedAddons != null && selectedAddons.isNotEmpty) {
      for (final ad in selectedAddons) {
        addonsTotal += (double.tryParse(ad['price']?.toString() ?? '0') ?? 0.0);
        addonNames.add(ad['name'].toString());
      }
    }

    final unitPrice = effVarPrice + addonsTotal;
    final addonsHash = selectedAddons != null ? selectedAddons.map((a) => a['id']).join('_') : '';
    final cartKey = '${id}_v${variationId ?? 0}_a$addonsHash';

    setState(() {
      if (_cart.containsKey(cartKey)) {
        _cart[cartKey]!['qty'] = (_cart[cartKey]!['qty'] as int) + quantity;
        if (notes.isNotEmpty) {
          _cart[cartKey]!['notes'] = notes;
        }
      } else {
        _cart[cartKey] = {
          'cart_key': cartKey,
          'product_id': id,
          'product': product,
          'product_name': product['name'] ?? 'Menu',
          'qty': quantity,
          'price': unitPrice,
          'base_price': basePrice,
          'variation_id': variationId,
          'variation_name': variationName,
          'addons': selectedAddons ?? [],
          'addons_text': addonNames.join(', '),
          'notes': notes,
        };
      }
    });

    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('"${product['name']}" ditambahkan ke keranjang POS'),
        duration: const Duration(milliseconds: 900),
        backgroundColor: const Color(0xFF1E293B),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _addToCart(Map<String, dynamic> product) {
    final rawVars = product['variations'];
    final bool hasVars = (rawVars is List && rawVars.isNotEmpty);
    final rawAddons = product['addons'];
    final bool hasAddons = (rawAddons is List && rawAddons.isNotEmpty);

    if (hasVars || hasAddons) {
      _showPosCustomizationModal(product);
    } else {
      _addToCartDirect(product);
    }
  }

  void _showPosCustomizationModal(Map<String, dynamic> product) {
    final rawVars = product['variations'];
    final List<dynamic> variations = (rawVars is List) ? rawVars : [];
    final rawAddons = product['addons'];
    final List<dynamic> addons = (rawAddons is List) ? rawAddons : [];

    int? selectedVariationId;
    String? selectedVariationName;
    double? selectedVariationPrice;

    if (variations.isNotEmpty) {
      final firstVar = variations.first as Map<String, dynamic>;
      selectedVariationId = int.tryParse(firstVar['id']?.toString() ?? '0');
      selectedVariationName = firstVar['name']?.toString() ?? '';
      selectedVariationPrice = double.tryParse(firstVar['price']?.toString() ?? '0') ?? 0.0;
    }

    final Set<int> selectedAddonIds = {};
    final TextEditingController itemNotesCtrl = TextEditingController();
    int qty = 1;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) {
          final basePrice = double.tryParse(product['price']?.toString() ?? '0') ?? 0.0;
          final currentVarPrice = (selectedVariationPrice != null && selectedVariationPrice! > 0)
              ? selectedVariationPrice!
              : basePrice;

          double addonsSum = 0.0;
          final List<Map<String, dynamic>> chosenAddons = [];
          for (var ad in addons) {
            if (ad is Map<String, dynamic>) {
              final adId = int.tryParse(ad['id']?.toString() ?? '0') ?? 0;
              if (selectedAddonIds.contains(adId)) {
                final adPrice = double.tryParse(ad['price']?.toString() ?? '0') ?? 0.0;
                addonsSum += adPrice;
                chosenAddons.add(ad);
              }
            }
          }

          final singleUnitPrice = currentVarPrice + addonsSum;
          final totalCustomPrice = singleUnitPrice * qty;

          return Container(
            constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.88),
            padding: EdgeInsets.only(
              left: 20,
              right: 20,
              top: 20,
              bottom: MediaQuery.of(context).viewInsets.bottom + 20,
            ),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 36,
                    height: 4,
                    decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(10)),
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            product['name']?.toString() ?? 'Menu Kasir',
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          Text(
                            'Harga Dasar: ${CurrencyFormatter.formatRupiah(basePrice)}',
                            style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                          ),
                        ],
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF64748B)),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const Divider(height: 16),

                Expanded(
                  child: ListView(
                    children: [
                      // ── PILIHAN VARIASI (UKURAN / LEVEL) ──
                      if (variations.isNotEmpty) ...[
                        Row(
                          children: const [
                            Icon(Icons.tune_rounded, size: 16, color: AppTheme.primaryRed),
                            SizedBox(width: 6),
                            Text(
                              'Pilihan Variasi / Ukuran / Level',
                              style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: variations.map((v) {
                            final vMap = v as Map<String, dynamic>;
                            final vId = int.tryParse(vMap['id']?.toString() ?? '0') ?? 0;
                            final vName = vMap['name']?.toString() ?? '';
                            final vPrice = double.tryParse(vMap['price']?.toString() ?? '0') ?? 0.0;
                            final isSelected = (selectedVariationId == vId);

                            return InkWell(
                              onTap: () {
                                setModalState(() {
                                  selectedVariationId = vId;
                                  selectedVariationName = vName;
                                  selectedVariationPrice = vPrice;
                                });
                              },
                              borderRadius: BorderRadius.circular(10),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                decoration: BoxDecoration(
                                  color: isSelected ? const Color(0xFFFEF2F2) : const Color(0xFFF8FAFC),
                                  borderRadius: BorderRadius.circular(10),
                                  border: Border.all(
                                    color: isSelected ? AppTheme.primaryRed : const Color(0xFFE2E8F0),
                                    width: isSelected ? 1.5 : 1,
                                  ),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      isSelected ? Icons.radio_button_checked : Icons.radio_button_off,
                                      size: 16,
                                      color: isSelected ? AppTheme.primaryRed : const Color(0xFF94A3B8),
                                    ),
                                    const SizedBox(width: 6),
                                    Text(
                                      vName,
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                                        color: isSelected ? AppTheme.primaryRed : const Color(0xFF334155),
                                      ),
                                    ),
                                    if (vPrice > 0) ...[
                                      const SizedBox(width: 4),
                                      Text(
                                        '(${CurrencyFormatter.formatRupiah(vPrice)})',
                                        style: TextStyle(
                                          fontSize: 11,
                                          fontWeight: FontWeight.w600,
                                          color: isSelected ? AppTheme.primaryRed : const Color(0xFF64748B),
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                            );
                          }).toList(),
                        ),
                        const SizedBox(height: 16),
                      ],

                      // ── PILIHAN TOPPING & TAMBAHAN ──
                      if (addons.isNotEmpty) ...[
                        Row(
                          children: const [
                            Icon(Icons.add_task_rounded, size: 16, color: Color(0xFF16A34A)),
                            SizedBox(width: 6),
                            Text(
                              'Topping & Tambahan Lezat',
                              style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        ...addons.map((ad) {
                          final adMap = ad as Map<String, dynamic>;
                          final adId = int.tryParse(adMap['id']?.toString() ?? '0') ?? 0;
                          final adName = adMap['name']?.toString() ?? '';
                          final adPrice = double.tryParse(adMap['price']?.toString() ?? '0') ?? 0.0;
                          final isChecked = selectedAddonIds.contains(adId);

                          return InkWell(
                            onTap: () {
                              setModalState(() {
                                if (isChecked) {
                                  selectedAddonIds.remove(adId);
                                } else {
                                  selectedAddonIds.add(adId);
                                }
                              });
                            },
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              margin: const EdgeInsets.only(bottom: 6),
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                              decoration: BoxDecoration(
                                color: isChecked ? const Color(0xFFF0FDF4) : const Color(0xFFF8FAFC),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: isChecked ? const Color(0xFF16A34A) : const Color(0xFFE2E8F0),
                                  width: isChecked ? 1.5 : 1,
                                ),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Icon(
                                        isChecked ? Icons.check_box_rounded : Icons.check_box_outline_blank_rounded,
                                        size: 18,
                                        color: isChecked ? const Color(0xFF16A34A) : const Color(0xFF94A3B8),
                                      ),
                                      const SizedBox(width: 8),
                                      Text(
                                        adName,
                                        style: TextStyle(
                                          fontSize: 12,
                                          fontWeight: isChecked ? FontWeight.bold : FontWeight.w500,
                                          color: isChecked ? const Color(0xFF15803D) : const Color(0xFF334155),
                                        ),
                                      ),
                                    ],
                                  ),
                                  Text(
                                    '+${CurrencyFormatter.formatRupiah(adPrice)}',
                                    style: TextStyle(
                                      fontSize: 11.5,
                                      fontWeight: FontWeight.bold,
                                      color: isChecked ? const Color(0xFF15803D) : const Color(0xFF64748B),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        }),
                        const SizedBox(height: 14),
                      ],

                      // ── CATATAN ITEM ──
                      const Text('Catatan Pesanan (Opsional)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                      const SizedBox(height: 5),
                      TextField(
                        controller: itemNotesCtrl,
                        decoration: InputDecoration(
                          hintText: 'Contoh: Jangan terlalu pedas / pisah sambal...',
                          hintStyle: const TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8)),
                          filled: true,
                          fillColor: const Color(0xFFF8FAFC),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        ),
                      ),
                      const SizedBox(height: 14),

                      // ── JUMLAH QTY ──
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Jumlah Porsi', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                          Row(
                            children: [
                              InkWell(
                                onTap: () {
                                  if (qty > 1) {
                                    setModalState(() => qty--);
                                  }
                                },
                                borderRadius: BorderRadius.circular(8),
                                child: Container(
                                  padding: const EdgeInsets.all(6),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF1F5F9),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: const Icon(Icons.remove_rounded, size: 18, color: Color(0xFF0F172A)),
                                ),
                              ),
                              Padding(
                                padding: const EdgeInsets.symmetric(horizontal: 14),
                                child: Text(
                                  '$qty',
                                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                                ),
                              ),
                              InkWell(
                                onTap: () {
                                  setModalState(() => qty++);
                                },
                                borderRadius: BorderRadius.circular(8),
                                child: Container(
                                  padding: const EdgeInsets.all(6),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF1F5F9),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: const Icon(Icons.add_rounded, size: 18, color: Color(0xFF0F172A)),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),

                // Tombol Simpan ke Kasir
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primaryRed,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      elevation: 2,
                    ),
                    onPressed: () {
                      Navigator.pop(ctx);
                      _addToCartDirect(
                        product,
                        variationId: selectedVariationId,
                        variationName: selectedVariationName,
                        variationPrice: selectedVariationPrice,
                        selectedAddons: chosenAddons,
                        quantity: qty,
                        notes: itemNotesCtrl.text.trim(),
                      );
                    },
                    child: Text(
                      'Tambah ke Kasir (${CurrencyFormatter.formatRupiah(totalCustomPrice)})',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  void _removeFromCart(String cartKey) {
    setState(() {
      if (_cart.containsKey(cartKey)) {
        final currentQty = _cart[cartKey]!['qty'] as int;
        if (currentQty > 1) {
          _cart[cartKey]!['qty'] = currentQty - 1;
        } else {
          _cart.remove(cartKey);
        }
      }
    });
  }

  void _deleteCartItem(String cartKey) {
    setState(() {
      _cart.remove(cartKey);
    });
  }

  void _clearCart() {
    if (_cart.isEmpty) return;
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Kosongkan Keranjang?', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        content: const Text('Apakah Anda ingin menghapus semua item dari kasir saat ini?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal', style: TextStyle(color: Color(0xFF64748B))),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primaryRed, foregroundColor: Colors.white),
            onPressed: () {
              Navigator.pop(ctx);
              setState(() => _cart.clear());
            },
            child: const Text('Kosongkan'),
          ),
        ],
      ),
    );
  }

  double get _subtotal {
    double sum = 0.0;
    for (var item in _cart.values) {
      final price = (item['price'] != null)
          ? (double.tryParse(item['price'].toString()) ?? 0.0)
          : (double.tryParse((item['product'] as Map<String, dynamic>)['price']?.toString() ?? '0') ?? 0.0);
      final qty = item['qty'] as int;
      sum += (price * qty);
    }
    return sum;
  }

  double get _discount {
    return double.tryParse(_discountCtrl.text.trim()) ?? 0.0;
  }

  double get _grandTotal {
    final t = _subtotal - _discount;
    return t < 0 ? 0.0 : t;
  }

  int get _totalItemCount {
    int count = 0;
    for (var item in _cart.values) {
      count += (item['qty'] as int);
    }
    return count;
  }

  Future<void> _showBarcodeScannerModal() async {
    final scannedCode = await BarcodeScannerModal.scan(
      context,
      title: 'Scan Barcode Produk Kasir',
    );
    if (scannedCode != null && scannedCode.trim().isNotEmpty) {
      _processBarcodeScan(scannedCode.trim());
    }
  }

  void _processBarcodeScan(String barcode) {
    if (barcode.isEmpty) return;

    final allProducts = context.read<MerchantController>().products;
    Map<String, dynamic>? matched;

    for (var p in allProducts) {
      final pMap = p as Map<String, dynamic>;
      final pBarcode = pMap['barcode']?.toString().trim() ?? '';
      final pId = pMap['id']?.toString() ?? '';

      if (pBarcode.isNotEmpty && pBarcode.toLowerCase() == barcode.toLowerCase()) {
        matched = pMap;
        break;
      } else if (pId == barcode) {
        matched = pMap;
        break;
      }
    }

    if (matched != null) {
      _addToCart(matched);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Produk dengan barcode "$barcode" tidak ditemukan.'),
          backgroundColor: const Color(0xFFDC2626),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  void _showCheckoutBottomSheet() {
    if (_cart.isEmpty) return;

    _cashGivenCtrl.text = _grandTotal.toInt().toString();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setSheetState) {
          final cashGiven = double.tryParse(_cashGivenCtrl.text.trim()) ?? 0.0;
          final changeAmount = (_paymentMethod == 'cash' && cashGiven >= _grandTotal) ? (cashGiven - _grandTotal) : 0.0;

          return Container(
            constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.90),
            padding: EdgeInsets.only(
              left: 20,
              right: 20,
              top: 20,
              bottom: MediaQuery.of(context).viewInsets.bottom + 20,
            ),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
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
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Checkout Kasir POS',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF64748B)),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const Divider(height: 1, color: Color(0xFFF1F5F9)),
                const SizedBox(height: 12),

                Expanded(
                  child: ListView(
                    children: [
                      // Ringkasan Pesanan
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Item Belanja ($_totalItemCount Item)',
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF475569)),
                            ),
                            const SizedBox(height: 8),
                            ..._cart.values.map((it) {
                              final p = it['product'] as Map<String, dynamic>;
                              final cartKey = it['cart_key']?.toString() ?? '${p['id']}';
                              final qty = it['qty'] as int;
                              final price = (it['price'] != null)
                                  ? (double.tryParse(it['price'].toString()) ?? 0.0)
                                  : (double.tryParse(p['price']?.toString() ?? '0') ?? 0.0);
                              return Container(
                                margin: const EdgeInsets.only(bottom: 8),
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(10),
                                  border: Border.all(color: const Color(0xFFE2E8F0)),
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        Expanded(
                                          child: Text(
                                            p['name']?.toString() ?? 'Menu',
                                            style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        Text(
                                          CurrencyFormatter.formatRupiah(price * qty),
                                          style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                                        ),
                                      ],
                                    ),
                                    if (it['variation_name'] != null && it['variation_name'].toString().isNotEmpty) ...[
                                      const SizedBox(height: 2),
                                      Text(
                                        '• Varian: ${it['variation_name']}',
                                        style: const TextStyle(fontSize: 10.5, color: AppTheme.primaryRed, fontWeight: FontWeight.bold),
                                      ),
                                    ],
                                    if (it['addons_text'] != null && it['addons_text'].toString().isNotEmpty) ...[
                                      const SizedBox(height: 2),
                                      Text(
                                        '• Topping: ${it['addons_text']}',
                                        style: const TextStyle(fontSize: 10.5, color: Color(0xFF16A34A), fontWeight: FontWeight.bold),
                                      ),
                                    ],
                                    if (it['notes'] != null && it['notes'].toString().isNotEmpty) ...[
                                      const SizedBox(height: 2),
                                      Text(
                                        '• Catatan: "${it['notes']}"',
                                        style: const TextStyle(fontSize: 10, fontStyle: FontStyle.italic, color: Color(0xFF64748B)),
                                      ),
                                    ],
                                    const SizedBox(height: 6),
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        Text(
                                          '@ ${CurrencyFormatter.formatRupiah(price)}',
                                          style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                                        ),
                                        Row(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            InkWell(
                                              onTap: () {
                                                _removeFromCart(cartKey);
                                                setSheetState(() {});
                                              },
                                              borderRadius: BorderRadius.circular(6),
                                              child: Container(
                                                padding: const EdgeInsets.all(3),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFFFEE2E2),
                                                  borderRadius: BorderRadius.circular(6),
                                                ),
                                                child: const Icon(Icons.remove_rounded, size: 14, color: Color(0xFFDC2626)),
                                              ),
                                            ),
                                            Padding(
                                              padding: const EdgeInsets.symmetric(horizontal: 8),
                                              child: Text(
                                                '$qty',
                                                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                                              ),
                                            ),
                                            InkWell(
                                              onTap: () {
                                                _addToCart(p);
                                                setSheetState(() {});
                                              },
                                              borderRadius: BorderRadius.circular(6),
                                              child: Container(
                                                padding: const EdgeInsets.all(3),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFFDCFCE7),
                                                  borderRadius: BorderRadius.circular(6),
                                                ),
                                                child: const Icon(Icons.add_rounded, size: 14, color: Color(0xFF16A34A)),
                                              ),
                                            ),
                                            const SizedBox(width: 10),
                                            InkWell(
                                              onTap: () {
                                                _deleteCartItem(cartKey);
                                                setSheetState(() {});
                                              },
                                              borderRadius: BorderRadius.circular(6),
                                              child: const Padding(
                                                padding: EdgeInsets.all(2),
                                                child: Icon(Icons.delete_outline_rounded, size: 16, color: Color(0xFF94A3B8)),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              );
                            }),
                            const Divider(height: 16, color: Color(0xFFE2E8F0)),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                const Text('Total Pembayaran', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A))),
                                Text(
                                  CurrencyFormatter.formatRupiah(_grandTotal),
                                  style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16, color: AppTheme.primaryRed),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 14),

                      // Nama Pelanggan
                      const Text('Nama Pelanggan / No. Meja', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                      const SizedBox(height: 6),
                      TextField(
                        controller: _customerNameCtrl,
                        decoration: InputDecoration(
                          hintText: 'Contoh: Meja 5 / Ibu Ani',
                          filled: true,
                          fillColor: const Color(0xFFF8FAFC),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        ),
                      ),
                      const SizedBox(height: 14),

                      // Metode Pembayaran
                      const Text('Metode Pembayaran', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: _paymentTypeButton(
                              label: 'Tunai',
                              icon: Icons.payments_rounded,
                              isSelected: _paymentMethod == 'cash',
                              onTap: () {
                                setSheetState(() => _paymentMethod = 'cash');
                                setState(() => _paymentMethod = 'cash');
                              },
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _paymentTypeButton(
                              label: 'QRIS',
                              icon: Icons.qr_code_2_rounded,
                              isSelected: _paymentMethod == 'qris',
                              onTap: () {
                                setSheetState(() => _paymentMethod = 'qris');
                                setState(() => _paymentMethod = 'qris');
                              },
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _paymentTypeButton(
                              label: 'Transfer',
                              icon: Icons.account_balance_rounded,
                              isSelected: _paymentMethod == 'transfer',
                              onTap: () {
                                setSheetState(() => _paymentMethod = 'transfer');
                                setState(() => _paymentMethod = 'transfer');
                              },
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),

                      // Jika Pembayaran Tunai: Kalkulator Uang Diterima & Kembalian
                      if (_paymentMethod == 'cash') ...[
                        const Text('Uang Diterima (Rp)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                        const SizedBox(height: 6),
                        TextField(
                          controller: _cashGivenCtrl,
                          keyboardType: TextInputType.number,
                          onChanged: (_) => setSheetState(() {}),
                          decoration: InputDecoration(
                            hintText: 'Masukkan nominal uang tunai',
                            prefixIcon: Container(
                              width: 44,
                              alignment: Alignment.center,
                              child: const Text(
                                'Rp',
                                style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w900, color: Color(0xFF16A34A)),
                              ),
                            ),
                            filled: true,
                            fillColor: const Color(0xFFF8FAFC),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                          ),
                        ),
                        const SizedBox(height: 8),
                        // Quick Cash Buttons
                        Wrap(
                          spacing: 6,
                          runSpacing: 6,
                          children: [
                            _quickCashChip('Uang Pas', _grandTotal, setSheetState),
                            _quickCashChip('Rp 10.000', 10000, setSheetState),
                            _quickCashChip('Rp 20.000', 20000, setSheetState),
                            _quickCashChip('Rp 50.000', 50000, setSheetState),
                            _quickCashChip('Rp 100.000', 100000, setSheetState),
                          ],
                        ),
                        const SizedBox(height: 14),

                        // Kembalian Box
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: cashGiven >= _grandTotal ? const Color(0xFFDCFCE7) : const Color(0xFFFEE2E2),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: cashGiven >= _grandTotal ? const Color(0xFF86EFAC) : const Color(0xFFFCA5A5),
                            ),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                cashGiven >= _grandTotal ? 'Uang Kembalian:' : 'Uang Kurang:',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 13,
                                  color: cashGiven >= _grandTotal ? const Color(0xFF15803D) : const Color(0xFFB91C1C),
                                ),
                              ),
                              Text(
                                CurrencyFormatter.formatRupiah(cashGiven >= _grandTotal ? changeAmount : (_grandTotal - cashGiven)),
                                style: TextStyle(
                                  fontWeight: FontWeight.w900,
                                  fontSize: 15,
                                  color: cashGiven >= _grandTotal ? const Color(0xFF15803D) : const Color(0xFFB91C1C),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: 14),

                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF16A34A),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    onPressed: _isProcessing
                        ? null
                        : () async {
                            Navigator.pop(ctx);
                            await _executePosCheckout();
                          },
                    child: _isProcessing
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : Text(
                            'Selesaikan Pembayaran (${CurrencyFormatter.formatRupiah(_grandTotal)})',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                          ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _paymentTypeButton({
    required String label,
    required IconData icon,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isSelected ? const Color(0xFF2563EB) : const Color(0xFFE2E8F0),
            width: isSelected ? 1.5 : 1,
          ),
        ),
        child: Column(
          children: [
            Icon(icon, size: 18, color: isSelected ? const Color(0xFF2563EB) : const Color(0xFF64748B)),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                color: isSelected ? const Color(0xFF1E40AF) : const Color(0xFF475569),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _quickCashChip(String label, double amount, StateSetter setSheetState) {
    return InkWell(
      onTap: () {
        setSheetState(() {
          _cashGivenCtrl.text = amount.toInt().toString();
        });
      },
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: const Color(0xFFCBD5E1)),
        ),
        child: Text(label, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
      ),
    );
  }

  Future<void> _executePosCheckout() async {
    setState(() => _isProcessing = true);

    final itemsPayload = _cart.values.map((it) {
      final p = it['product'] as Map<String, dynamic>;
      final price = (it['price'] != null)
          ? double.tryParse(it['price'].toString()) ?? 0.0
          : double.tryParse(p['price']?.toString() ?? '0') ?? 0.0;
      final qty = it['qty'] as int;
      return {
        'product_id': p['id'],
        'name': p['name'],
        'quantity': qty,
        'price': price,
        'variation_id': it['variation_id'],
        'variation_name': it['variation_name'],
        'addons': it['addons'],
        'addons_text': it['addons_text'],
        'notes': it['notes'] ?? '',
      };
    }).toList();

    final cashGiven = double.tryParse(_cashGivenCtrl.text.trim()) ?? _grandTotal;

    final res = await context.read<MerchantController>().posCheckout(
          items: itemsPayload,
          paymentMethod: _paymentMethod,
          cashGiven: cashGiven,
          discountAmount: _discount,
          customerName: _customerNameCtrl.text.trim().isEmpty ? 'Pelanggan Langsung (POS)' : _customerNameCtrl.text.trim(),
          notes: _notesCtrl.text.trim(),
        );

    setState(() => _isProcessing = false);

    if (res['success'] == true && res['data'] != null) {
      final receiptData = res['data'] as Map<String, dynamic>;
      setState(() => _cart.clear());
      if (mounted) {
        _showReceiptDialog(receiptData);
      }
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message'] ?? 'Gagal memproses transaksi kasir.'),
            backgroundColor: const Color(0xFFDC2626),
          ),
        );
      }
    }
  }

  void _showReceiptDialog(Map<String, dynamic> receipt) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        contentPadding: const EdgeInsets.all(20),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: const BoxDecoration(
                  color: Color(0xFFDCFCE7),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.check_circle_rounded, color: Color(0xFF16A34A), size: 36),
              ),
              const SizedBox(height: 10),
              const Text(
                'Transaksi Berhasil!',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
              ),
              Text(
                'No. Struk: ${receipt['order_code'] ?? '-'}',
                style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B), fontFamily: 'monospace'),
              ),
              const SizedBox(height: 14),

              // Paper Receipt Body
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Center(
                      child: Text(
                        receipt['store_name']?.toString() ?? 'Toko Mitra',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Color(0xFF0F172A)),
                        textAlign: TextAlign.center,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Center(
                      child: Text(
                        receipt['created_at']?.toString() ?? '',
                        style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                      ),
                    ),
                    const Divider(height: 16, color: Color(0xFFCBD5E1)),

                    // Items List
                    ...((receipt['items'] as List<dynamic>?) ?? []).map((it) {
                      final name = it['product_name']?.toString() ?? 'Item';
                      final qty = it['quantity']?.toString() ?? '1';
                      final price = double.tryParse(it['price']?.toString() ?? '0') ?? 0.0;
                      final intQty = int.tryParse(qty) ?? 1;
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 4),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(child: Text('$name x$qty', style: const TextStyle(fontSize: 11, color: Color(0xFF334155)))),
                            Text(CurrencyFormatter.formatRupiah(price * intQty), style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      );
                    }),
                    const Divider(height: 16, color: Color(0xFFCBD5E1)),

                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Total:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                        Text(
                          CurrencyFormatter.formatRupiah(double.tryParse(receipt['total_amount']?.toString() ?? '0') ?? 0.0),
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A)),
                        ),
                      ],
                    ),
                    if ((receipt['payment_method']?.toString() ?? '') == 'cash') ...[
                      const SizedBox(height: 3),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Tunai Diterima:', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                          Text(CurrencyFormatter.formatRupiah(double.tryParse(receipt['cash_given']?.toString() ?? '0') ?? 0.0), style: const TextStyle(fontSize: 11)),
                        ],
                      ),
                      const SizedBox(height: 2),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Kembalian:', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF16A34A))),
                          Text(
                            CurrencyFormatter.formatRupiah(double.tryParse(receipt['change_amount']?.toString() ?? '0') ?? 0.0),
                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF16A34A)),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 14),

              // Action Buttons: Cetak Struk & Transaksi Baru
              Row(
                children: [
                  Expanded(
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF1E293B),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      icon: const Icon(Icons.print_rounded, size: 18, color: Color(0xFF60A5FA)),
                      label: const Text('Cetak Struk', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                      onPressed: () => ReceiptPrinterService.printReceipt(receipt),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF16A34A),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      icon: const Icon(Icons.share_rounded, size: 18),
                      label: const Text('WhatsApp', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                      onPressed: () => ReceiptPrinterService.shareViaWhatsApp(receipt),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),

              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppTheme.primaryRed,
                    side: const BorderSide(color: AppTheme.primaryRed),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    padding: const EdgeInsets.symmetric(vertical: 11),
                  ),
                  icon: const Icon(Icons.add_shopping_cart_rounded, size: 18),
                  label: const Text('Transaksi Baru', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  onPressed: () => Navigator.pop(ctx),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// Builds a gradient placeholder with the product's initial letter when no image is available
  Widget _buildProductImagePlaceholder(String name, double height) {
    final initial = name.isNotEmpty ? name[0].toUpperCase() : '?';
    // Generate a stable color based on product name
    final colorSeed = name.codeUnits.fold(0, (a, b) => a + b);
    final colors = [
      [const Color(0xFFEF4444), const Color(0xFFB91C1C)], // red
      [const Color(0xFFF59E0B), const Color(0xFFD97706)], // amber
      [const Color(0xFF10B981), const Color(0xFF059669)], // emerald
      [const Color(0xFF3B82F6), const Color(0xFF1D4ED8)], // blue
      [const Color(0xFF8B5CF6), const Color(0xFF6D28D9)], // purple
      [const Color(0xFFEC4899), const Color(0xFFBE185D)], // pink
      [const Color(0xFF06B6D4), const Color(0xFF0E7490)], // cyan
      [const Color(0xFFF97316), const Color(0xFFEA580C)], // orange
    ];
    final pair = colors[colorSeed % colors.length];
    return Container(
      height: height,
      width: double.infinity,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: pair,
        ),
      ),
      child: Center(
        child: Text(
          initial,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 32,
            fontWeight: FontWeight.w900,
            shadows: [Shadow(color: Colors.black26, blurRadius: 6, offset: Offset(1, 2))],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final merchantCtrl = context.watch<MerchantController>();
    final allProducts = merchantCtrl.products;

    final filteredProducts = allProducts.where((p) {
      final pMap = p as Map<String, dynamic>;
      final name = (pMap['name'] ?? '').toString().toLowerCase();
      final barcode = (pMap['barcode'] ?? '').toString().toLowerCase();
      final query = _searchQuery.toLowerCase();
      return name.contains(query) || barcode.contains(query);
    }).toList();

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0.5,
        title: const Text(
          'Kasir Toko (POS)',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.qr_code_scanner_rounded, color: AppTheme.primaryRed, size: 24),
            tooltip: 'Scan Barcode Produk',
            onPressed: _showBarcodeScannerModal,
          ),
          if (_cart.isNotEmpty)
            IconButton(
              icon: const Icon(Icons.delete_sweep_outlined, color: Color(0xFFDC2626), size: 22),
              tooltip: 'Kosongkan Keranjang',
              onPressed: _clearCart,
            ),
          const SizedBox(width: 4),
        ],
      ),
      body: Column(
        children: [
          // Search & Barcode Quick Action Bar
          Container(
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
            color: Colors.white,
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchCtrl,
                    onChanged: (val) => setState(() => _searchQuery = val),
                    decoration: InputDecoration(
                      hintText: 'Cari menu / scan barcode...',
                      hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                      prefixIcon: const Icon(Icons.search_rounded, size: 18, color: Color(0xFF64748B)),
                      suffixIcon: _searchQuery.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear_rounded, size: 16),
                              onPressed: () {
                                _searchCtrl.clear();
                                setState(() => _searchQuery = '');
                              },
                            )
                          : null,
                      filled: true,
                      fillColor: const Color(0xFFF1F5F9),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                InkWell(
                  onTap: _showBarcodeScannerModal,
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                    decoration: BoxDecoration(
                      color: const Color(0xFFEFF6FF),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFFBFDBFE)),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.qr_code_scanner_rounded, size: 16, color: Color(0xFF2563EB)),
                        SizedBox(width: 4),
                        Text('Scan', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF1E40AF))),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),

          // Product Grid
          Expanded(
            child: filteredProducts.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.inventory_2_outlined, size: 48, color: Color(0xFF94A3B8)),
                        const SizedBox(height: 10),
                        Text(
                          _searchQuery.isNotEmpty ? 'Tidak ada produk cocok dengan "$_searchQuery"' : 'Belum ada produk terdaftar',
                          style: const TextStyle(fontSize: 13, color: Color(0xFF64748B), fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  )
                : GridView.builder(
                    padding: const EdgeInsets.all(12),
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      childAspectRatio: 0.82,
                      crossAxisSpacing: 10,
                      mainAxisSpacing: 10,
                    ),
                    itemCount: filteredProducts.length,
                    itemBuilder: (context, index) {
                      final p = filteredProducts[index] as Map<String, dynamic>;
                      final id = int.tryParse(p['id']?.toString() ?? '0') ?? 0;
                      final name = p['name']?.toString() ?? 'Menu';
                      final rawImg = p['image']?.toString();
                      final imgUrl = ApiConstants.formatImageUrl(rawImg);
                      final price = double.tryParse(p['price']?.toString() ?? '0') ?? 0.0;
                      final barcode = p['barcode']?.toString().trim() ?? '';
                      final inCart = _cart.values.any((item) => item['product_id'] == id);
                      final inCartQty = _cart.values.where((item) => item['product_id'] == id).fold<int>(0, (sum, item) => sum + (item['qty'] as int));
                      final hasVars = (p['variations'] is List && (p['variations'] as List).isNotEmpty);
                      final hasAds = (p['addons'] is List && (p['addons'] as List).isNotEmpty);

                      return InkWell(
                        onTap: () => _addToCart(p),
                        borderRadius: BorderRadius.circular(14),
                        child: Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: inCart ? AppTheme.primaryRed : const Color(0xFFE2E8F0),
                              width: inCart ? 1.5 : 1,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.02),
                                blurRadius: 6,
                              ),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Image + InCart Badge
                              Stack(
                                children: [
                                  ClipRRect(
                                    borderRadius: const BorderRadius.vertical(top: Radius.circular(13)),
                                    child: imgUrl.isEmpty
                                        ? _buildProductImagePlaceholder(name, 100)
                                        : CachedNetworkImage(
                                            imageUrl: imgUrl,
                                            height: 100,
                                            width: double.infinity,
                                            fit: BoxFit.cover,
                                            placeholder: (context, url) => _buildProductImagePlaceholder(name, 100),
                                            errorWidget: (context, url, error) => _buildProductImagePlaceholder(name, 100),
                                          ),
                                  ),
                                  if (inCart)
                                    Positioned(
                                      top: 6,
                                      right: 6,
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                                        decoration: BoxDecoration(
                                          color: AppTheme.primaryRed,
                                          borderRadius: BorderRadius.circular(10),
                                        ),
                                        child: Text(
                                          '$inCartQty x',
                                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 11),
                                        ),
                                      ),
                                    ),
                                ],
                              ),
                              Padding(
                                padding: const EdgeInsets.all(8),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      name,
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A)),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      CurrencyFormatter.formatRupiah(price),
                                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 12.5, color: AppTheme.primaryRed),
                                    ),
                                    if (hasVars || hasAds) ...[
                                      const SizedBox(height: 3),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFFEF2F2),
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        child: const Text(
                                          'Ada Varian / Topping',
                                          style: TextStyle(fontSize: 8.5, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                                        ),
                                      ),
                                    ],
                                    if (barcode.isNotEmpty) ...[
                                      const SizedBox(height: 2),
                                      Row(
                                        children: [
                                          const Icon(Icons.qr_code_2_rounded, size: 11, color: Color(0xFF64748B)),
                                          const SizedBox(width: 3),
                                          Expanded(
                                            child: Text(
                                              barcode,
                                              style: const TextStyle(fontSize: 9.5, color: Color(0xFF64748B), fontFamily: 'monospace'),
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
          ),

          // Bottom POS Bar
          if (_cart.isNotEmpty)
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.08),
                    blurRadius: 10,
                    offset: const Offset(0, -3),
                  ),
                ],
              ),
              child: SafeArea(
                child: Row(
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          '$_totalItemCount Item di Keranjang',
                          style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          CurrencyFormatter.formatRupiah(_grandTotal),
                          style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                        ),
                      ],
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF16A34A),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        icon: const Icon(Icons.point_of_sale_rounded, size: 18),
                        label: const Text('Checkout POS', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                        onPressed: _showCheckoutBottomSheet,
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
}
