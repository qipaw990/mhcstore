import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/network/api_service.dart';
import '../../../core/widgets/app_alert.dart';
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
  List<dynamic> _popularStores = [];
  List<dynamic> _recommendProducts = [];
  Timer? _debounceTimer;

  String _activeTab = 'all'; // 'all', 'products', 'stores'
  String _activeFilter = 'Semua'; // 'Semua', 'Rating 4.5+', 'Diskon', 'Buka'

  final List<Map<String, String>> _trendingChips = const [
    {'icon': '🌶️', 'label': 'Seblak'},
    {'icon': '🍗', 'label': 'Ayam Geprek'},
    {'icon': '🍜', 'label': 'Bakso & Mie'},
    {'icon': '☕', 'label': 'Kopi Susu'},
    {'icon': '🍚', 'label': 'Nasi Goreng'},
    {'icon': '🧋', 'label': 'Boba Drink'},
    {'icon': '🥞', 'label': 'Martabak'},
    {'icon': '🍢', 'label': 'Sate & Grill'},
  ];

  final List<String> _filterTags = const [
    'Semua',
    'Rating 4.5+',
    'Ada Promo',
    'Buka Sekarang',
  ];

  @override
  void initState() {
    super.initState();
    _searchCtrl.text = widget.initialQuery;
    _loadDiscoveryData();
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

  Future<void> _loadDiscoveryData() async {
    try {
      final res = await ApiService.get(ApiConstants.search);
      if (res['success'] == true && res['data'] != null) {
        final data = res['data'] as Map<String, dynamic>;
        if (mounted) {
          setState(() {
            _popularStores = data['popular_stores'] as List<dynamic>? ?? [];
            _recommendProducts = data['recommend_products'] as List<dynamic>? ?? [];
          });
        }
      }
    } catch (_) {}
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
    _debounceTimer = Timer(const Duration(milliseconds: 300), () {
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
      final res = await ApiService.get('${ApiConstants.search}?q=${Uri.encodeComponent(cleanQuery)}');
      if (res['success'] == true && res['data'] != null) {
        final data = res['data'] as Map<String, dynamic>;
        _products = data['products'] as List<dynamic>? ?? [];
        _stores = data['stores'] as List<dynamic>? ?? [];
      } else {
        // Fallback search
        final results = await Future.wait([
          ApiService.get('${ApiConstants.products}?q=${Uri.encodeComponent(cleanQuery)}'),
          ApiService.get('${ApiConstants.stores}?q=${Uri.encodeComponent(cleanQuery)}'),
        ]);
        if (results[0]['success'] == true && results[0]['data'] is List) {
          _products = results[0]['data'] as List<dynamic>;
        }
        if (results[1]['success'] == true && results[1]['data'] is List) {
          _stores = results[1]['data'] as List<dynamic>;
        }
      }
    } catch (_) {}

    if (mounted) {
      setState(() {
        _isSearching = false;
      });
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

  List<dynamic> _getFilteredProducts() {
    return _products.where((p) {
      final prod = p is Map<String, dynamic> ? p : Map<String, dynamic>.from(p as Map);
      final price = double.tryParse(prod['price']?.toString() ?? '0') ?? 0.0;
      final finalPrice = double.tryParse(prod['final_price']?.toString() ?? price.toString()) ?? price;

      if (_activeFilter == 'Ada Promo') {
        if (price <= finalPrice && (prod['discount'] == null || prod['discount'] == 0)) {
          return false;
        }
      }
      return true;
    }).toList();
  }

  List<dynamic> _getFilteredStores() {
    return _stores.where((s) {
      final store = s is Map<String, dynamic> ? s : Map<String, dynamic>.from(s as Map);
      final isOpen = store['is_open'] == 1 || store['is_open'] == true || store['is_open'] == '1';
      final rating = double.tryParse(store['rating']?.toString() ?? '4.8') ?? 4.8;

      if (_activeFilter == 'Buka Sekarang' && !isOpen) return false;
      if (_activeFilter == 'Rating 4.5+' && rating < 4.5) return false;
      return true;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final hasQuery = _searchCtrl.text.trim().isNotEmpty;
    final displayProducts = _getFilteredProducts();
    final displayStores = _getFilteredStores();

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0.5,
        scrolledUnderElevation: 0,
        titleSpacing: 0,
        leading: Navigator.canPop(context)
            ? IconButton(
                icon: const Icon(Icons.arrow_back_rounded, color: Color(0xFF0F172A)),
                onPressed: () => Navigator.pop(context),
              )
            : null,
        title: Padding(
          padding: EdgeInsets.only(
            left: Navigator.canPop(context) ? 0 : 16.0,
            right: 16.0,
          ),
          child: Container(
            height: 42,
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: TextField(
              controller: _searchCtrl,
              autofocus: widget.initialQuery.isEmpty,
              style: const TextStyle(fontSize: 13.5, color: Color(0xFF0F172A)),
              onChanged: _onSearchChanged,
              onSubmitted: _performSearch,
              decoration: InputDecoration(
                hintText: 'Cari seblak, ayam geprek, boba, resto...',
                hintStyle: const TextStyle(fontSize: 12.5, color: Color(0xFF94A3B8)),
                prefixIcon: const Icon(Icons.search_rounded, size: 20, color: Color(0xFFEF4444)),
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
                contentPadding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                border: InputBorder.none,
              ),
            ),
          ),
        ),
        bottom: hasQuery
            ? PreferredSize(
                preferredSize: const Size.fromHeight(84),
                child: Column(
                  children: [
                    // Tab Selector: Semua, Produk, Resto (Scrollable to prevent overflow)
                    SizedBox(
                      height: 38,
                      child: ListView(
                        scrollDirection: Axis.horizontal,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 2),
                        children: [
                          _buildTabButton(title: 'Semua', value: 'all', count: _products.length + _stores.length),
                          const SizedBox(width: 8),
                          _buildTabButton(title: 'Menu & Produk', value: 'products', count: _products.length),
                          const SizedBox(width: 8),
                          _buildTabButton(title: 'Resto & Toko', value: 'stores', count: _stores.length),
                        ],
                      ),
                    ),

                    // Filter tags
                    SizedBox(
                      height: 38,
                      child: ListView.builder(
                        scrollDirection: Axis.horizontal,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                        itemCount: _filterTags.length,
                        itemBuilder: (context, idx) {
                          final tag = _filterTags[idx];
                          final isSelected = _activeFilter == tag;
                          return Padding(
                            padding: const EdgeInsets.only(right: 6),
                            child: FilterChip(
                              label: Text(tag),
                              selected: isSelected,
                              onSelected: (val) {
                                setState(() => _activeFilter = tag);
                              },
                              selectedColor: const Color(0xFFEF4444),
                              backgroundColor: Colors.white,
                              labelStyle: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: isSelected ? Colors.white : const Color(0xFF475569),
                              ),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                                side: BorderSide(
                                  color: isSelected ? const Color(0xFFEF4444) : const Color(0xFFE2E8F0),
                                ),
                              ),
                              padding: const EdgeInsets.symmetric(horizontal: 4),
                              showCheckmark: false,
                            ),
                          );
                        },
                      ),
                    ),
                  ],
                ),
              )
            : null,
      ),
      body: _isSearching
          ? const Center(
              child: Padding(
                padding: EdgeInsets.all(32.0),
                child: CircularProgressIndicator(color: Color(0xFFEF4444)),
              ),
            )
          : hasQuery
              ? _buildSearchResults(displayProducts, displayStores)
              : _buildDiscoveryView(),
    );
  }

  Widget _buildTabButton({required String title, required String value, required int count}) {
    final isSelected = _activeTab == value;
    return GestureDetector(
      onTap: () => setState(() => _activeTab = value),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFFFEF2F2) : const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? const Color(0xFFEF4444) : Colors.transparent,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              title,
              style: TextStyle(
                fontSize: 11.5,
                fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                color: isSelected ? const Color(0xFFEF4444) : const Color(0xFF475569),
              ),
            ),
            if (count > 0) ...[
              const SizedBox(width: 4),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                decoration: BoxDecoration(
                  color: isSelected ? const Color(0xFFEF4444) : const Color(0xFF94A3B8),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '$count',
                  style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.white),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  // ── 1. Discovery View (When Query is Empty) ──
  Widget _buildDiscoveryView() {
    final customerCtrl = context.watch<CustomerController>();
    final recoProducts = _recommendProducts.isNotEmpty ? _recommendProducts : customerCtrl.recommendedProducts;
    final popStores = _popularStores.isNotEmpty ? _popularStores : customerCtrl.topRatedStores;

    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Trending Searches Bar
          Container(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
            color: Colors.white,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: const [
                    Icon(Icons.local_fire_department_rounded, size: 18, color: Color(0xFFEF4444)),
                    SizedBox(width: 6),
                    Text(
                      'Pencarian Populer di Cicalengka',
                      style: TextStyle(
                        fontSize: 13.5,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _trendingChips.map((chip) {
                    return InkWell(
                      onTap: () {
                        _searchCtrl.text = chip['label']!;
                        _performSearch(chip['label']!);
                      },
                      borderRadius: BorderRadius.circular(20),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(chip['icon']!, style: const TextStyle(fontSize: 12)),
                            const SizedBox(width: 4),
                            Text(
                              chip['label']!,
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                            ),
                          ],
                        ),
                      ),
                    );
                  }).toList(),
                ),
              ],
            ),
          ),

          const SizedBox(height: 12),

          // Recommended Products Grid
          if (recoProducts.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              child: Row(
                children: const [
                  Icon(Icons.auto_awesome_rounded, size: 16, color: Colors.amber),
                  SizedBox(width: 6),
                  Text(
                    'Rekomendasi Menu Favorit',
                    style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
            ),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                childAspectRatio: 0.68,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
              ),
              itemCount: recoProducts.length > 8 ? 8 : recoProducts.length,
              itemBuilder: (context, index) {
                final prod = recoProducts[index] is Map<String, dynamic>
                    ? recoProducts[index] as Map<String, dynamic>
                    : Map<String, dynamic>.from(recoProducts[index] as Map);

                final price = double.tryParse(prod['price']?.toString() ?? '0') ?? 0.0;
                final finalPrice = double.tryParse(prod['final_price']?.toString() ?? price.toString()) ?? price;
                final imgUrl = _getFoodImage(prod);
                final productId = int.tryParse(prod['id']?.toString() ?? '0') ?? 0;
                final bool hasDiscount = price > finalPrice;

                return _buildProductCard(context, prod, productId, imgUrl, price, finalPrice, hasDiscount);
              },
            ),
          ],

          const SizedBox(height: 14),

          // Popular Stores Section
          if (popStores.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              child: Row(
                children: const [
                  Icon(Icons.storefront_rounded, size: 16, color: Color(0xFFEF4444)),
                  SizedBox(width: 6),
                  Text(
                    'Resto Pilihan Populer',
                    style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 6),
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: popStores.length > 4 ? 4 : popStores.length,
              itemBuilder: (context, index) {
                final store = popStores[index] is Map<String, dynamic>
                    ? popStores[index] as Map<String, dynamic>
                    : Map<String, dynamic>.from(popStores[index] as Map);
                return _buildStoreCard(context, store);
              },
            ),
          ],

          const SizedBox(height: 24),
        ],
      ),
    );
  }

  // ── 2. Search Results View ──
  Widget _buildSearchResults(List<dynamic> products, List<dynamic> stores) {
    final showStores = (_activeTab == 'all' || _activeTab == 'stores') && stores.isNotEmpty;
    final showProducts = (_activeTab == 'all' || _activeTab == 'products') && products.isNotEmpty;

    if (products.isEmpty && stores.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 80,
                height: 80,
                decoration: const BoxDecoration(
                  color: Color(0xFFFEF2F2),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.search_off_rounded, size: 40, color: Color(0xFFEF4444)),
              ),
              const SizedBox(height: 16),
              const Text(
                'Tidak Ditemukan',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
              ),
              const SizedBox(height: 6),
              Text(
                'Menu atau resto "${_searchCtrl.text}" belum tersedia di Cicalengka. Coba kata kunci lain seperti "Ayam", "Seblak", atau "Kopi".',
                style: const TextStyle(fontSize: 12, color: Color(0xFF64748B), height: 1.4),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      );
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Stores Results
          if (showStores) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Resto & Toko (${stores.length})',
                    style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
            ),
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              itemCount: stores.length,
              itemBuilder: (context, index) {
                final store = stores[index] is Map<String, dynamic>
                    ? stores[index] as Map<String, dynamic>
                    : Map<String, dynamic>.from(stores[index] as Map);
                return _buildStoreCard(context, store);
              },
            ),
            const SizedBox(height: 14),
          ],

          // Products Results
          if (showProducts) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
              child: Text(
                'Menu Makanan & Produk (${products.length})',
                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
              ),
            ),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                childAspectRatio: 0.68,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
              ),
              itemCount: products.length,
              itemBuilder: (context, index) {
                final prod = products[index] is Map<String, dynamic>
                    ? products[index] as Map<String, dynamic>
                    : Map<String, dynamic>.from(products[index] as Map);

                final price = double.tryParse(prod['price']?.toString() ?? '0') ?? 0.0;
                final finalPrice = double.tryParse(prod['final_price']?.toString() ?? price.toString()) ?? price;
                final imgUrl = _getFoodImage(prod);
                final productId = int.tryParse(prod['id']?.toString() ?? '0') ?? 0;
                final bool hasDiscount = price > finalPrice;

                return _buildProductCard(context, prod, productId, imgUrl, price, finalPrice, hasDiscount);
              },
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildProductCard(
    BuildContext context,
    Map<String, dynamic> prod,
    int productId,
    String imgUrl,
    double price,
    double finalPrice,
    bool hasDiscount,
  ) {
    final customerCtrl = context.read<CustomerController>();
    final rawStoreOpen = prod['store_is_open'] ?? prod['is_store_open'] ?? prod['is_currently_open'] ?? prod['is_open'];
    final bool isStoreClosed = rawStoreOpen == 0 || rawStoreOpen == false || rawStoreOpen == '0' || rawStoreOpen == 'false';
    final bool storeIsOpen = (rawStoreOpen == 1 || rawStoreOpen == true || rawStoreOpen == '1' || rawStoreOpen == 'true') && !isStoreClosed;

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
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => ProductDetailModal.show(context, prod),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Stack(
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
                        child: Icon(Icons.fastfood_rounded, size: 36, color: Color(0xFF94A3B8)),
                      ),
                    ),
                  ),
                ),
                Positioned(
                  top: 8,
                  right: 8,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.7),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.star_rounded, size: 12, color: Colors.amber),
                        const SizedBox(width: 2),
                        Text(
                          '${prod['rating'] ?? '4.8'}',
                          style: const TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ),
                ),
                if (hasDiscount)
                  Positioned(
                    top: 8,
                    left: 8,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEF4444),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text(
                        'PROMO',
                        style: TextStyle(color: Colors.white, fontSize: 8.5, fontWeight: FontWeight.w900),
                      ),
                    ),
                  ),
                // Badge TUTUP jika toko tutup
                if (!storeIsOpen)
                  Positioned(
                    bottom: 0,
                    left: 0,
                    right: 0,
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.55),
                        borderRadius: const BorderRadius.only(
                          bottomLeft: Radius.circular(0),
                          bottomRight: Radius.circular(0),
                        ),
                      ),
                      child: const Center(
                        child: Text(
                          '🔴 TOKO TUTUP',
                          style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.w900),
                        ),
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
                    prod['store_name'] ?? 'Mitra CicalengkaGO',
                    style: const TextStyle(fontSize: 10, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    prod['name'] ?? 'Menu Kuliner',
                    style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Text(
                        CurrencyFormatter.formatRupiah(finalPrice),
                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFFEF4444)),
                      ),
                      if (hasDiscount) ...[
                        const SizedBox(width: 4),
                        Flexible(
                          child: Text(
                            CurrencyFormatter.formatRupiah(price),
                            style: const TextStyle(fontSize: 9.5, color: Color(0xFF94A3B8), decoration: TextDecoration.lineThrough),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 8),
                  SizedBox(
                    width: double.infinity,
                    height: 30,
                    child: storeIsOpen
                        ? ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFFEF4444),
                              foregroundColor: Colors.white,
                              elevation: 1,
                              padding: EdgeInsets.zero,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                            ),
                            onPressed: () {
                              ProductDetailModal.show(context, prod);
                            },
                            icon: const Icon(Icons.add_rounded, size: 16),
                            label: const Text('Pilih', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                          )
                        : Container(
                            decoration: BoxDecoration(
                              color: const Color(0xFFE2E8F0),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Center(
                              child: Text(
                                'Tutup',
                                style: TextStyle(color: Color(0xFF64748B), fontSize: 11, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ── Cool Compact Store Card ──
  Widget _buildStoreCard(BuildContext context, Map<String, dynamic> store) {
    final coverUrl = ApiConstants.formatImageUrl(
      store['cover_photo']?.toString() ?? store['logo']?.toString(),
    );
    final isOpen = store['is_open'] == 1 || store['is_open'] == true || store['is_open'] == '1';
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
              // Compact Squircle Store Thumbnail
              Stack(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(14),
                    child: CachedNetworkImage(
                      imageUrl: coverUrl,
                      height: 76,
                      width: 76,
                      fit: BoxFit.cover,
                      errorWidget: (context, url, error) => Container(
                        height: 76,
                        width: 76,
                        color: const Color(0xFFF1F5F9),
                        child: const Icon(Icons.storefront_rounded, size: 32, color: Color(0xFF94A3B8)),
                      ),
                    ),
                  ),
                  Positioned(
                    top: 4,
                    left: 4,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                      decoration: BoxDecoration(
                        color: isOpen ? const Color(0xFF16A34A) : const Color(0xFF64748B),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        isOpen ? 'BUKA' : 'TUTUP',
                        style: const TextStyle(color: Colors.white, fontSize: 7.5, fontWeight: FontWeight.w900),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(width: 12),

              // Store Info Details
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
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
  }
}
