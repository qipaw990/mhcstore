import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
import '../../auth/controllers/auth_controller.dart';
import '../../auth/screens/login_screen.dart';
import '../controllers/customer_controller.dart';
import 'store_detail_screen.dart';
import 'cart_screen.dart';
import 'customer_wallet_screen.dart';
import 'customer_search_screen.dart';
import 'order_tracking_screen.dart';

class CustomerHomeScreen extends StatefulWidget {
  const CustomerHomeScreen({super.key});

  @override
  State<CustomerHomeScreen> createState() => _CustomerHomeScreenState();
}

class _CustomerHomeScreenState extends State<CustomerHomeScreen> {
  int _currentIndex = 0;
  final PageController _bannerPageCtrl = PageController();
  int _currentBannerPage = 0;
  Timer? _bannerTimer;

  final List<Map<String, dynamic>> _categoriesGrid = const [
    {'name': 'Ayam & Geprek', 'icon': Icons.local_fire_department, 'color': Color(0xFFEA580C), 'query': 'Ayam'},
    {'name': 'Seblak Pedas', 'icon': Icons.favorite, 'color': Color(0xFFDC2626), 'query': 'Seblak'},
    {'name': 'Bakso & Mie', 'icon': Icons.soup_kitchen, 'color': Color(0xFF0284C7), 'query': 'Bakso'},
    {'name': 'Nasi Liwet', 'icon': Icons.rice_bowl, 'color': Color(0xFF16A34A), 'query': 'Nasi'},
    {'name': 'Sate Maranggi', 'icon': Icons.restaurant_menu, 'color': Color(0xFF9333EA), 'query': 'Sate'},
    {'name': 'Martabak', 'icon': Icons.pie_chart, 'color': Color(0xFFD97706), 'query': 'Martabak'},
    {'name': 'Kopi & Cafe', 'icon': Icons.local_cafe, 'color': Color(0xFF78350F), 'query': 'Kopi'},
    {'name': 'Sembako Mart', 'icon': Icons.shopping_basket, 'color': Color(0xFF059669), 'query': 'Sembako'},
  ];

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
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchHomeData();
    });

    _bannerTimer = Timer.periodic(const Duration(seconds: 4), (timer) {
      final banners = context.read<CustomerController>().banners;
      if (banners.isNotEmpty && _bannerPageCtrl.hasClients) {
        _currentBannerPage = (_currentBannerPage + 1) % banners.length;
        _bannerPageCtrl.animateToPage(
          _currentBannerPage,
          duration: const Duration(milliseconds: 350),
          curve: Curves.easeInOut,
        );
      }
    });
  }

  @override
  void dispose() {
    _bannerTimer?.cancel();
    _bannerPageCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final customerCtrl = context.watch<CustomerController>();
    final authCtrl = context.watch<AuthController>();
    final user = authCtrl.user;

    final walletData = customerCtrl.wallet?['wallet'];
    final double balance = (walletData?['balance'] != null)
        ? double.parse(walletData['balance'].toString())
        : 0.0;

    final cartItemsCount = customerCtrl.cart?['items'] != null
        ? (customerCtrl.cart!['items'] as List).length
        : 0;

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 16,
        elevation: 0,
        title: Row(
          children: [
            const CicalengkaGoLogo(size: 34, borderRadius: 10, showShadow: false),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Halo, ${user?['name'] ?? 'Pelanggan'} 👋',
                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                    overflow: TextOverflow.ellipsis,
                  ),
                  const Row(
                    children: [
                      Icon(Icons.location_on, size: 12, color: AppTheme.primaryRed),
                      SizedBox(width: 2),
                      Text(
                        'Cicalengka, Kab. Bandung',
                        style: TextStyle(fontSize: 11, color: Colors.grey),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          Stack(
            children: [
              IconButton(
                icon: const Icon(Icons.shopping_bag_outlined),
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const CartScreen()),
                  );
                },
              ),
              if (cartItemsCount > 0)
                Positioned(
                  right: 6,
                  top: 6,
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    decoration: const BoxDecoration(
                      color: AppTheme.primaryRed,
                      shape: BoxShape.circle,
                    ),
                    constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                    child: Text(
                      '$cartItemsCount',
                      style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ),
            ],
          ),
          if (authCtrl.isLoggedIn)
            IconButton(
              icon: const Icon(Icons.logout),
              onPressed: () => authCtrl.logout(),
              tooltip: 'Keluar',
            )
          else
            Padding(
              padding: const EdgeInsets.only(right: 12.0),
              child: Center(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primaryRed,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const LoginScreen()),
                    );
                  },
                  icon: const Icon(Icons.login, size: 14),
                  label: const Text('Masuk', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                ),
              ),
            ),
        ],
      ),
      body: _currentIndex == 0
          ? RefreshIndicator(
              onRefresh: () => customerCtrl.fetchHomeData(),
              child: SingleChildScrollView(
                padding: const EdgeInsets.only(bottom: 24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // 1. CicalengkaPay Super Card
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      child: GestureDetector(
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(builder: (_) => const CustomerWalletScreen()),
                          );
                        },
                        child: Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFFEE2737), Color(0xFF991B1B)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(20),
                            boxShadow: [
                              BoxShadow(
                                color: AppTheme.primaryRed.withOpacity(0.3),
                                blurRadius: 15,
                                offset: const Offset(0, 6),
                              ),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(6),
                                        decoration: BoxDecoration(
                                          color: Colors.white.withOpacity(0.2),
                                          shape: BoxShape.circle,
                                        ),
                                        child: const Icon(Icons.account_balance_wallet, color: Colors.white, size: 16),
                                      ),
                                      const SizedBox(width: 8),
                                      const Text(
                                        'Cicalengka',
                                        style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                                      ),
                                      const Text(
                                        'Pay',
                                        style: TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontSize: 13),
                                      ),
                                    ],
                                  ),
                                  const Icon(Icons.chevron_right, color: Colors.white70, size: 18),
                                ],
                              ),
                              const SizedBox(height: 10),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        CurrencyFormatter.formatRupiah(balance),
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontSize: 22,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      const Row(
                                        children: [
                                          Icon(Icons.stars, color: Colors.amberAccent, size: 12),
                                          SizedBox(width: 4),
                                          Text(
                                            'Gratis Ongkir & Promo • Riwayat',
                                            style: TextStyle(color: Colors.white70, fontSize: 10, fontWeight: FontWeight.w500),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                  Row(
                                    children: [
                                      _buildGopayActionButton(
                                        icon: Icons.north_east,
                                        label: 'Bayar',
                                        onTap: () {
                                          Navigator.push(
                                            context,
                                            MaterialPageRoute(builder: (_) => const CustomerWalletScreen()),
                                          );
                                        },
                                      ),
                                      const SizedBox(width: 8),
                                      _buildGopayActionButton(
                                        icon: Icons.add,
                                        label: 'Top Up',
                                        onTap: () {
                                          Navigator.push(
                                            context,
                                            MaterialPageRoute(builder: (_) => const CustomerWalletScreen()),
                                          );
                                        },
                                      ),
                                      const SizedBox(width: 8),
                                      _buildGopayActionButton(
                                        icon: Icons.explore_outlined,
                                        label: 'Eksplor',
                                        onTap: () {
                                          Navigator.push(
                                            context,
                                            MaterialPageRoute(builder: (_) => const CustomerWalletScreen()),
                                          );
                                        },
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),

                    // 2. Search & Trending Chips
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          GestureDetector(
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(builder: (_) => const CustomerSearchScreen()),
                              );
                            },
                            child: Container(
                              height: 40,
                              padding: const EdgeInsets.symmetric(horizontal: 14),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(25),
                                border: Border.all(color: const Color(0xFFCBD5E1)),
                              ),
                              child: const Row(
                                children: [
                                  Icon(Icons.search, size: 18, color: Colors.grey),
                                  SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      'Cari seblak, bento cake, nasi goreng, martabak...',
                                      style: TextStyle(fontSize: 12, color: Colors.grey),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 8),
                          SingleChildScrollView(
                            scrollDirection: Axis.horizontal,
                            child: Row(
                              children: [
                                const Text(
                                  '🔥 TRENDING: ',
                                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.redAccent),
                                ),
                                ..._trendingChips.map((chip) {
                                  return Padding(
                                    padding: const EdgeInsets.only(right: 6.0),
                                    child: InkWell(
                                      onTap: () {
                                        Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                            builder: (_) => CustomerSearchScreen(initialQuery: chip['query']!),
                                          ),
                                        );
                                      },
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFF8FAFC),
                                          borderRadius: BorderRadius.circular(20),
                                          border: Border.all(color: Colors.grey.shade300),
                                        ),
                                        child: Text(
                                          '${chip['icon']} ${chip['label']}',
                                          style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
                                        ),
                                      ),
                                    ),
                                  );
                                }),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),

                    // 3. Kategori Pilihan Grid (Gojek Services Grid)
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Row(
                                children: [
                                  Icon(Icons.grid_view_rounded, size: 16, color: AppTheme.primaryRed),
                                  SizedBox(width: 6),
                                  Text(
                                    'Kategori Pilihan',
                                    style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                              TextButton(
                                style: TextButton.styleFrom(padding: EdgeInsets.zero, minimumSize: Size.zero),
                                onPressed: () {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(builder: (_) => const CustomerSearchScreen()),
                                  );
                                },
                                child: const Text('Lihat Semua', style: TextStyle(fontSize: 11, color: AppTheme.primaryRed)),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          GridView.builder(
                            crossAxisCount: 4,
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            mainAxisSpacing: 12,
                            crossAxisSpacing: 10,
                            itemCount: _categoriesGrid.length,
                            itemBuilder: (context, index) {
                              final cat = _categoriesGrid[index];
                              return GestureDetector(
                                onTap: () {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (_) => CustomerSearchScreen(initialQuery: cat['query'] as String),
                                    ),
                                  );
                                },
                                child: Column(
                                  children: [
                                    Container(
                                      width: 48,
                                      height: 48,
                                      decoration: BoxDecoration(
                                        color: (cat['color'] as Color).withOpacity(0.12),
                                        borderRadius: BorderRadius.circular(16),
                                      ),
                                      child: Icon(cat['icon'] as IconData, color: cat['color'] as Color, size: 24),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      cat['name'] as String,
                                      style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600),
                                      textAlign: TextAlign.center,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                    ),

                    // 4. Promo Banners Carousel
                    if (customerCtrl.banners.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      SizedBox(
                        height: 140,
                        child: PageView.builder(
                          controller: _bannerPageCtrl,
                          onPageChanged: (idx) {
                            setState(() {
                              _currentBannerPage = idx;
                            });
                          },
                          itemCount: customerCtrl.banners.length,
                          itemBuilder: (context, index) {
                            final banner = customerCtrl.banners[index];
                            final imgUrl = banner['image'] != null && banner['image'].toString().startsWith('http')
                                ? banner['image']
                                : '${ApiConstants.imageBaseUrl}/${banner['image'] ?? ''}';

                            return Container(
                              margin: const EdgeInsets.symmetric(horizontal: 16),
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(16),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.08),
                                    blurRadius: 8,
                                    offset: const Offset(0, 3),
                                  ),
                                ],
                              ),
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(16),
                                child: CachedNetworkImage(
                                  imageUrl: imgUrl,
                                  fit: BoxFit.cover,
                                  width: double.infinity,
                                  errorWidget: (_, __, ___) => Container(color: Colors.grey[200]),
                                ),
                              ),
                            );
                          },
                        ),
                      ),
                      const SizedBox(height: 6),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: List.generate(customerCtrl.banners.length, (idx) {
                          return AnimatedContainer(
                            duration: const Duration(milliseconds: 200),
                            margin: const EdgeInsets.symmetric(horizontal: 3),
                            width: _currentBannerPage == idx ? 16 : 6,
                            height: 6,
                            decoration: BoxDecoration(
                              color: _currentBannerPage == idx ? AppTheme.primaryRed : Colors.grey.shade300,
                              borderRadius: BorderRadius.circular(3),
                            ),
                          );
                        }),
                      ),
                    ],

                    // 5. Flash Sale / Promo Diskon Hari Ini
                    if (customerCtrl.discountedProducts.isNotEmpty) ...[
                      const SizedBox(height: 18),
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Icon(Icons.bolt, size: 18, color: Colors.amber),
                                    SizedBox(width: 4),
                                    Text(
                                      'Flash Sale & Promo Diskon',
                                      style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                                    ),
                                  ],
                                ),
                                Text(
                                  'Hemat sampai 50% untuk menu pilihan hari ini',
                                  style: TextStyle(fontSize: 10, color: Colors.grey),
                                ),
                              ],
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.red.shade50,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: const Text(
                                'PROMO SPESIAL',
                                style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 10),
                      SizedBox(
                        height: 200,
                        child: ListView.builder(
                          scrollDirection: Axis.horizontal,
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          itemCount: customerCtrl.discountedProducts.length,
                          itemBuilder: (context, index) {
                            final prod = customerCtrl.discountedProducts[index];
                            final double price = double.tryParse(prod['price']?.toString() ?? '0') ?? 0;
                            final double finalPrice = double.tryParse(prod['final_price']?.toString() ?? price.toString()) ?? price;
                            final imgUrl = prod['image'] != null && prod['image'].toString().startsWith('http')
                                ? prod['image']
                                : '${ApiConstants.imageBaseUrl}/${prod['image'] ?? ''}';

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
                                width: 135,
                                margin: const EdgeInsets.only(right: 10),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(14),
                                  border: Border.all(color: Colors.grey.shade200),
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Stack(
                                      children: [
                                        ClipRRect(
                                          borderRadius: const BorderRadius.vertical(top: Radius.circular(14)),
                                          child: CachedNetworkImage(
                                            imageUrl: imgUrl,
                                            height: 95,
                                            width: double.infinity,
                                            fit: BoxFit.cover,
                                            errorWidget: (_, __, ___) => Container(color: Colors.grey[200]),
                                          ),
                                        ),
                                        Positioned(
                                          top: 0,
                                          left: 0,
                                          child: Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                            decoration: const BoxDecoration(
                                              color: AppTheme.primaryRed,
                                              borderRadius: BorderRadius.only(
                                                topLeft: Radius.circular(14),
                                                bottomRight: Radius.circular(8),
                                              ),
                                            ),
                                            child: Text(
                                              '-${prod['discount_type'] == 'percent' ? '${prod['discount']}%' : CurrencyFormatter.formatRupiah(double.tryParse(prod['discount'].toString()) ?? 0)}',
                                              style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                    Padding(
                                      padding: const EdgeInsets.all(8.0),
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            prod['store_name'] ?? 'Resto Cicalengka',
                                            style: const TextStyle(fontSize: 9, color: Colors.grey),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                          Text(
                                            prod['name'] ?? '',
                                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                          const SizedBox(height: 2),
                                          Text(
                                            CurrencyFormatter.formatRupiah(finalPrice),
                                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                                          ),
                                          if (price > finalPrice)
                                            Text(
                                              CurrencyFormatter.formatRupiah(price),
                                              style: const TextStyle(fontSize: 9, color: Colors.grey, decoration: TextDecoration.lineThrough),
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

                    // 6. Resto & Toko Paling Hit di Cicalengka
                    const SizedBox(height: 20),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Icon(Icons.storefront, size: 18, color: AppTheme.primaryRed),
                                  SizedBox(width: 6),
                                  Text(
                                    'Resto & Toko Paling Hit',
                                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                              Text(
                                'Buka sekarang dengan pengantaran cepat',
                                style: TextStyle(fontSize: 10, color: Colors.grey),
                              ),
                            ],
                          ),
                          TextButton(
                            onPressed: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(builder: (_) => const CustomerSearchScreen()),
                              );
                            },
                            child: const Text('Lihat Semua', style: TextStyle(fontSize: 11, color: AppTheme.primaryRed)),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 8),

                    if (customerCtrl.isLoading)
                      const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator()))
                    else if (customerCtrl.topRatedStores.isEmpty)
                      const Center(child: Padding(padding: EdgeInsets.all(20), child: Text('Belum ada resto tersedia')))
                    else
                      ListView.builder(
                        itemCount: customerCtrl.topRatedStores.length,
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        itemBuilder: (context, index) {
                          final store = customerCtrl.topRatedStores[index];
                          final coverUrl = store['cover_photo'] != null && store['cover_photo'].toString().startsWith('http')
                              ? store['cover_photo']
                              : '${ApiConstants.imageBaseUrl}/${store['cover_photo'] ?? store['logo'] ?? ''}';
                          final isOpen = store['is_open'] == 1 || store['is_open'] == true || store['is_open'] == '1';

                          return Card(
                            margin: const EdgeInsets.only(bottom: 14),
                            elevation: 2,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                            child: InkWell(
                              borderRadius: BorderRadius.circular(16),
                              onTap: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (_) => StoreDetailScreen(storeId: int.parse(store['id'].toString())),
                                  ),
                                );
                              },
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Stack(
                                    children: [
                                      ClipRRect(
                                        borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                                        child: CachedNetworkImage(
                                          imageUrl: coverUrl,
                                          height: 120,
                                          width: double.infinity,
                                          fit: BoxFit.cover,
                                          errorWidget: (_, __, ___) => Container(
                                            height: 120,
                                            color: Colors.grey[200],
                                            child: const Icon(Icons.store, size: 40, color: Colors.grey),
                                          ),
                                        ),
                                      ),
                                      Positioned(
                                        top: 10,
                                        left: 10,
                                        child: Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                          decoration: BoxDecoration(
                                            color: isOpen ? Colors.green : Colors.red,
                                            borderRadius: BorderRadius.circular(12),
                                          ),
                                          child: Row(
                                            children: [
                                              Icon(isOpen ? Icons.door_front_door : Icons.lock, size: 10, color: Colors.white),
                                              const SizedBox(width: 4),
                                              Text(
                                                isOpen ? 'Buka' : 'Tutup',
                                                style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  Padding(
                                    padding: const EdgeInsets.all(12.0),
                                    child: Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                store['name'] ?? 'Mitra Resto',
                                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                                maxLines: 1,
                                                overflow: TextOverflow.ellipsis,
                                              ),
                                              const SizedBox(height: 2),
                                              Text(
                                                '${store['address'] ?? 'Cicalengka'} • ${store['delivery_time'] ?? '15-25 min'}',
                                                style: const TextStyle(fontSize: 11, color: Colors.grey),
                                                maxLines: 1,
                                                overflow: TextOverflow.ellipsis,
                                              ),
                                            ],
                                          ),
                                        ),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                          decoration: BoxDecoration(
                                            color: Colors.amber.shade50,
                                            borderRadius: BorderRadius.circular(12),
                                            border: Border.all(color: Colors.amber),
                                          ),
                                          child: Row(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              const Icon(Icons.star, size: 12, color: Colors.amber),
                                              const SizedBox(width: 2),
                                              Text(
                                                '${store['rating'] ?? '4.8'}',
                                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11),
                                              ),
                                            ],
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

                    // 7. Eksplor Menu Kuliner Favorit
                    if (customerCtrl.recommendedProducts.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      const Padding(
                        padding: EdgeInsets.symmetric(horizontal: 16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Icon(Icons.auto_awesome, size: 18, color: Colors.amber),
                                SizedBox(width: 6),
                                Text(
                                  'Eksplor Menu Kuliner Favorit',
                                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                                ),
                              ],
                            ),
                            Text(
                              'Aneka pilihan makanan & cemilan segar siap diantar',
                              style: TextStyle(fontSize: 10, color: Colors.grey),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 10),
                      GridView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          childAspectRatio: 0.72,
                          crossAxisSpacing: 10,
                          mainAxisSpacing: 10,
                        ),
                        itemCount: customerCtrl.recommendedProducts.length,
                        itemBuilder: (context, index) {
                          final prod = customerCtrl.recommendedProducts[index];
                          final double price = double.tryParse(prod['price']?.toString() ?? '0') ?? 0;
                          final double finalPrice = double.tryParse(prod['final_price']?.toString() ?? price.toString()) ?? price;
                          final imgUrl = prod['image'] != null && prod['image'].toString().startsWith('http')
                              ? prod['image']
                              : '${ApiConstants.imageBaseUrl}/${prod['image'] ?? ''}';

                          return Container(
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: Colors.grey.shade200),
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
                                    errorWidget: (_, __, ___) => Container(color: Colors.grey[200]),
                                  ),
                                ),
                                Padding(
                                  padding: const EdgeInsets.all(8.0),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        prod['store_name'] ?? 'Mitra CicalengkaGO',
                                        style: const TextStyle(fontSize: 9, color: Colors.grey),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      Text(
                                        prod['name'] ?? '',
                                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        CurrencyFormatter.formatRupiah(finalPrice),
                                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppTheme.primaryRed),
                                      ),
                                      const SizedBox(height: 6),
                                      SizedBox(
                                        width: double.infinity,
                                        height: 26,
                                        child: ElevatedButton.icon(
                                          style: ElevatedButton.styleFrom(
                                            backgroundColor: AppTheme.primaryRed,
                                            foregroundColor: Colors.white,
                                            padding: EdgeInsets.zero,
                                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                          ),
                                          onPressed: () async {
                                            final ok = await customerCtrl.addToCart(int.parse(prod['id'].toString()), 1);
                                            if (ok && context.mounted) {
                                              ScaffoldMessenger.of(context).showSnackBar(
                                                SnackBar(
                                                  content: Text('${prod['name']} ditambahkan ke keranjang'),
                                                  duration: const Duration(seconds: 1),
                                                ),
                                              );
                                            }
                                          },
                                          icon: const Icon(Icons.add, size: 12),
                                          label: const Text('+ Tambah', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          );
                        },
                      ),
                    ],

                    // 8. Keunggulan Badges
                    const SizedBox(height: 24),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.grey.shade200),
                        ),
                        child: const Row(
                          mainAxisAlignment: MainAxisAlignment.spaceAround,
                          children: [
                            Column(
                              children: [
                                Icon(Icons.bolt, color: Colors.amber, size: 24),
                                SizedBox(height: 4),
                                Text('Pengantaran Cepat', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                                Text('15-25 Menit', style: TextStyle(fontSize: 9, color: Colors.grey)),
                              ],
                            ),
                            Column(
                              children: [
                                Icon(Icons.verified, color: Colors.green, size: 24),
                                SizedBox(height: 4),
                                Text('Resto Terverifikasi', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                                Text('100% Higienis', style: TextStyle(fontSize: 9, color: Colors.grey)),
                              ],
                            ),
                            Column(
                              children: [
                                Icon(Icons.shield, color: AppTheme.primaryRed, size: 24),
                                SizedBox(height: 4),
                                Text('CicalengkaPay', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                                Text('Aman & Instan', style: TextStyle(fontSize: 9, color: Colors.grey)),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            )
          : const CustomerOrdersListTab(),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        selectedItemColor: AppTheme.primaryRed,
        onTap: (idx) {
          setState(() {
            _currentIndex = idx;
          });
        },
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.home), label: 'Beranda'),
          BottomNavigationBarItem(icon: Icon(Icons.receipt_long), label: 'Pesanan Saya'),
        ],
      ),
    );
  }

  Widget _buildGopayActionButton({required IconData icon, required String label, required VoidCallback onTap}) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.25),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: Colors.white, size: 14),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }
}

class CustomerOrdersListTab extends StatefulWidget {
  const CustomerOrdersListTab({super.key});

  @override
  State<CustomerOrdersListTab> createState() => _CustomerOrdersListTabState();
}

class _CustomerOrdersListTabState extends State<CustomerOrdersListTab> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchOrders();
    });
  }

  @override
  Widget build(BuildContext context) {
    final customerCtrl = context.watch<CustomerController>();

    if (customerCtrl.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (customerCtrl.orders.isEmpty) {
      return const Center(child: Text('Belum ada riwayat pesanan'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: customerCtrl.orders.length,
      itemBuilder: (context, index) {
        final order = customerCtrl.orders[index];
        final total = double.tryParse(order['order_amount']?.toString() ?? '0') ?? 0.0;

        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          elevation: 1.5,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          child: ListTile(
            title: Text(
              'Pesanan #${order['order_code'] ?? order['id']}',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            subtitle: Text(
              'Status: ${order['order_status'] ?? 'pending'} • ${CurrencyFormatter.formatRupiah(total)}',
            ),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => OrderTrackingScreen(orderCode: order['order_code']?.toString() ?? ''),
                ),
              );
            },
          ),
        );
      },
    );
  }
}
