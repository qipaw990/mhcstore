import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/customer_controller.dart';
import 'cart_screen.dart';

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

  @override
  void initState() {
    super.initState();
    _fetchStoreDetail();
  }

  Future<void> _fetchStoreDetail() async {
    final res = await ApiService.get('${ApiConstants.storeDetail}/${widget.storeId}');
    if (res['success'] == true && res['data'] != null) {
      final data = res['data'];
      setState(() {
        _storeData = data['store'] is Map<String, dynamic> ? data['store'] : (data is Map<String, dynamic> ? data : {});
        _products = data['products'] is List ? data['products'] : [];
        _isLoading = false;
      });
    } else {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final customerCtrl = context.watch<CustomerController>();

    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(),
        body: const Center(child: CircularProgressIndicator()),
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
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 200,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: CachedNetworkImage(
                imageUrl: coverUrl,
                fit: BoxFit.cover,
                errorWidget: (_, url, error) => Container(
                  color: AppTheme.primaryRed,
                  child: const Center(child: Icon(Icons.storefront_rounded, size: 60, color: Colors.white70)),
                ),
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      if (rawLogo != null && rawLogo.trim().isNotEmpty) ...[
                        ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: CachedNetworkImage(
                            imageUrl: ApiConstants.formatImageUrl(rawLogo),
                            width: 48,
                            height: 48,
                            fit: BoxFit.cover,
                            errorWidget: (_, __, ___) => Container(
                              width: 48,
                              height: 48,
                              color: Colors.grey[200],
                              child: const Icon(Icons.store, size: 24, color: Colors.grey),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                      ],
                      Expanded(
                        child: Text(
                          store['name'] ?? 'Mitra Resto',
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: isOpen ? Colors.green : Colors.red,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          isOpen ? 'BUKA' : 'TUTUP',
                          style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.star, size: 16, color: Colors.amber),
                      const SizedBox(width: 4),
                      Text(
                        '${store['rating'] ?? '4.8'} (${store['reviews_count'] ?? '50+'} ulasan)',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(width: 10),
                      const Icon(Icons.access_time, size: 14, color: Colors.grey),
                      const SizedBox(width: 4),
                      Text(
                        '${store['delivery_time'] ?? '15-25 min'}',
                        style: const TextStyle(fontSize: 12, color: Colors.grey),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.location_on_outlined, size: 14, color: Colors.grey),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          store['address'] ?? 'Cicalengka, Kab. Bandung',
                          style: const TextStyle(fontSize: 12, color: Colors.grey),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  const Divider(height: 24),
                  const Text(
                    'Daftar Menu Makanan & Minuman',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                ],
              ),
            ),
          ),
          SliverList(
            delegate: SliverChildBuilderDelegate(
              (context, index) {
                final product = _products[index];
                final price = double.tryParse(product['price']?.toString() ?? '0') ?? 0.0;
                final finalPrice = double.tryParse(product['final_price']?.toString() ?? price.toString()) ?? price;
                final imgUrl = ApiConstants.formatImageUrl(product['image']?.toString());

                return Card(
                  margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                  elevation: 1,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: ListTile(
                    contentPadding: const EdgeInsets.all(8),
                    leading: ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: CachedNetworkImage(
                        imageUrl: imgUrl,
                        width: 60,
                        height: 60,
                        fit: BoxFit.cover,
                        errorWidget: (_, url, error) => Container(color: Colors.grey[200]),
                      ),
                    ),
                    title: Text(
                      product['name'] ?? 'Menu',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                    ),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (product['description'] != null && product['description'].toString().isNotEmpty)
                          Text(
                            product['description'].toString(),
                            style: const TextStyle(fontSize: 11, color: Colors.grey),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            Text(
                              CurrencyFormatter.formatRupiah(finalPrice),
                              style: const TextStyle(color: AppTheme.primaryRed, fontWeight: FontWeight.bold, fontSize: 13),
                            ),
                            if (price > finalPrice) ...[
                              const SizedBox(width: 6),
                              Text(
                                CurrencyFormatter.formatRupiah(price),
                                style: const TextStyle(fontSize: 10, color: Colors.grey, decoration: TextDecoration.lineThrough),
                              ),
                            ],
                          ],
                        ),
                      ],
                    ),
                    trailing: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.primaryRed,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        minimumSize: Size.zero,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      onPressed: () async {
                        final ok = await customerCtrl.addToCart(
                          int.parse(product['id'].toString()),
                          1,
                        );
                        if (ok && context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text('${product['name']} ditambahkan ke keranjang'),
                              duration: const Duration(seconds: 1),
                            ),
                          );
                        }
                      },
                      child: const Text('+ Tambah', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                    ),
                  ),
                );
              },
              childCount: _products.length,
            ),
          ),
        ],
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.all(16),
        color: Colors.white,
        child: ElevatedButton.icon(
          style: ElevatedButton.styleFrom(
            backgroundColor: AppTheme.primaryRed,
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 14),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
          onPressed: () {
            Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const CartScreen()),
            );
          },
          icon: const Icon(Icons.shopping_cart),
          label: const Text('Buka Keranjang Belanja', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
        ),
      ),
    );
  }
}
