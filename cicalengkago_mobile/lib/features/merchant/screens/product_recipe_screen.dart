import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../controllers/merchant_controller.dart';

class ProductRecipeScreen extends StatefulWidget {
  final Map<String, dynamic> product;

  const ProductRecipeScreen({super.key, required this.product});

  @override
  State<ProductRecipeScreen> createState() => _ProductRecipeScreenState();
}

class _ProductRecipeScreenState extends State<ProductRecipeScreen> {
  /// Resep yang sedang diedit: [{raw_material_id, qty_used}]
  List<Map<String, dynamic>> _recipeItems = [];
  bool _saving = false;

  // ── Mode Kalkulator Margin & Harga Jual ─────────────────────────────────────
  bool _autoUpdatePrice = true;
  String _marginMode = 'percent'; // 'percent' | 'nominal' | 'manual'
  double _marginPercent = 50.0;
  double _marginNominal = 5000.0;
  double _customSellingPrice = 0.0;
  late TextEditingController _percentCtrl;
  late TextEditingController _nominalCtrl;
  late TextEditingController _customPriceCtrl;

  @override
  void initState() {
    super.initState();
    final initialPrice = double.tryParse(widget.product['price']?.toString() ?? '0') ?? 0.0;
    _customSellingPrice = initialPrice;
    _percentCtrl = TextEditingController(text: '50');
    _nominalCtrl = TextEditingController(text: '5000');
    _customPriceCtrl = TextEditingController(text: initialPrice > 0 ? initialPrice.toInt().toString() : '');

    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final ctrl = context.read<MerchantController>();
      if (ctrl.rawMaterials.isEmpty) await ctrl.fetchRawMaterials();
      await ctrl.fetchProductRecipe(int.parse(widget.product['id'].toString()));
      _loadFromController();
    });
  }

  @override
  void dispose() {
    _percentCtrl.dispose();
    _nominalCtrl.dispose();
    _customPriceCtrl.dispose();
    super.dispose();
  }

  void _loadFromController() {
    final data = context.read<MerchantController>().productRecipe;
    final recipe = (data['recipe'] as List?) ?? [];
    setState(() {
      _recipeItems = recipe
          .map((r) => {
                'raw_material_id': int.tryParse(r['raw_material_id']?.toString() ?? '0') ?? 0,
                'qty_used': double.tryParse(r['qty_used']?.toString() ?? '0') ?? 0.0,
              })
          .toList();
      _recalcPricing();
    });
  }

  double get _totalHpp {
    final ctrl = context.read<MerchantController>();
    final mats = ctrl.rawMaterials;
    double total = 0;
    for (final item in _recipeItems) {
      final id = item['raw_material_id'];
      final qty = (item['qty_used'] as double?) ?? 0;
      final mat = mats.firstWhere(
          (m) => m['id']?.toString() == id.toString(),
          orElse: () => {});
      final price = double.tryParse(mat['price_per_unit']?.toString() ?? '0') ?? 0;
      total += qty * price;
    }
    return total;
  }

  double get _calculatedSellingPrice {
    final hpp = _totalHpp;
    if (hpp <= 0) {
      return double.tryParse(widget.product['price']?.toString() ?? '0') ?? 0.0;
    }
    if (_marginMode == 'percent') {
      return hpp * (1 + (_marginPercent / 100));
    } else if (_marginMode == 'nominal') {
      return hpp + _marginNominal;
    } else {
      return _customSellingPrice > 0 ? _customSellingPrice : hpp;
    }
  }

  double get _calculatedProfit {
    return _calculatedSellingPrice - _totalHpp;
  }

  double get _calculatedMarginPct {
    if (_totalHpp <= 0) return 0.0;
    return (_calculatedProfit / _totalHpp) * 100;
  }

  void _recalcPricing() {
    if (_marginMode == 'manual') {
      if (_totalHpp > 0 && _customSellingPrice > 0) {
        _marginPercent = ((_customSellingPrice - _totalHpp) / _totalHpp) * 100;
        _marginNominal = _customSellingPrice - _totalHpp;
      }
    }
  }

  void _addIngredient() {
    final ctrl = context.read<MerchantController>();
    final mats = ctrl.rawMaterials;
    if (mats.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Tambahkan bahan baku terlebih dahulu di menu Bahan Baku'),
          backgroundColor: Color(0xFFD97706),
        ),
      );
      return;
    }

    // Filter out already added
    final addedIds = _recipeItems.map((r) => r['raw_material_id'].toString()).toSet();
    final available = mats.where((m) => !addedIds.contains(m['id']?.toString())).toList();

    if (available.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Semua bahan baku sudah ditambahkan ke resep ini')),
      );
      return;
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _AddIngredientSheet(
        availableMats: available,
        onAdd: (id, qty) async {
          setState(() {
            _recipeItems.add({'raw_material_id': id, 'qty_used': qty});
            _recalcPricing();
          });
          // Auto-save to server immediately
          await _saveRecipe(silent: true);
        },
      ),
    );
  }

  Future<void> _saveRecipe({bool silent = false}) async {
    if (!silent) setState(() => _saving = true);
    final productId = int.tryParse(widget.product['id'].toString()) ?? 0;
    final targetPrice = _autoUpdatePrice ? _calculatedSellingPrice : null;

    final res = await context.read<MerchantController>().saveProductRecipe(
      productId,
      _recipeItems,
      newPrice: targetPrice,
    );
    if (!silent) setState(() => _saving = false);

    if (mounted) {
      final hpp = (res['total_hpp'] as num?)?.toDouble() ?? 0;
      final newPrice = (res['price'] as num?)?.toDouble();
      String priceMsg = '';
      if (newPrice != null && newPrice > 0) {
        priceMsg = ' • Harga Jual: Rp ${_fmtPrice(newPrice)}';
      }

      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(res['success'] == true
            ? (silent
                ? '✅ Bahan & Resep berhasil disimpan otomatis! (HPP: Rp ${_fmtPrice(hpp)})'
                : '✅ ${res['message']} (HPP: Rp ${_fmtPrice(hpp)}$priceMsg)')
            : '❌ ${res['message']}'),
        backgroundColor: res['success'] == true ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
        duration: const Duration(seconds: 2),
      ));
    }
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<MerchantController>();
    final mats = ctrl.rawMaterials;
    final pName = widget.product['name']?.toString() ?? 'Produk';
    final currentMenuPrice = double.tryParse(widget.product['price']?.toString() ?? '0') ?? 0;
    final hpp = _totalHpp;
    final sellingPrice = _calculatedSellingPrice;
    final profit = _calculatedProfit;
    final marginPct = _calculatedMarginPct;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 18),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Resep & Hitung HPP', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A))),
            Text(pName, style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)), overflow: TextOverflow.ellipsis),
          ],
        ),
        actions: [
          if (!_saving)
            TextButton.icon(
              onPressed: _recipeItems.isEmpty ? null : _saveRecipe,
              icon: const Icon(Icons.save_alt_rounded, size: 16),
              label: const Text('Simpan'),
              style: TextButton.styleFrom(foregroundColor: const Color(0xFF0F172A)),
            ),
        ],
      ),
      body: ctrl.isRecipeLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.only(bottom: 100),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // ── 1. SUMMARY CARD (HPP, UNTUNG & HARGA JUAL) ──
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.fromLTRB(16, 16, 16, 12),
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [
                        BoxShadow(color: Colors.black.withValues(alpha: 0.12), blurRadius: 14, offset: const Offset(0, 4)),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('HPP (Total Biaya Bahan)', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 11, fontWeight: FontWeight.w500)),
                                const SizedBox(height: 3),
                                Text(
                                  'Rp ${_fmtPrice(hpp)}',
                                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 24),
                                ),
                              ],
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                              decoration: BoxDecoration(
                                color: const Color(0xFF334155),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.menu_book_rounded, color: Color(0xFF94A3B8), size: 14),
                                  const SizedBox(width: 5),
                                  Text('${_recipeItems.length} bahan', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text('Harga Jual Disarankan', style: TextStyle(color: Color(0xFFCBD5E1), fontSize: 11)),
                                  const SizedBox(height: 2),
                                  Text(
                                    'Rp ${_fmtPrice(sellingPrice)}',
                                    style: const TextStyle(color: Color(0xFF38BDF8), fontWeight: FontWeight.bold, fontSize: 17),
                                  ),
                                ],
                              ),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  const Text('Keuntungan Bersih', style: TextStyle(color: Color(0xFFCBD5E1), fontSize: 11)),
                                  const SizedBox(height: 2),
                                  Text(
                                    '+Rp ${_fmtPrice(profit)} (+${marginPct.toStringAsFixed(1)}%)',
                                    style: TextStyle(
                                      color: profit >= 0 ? const Color(0xFF4ADE80) : const Color(0xFFF87171),
                                      fontWeight: FontWeight.bold,
                                      fontSize: 13,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),

                  // ── 2. KALKULATOR TARGET KEUNTUNGAN & HARGA JUAL ──
                  if (hpp > 0)
                    Container(
                      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                        boxShadow: [
                          BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 8, offset: const Offset(0, 2)),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(6),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFFEF3C7),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(Icons.price_change_outlined, size: 16, color: Color(0xFFD97706)),
                              ),
                              const SizedBox(width: 8),
                              const Text(
                                'Target Keuntungan & Harga Jual',
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Color(0xFF0F172A)),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),

                          // Mode Selector Chips (Persentase % vs Nominal Rp vs Custom)
                          Row(
                            children: [
                              _buildModeChip('percent', 'Margin (%)'),
                              const SizedBox(width: 6),
                              _buildModeChip('nominal', 'Nominal (+Rp)'),
                              const SizedBox(width: 6),
                              _buildModeChip('manual', 'Ketik Harga'),
                            ],
                          ),
                          const SizedBox(height: 12),

                          // Input Section based on mode
                          if (_marginMode == 'percent') ...[
                            Row(
                              children: [
                                Expanded(
                                  child: TextField(
                                    controller: _percentCtrl,
                                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                    inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d.,]'))],
                                    decoration: InputDecoration(
                                      labelText: 'Margin Keuntungan',
                                      suffixText: '%',
                                      suffixStyle: const TextStyle(fontWeight: FontWeight.bold),
                                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                                      filled: true,
                                      fillColor: const Color(0xFFF8FAFC),
                                    ),
                                    onChanged: (v) {
                                      final val = double.tryParse(v.replaceAll(',', '.')) ?? 0;
                                      setState(() => _marginPercent = val);
                                    },
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            // Quick preset chips
                            Wrap(
                              spacing: 6,
                              runSpacing: 6,
                              children: [20.0, 30.0, 50.0, 75.0, 100.0].map((p) {
                                final isSelected = _marginPercent == p;
                                return InkWell(
                                  onTap: () {
                                    _percentCtrl.text = p.toInt().toString();
                                    setState(() => _marginPercent = p);
                                  },
                                  borderRadius: BorderRadius.circular(8),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                                    decoration: BoxDecoration(
                                      color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Text(
                                      '+${p.toInt()}%',
                                      style: TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold,
                                        color: isSelected ? Colors.white : const Color(0xFF475569),
                                      ),
                                    ),
                                  ),
                                );
                              }).toList(),
                            ),
                          ] else if (_marginMode == 'nominal') ...[
                            Row(
                              children: [
                                Expanded(
                                  child: TextField(
                                    controller: _nominalCtrl,
                                    keyboardType: TextInputType.number,
                                    inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                                    decoration: InputDecoration(
                                      labelText: 'Tambah Untung Nominal',
                                      prefixText: 'Rp ',
                                      prefixStyle: const TextStyle(fontWeight: FontWeight.bold),
                                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                                      filled: true,
                                      fillColor: const Color(0xFFF8FAFC),
                                    ),
                                    onChanged: (v) {
                                      final val = double.tryParse(v) ?? 0;
                                      setState(() => _marginNominal = val);
                                    },
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            // Quick preset nominal chips
                            Wrap(
                              spacing: 6,
                              runSpacing: 6,
                              children: [2000.0, 5000.0, 10000.0, 15000.0, 20000.0].map((n) {
                                final isSelected = _marginNominal == n;
                                return InkWell(
                                  onTap: () {
                                    _nominalCtrl.text = n.toInt().toString();
                                    setState(() => _marginNominal = n);
                                  },
                                  borderRadius: BorderRadius.circular(8),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                                    decoration: BoxDecoration(
                                      color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Text(
                                      '+Rp ${_fmtPrice(n)}',
                                      style: TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold,
                                        color: isSelected ? Colors.white : const Color(0xFF475569),
                                      ),
                                    ),
                                  ),
                                );
                              }).toList(),
                            ),
                          ] else ...[
                            TextField(
                              controller: _customPriceCtrl,
                              keyboardType: TextInputType.number,
                              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                              decoration: InputDecoration(
                                labelText: 'Ketik Langsung Harga Jual',
                                prefixText: 'Rp ',
                                prefixStyle: const TextStyle(fontWeight: FontWeight.bold),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                                filled: true,
                                fillColor: const Color(0xFFF8FAFC),
                              ),
                              onChanged: (v) {
                                final val = double.tryParse(v) ?? 0;
                                setState(() {
                                  _customSellingPrice = val;
                                  _recalcPricing();
                                });
                              },
                            ),
                          ],

                          const SizedBox(height: 12),
                          const Divider(height: 1, color: Color(0xFFF1F5F9)),
                          const SizedBox(height: 10),

                          // Switch Auto Update Selling Price
                          InkWell(
                            onTap: () => setState(() => _autoUpdatePrice = !_autoUpdatePrice),
                            borderRadius: BorderRadius.circular(10),
                            child: Row(
                              children: [
                                Checkbox(
                                  value: _autoUpdatePrice,
                                  activeColor: const Color(0xFF0F172A),
                                  onChanged: (v) => setState(() => _autoUpdatePrice = v ?? true),
                                ),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const Text(
                                        'Perbarui Harga Jual Menu Otomatis',
                                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A)),
                                      ),
                                      Text(
                                        'Harga di menu akan diset ke Rp ${_fmtPrice(sellingPrice)} (sebelumnya Rp ${_fmtPrice(currentMenuPrice)})',
                                        style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
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

                  // ── 3. SECTION DAFTAR BAHAN BAKU ──
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Komposisi Bahan Baku',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                        ),
                        TextButton.icon(
                          onPressed: _addIngredient,
                          icon: const Icon(Icons.add_circle_outline_rounded, size: 16, color: Color(0xFF2563EB)),
                          label: const Text('Tambah Bahan', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF2563EB))),
                        ),
                      ],
                    ),
                  ),

                  _recipeItems.isEmpty
                      ? _buildEmptyRecipe()
                      : ListView.separated(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          itemCount: _recipeItems.length,
                          separatorBuilder: (_, _) => const SizedBox(height: 8),
                          itemBuilder: (ctx, i) {
                            final item = _recipeItems[i];
                            final matId = item['raw_material_id'];
                            final mat = mats.firstWhere(
                                (m) => m['id']?.toString() == matId.toString(),
                                orElse: () => <String, dynamic>{});
                            final matName = mat['name']?.toString() ?? 'Bahan #$matId';
                            final unit = mat['unit']?.toString() ?? '';
                            final price = double.tryParse(mat['price_per_unit']?.toString() ?? '0') ?? 0;
                            final qty = (item['qty_used'] as double?) ?? 0;
                            final cost = qty * price;

                            return Container(
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
                                  // ── Row 1: Icon + Nama Bahan + Tombol Hapus
                                  Padding(
                                    padding: const EdgeInsets.fromLTRB(12, 12, 8, 6),
                                    child: Row(
                                      crossAxisAlignment: CrossAxisAlignment.center,
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.all(7),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFF0FDF4),
                                            borderRadius: BorderRadius.circular(10),
                                          ),
                                          child: const Icon(Icons.eco_outlined, color: Color(0xFF16A34A), size: 16),
                                        ),
                                        const SizedBox(width: 10),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                matName,
                                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                                                maxLines: 2,
                                                overflow: TextOverflow.ellipsis,
                                              ),
                                              Text(
                                                'Rp ${_fmtPrice(price)} / $unit',
                                                style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                                              ),
                                            ],
                                          ),
                                        ),
                                        InkWell(
                                          onTap: () async {
                                            setState(() {
                                              _recipeItems.removeAt(i);
                                              _recalcPricing();
                                            });
                                            await _saveRecipe(silent: true);
                                          },
                                          borderRadius: BorderRadius.circular(8),
                                          child: const Padding(
                                            padding: EdgeInsets.all(6),
                                            child: Icon(Icons.delete_outline_rounded, size: 18, color: Color(0xFFDC2626)),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),

                                  // ── Row 2: Input Qty + Total Biaya
                                  Container(
                                    padding: const EdgeInsets.fromLTRB(12, 6, 12, 12),
                                    decoration: const BoxDecoration(
                                      border: Border(top: BorderSide(color: Color(0xFFF1F5F9))),
                                    ),
                                    child: Row(
                                      children: [
                                        // Label jumlah
                                        const Text('Jumlah:', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                                        const SizedBox(width: 10),
                                        // Qty input
                                        SizedBox(
                                          width: 90,
                                          height: 36,
                                          child: TextFormField(
                                            initialValue: qty == qty.toInt() ? qty.toInt().toString() : qty.toStringAsFixed(2),
                                            keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                            inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d.,]'))],
                                            textAlign: TextAlign.center,
                                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                            decoration: InputDecoration(
                                              suffix: Text(unit, style: const TextStyle(fontSize: 9, color: Color(0xFF94A3B8))),
                                              contentPadding: const EdgeInsets.symmetric(horizontal: 6, vertical: 0),
                                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                                              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                                              focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFF0F172A), width: 1.5)),
                                              filled: true,
                                              fillColor: const Color(0xFFF8FAFC),
                                            ),
                                            onChanged: (v) {
                                              final parsed = double.tryParse(v.replaceAll(',', '.')) ?? 0;
                                              setState(() => _recipeItems[i]['qty_used'] = parsed);
                                            },
                                          ),
                                        ),
                                        const Spacer(),
                                        // Total biaya bahan ini
                                        Column(
                                          crossAxisAlignment: CrossAxisAlignment.end,
                                          children: [
                                            const Text('Biaya', style: TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
                                            Text(
                                              'Rp ${_fmtPrice(cost)}',
                                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF2563EB)),
                                            ),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                ],
              ),
            ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
        ),
        child: Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  side: const BorderSide(color: Color(0xFF0F172A)),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: _addIngredient,
                icon: const Icon(Icons.add_rounded, size: 18, color: Color(0xFF0F172A)),
                label: const Text(
                  'Tambah Bahan',
                  style: TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.bold, fontSize: 13),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF0F172A),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  elevation: 0,
                ),
                onPressed: (_saving || _recipeItems.isEmpty) ? null : _saveRecipe,
                icon: _saving
                    ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Icon(Icons.save_alt_rounded, size: 18, color: Colors.white),
                label: Text(
                  _saving ? 'Menyimpan...' : 'Simpan Resep',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildModeChip(String mode, String label) {
    final isSelected = _marginMode == mode;
    return Expanded(
      child: InkWell(
        onTap: () {
          setState(() {
            _marginMode = mode;
            _recalcPricing();
          });
        },
        borderRadius: BorderRadius.circular(10),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          padding: const EdgeInsets.symmetric(vertical: 8),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: isSelected ? Colors.white : const Color(0xFF64748B),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyRecipe() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 30),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.menu_book_outlined, size: 48, color: Color(0xFFCBD5E1)),
            const SizedBox(height: 10),
            const Text(
              'Belum Ada Bahan di Resep',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 4),
            const Text(
              'Tekan tombol "+ Tambah Bahan" di bawah untuk memulai.',
              style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
            ),
          ],
        ),
      ),
    );
  }

  String _fmtPrice(double v) {
    if (v >= 1000000) return '${(v / 1000000).toStringAsFixed(2)} Jt';
    if (v >= 1000) return '${(v / 1000).toStringAsFixed(0)} Rb';
    return v.toStringAsFixed(0);
  }
}

// ── Sheet: Pilih Bahan + Input Takaran ────────────────────────────────────
class _AddIngredientSheet extends StatefulWidget {
  final List<dynamic> availableMats;
  final void Function(int id, double qty) onAdd;

  const _AddIngredientSheet({required this.availableMats, required this.onAdd});

  @override
  State<_AddIngredientSheet> createState() => _AddIngredientSheetState();
}

class _AddIngredientSheetState extends State<_AddIngredientSheet> {
  Map<String, dynamic>? _selected;
  final TextEditingController _searchCtrl = TextEditingController();
  final TextEditingController _qtyCtrl = TextEditingController();
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    // Default select first item if only 1 available
    if (widget.availableMats.length == 1) {
      _selected = widget.availableMats.first as Map<String, dynamic>;
    }
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _qtyCtrl.dispose();
    super.dispose();
  }

  List<dynamic> get _filteredMats {
    if (_searchQuery.trim().isEmpty) return widget.availableMats;
    final q = _searchQuery.trim().toLowerCase();
    return widget.availableMats.where((m) {
      final name = m['name']?.toString().toLowerCase() ?? '';
      final unit = m['unit']?.toString().toLowerCase() ?? '';
      final desc = m['description']?.toString().toLowerCase() ?? '';
      return name.contains(q) || unit.contains(q) || desc.contains(q);
    }).toList();
  }

  String _fmtPrice(double v) {
    if (v == v.toInt()) return v.toInt().toString();
    return v.toStringAsFixed(0);
  }

  void _submitForm() {
    if (_selected == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pilih salah satu bahan baku di daftar terlebih dahulu')),
      );
      return;
    }
    final rawText = _qtyCtrl.text.trim().isEmpty ? '1' : _qtyCtrl.text.trim();
    final parsedQty = double.tryParse(rawText.replaceAll(',', '.')) ?? 1.0;
    if (parsedQty <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Masukkan jumlah takaran yang valid (> 0)')),
      );
      return;
    }
    final id = int.tryParse(_selected!['id'].toString()) ?? 0;
    if (id <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Data bahan tidak valid')),
      );
      return;
    }
    widget.onAdd(id, parsedQty);
    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    final unit = _selected?['unit']?.toString() ?? '';
    final filtered = _filteredMats;
    final pricePerUnit = double.tryParse(_selected?['price_per_unit']?.toString() ?? '0') ?? 0;
    final qty = double.tryParse(_qtyCtrl.text.replaceAll(',', '.')) ?? 0;
    final subtotal = qty * pricePerUnit;

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.of(context).size.height * 0.85,
        ),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Drag handle & Header (Pinned Top)
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 14, 20, 10),
              child: Column(
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: const Color(0xFFE2E8F0),
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Tambah Bahan ke Resep',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17, color: Color(0xFF0F172A)),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          '${widget.availableMats.length} bahan',
                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF64748B)),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const Divider(height: 1, color: Color(0xFFF1F5F9)),

            // Scrollable Content (Search + Materials + Takaran + Cost)
            Flexible(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Search Field
                    TextField(
                      controller: _searchCtrl,
                      onChanged: (v) => setState(() => _searchQuery = v),
                      decoration: InputDecoration(
                        hintText: 'Cari nama bahan (contoh: Susu, Gula, Kopi...)',
                        hintStyle: const TextStyle(fontSize: 12.5, color: Color(0xFF94A3B8)),
                        prefixIcon: const Icon(Icons.search_rounded, size: 20, color: Color(0xFF64748B)),
                        suffixIcon: _searchQuery.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear_rounded, size: 16, color: Color(0xFF94A3B8)),
                                onPressed: () {
                                  _searchCtrl.clear();
                                  setState(() => _searchQuery = '');
                                },
                              )
                            : null,
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF0F172A))),
                      ),
                    ),
                    const SizedBox(height: 12),

                    // Material List (Selectable Cards)
                    ConstrainedBox(
                      constraints: const BoxConstraints(maxHeight: 150),
                      child: filtered.isEmpty
                          ? Center(
                              child: Padding(
                                padding: const EdgeInsets.symmetric(vertical: 16),
                                child: Column(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const Icon(Icons.search_off_rounded, size: 30, color: Color(0xFF94A3B8)),
                                    const SizedBox(height: 4),
                                    Text(
                                      'Tidak ada bahan dengan kata kunci "$_searchQuery"',
                                      style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                                    ),
                                  ],
                                ),
                              ),
                            )
                          : ListView.separated(
                              shrinkWrap: true,
                              itemCount: filtered.length,
                              separatorBuilder: (_, _) => const SizedBox(height: 6),
                              itemBuilder: (ctx, i) {
                                final m = filtered[i] as Map<String, dynamic>;
                                final isSelected = _selected?['id']?.toString() == m['id']?.toString();
                                final matName = m['name']?.toString() ?? '';
                                final matUnit = m['unit']?.toString() ?? 'gr';
                                final matPrice = double.tryParse(m['price_per_unit']?.toString() ?? '0') ?? 0;

                                return InkWell(
                                  onTap: () {
                                    setState(() {
                                      _selected = m;
                                      if (_qtyCtrl.text.trim().isEmpty) {
                                        _qtyCtrl.text = '1';
                                      }
                                    });
                                  },
                                  borderRadius: BorderRadius.circular(12),
                                  child: AnimatedContainer(
                                    duration: const Duration(milliseconds: 180),
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                    decoration: BoxDecoration(
                                      color: isSelected ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
                                      borderRadius: BorderRadius.circular(12),
                                      border: Border.all(
                                        color: isSelected ? const Color(0xFF3B82F6) : const Color(0xFFE2E8F0),
                                        width: isSelected ? 1.5 : 1,
                                      ),
                                    ),
                                    child: Row(
                                      children: [
                                        Container(
                                          width: 32,
                                          height: 32,
                                          decoration: BoxDecoration(
                                            color: isSelected ? const Color(0xFF3B82F6) : const Color(0xFFE2E8F0),
                                            shape: BoxShape.circle,
                                          ),
                                          child: Icon(
                                            isSelected ? Icons.check_rounded : Icons.science_outlined,
                                            size: 16,
                                            color: isSelected ? Colors.white : const Color(0xFF64748B),
                                          ),
                                        ),
                                        const SizedBox(width: 10),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                matName,
                                                style: TextStyle(
                                                  fontWeight: FontWeight.bold,
                                                  fontSize: 13,
                                                  color: isSelected ? const Color(0xFF1E40AF) : const Color(0xFF0F172A),
                                                ),
                                              ),
                                              Text(
                                                'Rp ${_fmtPrice(matPrice)} / $matUnit',
                                                style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                              ),
                                            ],
                                          ),
                                        ),
                                        if (isSelected)
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                            decoration: BoxDecoration(
                                              color: const Color(0xFF3B82F6),
                                              borderRadius: BorderRadius.circular(12),
                                            ),
                                            child: const Text(
                                              'Dipilih',
                                              style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                                            ),
                                          ),
                                      ],
                                    ),
                                  ),
                                );
                              },
                            ),
                    ),
                    const SizedBox(height: 14),

                    // Takaran Input & Calculation Preview
                    if (_selected != null) ...[
                      Text(
                        'Takaran yang Digunakan ($unit) *',
                        style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12.5, color: Color(0xFF374151)),
                      ),
                      const SizedBox(height: 6),
                      TextField(
                        controller: _qtyCtrl,
                        autofocus: true,
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        textInputAction: TextInputAction.done,
                        inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d.,]'))],
                        onChanged: (_) => setState(() {}),
                        onSubmitted: (_) => _submitForm(),
                        decoration: InputDecoration(
                          hintText: 'Contoh: 100 (takaran per 1 porsi)',
                          suffixText: unit,
                          suffixStyle: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF0F172A))),
                          filled: true,
                          fillColor: const Color(0xFFF8FAFC),
                        ),
                      ),
                      const SizedBox(height: 10),

                      // Live Calculation Card
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF0FDF4),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFFBBF7D0)),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.calculate_rounded, size: 20, color: Color(0xFF16A34A)),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    '$qty $unit × Rp ${_fmtPrice(pricePerUnit)}/$unit',
                                    style: const TextStyle(fontSize: 11, color: Color(0xFF15803D)),
                                  ),
                                  const SizedBox(height: 1),
                                  Text(
                                    'Estimasi Biaya: Rp ${_fmtPrice(subtotal)}',
                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF15803D)),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),

            // Fixed Bottom Submit Button
            Container(
              padding: const EdgeInsets.fromLTRB(20, 10, 20, 16),
              decoration: const BoxDecoration(
                color: Colors.white,
                border: Border(top: BorderSide(color: Color(0xFFF1F5F9))),
              ),
              child: SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF0F172A),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    elevation: 0,
                  ),
                  onPressed: _submitForm,
                  child: const Text(
                    'Tambahkan ke Resep',
                    style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14.5),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
