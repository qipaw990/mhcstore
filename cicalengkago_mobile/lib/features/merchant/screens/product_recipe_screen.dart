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

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final ctrl = context.read<MerchantController>();
      // Pastikan daftar bahan baku sudah ada
      if (ctrl.rawMaterials.isEmpty) await ctrl.fetchRawMaterials();
      await ctrl.fetchProductRecipe(int.parse(widget.product['id'].toString()));
      _loadFromController();
    });
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
        onAdd: (id, qty) {
          setState(() {
            _recipeItems.add({'raw_material_id': id, 'qty_used': qty});
          });
        },
      ),
    );
  }

  Future<void> _saveRecipe() async {
    setState(() => _saving = true);
    final productId = int.tryParse(widget.product['id'].toString()) ?? 0;
    final res = await context.read<MerchantController>().saveProductRecipe(productId, _recipeItems);
    setState(() => _saving = false);

    if (mounted) {
      final hpp = (res['total_hpp'] as num?)?.toDouble() ?? 0;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(res['success'] == true
            ? '✅ ${res['message']} • HPP: Rp ${_fmtPrice(hpp)}'
            : '❌ ${res['message']}'),
        backgroundColor: res['success'] == true ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
        duration: const Duration(seconds: 3),
      ));
    }
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<MerchantController>();
    final mats = ctrl.rawMaterials;
    final pName = widget.product['name']?.toString() ?? 'Produk';
    final hppDb = double.tryParse(widget.product['hpp']?.toString() ?? '0') ?? 0;

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
            const Text('Resep & HPP', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A))),
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
          : Column(
              children: [
                // ── HPP Summary Card ──
                Container(
                  width: double.infinity,
                  margin: const EdgeInsets.all(16),
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(18),
                    boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 12, offset: const Offset(0, 4))],
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('HPP Terhitung', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 11, fontWeight: FontWeight.w500)),
                            const SizedBox(height: 4),
                            Text('Rp ${_fmtPrice(_totalHpp)}',
                                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 22)),
                            const SizedBox(height: 4),
                            Text('(tersimpan: Rp ${_fmtPrice(hppDb)})',
                                style: const TextStyle(color: Color(0xFF64748B), fontSize: 11)),
                          ],
                        ),
                      ),
                      Column(
                        children: [
                          const Icon(Icons.calculate_outlined, color: Color(0xFF64748B), size: 28),
                          const SizedBox(height: 4),
                          Text('${_recipeItems.length} bahan', style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 10)),
                        ],
                      ),
                    ],
                  ),
                ),

                // ── Daftar Bahan di Resep ──
                Expanded(
                  child: _recipeItems.isEmpty
                      ? _buildEmptyRecipe()
                      : ListView.separated(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          itemCount: _recipeItems.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
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
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(color: const Color(0xFFE2E8F0)),
                              ),
                              child: Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(8),
                                    decoration: BoxDecoration(color: const Color(0xFFF0FDF4), borderRadius: BorderRadius.circular(10)),
                                    child: const Icon(Icons.eco_outlined, color: Color(0xFF16A34A), size: 18),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(matName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A))),
                                        Text('Rp ${_fmtPrice(price)}/$unit', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                                      ],
                                    ),
                                  ),
                                  // Qty input
                                  SizedBox(
                                    width: 80,
                                    child: TextFormField(
                                      initialValue: qty == qty.toInt() ? qty.toInt().toString() : qty.toStringAsFixed(2),
                                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                      inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d.,]'))],
                                      textAlign: TextAlign.center,
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                      decoration: InputDecoration(
                                        suffix: Text(unit, style: const TextStyle(fontSize: 9, color: Color(0xFF94A3B8))),
                                        contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                                        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFF0F172A))),
                                        filled: true,
                                        fillColor: const Color(0xFFF8FAFC),
                                      ),
                                      onChanged: (v) {
                                        final parsed = double.tryParse(v.replaceAll(',', '.')) ?? 0;
                                        setState(() => _recipeItems[i]['qty_used'] = parsed);
                                      },
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Column(
                                    children: [
                                      Text('Rp ${_fmtPrice(cost)}',
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF2563EB))),
                                      IconButton(
                                        padding: EdgeInsets.zero,
                                        constraints: const BoxConstraints(minWidth: 28, minHeight: 28),
                                        icon: const Icon(Icons.remove_circle_outline, size: 18, color: Color(0xFFDC2626)),
                                        onPressed: () => setState(() => _recipeItems.removeAt(i)),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                ),
              ],
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
                label: const Text('Tambah Bahan', style: TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.bold)),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF0F172A),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: (_saving || _recipeItems.isEmpty) ? null : _saveRecipe,
                icon: _saving
                    ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Icon(Icons.save_alt_rounded, size: 18, color: Colors.white),
                label: Text(_saving ? 'Menyimpan...' : 'Simpan Resep',
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyRecipe() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.menu_book_outlined, size: 52, color: Color(0xFFCBD5E1)),
          const SizedBox(height: 12),
          const Text('Resep Kosong', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A))),
          const SizedBox(height: 6),
          const Text('Tekan "+ Tambah Bahan" untuk memulai resep.',
              style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
        ],
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
  final _qtyCtrl = TextEditingController();

  @override
  void dispose() {
    _qtyCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final unit = _selected?['unit']?.toString() ?? '';
    return Container(
      padding: EdgeInsets.only(
        left: 20, right: 20, top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
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
            child: Container(width: 40, height: 4,
                decoration: BoxDecoration(color: const Color(0xFFE2E8F0), borderRadius: BorderRadius.circular(2))),
          ),
          const SizedBox(height: 20),
          const Text('Tambah Bahan ke Resep', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
          const SizedBox(height: 16),

          // Pilih bahan
          const Text('Pilih Bahan Baku', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 12.5, color: Color(0xFF374151))),
          const SizedBox(height: 8),
          DropdownButtonFormField<Map<String, dynamic>>(
            value: _selected,
            hint: const Text('-- Pilih Bahan --'),
            items: widget.availableMats.map((m) {
              final mat = m as Map<String, dynamic>;
              return DropdownMenuItem(
                value: mat,
                child: Text('${mat['name']} (${mat['unit']}) — Rp ${mat['price_per_unit']}/${mat['unit']}'),
              );
            }).toList(),
            onChanged: (v) => setState(() => _selected = v),
            decoration: InputDecoration(
              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
              filled: true, fillColor: const Color(0xFFF8FAFC),
            ),
          ),
          const SizedBox(height: 16),

          // Jumlah takaran
          Text('Jumlah Dipakai${unit.isNotEmpty ? " ($unit)" : ""} *',
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12.5, color: Color(0xFF374151))),
          const SizedBox(height: 8),
          TextField(
            controller: _qtyCtrl,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d.,]'))],
            decoration: InputDecoration(
              hintText: 'contoh: 100',
              suffixText: unit,
              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
              focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF0F172A))),
              filled: true, fillColor: const Color(0xFFF8FAFC),
            ),
          ),

          // Hitung biaya preview
          if (_selected != null && _qtyCtrl.text.isNotEmpty) ...[
            const SizedBox(height: 10),
            Builder(builder: (_) {
              final qty = double.tryParse(_qtyCtrl.text.replaceAll(',', '.')) ?? 0;
              final price = double.tryParse(_selected!['price_per_unit']?.toString() ?? '0') ?? 0;
              final cost = qty * price;
              return Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: const Color(0xFFF0FDF4), borderRadius: BorderRadius.circular(10)),
                child: Row(
                  children: [
                    const Icon(Icons.calculate_outlined, size: 16, color: Color(0xFF16A34A)),
                    const SizedBox(width: 8),
                    Text('Biaya: Rp ${cost.toStringAsFixed(0)}',
                        style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF16A34A), fontSize: 13)),
                  ],
                ),
              );
            }),
          ],

          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            height: 50,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0F172A),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              onPressed: () {
                if (_selected == null) {
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Pilih bahan terlebih dahulu')));
                  return;
                }
                final qty = double.tryParse(_qtyCtrl.text.replaceAll(',', '.')) ?? 0;
                if (qty <= 0) {
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Masukkan jumlah takaran')));
                  return;
                }
                final id = int.tryParse(_selected!['id'].toString()) ?? 0;
                widget.onAdd(id, qty);
                Navigator.pop(context);
              },
              child: const Text('Tambahkan ke Resep', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
            ),
          ),
        ],
      ),
    );
  }
}
