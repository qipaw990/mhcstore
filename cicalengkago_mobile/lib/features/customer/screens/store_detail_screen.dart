import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
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
    final res = await ApiService.get('${ApiConstants.stores}/${widget.storeId}');
    if (res['success'] == true && res['data'] != null) {
      setState(() {
        _storeData = res['data']['store'];
        _products = res['data']['products'] ?? [];
        _isLoading = false;
      });
    } else {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _addToCart(int productId) async {
    final res = await ApiService.post(ApiConstants.cart, {
      'product_id': productId.toString(),
      'quantity': '1',
    });

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['message'] ?? 'Menu berhasil ditambahkan ke keranjang!'),
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    final store = _storeData ?? {};
    final coverUrl = store['cover_photo'] != null && store['cover_photo'].toString().startsWith('http')
        ? store['cover_photo']
        : '${ApiConstants.imageBaseUrl}/${store['cover_photo'] ?? ''}';

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 180,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: CachedNetworkImage(
                imageUrl: coverUrl,
                fit: BoxFit.cover,
                errorWidget: (_, __, ___) => Container(color: AppTheme.primaryRed),
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    store['name'] ?? 'Mitra Resto',
                    style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    store['address'] ?? 'Cicalengka',
                    style: const TextStyle(fontSize: 13, color: Colors.grey),
                  ),
                  const SizedBox(height: 16),
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
                final imgUrl = product['image'] != null && product['image'].toString().startsWith('http')
                    ? product['image']
                    : '${ApiConstants.imageBaseUrl}/${product['image'] ?? ''}';

                return Card(
                  margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                  child: ListTile(
                    leading: ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: CachedNetworkImage(
                        imageUrl: imgUrl,
                        width: 50,
                        height: 50,
                        fit: BoxFit.cover,
                        errorWidget: (_, __, ___) => const Icon(Icons.fastfood),
                      ),
                    ),
                    title: Text(
                      product['name'] ?? 'Menu',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                    ),
                    subtitle: Text(
                      CurrencyFormatter.formatRupiah(price),
                      style: const TextStyle(color: AppTheme.primaryRed, fontWeight: FontWeight.bold),
                    ),
                    trailing: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        minimumSize: Size.zero,
                      ),
                      onPressed: () => _addToCart(int.parse(product['id'].toString())),
                      child: const Text('Tambah', style: TextStyle(fontSize: 12)),
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
        child: ElevatedButton(
          onPressed: () {
            Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const CartScreen()),
            );
          },
          child: const Text('Buka Keranjang Belanja'),
        ),
      ),
    );
  }
}
