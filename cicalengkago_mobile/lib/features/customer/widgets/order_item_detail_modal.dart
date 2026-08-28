import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';

class OrderItemDetailModal extends StatelessWidget {
  final Map<dynamic, dynamic> item;
  final String? storeName;
  final String? storeLogo;

  const OrderItemDetailModal({
    super.key,
    required this.item,
    this.storeName,
    this.storeLogo,
  });

  static void show(BuildContext context, Map<dynamic, dynamic> item, {String? storeName, String? storeLogo}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => OrderItemDetailModal(item: item, storeName: storeName, storeLogo: storeLogo),
    );
  }

  @override
  Widget build(BuildContext context) {
    final name = (item['product_name'] ??
            item['name'] ??
            item['title'] ??
            item['item_name'] ??
            (item['product'] is Map ? item['product']['name'] : null) ??
            'Menu Kuliner')
        .toString();

    final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;
    final price = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
    final itemTotal = price * qty;

    final variantText = item['variant']?.toString() ??
        item['variation_name']?.toString() ??
        '';

    final originStore = item['store_name']?.toString() ?? storeName ?? 'Mitra Resto CicalengkaGO';

    final rawImg = item['product_image'] ?? item['image'] ?? (item['product'] is Map ? item['product']['image'] : null);
    final imgUrl = rawImg != null && rawImg.toString().isNotEmpty
        ? ApiConstants.formatImageUrl(rawImg.toString())
        : null;

    final rawStoreLogo = storeLogo ?? item['store_logo'] ?? item['logo'];
    final storeLogoUrl = (rawStoreLogo != null && rawStoreLogo.toString().isNotEmpty)
        ? ApiConstants.formatImageUrl(rawStoreLogo.toString())
        : null;

    final notes = item['notes']?.toString() ?? item['order_notes']?.toString() ?? '';

    return Container(
      constraints: BoxConstraints(
        maxHeight: MediaQuery.of(context).size.height * 0.75,
      ),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Drag handle indicator
          Center(
            child: Container(
              margin: const EdgeInsets.only(top: 10, bottom: 6),
              width: 36,
              height: 4,
              decoration: BoxDecoration(
                color: const Color(0xFFCBD5E1),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),

          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Product Image Card Header
                  Center(
                    child: Container(
                      width: 120,
                      height: 120,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.08),
                            blurRadius: 15,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(20),
                        child: imgUrl != null
                            ? CachedNetworkImage(
                                imageUrl: imgUrl,
                                fit: BoxFit.cover,
                                errorWidget: (_, __, ___) => Container(
                                  color: const Color(0xFFFEF2F2),
                                  child: const Icon(Icons.fastfood_rounded, size: 50, color: AppTheme.primaryRed),
                                ),
                              )
                            : Container(
                                color: const Color(0xFFFEF2F2),
                                child: const Icon(Icons.restaurant_rounded, size: 50, color: AppTheme.primaryRed),
                              ),
                      ),
                    ),
                  ),

                  const SizedBox(height: 18),

                  // Store Tag
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      if (storeLogoUrl != null)
                        ClipRRect(
                          borderRadius: BorderRadius.circular(6),
                          child: CachedNetworkImage(
                            imageUrl: storeLogoUrl,
                            width: 20,
                            height: 20,
                            fit: BoxFit.cover,
                            errorWidget: (_, __, ___) => const Icon(Icons.storefront_rounded, size: 14, color: AppTheme.primaryRed),
                          ),
                        )
                      else
                        const Icon(Icons.storefront_rounded, size: 14, color: AppTheme.primaryRed),
                      const SizedBox(width: 6),
                      Flexible(
                        child: Text(
                          originStore,
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppTheme.primaryRed),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 8),

                  // Product Title
                  Center(
                    child: Text(
                      name,
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                    ),
                  ),

                  const SizedBox(height: 16),
                  const Divider(color: Color(0xFFF1F5F9)),
                  const SizedBox(height: 12),

                  // Order Item Details Table
                  const Text('Detail Pesanan', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF64748B), letterSpacing: 0.5)),
                  const SizedBox(height: 10),

                  // Quantity Row
                  _buildDetailRow('Jumlah', '${qty}x Porsi'),

                  // Price per Item Row
                  if (price > 0) ...[
                    const SizedBox(height: 8),
                    _buildDetailRow('Harga Satuan', CurrencyFormatter.formatRupiah(price)),
                    const SizedBox(height: 8),
                    _buildDetailRow('Subtotal Item', CurrencyFormatter.formatRupiah(itemTotal), isBold: true),
                  ],

                  // Variant Row if available
                  if (variantText.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    _buildDetailRow('Varian / Opsi', variantText),
                  ],

                  // Notes if available
                  if (notes.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFFBEB),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFFCD34D)),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Icon(Icons.edit_note_rounded, color: Color(0xFFD97706), size: 18),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('Catatan Item:', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFFB45309))),
                                const SizedBox(height: 2),
                                Text(notes, style: const TextStyle(fontSize: 12, color: Color(0xFF92400E))),
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

          // Close Button
          Padding(
            padding: const EdgeInsets.all(16),
            child: SafeArea(
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppTheme.inkBlack,
                  foregroundColor: Colors.white,
                  minimumSize: const Size(double.infinity, 46),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
                ),
                onPressed: () => Navigator.pop(context),
                child: const Text('Tutup', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailRow(String label, String value, {bool isBold = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
        const SizedBox(width: 8),
        Flexible(
          child: Text(
            value,
            textAlign: TextAlign.end,
            style: TextStyle(
              fontSize: 12.5,
              fontWeight: isBold ? FontWeight.w900 : FontWeight.w600,
              color: isBold ? AppTheme.primaryRed : AppTheme.inkBlack,
            ),
          ),
        ),
      ],
    );
  }
}
