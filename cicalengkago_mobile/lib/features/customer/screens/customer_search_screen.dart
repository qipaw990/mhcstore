import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/network/api_service.dart';
import '../controllers/customer_controller.dart';
import 'store_detail_screen.dart';

class CustomerSearchScreen extends StatefulWidget {
  final String initialQuery;
  const CustomerSearchScreen({super.key, this.initialQuery = ''});

  @override
  State<CustomerSearchScreen> createState() => _CustomerSearchScreenState();
}

class _CustomerSearchScreenState extends State<CustomerSearchScreen> {
  final TextEditingController _searchCtrl = TextEditingController();
  bool _isSearching = false;
  List<dynamic> _products = [];
  List<dynamic> _stores = [];

  final List<Map<String, String>> _trendingChips = const [
    {'icon': '🌶️', 'label': 'Seblak', 'query': 'Seblak'},
    {'icon': '🎂', 'label': 'Bento Cake', 'query': 'Bento'},
    {'icon': '🍔', 'label': 'Burger Bangor', 'query': 'Burger'},
    {'icon': '🍚', 'label': 'Nasi Goreng', 'query': 'Nasi Goreng'},
    {'icon': '🍡', 'label': 'Cimol Padang', 'query': 'Cimol'},
    {'icon': '🍢', 'label': 'Sate Ade', 'query': 'Sate'},
  ];

  @override
  void initState() {
    super.initState();
    _searchCtrl.text = widget.initialQuery;
    if (widget.initialQuery.isNotEmpty) {
      _performSearch(widget.initialQuery);
    }
  }

  Future<void> _performSearch(String query) async {
    if (query.trim().isEmpty) return;

    setState(() {
      _isSearching = true;
    });

    try {
      final prodRes = await ApiService.get('${ApiConstants.products}?q=${Uri.encodeComponent(query)}');
      if (prodRes['success'] == true && prodRes['data'] != null) {
        _products = prodRes['data'] as List<dynamic>;
      }

      final storeRes = await ApiService.get('${ApiConstants.stores}?q=${Uri.encodeComponent(query)}');
      if (storeRes['success'] == true && storeRes['data'] != null) {
        _stores = storeRes['data'] as List<dynamic>;
      }
    } catch (_) {}

    setState(() {
      _isSearching = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        titleSpacing: 0,
        title: Padding(
          padding: const EdgeInsets.only(right: 16.0),
          child: TextField(
            controller: _searchCtrl,
            autofocus: widget.initialQuery.isEmpty,
            onSubmitted: _performSearch,
            decoration: InputDecoration(
              hintText: 'Cari seblak, bento cake, nasi goreng...',
              hintStyle: const TextStyle(fontSize: 13, color: Colors.grey),
              prefixIcon: const Icon(Icons.search, size: 20, color: AppTheme.primaryRed),
              suffixIcon: _searchCtrl.text.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear, size: 18),
                      onPressed: () {
                        _searchCtrl.clear();
                        setState(() {
                          _products.clear();
                          _stores.clear();
                        });
                      },
                    )
                  : null,
              contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
              filled: true,
              fillColor: const Color(0xFFF1F5F9),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(30),
                borderSide: BorderSide.none,
              ),
            ),
          ),
        ),
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Trending Chips Section
            Container(
              padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
              color: Colors.white,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.local_fire_department, size: 16, color: Colors.redAccent),
                      SizedBox(width: 4),
                      Text(
                        'TRENDING HARI INI',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: Colors.grey,
                          letterSpacing: 0.5,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: _trendingChips.map((chip) {
                        return Padding(
                          padding: const EdgeInsets.only(right: 8.0),
                          child: ActionChip(
                            avatar: Text(chip['icon']!),
                            label: Text(chip['label']!),
                            labelStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                            backgroundColor: const Color(0xFFF8FAFC),
                            side: BorderSide(color: Colors.grey.shade300),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                            onPressed: () {
                              _searchCtrl.text = chip['query']!;
                              _performSearch(chip['query']!);
                            },
                          ),
                        );
                      }).toList(),
                    ),
                  ),
                ],
              ),
            ),

            if (_isSearching)
              const Padding(
                padding: EdgeInsets.all(32.0),
                child: Center(child: CircularProgressIndicator()),
              )
            else if (_searchCtrl.text.isNotEmpty && _products.isEmpty && _stores.isEmpty)
              const Padding(
                padding: EdgeInsets.all(32.0),
                child: Center(
                  child: Column(
                    children: [
                      Icon(Icons.search_off, size: 48, color: Colors.grey),
                      SizedBox(height: 12),
                      Text(
                        'Tidak ditemukan hasil untuk pencarian Anda',
                        style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
              )
            else ...[
              // Stores Result Section
              if (_stores.isNotEmpty) ...[
                const Padding(
                  padding: EdgeInsets.fromLTRB(16, 16, 16, 8),
                  child: Text(
                    'Toko & Resto',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                  ),
                ),
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: _stores.length,
                  itemBuilder: (context, index) {
                    final store = _stores[index];
                    final logoUrl = ApiConstants.formatImageUrl(store['logo']?.toString() ?? store['cover_photo']?.toString());

                    return Card(
                      margin: const EdgeInsets.only(bottom: 10),
                      elevation: 1,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      child: ListTile(
                        leading: ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: CachedNetworkImage(
                            imageUrl: logoUrl,
                            width: 48,
                            height: 48,
                            fit: BoxFit.cover,
                            errorWidget: (_, __, ___) => const Icon(Icons.store),
                          ),
                        ),
                        title: Text(
                          store['name'] ?? '',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                        ),
                        subtitle: Text(
                          '${store['address'] ?? 'Cicalengka'} • ${store['delivery_time'] ?? '15-25 min'}',
                          style: const TextStyle(fontSize: 12),
                        ),
                        trailing: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.amber.shade50,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: Colors.amber),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.star, size: 12, color: Colors.amber),
                              const SizedBox(width: 2),
                              Text(
                                '${store['rating'] ?? '4.8'}',
                                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold),
                              ),
                            ],
                          ),
                        ),
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => StoreDetailScreen(storeId: int.parse(store['id'].toString())),
                            ),
                          );
                        },
                      ),
                    );
                  },
                ),
              ],

              // Products Result Section
              if (_products.isNotEmpty) ...[
                const Padding(
                  padding: EdgeInsets.fromLTRB(16, 16, 16, 8),
                  child: Text(
                    'Menu Makanan & Produk',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                  ),
                ),
                GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    childAspectRatio: 0.72,
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                  ),
                  itemCount: _products.length,
                  itemBuilder: (context, index) {
                    final prod = _products[index];
                    final double price = double.tryParse(prod['price']?.toString() ?? '0') ?? 0;
                    final double finalPrice = double.tryParse(prod['final_price']?.toString() ?? price.toString()) ?? price;
                    final imgUrl = ApiConstants.formatImageUrl(prod['image']?.toString());

                    return GestureDetector(
                      onTap: () {
                        if (prod['store_id'] != null) {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => StoreDetailScreen(storeId: int.parse(prod['store_id'].toString())),
                            ),
                          );
                        }
                      },
                      child: Container(
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: Colors.grey.shade200),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.04),
                              blurRadius: 6,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            ClipRRect(
                              borderRadius: const BorderRadius.vertical(top: Radius.circular(14)),
                              child: CachedNetworkImage(
                                imageUrl: imgUrl,
                                height: 110,
                                width: double.infinity,
                                fit: BoxFit.cover,
                                errorWidget: (_, url, error) => Container(color: Colors.grey[200]),
                              ),
                            ),
                            Padding(
                              padding: const EdgeInsets.all(8.0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    prod['store_name'] ?? 'Mitra CicalengkaGO',
                                    style: const TextStyle(fontSize: 10, color: Colors.grey),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    prod['name'] ?? '',
                                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    CurrencyFormatter.formatRupiah(finalPrice),
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w800,
                                      color: AppTheme.primaryRed,
                                    ),
                                  ),
                                  const SizedBox(height: 6),
                                  SizedBox(
                                    width: double.infinity,
                                    height: 28,
                                    child: ElevatedButton.icon(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: AppTheme.primaryRed,
                                        foregroundColor: Colors.white,
                                        padding: EdgeInsets.zero,
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                      ),
                                      onPressed: () async {
                                        final ok = await context.read<CustomerController>().addToCart(
                                          int.parse(prod['id'].toString()),
                                          1,
                                        );
                                        if (ok && context.mounted) {
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            SnackBar(
                                              content: Text('${prod['name']} ditambahkan ke keranjang'),
                                              duration: const Duration(seconds: 1),
                                            ),
                                          );
                                        }
                                      },
                                      icon: const Icon(Icons.add, size: 14),
                                      label: const Text('+ Tambah', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                                    ),
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
              ],
            ],
          ],
        ),
      ),
    );
  }
}
