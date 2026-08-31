import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/uber_pill_button.dart';
import '../../../core/widgets/app_alert.dart';
import '../controllers/customer_controller.dart';
import '../screens/store_detail_screen.dart';

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
  bool? _storeIsOpenOverride;
  int? _storeIdOverride;
  String? _storeNameOverride;
  bool _isAdding = false;

  // Variations and Addons / Toppings State
  List<dynamic> _variations = [];
  List<dynamic> _addons = [];
  int? _selectedVariationId;
  double? _selectedVariationPrice;
  String? _selectedVariationName;
  final Set<int> _selectedAddonIds = {};
  final Map<int, Map<String, dynamic>> _selectedAddonsMap = {};

  @override
  void initState() {
    super.initState();
    final initialRating = double.tryParse(widget.product['rating']?.toString() ?? widget.product['avg_rating']?.toString() ?? '5.0') ?? 5.0;
    final initialCount = int.tryParse(widget.product['reviews_count']?.toString() ?? '0') ?? 0;
    _avgRating = initialRating;
    _reviewsCount = initialCount;

    // Load initial variations from widget
    final rawVars = widget.product['variations'];
    if (rawVars is List && rawVars.isNotEmpty) {
      _variations = rawVars;
      final firstVar = _variations.first;
      _selectedVariationId = int.tryParse(firstVar['id']?.toString() ?? '');
      _selectedVariationPrice = double.tryParse(firstVar['price']?.toString() ?? '');
      _selectedVariationName = firstVar['name']?.toString();
    }

    // Load initial addons from widget
    final rawAddons = widget.product['addons'];
    if (rawAddons is List && rawAddons.isNotEmpty) {
      _addons = rawAddons;
    }

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
          if (data['store_is_open'] != null || data['is_store_open'] != null) {
            final sOpen = data['store_is_open'] ?? data['is_store_open'];
            _storeIsOpenOverride = (sOpen == 1 || sOpen == true || sOpen == '1');
          }
          if (data['store_id'] != null) {
            _storeIdOverride = int.tryParse(data['store_id'].toString());
          }
          if (data['store_name'] != null && data['store_name'].toString().isNotEmpty) {
            _storeNameOverride = data['store_name'].toString();
          }

          // Update variations if provided by detail endpoint
          if (data['variations'] is List && (data['variations'] as List).isNotEmpty) {
            _variations = data['variations'];
            if (_selectedVariationId == null) {
              final firstVar = _variations.first;
              _selectedVariationId = int.tryParse(firstVar['id']?.toString() ?? '');
              _selectedVariationPrice = double.tryParse(firstVar['price']?.toString() ?? '');
              _selectedVariationName = firstVar['name']?.toString();
            }
          }

          // Update addons if provided by detail endpoint
          if (data['addons'] is List && (data['addons'] as List).isNotEmpty) {
            _addons = data['addons'];
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
    final rawImg = product['image']?.toString() ??
        product['product_image']?.toString() ??
        product['image_url']?.toString() ??
        product['photo']?.toString() ??
        product['product_photo']?.toString() ??
        product['cover_photo']?.toString() ??
        product['thumbnail']?.toString();
    if (rawImg != null && rawImg.isNotEmpty && !rawImg.contains('null')) {
      final formatted = ApiConstants.formatImageUrl(rawImg);
      if (formatted.isNotEmpty) return formatted;
    }
    return '';
  }

  Widget _buildImagePlaceholder(String name) {
    final initial = name.isNotEmpty ? name[0].toUpperCase() : '🍽️';
    return Container(
      height: 240,
      width: double.infinity,
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Color(0xFF1E293B),
            Color(0xFF0F172A),
          ],
        ),
      ),
      child: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Text(
                initial,
                style: const TextStyle(fontSize: 36, color: Colors.white, fontWeight: FontWeight.bold),
              ),
            ),
            const SizedBox(height: 8),
            Text(
              name,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final customerCtrl = context.watch<CustomerController>();
    final product = widget.product;
    final productId = int.tryParse(product['id']?.toString() ?? product['product_id']?.toString() ?? '0') ?? 0;
    final storeId = _storeIdOverride ?? int.tryParse(product['store_id']?.toString() ?? '0') ?? 0;
    final name = (product['name'] ?? product['product_name'] ?? 'Menu Kuliner').toString();
    final storeName = _storeNameOverride ?? product['store_name'] ?? product['store'] ?? 'Mitra CicalengkaGO';
    final price = double.tryParse(product['price']?.toString() ?? '0') ?? 0.0;
    final finalPrice = double.tryParse(product['final_price']?.toString() ?? price.toString()) ?? price;
    final bool hasDiscount = price > finalPrice;
    final double discountAmount = hasDiscount ? (price - finalPrice) : 0.0;
    final int discountPercent = hasDiscount && price > 0 ? (((price - finalPrice) / price) * 100).round() : 0;

    final description = (product['description'] != null && product['description'].toString().trim().isNotEmpty)
        ? product['description'].toString()
        : 'Olahan kuliner lezat khas Cicalengka yang disiapkan dengan bahan-bahan segar berkualitas dan cita rasa istimewa.';
    final imgUrl = _getFoodImage(product);
    final double effectiveItemBasePrice = (_selectedVariationPrice != null && _selectedVariationPrice! > 0) ? _selectedVariationPrice! : finalPrice;
    double addonsSubtotal = 0;
    for (final ad in _selectedAddonsMap.values) {
      addonsSubtotal += (double.tryParse(ad['price']?.toString() ?? '0') ?? 0);
    }
    final double singleUnitPrice = effectiveItemBasePrice + addonsSubtotal;
    final double totalPrice = singleUnitPrice * _quantity;

    final rawStoreOpen = _storeIsOpenOverride ?? product['store_is_open'] ?? product['is_open'] ?? product['is_currently_open'];
    final bool isStoreClosed = (rawStoreOpen != null && (rawStoreOpen == 0 || rawStoreOpen == false || rawStoreOpen == '0' || rawStoreOpen == 'false'));
    final bool isStoreOpen = !isStoreClosed;

    return Container(
      constraints: BoxConstraints(
        maxHeight: MediaQuery.of(context).size.height * 0.92,
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
            // Scrollable Content
            Expanded(
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Header Image Stack
                    Stack(
                      children: [
                        // Cover Image
                        Hero(
                          tag: 'product_img_$productId',
                          child: (imgUrl.isNotEmpty)
                              ? CachedNetworkImage(
                                  imageUrl: imgUrl,
                                  height: 240,
                                  width: double.infinity,
                                  fit: BoxFit.cover,
                                  placeholder: (context, url) => Container(
                                    height: 240,
                                    color: const Color(0xFFF1F5F9),
                                    child: const Center(
                                      child: SizedBox(
                                        width: 24,
                                        height: 24,
                                        child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.primaryRed),
                                      ),
                                    ),
                                  ),
                                  errorWidget: (context, url, error) => _buildImagePlaceholder(name),
                                )
                              : _buildImagePlaceholder(name),
                        ),

                        // Gradient Scrim Top (for drag bar & close button)
                        Positioned(
                          top: 0,
                          left: 0,
                          right: 0,
                          height: 70,
                          child: Container(
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                begin: Alignment.topCenter,
                                end: Alignment.bottomCenter,
                                colors: [
                                  Colors.black.withValues(alpha: 0.55),
                                  Colors.transparent,
                                ],
                              ),
                            ),
                          ),
                        ),

                        // Drag Pill Indicator
                        Positioned(
                          top: 10,
                          left: 0,
                          right: 0,
                          child: Center(
                            child: Container(
                              width: 42,
                              height: 4.5,
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.85),
                                borderRadius: BorderRadius.circular(10),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withValues(alpha: 0.3),
                                    blurRadius: 4,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),

                        // Close Button Top Right
                        Positioned(
                          top: 14,
                          right: 14,
                          child: Material(
                            color: Colors.transparent,
                            child: InkWell(
                              onTap: () => Navigator.pop(context),
                              borderRadius: BorderRadius.circular(20),
                              child: Container(
                                width: 34,
                                height: 34,
                                decoration: BoxDecoration(
                                  color: Colors.black.withValues(alpha: 0.5),
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                    color: Colors.white.withValues(alpha: 0.2),
                                    width: 1,
                                  ),
                                ),
                                child: const Icon(
                                  Icons.close_rounded,
                                  color: Colors.white,
                                  size: 18,
                                ),
                              ),
                            ),
                          ),
                        ),

                        // Gradient Scrim Bottom (for rating & promo chips)
                        Positioned(
                          bottom: 0,
                          left: 0,
                          right: 0,
                          height: 75,
                          child: Container(
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                begin: Alignment.bottomCenter,
                                end: Alignment.topCenter,
                                colors: [
                                  Colors.black.withValues(alpha: 0.65),
                                  Colors.transparent,
                                ],
                              ),
                            ),
                          ),
                        ),

                        // Rating Chip Bottom Left
                        Positioned(
                          bottom: 12,
                          left: 14,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                            decoration: BoxDecoration(
                              color: Colors.black.withValues(alpha: 0.65),
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
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
                                  style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 11),
                                ),
                              ],
                            ),
                          ),
                        ),

                        // Promo / Discount Badge Bottom Right
                        if (hasDiscount)
                          Positioned(
                            bottom: 12,
                            right: 14,
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                              decoration: BoxDecoration(
                                gradient: const LinearGradient(
                                  colors: [Color(0xFFEF4444), Color(0xFFDC2626)],
                                ),
                                borderRadius: BorderRadius.circular(20),
                                boxShadow: [
                                  BoxShadow(
                                    color: const Color(0xFFEF4444).withValues(alpha: 0.4),
                                    blurRadius: 6,
                                  ),
                                ],
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.local_offer_rounded, color: Colors.white, size: 13),
                                  const SizedBox(width: 4),
                                  Text(
                                    discountPercent > 0 ? 'Diskon $discountPercent%' : 'Hemat ${CurrencyFormatter.formatRupiah(discountAmount)}',
                                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 11),
                                  ),
                                ],
                              ),
                            ),
                          ),
                      ],
                    ),

                    // Body Details
                    Padding(
                      padding: const EdgeInsets.all(18.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Store Closed Banner if applicable
                          if (!isStoreOpen)
                            Container(
                              margin: const EdgeInsets.only(bottom: 14),
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                              decoration: BoxDecoration(
                                color: const Color(0xFFFEF2F2),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: const Color(0xFFFCA5A5)),
                              ),
                              child: const Row(
                                children: [
                                  Icon(Icons.storefront_rounded, size: 18, color: Color(0xFFDC2626)),
                                  SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      'Toko Sedang Tutup • Menu ini tidak dapat dipesan saat ini',
                                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF991B1B)),
                                    ),
                                  ),
                                ],
                              ),
                            ),

                          // Store Interactive Pill Card
                          InkWell(
                            onTap: storeId > 0
                                ? () {
                                    Navigator.pop(context);
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (_) => StoreDetailScreen(storeId: storeId),
                                      ),
                                    );
                                  }
                                : null,
                            borderRadius: BorderRadius.circular(12),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF8FAFC),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: const Color(0xFFE2E8F0)),
                              ),
                              child: Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(5),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFFEF2F2),
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: const Icon(Icons.storefront_rounded, size: 16, color: AppTheme.primaryRed),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      storeName,
                                      style: const TextStyle(
                                        fontSize: 13,
                                        fontWeight: FontWeight.w700,
                                        color: AppTheme.inkBlack,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  if (storeId > 0) ...[
                                    const SizedBox(width: 8),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: const Color(0xFFFEF2F2),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: const Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Text(
                                            'Kunjungi Toko',
                                            style: TextStyle(
                                              fontSize: 11,
                                              fontWeight: FontWeight.bold,
                                              color: AppTheme.primaryRed,
                                            ),
                                          ),
                                          SizedBox(width: 3),
                                          Icon(Icons.arrow_forward_ios_rounded, size: 9, color: AppTheme.primaryRed),
                                        ],
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 12),

                          // Product Name
                          Text(
                            name,
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF0F172A),
                              letterSpacing: -0.3,
                            ),
                          ),
                          const SizedBox(height: 8),

                          // Price Row
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.baseline,
                            textBaseline: TextBaseline.alphabetic,
                            children: [
                              Text(
                                CurrencyFormatter.formatRupiah(singleUnitPrice),
                                style: const TextStyle(
                                  fontSize: 20,
                                  fontWeight: FontWeight.w900,
                                  color: AppTheme.primaryRed,
                                ),
                              ),
                              if (hasDiscount && _selectedVariationPrice == null) ...[
                                const SizedBox(width: 8),
                                Text(
                                  CurrencyFormatter.formatRupiah(price),
                                  style: const TextStyle(
                                    fontSize: 13.5,
                                    color: Color(0xFF94A3B8),
                                    decoration: TextDecoration.lineThrough,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                              if (_selectedVariationName != null) ...[
                                const SizedBox(width: 8),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFFEF2F2),
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                  child: Text(
                                    _selectedVariationName!,
                                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                                  ),
                                ),
                              ],
                            ],
                          ),

                          // Section Variasi Pilihan (Ukuran / Level / Porsi)
                          _buildVariationsSection(finalPrice),

                          // Section Topping & Tambahan Lezat
                          _buildAddonsSection(),

                          const SizedBox(height: 16),
                          const Divider(color: Color(0xFFF1F5F9), thickness: 1.2),
                          const SizedBox(height: 12),

                          // Description Section
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(5),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFEFF6FF),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(Icons.notes_rounded, size: 15, color: Color(0xFF2563EB)),
                              ),
                              const SizedBox(width: 8),
                              const Text(
                                'Deskripsi Menu',
                                style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Text(
                            description,
                            style: const TextStyle(fontSize: 13, color: Color(0xFF475569), height: 1.5),
                          ),

                          const SizedBox(height: 18),
                          const Divider(color: Color(0xFFF1F5F9), thickness: 1.2),
                          const SizedBox(height: 14),

                          // Reviews & Ratings Header
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(5),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFFEF3C7),
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: const Icon(Icons.star_rounded, size: 15, color: Color(0xFFD97706)),
                                  ),
                                  const SizedBox(width: 8),
                                  const Text(
                                    'Penilaian & Ulasan',
                                    style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                                  ),
                                ],
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
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
                                      style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFFB45309)),
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
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(color: const Color(0xFFE2E8F0)),
                              ),
                              child: Column(
                                children: [
                                  Icon(Icons.rate_review_outlined, size: 32, color: Colors.grey.shade400),
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
                                borderRadius: BorderRadius.circular(14),
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
                            const SizedBox(height: 12),

                            // List of reviews
                            ListView.separated(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              itemCount: _reviews.length > 5 ? 5 : _reviews.length,
                              separatorBuilder: (_, __) => const Divider(height: 18, color: Color(0xFFF1F5F9)),
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
                                          radius: 15,
                                          backgroundColor: AppTheme.primaryRed.withValues(alpha: 0.1),
                                          backgroundImage: (cAvatar != null && cAvatar.isNotEmpty)
                                              ? CachedNetworkImageProvider(ApiConstants.formatImageUrl(cAvatar))
                                              : null,
                                          child: (cAvatar == null || cAvatar.isEmpty)
                                              ? Text(
                                                  cName.isNotEmpty ? cName[0].toUpperCase() : 'U',
                                                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppTheme.primaryRed),
                                                )
                                              : null,
                                        ),
                                        const SizedBox(width: 9),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Row(
                                                children: [
                                                  Text(
                                                    cName,
                                                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                                                  ),
                                                  const SizedBox(width: 6),
                                                  Container(
                                                    padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                                                    decoration: BoxDecoration(
                                                      color: const Color(0xFFDCFCE7),
                                                      borderRadius: BorderRadius.circular(4),
                                                    ),
                                                    child: const Text(
                                                      'Terverifikasi',
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
                                                        size: 12,
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
                                      const SizedBox(height: 6),
                                      Text(
                                        comment,
                                        style: const TextStyle(fontSize: 12, color: Color(0xFF334155), height: 1.4),
                                      ),
                                    ],
                                    if (reply != null && reply.isNotEmpty) ...[
                                      const SizedBox(height: 6),
                                      Container(
                                        padding: const EdgeInsets.all(9),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFF1F5F9),
                                          borderRadius: BorderRadius.circular(8),
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

                          const SizedBox(height: 18),
                          const Divider(color: Color(0xFFF1F5F9), thickness: 1.2),
                          const SizedBox(height: 12),

                          // Special Notes Textfield
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(5),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF1F5F9),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(Icons.edit_note_rounded, size: 16, color: Color(0xFF475569)),
                              ),
                              const SizedBox(width: 8),
                              const Text(
                                'Catatan Khusus Pesanan (Opsional)',
                                style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                              ),
                            ],
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

            // Bottom Action Bar with Quantity Stepper & Add to Cart Button (Add to Cart only, NO direct checkout)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.06),
                    blurRadius: 10,
                    offset: const Offset(0, -3),
                  ),
                ],
              ),
              child: SafeArea(
                child: Row(
                  children: [
                    if (isStoreOpen) ...[
                      // Quantity Controls (only when open)
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
                              constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                              padding: EdgeInsets.zero,
                              icon: Icon(
                                Icons.remove_rounded,
                                size: 18,
                                color: _quantity > 1 ? AppTheme.inkBlack : const Color(0xFFCBD5E1),
                              ),
                              onPressed: _quantity > 1
                                  ? () {
                                      setState(() => _quantity--);
                                    }
                                  : null,
                            ),
                            Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 4),
                              child: Text(
                                '$_quantity',
                                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                              ),
                            ),
                            IconButton(
                              constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                              padding: EdgeInsets.zero,
                              icon: const Icon(Icons.add_rounded, size: 18, color: AppTheme.inkBlack),
                              onPressed: () {
                                setState(() => _quantity++);
                              },
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 12),
                    ],

                    // Add to Cart Button or Closed Indicator
                    Expanded(
                      child: isStoreOpen
                          ? UberPillButton(
                              label: _isAdding ? 'Menambahkan...' : CurrencyFormatter.formatRupiah(totalPrice),
                              icon: Icons.add_shopping_cart_rounded,
                              paddingHorizontal: 12,
                              fullWidth: true,
                              onPressed: _isAdding
                                  ? null
                                  : () async {
                                      setState(() => _isAdding = true);
                                      final ok = await customerCtrl.addToCart(
                                        productId,
                                        _quantity,
                                        notes: _notesController.text.trim(),
                                        variationId: _selectedVariationId,
                                        addons: _selectedAddonIds.toList(),
                                      );
                                      if (mounted) {
                                        setState(() => _isAdding = false);
                                        if (ok) {
                                          Navigator.pop(context);
                                          AppAlert.showCartAdded(
                                            context,
                                            productName: name,
                                            quantity: _quantity,
                                          );
                                        } else {
                                          final err = customerCtrl.lastCartError ?? 'Gagal menambahkan ke keranjang';
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            SnackBar(
                                              content: Text(err),
                                              backgroundColor: AppTheme.primaryRed,
                                              behavior: SnackBarBehavior.floating,
                                              duration: const Duration(seconds: 3),
                                            ),
                                          );
                                        }
                                      }
                                    },
                            )
                          : Container(
                              height: 48,
                              decoration: BoxDecoration(
                                color: const Color(0xFF94A3B8),
                                borderRadius: BorderRadius.circular(24),
                              ),
                              child: const Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.store_outlined, color: Colors.white, size: 18),
                                  SizedBox(width: 8),
                                  Text(
                                    'Toko Sedang Tutup',
                                    style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                                  ),
                                ],
                              ),
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

  Widget _buildVariationsSection(double baseFinalPrice) {
    if (_variations.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SizedBox(height: 14),
        const Divider(color: Color(0xFFF1F5F9), thickness: 1.2),
        const SizedBox(height: 10),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(5),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEF2F2),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.tune_rounded, size: 15, color: AppTheme.primaryRed),
                ),
                const SizedBox(width: 8),
                const Text(
                  'Pilihan Variasi Menu',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
              ],
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF2F2),
                borderRadius: BorderRadius.circular(6),
              ),
              child: const Text(
                'Pilih 1 (Wajib)',
                style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Column(
          children: _variations.map((v) {
            final vId = int.tryParse(v['id']?.toString() ?? '0') ?? 0;
            final vName = (v['name'] ?? 'Variasi').toString();
            final vPrice = double.tryParse(v['price']?.toString() ?? '0') ?? 0.0;
            final isSelected = (_selectedVariationId == vId);

            return InkWell(
              onTap: () {
                setState(() {
                  _selectedVariationId = vId;
                  _selectedVariationPrice = vPrice;
                  _selectedVariationName = vName;
                });
              },
              borderRadius: BorderRadius.circular(12),
              child: Container(
                margin: const EdgeInsets.only(bottom: 8),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                decoration: BoxDecoration(
                  color: isSelected ? const Color(0xFFFEF2F2) : const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: isSelected ? AppTheme.primaryRed : const Color(0xFFE2E8F0),
                    width: isSelected ? 1.5 : 1,
                  ),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 20,
                      height: 20,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: isSelected ? AppTheme.primaryRed : Colors.transparent,
                        border: Border.all(
                          color: isSelected ? AppTheme.primaryRed : const Color(0xFFCBD5E1),
                          width: 2,
                        ),
                      ),
                      child: isSelected
                          ? const Center(
                              child: Icon(Icons.check, size: 13, color: Colors.white),
                            )
                          : null,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        vName,
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                          color: isSelected ? AppTheme.primaryRed : const Color(0xFF1E293B),
                        ),
                      ),
                    ),
                    Text(
                      CurrencyFormatter.formatRupiah(vPrice > 0 ? vPrice : baseFinalPrice),
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                        color: isSelected ? AppTheme.primaryRed : const Color(0xFF475569),
                      ),
                    ),
                  ],
                ),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }

  Widget _buildAddonsSection() {
    if (_addons.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SizedBox(height: 14),
        const Divider(color: Color(0xFFF1F5F9), thickness: 1.2),
        const SizedBox(height: 10),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(5),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEF3C7),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.add_circle_outline_rounded, size: 15, color: Color(0xFFD97706)),
                ),
                const SizedBox(width: 8),
                const Text(
                  'Topping & Tambahan Lezat',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
              ],
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: const Color(0xFFF1F5F9),
                borderRadius: BorderRadius.circular(6),
              ),
              child: const Text(
                'Opsional (Bisa Pilih Banyak)',
                style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF64748B)),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Column(
          children: _addons.map((ad) {
            final aId = int.tryParse(ad['id']?.toString() ?? '0') ?? 0;
            final aName = (ad['name'] ?? 'Topping').toString();
            final aPrice = double.tryParse(ad['price']?.toString() ?? '0') ?? 0.0;
            final isSelected = _selectedAddonIds.contains(aId);

            return InkWell(
              onTap: () {
                setState(() {
                  if (isSelected) {
                    _selectedAddonIds.remove(aId);
                    _selectedAddonsMap.remove(aId);
                  } else {
                    _selectedAddonIds.add(aId);
                    _selectedAddonsMap[aId] = {
                      'id': aId,
                      'name': aName,
                      'price': aPrice,
                    };
                  }
                });
              },
              borderRadius: BorderRadius.circular(12),
              child: Container(
                margin: const EdgeInsets.only(bottom: 8),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                decoration: BoxDecoration(
                  color: isSelected ? const Color(0xFFFFFBEB) : const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: isSelected ? const Color(0xFFF59E0B) : const Color(0xFFE2E8F0),
                    width: isSelected ? 1.5 : 1,
                  ),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 20,
                      height: 20,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(6),
                        color: isSelected ? const Color(0xFFF59E0B) : Colors.transparent,
                        border: Border.all(
                          color: isSelected ? const Color(0xFFF59E0B) : const Color(0xFFCBD5E1),
                          width: 1.5,
                        ),
                      ),
                      child: isSelected
                          ? const Center(
                              child: Icon(Icons.check, size: 14, color: Colors.white),
                            )
                          : null,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        aName,
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                          color: isSelected ? const Color(0xFF92400E) : const Color(0xFF1E293B),
                        ),
                      ),
                    ),
                    Text(
                      '+${CurrencyFormatter.formatRupiah(aPrice)}',
                      style: TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.bold,
                        color: isSelected ? const Color(0xFFB45309) : const Color(0xFF64748B),
                      ),
                    ),
                  ],
                ),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }
}
