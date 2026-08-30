import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/barcode_scanner_modal.dart';
import '../controllers/merchant_controller.dart';

/// Modal lengkap untuk Stok Masuk — dipanggil setelah barcode dipindai
/// atau bisa dibuka manual via tombol pada product card.
class StockInputModal extends StatefulWidget {
  /// Produk yang sudah diketahui (dari card), jika null akan lookup barcode dulu
  final Map<String, dynamic>? initialProduct;

  const StockInputModal({super.key, this.initialProduct});

  /// Factory: buka scanner dulu, lalu tampilkan modal jika barcode ketemu
  static Future<bool?> scanAndOpen(BuildContext context) async {
    // Capture controller dan navigator sebelum async
    final ctrl = context.read<MerchantController>();
    final nav  = Navigator.of(context);
    final sm   = ScaffoldMessenger.of(context);

    // 1. Pindai barcode
    final barcode = await BarcodeScannerModal.scan(
      context,
      title: 'Scan Produk untuk Input Stok',
    );
    if (barcode == null || barcode.isEmpty) return null;
    if (!context.mounted) return null;

    // 2. Tampilkan loading
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
        child: Card(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(16))),
          child: Padding(
            padding: EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                CircularProgressIndicator(color: AppTheme.primaryRed),
                SizedBox(height: 14),
                Text('Mencari produk...', style: TextStyle(fontSize: 13, color: Color(0xFF334155))),
              ],
            ),
          ),
        ),
      ),
    );

    // 3. Cari produk berdasarkan barcode
    final result = await ctrl.findProductByBarcode(barcode);
    if (context.mounted) nav.pop(); // tutup loading dialog

    if (!context.mounted) return null;

    if (result['found'] != true || result['product'] == null) {
      sm.showSnackBar(
        SnackBar(
          content: Row(
            children: [
              const Icon(Icons.search_off_rounded, color: Colors.white, size: 18),
              const SizedBox(width: 8),
              Expanded(child: Text('Produk dengan barcode "$barcode" tidak ditemukan di toko Anda.')),
            ],
          ),
          backgroundColor: const Color(0xFFDC2626),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return false;
    }

    // 4. Tampilkan modal stok masuk
    final product = result['product'] as Map<String, dynamic>;
    if (!context.mounted) return null;
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => StockInputModal(initialProduct: product),
    );
  }
  /// Buka langsung dari product card (tanpa scan)
  static Future<bool?> openForProduct(BuildContext context, Map<String, dynamic> product) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => StockInputModal(initialProduct: product),
    );
  }


  @override
  State<StockInputModal> createState() => _StockInputModalState();
}

class _StockInputModalState extends State<StockInputModal> with SingleTickerProviderStateMixin {
  final TextEditingController _qtyCtrl = TextEditingController(text: '0');
  final TextEditingController _hppCtrl = TextEditingController();

  late AnimationController _animCtrl;
  late Animation<double> _fadeAnim;
  late Animation<Offset> _slideAnim;

  bool _isSaving = false;

  // Data produk
  Map<String, dynamic>? _product;
  double _currentHpp = 0;
  double _currentPrice = 0;
  int _currentStock = 0;
  double _markupPct = 0;

  // Live preview
  double _previewNewPrice = 0;
  double _previewProfit = 0;
  int _previewNewStock = 0;

  @override
  void initState() {
    super.initState();
    _animCtrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 350));
    _fadeAnim = CurvedAnimation(parent: _animCtrl, curve: Curves.easeOut);
    _slideAnim = Tween<Offset>(begin: const Offset(0, 0.08), end: Offset.zero)
        .animate(CurvedAnimation(parent: _animCtrl, curve: Curves.easeOut));
    _animCtrl.forward();

    _initFromProduct(widget.initialProduct);

    _qtyCtrl.addListener(_recalcPreview);
    _hppCtrl.addListener(_recalcPreview);
  }

  void _initFromProduct(Map<String, dynamic>? p) {
    if (p == null) return;
    _product = p;
    _currentHpp   = double.tryParse(p['hpp']?.toString() ?? '0') ?? 0;
    _currentPrice = double.tryParse(p['price']?.toString() ?? '0') ?? 0;
    _currentStock = int.tryParse(p['stock']?.toString() ?? '0') ?? 0;
    _markupPct    = double.tryParse(p['markup_pct']?.toString() ?? '0') ?? 0;

    // Kalau markup_pct belum ada di data, hitung manual
    if (_markupPct == 0 && _currentHpp > 0) {
      _markupPct = ((_currentPrice - _currentHpp) / _currentHpp) * 100;
    }

    _hppCtrl.text = _currentHpp > 0 ? _currentHpp.toInt().toString() : '';
    _previewNewPrice = _currentPrice;
    _previewProfit   = _currentPrice - _currentHpp;
    _previewNewStock = _currentStock;
  }

  void _recalcPreview() {
    final qty     = int.tryParse(_qtyCtrl.text) ?? 0;
    final hppInput = double.tryParse(_hppCtrl.text) ?? _currentHpp;
    final markupRate = _markupPct / 100.0;

    double newPrice;
    if (hppInput > 0) {
      newPrice = hppInput * (1 + markupRate);
    } else {
      newPrice = _currentPrice;
    }

    setState(() {
      _previewNewPrice = newPrice;
      _previewProfit   = (hppInput > 0) ? (newPrice - hppInput) : (_currentPrice - _currentHpp);
      _previewNewStock = _currentStock + qty;
    });
  }

  @override
  void dispose() {
    _animCtrl.dispose();
    _qtyCtrl.dispose();
    _hppCtrl.dispose();
    super.dispose();
  }

  String _fmtCurrency(double v) => CurrencyFormatter.formatRupiah(v);

  @override
  Widget build(BuildContext context) {
    final p = _product;
    final imgUrl = ApiConstants.formatImageUrl(p?['image']?.toString());

    return FadeTransition(
      opacity: _fadeAnim,
      child: SlideTransition(
        position: _slideAnim,
        child: Container(
          constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.90),
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Drag handle
              Center(
                child: Container(
                  width: 36, height: 4,
                  decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(10)),
                ),
              ),
              const SizedBox(height: 16),

              // Header
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: const Color(0xFF059669).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.input_rounded, color: Color(0xFF059669), size: 22),
                  ),
                  const SizedBox(width: 10),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Input Stok Masuk', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                        Text('Perbarui stok & HPP terkini produk', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF64748B)),
                    onPressed: () => Navigator.pop(context, false),
                  ),
                ],
              ),
              const Divider(height: 20, color: Color(0xFFF1F5F9)),

              Expanded(
                child: ListView(
                  children: [
                    // Product Preview Card
                    if (p != null) _buildProductPreviewCard(p, imgUrl),
                    const SizedBox(height: 16),

                    // Live Info Strip
                    _buildLiveInfoStrip(),
                    const SizedBox(height: 16),

                    // Qty Input
                    _buildLabel('Jumlah Stok Masuk (unit) *'),
                    _buildQtyField(),
                    const SizedBox(height: 12),

                    // HPP Input
                    _buildLabel('HPP Terbaru per Unit (Rp)'),
                    _buildHppField(),
                    const SizedBox(height: 4),
                    if (_markupPct > 0)
                      Padding(
                        padding: const EdgeInsets.only(left: 4),
                        child: Text(
                          'Markup saat ini ${_markupPct.toStringAsFixed(1)}% akan dipertahankan → harga jual otomatis disesuaikan',
                          style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontStyle: FontStyle.italic),
                        ),
                      ),
                    const SizedBox(height: 16),

                    // Preview Calculation Card
                    _buildPreviewCard(),
                    const SizedBox(height: 20),
                  ],
                ),
              ),

              // Save Button
              SafeArea(
                top: false,
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF059669),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 15),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      elevation: 0,
                    ),
                    icon: _isSaving
                        ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Icon(Icons.check_circle_outline_rounded, size: 20),
                    label: Text(
                      _isSaving ? 'Menyimpan...' : 'Simpan Stok Masuk',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                    ),
                    onPressed: _isSaving ? null : _onSave,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildProductPreviewCard(Map<String, dynamic> p, String imgUrl) {
    final name    = p['name']?.toString() ?? '-';
    final barcode = p['barcode']?.toString() ?? '';

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: imgUrl.isNotEmpty
                ? CachedNetworkImage(imageUrl: imgUrl, width: 56, height: 56, fit: BoxFit.cover,
                    errorWidget: (context, url, err) => _defaultImg())
                : _defaultImg(),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name, style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)), maxLines: 1, overflow: TextOverflow.ellipsis),
                if (barcode.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Row(
                    children: [
                      const Icon(Icons.qr_code_2_rounded, size: 12, color: Color(0xFF64748B)),
                      const SizedBox(width: 3),
                      Text(barcode, style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontFamily: 'monospace')),
                    ],
                  ),
                ],
                const SizedBox(height: 4),
                Row(
                  children: [
                    _chipBadge('Stok: $_currentStock', const Color(0xFF2563EB), const Color(0xFFEFF6FF)),
                    const SizedBox(width: 6),
                    _chipBadge(_fmtCurrency(_currentPrice), AppTheme.primaryRed, const Color(0xFFFFF7F7)),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _defaultImg() => Container(
    width: 56, height: 56, color: const Color(0xFFF1F5F9),
    child: const Icon(Icons.fastfood_rounded, color: Color(0xFF94A3B8), size: 26),
  );

  Widget _chipBadge(String text, Color textColor, Color bgColor) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(8)),
      child: Text(text, style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: textColor)),
    );
  }

  Widget _buildLiveInfoStrip() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        gradient: const LinearGradient(colors: [Color(0xFF0F172A), Color(0xFF1E3A5F)]),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _infoItem('HPP Sekarang', _currentHpp > 0 ? _fmtCurrency(_currentHpp) : 'Belum diset', Colors.white, const Color(0xFFFBBF24)),
          _vDivider(),
          _infoItem('Harga Jual', _fmtCurrency(_currentPrice), Colors.white, const Color(0xFF34D399)),
          _vDivider(),
          _infoItem('Markup', '${_markupPct.toStringAsFixed(1)}%', Colors.white, const Color(0xFF60A5FA)),
        ],
      ),
    );
  }

  Widget _infoItem(String label, String val, Color labelColor, Color valColor) {
    return Column(
      children: [
        Text(label, style: TextStyle(fontSize: 9.5, color: labelColor.withValues(alpha: 0.7))),
        const SizedBox(height: 2),
        Text(val, style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: valColor)),
      ],
    );
  }

  Widget _vDivider() => Container(width: 1, height: 30, color: Colors.white.withValues(alpha: 0.15));

  Widget _buildLabel(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 6),
    child: Text(text, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
  );

  Widget _buildQtyField() {
    return Row(
      children: [
        // Minus button
        _qtyBtn(Icons.remove_rounded, () {
          final v = int.tryParse(_qtyCtrl.text) ?? 0;
          if (v > 0) _qtyCtrl.text = (v - 1).toString();
        }),
        const SizedBox(width: 10),
        Expanded(
          child: TextFormField(
            controller: _qtyCtrl,
            keyboardType: TextInputType.number,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
            decoration: InputDecoration(
              filled: true,
              fillColor: const Color(0xFFF8FAFC),
              contentPadding: const EdgeInsets.symmetric(vertical: 12),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
              focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF059669))),
              suffixText: 'unit',
              suffixStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
            ),
          ),
        ),
        const SizedBox(width: 10),
        // Plus button
        _qtyBtn(Icons.add_rounded, () {
          final v = int.tryParse(_qtyCtrl.text) ?? 0;
          _qtyCtrl.text = (v + 1).toString();
        }),
      ],
    );
  }

  Widget _qtyBtn(IconData icon, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 44, height: 44,
        decoration: BoxDecoration(
          color: const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Icon(icon, size: 22, color: const Color(0xFF334155)),
      ),
    );
  }

  Widget _buildHppField() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TextFormField(
          controller: _hppCtrl,
          keyboardType: TextInputType.number,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          decoration: InputDecoration(
            hintText: _currentHpp > 0 ? 'HPP lama: ${_currentHpp.toInt()}' : 'Contoh: 8000',
            hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
            prefixIcon: const Padding(
              padding: EdgeInsets.only(left: 12, right: 8),
              child: Text('Rp', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF334155))),
            ),
            prefixIconConstraints: const BoxConstraints(minWidth: 0, minHeight: 0),
            filled: true,
            fillColor: const Color(0xFFF8FAFC),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
            enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
            focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF059669))),
          ),
        ),
        const SizedBox(height: 6),
        Wrap(
          crossAxisAlignment: WrapCrossAlignment.center,
          spacing: 6,
          runSpacing: 6,
          children: [
            const Text(
              'Preset Untung:',
              style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF64748B)),
            ),
            _buildStockMarkupChip(20),
            _buildStockMarkupChip(30),
            _buildStockMarkupChip(50),
            _buildStockMarkupChip(100),
          ],
        ),
      ],
    );
  }

  Widget _buildStockMarkupChip(double percent) {
    return InkWell(
      onTap: () {
        final hppInput = double.tryParse(_hppCtrl.text) ?? _currentHpp;
        if (hppInput <= 0) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Masukkan nominal modal HPP terlebih dahulu'),
              backgroundColor: Color(0xFFD97706),
              duration: Duration(seconds: 2),
            ),
          );
          return;
        }
        final newPrice = (hppInput * (1 + percent / 100)).roundToDouble();
        final qty = int.tryParse(_qtyCtrl.text) ?? 0;
        setState(() {
          _markupPct = percent;
          _previewNewPrice = newPrice;
          _previewProfit = newPrice - hppInput;
          _previewNewStock = _currentStock + qty;
        });
      },
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3.5),
        decoration: BoxDecoration(
          color: const Color(0xFFECFDF5),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: const Color(0xFFA7F3D0)),
        ),
        child: Text(
          '+${percent.toInt()}%',
          style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF059669)),
        ),
      ),
    );
  }

  Widget _buildPreviewCard() {
    final hppInput = double.tryParse(_hppCtrl.text) ?? _currentHpp;
    final hppChanged = hppInput > 0 && hppInput != _currentHpp;
    final qty = int.tryParse(_qtyCtrl.text) ?? 0;

    return AnimatedContainer(
      duration: const Duration(milliseconds: 280),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFF0FDF4),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFBBF7D0), width: 1.5),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.calculate_outlined, size: 16, color: Color(0xFF059669)),
              const SizedBox(width: 6),
              const Text('Ringkasan Update', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF065F46))),
              const Spacer(),
              if (hppChanged)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                  decoration: BoxDecoration(color: const Color(0xFFFEF3C7), borderRadius: BorderRadius.circular(8)),
                  child: const Text('HPP Berubah', style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: Color(0xFFB45309))),
                ),
            ],
          ),
          const Divider(height: 14, color: Color(0xFFD1FAE5)),
          _previewRow('Stok Sebelumnya', '$_currentStock unit', const Color(0xFF6B7280)),
          _previewRow('Stok Masuk', '+ $qty unit', const Color(0xFF2563EB)),
          _previewRow('Stok Baru', '$_previewNewStock unit', const Color(0xFF059669), bold: true),
          if (hppChanged) ...[
            const Divider(height: 14, color: Color(0xFFD1FAE5)),
            _previewRow('HPP Lama', _fmtCurrency(_currentHpp), const Color(0xFF9CA3AF), strike: true),
            _previewRow('HPP Baru', _fmtCurrency(hppInput), const Color(0xFFDC2626), bold: true),
            _previewRow('Markup (tetap)', '${_markupPct.toStringAsFixed(1)}%', const Color(0xFF6B7280)),
            _previewRow('Harga Jual Baru', _fmtCurrency(_previewNewPrice), const Color(0xFF059669), bold: true),
            _previewRow('Profit / unit', _fmtCurrency(_previewProfit), const Color(0xFF7C3AED)),
          ] else if (_currentHpp > 0) ...[
            const Divider(height: 14, color: Color(0xFFD1FAE5)),
            _previewRow('HPP', _fmtCurrency(_currentHpp), const Color(0xFF6B7280)),
            _previewRow('Harga Jual', _fmtCurrency(_currentPrice), const Color(0xFF059669)),
            _previewRow('Profit / unit', _fmtCurrency(_currentPrice - _currentHpp), const Color(0xFF7C3AED)),
          ],
        ],
      ),
    );
  }

  Widget _previewRow(String label, String val, Color valColor, {bool bold = false, bool strike = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 11.5, color: Color(0xFF374151))),
          Text(
            val,
            style: TextStyle(
              fontSize: 12,
              fontWeight: bold ? FontWeight.bold : FontWeight.w500,
              color: valColor,
              decoration: strike ? TextDecoration.lineThrough : null,
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _onSave() async {
    final qty = int.tryParse(_qtyCtrl.text) ?? 0;
    if (qty <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Jumlah stok harus lebih dari 0!'), backgroundColor: Color(0xFFDC2626)),
      );
      return;
    }

    final productId = int.tryParse(_product?['id']?.toString() ?? '0') ?? 0;
    if (productId <= 0) return;

    final hppInput = double.tryParse(_hppCtrl.text) ?? 0;

    setState(() => _isSaving = true);
    final ctrl = context.read<MerchantController>();
    final result = await ctrl.stockIn(
      productId: productId,
      qtyIn: qty,
      newHpp: hppInput,
    );
    if (mounted) setState(() => _isSaving = false);

    if (!mounted) return;

    if (result['success'] == true) {
      final data = result['data'] as Map<String, dynamic>?;
      Navigator.pop(context, true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Row(
            children: [
              const Icon(Icons.check_circle_rounded, color: Colors.white, size: 18),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  data != null
                      ? '${data['product_name']} — Stok +${data['qty_added']}, total ${data['new_stock']} unit'
                      : result['message'] ?? 'Stok berhasil diperbarui!',
                  style: const TextStyle(fontSize: 12),
                ),
              ),
            ],
          ),
          backgroundColor: const Color(0xFF059669),
          behavior: SnackBarBehavior.floating,
          duration: const Duration(seconds: 3),
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Gagal menyimpan stok.'),
          backgroundColor: const Color(0xFFDC2626),
        ),
      );
    }
  }
}
