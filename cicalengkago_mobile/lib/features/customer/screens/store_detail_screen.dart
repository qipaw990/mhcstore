import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/services/location_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/app_alert.dart';
import '../controllers/customer_controller.dart';
import '../../auth/controllers/auth_controller.dart';
import '../../common/screens/in_app_chat_modal.dart';
import '../widgets/product_detail_modal.dart';
import 'cart_screen.dart';
import 'package:url_launcher/url_launcher.dart';

class StoreDetailScreen extends StatefulWidget {
  final int storeId;
  const StoreDetailScreen({super.key, required this.storeId});

  @override
  State<StoreDetailScreen> createState() => _StoreDetailScreenState();
}

class _StoreDetailScreenState extends State<StoreDetailScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _storeData;
  List<dynamic> _products = [];
  List<dynamic> _reviews = [];

  double _userLat = -6.9835;
  double _userLng = 107.8335;

  double _calculateDistanceKm(double sLat, double sLng, double uLat, double uLng) {
    if (sLat == 0 || sLng == 0 || uLat == 0 || uLng == 0) return 0.0;
    const double p = 0.017453292519943295; // Math.PI / 180
    final double a = 0.5 -
        math.cos((uLat - sLat) * p) / 2 +
        math.cos(sLat * p) * math.cos(uLat * p) * (1 - math.cos((uLng - sLng) * p)) / 2;
    final double dist = 12742 * math.asin(math.sqrt(a)); // 2 * R; R = 6371 km
    return dist;
  }

  @override
  void initState() {
    super.initState();
    _fetchGpsLocation();
    _fetchStoreDetail();
  }

  Future<void> _fetchGpsLocation() async {
    try {
      final pos = await LocationService.getCurrentPosition();
      if (mounted) {
        setState(() {
          _userLat = pos.latitude;
          _userLng = pos.longitude;
        });
      }
    } catch (_) {}
  }

  Future<void> _fetchStoreDetail() async {
    final res = await ApiService.get('${ApiConstants.storeDetail}/${widget.storeId}');
    if (res['success'] == true && res['data'] != null) {
      final data = res['data'];
      setState(() {
        _storeData = data['store'] is Map<String, dynamic> ? data['store'] : (data is Map<String, dynamic> ? data : {});
        _products = data['products'] is List ? data['products'] : [];
        _reviews = data['reviews'] is List ? data['reviews'] : [];
        _isLoading = false;
      });
    } else {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _launchMerchantWhatsApp(String? rawPhone, String storeName) async {
    String phone = (rawPhone ?? '').replaceAll(RegExp(r'[^0-9]'), '');
    if (phone.startsWith('0')) {
      phone = '62${phone.substring(1)}';
    }
    if (phone.isEmpty) {
      phone = '6285158397756'; // Fallback CS CicalengkaGO
    }

    final message = 'Halo $storeName, saya ingin bertanya seputar menu dan produk Anda di aplikasi CicalengkaGO.';
    final url = 'https://wa.me/$phone?text=${Uri.encodeComponent(message)}';
    try {
      final uri = Uri.parse(url);
      if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
        await launchUrl(uri);
      }
    } catch (e) {
      if (mounted) {
        AppAlert.showError(context, title: 'Gagal Membuka WhatsApp', message: 'Tidak dapat membuka chat ke mitra toko.');
      }
    }
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

  @override
  Widget build(BuildContext context) {
    final customerCtrl = context.watch<CustomerController>();

    if (_isLoading) {
      return const Scaffold(
        backgroundColor: Color(0xFFF8FAFC),
        body: Center(
          child: CircularProgressIndicator(color: AppTheme.inkBlack),
        ),
      );
    }

    final store = _storeData ?? {};
    final rawCover = store['cover_photo']?.toString();
    final rawLogo = store['logo']?.toString();
    final coverUrl = ApiConstants.formatImageUrl(
      (rawCover != null && rawCover.trim().isNotEmpty) ? rawCover : rawLogo
    );
    final isOpen = store['is_open'] == 1 || store['is_open'] == true || store['is_open'] == '1';

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: CustomScrollView(
        slivers: [
          // Banner Cover Header
          SliverAppBar(
            expandedHeight: 190,
            pinned: true,
            backgroundColor: AppTheme.inkBlack,
            elevation: 0,
            leading: Padding(
              padding: const EdgeInsets.all(8.0),
              child: CircleAvatar(
                backgroundColor: Colors.black.withValues(alpha: 0.5),
                child: IconButton(
                  icon: const Icon(Icons.arrow_back_rounded, color: Colors.white, size: 20),
                  onPressed: () => Navigator.pop(context),
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                ),
              ),
            ),
            flexibleSpace: FlexibleSpaceBar(
              background: Stack(
                fit: StackFit.expand,
                children: [
                  CachedNetworkImage(
                    imageUrl: coverUrl,
                    fit: BoxFit.cover,
                    errorWidget: (_, url, error) => Container(
                      color: AppTheme.inkBlack,
                      child: const Center(child: Icon(Icons.storefront_rounded, size: 60, color: Colors.white38)),
                    ),
                  ),
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.black.withValues(alpha: 0.4),
                          Colors.transparent,
                          Colors.black.withValues(alpha: 0.7),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Compact Store Details Card
          SliverToBoxAdapter(
            child: Container(
              margin: const EdgeInsets.fromLTRB(14, 12, 14, 16),
              padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.05),
                      blurRadius: 16,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Store Logo
                        Container(
                          width: 58,
                          height: 58,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: const Color(0xFFF1F5F9), width: 2),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.04),
                                blurRadius: 8,
                              ),
                            ],
                          ),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: CachedNetworkImage(
                              imageUrl: ApiConstants.formatImageUrl(rawLogo ?? rawCover),
                              fit: BoxFit.cover,
                              errorWidget: (context, url, error) => const CicalengkaGoLogo(size: 58, borderRadius: 12),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        // Store Title & Status
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      store['name'] ?? 'Mitra Resto',
                                      style: const TextStyle(
                                        fontSize: 17,
                                        fontWeight: FontWeight.w800,
                                        color: AppTheme.inkBlack,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  const SizedBox(width: 6),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: isOpen ? const Color(0xFFDCFCE7) : const Color(0xFFF1F5F9),
                                      borderRadius: BorderRadius.circular(20),
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Container(
                                          width: 6,
                                          height: 6,
                                          decoration: BoxDecoration(
                                            color: isOpen ? const Color(0xFF16A34A) : const Color(0xFF64748B),
                                            shape: BoxShape.circle,
                                          ),
                                        ),
                                        const SizedBox(width: 4),
                                        Text(
                                          isOpen ? 'BUKA' : 'TUTUP',
                                          style: TextStyle(
                                            color: isOpen ? const Color(0xFF15803D) : const Color(0xFF64748B),
                                            fontSize: 10,
                                            fontWeight: FontWeight.w800,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 4),
                              // Rating & Info Chips
                              Row(
                                children: [
                                  const Icon(Icons.star_rounded, size: 16, color: Colors.amber),
                                  const SizedBox(width: 2),
                                  Text(
                                    '${store['rating'] ?? '4.8'}',
                                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                                  ),
                                  Text(
                                    ' (${store['reviews_count'] ?? '50+'} ulasan)',
                                    style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                  ),
                                  const SizedBox(width: 8),
                                  const Text('•', style: TextStyle(color: Color(0xFF94A3B8))),
                                  const SizedBox(width: 8),
                                  const Icon(Icons.access_time_rounded, size: 13, color: Color(0xFF64748B)),
                                  const SizedBox(width: 3),
                                  Text(
                                    '${store['delivery_time'] ?? '15-25 min'}',
                                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    // Address Line & Chat Button
                    Row(
                      children: [
                        const Icon(Icons.location_on_outlined, size: 14, color: Color(0xFF64748B)),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            store['address'] ?? 'Cicalengka, Kab. Bandung',
                            style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 8),
                        InkWell(
                          onTap: () {
                            final authCtrl = context.read<AuthController>();
                            final uid = int.tryParse(authCtrl.user?['id']?.toString() ?? '0') ?? 0;
                            InAppChatModal.show(
                              context,
                              storeId: widget.storeId,
                              initialStoreName: store['name']?.toString(),
                              initialStoreLogo: (store['logo'] ?? store['cover_photo'])?.toString(),
                              currentUserId: uid,
                              currentUserRole: 'customer',
                            );
                          },
                          borderRadius: BorderRadius.circular(20),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF0FDF4),
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(color: const Color(0xFF86EFAC)),
                            ),
                            child: const Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.chat_rounded, size: 12, color: Color(0xFF16A34A)),
                                SizedBox(width: 4),
                                Text(
                                  'Chat In-App',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFF15803D),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                    // Delivery Fee & Proximity Highlight
                    () {
                      final double sLat = double.tryParse(store['latitude']?.toString() ?? '0') ?? 0.0;
                      final double sLng = double.tryParse(store['longitude']?.toString() ?? '0') ?? 0.0;
                      double distKm = 0.0;
                      bool hasCoords = (sLat != 0.0 && sLng != 0.0 && _userLat != 0.0 && _userLng != 0.0);
                      if (hasCoords) {
                        distKm = _calculateDistanceKm(sLat, sLng, _userLat, _userLng);
                      }
                      final bool isCloseMerchant = hasCoords && (distKm <= 0.30);

                      if (isCloseMerchant) {
                        final distMeters = (distKm * 1000).toInt();
                        return Container(
                          margin: const EdgeInsets.only(top: 10),
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF0FDF4),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFFBBF7D0)),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.storefront_rounded, size: 16, color: Color(0xFF16A34A)),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  'Bebas Ongkir • Diantar Staf Toko ($distMeters m dari Anda)',
                                  style: const TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFF15803D),
                                  ),
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF16A34A),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: const Text(
                                  'ONGKIR RP 0',
                                  style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.w900),
                                ),
                              ),
                            ],
                          ),
                        );
                      } else if (hasCoords && distKm > 0) {
                        return Container(
                          margin: const EdgeInsets.only(top: 10),
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF8FAFC),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.two_wheeler_rounded, size: 16, color: Color(0xFF64748B)),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  'Pengantaran Mitra Driver (${distKm.toStringAsFixed(1)} km)',
                                  style: const TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFF475569),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        );
                      }
                      return const SizedBox.shrink();
                    }(),
                  ],
                ),
              ),
            ),

          // Closed Store Banner
          if (!isOpen)
            SliverToBoxAdapter(
              child: Container(
                margin: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEE2E2),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFFCA5A5)),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.storefront_rounded, color: Color(0xFFDC2626), size: 22),
                    SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '🔴 Toko Sedang Tutup',
                            style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF991B1B)),
                          ),
                          SizedBox(height: 2),
                          Text(
                            'Anda tidak dapat memesan saat ini. Silakan kembali lagi nanti.',
                            style: TextStyle(fontSize: 11, color: Color(0xFFB91C1C)),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

          // Menu Section Header
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
              child: Row(
                children: [
                  Container(
                    width: 4,
                    height: 18,
                    decoration: BoxDecoration(
                      color: AppTheme.inkBlack,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                  const SizedBox(width: 8),
                  const Text(
                    'Daftar Menu Makanan & Minuman',
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                      color: AppTheme.inkBlack,
                    ),
                  ),
                  const Spacer(),
                  Text(
                    '${_products.length} Menu',
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
                  ),
                ],
              ),
            ),
          ),

          // Products List
          if (_products.isEmpty)
            const SliverToBoxAdapter(
              child: Padding(
                padding: EdgeInsets.symmetric(vertical: 40),
                child: Center(
                  child: Text(
                    'Belum ada produk yang tersedia',
                    style: TextStyle(color: Color(0xFF64748B), fontSize: 13),
                  ),
                ),
              ),
            )
          else
            SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) {
                  final product = _products[index] is Map<String, dynamic>
                      ? _products[index] as Map<String, dynamic>
                      : Map<String, dynamic>.from(_products[index] as Map);

                  final price = double.tryParse(product['price']?.toString() ?? '0') ?? 0.0;
                  final finalPrice = double.tryParse(product['final_price']?.toString() ?? price.toString()) ?? price;
                  final imgUrl = _getFoodImage(product);
                  final bool hasDiscount = price > finalPrice;

                  return GestureDetector(
                    onTap: () => ProductDetailModal.show(context, product),
                    child: Container(
                      margin: const EdgeInsets.symmetric(horizontal: 14, vertical: 5),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.02),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        // Product Image
                        Stack(
                          children: [
                            ClipRRect(
                              borderRadius: BorderRadius.circular(14),
                              child: (imgUrl.isNotEmpty)
                                  ? CachedNetworkImage(
                                      imageUrl: imgUrl,
                                      width: 76,
                                      height: 76,
                                      memCacheWidth: 200,
                                      memCacheHeight: 200,
                                      fit: BoxFit.cover,
                                      errorWidget: (_, url, error) => Container(
                                        width: 76,
                                        height: 76,
                                        color: const Color(0xFFF1F5F9),
                                        child: const Icon(Icons.fastfood_rounded, size: 30, color: Color(0xFF94A3B8)),
                                      ),
                                    )
                                  : Container(
                                      width: 76,
                                      height: 76,
                                      color: const Color(0xFFF1F5F9),
                                      child: const Icon(Icons.fastfood_rounded, size: 30, color: Color(0xFF94A3B8)),
                                    ),
                            ),
                            if (hasDiscount)
                              Positioned(
                                top: 0,
                                left: 0,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                  decoration: const BoxDecoration(
                                    color: Color(0xFFEF4444),
                                    borderRadius: BorderRadius.only(
                                      topLeft: Radius.circular(14),
                                      bottomRight: Radius.circular(8),
                                    ),
                                  ),
                                  child: const Text(
                                    'PROMO',
                                    style: TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.w900),
                                  ),
                                ),
                              ),
                          ],
                        ),
                        const SizedBox(width: 14),

                          // Product Details
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  product['name'] ?? 'Menu Kuliner',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 14,
                                    color: AppTheme.inkBlack,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                if (product['description'] != null && product['description'].toString().isNotEmpty) ...[
                                  const SizedBox(height: 2),
                                  Text(
                                    product['description'].toString(),
                                    style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ],
                                const SizedBox(height: 6),
                                Row(
                                  children: [
                                    Text(
                                      CurrencyFormatter.formatRupiah(finalPrice),
                                      style: const TextStyle(
                                        color: Color(0xFFEF4444),
                                        fontWeight: FontWeight.w800,
                                        fontSize: 13.5,
                                      ),
                                    ),
                                    if (hasDiscount) ...[
                                      const SizedBox(width: 6),
                                      Text(
                                        CurrencyFormatter.formatRupiah(price),
                                        style: const TextStyle(
                                          fontSize: 10.5,
                                          color: Color(0xFF94A3B8),
                                          decoration: TextDecoration.lineThrough,
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                                if ((product['variations'] is List && (product['variations'] as List).isNotEmpty) ||
                                    (product['addons'] is List && (product['addons'] as List).isNotEmpty)) ...[
                                  const SizedBox(height: 4),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFFEF2F2),
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                    child: const Text(
                                      'Ada Varian / Topping',
                                      style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),

                          // Add to Cart Button (disabled if store closed)
                          InkWell(
                            onTap: isOpen ? () async {
                              final bool hasVars = (product['variations'] is List && (product['variations'] as List).isNotEmpty);
                              final bool hasAds = (product['addons'] is List && (product['addons'] as List).isNotEmpty);

                              if (hasVars || hasAds) {
                                ProductDetailModal.show(context, product);
                                return;
                              }

                              final productId = int.tryParse(product['id']?.toString() ?? '0') ?? 0;
                              final ok = await customerCtrl.addToCart(productId, 1);
                              if (context.mounted) {
                                if (ok) {
                                  AppAlert.showCartAdded(
                                    context,
                                    productName: product['name'] ?? 'Menu',
                                    quantity: 1,
                                  );
                                } else {
                                  final err = customerCtrl.lastCartError ?? 'Gagal menambahkan ke keranjang';
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(content: Text(err), backgroundColor: AppTheme.primaryRed),
                                  );
                                }
                              }
                            } : null,
                            borderRadius: BorderRadius.circular(20),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                              decoration: BoxDecoration(
                                color: isOpen ? const Color(0xFFEF4444) : const Color(0xFFE2E8F0),
                                borderRadius: BorderRadius.circular(20),
                                boxShadow: isOpen ? [
                                  BoxShadow(
                                    color: const Color(0xFFEF4444).withValues(alpha: 0.25),
                                    blurRadius: 6,
                                    offset: const Offset(0, 2),
                                  ),
                                ] : null,
                              ),
                              child: Text(
                                !isOpen
                                    ? 'Tutup'
                                    : ((product['variations'] is List && (product['variations'] as List).isNotEmpty)
                                        ? 'Pilih'
                                        : 'Tambah'),
                                style: TextStyle(
                                  color: isOpen ? Colors.white : const Color(0xFF64748B),
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
                childCount: _products.length,
              ),
            ),

          // Reviews Section
          if (_reviews.isNotEmpty) ...[
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
                child: Row(
                  children: [
                    Container(
                      width: 4, height: 18,
                      decoration: BoxDecoration(color: Colors.amber.shade600, borderRadius: BorderRadius.circular(4)),
                    ),
                    const SizedBox(width: 8),
                    const Text(
                      'Ulasan Pelanggan',
                      style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: AppTheme.inkBlack),
                    ),
                    const Spacer(),
                    Text(
                      '${_reviews.length} Ulasan',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
                    ),
                  ],
                ),
              ),
            ),

            // Rating Summary Card
            SliverToBoxAdapter(
              child: _buildRatingSummary(),
            ),

            // Review Cards
            SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) => _buildReviewCard(_reviews[index]),
                childCount: _reviews.length,
              ),
            ),
          ],

          const SliverPadding(padding: EdgeInsets.only(bottom: 90)),
        ],
      ),

      // Floating Cart Bottom Bar
      bottomNavigationBar: customerCtrl.cartItems.isEmpty
          ? null
          : Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.08),
                    blurRadius: 16,
                    offset: const Offset(0, -4),
                  ),
                ],
              ),
              child: SafeArea(
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFEF2F2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.shopping_bag_rounded, color: Color(0xFFEF4444), size: 22),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '${customerCtrl.cartCount} Item di keranjang',
                            style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            CurrencyFormatter.formatRupiah(customerCtrl.cartSubtotal),
                            style: const TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF0F172A),
                            ),
                          ),
                        ],
                      ),
                    ),
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFEF4444),
                        foregroundColor: Colors.white,
                        elevation: 2,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      ),
                      icon: const Icon(Icons.arrow_forward_rounded, size: 16),
                      label: const Text('Keranjang', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                       onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => const CartScreen()),
                        );
                      },
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildRatingSummary() {
    final store = _storeData ?? {};
    final double avgRating = double.tryParse(store['rating']?.toString() ?? '0') ?? 0.0;
    final int totalReviews = _reviews.length;

    final Map<int, int> dist = {5: 0, 4: 0, 3: 0, 2: 0, 1: 0};
    for (final r in _reviews) {
      final rating = int.tryParse(r['rating']?.toString() ?? '0') ?? 0;
      if (dist.containsKey(rating)) dist[rating] = dist[rating]! + 1;
    }

    return Container(
      margin: const EdgeInsets.fromLTRB(14, 0, 14, 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFFDE68A)),
        boxShadow: [
          BoxShadow(color: Colors.amber.withValues(alpha: 0.06), blurRadius: 12, offset: const Offset(0, 4)),
        ],
      ),
      child: Row(
        children: [
          // Big Score
          Column(
            children: [
              Text(
                avgRating.toStringAsFixed(1),
                style: const TextStyle(fontSize: 42, fontWeight: FontWeight.w900, color: AppTheme.inkBlack, height: 1),
              ),
              const SizedBox(height: 4),
              Row(
                children: List.generate(5, (i) => Icon(
                  i < avgRating.round() ? Icons.star_rounded : Icons.star_outline_rounded,
                  color: Colors.amber, size: 14,
                )),
              ),
              const SizedBox(height: 4),
              Text(
                '$totalReviews ulasan',
                style: const TextStyle(fontSize: 10, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
              ),
            ],
          ),
          const SizedBox(width: 20),
          // Breakdown Bars
          Expanded(
            child: Column(
              children: [5, 4, 3, 2, 1].map((star) {
                final count = dist[star] ?? 0;
                final frac = totalReviews > 0 ? count / totalReviews : 0.0;
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 2),
                  child: Row(
                    children: [
                      Icon(Icons.star_rounded, size: 12, color: Colors.amber.shade600),
                      const SizedBox(width: 4),
                      Text('$star', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.inkBlack)),
                      const SizedBox(width: 6),
                      Expanded(
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(4),
                          child: LinearProgressIndicator(
                            value: frac,
                            backgroundColor: const Color(0xFFF1F5F9),
                            color: Colors.amber,
                            minHeight: 7,
                          ),
                        ),
                      ),
                      const SizedBox(width: 6),
                      SizedBox(
                        width: 22,
                        child: Text('$count', textAlign: TextAlign.right,
                            style: const TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                      ),
                    ],
                  ),
                );
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReviewCard(dynamic review) {
    final Map rev = review is Map ? review : {};
    final String customerName = rev['customer_name']?.toString() ?? 'Pelanggan';
    final String? avatarRaw = rev['customer_avatar']?.toString();
    final String avatarUrl = (avatarRaw != null && avatarRaw.isNotEmpty && !avatarRaw.contains('null'))
        ? ApiConstants.formatImageUrl(avatarRaw)
        : '';
    final int rating = int.tryParse(rev['rating']?.toString() ?? '5') ?? 5;
    final String comment = rev['comment']?.toString() ?? '';
    final String? reply = rev['reply']?.toString();
    final String date = (rev['created_at']?.toString() ?? '').split(' ').first;

    return Container(
      margin: const EdgeInsets.fromLTRB(14, 0, 14, 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFF1F5F9)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 8, offset: const Offset(0, 2)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Reviewer Header
          Row(
            children: [
              CircleAvatar(
                radius: 20,
                backgroundColor: const Color(0xFFF1F5F9),
                backgroundImage: avatarUrl.isNotEmpty ? NetworkImage(avatarUrl) : null,
                child: avatarUrl.isEmpty
                    ? Text(
                        customerName.isNotEmpty ? customerName[0].toUpperCase() : 'P',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppTheme.inkBlack),
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
                        Flexible(
                          child: Text(
                            customerName,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppTheme.inkBlack),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: const Color(0xFFDCFCE7),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Text(
                            '✓ Pembeli Terverifikasi',
                            style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Color(0xFF15803D)),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        ...List.generate(5, (i) => Icon(
                          i < rating ? Icons.star_rounded : Icons.star_outline_rounded,
                          color: Colors.amber, size: 13,
                        )),
                        const SizedBox(width: 6),
                        Text(date, style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),

          // Comment
          if (comment.isNotEmpty) ...[
            const SizedBox(height: 10),
            Text(
              '"$comment"',
              style: const TextStyle(fontSize: 12.5, color: Color(0xFF374151), height: 1.5, fontStyle: FontStyle.italic),
            ),
          ],

          // Store Reply
          if (reply != null && reply.trim().isNotEmpty) ...[
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.storefront_rounded, size: 13, color: AppTheme.primaryRed),
                      SizedBox(width: 4),
                      Text(
                        'Balasan Mitra Toko',
                        style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(reply, style: const TextStyle(fontSize: 11.5, color: Color(0xFF475569))),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}
