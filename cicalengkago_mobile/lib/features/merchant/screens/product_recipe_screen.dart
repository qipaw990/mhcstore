import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../controllers/merchant_controller.dart';

class ProductRecipeScreen extends StatefulWidget {
  final Map<String, dynamic> product;

  const ProductRecipeScreen({super.key, required this.product});

  @override
  State<ProductRecipeScreen> createState() => _ProductRecipeScreenState();
}

class _ProductRecipeScreenState extends State<ProductRecipeScreen> {
  /// 0 = Resep Dasar (Default), 1..N = Variasi Menu (index 0..N-1 di _variations)
  int _selectedTabIndex = 0;

  /// Resep Dasar: [{raw_material_id, qty_used, material_name, unit, price_per_unit}]
  List<Map<String, dynamic>> _baseRecipeItems = [];

  /// Variasi Produk:
  /// [
  ///   {
  ///     'id': 1,
  ///     'name': 'Large (200gr)',
  ///     'price': 25000.0,
  ///     'stock': 100,
  ///     'hpp': 12000.0,
  ///     'margin_mode': 'percent', // 'percent' | 'nominal' | 'manual'
  ///     'margin_percent': 50.0,
  ///     'margin_nominal': 5000.0,
  ///     'custom_selling_price': 25000.0,
  ///     'auto_update_price': true,
  ///     'ingredients': [{raw_material_id, qty_used, material_name, unit, price_per_unit}],
  ///   }
  /// ]
  List<Map<String, dynamic>> _variations = [];

  bool _saving = false;

  // ── Mode Kalkulator Margin & Harga Jual Resep Dasar ────────────────────────
  bool _autoUpdateBasePrice = false;
  String _baseMarginMode = 'percent'; // 'percent' | 'nominal' | 'manual'
  double _baseMarginPercent = 50.0;
  double _baseMarginNominal = 5000.0;
  double _baseCustomSellingPrice = 0.0;
  late TextEditingController _basePercentCtrl;
  late TextEditingController _baseNominalCtrl;
  late TextEditingController _baseCustomPriceCtrl;

  @override
  void initState() {
    super.initState();
    final initialPrice = double.tryParse(widget.product['price']?.toString() ?? '0') ?? 0.0;
    _baseCustomSellingPrice = initialPrice;
    _basePercentCtrl = TextEditingController(text: '50');
    _baseNominalCtrl = TextEditingController(text: '5000');
    _baseCustomPriceCtrl = TextEditingController(text: initialPrice > 0 ? initialPrice.toInt().toString() : '');

    // Inisialisasi awal variasi jika tersedia di widget.product
    final initialVars = widget.product['variations'];
    if (initialVars is List && initialVars.isNotEmpty) {
      _variations = initialVars.map((v) {
        final vPrice = double.tryParse(v['price']?.toString() ?? '0') ?? 0.0;
        final vHpp = double.tryParse(v['hpp']?.toString() ?? '0') ?? 0.0;
        return {
          'id': v['id'],
          'name': v['name']?.toString() ?? 'Variasi',
          'price': vPrice,
          'stock': int.tryParse(v['stock']?.toString() ?? '100') ?? 100,
          'hpp': vHpp,
          'margin_mode': 'percent',
          'margin_percent': 50.0,
          'margin_nominal': 5000.0,
          'custom_selling_price': vPrice,
          'auto_update_price': true,
          'ingredients': <Map<String, dynamic>>[],
        };
      }).toList();
    }

    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final ctrl = context.read<MerchantController>();
      if (ctrl.rawMaterials.isEmpty) await ctrl.fetchRawMaterials();
      await ctrl.fetchProductRecipe(int.parse(widget.product['id'].toString()));
      _loadFromController();
    });
  }

  @override
  void dispose() {
    _basePercentCtrl.dispose();
    _baseNominalCtrl.dispose();
    _baseCustomPriceCtrl.dispose();
    super.dispose();
  }

  void _loadFromController() {
    final data = context.read<MerchantController>().productRecipe;
    final recipe = (data['recipe'] as List?) ?? [];
    final variations = (data['variations'] as List?) ?? [];

    setState(() {
      _baseRecipeItems = recipe
          .map((r) => {
                'raw_material_id': int.tryParse(r['raw_material_id']?.toString() ?? '0') ?? 0,
                'qty_used': double.tryParse(r['qty_used']?.toString() ?? '0') ?? 0.0,
                'material_name': r['material_name'],
                'unit': r['unit'],
                'price_per_unit': r['price_per_unit'],
              })
          .toList();

      if (variations.isNotEmpty) {
        _variations = variations.map((v) {
          final vRecipe = (v['recipe'] as List?) ?? [];
          final vPrice = double.tryParse(v['price']?.toString() ?? '0') ?? 0.0;
          final vHpp = double.tryParse(v['hpp']?.toString() ?? '0') ?? 0.0;
          return {
            'id': v['id'],
            'name': v['name']?.toString() ?? 'Variasi',
            'price': vPrice,
            'stock': int.tryParse(v['stock']?.toString() ?? '100') ?? 100,
            'hpp': vHpp,
            'margin_mode': 'percent',
            'margin_percent': 50.0,
            'margin_nominal': 5000.0,
            'custom_selling_price': vPrice,
            'auto_update_price': true,
            'ingredients': vRecipe
                .map((r) => {
                      'raw_material_id': int.tryParse(r['raw_material_id']?.toString() ?? '0') ?? 0,
                      'qty_used': double.tryParse(r['qty_used']?.toString() ?? '0') ?? 0.0,
                      'material_name': r['material_name'],
                      'unit': r['unit'],
                      'price_per_unit': r['price_per_unit'],
                    })
                .toList(),
          };
        }).toList();
      }

      _recalcPricing();
    });
  }

  // ── Helper Perhitungan HPP ───────────────────────────────────────────────

  double _calcHppForIngredients(List<Map<String, dynamic>> items) {
    final ctrl = context.read<MerchantController>();
    final mats = ctrl.rawMaterials;
    double total = 0;
    for (final item in items) {
      final id = item['raw_material_id'];
      final qty = (item['qty_used'] as double?) ?? (double.tryParse(item['qty_used']?.toString() ?? '0') ?? 0.0);
      final mat = mats.firstWhere(
          (m) => m['id']?.toString() == id.toString(),
          orElse: () => <String, dynamic>{});
      final price = double.tryParse((mat['price_per_unit'] ?? item['price_per_unit'] ?? '0').toString()) ?? 0.0;
      total += qty * price;
    }
    return total;
  }

  double get _baseHpp => _calcHppForIngredients(_baseRecipeItems);

  double get _baseCalculatedSellingPrice {
    final hpp = _baseHpp;
    if (hpp <= 0) {
      return double.tryParse(widget.product['price']?.toString() ?? '0') ?? 0.0;
    }
    if (_baseMarginMode == 'percent') {
      return hpp * (1 + (_baseMarginPercent / 100));
    } else if (_baseMarginMode == 'nominal') {
      return hpp + _baseMarginNominal;
    } else {
      return _baseCustomSellingPrice > 0 ? _baseCustomSellingPrice : hpp;
    }
  }

  double _calcVariantHpp(int variantIndex) {
    if (variantIndex < 0 || variantIndex >= _variations.length) return 0.0;
    final items = (_variations[variantIndex]['ingredients'] as List<Map<String, dynamic>>?) ?? [];
    return _calcHppForIngredients(items);
  }

  double _calcVariantSellingPrice(int variantIndex) {
    if (variantIndex < 0 || variantIndex >= _variations.length) return 0.0;
    final v = _variations[variantIndex];
    final hpp = _calcVariantHpp(variantIndex);
    final mode = v['margin_mode']?.toString() ?? 'percent';
    final pct = (v['margin_percent'] as num?)?.toDouble() ?? 50.0;
    final nom = (v['margin_nominal'] as num?)?.toDouble() ?? 5000.0;
    final custom = (v['custom_selling_price'] as num?)?.toDouble() ?? (v['price'] as num?)?.toDouble() ?? 0.0;

    if (hpp <= 0) return custom > 0 ? custom : ((v['price'] as num?)?.toDouble() ?? 0.0);

    if (mode == 'percent') {
      return hpp * (1 + (pct / 100));
    } else if (mode == 'nominal') {
      return hpp + nom;
    } else {
      return custom > 0 ? custom : hpp;
    }
  }

  void _recalcPricing() {
    if (_baseMarginMode == 'manual') {
      if (_baseHpp > 0 && _baseCustomSellingPrice > 0) {
        _baseMarginPercent = ((_baseCustomSellingPrice - _baseHpp) / _baseHpp) * 100;
        _baseMarginNominal = _baseCustomSellingPrice - _baseHpp;
      }
    }
  }

  // ── Manajemen Bahan Baku Active Tab ──────────────────────────────────────

  List<Map<String, dynamic>> get _currentActiveIngredients {
    if (_selectedTabIndex == 0) {
      return _baseRecipeItems;
    } else {
      final vIdx = _selectedTabIndex - 1;
      if (vIdx >= 0 && vIdx < _variations.length) {
        return _variations[vIdx]['ingredients'] as List<Map<String, dynamic>>;
      }
      return [];
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

    final activeItems = _currentActiveIngredients;
    final addedIds = activeItems.map((r) => r['raw_material_id'].toString()).toSet();
    final available = mats.where((m) => !addedIds.contains(m['id']?.toString())).toList();

    if (available.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Semua bahan baku sudah ditambahkan ke racikan ini')),
      );
      return;
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _AddIngredientSheet(
        availableMats: available,
        onAdd: (id, qty) {
          final mat = mats.firstWhere((m) => m['id']?.toString() == id.toString(), orElse: () => <String, dynamic>{});
          setState(() {
            final existingIndex = activeItems.indexWhere((r) => r['raw_material_id'].toString() == id.toString());
            if (existingIndex >= 0) {
              activeItems[existingIndex]['qty_used'] = qty;
            } else {
              activeItems.add({
                'raw_material_id': id,
                'qty_used': qty,
                'material_name': mat['name'],
                'unit': mat['unit'],
                'price_per_unit': mat['price_per_unit'],
              });
            }

            // Jika tab varian sedang aktif dan auto_update_price dicentang, update harga variannya
            if (_selectedTabIndex > 0) {
              final vIdx = _selectedTabIndex - 1;
              if (vIdx >= 0 && vIdx < _variations.length) {
                final v = _variations[vIdx];
                if (v['auto_update_price'] == true) {
                  v['price'] = _calcVariantSellingPrice(vIdx);
                }
              }
            } else {
              _recalcPricing();
            }
          });
        },
      ),
    );
  }

  // ── Salin Resep Dasar ke Varian ──────────────────────────────────────────

  void _showCopyBaseRecipeDialog(int variantIndex) {
    if (_baseRecipeItems.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Resep dasar masih kosong! Atur resep dasar terlebih dahulu.')),
      );
      return;
    }

    final vName = _variations[variantIndex]['name'] ?? 'Variasi';
    double multiplier = 1.0;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialogState) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
          title: Text('Salin Bahan ke $vName', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Salin ${_baseRecipeItems.length} bahan baku dari Resep Dasar ke varian ini dengan pengali takaran:',
                style: const TextStyle(fontSize: 12.5, color: Color(0xFF475569)),
              ),
              const SizedBox(height: 14),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [0.5, 1.0, 1.25, 1.5, 2.0, 3.0, 5.0, 10.0].map((m) {
                  final isSelected = multiplier == m;
                  return InkWell(
                    onTap: () => setDialogState(() => multiplier = m),
                    borderRadius: BorderRadius.circular(8),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      decoration: BoxDecoration(
                        color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        '${m}x',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: isSelected ? Colors.white : const Color(0xFF334155),
                        ),
                      ),
                    ),
                  );
                }).toList(),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Batal', style: TextStyle(color: Color(0xFF64748B))),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0F172A),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              onPressed: () {
                setState(() {
                  _variations[variantIndex]['ingredients'] = _baseRecipeItems.map((item) {
                    final origQty = (item['qty_used'] as double?) ?? (double.tryParse(item['qty_used']?.toString() ?? '0') ?? 0.0);
                    return {
                      'raw_material_id': item['raw_material_id'],
                      'qty_used': origQty * multiplier,
                      'material_name': item['material_name'],
                      'unit': item['unit'],
                      'price_per_unit': item['price_per_unit'],
                    };
                  }).toList();

                  // Auto update harga varian
                  final calculatedPrice = _calcVariantSellingPrice(variantIndex);
                  _variations[variantIndex]['price'] = calculatedPrice;
                  _variations[variantIndex]['hpp'] = _calcVariantHpp(variantIndex);
                });
                Navigator.pop(ctx);
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('✅ ${_baseRecipeItems.length} bahan berhasil disalin (${multiplier}x) ke $vName!'),
                    backgroundColor: const Color(0xFF16A34A),
                  ),
                );
              },
              child: const Text('Terapkan', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      ),
    );
  }

  // ── Tambah / Edit Varian Dialog ──────────────────────────────────────────

  void _showAddOrEditVariantDialog({int? editIndex}) {
    final isEdit = editIndex != null;
    final currentVar = isEdit ? _variations[editIndex] : null;

    final nameCtrl = TextEditingController(text: currentVar?['name']?.toString() ?? '');
    final priceCtrl = TextEditingController(text: currentVar != null ? (currentVar['price'] as num).toInt().toString() : '');
    final stockCtrl = TextEditingController(text: currentVar != null ? (currentVar['stock'] as num).toInt().toString() : '100');

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        title: Row(
          children: [
            Icon(isEdit ? Icons.edit_note_rounded : Icons.add_circle_outline_rounded, color: AppTheme.primaryRed, size: 22),
            const SizedBox(width: 8),
            Text(isEdit ? 'Ubah Variasi Menu' : 'Tambah Variasi Menu', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Nama Varian / Porsi *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12, color: Color(0xFF475569))),
            const SizedBox(height: 4),
            TextField(
              controller: nameCtrl,
              autofocus: true,
              decoration: InputDecoration(
                hintText: 'Contoh: Regular, Jumbo, 1 Bungkus, 1 Slop',
                hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Harga Jual (Rp) *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12, color: Color(0xFF475569))),
                      const SizedBox(height: 4),
                      TextField(
                        controller: priceCtrl,
                        keyboardType: TextInputType.number,
                        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                        decoration: InputDecoration(
                          hintText: '25000',
                          prefixText: 'Rp ',
                          contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                          filled: true,
                          fillColor: const Color(0xFFF8FAFC),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 10),
                SizedBox(
                  width: 90,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Stok', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12, color: Color(0xFF475569))),
                      const SizedBox(height: 4),
                      TextField(
                        controller: stockCtrl,
                        keyboardType: TextInputType.number,
                        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                        decoration: InputDecoration(
                          hintText: '100',
                          contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                          filled: true,
                          fillColor: const Color(0xFFF8FAFC),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal', style: TextStyle(color: Color(0xFF64748B))),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryRed,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () {
              final name = nameCtrl.text.trim();
              if (name.isEmpty) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nama varian tidak boleh kosong!')));
                return;
              }
              final price = double.tryParse(priceCtrl.text.trim()) ?? 0.0;
              final stock = int.tryParse(stockCtrl.text.trim()) ?? 100;

              setState(() {
                if (isEdit) {
                  _variations[editIndex]['name'] = name;
                  _variations[editIndex]['price'] = price;
                  _variations[editIndex]['stock'] = stock;
                } else {
                  _variations.add({
                    'id': null,
                    'name': name,
                    'price': price,
                    'stock': stock,
                    'hpp': 0.0,
                    'margin_mode': 'percent',
                    'margin_percent': 50.0,
                    'margin_nominal': 5000.0,
                    'custom_selling_price': price,
                    'auto_update_price': true,
                    'ingredients': <Map<String, dynamic>>[],
                  });
                  // Pindah ke tab varian yang baru dibuat
                  _selectedTabIndex = _variations.length;
                }
              });

              Navigator.pop(ctx);
            },
            child: Text(isEdit ? 'Simpan' : 'Tambah', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  void _deleteVariant(int variantIndex) {
    final vName = _variations[variantIndex]['name'] ?? 'Variasi';
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        title: Text('Hapus Variasi $vName?', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        content: const Text('Variasi ini dan racikan bahan bakunya akan dihapus dari resep produk.', style: TextStyle(fontSize: 12.5)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal', style: TextStyle(color: Color(0xFF64748B))),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFDC2626)),
            onPressed: () {
              setState(() {
                _variations.removeAt(variantIndex);
                if (_selectedTabIndex > _variations.length) {
                  _selectedTabIndex = _variations.length;
                }
              });
              Navigator.pop(ctx);
            },
            child: const Text('Hapus', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  // ── Simpan Semua Resep & Variasi ke Backend ──────────────────────────────

  Future<void> _saveRecipe({bool closeOnSuccess = false}) async {
    setState(() => _saving = true);
    final productId = int.tryParse(widget.product['id'].toString()) ?? 0;

    // Persiapkan payload variasi
    final List<Map<String, dynamic>> variationsPayload = _variations.map((v) {
      final vIngredients = (v['ingredients'] as List<dynamic>?) ?? [];
      return {
        'id': v['id'],
        'name': v['name'],
        'price': (v['price'] as num?)?.toDouble() ?? 0.0,
        'stock': v['stock'] ?? 100,
        'hpp': _calcHppForIngredients(List<Map<String, dynamic>>.from(vIngredients)),
        'ingredients': vIngredients.map((ing) => {
          'raw_material_id': ing['raw_material_id'],
          'qty_used': ing['qty_used'],
        }).toList(),
      };
    }).toList();

    final targetBasePrice = _autoUpdateBasePrice ? _baseCalculatedSellingPrice : null;

    final res = await context.read<MerchantController>().saveProductRecipe(
      productId,
      _baseRecipeItems.map((ing) => {
        'raw_material_id': ing['raw_material_id'],
        'qty_used': ing['qty_used'],
      }).toList(),
      newPrice: targetBasePrice,
      variations: variationsPayload,
    );

    setState(() => _saving = false);

    if (mounted) {
      final hpp = (res['total_hpp'] as num?)?.toDouble() ?? 0;
      final newPrice = (res['price'] as num?)?.toDouble();
      String priceMsg = '';
      if (targetBasePrice != null && newPrice != null && newPrice > 0) {
        priceMsg = ' • Harga Dasar: Rp ${_fmtPrice(newPrice)}';
      }

      if (res['success'] == true) {
        _loadFromController();
      }

      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(res['success'] == true
            ? '✅ Resep & ${_variations.length} Variasi berhasil disimpan! (HPP Dasar: Rp ${_fmtPrice(hpp)}$priceMsg)'
            : '❌ ${res['message']}'),
        backgroundColor: res['success'] == true ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
        duration: const Duration(seconds: 2),
      ));

      if (res['success'] == true && closeOnSuccess) {
        Navigator.pop(context, true);
      }
    }
  }

  // ── Build UI ─────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<MerchantController>();
    final pName = widget.product['name']?.toString() ?? 'Produk';
    final currentBasePrice = double.tryParse(widget.product['price']?.toString() ?? '0') ?? 0;

    final bool isBaseTab = _selectedTabIndex == 0;
    final int variantIndex = _selectedTabIndex - 1;

    // HPP & Selling Price for Current View
    final double activeHpp = isBaseTab ? _baseHpp : _calcVariantHpp(variantIndex);
    final double activeSellingPrice = isBaseTab
        ? _baseCalculatedSellingPrice
        : (variantIndex >= 0 && variantIndex < _variations.length ? _calcVariantSellingPrice(variantIndex) : 0.0);
    final double activeProfit = activeSellingPrice - activeHpp;
    final double activeMarginPct = activeHpp > 0 ? (activeProfit / activeHpp) * 100 : 0.0;

    final activeIngredients = _currentActiveIngredients;

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
            const Text('Resep, HPP & Variasi', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A))),
            Text(pName, style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)), overflow: TextOverflow.ellipsis),
          ],
        ),
        actions: [
          if (!_saving)
            TextButton.icon(
              onPressed: () => _saveRecipe(closeOnSuccess: true),
              icon: const Icon(Icons.check_circle_outline_rounded, size: 17, color: Color(0xFF16A34A)),
              label: const Text('Simpan', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF16A34A))),
            ),
        ],
      ),
      body: ctrl.isRecipeLoading && _baseRecipeItems.isEmpty && _variations.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.only(bottom: 100),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // ── HORIZONTAL TAB BAR: Resep Dasar & Variasi Menu ──
                  Container(
                    width: double.infinity,
                    color: Colors.white,
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
                    child: SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          // Tab 0: Resep Dasar
                          _buildTabPill(
                            index: 0,
                            icon: Icons.push_pin_rounded,
                            title: 'Resep Dasar',
                            subtitle: 'HPP Rp ${_fmtPrice(_baseHpp)}',
                            isSelected: _selectedTabIndex == 0,
                          ),
                          const SizedBox(width: 8),

                          // Tab 1..N: Variasi Produk
                          ..._variations.asMap().entries.map((entry) {
                            final idx = entry.key + 1;
                            final v = entry.value;
                            final vHpp = _calcVariantHpp(entry.key);
                            final vName = v['name']?.toString() ?? 'Variasi';

                            return Padding(
                              padding: const EdgeInsets.only(right: 8),
                              child: _buildTabPill(
                                index: idx,
                                icon: Icons.local_offer_outlined,
                                title: vName,
                                subtitle: 'HPP Rp ${_fmtPrice(vHpp)}',
                                isSelected: _selectedTabIndex == idx,
                              ),
                            );
                          }),

                          // Tombol Tambah Variasi
                          InkWell(
                            onTap: () => _showAddOrEditVariantDialog(),
                            borderRadius: BorderRadius.circular(12),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                              decoration: BoxDecoration(
                                color: const Color(0xFFFEF2F2),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: const Color(0xFFFECACA)),
                              ),
                              child: const Row(
                                children: [
                                  Icon(Icons.add_rounded, size: 16, color: AppTheme.primaryRed),
                                  SizedBox(width: 4),
                                  Text(
                                    'Tambah Varian',
                                    style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const Divider(height: 1, color: Color(0xFFE2E8F0)),

                  // ── VARIANT HEADER ACTION (JIKA SEDANG MEMILIH VARIAN) ──
                  if (!isBaseTab && variantIndex >= 0 && variantIndex < _variations.length) ...[
                    Container(
                      margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: const Color(0xFFFEE2E2),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: const Text('VARIAN', style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: AppTheme.primaryRed)),
                                    ),
                                    const SizedBox(width: 6),
                                    Expanded(
                                      child: Text(
                                        _variations[variantIndex]['name'] ?? 'Variasi',
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 3),
                                Text(
                                  'Harga Jual: Rp ${_fmtPrice((_variations[variantIndex]['price'] as num?)?.toDouble() ?? 0)} • Stok: ${_variations[variantIndex]['stock'] ?? 100}',
                                  style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                ),
                              ],
                            ),
                          ),
                          Row(
                            children: [
                              IconButton(
                                icon: const Icon(Icons.copy_all_rounded, size: 18, color: Color(0xFF2563EB)),
                                tooltip: 'Salin Bahan dari Resep Dasar',
                                onPressed: () => _showCopyBaseRecipeDialog(variantIndex),
                              ),
                              IconButton(
                                icon: const Icon(Icons.edit_outlined, size: 18, color: Color(0xFF475569)),
                                tooltip: 'Ubah Nama/Stok Varian',
                                onPressed: () => _showAddOrEditVariantDialog(editIndex: variantIndex),
                              ),
                              IconButton(
                                icon: const Icon(Icons.delete_outline_rounded, size: 18, color: Color(0xFFDC2626)),
                                tooltip: 'Hapus Varian Ini',
                                onPressed: () => _deleteVariant(variantIndex),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],

                  // ── 1. SUMMARY CARD (HPP, UNTUNG & HARGA JUAL) ──
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.fromLTRB(16, 12, 16, 10),
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: isBaseTab
                            ? const [Color(0xFF0F172A), Color(0xFF1E293B)]
                            : const [Color(0xFF881337), Color(0xFF9F1239)],
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
                                Text(
                                  isBaseTab ? 'HPP Resep Dasar (Biaya Bahan)' : 'HPP Varian (Biaya Bahan)',
                                  style: const TextStyle(color: Color(0xFFE2E8F0), fontSize: 11, fontWeight: FontWeight.w500),
                                ),
                                const SizedBox(height: 3),
                                Text(
                                  'Rp ${_fmtPrice(activeHpp)}',
                                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 24),
                                ),
                              ],
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.science_outlined, color: Colors.white, size: 14),
                                  const SizedBox(width: 5),
                                  Text('${activeIngredients.length} bahan', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
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
                                  const Text('Harga Jual Otomatis', style: TextStyle(color: Color(0xFFCBD5E1), fontSize: 11)),
                                  const SizedBox(height: 2),
                                  Text(
                                    'Rp ${_fmtPrice(activeSellingPrice)}',
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
                                    '+Rp ${_fmtPrice(activeProfit)} (+${activeMarginPct.toStringAsFixed(1)}%)',
                                    style: TextStyle(
                                      color: activeProfit >= 0 ? const Color(0xFF4ADE80) : const Color(0xFFF87171),
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
                  if (activeHpp > 0)
                    Container(
                      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
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
                              Text(
                                isBaseTab ? 'Target Keuntungan & Harga Dasar' : 'Target Keuntungan & Harga Varian',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Color(0xFF0F172A)),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),

                          if (isBaseTab) ...[
                            // Mode selector untuk Resep Dasar
                            Row(
                              children: [
                                _buildModeChip('percent', 'Margin (%)', _baseMarginMode, (m) => setState(() => _baseMarginMode = m)),
                                const SizedBox(width: 6),
                                _buildModeChip('nominal', 'Nominal (+Rp)', _baseMarginMode, (m) => setState(() => _baseMarginMode = m)),
                                const SizedBox(width: 6),
                                _buildModeChip('manual', 'Ketik Harga', _baseMarginMode, (m) => setState(() => _baseMarginMode = m)),
                              ],
                            ),
                            const SizedBox(height: 10),
                            if (_baseMarginMode == 'percent') ...[
                              TextField(
                                controller: _basePercentCtrl,
                                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d.,]'))],
                                decoration: InputDecoration(
                                  labelText: 'Margin Keuntungan',
                                  suffixText: '%',
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                                  filled: true,
                                  fillColor: const Color(0xFFF8FAFC),
                                ),
                                onChanged: (v) {
                                  final val = double.tryParse(v.replaceAll(',', '.')) ?? 0;
                                  setState(() => _baseMarginPercent = val);
                                },
                              ),
                            ] else if (_baseMarginMode == 'nominal') ...[
                              TextField(
                                controller: _baseNominalCtrl,
                                keyboardType: TextInputType.number,
                                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                                decoration: InputDecoration(
                                  labelText: 'Tambah Untung Nominal',
                                  prefixText: 'Rp ',
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                                  filled: true,
                                  fillColor: const Color(0xFFF8FAFC),
                                ),
                                onChanged: (v) {
                                  final val = double.tryParse(v) ?? 0;
                                  setState(() => _baseMarginNominal = val);
                                },
                              ),
                            ] else ...[
                              TextField(
                                controller: _baseCustomPriceCtrl,
                                keyboardType: TextInputType.number,
                                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                                decoration: InputDecoration(
                                  labelText: 'Ketik Langsung Harga Jual',
                                  prefixText: 'Rp ',
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                                  filled: true,
                                  fillColor: const Color(0xFFF8FAFC),
                                ),
                                onChanged: (v) {
                                  final val = double.tryParse(v) ?? 0;
                                  setState(() {
                                    _baseCustomSellingPrice = val;
                                    _recalcPricing();
                                  });
                                },
                              ),
                            ],
                            const SizedBox(height: 10),
                            InkWell(
                              onTap: () => setState(() => _autoUpdateBasePrice = !_autoUpdateBasePrice),
                              borderRadius: BorderRadius.circular(10),
                              child: Row(
                                children: [
                                  Checkbox(
                                    value: _autoUpdateBasePrice,
                                    activeColor: const Color(0xFF0F172A),
                                    onChanged: (v) => setState(() => _autoUpdateBasePrice = v ?? false),
                                  ),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        const Text('Perbarui Harga Produk Dasar Otomatis', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A))),
                                        Text('Harga dasar diatur ke Rp ${_fmtPrice(activeSellingPrice)} (sebelumnya Rp ${_fmtPrice(currentBasePrice)})', style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B))),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ] else if (variantIndex >= 0 && variantIndex < _variations.length) ...[
                            // Mode selector untuk Varian Menu
                            Builder(
                              builder: (ctx) {
                                final v = _variations[variantIndex];
                                final vMode = v['margin_mode']?.toString() ?? 'percent';

                                return Column(
                                  children: [
                                    Row(
                                      children: [
                                        _buildModeChip('percent', 'Margin (%)', vMode, (m) {
                                          setState(() {
                                            v['margin_mode'] = m;
                                            v['price'] = _calcVariantSellingPrice(variantIndex);
                                          });
                                        }),
                                        const SizedBox(width: 6),
                                        _buildModeChip('nominal', 'Nominal (+Rp)', vMode, (m) {
                                          setState(() {
                                            v['margin_mode'] = m;
                                            v['price'] = _calcVariantSellingPrice(variantIndex);
                                          });
                                        }),
                                        const SizedBox(width: 6),
                                        _buildModeChip('manual', 'Ketik Harga', vMode, (m) {
                                          setState(() {
                                            v['margin_mode'] = m;
                                            v['price'] = _calcVariantSellingPrice(variantIndex);
                                          });
                                        }),
                                      ],
                                    ),
                                    const SizedBox(height: 10),
                                    if (vMode == 'percent') ...[
                                      TextFormField(
                                        key: ValueKey('var_pct_${variantIndex}_${v['margin_percent']}'),
                                        initialValue: (v['margin_percent'] ?? 50).toString(),
                                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                        inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d.,]'))],
                                        decoration: InputDecoration(
                                          labelText: 'Margin Keuntungan Varian',
                                          suffixText: '%',
                                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                                          filled: true,
                                          fillColor: const Color(0xFFF8FAFC),
                                        ),
                                        onChanged: (val) {
                                          final parsed = double.tryParse(val.replaceAll(',', '.')) ?? 0;
                                          setState(() {
                                            v['margin_percent'] = parsed;
                                            v['price'] = _calcVariantSellingPrice(variantIndex);
                                          });
                                        },
                                      ),
                                    ] else if (vMode == 'nominal') ...[
                                      TextFormField(
                                        key: ValueKey('var_nom_${variantIndex}_${v['margin_nominal']}'),
                                        initialValue: (v['margin_nominal'] ?? 5000).toString(),
                                        keyboardType: TextInputType.number,
                                        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                                        decoration: InputDecoration(
                                          labelText: 'Tambah Untung Nominal',
                                          prefixText: 'Rp ',
                                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                                          filled: true,
                                          fillColor: const Color(0xFFF8FAFC),
                                        ),
                                        onChanged: (val) {
                                          final parsed = double.tryParse(val) ?? 0;
                                          setState(() {
                                            v['margin_nominal'] = parsed;
                                            v['price'] = _calcVariantSellingPrice(variantIndex);
                                          });
                                        },
                                      ),
                                    ] else ...[
                                      TextFormField(
                                        key: ValueKey('var_custom_${variantIndex}_${v['price']}'),
                                        initialValue: ((v['price'] as num?)?.toInt() ?? 0).toString(),
                                        keyboardType: TextInputType.number,
                                        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                                        decoration: InputDecoration(
                                          labelText: 'Harga Jual Varian',
                                          prefixText: 'Rp ',
                                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                                          filled: true,
                                          fillColor: const Color(0xFFF8FAFC),
                                        ),
                                        onChanged: (val) {
                                          final parsed = double.tryParse(val) ?? 0;
                                          setState(() {
                                            v['custom_selling_price'] = parsed;
                                            v['price'] = parsed;
                                          });
                                        },
                                      ),
                                    ],
                                    const SizedBox(height: 8),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                      decoration: BoxDecoration(
                                        color: const Color(0xFFF0FDF4),
                                        borderRadius: BorderRadius.circular(8),
                                        border: Border.all(color: const Color(0xFFBBF7D0)),
                                      ),
                                      child: Row(
                                        children: [
                                          const Icon(Icons.check_circle_rounded, size: 16, color: Color(0xFF16A34A)),
                                          const SizedBox(width: 6),
                                          Expanded(
                                            child: Text(
                                              'Harga Jual varian akan otomatis tersimpan: Rp ${_fmtPrice(activeSellingPrice)}',
                                              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF15803D)),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                );
                              },
                            ),
                          ],
                        ],
                      ),
                    ),

                  // ── 3. SECTION DAFTAR BAHAN BAKU ACTIVE TAB ──
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          isBaseTab ? 'Komposisi Resep Dasar' : 'Komposisi Varian (${_variations[variantIndex]['name']})',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Color(0xFF0F172A)),
                        ),
                        TextButton.icon(
                          onPressed: _addIngredient,
                          icon: const Icon(Icons.add_circle_outline_rounded, size: 16, color: Color(0xFF2563EB)),
                          label: const Text('Tambah Bahan', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF2563EB))),
                        ),
                      ],
                    ),
                  ),

                  activeIngredients.isEmpty
                      ? _buildEmptyRecipe(
                          isBase: isBaseTab,
                          onCopyBase: !isBaseTab && _baseRecipeItems.isNotEmpty ? () => _showCopyBaseRecipeDialog(variantIndex) : null,
                        )
                      : ListView.separated(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          itemCount: activeIngredients.length,
                          separatorBuilder: (_, _) => const SizedBox(height: 8),
                          itemBuilder: (ctx, i) {
                            final item = activeIngredients[i];
                            final matId = item['raw_material_id'];
                            final mat = ctrl.rawMaterials.firstWhere(
                                (m) => m['id']?.toString() == matId.toString(),
                                orElse: () => <String, dynamic>{});
                            final matName = (mat['name'] ?? item['material_name'] ?? 'Bahan #$matId').toString();
                            final unit = (mat['unit'] ?? item['unit'] ?? '').toString();
                            final price = double.tryParse((mat['price_per_unit'] ?? item['price_per_unit'] ?? '0').toString()) ?? 0.0;
                            final qty = double.tryParse((item['qty_used'] ?? 0).toString()) ?? 0.0;
                            final cost = qty * price;

                            return Container(
                              key: ValueKey('recipe_item_${_selectedTabIndex}_${matId}_$i'),
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
                                          onTap: () {
                                            setState(() {
                                              activeIngredients.removeAt(i);
                                              if (!isBaseTab && variantIndex >= 0 && variantIndex < _variations.length) {
                                                _variations[variantIndex]['price'] = _calcVariantSellingPrice(variantIndex);
                                              }
                                            });
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
                                  Container(
                                    padding: const EdgeInsets.fromLTRB(12, 6, 12, 12),
                                    decoration: const BoxDecoration(
                                      border: Border(top: BorderSide(color: Color(0xFFF1F5F9))),
                                    ),
                                    child: Row(
                                      children: [
                                        const Text('Takaran:', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                                        const SizedBox(width: 10),
                                        SizedBox(
                                          width: 90,
                                          height: 36,
                                          child: TextFormField(
                                            key: ValueKey('qty_field_${_selectedTabIndex}_${matId}_${qty}_$i'),
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
                                              setState(() {
                                                activeIngredients[i]['qty_used'] = parsed;
                                                if (!isBaseTab && variantIndex >= 0 && variantIndex < _variations.length) {
                                                  _variations[variantIndex]['price'] = _calcVariantSellingPrice(variantIndex);
                                                }
                                              });
                                            },
                                          ),
                                        ),
                                        const Spacer(),
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
                onPressed: _saving ? null : () => _saveRecipe(closeOnSuccess: true),
                icon: _saving
                    ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Icon(Icons.save_alt_rounded, size: 18, color: Colors.white),
                label: Text(
                  _saving ? 'Menyimpan...' : 'Simpan Semua',
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

  Widget _buildTabPill({
    required int index,
    required IconData icon,
    required String title,
    required String subtitle,
    required bool isSelected,
  }) {
    return InkWell(
      onTap: () => setState(() => _selectedTabIndex = index),
      borderRadius: BorderRadius.circular(14),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFE2E8F0),
            width: 1.5,
          ),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.08),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  )
                ]
              : null,
        ),
        child: Row(
          children: [
            Icon(icon, size: 16, color: isSelected ? Colors.white : const Color(0xFF64748B)),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                    color: isSelected ? Colors.white : const Color(0xFF1E293B),
                  ),
                ),
                Text(
                  subtitle,
                  style: TextStyle(
                    fontSize: 9.5,
                    color: isSelected ? const Color(0xFF94A3B8) : const Color(0xFF64748B),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildModeChip(String mode, String label, String currentMode, void Function(String) onSelect) {
    final isSelected = currentMode == mode;
    return Expanded(
      child: InkWell(
        onTap: () => onSelect(mode),
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

  Widget _buildEmptyRecipe({required bool isBase, VoidCallback? onCopyBase}) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 20),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.science_outlined, size: 44, color: Color(0xFFCBD5E1)),
            const SizedBox(height: 10),
            Text(
              isBase ? 'Belum Ada Bahan di Resep Dasar' : 'Belum Ada Bahan di Varian Ini',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 4),
            Text(
              isBase
                  ? 'Tekan tombol "+ Tambah Bahan" di bawah untuk menentukan komposisi racikan.'
                  : 'Anda dapat menyalin komposisi dari Resep Dasar atau menambahkan bahan baru khusus varian ini.',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
            ),
            if (onCopyBase != null) ...[
              const SizedBox(height: 12),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFEFF6FF),
                  foregroundColor: const Color(0xFF1D4ED8),
                  elevation: 0,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                    side: const BorderSide(color: Color(0xFFBFDBFE)),
                  ),
                ),
                onPressed: onCopyBase,
                icon: const Icon(Icons.copy_all_rounded, size: 16),
                label: const Text('Salin Bahan dari Resep Dasar', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold)),
              ),
            ],
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
                        'Tambah Bahan ke Racikan',
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
            Flexible(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
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
                    'Tambahkan ke Racikan',
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
