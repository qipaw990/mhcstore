import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/uber_pill_button.dart';
import '../../../core/widgets/app_alert.dart';
import '../../../core/widgets/require_auth_widget.dart';
import '../controllers/customer_controller.dart';

class ProductDetailModal extends StatefulWidget {
  final Map<String, dynamic> product;

  const ProductDetailModal({super.key, required this.product});

  static void show(BuildContext context, Map<String, dynamic> product) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => ProductDetailModal(product: product),
    );
  }

  @override
  State<ProductDetailModal> createState() => _ProductDetailModalState();
}

class _ProductDetailModalState extends State<ProductDetailModal> {
  int _quantity = 1;
  final TextEditingController _notesController = TextEditingController();

  @override
  void dispose() {
    _notesController.dispose();
    super.dispose();
  }

  String _getFoodImage(Map<String, dynamic> product) {
    final rawImg = product['image']?.toString();
    if (rawImg != null && rawImg.isNotEmpty && !rawImg.contains('null')) {
      return ApiConstants.formatImageUrl(rawImg);
    }
    final name = (product['name'] ?? product['product_name'] ?? '').toString().toLowerCase();
    if (name.contains('ayam') || name.contains('chick')) {
      return 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=600&q=80';
    } else if (name.contains('nasi') || name.contains('rice')) {
      return 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600&q=80';
    } else if (name.contains('kopi') || name.contains('coffee') || name.contains('boba') || name.contains('es')) {
      return 'https://images.unsplash.com/photo-1541167760496-1628856ab772?w=600&q=80';
    } else if (name.contains('seblak') || name.contains('bakso') || name.contains('mie') || name.contains('ramen')) {
      return 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=600&q=80';
    }
    return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80';
  }

  @override
  Widget build(BuildContext context) {
    final customerCtrl = context.watch<CustomerController>();
    final product = widget.product;
    final productId = int.tryParse(product['id']?.toString() ?? product['product_id']?.toString() ?? '0') ?? 0;
    final name = product['name'] ?? product['product_name'] ?? 'Menu Kuliner';
    final storeName = product['store_name'] ?? product['store'] ?? 'Mitra CicalengkaGO';
    final price = double.tryParse(product['price']?.toString() ?? '0') ?? 0.0;
    final finalPrice = double.tryParse(product['final_price']?.toString() ?? price.toString()) ?? price;
    final description = (product['description'] != null && product['description'].toString().trim().isNotEmpty)
        ? product['description'].toString()
        : 'Olahan kuliner lezat khas Cicalengka yang disiapkan dengan bahan-bahan segar berkualitas dan cita rasa istimewa.';
    final imgUrl = _getFoodImage(product);
    final totalPrice = finalPrice * _quantity;

    return Container(
      constraints: BoxConstraints(
        maxHeight: MediaQuery.of(context).size.height * 0.85,
      ),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      child: ClipRRect(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Drag Indicator
            Container(
              margin: const EdgeInsets.only(top: 10, bottom: 4),
              width: 36,
              height: 4,
              decoration: BoxDecoration(
                color: const Color(0xFFCBD5E1),
                borderRadius: BorderRadius.circular(2),
              ),
            ),

            Expanded(
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Product Cover Image with Close Button
                    Stack(
                      children: [
                        CachedNetworkImage(
                          imageUrl: imgUrl,
                          height: 220,
                          width: double.infinity,
                          fit: BoxFit.cover,
                          errorWidget: (context, url, error) => Container(
                            height: 220,
                            color: const Color(0xFFF1F5F9),
                            child: const Center(
                              child: Icon(Icons.fastfood_rounded, size: 60, color: AppTheme.inkBlack),
                            ),
                          ),
                        ),
                        Positioned(
                          top: 12,
                          right: 12,
                          child: CircleAvatar(
                            backgroundColor: Colors.black.withValues(alpha: 0.6),
                            child: IconButton(
                              icon: const Icon(Icons.close_rounded, color: Colors.white, size: 20),
                              onPressed: () => Navigator.pop(context),
                            ),
                          ),
                        ),
                      ],
                    ),

                    Padding(
                      padding: const EdgeInsets.all(20.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Store Name Tag
                          Row(
                            children: [
                              const Icon(Icons.storefront_rounded, size: 14, color: Color(0xFF64748B)),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  storeName,
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 6),

                          // Product Name
                          Text(
                            name,
                            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                          ),
                          const SizedBox(height: 8),

                          // Price
                          Row(
                            children: [
                              Text(
                                CurrencyFormatter.formatRupiah(finalPrice),
                                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppTheme.inkBlack),
                              ),
                              if (price > finalPrice) ...[
                                const SizedBox(width: 8),
                                Text(
                                  CurrencyFormatter.formatRupiah(price),
                                  style: const TextStyle(
                                    fontSize: 13,
                                    color: Color(0xFF94A3B8),
                                    decoration: TextDecoration.lineThrough,
                                  ),
                                ),
                              ],
                            ],
                          ),

                          const SizedBox(height: 16),
                          const Divider(color: Color(0xFFF1F5F9)),
                          const SizedBox(height: 12),

                          // Description Header & Body
                          const Text(
                            'Deskripsi Menu',
                            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            description,
                            style: const TextStyle(fontSize: 13, color: Color(0xFF475569), height: 1.5),
                          ),

                          const SizedBox(height: 20),
                          const Divider(color: Color(0xFFF1F5F9)),
                          const SizedBox(height: 12),

                          // Special Notes Textfield
                          const Text(
                            'Catatan Khusus (Opsional)',
                            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                          ),
                          const SizedBox(height: 8),
                          TextField(
                            controller: _notesController,
                            decoration: InputDecoration(
                              hintText: 'Contoh: Pedas banget, sambal dipisah, dll.',
                              hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                              filled: true,
                              fillColor: const Color(0xFFF8FAFC),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
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
                                borderSide: const BorderSide(color: AppTheme.inkBlack, width: 1.5),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // Bottom Action Bar with Quantity Stepper & Add Button
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.05),
                    blurRadius: 10,
                    offset: const Offset(0, -3),
                  ),
                ],
              ),
              child: SafeArea(
                child: Row(
                  children: [
                    // Quantity Controls
                    Container(
                      height: 44,
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(22),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Row(
                        children: [
                          IconButton(
                            constraints: const BoxConstraints(minWidth: 34, minHeight: 34),
                            padding: EdgeInsets.zero,
                            icon: const Icon(Icons.remove_rounded, size: 18, color: AppTheme.inkBlack),
                            onPressed: () {
                              if (_quantity > 1) {
                                setState(() => _quantity--);
                              }
                            },
                          ),
                          Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 4),
                            child: Text(
                              '$_quantity',
                              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                            ),
                          ),
                          IconButton(
                            constraints: const BoxConstraints(minWidth: 34, minHeight: 34),
                            padding: EdgeInsets.zero,
                            icon: const Icon(Icons.add_rounded, size: 18, color: AppTheme.inkBlack),
                            onPressed: () {
                              setState(() => _quantity++);
                            },
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),

                    // Add to Cart Button
                    Expanded(
                      child: UberPillButton(
                        label: 'Tambah • ${CurrencyFormatter.formatRupiah(totalPrice)}',
                        icon: Icons.shopping_bag_outlined,
                        paddingHorizontal: 12,
                        fullWidth: true,
                        onPressed: () async {
                          if (!RequireAuthWidget.check(context)) {
                            return;
                          }
                          await customerCtrl.addToCart(
                            productId,
                            _quantity,
                            notes: _notesController.text,
                          );
                          if (context.mounted) {
                            Navigator.pop(context);
                            AppAlert.showCartAdded(
                              context,
                              productName: name,
                              quantity: _quantity,
                            );
                          }
                        },
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
