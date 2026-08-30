import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:image_picker/image_picker.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/uber_pill_button.dart';
import '../../../core/widgets/barcode_scanner_modal.dart';
import '../controllers/merchant_controller.dart';
import '../widgets/stock_input_modal.dart';

class ProductManagementScreen extends StatefulWidget {
  const ProductManagementScreen({super.key});

  @override
  State<ProductManagementScreen> createState() => _ProductManagementScreenState();
}

class _ProductManagementScreenState extends State<ProductManagementScreen> {
  final TextEditingController _searchCtrl = TextEditingController();
  String _searchQuery = '';

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
    super.dispose();
  }

  void _showProductFormModal(BuildContext context, {Map<String, dynamic>? product}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ProductFormBottomSheet(product: product),
    );
  }

  void _confirmDelete(BuildContext context, int productId, String productName) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        title: const Text('Hapus Menu?', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        content: Text('Apakah Anda yakin ingin menghapus "$productName" dari katalog resto Anda?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal', style: TextStyle(color: Color(0xFF64748B))),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryRed,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () async {
              Navigator.pop(ctx);
              final ok = await context.read<MerchantController>().deleteProduct(productId);
              if (ok && context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('Menu "$productName" berhasil dihapus!'), backgroundColor: AppTheme.primaryRed),
                );
              }
            },
            child: const Text('Hapus'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final merchantCtrl = context.watch<MerchantController>();
    final allProducts = merchantCtrl.products;

    final filtered = allProducts.where((p) {
      final name = (p['name'] ?? '').toString().toLowerCase();
      return name.contains(_searchQuery.toLowerCase());
    }).toList();

    return Column(
      children: [
        // Top Action Bar & Search
        Container(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
          color: Colors.white,
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Katalog Menu (${allProducts.length})',
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15, color: Color(0xFF0F172A)),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Scan Stok Masuk Button
                      GestureDetector(
                        onTap: () async {
                          final ok = await StockInputModal.scanAndOpen(context);
                          if (ok == true && context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Stok berhasil diperbarui!'),
                                backgroundColor: Color(0xFF059669),
                              ),
                            );
                          }
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 7.5),
                          margin: const EdgeInsets.only(right: 6),
                          decoration: BoxDecoration(
                            color: const Color(0xFF059669),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.qr_code_scanner_rounded, size: 14, color: Colors.white),
                              SizedBox(width: 4),
                              Text('Stok Masuk', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white)),
                            ],
                          ),
                        ),
                      ),
                      ElevatedButton.icon(
                        onPressed: () => _showProductFormModal(context),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppTheme.primaryRed,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7.5),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          elevation: 0,
                          minimumSize: Size.zero,
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                        icon: const Icon(Icons.add_rounded, size: 14),
                        label: const Text('Tambah', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _searchCtrl,
                onChanged: (val) => setState(() => _searchQuery = val),
                decoration: InputDecoration(
                  hintText: 'Cari nama menu / makanan...',
                  hintStyle: const TextStyle(fontSize: 12.5, color: Color(0xFF94A3B8)),
                  prefixIcon: const Icon(Icons.search_rounded, size: 20, color: Color(0xFF94A3B8)),
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
                  contentPadding: const EdgeInsets.symmetric(vertical: 10),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: AppTheme.primaryRed),
                  ),
                ),
              ),
            ],
          ),
        ),

        // Product List
        Expanded(
          child: merchantCtrl.isLoading
              ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryRed))
              : filtered.isEmpty
                  ? RefreshIndicator(
                      onRefresh: () => merchantCtrl.fetchProducts(),
                      child: ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        children: [
                          SizedBox(height: MediaQuery.of(context).size.height * 0.2),
                          Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(20),
                                  decoration: const BoxDecoration(
                                    color: Color(0xFFF1F5F9),
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(Icons.restaurant_menu_rounded, size: 48, color: Color(0xFF94A3B8)),
                                ),
                                const SizedBox(height: 14),
                                Text(
                                  _searchQuery.isNotEmpty
                                      ? 'Tidak ditemukan menu dengan kata kunci "$_searchQuery"'
                                      : 'Belum ada menu produk terdaftar',
                                  style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Color(0xFF64748B)),
                                ),
                                const SizedBox(height: 4),
                                const Text(
                                  'Tekan tombol "+ Tambah Menu" untuk membuat menu baru',
                                  style: TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8)),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: () => merchantCtrl.fetchProducts(),
                      child: ListView.builder(
                        padding: const EdgeInsets.all(14),
                        itemCount: filtered.length,
                        itemBuilder: (context, index) {
                          final product = filtered[index] is Map ? (filtered[index] as Map<String, dynamic>) : <String, dynamic>{};
                          return _buildProductCard(context, merchantCtrl, product);
                        },
                      ),
                    ),
        ),
      ],
    );
  }

  Widget _buildProductCard(BuildContext context, MerchantController ctrl, Map<String, dynamic> product) {
    final productId = int.tryParse(product['id']?.toString() ?? '0') ?? 0;
    final name = product['name']?.toString() ?? 'Menu';
    final price = double.tryParse(product['price']?.toString() ?? '0') ?? 0.0;
    final hpp   = double.tryParse(product['hpp']?.toString() ?? '0') ?? 0.0;
    final profit = hpp > 0 ? price - hpp : 0.0;
    final markupPct = hpp > 0 ? ((price - hpp) / hpp * 100) : 0.0;
    final discount = double.tryParse(product['discount']?.toString() ?? '0') ?? 0.0;
    final stock = int.tryParse(product['stock']?.toString() ?? '100') ?? 100;
    final isAvailable = stock > 0;
    final rawImg = product['image']?.toString();
    final imgUrl = ApiConstants.formatImageUrl(rawImg);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
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
          // Thumbnail
          Stack(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: CachedNetworkImage(
                  imageUrl: imgUrl,
                  width: 72,
                  height: 72,
                  fit: BoxFit.cover,
                  errorWidget: (context, url, error) => Container(
                    width: 72,
                    height: 72,
                    color: const Color(0xFFF1F5F9),
                    child: const Icon(Icons.fastfood_rounded, color: Color(0xFF94A3B8), size: 30),
                  ),
                ),
              ),
              if (discount > 0)
                Positioned(
                  top: 4,
                  left: 4,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryRed,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      '-${discount.toStringAsFixed(0)}%',
                      style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(width: 12),

          // Details
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        name,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Color(0xFF0F172A)),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    // Action Buttons (Edit / Delete)
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        IconButton(
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(),
                          icon: const Icon(Icons.edit_outlined, size: 18, color: Color(0xFF2563EB)),
                          tooltip: 'Edit Menu',
                          onPressed: () => _showProductFormModal(context, product: product),
                        ),
                        const SizedBox(width: 8),
                        IconButton(
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(),
                          icon: const Icon(Icons.delete_outline_rounded, size: 18, color: Color(0xFFDC2626)),
                          tooltip: 'Hapus Menu',
                          onPressed: () => _confirmDelete(context, productId, name),
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 3),

                // Price & Barcode
                Row(
                  children: [
                    Text(
                      CurrencyFormatter.formatRupiah(price),
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13, color: AppTheme.primaryRed),
                    ),
                    if (discount > 0) ...[
                      const SizedBox(width: 6),
                      Text(
                        CurrencyFormatter.formatRupiah(price / (1 - (discount / 100))),
                        style: const TextStyle(
                          fontSize: 11,
                          color: Color(0xFF94A3B8),
                          decoration: TextDecoration.lineThrough,
                        ),
                      ),
                    ],
                  ],
                ),
                if (product['barcode'] != null && product['barcode'].toString().trim().isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Row(
                    children: [
                      const Icon(Icons.qr_code_2_rounded, size: 13, color: Color(0xFF64748B)),
                      const SizedBox(width: 4),
                      Text(
                        product['barcode'].toString(),
                        style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF64748B), fontFamily: 'monospace'),
                      ),
                    ],
                  ),
                ],
                // HPP + Profit Row
                if (hpp > 0) ...[
                  const SizedBox(height: 5),
                  Wrap(
                    spacing: 5,
                    runSpacing: 4,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(color: const Color(0xFFFEF3C7), borderRadius: BorderRadius.circular(6)),
                        child: Text(
                          'HPP ${CurrencyFormatter.formatRupiah(hpp)}',
                          style: const TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: Color(0xFFB45309)),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(color: const Color(0xFFD1FAE5), borderRadius: BorderRadius.circular(6)),
                        child: Text(
                          '+${CurrencyFormatter.formatRupiah(profit)} (${markupPct.toStringAsFixed(0)}%)',
                          style: const TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: Color(0xFF065F46)),
                        ),
                      ),
                    ],
                  ),
                ],
                const SizedBox(height: 6),

                // In-Stock Switch & Active Toggle + Stok Masuk button
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 8,
                          height: 8,
                          decoration: BoxDecoration(
                            color: isAvailable ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 5),
                        Text(
                          isAvailable ? 'Stok: $stock' : 'Stok Habis',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: isAvailable ? const Color(0xFF15803D) : const Color(0xFF991B1B),
                          ),
                        ),
                      ],
                    ),
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        // Tombol Stok Masuk cepat
                        GestureDetector(
                          onTap: () => StockInputModal.openForProduct(context, product),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                            margin: const EdgeInsets.only(right: 6),
                            decoration: BoxDecoration(
                              color: const Color(0xFF059669),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.add_box_outlined, size: 13, color: Colors.white),
                                SizedBox(width: 3),
                                Text('Stok', style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: Colors.white)),
                              ],
                            ),
                          ),
                        ),
                        SizedBox(
                          height: 24,
                          width: 38,
                          child: Switch(
                            value: isAvailable,
                            activeThumbColor: const Color(0xFF16A34A),
                            activeTrackColor: const Color(0xFFDCFCE7),
                            onChanged: (val) {
                              ctrl.toggleProductStock(productId, val);
                            },
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ── BOTTOM SHEET MODAL: TAMBAH / EDIT MENU ──
class _ProductFormBottomSheet extends StatefulWidget {
  final Map<String, dynamic>? product;
  const _ProductFormBottomSheet({this.product});

  @override
  State<_ProductFormBottomSheet> createState() => _ProductFormBottomSheetState();
}

class _ProductFormBottomSheetState extends State<_ProductFormBottomSheet> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameCtrl;
  late TextEditingController _barcodeCtrl;
  late TextEditingController _priceCtrl;
  late TextEditingController _hppCtrl;
  late TextEditingController _discountCtrl;
  late TextEditingController _descCtrl;
  late TextEditingController _unitCtrl;
  File? _selectedImage;
  bool _isSaving = false;

  bool get _isEdit => widget.product != null;

  @override
  void initState() {
    super.initState();
    final p = widget.product ?? {};
    _nameCtrl = TextEditingController(text: p['name']?.toString() ?? '');
    _barcodeCtrl = TextEditingController(text: p['barcode']?.toString() ?? '');

    final rawPrice = p['price'];
    final parsedPrice = (rawPrice is num) ? rawPrice.toInt() : (double.tryParse(rawPrice?.toString() ?? '')?.toInt());
    _priceCtrl = TextEditingController(text: parsedPrice != null ? parsedPrice.toString() : (rawPrice?.toString() ?? ''));

    final rawHpp = p['hpp'];
    final parsedHppVal = (rawHpp is num) ? rawHpp.toDouble() : double.tryParse(rawHpp?.toString() ?? '');
    _hppCtrl = TextEditingController(text: (parsedHppVal != null && parsedHppVal > 0) ? parsedHppVal.toInt().toString() : '');

    final rawDiscount = p['discount'];
    final parsedDiscount = (rawDiscount is num) ? rawDiscount.toInt() : (double.tryParse(rawDiscount?.toString() ?? '')?.toInt());
    _discountCtrl = TextEditingController(text: parsedDiscount != null ? parsedDiscount.toString() : (rawDiscount?.toString() ?? '0'));

    _descCtrl = TextEditingController(text: p['description']?.toString() ?? '');
    _unitCtrl = TextEditingController(text: p['unit']?.toString() ?? 'porsi');
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _barcodeCtrl.dispose();
    _priceCtrl.dispose();
    _hppCtrl.dispose();
    _discountCtrl.dispose();
    _descCtrl.dispose();
    _unitCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickImage(ImageSource source) async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: source, maxWidth: 800, imageQuality: 80);
    if (picked != null) {
      setState(() => _selectedImage = File(picked.path));
    }
  }

  @override
  Widget build(BuildContext context) {
    final existingImg = widget.product?['image']?.toString();

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
      child: Form(
        key: _formKey,
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
                Text(
                  _isEdit ? 'Edit Menu / Produk' : 'Tambah Menu Baru',
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF64748B)),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const Divider(height: 1, color: Color(0xFFF1F5F9)),
            const SizedBox(height: 14),

            Expanded(
              child: ListView(
                children: [
                  // Image Picker Preview
                  Center(
                    child: GestureDetector(
                      onTap: () => _pickImage(ImageSource.gallery),
                      child: Container(
                        width: 120,
                        height: 100,
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: const Color(0xFFCBD5E1), style: BorderStyle.solid),
                        ),
                        child: _selectedImage != null
                            ? ClipRRect(
                                borderRadius: BorderRadius.circular(14),
                                child: Image.file(_selectedImage!, fit: BoxFit.cover),
                              )
                            : (existingImg != null && existingImg.isNotEmpty)
                                ? ClipRRect(
                                    borderRadius: BorderRadius.circular(14),
                                    child: CachedNetworkImage(
                                      imageUrl: ApiConstants.formatImageUrl(existingImg),
                                      fit: BoxFit.cover,
                                    ),
                                  )
                                : Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: const [
                                      Icon(Icons.add_photo_alternate_outlined, size: 28, color: Color(0xFF64748B)),
                                      SizedBox(height: 4),
                                      Text('Foto Menu', style: TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                                    ],
                                  ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Nama Menu
                  _buildInputLabel('Nama Menu / Produk *'),
                  TextFormField(
                    controller: _nameCtrl,
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Nama menu wajib diisi' : null,
                    decoration: _inputDecoration('Contoh: Ayam Geprek Sambal Korek'),
                  ),
                  const SizedBox(height: 12),

                  // Kode Barcode Produk (Untuk POS Kasir Scan)
                  _buildInputLabel('Kode Barcode / SKU Produk (Opsional)'),
                  TextFormField(
                    controller: _barcodeCtrl,
                    keyboardType: TextInputType.text,
                    decoration: InputDecoration(
                      hintText: 'Scan / Masukkan barcode (Contoh: 8991234567890)',
                      hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                      prefixIcon: const Icon(Icons.qr_code_scanner_rounded, size: 20, color: Color(0xFF64748B)),
                      suffixIcon: IconButton(
                        icon: const Icon(Icons.camera_alt_rounded, size: 20, color: Color(0xFF2563EB)),
                        tooltip: 'Scan dengan Kamera',
                        onPressed: () async {
                          final code = await BarcodeScannerModal.scan(context, title: 'Scan Barcode Produk');
                          if (code != null && code.isNotEmpty) {
                            setState(() {
                              _barcodeCtrl.text = code;
                            });
                          }
                        },
                      ),
                      filled: true,
                      fillColor: const Color(0xFFF8FAFC),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppTheme.primaryRed)),
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Harga, HPP & Diskon
                  Row(
                    children: [
                      Expanded(
                        flex: 3,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _buildInputLabel('Harga Jual (Rp) *'),
                            TextFormField(
                              controller: _priceCtrl,
                              keyboardType: TextInputType.number,
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Harga wajib diisi' : null,
                              decoration: _inputDecoration('Contoh: 18000'),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        flex: 2,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _buildInputLabel('Diskon (%)'),
                            TextFormField(
                              controller: _discountCtrl,
                              keyboardType: TextInputType.number,
                              decoration: _inputDecoration('0'),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // HPP (Harga Pokok Penjualan)
                  _buildInputLabel('HPP / Modal per Unit (Rp) — Opsional'),
                  TextFormField(
                    controller: _hppCtrl,
                    keyboardType: TextInputType.number,
                    onChanged: (_) => setState(() {}),
                    decoration: InputDecoration(
                      hintText: 'Contoh: 8000 (untuk hitung profit)',
                      hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                      prefixIcon: const Icon(Icons.price_change_outlined, size: 18, color: Color(0xFF64748B)),
                      filled: true,
                      fillColor: const Color(0xFFFFFBEB),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFFDE68A))),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFFDE68A))),
                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFD97706))),
                    ),
                  ),
                  const SizedBox(height: 6),
                  // Smart Markup Preset Chips
                  Wrap(
                    crossAxisAlignment: WrapCrossAlignment.center,
                    spacing: 6,
                    runSpacing: 6,
                    children: [
                      const Text(
                        'Preset Untung:',
                        style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF64748B)),
                      ),
                      _buildMarkupChip(20),
                      _buildMarkupChip(30),
                      _buildMarkupChip(50),
                      _buildMarkupChip(100),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Satuan Unit
                  _buildInputLabel('Satuan Porsi / Unit'),
                  TextFormField(
                    controller: _unitCtrl,
                    decoration: _inputDecoration('Contoh: porsi, box, gelas, pcs'),
                  ),
                  const SizedBox(height: 12),

                  // Deskripsi
                  _buildInputLabel('Deskripsi Singkat (Opsional)'),
                  TextFormField(
                    controller: _descCtrl,
                    maxLines: 2,
                    decoration: _inputDecoration('Jelaskan rasa, lauk pelengkap, atau level pedas...'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            UberPillButton(
              label: _isSaving ? 'Menyimpan...' : (_isEdit ? 'Simpan Perubahan' : 'Terbitkan Menu'),
              icon: Icons.check_circle_outline_rounded,
              onPressed: _isSaving
                  ? null
                  : () async {
                      if (!_formKey.currentState!.validate()) return;
                      setState(() => _isSaving = true);

                      final payload = <String, String>{
                        'name': _nameCtrl.text.trim(),
                        'barcode': _barcodeCtrl.text.trim(),
                        'price': _priceCtrl.text.trim(),
                        'hpp': _hppCtrl.text.trim().isEmpty ? '0' : _hppCtrl.text.trim(),
                        'discount': _discountCtrl.text.trim().isEmpty ? '0' : _discountCtrl.text.trim(),
                        'unit': _unitCtrl.text.trim().isEmpty ? 'porsi' : _unitCtrl.text.trim(),
                        'description': _descCtrl.text.trim(),
                      };
                      if (_isEdit) {
                        payload['id'] = widget.product!['id'].toString();
                        if (widget.product!['image'] != null && widget.product!['image'].toString().isNotEmpty) {
                          payload['existing_image'] = widget.product!['image'].toString();
                        }
                      }

                      final ok = await context.read<MerchantController>().saveProduct(
                            payload,
                            imagePath: _selectedImage?.path,
                          );

                      if (mounted) setState(() => _isSaving = false);

                      if (ok && context.mounted) {
                        Navigator.pop(context);
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(_isEdit ? 'Menu berhasil diperbarui!' : 'Menu baru berhasil ditambahkan ke etalase!'),
                            backgroundColor: const Color(0xFF10B981),
                          ),
                        );
                      }
                    },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInputLabel(String label) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 5),
      child: Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
    );
  }

  Widget _buildMarkupChip(double percent) {
    final hpp = double.tryParse(_hppCtrl.text.trim()) ?? 0.0;
    return InkWell(
      onTap: () {
        if (hpp <= 0) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Masukkan nominal modal HPP terlebih dahulu'),
              backgroundColor: Color(0xFFD97706),
              duration: Duration(seconds: 2),
            ),
          );
          return;
        }
        final calculatedPrice = (hpp * (1 + percent / 100)).round();
        setState(() {
          _priceCtrl.text = calculatedPrice.toString();
        });
      },
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3.5),
        decoration: BoxDecoration(
          color: const Color(0xFFEFF6FF),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: const Color(0xFFBFDBFE)),
        ),
        child: Text(
          '+${percent.toInt()}%',
          style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF1D4ED8)),
        ),
      ),
    );
  }

  InputDecoration _inputDecoration(String hint) {
    return InputDecoration(
      hintText: hint,
      hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
      filled: true,
      fillColor: const Color(0xFFF8FAFC),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppTheme.primaryRed)),
    );
  }
}
