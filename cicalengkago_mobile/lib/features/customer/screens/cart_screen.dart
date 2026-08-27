import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/uber_pill_button.dart';
import '../controllers/customer_controller.dart';
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
      context.read<CustomerController>().fetchCart();
    });
  }

  @override
  Widget build(BuildContext context) {
    final customerCtrl = context.watch<CustomerController>();
    final cart = customerCtrl.cart;

    // Support flat items list or grouped stores list
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

    return Scaffold(
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
                    _buildFlatItemsCard(context, customerCtrl, rawItems),
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
                    color: Colors.black.withValues(alpha: 0.06),
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
                              color: AppTheme.inkBlack,
                            ),
                          ),
                        ],
                      ),
                    ),
                    UberPillButton(
                      label: 'Lanjut Checkout',
                      icon: Icons.arrow_forward_rounded,
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
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEFEFEF),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.storefront_rounded, color: AppTheme.inkBlack, size: 18),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    store['store_name'] ?? store['name'] ?? 'Mitra Resto',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppTheme.inkBlack),
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

  Widget _buildFlatItemsCard(BuildContext context, CustomerController customerCtrl, List<dynamic> items) {
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
        children: items.asMap().entries.map((entry) {
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
      ),
    );
  }

  Widget _buildCartItemTile(BuildContext context, CustomerController customerCtrl, Map<String, dynamic> item) {
    final productId = int.tryParse(item['product_id']?.toString() ?? item['id']?.toString() ?? '0') ?? 0;
    final itemPrice = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
    final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;

    final imageUrl = item['image'] != null && item['image'].toString().isNotEmpty
        ? ApiConstants.formatImageUrl(item['image'].toString())
        : null;

    return Padding(
      padding: const EdgeInsets.all(14.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          // Product Thumbnail Image
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Container(
              width: 58,
              height: 58,
              color: const Color(0xFFF1F5F9),
              child: imageUrl != null
                  ? CachedNetworkImage(
                      imageUrl: imageUrl,
                      fit: BoxFit.cover,
                      errorWidget: (context, url, error) => const Icon(Icons.fastfood_rounded, color: AppTheme.inkBlack, size: 26),
                    )
                  : const Icon(Icons.fastfood_rounded, color: AppTheme.inkBlack, size: 26),
            ),
          ),
          const SizedBox(width: 14),

          // Product Details
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item['product_name'] ?? item['name'] ?? 'Produk',
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
                      await customerCtrl.updateCartQty(productId, qty - 1);
                    } else {
                      await customerCtrl.removeFromCart(productId);
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
                    await customerCtrl.updateCartQty(productId, qty + 1);
                  },
                ),
              ],
            ),
          ),
        ],
      ),
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
            },
          ),
        ],
      ),
    );
  }
}
