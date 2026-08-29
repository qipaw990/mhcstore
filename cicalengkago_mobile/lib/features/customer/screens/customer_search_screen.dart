import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/network/api_service.dart';
import '../controllers/customer_controller.dart';
import '../widgets/product_detail_modal.dart';
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
  Timer? _debounceTimer;

  final List<Map<String, String>> _trendingChips = const [
    {'icon': '🌶️', 'label': 'Seblak', 'query': 'Seblak'},
    {'icon': '🎂', 'label': 'Bento Cake', 'query': 'Bento'},
    {'icon': '🍔', 'label': 'Burger', 'query': 'Burger'},
    {'icon': '🍚', 'label': 'Nasi Goreng', 'query': 'Nasi Goreng'},
    {'icon': '🍡', 'label': 'Cimol', 'query': 'Cimol'},
    {'icon': '🍢', 'label': 'Sate', 'query': 'Sate'},
  ];

  @override
  void initState() {
    super.initState();
    _searchCtrl.text = widget.initialQuery;
    if (widget.initialQuery.isNotEmpty) {
      _performSearch(widget.initialQuery);
    }
  }

  @override
  void dispose() {
    _debounceTimer?.cancel();
    _searchCtrl.dispose();
    super.dispose();
  }

  void _onSearchChanged(String query) {
    _debounceTimer?.cancel();
    if (query.trim().isEmpty) {
      setState(() {
        _products.clear();
        _stores.clear();
        _isSearching = false;
      });
      return;
    }
    _debounceTimer = Timer(const Duration(milliseconds: 350), () {
      _performSearch(query);
    });
  }

  Future<void> _performSearch(String query) async {
    final cleanQuery = query.trim();
    if (cleanQuery.isEmpty) return;

    setState(() {
      _isSearching = true;
    });

    try {
      final results = await Future.wait([
        ApiService.get('${ApiConstants.products}?q=${Uri.encodeComponent(cleanQuery)}'),
        ApiService.get('${ApiConstants.stores}?q=${Uri.encodeComponent(cleanQuery)}'),
      ]);

      final prodRes = results[0];
      final storeRes = results[1];

      if (prodRes['success'] == true && prodRes['data'] is List) {
        _products = prodRes['data'] as List<dynamic>;
      } else {
        _products = [];
      }

      if (storeRes['success'] == true && storeRes['data'] is List) {
        _stores = storeRes['data'] as List<dynamic>;
      } else {
        _stores = [];
      }
    } catch (_) {}

    if (mounted) {
      setState(() {
        _isSearching = false;
      });
    }
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
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 0,
        titleSpacing: 0,
        iconTheme: const IconThemeData(color: AppTheme.inkBlack),
        title: Padding(
          padding: const EdgeInsets.only(right: 16.0),
          child: TextField(
            controller: _searchCtrl,
            autofocus: widget.initialQuery.isEmpty,
            onChanged: _onSearchChanged,
            onSubmitted: _performSearch,
            decoration: InputDecoration(
              hintText: 'Cari seblak, bento cake, nasi goreng...',
              hintStyle: const TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
              prefixIcon: const Icon(Icons.search_rounded, size: 20, color: AppTheme.inkBlack),
              suffixIcon: _searchCtrl.text.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear_rounded, size: 18, color: Color(0xFF64748B)),
                      onPressed: () {
                        _searchCtrl.clear();
                        setState(() {
                          _products.clear();
                          _stores.clear();
                          _isSearching = false;
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
              padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
              color: Colors.white,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.local_fire_department_rounded, size: 16, color: AppTheme.inkBlack),
                      SizedBox(width: 4),
                      Text(
                        'TRENDING HARI INI',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                          color: AppTheme.inkBlack,
                          letterSpacing: 0.5,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: _trendingChips.map((chip) {
                        return Padding(
                          padding: const EdgeInsets.only(right: 8.0),
                          child: ActionChip(
                            avatar: Text(chip['icon']!),
                            label: Text(chip['label']!),
                            labelStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                            backgroundColor: const Color(0xFFF8FAFC),
                            side: const BorderSide(color: Color(0xFFE2E8F0)),
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
                padding: EdgeInsets.all(48.0),
                child: Center(
                  child: CircularProgressIndicator(color: AppTheme.inkBlack),
                ),
              )
            else if (_searchCtrl.text.isNotEmpty && _products.isEmpty && _stores.isEmpty)
              const Padding(
                padding: EdgeInsets.all(48.0),
                child: Center(
                  child: Column(
                    children: [
                      CicalengkaGoLogo(size: 64, borderRadius: 20),
                      SizedBox(height: 14),
                      Text(
                        'Tidak ditemukan hasil untuk pencarian Anda',
                        style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.bold, fontSize: 14),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                ),
              )
            else ...[
              // Stores Result Section
              if (_stores.isNotEmpty) ...[
                const Padding(
                  padding: EdgeInsets.fromLTRB(16, 20, 16, 10),
                  child: Text(
                    'Toko & Resto',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppTheme.inkBlack),
                  ),
                ),
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  padding: const EdgeInsets.symmetric(horizontal: 14),
                  itemCount: _stores.length,
                  itemBuilder: (context, index) {
                    final store = _stores[index] is Map<String, dynamic>
                        ? _stores[index] as Map<String, dynamic>
                        : Map<String, dynamic>.from(_stores[index] as Map);
                    final logoUrl = ApiConstants.formatImageUrl(store['logo']?.toString() ?? store['cover_photo']?.toString());
                    final storeId = int.tryParse(store['id']?.toString() ?? '0') ?? 0;

                    return Container(
                      margin: const EdgeInsets.only(bottom: 10),
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
                      child: InkWell(
                        borderRadius: BorderRadius.circular(16),
                        onTap: () {
                          if (storeId > 0) {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => StoreDetailScreen(storeId: storeId),
                              ),
                            );
                          }
                        },
                        child: Padding(
                          padding: const EdgeInsets.all(10.0),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.center,
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(14),
                                child: CachedNetworkImage(
                                  imageUrl: logoUrl,
                                  width: 68,
                                  height: 68,
                                  fit: BoxFit.cover,
                                  errorWidget: (_, __, ___) => Container(
                                    width: 68,
                                    height: 68,
                                    color: const Color(0xFFF1F5F9),
                                    child: const Icon(Icons.storefront_rounded, size: 28, color: Color(0xFF94A3B8)),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Flexible(
                                          child: Text(
                                            store['name'] ?? 'Mitra Resto',
                                            style: const TextStyle(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 13.5,
                                              color: Color(0xFF0F172A),
                                            ),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                        const SizedBox(width: 4),
                                        const Icon(Icons.verified_rounded, size: 14, color: Color(0xFF0284C7)),
                                      ],
                                    ),
                                    const SizedBox(height: 3),
                                    Text(
                                      '${store['address'] ?? 'Cicalengka'} • ${store['delivery_time'] ?? '15-25 min'}',
                                      style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    const SizedBox(height: 6),
                                    Row(
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFFFFBEB),
                                            borderRadius: BorderRadius.circular(8),
                                            border: Border.all(color: const Color(0xFFFDE68A)),
                                          ),
                                          child: Row(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              const Icon(Icons.star_rounded, size: 13, color: Colors.amber),
                                              const SizedBox(width: 2),
                                              Text(
                                                '${store['rating'] ?? '4.8'}',
                                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 10.5, color: Color(0xFF0F172A)),
                                              ),
                                            ],
                                          ),
                                        ),
                                        const SizedBox(width: 6),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFFEF2F2),
                                            borderRadius: BorderRadius.circular(8),
                                            border: Border.all(color: const Color(0xFFFECACA)),
                                          ),
                                          child: const Row(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              Icon(Icons.two_wheeler_rounded, size: 11, color: Color(0xFFEF4444)),
                                              SizedBox(width: 3),
                                              Text(
                                                'Diskon Ongkir',
                                                style: TextStyle(color: Color(0xFFEF4444), fontSize: 9.5, fontWeight: FontWeight.bold),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 4),
                              const Icon(Icons.chevron_right_rounded, color: Color(0xFF94A3B8), size: 20),
                            ],
                          ),
                        ),
                      ),
                    );
                  },
                ),
              ],

              // Products Result Section
              if (_products.isNotEmpty) ...[
                const Padding(
                  padding: EdgeInsets.fromLTRB(16, 20, 16, 10),
                  child: Text(
                    'Menu Makanan & Produk',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppTheme.inkBlack),
                  ),
                ),
                GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    childAspectRatio: 0.70,
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                  ),
                  itemCount: _products.length,
                  itemBuilder: (context, index) {
                    final prod = _products[index] is Map<String, dynamic>
                        ? _products[index] as Map<String, dynamic>
                        : Map<String, dynamic>.from(_products[index] as Map);

                    final price = double.tryParse(prod['price']?.toString() ?? '0') ?? 0.0;
                    final finalPrice = double.tryParse(prod['final_price']?.toString() ?? price.toString()) ?? price;
                    final imgUrl = _getFoodImage(prod);
                    final productId = int.tryParse(prod['id']?.toString() ?? '0') ?? 0;

                    return GestureDetector(
                      onTap: () => ProductDetailModal.show(context, prod),
                      child: Container(
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
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            ClipRRect(
                              borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                              child: CachedNetworkImage(
                                imageUrl: imgUrl,
                                height: 115,
                                width: double.infinity,
                                fit: BoxFit.cover,
                                errorWidget: (_, __, ___) => Container(
                                  height: 115,
                                  color: const Color(0xFFF1F5F9),
                                  child: const Center(
                                    child: Icon(Icons.fastfood_rounded, size: 36, color: AppTheme.inkBlack),
                                  ),
                                ),
                              ),
                            ),
                            Padding(
                              padding: const EdgeInsets.all(10.0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    prod['store_name'] ?? 'Mitra CicalengkaGO',
                                    style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  const SizedBox(height: 3),
                                  Text(
                                    prod['name'] ?? 'Menu Kuliner',
                                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    CurrencyFormatter.formatRupiah(finalPrice),
                                    style: const TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w800,
                                      color: AppTheme.inkBlack,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  SizedBox(
                                    width: double.infinity,
                                    height: 32,
                                    child: ElevatedButton(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: AppTheme.inkBlack,
                                        foregroundColor: Colors.white,
                                        elevation: 0,
                                        padding: EdgeInsets.zero,
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(10),
                                        ),
                                      ),
                                      onPressed: () async {
                                        final ok = await context.read<CustomerController>().addToCart(productId, 1);
                                        if (ok && context.mounted) {
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            SnackBar(
                                              content: Text('${prod['name']} ditambahkan ke keranjang'),
                                              duration: const Duration(seconds: 1),
                                              backgroundColor: AppTheme.inkBlack,
                                              behavior: SnackBarBehavior.floating,
                                            ),
                                          );
                                        }
                                      },
                                      child: const Text('+ Tambah', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
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
