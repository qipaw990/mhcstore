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
import 'order_tracking_screen.dart';

class CustomerHomeScreen extends StatefulWidget {
  const CustomerHomeScreen({super.key});

  @override
  State<CustomerHomeScreen> createState() => _CustomerHomeScreenState();
}

class _CustomerHomeScreenState extends State<CustomerHomeScreen> {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchHomeData();
    });
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

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 16,
        title: Row(
          children: [
            const CicalengkaGoLogo(size: 34, borderRadius: 10, showShadow: false),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Halo, ${user?['name'] ?? 'Pelanggan'} 👋',
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                ),
                Row(
                  children: const [
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
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.shopping_bag_outlined),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const CartScreen()),
              );
            },
          ),
          if (authCtrl.isLoggedIn)
            IconButton(
              icon: const Icon(Icons.logout),
              onPressed: () => authCtrl.logout(),
              tooltip: 'Keluar',
            )
          else
            Padding(
              padding: const EdgeInsets.only(right: 8.0),
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
                icon: const Icon(Icons.login, size: 16),
                label: const Text('Masuk', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
              ),
            ),
        ],
      ),
      body: _currentIndex == 0
          ? RefreshIndicator(
              onRefresh: () => customerCtrl.fetchHomeData(),
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // CicalengkaPay Wallet Banner
                    GestureDetector(
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
                            colors: [Color(0xFFEE2737), Color(0xFFB91C1C)],
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
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Row(
                                  children: [
                                    Icon(Icons.account_balance_wallet, color: Colors.white, size: 18),
                                    SizedBox(width: 6),
                                    Text(
                                      'Saldo CicalengkaPay',
                                      style: TextStyle(color: Colors.white70, fontSize: 12),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  CurrencyFormatter.formatRupiah(balance),
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 20,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ],
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                              decoration: BoxDecoration(
                                color: Colors.white.withOpacity(0.25),
                                borderRadius: BorderRadius.circular(30),
                              ),
                              child: const Row(
                                children: [
                                  Icon(Icons.add_circle, color: Colors.white, size: 16),
                                  SizedBox(width: 4),
                                  Text(
                                    'Isi Saldo',
                                    style: TextStyle(
                                      color: Colors.white,
                                      fontWeight: FontWeight.bold,
                                      fontSize: 12,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),

                    const SizedBox(height: 24),
                    const Text(
                      'Layanan CicalengkaGO',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 12),

                    // Grid Modules
                    GridView.count(
                      crossAxisCount: 4,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      mainAxisSpacing: 12,
                      crossAxisSpacing: 12,
                      children: [
                        _buildModuleItem(Icons.restaurant, 'CicaFood', Colors.orange),
                        _buildModuleItem(Icons.storefront, 'CicaMart', const Color(0xFF059669)),
                        _buildModuleItem(Icons.local_shipping, 'CicaExpress', Colors.blue),
                        _buildModuleItem(Icons.medical_services, 'CicaMed', Colors.purple),
                      ],
                    ),

                    const SizedBox(height: 24),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Mitra Toko & Resto Cicalengka',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                        TextButton(
                          onPressed: () {},
                          child: const Text('Lihat Semua'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),

                    // Store Cards List
                    if (customerCtrl.isLoading)
                      const Center(child: CircularProgressIndicator())
                    else if (customerCtrl.stores.isEmpty)
                      const Center(child: Text('Belum ada data toko tersedia'))
                    else
                      ListView.builder(
                        itemCount: customerCtrl.stores.length,
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemBuilder: (context, index) {
                          final store = customerCtrl.stores[index];
                          final logoUrl = store['logo'] != null && store['logo'].toString().startsWith('http')
                              ? store['logo']
                              : '${ApiConstants.imageBaseUrl}/${store['logo'] ?? ''}';

                          return Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            child: ListTile(
                              leading: ClipRRect(
                                borderRadius: BorderRadius.circular(10),
                                child: CachedNetworkImage(
                                  imageUrl: logoUrl,
                                  width: 50,
                                  height: 50,
                                  fit: BoxFit.cover,
                                  placeholder: (_, url) => Container(color: Colors.grey[200]),
                                  errorWidget: (_, url, error) => const Icon(Icons.store),
                                ),
                              ),
                              title: Text(
                                store['name'] ?? 'Mitra Resto',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                              ),
                              subtitle: Text(
                                '${store['address'] ?? 'Cicalengka'} • ${store['delivery_time'] ?? '15-25 min'}',
                                style: const TextStyle(fontSize: 12),
                              ),
                              trailing: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: Colors.amber[50],
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(color: Colors.amber),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const Icon(Icons.star, size: 14, color: Colors.amber),
                                    const SizedBox(width: 2),
                                    Text(
                                      '${store['rating'] ?? '4.8'}',
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11),
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

  Widget _buildModuleItem(IconData icon, String label, Color color) {
    return Column(
      children: [
        Container(
          width: 50,
          height: 50,
          decoration: BoxDecoration(
            color: color.withOpacity(0.12),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Icon(icon, color: color, size: 26),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600),
        ),
      ],
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
