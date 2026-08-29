import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:http/http.dart' as http;
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/location_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
import '../../../core/widgets/location_picker_modal.dart';
import '../../../core/widgets/app_alert.dart';
import '../../auth/controllers/auth_controller.dart';
import '../../auth/screens/login_screen.dart';
import '../../auth/screens/register_screen.dart';
import '../controllers/customer_controller.dart';
import 'store_detail_screen.dart';
import 'cart_screen.dart';
import 'customer_wallet_screen.dart';
import 'customer_search_screen.dart';
import 'order_tracking_screen.dart';
import 'customer_orders_screen.dart';
import 'customer_profile_screen.dart';
import '../widgets/product_detail_modal.dart';

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

  double _userLat = -6.9835;
  double _userLng = 107.8335;
  String _currentAddress = 'Mendeteksi lokasi GPS...';
  bool _isLocating = true;

  final List<Map<String, dynamic>> _categoriesGrid = const [
    {'name': 'Ayam & Bebek', 'icon': Icons.restaurant_rounded, 'color': Color(0xFF000000), 'bgColor': Color(0xFFF3F3F3), 'query': 'Ayam'},
    {'name': 'Seblak & Pedas', 'icon': Icons.local_fire_department_rounded, 'color': Color(0xFF000000), 'bgColor': Color(0xFFF3F3F3), 'query': 'Seblak'},
    {'name': 'Bakso & Mie', 'icon': Icons.soup_kitchen_rounded, 'color': Color(0xFF000000), 'bgColor': Color(0xFFF3F3F3), 'query': 'Bakso'},
    {'name': 'Nasi & Lauk', 'icon': Icons.rice_bowl_rounded, 'color': Color(0xFF000000), 'bgColor': Color(0xFFF3F3F3), 'query': 'Nasi Goreng'},
    {'name': 'Kopi & Cafe', 'icon': Icons.local_cafe_rounded, 'color': Color(0xFF000000), 'bgColor': Color(0xFFF3F3F3), 'query': 'Kopi'},
    {'name': 'Snack & Boba', 'icon': Icons.fastfood_rounded, 'color': Color(0xFF000000), 'bgColor': Color(0xFFF3F3F3), 'query': 'Snack'},
    {'name': 'Sate & Bakaran', 'icon': Icons.kebab_dining_rounded, 'color': Color(0xFF000000), 'bgColor': Color(0xFFF3F3F3), 'query': 'Sate'},
    {'name': 'Aneka Minuman', 'icon': Icons.local_drink_rounded, 'color': Color(0xFF000000), 'bgColor': Color(0xFFF3F3F3), 'query': 'Minuman'},
  ];

  final List<Map<String, String>> _trendingChips = const [
    {'icon': '🌶️', 'label': 'Seblak Pedas', 'query': 'Seblak'},
    {'icon': '🎂', 'label': 'Bento Cake', 'query': 'Bento'},
    {'icon': '🍔', 'label': 'Burger Bangor', 'query': 'Burger'},
    {'icon': '🍚', 'label': 'Nasi Goreng', 'query': 'Nasi Goreng'},
    {'icon': '🍢', 'label': 'Sate Maranggi', 'query': 'Sate'},
    {'icon': '🧋', 'label': 'Bobba Drink', 'query': 'Boba'},
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchHomeData();
      _fetchRealLocation();
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

  Future<void> _fetchRealLocation() async {
    if (!mounted) return;
    setState(() {
      _isLocating = true;
    });

    try {
      final pos = await LocationService.getCurrentPosition();
      _userLat = pos.latitude;
      _userLng = pos.longitude;

      String formattedAddress = '';
      try {
        final url = Uri.parse(
          'https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.latitude}&lon=${pos.longitude}&accept-language=id',
        );
        final response = await http
            .get(url, headers: {'User-Agent': 'CicalengkaGO-Mobile/1.0'})
            .timeout(const Duration(seconds: 5));

        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          if (data is Map) {
            final addr = data['address'];
            if (addr is Map) {
              final road = addr['road'] ?? addr['suburb'] ?? addr['village'] ?? addr['neighbourhood'] ?? addr['hamlet'];
              final city = addr['county'] ?? addr['city'] ?? addr['town'] ?? addr['regency'] ?? 'Kab. Bandung';

              if (road != null && road.toString().trim().isNotEmpty) {
                formattedAddress = '${road.toString().trim()}, ${city.toString().trim()}';
              } else if (data['display_name'] != null) {
                final parts = data['display_name'].toString().split(',');
                if (parts.length >= 2) {
                  formattedAddress = '${parts[0].trim()}, ${parts[1].trim()}';
                } else {
                  formattedAddress = data['display_name'].toString();
                }
              }
            } else if (data['display_name'] != null) {
              formattedAddress = data['display_name'].toString();
            }
          }
        }
      } catch (_) {}

      if (formattedAddress.isEmpty) {
        formattedAddress = 'Cicalengka (${_userLat.toStringAsFixed(4)}, ${_userLng.toStringAsFixed(4)})';
      }

      if (mounted) {
        setState(() {
          _currentAddress = formattedAddress;
          _isLocating = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _currentAddress = 'Cicalengka, Kab. Bandung';
          _isLocating = false;
        });
      }
    }
  }

  Future<void> _openLocationPicker() async {
    final result = await LocationPickerModal.show(
      context,
      initialLat: _userLat,
      initialLng: _userLng,
    );

    if (result != null && mounted) {
      final double lat = result['lat'] ?? _userLat;
      final double lng = result['lng'] ?? _userLng;
      final String addr = result['address'] ?? _currentAddress;

      String shortAddr = addr;
      final parts = addr.split(',');
      if (parts.length >= 2) {
        shortAddr = '${parts[0].trim()}, ${parts[1].trim()}';
      }

      setState(() {
        _userLat = lat;
        _userLng = lng;
        _currentAddress = shortAddr;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Lokasi diatur ke: $shortAddr'),
          backgroundColor: AppTheme.inkBlack,
          behavior: SnackBarBehavior.floating,
          duration: const Duration(seconds: 2),
        ),
      );
    }
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

    final cartItemsCount = customerCtrl.cartCount;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: _currentIndex == 0
          ? RefreshIndicator(
              color: AppTheme.primaryRed,
              onRefresh: () async {
                await Future.wait([
                  customerCtrl.fetchHomeData(),
                  _fetchRealLocation(),
                ]);
              },
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                child: Column(
                  children: [
                    // 1. Gojek-Style Modern Red Header
                    _buildGojekHeader(user, authCtrl, cartItemsCount, context),

                    // 2. Gojek-Style Floating Super Card (CicalengkaPay)
                    Transform.translate(
                      offset: const Offset(0, -28),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: _buildGopaySuperCard(balance, context),
                      ),
                    ),

                    // Content Body Container
                    Transform.translate(
                      offset: const Offset(0, -12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // 3. Search & Trending Discovery Bar
                          _buildSearchSection(context),

                          const SizedBox(height: 14),

                          // 4. Gojek Service Grid Categories
                          _buildServiceCategoriesGrid(context),

                          const SizedBox(height: 14),

                          // 4.1 Exploration Filter Chips Bar
                          _buildExplorationFilterChips(context),

                          const SizedBox(height: 16),

                          // 5. Promo Banners Carousel
                          if (customerCtrl.banners.isNotEmpty) ...[
                            _buildBannersCarousel(customerCtrl),
                            const SizedBox(height: 18),
                          ],

                          // 5.1 Uber-Inspired Polarity-Flipped Promo Dark Band
                          _buildUberDarkPromoCard(context),

                          const SizedBox(height: 16),

                          // 5.2 Kupon & Voucher Hemat Discovery
                          _buildVoucherDiscoverySection(context),

                          const SizedBox(height: 18),

                          // 6. Flash Sale & Promo Diskon
                          if (customerCtrl.discountedProducts.isNotEmpty) ...[
                            _buildFlashSaleSection(customerCtrl, context),
                            const SizedBox(height: 20),
                          ],

                          // 7. Resto & Toko Paling Hit di Cicalengka
                          _buildTopStoresSection(customerCtrl, context),

                          const SizedBox(height: 20),

                          // 8. Recommended Food Menu Items
                          if (customerCtrl.recommendedProducts.isNotEmpty) ...[
                            _buildRecommendedProductsSection(customerCtrl, context),
                            const SizedBox(height: 24),
                          ],

                          // 9. Gojek Trust & Service Badges
                          _buildTrustBadgesSection(),

                          const SizedBox(height: 32),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            )
          : _buildTabBody(),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.06),
              blurRadius: 15,
              offset: const Offset(0, -4),
            ),
          ],
        ),
        child: Builder(builder: (context) {
          final ctrl = context.watch<CustomerController>();
          final unread = ctrl.unreadNotifCount;

          return BottomNavigationBar(
            currentIndex: _currentIndex,
            selectedItemColor: AppTheme.inkBlack,
            unselectedItemColor: const Color(0xFF94A3B8),
            selectedLabelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11),
            unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w500, fontSize: 11),
            type: BottomNavigationBarType.fixed,
            elevation: 0,
            backgroundColor: Colors.white,
            onTap: (idx) {
              setState(() {
                _currentIndex = idx;
              });
            },
            items: [
              const BottomNavigationBarItem(
                icon: Icon(Icons.grid_view_rounded),
                activeIcon: Icon(Icons.grid_view_rounded, color: AppTheme.inkBlack),
                label: 'Beranda',
              ),
              const BottomNavigationBarItem(
                icon: Icon(Icons.receipt_long_outlined),
                activeIcon: Icon(Icons.receipt_long_rounded, color: AppTheme.inkBlack),
                label: 'Pesanan',
              ),
              const BottomNavigationBarItem(
                icon: Icon(Icons.account_balance_wallet_outlined),
                activeIcon: Icon(Icons.account_balance_wallet_rounded, color: AppTheme.inkBlack),
                label: 'Dompet',
              ),
              BottomNavigationBarItem(
                icon: Stack(
                  clipBehavior: Clip.none,
                  children: [
                    const Icon(Icons.person_outline_rounded),
                    if (unread > 0)
                      Positioned(
                        right: -4,
                        top: -4,
                        child: Container(
                          width: 10,
                          height: 10,
                          decoration: const BoxDecoration(color: AppTheme.inkBlack, shape: BoxShape.circle),
                        ),
                      ),
                  ],
                ),
                activeIcon: const Icon(Icons.person_rounded, color: AppTheme.inkBlack),
                label: 'Profil',
              ),
            ],
          );
        }),
      ),
    );
  }

  Widget _buildTabBody() {
    switch (_currentIndex) {
      case 1:
        return const CustomerOrdersScreen();
      case 2:
        return const CustomerWalletScreen();
      case 3:
        return const CustomerProfileScreen();
      default:
        return const SizedBox();
    }
  }

  // --- Gojek-Style Modern Red Header ---
  Widget _buildGojekHeader(Map<String, dynamic>? user, AuthController authCtrl, int cartItemsCount, BuildContext context) {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.fromLTRB(16, MediaQuery.of(context).padding.top + 12, 16, 42),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFF262626), Color(0xFF000000)],
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
        ),
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(24)),
      ),
      child: Column(
        children: [
          Row(
            children: [
              const CicalengkaGoLogo(size: 38, borderRadius: 12, showShadow: true),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Halo, ${user?['name'] ?? 'Pelanggan'} 👋',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    GestureDetector(
                      onTap: _openLocationPicker,
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.location_on_rounded, size: 13, color: Colors.amberAccent),
                          const SizedBox(width: 4),
                          if (_isLocating) ...[
                            const SizedBox(
                              width: 10,
                              height: 10,
                              child: CircularProgressIndicator(
                                strokeWidth: 1.5,
                                color: Colors.amberAccent,
                              ),
                            ),
                            const SizedBox(width: 5),
                            Text(
                              'Mendeteksi GPS...',
                              style: TextStyle(color: Colors.white.withValues(alpha: 0.8), fontSize: 11, fontWeight: FontWeight.w600),
                            ),
                          ] else ...[
                            Flexible(
                              child: Text(
                                '$_currentAddress ▾',
                                style: TextStyle(color: Colors.white.withValues(alpha: 0.9), fontSize: 11, fontWeight: FontWeight.w600),
                                overflow: TextOverflow.ellipsis,
                                maxLines: 1,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              // Cart Button
              Stack(
                children: [
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.18),
                      shape: BoxShape.circle,
                    ),
                    child: IconButton(
                      icon: const Icon(Icons.shopping_bag_outlined, color: Colors.white, size: 20),
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => const CartScreen()),
                        );
                      },
                    ),
                  ),
                  if (cartItemsCount > 0)
                    Positioned(
                      right: 4,
                      top: 4,
                      child: Container(
                        padding: const EdgeInsets.all(4),
                        decoration: const BoxDecoration(
                          color: Colors.amber,
                          shape: BoxShape.circle,
                        ),
                        constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                        child: Text(
                          '$cartItemsCount',
                          style: const TextStyle(color: Colors.black, fontSize: 9, fontWeight: FontWeight.bold),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(width: 8),

              // Login / Logout
              if (authCtrl.isLoggedIn)
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.18),
                    shape: BoxShape.circle,
                  ),
                  child: IconButton(
                    icon: const Icon(Icons.logout_rounded, color: Colors.white, size: 20),
                    onPressed: () => authCtrl.logout(),
                    tooltip: 'Keluar',
                  ),
                )
              else
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: AppTheme.inkBlack,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                    shape: const StadiumBorder(),
                  ),
                  onPressed: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const LoginScreen()),
                    );
                  },
                  child: const Text('Masuk', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                ),
            ],
          ),
        ],
      ),
    );
  }

  // --- Gojek-Style Floating Super Card (CicalengkaPay) ---
  Widget _buildGopaySuperCard(double balance, BuildContext context) {
    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => const CustomerWalletScreen()),
        );
      },
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.08),
              blurRadius: 18,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(7),
                      decoration: BoxDecoration(
                        color: AppTheme.primaryRed.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.account_balance_wallet_rounded, color: AppTheme.primaryRed, size: 18),
                    ),
                    const SizedBox(width: 10),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: const [
                            Text(
                              'Cicalengka',
                              style: TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.bold, fontSize: 13),
                            ),
                            Text(
                              'Pay',
                              style: TextStyle(color: AppTheme.primaryRed, fontWeight: FontWeight.w900, fontSize: 13),
                            ),
                          ],
                        ),
                        Text(
                          CurrencyFormatter.formatRupiah(balance),
                          style: const TextStyle(
                            color: Color(0xFF0F172A),
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.amber.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.amber.shade300),
                  ),
                  child: Row(
                    children: const [
                      Icon(Icons.stars_rounded, color: Colors.amber, size: 14),
                      SizedBox(width: 4),
                      Text(
                        '250 Poin',
                        style: TextStyle(color: Colors.amber, fontSize: 10, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            const Divider(height: 1, color: Color(0xFFF1F5F9)),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildGopayActionButton(
                  icon: Icons.qr_code_scanner_rounded,
                  label: 'Bayar',
                  onTap: () {
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const CustomerWalletScreen()));
                  },
                ),
                _buildGopayActionButton(
                  icon: Icons.add_circle_outline_rounded,
                  label: 'Top Up',
                  onTap: () {
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const CustomerWalletScreen()));
                  },
                ),
                _buildGopayActionButton(
                  icon: Icons.history_rounded,
                  label: 'Riwayat',
                  onTap: () {
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const CustomerWalletScreen()));
                  },
                ),
                _buildGopayActionButton(
                  icon: Icons.grid_view_rounded,
                  label: 'Lainnya',
                  onTap: () {
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const CustomerWalletScreen()));
                  },
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildGopayActionButton({required IconData icon, required String label, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Column(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: const Color(0xFFF3F3F3),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(icon, color: AppTheme.inkBlack, size: 20),
          ),
          const SizedBox(height: 5),
          Text(
            label,
            style: const TextStyle(color: Color(0xFF334155), fontSize: 10, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  // --- Search Bar & Trending Discovery Section ---
  Widget _buildSearchSection(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
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
              height: 46,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(25),
                border: Border.all(color: const Color(0xFFE2E8F0)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.03),
                    blurRadius: 10,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: Row(
                children: const [
                  Icon(Icons.search_rounded, size: 20, color: AppTheme.inkBlack),
                  SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Mau makan seblak, bento, atau nasi goreng?',
                      style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8), fontWeight: FontWeight.w500),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  Icon(Icons.mic_none_rounded, size: 18, color: Color(0xFF94A3B8)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 4),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                const Text(
                  '🔥 TRENDING: ',
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: AppTheme.inkBlack),
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
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: Text(
                          '${chip['icon']} ${chip['label']}',
                          style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF334155)),
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
    );
  }

  // --- Gojek-Style Service Grid Categories ---
  Widget _buildServiceCategoriesGrid(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: const [
                  Icon(Icons.restaurant_rounded, size: 18, color: AppTheme.inkBlack),
                  SizedBox(width: 6),
                  Text(
                    'Kategori Kuliner',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
              TextButton(
                style: TextButton.styleFrom(
                  padding: EdgeInsets.zero,
                  minimumSize: Size.zero,
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  visualDensity: VisualDensity.compact,
                ),
                onPressed: () {
                  Navigator.push(context, MaterialPageRoute(builder: (_) => const CustomerSearchScreen()));
                },
                child: const Text('Semua Kuliner', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.inkBlack)),
              ),
            ],
          ),
          const SizedBox(height: 10),
          GridView.builder(
            padding: EdgeInsets.zero,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 4,
              mainAxisSpacing: 6,
              crossAxisSpacing: 8,
              childAspectRatio: 0.95,
            ),
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
                        color: cat['bgColor'] as Color,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: (cat['color'] as Color).withOpacity(0.1),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),
                      child: Icon(cat['icon'] as IconData, color: cat['color'] as Color, size: 24),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      cat['name'] as String,
                      style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
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
    );
  }

  // --- Exploration Filter Chips ---
  Widget _buildExplorationFilterChips(BuildContext context) {
    final filters = [
      {'icon': '🔥', 'label': 'Semua Kuliner', 'query': ''},
      {'icon': '🍗', 'label': 'Ayam & Bebek', 'query': 'Ayam'},
      {'icon': '🍚', 'label': 'Nasi & Bento', 'query': 'Nasi'},
      {'icon': '🍜', 'label': 'Mie & Seblak', 'query': 'Seblak'},
      {'icon': '🧋', 'label': 'Kopi & Boba', 'query': 'Kopi'},
      {'icon': '🍰', 'label': 'Camilan & Dessert', 'query': 'Camilan'},
      {'icon': '⚡', 'label': 'Flash Sale', 'query': 'Promo'},
      {'icon': '⭐', 'label': 'Rating 4.8+', 'query': 'Top'},
    ];

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        children: filters.map((f) {
          return Padding(
            padding: const EdgeInsets.only(right: 8.0),
            child: InkWell(
              borderRadius: BorderRadius.circular(20),
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => CustomerSearchScreen(initialQuery: f['query']!),
                  ),
                );
              },
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(f['icon']!, style: const TextStyle(fontSize: 12)),
                    const SizedBox(width: 6),
                    Text(
                      f['label']!,
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1E293B),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }

  // --- Voucher & Promo Discovery Section ---
  Widget _buildVoucherDiscoverySection(BuildContext context) {
    final vouchers = [
      {
        'title': 'Diskon Rp 10.000',
        'sub': 'Min. belanja Rp 30rb',
        'code': 'CICAHEBAT',
        'color': const Color(0xFFDC2626),
        'bg': const Color(0xFFFEF2F2),
        'icon': Icons.confirmation_number_rounded,
      },
      {
        'title': 'Bebas Ongkir 5 km',
        'sub': 'Tanpa syarat minimal',
        'code': 'FREESHIP',
        'color': const Color(0xFF059669),
        'bg': const Color(0xFFECFDF5),
        'icon': Icons.two_wheeler_rounded,
      },
      {
        'title': 'Cashback 20%',
        'sub': 'Khusus CicalengkaPay',
        'code': 'MAKANLANCAR',
        'color': const Color(0xFFD97706),
        'bg': const Color(0xFFFFFBEB),
        'icon': Icons.stars_rounded,
      },
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.symmetric(horizontal: 16),
          child: Row(
            children: [
              Icon(Icons.local_offer_rounded, size: 18, color: AppTheme.primaryRed),
              SizedBox(width: 6),
              Text(
                'Kupon & Voucher Hemat',
                style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
              ),
            ],
          ),
        ),
        const SizedBox(height: 10),
        SizedBox(
          height: 84,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: vouchers.length,
            itemBuilder: (context, idx) {
              final v = vouchers[idx];
              final code = v['code'] as String;
              final color = v['color'] as Color;
              final bg = v['bg'] as Color;
              final icon = v['icon'] as IconData;

              return Container(
                width: 210,
                margin: const EdgeInsets.only(right: 12),
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                decoration: BoxDecoration(
                  color: bg,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: color.withValues(alpha: 0.3)),
                ),
                child: InkWell(
                  borderRadius: BorderRadius.circular(16),
                  onTap: () {
                    AppAlert.showSuccess(
                      context,
                      title: 'Voucher Disalin! 🎟️',
                      message: 'Kode "$code" siap digunakan saat checkout.',
                    );
                  },
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: color.withValues(alpha: 0.15),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(icon, color: color, size: 20),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              v['title'] as String,
                              style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: color),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 2),
                            Text(
                              v['sub'] as String,
                              style: const TextStyle(fontSize: 10, color: Color(0xFF64748B)),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 4),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(color: color.withValues(alpha: 0.4)),
                              ),
                              child: Text(
                                'KODE: $code',
                                style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: color),
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
        ),
      ],
    );
  }

  // --- Banner Carousel Section ---
  Widget _buildBannersCarousel(CustomerController customerCtrl) {
    return Column(
      children: [
        SizedBox(
          height: 145,
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
              final imgUrl = ApiConstants.formatImageUrl(banner['image']?.toString());

              return Container(
                margin: const EdgeInsets.symmetric(horizontal: 16),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.08),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(20),
                  child: CachedNetworkImage(
                    imageUrl: imgUrl,
                    fit: BoxFit.cover,
                    width: double.infinity,
                    errorWidget: (context, url, error) => Container(
                      color: Colors.red.shade50,
                      child: const Center(
                        child: Icon(Icons.card_giftcard_rounded, color: AppTheme.primaryRed, size: 40),
                      ),
                    ),
                  ),
                ),
              );
            },
          ),
        ),
        const SizedBox(height: 8),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(customerCtrl.banners.length, (idx) {
            return AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              margin: const EdgeInsets.symmetric(horizontal: 3),
              width: _currentBannerPage == idx ? 18 : 6,
              height: 6,
              decoration: BoxDecoration(
                color: _currentBannerPage == idx ? AppTheme.primaryRed : const Color(0xFFCBD5E1),
                borderRadius: BorderRadius.circular(3),
              ),
            );
          }),
        ),
      ],
    );
  }

  // --- Uber-Inspired Polarity-Flipped Promo Dark Card ---
  Widget _buildUberDarkPromoCard(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: AppTheme.inkBlack,
          borderRadius: BorderRadius.circular(AppTheme.radiusCard),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.15),
              blurRadius: 16,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Cari Penghasilan Ekstra?',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Gabung jadi mitra Driver & antar penumpang atau kuliner di Cicalengka.',
                    style: TextStyle(
                      color: Color(0xFFAFAFAF),
                      fontSize: 12,
                    ),
                  ),
                  const SizedBox(height: 14),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: Colors.black,
                      shape: const StadiumBorder(),
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    ),
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const RegisterScreen()),
                      );
                    },
                    child: const Text(
                      'Daftar Driver',
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Container(
              width: 64,
              height: 64,
              decoration: const BoxDecoration(
                color: Color(0xFF282828),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.two_wheeler_rounded,
                color: Colors.white,
                size: 34,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // --- Flash Sale & Promo Diskon ---
  Widget _buildFlashSaleSection(CustomerController customerCtrl, BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(5),
                    decoration: BoxDecoration(
                      color: Colors.amber.shade100,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.bolt_rounded, size: 18, color: Colors.amber),
                  ),
                  const SizedBox(width: 8),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: const [
                      Text(
                        'Flash Sale Diskon',
                        style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                      ),
                      Text(
                        'Hemat hingga 50% khusus hari ini',
                        style: TextStyle(fontSize: 10, color: Color(0xFF64748B)),
                      ),
                    ],
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF2F2),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppTheme.primaryRed.withOpacity(0.3)),
                ),
                child: const Text(
                  'PROMO SPESIAL',
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: AppTheme.primaryRed),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        SizedBox(
          height: 215,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: customerCtrl.discountedProducts.length,
            itemBuilder: (context, index) {
              final prod = customerCtrl.discountedProducts[index];
              final double price = double.tryParse(prod['price']?.toString() ?? '0') ?? 0;
              final double finalPrice = double.tryParse(prod['final_price']?.toString() ?? price.toString()) ?? price;
              final imgUrl = ApiConstants.formatImageUrl(prod['image']?.toString());

              return GestureDetector(
                onTap: () {
                  ProductDetailModal.show(context, prod);
                },
                child: Container(
                  width: 140,
                  margin: const EdgeInsets.only(right: 12),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.04),
                        blurRadius: 8,
                        offset: const Offset(0, 3),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Stack(
                        children: [
                          ClipRRect(
                            borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                            child: CachedNetworkImage(
                              imageUrl: imgUrl,
                              height: 100,
                              width: double.infinity,
                              fit: BoxFit.cover,
                              errorWidget: (context, url, error) => Container(color: Colors.grey[200]),
                            ),
                          ),
                          Positioned(
                            top: 0,
                            left: 0,
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: const BoxDecoration(
                                color: AppTheme.primaryRed,
                                borderRadius: BorderRadius.only(
                                  topLeft: Radius.circular(16),
                                  bottomRight: Radius.circular(10),
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
                              style: const TextStyle(fontSize: 9, color: Color(0xFF64748B)),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            Text(
                              prod['name'] ?? '',
                              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 3),
                            Text(
                              CurrencyFormatter.formatRupiah(finalPrice),
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppTheme.primaryRed),
                            ),
                            if (price > finalPrice)
                              Text(
                                CurrencyFormatter.formatRupiah(price),
                                style: const TextStyle(fontSize: 9, color: Color(0xFF94A3B8), decoration: TextDecoration.lineThrough),
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

  // --- Top Rated Stores (Merchant List) ---
  Widget _buildTopStoresSection(CustomerController customerCtrl, BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: const [
                  Icon(Icons.storefront_rounded, size: 18, color: AppTheme.primaryRed),
                  SizedBox(width: 6),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Resto Pilihan Cicalengka',
                        style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                      ),
                      Text(
                        'Mitra kuliner favorit siap kirim cepat',
                        style: TextStyle(fontSize: 10, color: Color(0xFF64748B)),
                      ),
                    ],
                  ),
                ],
              ),
              TextButton(
                onPressed: () {
                  Navigator.push(context, MaterialPageRoute(builder: (_) => const CustomerSearchScreen()));
                },
                child: const Text('Lihat Semua', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.primaryRed)),
              ),
            ],
          ),
        ),
        const SizedBox(height: 8),
        if (customerCtrl.isLoading)
          const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator(color: AppTheme.primaryRed)))
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
              final coverUrl = ApiConstants.formatImageUrl(store['cover_photo']?.toString() ?? store['logo']?.toString());
              final isOpen = store['is_open'] == 1 || store['is_open'] == true || store['is_open'] == '1';

              return Container(
                margin: const EdgeInsets.only(bottom: 14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.04),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
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
                              height: 125,
                              width: double.infinity,
                              fit: BoxFit.cover,
                              errorWidget: (context, url, error) => Container(
                                height: 125,
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
                                color: isOpen ? const Color(0xFF16A34A) : AppTheme.primaryRed,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Row(
                                children: [
                                  Icon(isOpen ? Icons.door_front_door_rounded : Icons.lock_rounded, size: 10, color: Colors.white),
                                  const SizedBox(width: 4),
                                  Text(
                                    isOpen ? 'BUKA' : 'TUTUP',
                                    style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.w900),
                                  ),
                                ],
                              ),
                            ),
                          ),
                          Positioned(
                            bottom: 10,
                            right: 10,
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.black.withOpacity(0.7),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Row(
                                children: const [
                                  Icon(Icons.two_wheeler_rounded, size: 12, color: Colors.amberAccent),
                                  SizedBox(width: 4),
                                  Text(
                                    'Gratis Ongkir',
                                    style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
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
                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    '${store['address'] ?? 'Cicalengka'} • ${store['delivery_time'] ?? '15-25 min'}',
                                    style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
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
                                border: Border.all(color: Colors.amber.shade300),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.star_rounded, size: 14, color: Colors.amber),
                                  const SizedBox(width: 2),
                                  Text(
                                    '${store['rating'] ?? '4.8'}',
                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF0F172A)),
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
      ],
    );
  }

  // --- Recommended Products Grid ---
  Widget _buildRecommendedProductsSection(CustomerController customerCtrl, BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: const [
              Row(
                children: [
                  Icon(Icons.auto_awesome_rounded, size: 18, color: Colors.amber),
                  SizedBox(width: 6),
                  Text(
                    'Eksplor Kuliner Favorit',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
              Text(
                'Menu lezat paling banyak dipesan minggu ini',
                style: TextStyle(fontSize: 10, color: Color(0xFF64748B)),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 16),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            childAspectRatio: 0.70,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
          ),
          itemCount: customerCtrl.recommendedProducts.length,
          itemBuilder: (context, index) {
            final prod = customerCtrl.recommendedProducts[index];
            final double price = double.tryParse(prod['price']?.toString() ?? '0') ?? 0;
            final double finalPrice = double.tryParse(prod['final_price']?.toString() ?? price.toString()) ?? price;
            final imgUrl = ApiConstants.formatImageUrl(prod['image']?.toString());

            return Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.04),
                    blurRadius: 8,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: InkWell(
                borderRadius: BorderRadius.circular(16),
                onTap: () {
                  ProductDetailModal.show(context, prod);
                },
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
                            errorWidget: (context, url, error) => Container(color: Colors.grey[200]),
                          ),
                        ),
                        Positioned(
                          top: 8,
                          right: 8,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
                            decoration: BoxDecoration(
                              color: Colors.black.withValues(alpha: 0.65),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.star_rounded, size: 11, color: Colors.amber),
                                const SizedBox(width: 2),
                                Text(
                                  '${prod['rating'] ?? '4.8'}',
                                  style: const TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.bold),
                                ),
                              ],
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
                            prod['store_name'] ?? 'Mitra CicalengkaGO',
                            style: const TextStyle(fontSize: 9, color: Color(0xFF64748B)),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          Text(
                            prod['name'] ?? '',
                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 3),
                          Text(
                            CurrencyFormatter.formatRupiah(finalPrice),
                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppTheme.primaryRed),
                          ),
                          const SizedBox(height: 8),
                          SizedBox(
                            width: double.infinity,
                            height: 28,
                            child: ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppTheme.inkBlack,
                                foregroundColor: AppTheme.onPrimary,
                                padding: EdgeInsets.zero,
                                shape: const StadiumBorder(),
                                elevation: 0,
                              ),
                              onPressed: () async {
                                final ok = await customerCtrl.addToCart(int.parse(prod['id'].toString()), 1);
                                if (ok && context.mounted) {
                                  AppAlert.showCartAdded(
                                    context,
                                    productName: prod['name'] ?? 'Menu Kuliner',
                                    quantity: 1,
                                  );
                                }
                              },
                              icon: const Icon(Icons.add_rounded, size: 14),
                              label: const Text('+ Tambah', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
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
    );
  }

  // --- Trust & Service Badges ---
  Widget _buildTrustBadgesSection() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: const [
            Column(
              children: [
                Icon(Icons.bolt_rounded, color: Colors.amber, size: 26),
                SizedBox(height: 4),
                Text('Pengantaran Cepat', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                Text('15-25 Menit', style: TextStyle(fontSize: 9, color: Color(0xFF64748B))),
              ],
            ),
            Column(
              children: [
                Icon(Icons.verified_rounded, color: Color(0xFF16A34A), size: 26),
                SizedBox(height: 4),
                Text('Resto Terverifikasi', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                Text('100% Higienis', style: TextStyle(fontSize: 9, color: Color(0xFF64748B))),
              ],
            ),
            Column(
              children: [
                Icon(Icons.shield_rounded, color: AppTheme.primaryRed, size: 26),
                SizedBox(height: 4),
                Text('CicalengkaPay', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                Text('Aman & Instan', style: TextStyle(fontSize: 9, color: Color(0xFF64748B))),
              ],
            ),
          ],
        ),
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
      return const Center(child: CircularProgressIndicator(color: AppTheme.primaryRed));
    }

    if (customerCtrl.orders.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.receipt_long_outlined, size: 48, color: Color(0xFF94A3B8)),
            SizedBox(height: 8),
            Text('Belum ada riwayat pesanan', style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
          ],
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Riwayat Pesanan Saya', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
        backgroundColor: Colors.white,
        elevation: 0.5,
      ),
      body: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: customerCtrl.orders.length,
        itemBuilder: (context, index) {
          final order = customerCtrl.orders[index];
          final total = double.tryParse(order['order_amount']?.toString() ?? '0') ?? 0.0;

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            elevation: 1,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            child: ListTile(
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
              title: Text(
                'Pesanan #${order['order_code'] ?? order['id']}',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
              ),
              subtitle: Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text(
                  'Status: ${order['order_status'] ?? 'pending'} • ${CurrencyFormatter.formatRupiah(total)}',
                  style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                ),
              ),
              trailing: const Icon(Icons.chevron_right_rounded, color: AppTheme.primaryRed),
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
      ),
    );
  }
}
