import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../controllers/merchant_controller.dart';

// ── Daftar Satuan yang Tersedia ─────────────────────────────────────────────
const _kUnits = [
  'ml', 'ltr', 'gr', 'kg', 'sdm', 'sdt', 'pcs', 'bungkus',
  'slice', 'lembar', 'butir', 'potong', 'lainnya',
];

class RawMaterialsScreen extends StatefulWidget {
  const RawMaterialsScreen({super.key});

  @override
  State<RawMaterialsScreen> createState() => _RawMaterialsScreenState();
}

class _RawMaterialsScreenState extends State<RawMaterialsScreen> {
  final TextEditingController _searchCtrl = TextEditingController();
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MerchantController>().fetchRawMaterials();
    });
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  void _showFormDialog({Map<String, dynamic>? existing}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _RawMaterialFormSheet(existing: existing),
    );
  }

  Future<void> _confirmDelete(BuildContext ctx, int id, String name) async {
    final confirm = await showDialog<bool>(
      context: ctx,
      builder: (d) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Hapus Bahan Baku?', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        content: Text('Bahan baku "$name" akan dihapus dan dicopot dari semua resep produk.',
            style: const TextStyle(fontSize: 13)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(d, false), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFDC2626)),
            onPressed: () => Navigator.pop(d, true),
            child: const Text('Hapus', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
    if (confirm == true && ctx.mounted) {
      final res = await ctx.read<MerchantController>().deleteRawMaterial(id);
      if (ctx.mounted) {
        ScaffoldMessenger.of(ctx).showSnackBar(SnackBar(
          content: Text(res['message'] ?? ''),
          backgroundColor: res['success'] == true ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
        ));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<MerchantController>();
    final materials = ctrl.rawMaterials;
    final filtered = _searchQuery.trim().isEmpty
        ? materials
        : materials.where((m) {
            final name = m['name']?.toString().toLowerCase() ?? '';
            final unit = m['unit']?.toString().toLowerCase() ?? '';
            final desc = m['description']?.toString().toLowerCase() ?? '';
            final q = _searchQuery.trim().toLowerCase();
            return name.contains(q) || unit.contains(q) || desc.contains(q);
          }).toList();

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
            const Text('Bahan Baku', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A))),
            Text('${materials.length} bahan terdaftar', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: Color(0xFF0F172A)),
            onPressed: () => ctrl.fetchRawMaterials(),
          ),
        ],
      ),
      body: ctrl.isRawMatsLoading
          ? const Center(child: CircularProgressIndicator())
          : materials.isEmpty
              ? _buildEmpty()
              : Column(
                  children: [
                    // Search bar
                    Container(
                      color: Colors.white,
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                      child: TextField(
                        controller: _searchCtrl,
                        onChanged: (val) => setState(() => _searchQuery = val),
                        decoration: InputDecoration(
                          hintText: 'Cari nama bahan baku...',
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
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF0F172A))),
                        ),
                      ),
                    ),
                    Expanded(
                      child: filtered.isEmpty
                          ? Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.search_off_rounded, size: 40, color: Color(0xFF94A3B8)),
                                  const SizedBox(height: 8),
                                  Text(
                                    'Tidak ada bahan dengan kata kunci "$_searchQuery"',
                                    style: const TextStyle(fontSize: 13, color: Color(0xFF64748B)),
                                  ),
                                ],
                              ),
                            )
                          : RefreshIndicator(
                              onRefresh: ctrl.fetchRawMaterials,
                              child: ListView.separated(
                                padding: const EdgeInsets.all(16),
                                itemCount: filtered.length,
                                separatorBuilder: (_, _) => const SizedBox(height: 10),
                                itemBuilder: (ctx, i) {
                                  final m = filtered[i] as Map;
                                  final id = int.tryParse(m['id']?.toString() ?? '0') ?? 0;
                                  final name = m['name']?.toString() ?? '';
                                  final unit = m['unit']?.toString() ?? 'gr';
                                  final price = double.tryParse(m['price_per_unit']?.toString() ?? '0') ?? 0;
                                  final stock = double.tryParse(m['stock_qty']?.toString() ?? '0') ?? 0;
                                  final desc = m['description']?.toString() ?? '';

                      return Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                          boxShadow: [
                            BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 6, offset: const Offset(0, 2)),
                          ],
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Icon
                            Container(
                              width: 42,
                              height: 42,
                              decoration: BoxDecoration(
                                color: const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: const Icon(Icons.science_outlined, color: Color(0xFF64748B), size: 20),
                            ),
                            const SizedBox(width: 12),
                            // Info & Badges
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Expanded(
                                        child: Text(
                                          name,
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Color(0xFF0F172A)),
                                          maxLines: 2,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                      const SizedBox(width: 4),
                                      // Actions
                                      Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          InkWell(
                                            onTap: () => _showFormDialog(existing: Map<String, dynamic>.from(m)),
                                            borderRadius: BorderRadius.circular(6),
                                            child: const Padding(
                                              padding: EdgeInsets.all(4),
                                              child: Icon(Icons.edit_outlined, size: 17, color: Color(0xFF2563EB)),
                                            ),
                                          ),
                                          const SizedBox(width: 4),
                                          InkWell(
                                            onTap: () => _confirmDelete(context, id, name),
                                            borderRadius: BorderRadius.circular(6),
                                            child: const Padding(
                                              padding: EdgeInsets.all(4),
                                              child: Icon(Icons.delete_outline_rounded, size: 17, color: Color(0xFFDC2626)),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                  if (desc.isNotEmpty) ...[
                                    const SizedBox(height: 2),
                                    Text(
                                      desc,
                                      style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ],
                                  const SizedBox(height: 8),
                                  Wrap(
                                    spacing: 6,
                                    runSpacing: 4,
                                    children: [
                                      _pill('Rp ${_fmtPrice(price)}/$unit', const Color(0xFF2563EB), const Color(0xFFEFF6FF)),
                                      _pill('Stok: ${_fmtQty(stock)} $unit', const Color(0xFF16A34A), const Color(0xFFF0FDF4)),
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
                ),
              ),
            ],
          ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showFormDialog(),
        backgroundColor: const Color(0xFF0F172A),
        icon: const Icon(Icons.add_rounded, color: Colors.white),
        label: const Text('Tambah Bahan', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(color: const Color(0xFFF1F5F9), shape: BoxShape.circle),
            child: const Icon(Icons.science_outlined, size: 48, color: Color(0xFF94A3B8)),
          ),
          const SizedBox(height: 16),
          const Text('Belum Ada Bahan Baku', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A))),
          const SizedBox(height: 6),
          const Text('Tambahkan bahan baku untuk menghitung\nHPP produk Anda secara otomatis.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12.5, color: Color(0xFF64748B), height: 1.5)),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF0F172A), padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12)),
            onPressed: () => _showFormDialog(),
            icon: const Icon(Icons.add_rounded, color: Colors.white),
            label: const Text('Tambah Bahan Baku', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _pill(String text, Color fg, Color bg) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
      child: Text(text, style: TextStyle(fontSize: 10.5, color: fg, fontWeight: FontWeight.w600)),
    );
  }

  String _fmtPrice(double v) {
    if (v >= 1000000) return '${(v / 1000000).toStringAsFixed(1)} Jt';
    if (v >= 1000) return '${(v / 1000).toStringAsFixed(0)} Rb';
    return v.toInt().toString();
  }

  String _fmtQty(double v) {
    if (v == v.toInt()) return v.toInt().toString();
    return v.toStringAsFixed(2);
  }
}

// ── Bottom Sheet Form Tambah / Edit Bahan Baku ─────────────────────────────
class _RawMaterialFormSheet extends StatefulWidget {
  final Map<String, dynamic>? existing;
  const _RawMaterialFormSheet({this.existing});

  @override
  State<_RawMaterialFormSheet> createState() => _RawMaterialFormSheetState();
}

class _RawMaterialFormSheetState extends State<_RawMaterialFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameCtrl;
  late TextEditingController _priceCtrl;
  late TextEditingController _stockCtrl;
  late TextEditingController _descCtrl;
  late TextEditingController _customUnitCtrl;
  String _selectedUnit = 'gr';
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final e = widget.existing;
    _nameCtrl  = TextEditingController(text: e?['name']?.toString() ?? '');
    _priceCtrl = TextEditingController(text: e?['price_per_unit']?.toString() ?? '');
    _stockCtrl = TextEditingController(text: e?['stock_qty']?.toString() ?? '0');
    _descCtrl  = TextEditingController(text: e?['description']?.toString() ?? '');
    _customUnitCtrl = TextEditingController();

    final existingUnit = e?['unit']?.toString() ?? 'gr';
    if (_kUnits.contains(existingUnit)) {
      _selectedUnit = existingUnit;
    } else {
      _selectedUnit = 'lainnya';
      _customUnitCtrl.text = existingUnit;
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _priceCtrl.dispose();
    _stockCtrl.dispose();
    _descCtrl.dispose();
    _customUnitCtrl.dispose();
    super.dispose();
  }

  String get _effectiveUnit =>
      _selectedUnit == 'lainnya' ? _customUnitCtrl.text.trim() : _selectedUnit;

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedUnit == 'lainnya' && _customUnitCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Masukkan nama satuan terlebih dahulu'), backgroundColor: Colors.red),
      );
      return;
    }
    setState(() => _saving = true);

    final data = <String, dynamic>{
      if (widget.existing != null) 'id': widget.existing!['id'],
      'name'           : _nameCtrl.text.trim(),
      'unit'           : _effectiveUnit,
      'price_per_unit' : double.tryParse(_priceCtrl.text.replaceAll(',', '.')) ?? 0,
      'stock_qty'      : double.tryParse(_stockCtrl.text.replaceAll(',', '.')) ?? 0,
      'description'    : _descCtrl.text.trim(),
    };

    final res = await context.read<MerchantController>().saveRawMaterial(data);
    setState(() => _saving = false);

    if (mounted) {
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(res['message'] ?? ''),
        backgroundColor: res['success'] == true ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
      ));
    }
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.existing != null;
    return Container(
      padding: EdgeInsets.only(
        left: 20, right: 20, top: 24,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.9),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Handle bar
              Center(
                child: Container(width: 40, height: 4,
                    decoration: BoxDecoration(color: const Color(0xFFE2E8F0), borderRadius: BorderRadius.circular(2))),
              ),
              const SizedBox(height: 20),
              Text(isEdit ? 'Edit Bahan Baku' : 'Tambah Bahan Baku',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF0F172A))),
              const SizedBox(height: 4),
              const Text('Isi harga per 1 satuan unit bahan baku',
                  style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
              const SizedBox(height: 20),

              // Nama
              _label('Nama Bahan Baku *'),
              TextFormField(
                controller: _nameCtrl,
                decoration: _inputDec('contoh: Susu Segar, Gula Pasir...'),
                validator: (v) => v?.trim().isEmpty == true ? 'Nama wajib diisi' : null,
              ),
              const SizedBox(height: 16),

              // Satuan
              _label('Satuan *'),
              DropdownButtonFormField<String>(
                initialValue: _selectedUnit,
                decoration: _inputDec(''),
                items: _kUnits.map((u) => DropdownMenuItem(value: u, child: Text(u))).toList(),
                onChanged: (v) => setState(() => _selectedUnit = v!),
              ),
              if (_selectedUnit == 'lainnya') ...[
                const SizedBox(height: 10),
                TextFormField(
                  controller: _customUnitCtrl,
                  decoration: _inputDec('Masukkan nama satuan (misal: ikat, biji)'),
                ),
              ],
              const SizedBox(height: 16),

              // Harga per unit
              _label('Harga per 1 $_effectiveUnit (Rp) *'),
              TextFormField(
                controller: _priceCtrl,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d.,]'))],
                decoration: _inputDec('contoh: 10000'),
                validator: (v) {
                  if (v?.trim().isEmpty == true) return 'Harga wajib diisi';
                  if ((double.tryParse(v!.replaceAll(',', '.')) ?? -1) < 0) return 'Harga tidak valid';
                  return null;
                },
              ),
              const SizedBox(height: 16),

              // Stok awal
              _label('Stok Saat Ini ($_effectiveUnit)'),
              TextFormField(
                controller: _stockCtrl,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d.,]'))],
                decoration: _inputDec('0'),
              ),
              const SizedBox(height: 16),

              // Keterangan
              _label('Keterangan (opsional)'),
              TextFormField(
                controller: _descCtrl,
                maxLines: 2,
                decoration: _inputDec('contoh: Susu UHT full cream, beli di pasar'),
              ),
              const SizedBox(height: 28),

              // Tombol Simpan
              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF0F172A),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  onPressed: _saving ? null : _save,
                  child: _saving
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : Text(isEdit ? 'Simpan Perubahan' : 'Tambah Bahan Baku',
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _label(String t) => Padding(
        padding: const EdgeInsets.only(bottom: 6),
        child: Text(t, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12.5, color: Color(0xFF374151))),
      );

  InputDecoration _inputDec(String hint) => InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: Color(0xFFCBD5E1), fontSize: 13),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF0F172A))),
        filled: true,
        fillColor: const Color(0xFFF8FAFC),
      );
}
