import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
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
        foregroundColor: const Color(0xFF0F172A),
        title: const Text(
          'Keranjang Belanja',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
        ),
        actions: [
          if (!isEmpty)
            IconButton(
              tooltip: 'Kosongkan Keranjang',
              icon: const Icon(Icons.delete_sweep_rounded, color: AppTheme.primaryRed),
              onPressed: () => _confirmClearCart(context, customerCtrl),
            ),
        ],
      ),
      body: isEmpty
          ? _buildEmptyState()
          : RefreshIndicator(
              onRefresh: () => customerCtrl.fetchCart(),
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (stores.isNotEmpty)
                    ...stores.map((store) => _buildStoreCard(context, customerCtrl, store))
                  else
                    _buildFlatItemsCard(context, customerCtrl, rawItems),
                ],
              ),
            ),
      bottomNavigationBar: isEmpty
          ? null
          : Container(
              padding: const EdgeInsets.all(16),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
                boxShadow: [
                  BoxShadow(color: Colors.black12, blurRadius: 10, offset: Offset(0, -2)),
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
                          const Text('Total Pembelian', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                          const SizedBox(height: 2),
                          Text(
                            CurrencyFormatter.formatRupiah(subtotal),
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.primaryRed,
                            ),
                          ),
                        ],
                      ),
                    ),
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.primaryRed,
                        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        elevation: 2,
                      ),
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => const CheckoutScreen()),
                        );
                      },
                      child: const Row(
                        children: [
                          Text('LANJUT CHECKOUT', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white)),
                          SizedBox(width: 6),
                          Icon(Icons.arrow_forward_rounded, size: 16, color: Colors.white),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 100,
              height: 100,
              decoration: const BoxDecoration(
                color: Color(0xFFFEE2E2),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.shopping_bag_outlined, size: 48, color: AppTheme.primaryRed),
            ),
            const SizedBox(height: 20),
            const Text(
              'Keranjang Belanja Kosong',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 8),
            const Text(
              'Yuk, temukan makanan dan produk terbaik\ndi CicalengkaGO dan tambahkan ke keranjang!',
              style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
              textAlign: TextAlign.center,
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
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 8)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(14.0),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEE2E2),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.storefront_rounded, color: AppTheme.primaryRed, size: 18),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    store['store_name'] ?? store['name'] ?? 'Mitra Resto',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          ...items.map((item) => _buildCartItemTile(context, customerCtrl, item)),
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
      ),
      child: Column(
        children: items.map((item) => _buildCartItemTile(context, customerCtrl, item)).toList(),
      ),
    );
  }

  Widget _buildCartItemTile(BuildContext context, CustomerController customerCtrl, Map<String, dynamic> item) {
    final productId = int.tryParse(item['product_id']?.toString() ?? item['id']?.toString() ?? '0') ?? 0;
    final itemPrice = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
    final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;

    return Padding(
      padding: const EdgeInsets.all(14.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          // Item Image / Icon
          Container(
            width: 50,
            height: 50,
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(Icons.fastfood_rounded, color: AppTheme.primaryRed, size: 24),
          ),
          const SizedBox(width: 12),

          // Details
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item['product_name'] ?? item['name'] ?? 'Produk',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A)),
                ),
                const SizedBox(height: 2),
                Text(
                  CurrencyFormatter.formatRupiah(itemPrice),
                  style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                ),
                const SizedBox(height: 4),
                Text(
                  CurrencyFormatter.formatRupiah(itemPrice * qty),
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppTheme.primaryRed),
                ),
              ],
            ),
          ),

          // Quantity controls
          Container(
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.remove, size: 16),
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
                Text(
                  '$qty',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                ),
                IconButton(
                  icon: const Icon(Icons.add, size: 16),
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
        title: const Text('Kosongkan Keranjang?'),
        content: const Text('Apakah Anda yakin ingin menghapus seluruh isi keranjang belanja?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primaryRed),
            onPressed: () async {
              Navigator.pop(ctx);
              await customerCtrl.clearCart();
            },
            child: const Text('Kosongkan'),
          ),
        ],
      ),
    );
  }
}
