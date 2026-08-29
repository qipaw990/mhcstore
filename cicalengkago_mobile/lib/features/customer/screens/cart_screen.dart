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
import '../widgets/product_detail_modal.dart';
import 'checkout_screen.dart';

class CartScreen extends StatefulWidget {
  const CartScreen({super.key});

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  final List<Map<String, dynamic>> _foodSuggestions = const [
    {
      'id': 101,
      'name': 'Ayam Bakar Madu Spesial',
      'store': 'Ayam Bakar Cica',
      'price': 22000.0,
      'rating': '4.9',
      'image': 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=300&q=80',
    },
    {
      'id': 102,
      'name': 'Seblak Jeletot Seafood',
      'store': 'Seblak Prasmanan Cica',
      'price': 18000.0,
      'rating': '4.8',
      'image': 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=300&q=80',
    },
    {
      'id': 103,
      'name': 'Es Kopi Gula Aren Cica',
      'store': 'Kopi & Mood Cicalengka',
      'price': 15000.0,
      'rating': '4.9',
      'image': 'https://images.unsplash.com/photo-1541167760496-1628856ab772?w=300&q=80',
    },
    {
      'id': 104,
      'name': 'Nasi Goreng Telur Double',
      'store': 'Kedai Nasi Goreng Top',
      'price': 20000.0,
      'rating': '4.7',
      'image': 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=300&q=80',
    },
    {
      'id': 105,
      'name': 'Bakso Urat Jumbo Kuah Pedas',
      'store': 'Bakso & Mie Ayam Cica',
      'price': 25000.0,
      'rating': '4.8',
      'image': 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=300&q=80',
    },
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final ctrl = context.read<CustomerController>();
      ctrl.fetchCart();
      if (ctrl.recommendedProducts.isEmpty) {
        ctrl.fetchHomeData();
      }
    });
  }

  String _getFoodImage(Map<String, dynamic> item) {
    final rawImg = item['image']?.toString();
    if (rawImg != null && rawImg.isNotEmpty && !rawImg.contains('null')) {
      return ApiConstants.formatImageUrl(rawImg);
    }
    final name = (item['product_name'] ?? item['name'] ?? '').toString().toLowerCase();
    if (name.contains('ayam') || name.contains('chick')) {
      return 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=300&q=80';
    } else if (name.contains('nasi') || name.contains('rice')) {
      return 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=300&q=80';
    } else if (name.contains('kopi') || name.contains('coffee') || name.contains('boba') || name.contains('es')) {
      return 'https://images.unsplash.com/photo-1541167760496-1628856ab772?w=300&q=80';
    } else if (name.contains('seblak') || name.contains('bakso') || name.contains('mie') || name.contains('ramen')) {
      return 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=300&q=80';
    }
    return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&q=80';
  }

  @override
  Widget build(BuildContext context) {
    final customerCtrl = context.watch<CustomerController>();
    final cart = customerCtrl.cart;

    final rawItems = cart?['items'] as List<dynamic>? ?? [];
    final stores = cart?['stores'] as List<dynamic>? ?? [];

    final bool isEmpty = rawItems.isEmpty && stores.isEmpty;

    double subtotal = 0.0;
    if (stores.isNotEmpty) {
      for (var s in stores) {
        final items = (s['items'] as List<dynamic>?) ?? [];
        for (var item in items) {
          final itemPrice = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
          final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;
          subtotal += (itemPrice * qty);
        }
      }
    } else {
      for (var item in rawItems) {
        final itemPrice = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
        final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;
        subtotal += (itemPrice * qty);
      }
    }

    return RequireAuthWidget(
      title: 'Keranjang Belanja',
      subtitle: 'Silakan masuk ke akun Anda untuk melihat isi keranjang belanja dan memproses checkout.',
      icon: Icons.shopping_bag_outlined,
      child: Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 0.5,
        foregroundColor: AppTheme.inkBlack,
        title: const Text(
          'Keranjang Belanja',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
        ),
        actions: [
          if (!isEmpty)
            IconButton(
              tooltip: 'Kosongkan Keranjang',
              icon: const Icon(Icons.delete_outline_rounded, color: Color(0xFFEF4444)),
              onPressed: () => _confirmClearCart(context, customerCtrl),
            ),
        ],
      ),
      body: isEmpty
          ? _buildEmptyState(context)
          : RefreshIndicator(
              color: AppTheme.inkBlack,
              onRefresh: () => customerCtrl.fetchCart(),
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (stores.isNotEmpty)
                    ...stores.map((store) => _buildStoreCard(context, customerCtrl, store))
                  else
                    _buildFlatItemsCard(context, customerCtrl, rawItems, cart?['store'] as Map<String, dynamic>?),
                  const SizedBox(height: 24),
                  _buildFoodSuggestionsSection(context, customerCtrl),
                  const SizedBox(height: 80),
                ],
              ),
            ),
      bottomNavigationBar: isEmpty
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
                    Expanded(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Total Pembelian',
                            style: TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            CurrencyFormatter.formatRupiah(subtotal),
                            style: const TextStyle(
                              fontSize: 18,
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
                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
                      icon: const Icon(Icons.arrow_forward_rounded, size: 18),
                      label: const Text(
                        'Lanjut Checkout',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                      ),
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => const CheckoutScreen()),
                        );
                      },
                    ),
                  ],
                ),
              ),
            ),
      ),
    );
  }

  Widget _buildEmptyState(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 90,
              height: 90,
              decoration: const BoxDecoration(
                color: Color(0xFFEFEFEF),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.shopping_bag_outlined, size: 44, color: AppTheme.inkBlack),
            ),
            const SizedBox(height: 20),
            const Text(
              'Keranjang Belanja Kosong',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
            ),
            const SizedBox(height: 8),
            const Text(
              'Yuk, temukan makanan dan kuliner favoritmu\ndi CicalengkaGO dan tambahkan ke keranjang!',
              style: TextStyle(fontSize: 13, color: Color(0xFF64748B), height: 1.4),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            UberPillButton(
              label: 'Mulai Jelajah Kuliner',
              icon: Icons.restaurant_menu_rounded,
              onPressed: () {
                Navigator.pop(context);
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStoreCard(BuildContext context, CustomerController customerCtrl, Map<String, dynamic> store) {
    final items = (store['items'] as List<dynamic>?) ?? [];
    final rawLogo = store['logo'] ?? store['store_logo'] ?? (store['store'] is Map ? store['store']['logo'] : null);
    final storeLogoUrl = (rawLogo != null && rawLogo.toString().isNotEmpty)
        ? ApiConstants.formatImageUrl(rawLogo.toString())
        : null;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    width: 38,
                    height: 38,
                    color: const Color(0xFFF1F5F9),
                    child: (storeLogoUrl != null)
                        ? CachedNetworkImage(
                            imageUrl: storeLogoUrl,
                            fit: BoxFit.cover,
                            placeholder: (_, __) => const Center(
                              child: SizedBox(
                                width: 14,
                                height: 14,
                                child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.inkBlack),
                              ),
                            ),
                            errorWidget: (_, __, ___) => const Icon(
                              Icons.storefront_rounded,
                              color: AppTheme.inkBlack,
                              size: 20,
                            ),
                          )
                        : const Icon(
                            Icons.storefront_rounded,
                            color: AppTheme.inkBlack,
                            size: 20,
                          ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        store['store_name'] ?? store['name'] ?? 'Mitra Resto Cicalengka',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14.5, color: AppTheme.inkBlack),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      const Text(
                        'Mitra Resmi CicalengkaGO',
                        style: TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    '${items.length} Menu',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF64748B)),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          ...items.asMap().entries.map((entry) {
            final idx = entry.key;
            final item = entry.value;
            final isLast = idx == items.length - 1;
            return Column(
              children: [
                _buildCartItemTile(context, customerCtrl, item),
                if (!isLast) const Divider(height: 1, indent: 16, endIndent: 16, color: Color(0xFFF8FAFC)),
              ],
            );
          }),
        ],
      ),
    );
  }

  Widget _buildFlatItemsCard(BuildContext context, CustomerController customerCtrl, List<dynamic> items, Map<String, dynamic>? storeInfo) {
    final rawStoreName = storeInfo?['name'] ?? (items.isNotEmpty ? items.first['store_name'] : null) ?? 'Mitra Resto Cicalengka';
    final rawLogo = storeInfo?['logo'] ?? (items.isNotEmpty ? (items.first['store_logo'] ?? items.first['logo']) : null);
    final storeLogoUrl = (rawLogo != null && rawLogo.toString().isNotEmpty)
        ? ApiConstants.formatImageUrl(rawLogo.toString())
        : null;

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    width: 38,
                    height: 38,
                    color: const Color(0xFFF1F5F9),
                    child: (storeLogoUrl != null)
                        ? CachedNetworkImage(
                            imageUrl: storeLogoUrl,
                            fit: BoxFit.cover,
                            placeholder: (_, __) => const Center(
                              child: SizedBox(
                                width: 14,
                                height: 14,
                                child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.inkBlack),
                              ),
                            ),
                            errorWidget: (_, __, ___) => const Icon(
                              Icons.storefront_rounded,
                              color: AppTheme.inkBlack,
                              size: 20,
                            ),
                          )
                        : const Icon(
                            Icons.storefront_rounded,
                            color: AppTheme.inkBlack,
                            size: 20,
                          ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        rawStoreName,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14.5, color: AppTheme.inkBlack),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      const Text(
                        'Mitra Resmi CicalengkaGO',
                        style: TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    '${items.length} Menu',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF64748B)),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          ...items.asMap().entries.map((entry) {
            final idx = entry.key;
            final item = entry.value;
            final isLast = idx == items.length - 1;
            return Column(
              children: [
                _buildCartItemTile(context, customerCtrl, item),
                if (!isLast) const Divider(height: 1, indent: 16, endIndent: 16, color: Color(0xFFF8FAFC)),
              ],
            );
          }).toList(),
        ],
      ),
    );
  }

  Widget _buildCartItemTile(BuildContext context, CustomerController customerCtrl, Map<String, dynamic> item) {
    final cartId = int.tryParse(item['id']?.toString() ?? '0') ?? 0;
    final productId = int.tryParse(item['product_id']?.toString() ?? item['id']?.toString() ?? '0') ?? 0;
    final itemPrice = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
    final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;
    final imageUrl = _getFoodImage(item);

    return Padding(
      padding: const EdgeInsets.all(14.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          // Product Food Image with ClipRRect
          ClipRRect(
            borderRadius: BorderRadius.circular(14),
            child: SizedBox(
              width: 64,
              height: 64,
              child: CachedNetworkImage(
                imageUrl: imageUrl,
                fit: BoxFit.cover,
                placeholder: (context, url) => Container(
                  color: const Color(0xFFF1F5F9),
                  child: const Center(
                    child: SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.inkBlack),
                    ),
                  ),
                ),
                errorWidget: (context, url, error) => Container(
                  color: const Color(0xFFF1F5F9),
                  child: const Icon(Icons.fastfood_rounded, color: AppTheme.inkBlack, size: 26),
                ),
              ),
            ),
          ),
          const SizedBox(width: 14),

          // Product Details
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item['product_name'] ?? item['name'] ?? 'Produk Kuliner',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppTheme.inkBlack),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 3),
                Text(
                  '${CurrencyFormatter.formatRupiah(itemPrice)} / porsi',
                  style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                ),
                const SizedBox(height: 4),
                Text(
                  CurrencyFormatter.formatRupiah(itemPrice * qty),
                  style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13, color: AppTheme.inkBlack),
                ),
              ],
            ),
          ),

          const SizedBox(width: 8),

          // Quantity Stepper Controls
          Container(
            height: 36,
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                IconButton(
                  icon: Icon(
                    qty == 1 ? Icons.delete_outline_rounded : Icons.remove_rounded,
                    size: 16,
                    color: qty == 1 ? const Color(0xFFEF4444) : AppTheme.inkBlack,
                  ),
                  constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                  padding: EdgeInsets.zero,
                  onPressed: () async {
                    if (qty > 1) {
                      await customerCtrl.updateCartQty(productId, qty - 1, cartId: cartId);
                    } else {
                      await customerCtrl.removeFromCart(productId, cartId: cartId);
                    }
                  },
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 6),
                  child: Text(
                    '$qty',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppTheme.inkBlack),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.add_rounded, size: 16, color: AppTheme.inkBlack),
                  constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                  padding: EdgeInsets.zero,
                  onPressed: () async {
                    await customerCtrl.updateCartQty(productId, qty + 1, cartId: cartId);
                  },
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFoodSuggestionsSection(BuildContext context, CustomerController customerCtrl) {
    final List<dynamic> realProducts = customerCtrl.recommendedProducts;
    final itemsToDisplay = realProducts.isNotEmpty ? realProducts : _foodSuggestions;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: const [
            Icon(Icons.thumb_up_alt_rounded, size: 18, color: AppTheme.inkBlack),
            SizedBox(width: 8),
            Text(
              'Rekomendasi Makanan Untukmu',
              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
            ),
          ],
        ),
        const SizedBox(height: 12),
        SizedBox(
          height: 225,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            itemCount: itemsToDisplay.length,
            itemBuilder: (context, index) {
              final rawItem = itemsToDisplay[index];
              final Map<String, dynamic> food = rawItem is Map<String, dynamic>
                  ? rawItem
                  : Map<String, dynamic>.from(rawItem as Map);

              final int foodId = int.tryParse(food['id']?.toString() ?? food['product_id']?.toString() ?? '0') ?? 0;
              final String foodName = (food['name'] ?? food['product_name'] ?? 'Menu Kuliner').toString();
              final String storeName = (food['store_name'] ?? food['store'] ?? 'Mitra CicalengkaGO').toString();
              final double foodPrice = double.tryParse(food['final_price']?.toString() ?? food['price']?.toString() ?? '0') ?? 0.0;
              final String rating = food['rating']?.toString() ?? '4.8';
              final String imgUrl = _getFoodImage(food);

              return GestureDetector(
                onTap: () => ProductDetailModal.show(context, food),
                child: Container(
                  width: 160,
                margin: const EdgeInsets.only(right: 14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.03),
                      blurRadius: 8,
                      offset: const Offset(0, 3),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Food Image + Rating
                    Stack(
                      children: [
                        ClipRRect(
                          borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                          child: CachedNetworkImage(
                            imageUrl: imgUrl,
                            height: 100,
                            width: double.infinity,
                            fit: BoxFit.cover,
                            errorWidget: (_, __, ___) => Container(
                              height: 100,
                              color: const Color(0xFFF1F5F9),
                              child: const Icon(Icons.fastfood_rounded, color: AppTheme.inkBlack),
                            ),
                          ),
                        ),
                        Positioned(
                          top: 8,
                          right: 8,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: Colors.black.withValues(alpha: 0.75),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.star_rounded, color: Colors.amber, size: 12),
                                const SizedBox(width: 2),
                                Text(
                                  rating,
                                  style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                    Padding(
                      padding: const EdgeInsets.all(10.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            foodName,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppTheme.inkBlack),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 2),
                          Text(
                            storeName,
                            style: const TextStyle(fontSize: 10, color: Color(0xFF64748B)),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 8),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Text(
                                  CurrencyFormatter.formatRupiah(foodPrice),
                                  style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 11, color: AppTheme.inkBlack),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              InkWell(
                                onTap: () async {
                                  final success = await customerCtrl.addToCart(foodId, 1);
                                  if (context.mounted) {
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      SnackBar(
                                        content: Text(
                                          success ? '$foodName ditambahkan!' : 'Berhasil ditambahkan ke keranjang!',
                                        ),
                                        backgroundColor: AppTheme.inkBlack,
                                        duration: const Duration(seconds: 2),
                                        behavior: SnackBarBehavior.floating,
                                      ),
                                    );
                                  }
                                },
                                borderRadius: BorderRadius.circular(20),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                                  decoration: BoxDecoration(
                                    color: AppTheme.inkBlack,
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: const Text(
                                    '+ Tambah',
                                    style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                                  ),
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
            );
            },
          ),
        ),
      ],
    );
  }

  void _confirmClearCart(BuildContext context, CustomerController customerCtrl) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Kosongkan Keranjang?', style: TextStyle(fontWeight: FontWeight.bold)),
        content: const Text('Apakah Anda yakin ingin menghapus seluruh isi keranjang belanja?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal', style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
          ),
          UberPillButton(
            label: 'Kosongkan',
            bgColor: const Color(0xFFEF4444),
            textColor: Colors.white,
            onPressed: () async {
              Navigator.pop(ctx);
              await customerCtrl.clearCart();
              if (context.mounted) {
                AppAlert.showInfo(
                  context,
                  title: 'Keranjang Dikosongkan',
                  message: 'Seluruh isi keranjang belanja telah dibersihkan.',
                );
              }
            },
          ),
        ],
      ),
    );
  }
}
