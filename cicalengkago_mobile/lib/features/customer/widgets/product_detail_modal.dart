import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
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
  bool _isLoadingReviews = true;
  List<dynamic> _reviews = [];
  double _avgRating = 5.0;
  int _reviewsCount = 0;
  Map<dynamic, dynamic> _ratingBreakdown = {5: 0, 4: 0, 3: 0, 2: 0, 1: 0};

  @override
  void initState() {
    super.initState();
    final initialRating = double.tryParse(widget.product['rating']?.toString() ?? widget.product['avg_rating']?.toString() ?? '5.0') ?? 5.0;
    final initialCount = int.tryParse(widget.product['reviews_count']?.toString() ?? '0') ?? 0;
    _avgRating = initialRating;
    _reviewsCount = initialCount;

    final pid = int.tryParse(widget.product['id']?.toString() ?? widget.product['product_id']?.toString() ?? '0') ?? 0;
    if (pid > 0) {
      _fetchReviews(pid);
    } else {
      _isLoadingReviews = false;
    }
  }

  Future<void> _fetchReviews(int productId) async {
    try {
      final res = await ApiService.get('${ApiConstants.productDetail}/$productId');
      if (mounted && res['success'] == true && res['data'] != null) {
        final data = res['data'];
        setState(() {
          _reviews = (data['reviews'] as List<dynamic>?) ?? [];
          _avgRating = double.tryParse(data['avg_rating']?.toString() ?? '5.0') ?? 5.0;
          _reviewsCount = int.tryParse(data['reviews_count']?.toString() ?? _reviews.length.toString()) ?? _reviews.length;
          if (data['rating_breakdown'] is Map) {
            _ratingBreakdown = data['rating_breakdown'];
          }
          _isLoadingReviews = false;
        });
        return;
      }
    } catch (e) {
      debugPrint('[ProductDetailModal] Error fetching reviews: $e');
    }

    if (mounted) {
      setState(() {
        _isLoadingReviews = false;
      });
    }
  }

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
        maxHeight: MediaQuery.of(context).size.height * 0.90,
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
                            backgroundColor: Colors.black.withOpacity(0.6),
                            child: IconButton(
                              icon: const Icon(Icons.close_rounded, color: Colors.white, size: 20),
                              onPressed: () => Navigator.pop(context),
                            ),
                          ),
                        ),
                        // Rating Chip on top of image
                        Positioned(
                          bottom: 12,
                          left: 12,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                            decoration: BoxDecoration(
                              color: Colors.black.withOpacity(0.75),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.star_rounded, color: Color(0xFFFBBF24), size: 16),
                                const SizedBox(width: 4),
                                Text(
                                  _avgRating.toStringAsFixed(1),
                                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  '($_reviewsCount ulasan)',
                                  style: TextStyle(color: Colors.white.withOpacity(0.8), fontSize: 11),
                                ),
                              ],
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

                          // ==========================================
                          // SYNCHRONIZED REVIEWS SECTION
                          // ==========================================
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text(
                                'Penilaian & Ulasan',
                                style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFFEF3C7),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Row(
                                  children: [
                                    const Icon(Icons.star_rounded, size: 14, color: Color(0xFFD97706)),
                                    const SizedBox(width: 4),
                                    Text(
                                      '${_avgRating.toStringAsFixed(1)} / 5.0',
                                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFFB45309)),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),

                          if (_isLoadingReviews) ...[
                            const Center(
                              child: Padding(
                                padding: EdgeInsets.all(20),
                                child: SizedBox(
                                  width: 24,
                                  height: 24,
                                  child: CircularProgressIndicator(strokeWidth: 2.5, color: AppTheme.primaryRed),
                                ),
                              ),
                            ),
                          ] else if (_reviews.isEmpty) ...[
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF8FAFC),
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: const Color(0xFFE2E8F0)),
                              ),
                              child: Column(
                                children: [
                                  Icon(Icons.rate_review_outlined, size: 36, color: Colors.grey.shade400),
                                  const SizedBox(height: 8),
                                  const Text(
                                    'Belum Ada Ulasan Menu',
                                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF334155)),
                                  ),
                                  const SizedBox(height: 4),
                                  const Text(
                                    'Pesan menu lezat ini dan jadilah yang pertama memberikan ulasan!',
                                    style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                    textAlign: TextAlign.center,
                                  ),
                                ],
                              ),
                            ),
                          ] else ...[
                            // Summary Score Card
                            Container(
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF8FAFC),
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: const Color(0xFFE2E8F0)),
                              ),
                              child: Row(
                                children: [
                                  Column(
                                    children: [
                                      Text(
                                        _avgRating.toStringAsFixed(1),
                                        style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                                      ),
                                      Row(
                                        children: List.generate(5, (index) {
                                          return Icon(
                                            index < _avgRating.floor() ? Icons.star_rounded : Icons.star_half_rounded,
                                            size: 14,
                                            color: const Color(0xFFF59E0B),
                                          );
                                        }),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        '$_reviewsCount Ulasan',
                                        style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(width: 16),
                                  const VerticalDivider(width: 1, thickness: 1, color: Color(0xFFCBD5E1)),
                                  const SizedBox(width: 16),
                                  Expanded(
                                    child: Column(
                                      children: [
                                        _buildRatingBar(5, _ratingBreakdown[5] ?? _ratingBreakdown['5'] ?? 0, _reviewsCount),
                                        _buildRatingBar(4, _ratingBreakdown[4] ?? _ratingBreakdown['4'] ?? 0, _reviewsCount),
                                        _buildRatingBar(3, _ratingBreakdown[3] ?? _ratingBreakdown['3'] ?? 0, _reviewsCount),
                                        _buildRatingBar(2, _ratingBreakdown[2] ?? _ratingBreakdown['2'] ?? 0, _reviewsCount),
                                        _buildRatingBar(1, _ratingBreakdown[1] ?? _ratingBreakdown['1'] ?? 0, _reviewsCount),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 14),

                            // List of reviews
                            ListView.separated(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              itemCount: _reviews.length > 5 ? 5 : _reviews.length,
                              separatorBuilder: (_, __) => const Divider(height: 20, color: Color(0xFFF1F5F9)),
                              itemBuilder: (ctx, idx) {
                                final rev = _reviews[idx];
                                final cName = (rev['customer_name'] ?? 'Pelanggan').toString();
                                final cAvatar = rev['customer_avatar']?.toString();
                                final rVal = int.tryParse(rev['rating']?.toString() ?? '5') ?? 5;
                                final comment = (rev['comment'] ?? '').toString();
                                final reply = rev['reply']?.toString();
                                final rDate = rev['created_at'] != null ? rev['created_at'].toString().split(' ')[0] : 'Baru saja';

                                return Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        CircleAvatar(
                                          radius: 16,
                                          backgroundColor: AppTheme.primaryRed.withOpacity(0.1),
                                          backgroundImage: (cAvatar != null && cAvatar.isNotEmpty)
                                              ? CachedNetworkImageProvider(ApiConstants.formatImageUrl(cAvatar))
                                              : null,
                                          child: (cAvatar == null || cAvatar.isEmpty)
                                              ? Text(
                                                  cName.isNotEmpty ? cName[0].toUpperCase() : 'U',
                                                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppTheme.primaryRed),
                                                )
                                              : null,
                                        ),
                                        const SizedBox(width: 10),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Row(
                                                children: [
                                                  Text(
                                                    cName,
                                                    style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                                                  ),
                                                  const SizedBox(width: 6),
                                                  Container(
                                                    padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                                    decoration: BoxDecoration(
                                                      color: const Color(0xFFDCFCE7),
                                                      borderRadius: BorderRadius.circular(4),
                                                    ),
                                                    child: const Text(
                                                      'Pembeli Terverifikasi',
                                                      style: TextStyle(fontSize: 8.5, fontWeight: FontWeight.bold, color: Color(0xFF15803D)),
                                                    ),
                                                  ),
                                                ],
                                              ),
                                              const SizedBox(height: 2),
                                              Row(
                                                children: [
                                                  Row(
                                                    children: List.generate(5, (sIdx) {
                                                      return Icon(
                                                        sIdx < rVal ? Icons.star_rounded : Icons.star_outline_rounded,
                                                        size: 13,
                                                        color: const Color(0xFFF59E0B),
                                                      );
                                                    }),
                                                  ),
                                                  const SizedBox(width: 6),
                                                  Text(
                                                    rDate,
                                                    style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                                                  ),
                                                ],
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                    ),
                                    if (comment.isNotEmpty) ...[
                                      const SizedBox(height: 8),
                                      Text(
                                        comment,
                                        style: const TextStyle(fontSize: 12, color: Color(0xFF334155), height: 1.4),
                                      ),
                                    ],
                                    if (reply != null && reply.isNotEmpty) ...[
                                      const SizedBox(height: 8),
                                      Container(
                                        padding: const EdgeInsets.all(10),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFF1F5F9),
                                          borderRadius: BorderRadius.circular(10),
                                        ),
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            const Row(
                                              children: [
                                                Icon(Icons.reply_rounded, size: 12, color: AppTheme.primaryRed),
                                                SizedBox(width: 4),
                                                Text(
                                                  'Balasan Mitra Toko',
                                                  style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                                                ),
                                              ],
                                            ),
                                            const SizedBox(height: 2),
                                            Text(
                                              reply,
                                              style: const TextStyle(fontSize: 11, color: Color(0xFF475569)),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ],
                                );
                              },
                            ),
                          ],

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
                    color: Colors.black.withOpacity(0.05),
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

  Widget _buildRatingBar(int star, dynamic countVal, int totalCount) {
    final count = int.tryParse(countVal?.toString() ?? '0') ?? 0;
    final percent = totalCount > 0 ? (count / totalCount) : 0.0;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 1.5),
      child: Row(
        children: [
          Text('$star', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF475569))),
          const Icon(Icons.star_rounded, size: 11, color: Color(0xFFF59E0B)),
          const SizedBox(width: 6),
          Expanded(
            child: ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: LinearProgressIndicator(
                value: percent.clamp(0.0, 1.0),
                backgroundColor: const Color(0xFFE2E8F0),
                valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFFF59E0B)),
                minHeight: 4.5,
              ),
            ),
          ),
          const SizedBox(width: 6),
          SizedBox(
            width: 18,
            child: Text(
              '$count',
              style: const TextStyle(fontSize: 9.5, color: Color(0xFF64748B)),
              textAlign: TextAlign.right,
            ),
          ),
        ],
      ),
    );
  }
}
