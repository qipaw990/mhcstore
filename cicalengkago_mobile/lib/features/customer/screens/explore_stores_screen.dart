import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/location_service.dart';
import '../controllers/customer_controller.dart';
import 'store_detail_screen.dart';

class ExploreStoresScreen extends StatefulWidget {
  final String? initialCategory;

  const ExploreStoresScreen({super.key, this.initialCategory});

  @override
  State<ExploreStoresScreen> createState() => _ExploreStoresScreenState();
}

class _ExploreStoresScreenState extends State<ExploreStoresScreen> {
  final TextEditingController _searchCtrl = TextEditingController();
  String _searchQuery = '';
  String _selectedFilter = 'Semua';
  String _sortBy = 'popular'; // popular, rating, fastest, name

  double _userLat = -6.9835;
  double _userLng = 107.8335;

  final List<String> _filterCategories = const [
    'Semua',
    'Gratis Ongkir (<300m)',
    'Buka Sekarang',
    'Rating 4.5+',
    'Diskon Ongkir',
    'Ayam & Bebek',
    'Seblak & Pedas',
    'Bakso & Mie',
    'Kopi & Cafe',
    'Snack & Boba',
    'Nasi & Lauk',
  ];

  double _calculateDistanceKm(double sLat, double sLng, double uLat, double uLng) {
    if (sLat == 0 || sLng == 0 || uLat == 0 || uLng == 0) return 0.0;
    const double p = 0.017453292519943295; // Math.PI / 180
    final double a = 0.5 -
        math.cos((uLat - sLat) * p) / 2 +
        math.cos(sLat * p) * math.cos(uLat * p) * (1 - math.cos((uLng - sLng) * p)) / 2;
    final double dist = 12742 * math.asin(math.sqrt(a)); // 2 * R; R = 6371 km
    return dist;
  }

  @override
  void initState() {
    super.initState();
    if (widget.initialCategory != null && widget.initialCategory!.isNotEmpty) {
      _selectedFilter = widget.initialCategory!;
    }
    _fetchGpsLocation();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchExploreStores();
    });
  }

  Future<void> _fetchGpsLocation() async {
    try {
      final pos = await LocationService.getCurrentPosition();
      if (mounted) {
        setState(() {
          _userLat = pos.latitude;
          _userLng = pos.longitude;
        });
      }
    } catch (_) {}
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  List<dynamic> _getFilteredStores(List<dynamic> stores) {
    return stores.where((s) {
      final store = s is Map<String, dynamic> ? s : Map<String, dynamic>.from(s as Map);
      final name = (store['name'] ?? '').toString().toLowerCase();
      final address = (store['address'] ?? '').toString().toLowerCase();
      final description = (store['description'] ?? '').toString().toLowerCase();
      final isOpen = store['is_open'] == 1 || store['is_open'] == true || store['is_open'] == '1';
      final rating = double.tryParse(store['rating']?.toString() ?? '4.8') ?? 4.8;

      final double sLat = double.tryParse(store['latitude']?.toString() ?? '0') ?? 0.0;
      final double sLng = double.tryParse(store['longitude']?.toString() ?? '0') ?? 0.0;
      final double distKm = (sLat != 0 && sLng != 0 && _userLat != 0 && _userLng != 0)
          ? _calculateDistanceKm(sLat, sLng, _userLat, _userLng)
          : 0.0;

      // 1. Search Query Filter
      if (_searchQuery.isNotEmpty) {
        final q = _searchQuery.toLowerCase();
        if (!name.contains(q) && !address.contains(q) && !description.contains(q)) {
          return false;
        }
      }

      // 2. Chip Category Filter
      if (_selectedFilter == 'Gratis Ongkir (<300m)' || _selectedFilter == 'Gratis Ongkir') {
        if (distKm <= 0.0 || distKm > 0.30) return false;
      } else if (_selectedFilter == 'Buka Sekarang') {
        if (!isOpen) return false;
      } else if (_selectedFilter == 'Rating 4.5+') {
        if (rating < 4.5) return false;
      } else if (_selectedFilter == 'Diskon Ongkir') {
        // All featured stores have ongkir promo
      } else if (_selectedFilter != 'Semua') {
        final catQuery = _selectedFilter.split('&')[0].trim().toLowerCase();
        if (!name.contains(catQuery) && !description.contains(catQuery)) {
          return false;
        }
      }

      return true;
    }).toList()
      ..sort((a, b) {
        final storeA = a is Map ? a : {};
        final storeB = b is Map ? b : {};

        if (_sortBy == 'rating') {
          final rA = double.tryParse(storeA['rating']?.toString() ?? '0') ?? 0;
          final rB = double.tryParse(storeB['rating']?.toString() ?? '0') ?? 0;
          return rB.compareTo(rA);
        } else if (_sortBy == 'name') {
          final nA = (storeA['name'] ?? '').toString();
          final nB = (storeB['name'] ?? '').toString();
          return nA.compareTo(nB);
        } else if (_sortBy == 'fastest') {
          final tA = int.tryParse(storeA['delivery_time']?.toString().split('-')[0].trim() ?? '20') ?? 20;
          final tB = int.tryParse(storeB['delivery_time']?.toString().split('-')[0].trim() ?? '20') ?? 20;
          return tA.compareTo(tB);
        }
        return 0; // Default popular order
      });
  }

  void _showSortBottomSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Urutkan Resto Berdasarkan',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded, size: 20),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                _buildSortOption(ctx, title: 'Paling Populer', value: 'popular', icon: Icons.trending_up_rounded),
                _buildSortOption(ctx, title: 'Rating Tertinggi ⭐', value: 'rating', icon: Icons.star_rounded),
                _buildSortOption(ctx, title: 'Waktu Antar Tercepat ⏱️', value: 'fastest', icon: Icons.speed_rounded),
                _buildSortOption(ctx, title: 'Nama Toko (A - Z)', value: 'name', icon: Icons.sort_by_alpha_rounded),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildSortOption(BuildContext ctx, {required String title, required String value, required IconData icon}) {
    final isSelected = _sortBy == value;
    return ListTile(
      leading: Icon(icon, color: isSelected ? const Color(0xFFEF4444) : const Color(0xFF64748B)),
      title: Text(
        title,
        style: TextStyle(
          fontSize: 13.5,
          fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
          color: isSelected ? const Color(0xFFEF4444) : const Color(0xFF0F172A),
        ),
      ),
      trailing: isSelected ? const Icon(Icons.check_circle_rounded, color: Color(0xFFEF4444), size: 20) : null,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      tileColor: isSelected ? const Color(0xFFFEF2F2) : Colors.transparent,
      onTap: () {
        setState(() => _sortBy = value);
        Navigator.pop(ctx);
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final customerCtrl = context.watch<CustomerController>();
    final allStores = customerCtrl.stores.isNotEmpty ? customerCtrl.stores : customerCtrl.topRatedStores;
    final filteredStores = _getFilteredStores(allStores);

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0.5,
        titleSpacing: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: Color(0xFF0F172A)),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'Semua Resto Cicalengka',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: Color(0xFF0F172A),
          ),
        ),
        actions: [
          IconButton(
            tooltip: 'Urutkan Resto',
            icon: const Icon(Icons.tune_rounded, color: Color(0xFFEF4444)),
            onPressed: () => _showSortBottomSheet(context),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(106),
          child: Column(
            children: [
              // Search Input Box
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                child: Container(
                  height: 42,
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: TextField(
                    controller: _searchCtrl,
                    style: const TextStyle(fontSize: 13),
                    decoration: InputDecoration(
                      hintText: 'Cari nama resto, makanan, atau lokasi...',
                      hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                      prefixIcon: const Icon(Icons.search_rounded, size: 20, color: Color(0xFF64748B)),
                      suffixIcon: _searchQuery.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear_rounded, size: 18, color: Color(0xFF64748B)),
                              onPressed: () {
                                _searchCtrl.clear();
                                setState(() => _searchQuery = '');
                              },
                            )
                          : null,
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(vertical: 10),
                    ),
                    onChanged: (val) => setState(() => _searchQuery = val.trim()),
                  ),
                ),
              ),

              // Filter Chips Carousel
              SizedBox(
                height: 42,
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                  itemCount: _filterCategories.length,
                  itemBuilder: (context, idx) {
                    final cat = _filterCategories[idx];
                    final isSelected = _selectedFilter == cat;
                    return Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: FilterChip(
                        label: Text(cat),
                        selected: isSelected,
                        onSelected: (val) {
                          setState(() => _selectedFilter = cat);
                        },
                        selectedColor: const Color(0xFFEF4444),
                        backgroundColor: Colors.white,
                        labelStyle: TextStyle(
                          fontSize: 11.5,
                          fontWeight: FontWeight.bold,
                          color: isSelected ? Colors.white : const Color(0xFF475569),
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(20),
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
              const SizedBox(height: 4),
            ],
          ),
        ),
      ),
      body: RefreshIndicator(
        color: const Color(0xFFEF4444),
        onRefresh: () async {
          await customerCtrl.fetchHomeData();
          await customerCtrl.fetchExploreStores();
        },
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header stats
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Menampilkan ${filteredStores.length} Resto & Toko',
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF64748B)),
                  ),
                  GestureDetector(
                    onTap: () => _showSortBottomSheet(context),
                    child: Row(
                      children: [
                        const Icon(Icons.sort_rounded, size: 14, color: Color(0xFFEF4444)),
                        const SizedBox(width: 4),
                        Text(
                          _sortBy == 'rating'
                              ? 'Rating'
                              : _sortBy == 'fastest'
                                  ? 'Tercepat'
                                  : _sortBy == 'name'
                                      ? 'A-Z'
                                      : 'Terpopuler',
                          style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFFEF4444)),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            // Store List
            Expanded(
              child: customerCtrl.isLoading && allStores.isEmpty
                  ? const Center(child: CircularProgressIndicator(color: Color(0xFFEF4444)))
                  : filteredStores.isEmpty
                      ? Center(
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
                                  child: const Icon(Icons.storefront_outlined, size: 40, color: Color(0xFFEF4444)),
                                ),
                                const SizedBox(height: 16),
                                const Text(
                                  'Resto Tidak Ditemukan',
                                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  'Tidak ada resto yang sesuai dengan filter "$_selectedFilter"${_searchQuery.isNotEmpty ? ' dan kata kunci "$_searchQuery"' : ''}.',
                                  style: const TextStyle(fontSize: 12, color: Color(0xFF64748B), height: 1.4),
                                  textAlign: TextAlign.center,
                                ),
                                const SizedBox(height: 16),
                                ElevatedButton(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFFEF4444),
                                    foregroundColor: Colors.white,
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                  ),
                                  onPressed: () {
                                    _searchCtrl.clear();
                                    setState(() {
                                      _searchQuery = '';
                                      _selectedFilter = 'Semua';
                                      _sortBy = 'popular';
                                    });
                                  },
                                  child: const Text('Reset Filter'),
                                ),
                              ],
                            ),
                          ),
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                          itemCount: filteredStores.length,
                          itemBuilder: (context, index) {
                            final store = filteredStores[index] is Map<String, dynamic>
                                ? filteredStores[index] as Map<String, dynamic>
                                : Map<String, dynamic>.from(filteredStores[index] as Map);

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
                                                child: const Icon(
                                                  Icons.storefront_rounded,
                                                  size: 32,
                                                  color: Color(0xFF94A3B8),
                                                ),
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
                                                style: const TextStyle(
                                                  color: Colors.white,
                                                  fontSize: 7.5,
                                                  fontWeight: FontWeight.w900,
                                                ),
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: () {
                                          final double sLat = double.tryParse(store['latitude']?.toString() ?? '0') ?? 0.0;
                                          final double sLng = double.tryParse(store['longitude']?.toString() ?? '0') ?? 0.0;
                                          double distKm = 0.0;
                                          bool hasCoords = (sLat != 0.0 && sLng != 0.0 && _userLat != 0.0 && _userLng != 0.0);
                                          if (hasCoords) {
                                            distKm = _calculateDistanceKm(sLat, sLng, _userLat, _userLng);
                                          }
                                          final bool isCloseMerchant = hasCoords && (distKm <= 0.30);
                                          final String distLabel = hasCoords
                                              ? (distKm < 1.0 ? '${(distKm * 1000).toInt()} m' : '${distKm.toStringAsFixed(1)} km')
                                              : (store['address']?.toString() ?? 'Cicalengka');

                                          return Column(
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
                                                  const Icon(
                                                    Icons.verified_rounded,
                                                    size: 14,
                                                    color: Color(0xFF0284C7),
                                                  ),
                                                ],
                                              ),
                                              const SizedBox(height: 3),
                                              Text(
                                                '$distLabel • ${store['delivery_time'] ?? '15-25 min'}',
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
                                                          style: const TextStyle(
                                                            fontWeight: FontWeight.bold,
                                                            fontSize: 10.5,
                                                            color: Color(0xFF0F172A),
                                                          ),
                                                        ),
                                                      ],
                                                    ),
                                                  ),
                                                  const SizedBox(width: 6),
                                                  if (isCloseMerchant)
                                                    Container(
                                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                      decoration: BoxDecoration(
                                                        color: const Color(0xFFDCFCE7),
                                                        borderRadius: BorderRadius.circular(8),
                                                        border: Border.all(color: const Color(0xFF86EFAC)),
                                                      ),
                                                      child: const Row(
                                                        mainAxisSize: MainAxisSize.min,
                                                        children: [
                                                          Icon(Icons.storefront_rounded, size: 11, color: Color(0xFF16A34A)),
                                                          SizedBox(width: 3),
                                                          Text(
                                                            'Gratis Ongkir • Toko',
                                                            style: TextStyle(
                                                              color: Color(0xFF15803D),
                                                              fontSize: 9.5,
                                                              fontWeight: FontWeight.bold,
                                                            ),
                                                          ),
                                                        ],
                                                      ),
                                                    )
                                                  else
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
                                                            'Mitra Driver',
                                                            style: TextStyle(
                                                              color: Color(0xFFEF4444),
                                                              fontSize: 9.5,
                                                              fontWeight: FontWeight.bold,
                                                            ),
                                                          ),
                                                        ],
                                                      ),
                                                    ),
                                                ],
                                              ),
                                            ],
                                          );
                                        }(),
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
            ),
          ],
        ),
      ),
    );
  }
}
