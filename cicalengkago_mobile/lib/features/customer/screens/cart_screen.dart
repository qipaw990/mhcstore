import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/utils/item_options_helper.dart';
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
    final rawImg = item['product_image']?.toString() ??
        item['image']?.toString() ??
        item['image_url']?.toString() ??
        item['photo']?.toString() ??
        item['product_photo']?.toString() ??
        item['cover_photo']?.toString() ??
        item['thumbnail']?.toString() ??
        (item['product'] is Map ? (item['product']['image'] ?? item['product']['product_image'])?.toString() : null);
    if (rawImg != null && rawImg.isNotEmpty && !rawImg.contains('null')) {
      final formatted = ApiConstants.formatImageUrl(rawImg);
      if (formatted.isNotEmpty) return formatted;
    }
    return '';
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
          scrolledUnderElevation: 1,
          foregroundColor: AppTheme.inkBlack,
          centerTitle: false,
          title: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Keranjang Belanja',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppTheme.inkBlack),
              ),
              if (!isEmpty)
                Text(
                  '${stores.isNotEmpty ? stores.length : 1} Toko • Siap checkout',
                  style: const TextStyle(fontSize: 11, color: AppTheme.textMute, fontWeight: FontWeight.w500),
                ),
            ],
          ),
          actions: [
            if (!isEmpty)
              Padding(
                padding: const EdgeInsets.only(right: 8),
                child: IconButton(
                  tooltip: 'Kosongkan Keranjang',
                  icon: Container(
                    padding: const EdgeInsets.all(7),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEE2E2),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.delete_outline_rounded, color: Color(0xFFEF4444), size: 18),
                  ),
                  onPressed: () => _confirmClearCart(context, customerCtrl),
                ),
              ),
          ],
        ),
        body: isEmpty
            ? _buildEmptyState(context)
            : RefreshIndicator(
                color: AppTheme.primaryRed,
                onRefresh: () => customerCtrl.fetchCart(),
                child: ListView(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                  children: [
                    if (stores.isNotEmpty)
                      ...stores.map((store) => _buildStoreCard(context, customerCtrl, store))
                    else
                      _buildFlatItemsCard(context, customerCtrl, rawItems, cart?['store'] as Map<String, dynamic>?),
                    const SizedBox(height: 20),
                    _buildFoodSuggestionsSection(context, customerCtrl),
                    const SizedBox(height: 100),
                  ],
                ),
              ),
        bottomNavigationBar: isEmpty
            ? null
            : Container(
                padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF1E293B).withValues(alpha: 0.08),
                      blurRadius: 20,
                      offset: const Offset(0, -6),
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
                            Row(
                              children: [
                                const Text(
                                  'Total Pesanan',
                                  style: TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                                ),
                                const SizedBox(width: 4),
                                const Icon(Icons.info_outline_rounded, size: 12, color: Color(0xFF94A3B8)),
                              ],
                            ),
                            const SizedBox(height: 3),
                            Text(
                              CurrencyFormatter.formatRupiah(subtotal),
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w900,
                                color: AppTheme.inkBlack,
                                letterSpacing: -0.3,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 16),
                      Container(
                        decoration: BoxDecoration(
                          gradient: AppTheme.primaryGradient,
                          borderRadius: BorderRadius.circular(16),
                          boxShadow: [
                            BoxShadow(
                              color: AppTheme.primaryRed.withValues(alpha: 0.35),
                              blurRadius: 12,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Material(
                          color: Colors.transparent,
                          child: InkWell(
                            borderRadius: BorderRadius.circular(16),
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(builder: (_) => const CheckoutScreen()),
                              );
                            },
                            child: const Padding(
                              padding: EdgeInsets.symmetric(horizontal: 22, vertical: 14),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(
                                    'Checkout',
                                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                                  ),
                                  SizedBox(width: 8),
                                  Icon(Icons.arrow_forward_rounded, size: 18, color: Colors.white),
                                ],
                              ),
                            ),
                          ),
                        ),
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
        padding: const EdgeInsets.symmetric(horizontal: 32.0, vertical: 48),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 100,
              height: 100,
              decoration: BoxDecoration(
                color: AppTheme.brandCream,
                shape: BoxShape.circle,
                border: Border.all(color: AppTheme.cardBorder, width: 2),
                boxShadow: AppTheme.cardShadow,
              ),
              child: const Center(
                child: Icon(Icons.shopping_bag_outlined, size: 48, color: AppTheme.primaryRed),
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'Keranjang Masih Kosong',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: AppTheme.inkBlack),
            ),
            const SizedBox(height: 8),
            const Text(
              'Yuk, temukan makanan dan kuliner favoritmu\ndi CicalengkaGO dan tambahkan ke keranjang!',
              style: TextStyle(fontSize: 13.5, color: Color(0xFF64748B), height: 1.5),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 28),
            Container(
              decoration: BoxDecoration(
                gradient: AppTheme.primaryGradient,
                borderRadius: BorderRadius.circular(999),
                boxShadow: [
                  BoxShadow(
                    color: AppTheme.primaryRed.withValues(alpha: 0.3),
                    blurRadius: 16,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  borderRadius: BorderRadius.circular(999),
                  onTap: () => Navigator.pop(context),
                  child: const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 28, vertical: 14),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.restaurant_menu_rounded, color: Colors.white, size: 20),
                        SizedBox(width: 10),
                        Text(
                          'Mulai Jelajah Kuliner',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
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
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFFFE5D0).withValues(alpha: 0.8)),
        boxShadow: AppTheme.cardShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    width: 40,
                    height: 40,
                    color: AppTheme.brandCream,
                    child: (storeLogoUrl != null)
                        ? CachedNetworkImage(
                            imageUrl: storeLogoUrl,
                            fit: BoxFit.cover,
                            placeholder: (_, __) => const Center(
                              child: SizedBox(
                                width: 14,
                                height: 14,
                                child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.primaryRed),
                              ),
                            ),
                            errorWidget: (_, __, ___) => const Icon(
                              Icons.storefront_rounded,
                              color: AppTheme.primaryRed,
                              size: 22,
                            ),
                          )
                        : const Icon(
                            Icons.storefront_rounded,
                            color: AppTheme.primaryRed,
                            size: 22,
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
                        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14.5, color: AppTheme.inkBlack),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          Icon(Icons.verified_rounded, size: 12, color: AppTheme.brandOrange),
                          const SizedBox(width: 4),
                          const Text(
                            'Mitra Resmi CicalengkaGO',
                            style: TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppTheme.brandCream,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: const Color(0xFFFFE5D0)),
                  ),
                  child: Text(
                    '${items.length} Menu',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppTheme.primaryRed),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFF8FAFC)),
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
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFFFE5D0).withValues(alpha: 0.8)),
        boxShadow: AppTheme.cardShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    width: 40,
                    height: 40,
                    color: AppTheme.brandCream,
                    child: (storeLogoUrl != null)
                        ? CachedNetworkImage(
                            imageUrl: storeLogoUrl,
                            fit: BoxFit.cover,
                            placeholder: (_, __) => const Center(
                              child: SizedBox(
                                width: 14,
                                height: 14,
                                child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.primaryRed),
                              ),
                            ),
                            errorWidget: (_, __, ___) => const Icon(
                              Icons.storefront_rounded,
                              color: AppTheme.primaryRed,
                              size: 22,
                            ),
                          )
                        : const Icon(
                            Icons.storefront_rounded,
                            color: AppTheme.primaryRed,
                            size: 22,
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
                        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14.5, color: AppTheme.inkBlack),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          Icon(Icons.verified_rounded, size: 12, color: AppTheme.brandOrange),
                          const SizedBox(width: 4),
                          const Text(
                            'Mitra Resmi CicalengkaGO',
                            style: TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppTheme.brandCream,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: const Color(0xFFFFE5D0)),
                  ),
                  child: Text(
                    '${items.length} Menu',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppTheme.primaryRed),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFF8FAFC)),
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

  Widget _buildCartItemTile(BuildContext context, CustomerController customerCtrl, Map<String, dynamic> item) {
    final cartId = int.tryParse(item['id']?.toString() ?? '0') ?? 0;
    final productId = int.tryParse(item['product_id']?.toString() ?? item['id']?.toString() ?? '0') ?? 0;
    final itemPrice = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
    final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;
    final imageUrl = _getFoodImage(item);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 14.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          // Product Food Image with ClipRRect
          ClipRRect(
            borderRadius: BorderRadius.circular(14),
            child: SizedBox(
              width: 68,
              height: 68,
              child: (imageUrl.isNotEmpty)
                  ? CachedNetworkImage(
                      imageUrl: imageUrl,
                      fit: BoxFit.cover,
                      placeholder: (context, url) => Container(
                        color: const Color(0xFFF1F5F9),
                        child: const Center(
                          child: SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.primaryRed),
                          ),
                        ),
                      ),
                      errorWidget: (context, url, error) => Container(
                        color: AppTheme.brandCream,
                        child: const Icon(Icons.fastfood_rounded, color: AppTheme.brandOrange, size: 26),
                      ),
                    )
                  : Container(
                      color: AppTheme.brandCream,
                      child: const Icon(Icons.fastfood_rounded, color: AppTheme.brandOrange, size: 26),
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
                  style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14, color: AppTheme.inkBlack),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                // Variation badge
                Builder(
                  builder: (context) {
                    final varName = ItemOptionsHelper.getVariationName(item);
                    if (varName == null || varName.isEmpty) return const SizedBox.shrink();
                    return Padding(
                      padding: const EdgeInsets.only(top: 3),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFF3E0),
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: const Color(0xFFFFCC80)),
                        ),
                        child: Text(
                          'Varian: $varName',
                          style: const TextStyle(fontSize: 10.5, color: Color(0xFFE65100), fontWeight: FontWeight.bold),
                        ),
                      ),
                    );
                  },
                ),
                // Addons / Toppings badge
                Builder(
                  builder: (context) {
                    final addons = ItemOptionsHelper.getAddonNames(item);
                    if (addons.isEmpty) return const SizedBox.shrink();
                    return Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child: Text(
                        '+ Topping: ${addons.join(", ")}',
                        style: const TextStyle(fontSize: 10.5, color: Color(0xFFD97706), fontWeight: FontWeight.w600),
                      ),
                    );
                  },
                ),
                // Special notes
                Builder(
                  builder: (context) {
                    final notes = ItemOptionsHelper.getItemNotes(item);
                    if (notes == null || notes.isEmpty) return const SizedBox.shrink();
                    return Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child: Text(
                        'Catatan: $notes',
                        style: const TextStyle(fontSize: 10, fontStyle: FontStyle.italic, color: Color(0xFF64748B)),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    );
                  },
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Text(
                      CurrencyFormatter.formatRupiah(itemPrice * qty),
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13.5, color: AppTheme.primaryRed),
                    ),
                    if (qty > 1) ...[
                      const SizedBox(width: 4),
                      Text(
                        '(${CurrencyFormatter.formatRupiah(itemPrice)} / porsi)',
                        style: const TextStyle(fontSize: 10.5, color: Color(0xFF94A3B8)),
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(width: 8),

          // Quantity Stepper Controls
          Container(
            height: 36,
            decoration: BoxDecoration(
              color: AppTheme.brandCream,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: const Color(0xFFFFE5D0)),
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
                    style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13, color: AppTheme.inkBlack),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.add_rounded, size: 16, color: AppTheme.primaryRed),
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
    final List<dynamic> itemsToDisplay = customerCtrl.recommendedProducts;
    if (itemsToDisplay.isEmpty) {
      return const SizedBox.shrink();
    }

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
              final double? rVal = double.tryParse(food['rating']?.toString() ?? food['avg_rating']?.toString() ?? '');
              final String rating = rVal != null ? rVal.toStringAsFixed(1) : '-';
              final String imgUrl = _getFoodImage(food);
              final rawStoreOpen = food['store_is_open'] ?? food['is_store_open'] ?? food['is_currently_open'] ?? food['is_open'];
              final bool isStoreClosed = rawStoreOpen == 0 || rawStoreOpen == false || rawStoreOpen == '0' || rawStoreOpen == 'false';
              final bool storeOpen = (rawStoreOpen == 1 || rawStoreOpen == true || rawStoreOpen == '1' || rawStoreOpen == 'true') && !isStoreClosed;

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
                                onTap: storeOpen ? () async {
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
                                } : null,
                                borderRadius: BorderRadius.circular(20),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                                  decoration: BoxDecoration(
                                    color: storeOpen ? AppTheme.inkBlack : const Color(0xFFE2E8F0),
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: Text(
                                    storeOpen ? 'Tambah' : 'Tutup',
                                    style: TextStyle(
                                      color: storeOpen ? Colors.white : const Color(0xFF64748B),
                                      fontSize: 10,
                                      fontWeight: FontWeight.bold,
                                    ),
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
