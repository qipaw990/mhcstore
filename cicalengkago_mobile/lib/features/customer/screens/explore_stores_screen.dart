import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/location_service.dart';
import '../../../core/services/app_config_service.dart';
import '../../../core/theme/app_theme.dart';
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
  String _sortBy = 'popular';

  late double _userLat = AppConfigService.instance.defaultLat;
  late double _userLng = AppConfigService.instance.defaultLng;

  final List<String> _filterCategories = const [
    'Semua',
    'Gratis Ongkir (<300m)',
    'Buka Sekarang',
    'Rating 4.5+',
    'Ayam & Bebek',
    'Seblak & Pedas',
    'Bakso & Mie',
    'Kopi & Cafe',
    'Snack & Boba',
    'Nasi & Lauk',
  ];

  double _calcDistKm(double sLat, double sLng, double uLat, double uLng) {
    if (sLat == 0 || sLng == 0 || uLat == 0 || uLng == 0) return 0.0;
    const double p = 0.017453292519943295;
    final double a = 0.5 -
        math.cos((uLat - sLat) * p) / 2 +
        math.cos(sLat * p) * math.cos(uLat * p) * (1 - math.cos((uLng - sLng) * p)) / 2;
    return 12742 * math.asin(math.sqrt(a));
  }

  @override
  void initState() {
    super.initState();
    if (widget.initialCategory != null && widget.initialCategory!.isNotEmpty) {
      _selectedFilter = widget.initialCategory!;
    }
    _fetchGps();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchExploreStores();
    });
  }

  Future<void> _fetchGps() async {
    try {
      final pos = await LocationService.getCurrentPosition();
      if (mounted) setState(() { _userLat = pos.latitude; _userLng = pos.longitude; });
    } catch (_) {}
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  List<dynamic> _filtered(List<dynamic> stores) {
    return stores.where((s) {
      final store = s is Map<String, dynamic> ? s : Map<String, dynamic>.from(s as Map);
      final name = (store['name'] ?? '').toString().toLowerCase();
      final address = (store['address'] ?? '').toString().toLowerCase();
      final desc = (store['description'] ?? '').toString().toLowerCase();
      final isOpen = store['is_open'] == 1 || store['is_open'] == true || store['is_open'] == '1';
      final rating = double.tryParse(store['rating']?.toString() ?? '') ?? 5.0;
      final sLat = double.tryParse(store['latitude']?.toString() ?? '0') ?? 0.0;
      final sLng = double.tryParse(store['longitude']?.toString() ?? '0') ?? 0.0;
      final dist = _calcDistKm(sLat, sLng, _userLat, _userLng);

      if (_searchQuery.isNotEmpty) {
        final q = _searchQuery.toLowerCase();
        if (!name.contains(q) && !address.contains(q) && !desc.contains(q)) return false;
      }

      if (_selectedFilter == 'Gratis Ongkir (<300m)') {
        if (dist <= 0.0 || dist > 0.30) return false;
      } else if (_selectedFilter == 'Buka Sekarang') {
        if (!isOpen) return false;
      } else if (_selectedFilter == 'Rating 4.5+') {
        if (rating < 4.5) return false;
      } else if (_selectedFilter != 'Semua') {
        final q = _selectedFilter.split('&')[0].trim().toLowerCase();
        if (!name.contains(q) && !desc.contains(q)) return false;
      }

      return true;
    }).toList()
      ..sort((a, b) {
        final sa = a is Map ? a : {};
        final sb = b is Map ? b : {};
        if (_sortBy == 'rating') {
          return (double.tryParse(sb['rating']?.toString() ?? '0') ?? 0)
              .compareTo(double.tryParse(sa['rating']?.toString() ?? '0') ?? 0);
        } else if (_sortBy == 'name') {
          return (sa['name'] ?? '').toString().compareTo((sb['name'] ?? '').toString());
        } else if (_sortBy == 'fastest') {
          final tA = int.tryParse(sa['delivery_time']?.toString().split('-')[0].trim() ?? '20') ?? 20;
          final tB = int.tryParse(sb['delivery_time']?.toString().split('-')[0].trim() ?? '20') ?? 20;
          return tA.compareTo(tB);
        }
        return 0;
      });
  }

  void _showSortSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 18, 20, 12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Urutkan Berdasarkan',
                      style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Color(0xFF0F172A))),
                  IconButton(
                    icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF64748B)),
                    onPressed: () => Navigator.pop(ctx),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              ...[
                ('popular', 'Paling Populer', Icons.trending_up_rounded),
                ('rating', 'Rating Tertinggi', Icons.star_rounded),
                ('fastest', 'Waktu Antar Tercepat', Icons.speed_rounded),
                ('name', 'Nama Toko (A - Z)', Icons.sort_by_alpha_rounded),
              ].map((entry) {
                final value = entry.$1;
                final title = entry.$2;
                final icon = entry.$3;
                final isSel = _sortBy == value;
                return ListTile(
                  leading: Container(
                    width: 36, height: 36,
                    decoration: BoxDecoration(
                      color: isSel ? AppTheme.brandOrange.withValues(alpha: 0.1) : const Color(0xFFF1F5F9),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(icon, size: 18, color: isSel ? AppTheme.brandOrange : const Color(0xFF64748B)),
                  ),
                  title: Text(title,
                      style: TextStyle(
                        fontSize: 13.5,
                        fontWeight: isSel ? FontWeight.w700 : FontWeight.w500,
                        color: isSel ? AppTheme.brandOrange : const Color(0xFF0F172A),
                      )),
                  trailing: isSel ? Icon(Icons.check_circle_rounded, color: AppTheme.brandOrange, size: 20) : null,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  onTap: () { setState(() => _sortBy = value); Navigator.pop(ctx); },
                );
              }),
            ],
          ),
        ),
      ),
    );
  }

  String _getSortLabel() {
    switch (_sortBy) {
      case 'rating': return 'Rating';
      case 'fastest': return 'Tercepat';
      case 'name': return 'A–Z';
      default: return 'Terpopuler';
    }
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<CustomerController>();
    final allStores = ctrl.stores.isNotEmpty ? ctrl.stores : ctrl.topRatedStores;
    final filteredStores = _filtered(allStores);

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 1,
        centerTitle: false,
        leading: IconButton(
          icon: Container(
            padding: const EdgeInsets.all(6),
            decoration: const BoxDecoration(color: Color(0xFFF1F5F9), shape: BoxShape.circle),
            child: const Icon(Icons.arrow_back_rounded, color: Color(0xFF0F172A), size: 18),
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text('Semua Mitra Resto',
            style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: Color(0xFF0F172A), letterSpacing: -0.3)),
        actions: [
          GestureDetector(
            onTap: () => _showSortSheet(context),
            child: Container(
              margin: const EdgeInsets.only(right: 14),
              padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
              decoration: BoxDecoration(
                color: const Color(0xFFF1F5F9),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.tune_rounded, size: 14, color: Color(0xFF475569)),
                  const SizedBox(width: 5),
                  Text(_getSortLabel(),
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF334155))),
                ],
              ),
            ),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(104),
          child: Container(
            color: Colors.white,
            child: Column(
              children: [
                // Search Bar
                Padding(
                  padding: const EdgeInsets.fromLTRB(14, 6, 14, 6),
                  child: Container(
                    height: 44,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: TextField(
                      controller: _searchCtrl,
                      style: const TextStyle(fontSize: 13.5, color: Color(0xFF0F172A)),
                      decoration: InputDecoration(
                        hintText: 'Cari nama resto, menu, atau alamat...',
                        hintStyle: const TextStyle(fontSize: 12.5, color: Color(0xFF94A3B8)),
                        prefixIcon: const Icon(Icons.search_rounded, size: 20, color: Color(0xFF64748B)),
                        suffixIcon: _searchQuery.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear_rounded, size: 18, color: Color(0xFF64748B)),
                                onPressed: () { _searchCtrl.clear(); setState(() => _searchQuery = ''); },
                              )
                            : null,
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      onChanged: (val) => setState(() => _searchQuery = val.trim()),
                    ),
                  ),
                ),
                // Filter Chips
                SizedBox(
                  height: 40,
                  child: ListView.builder(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.fromLTRB(14, 2, 14, 6),
                    itemCount: _filterCategories.length,
                    itemBuilder: (_, idx) {
                      final cat = _filterCategories[idx];
                      final isSel = _selectedFilter == cat;
                      return Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: GestureDetector(
                          onTap: () => setState(() => _selectedFilter = cat),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 160),
                            padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 6),
                            decoration: BoxDecoration(
                              gradient: isSel ? AppTheme.primaryGradient : null,
                              color: isSel ? null : Colors.white,
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(
                                color: isSel ? Colors.transparent : const Color(0xFFE2E8F0),
                              ),
                              boxShadow: isSel
                                  ? [BoxShadow(color: AppTheme.brandOrange.withValues(alpha: 0.25), blurRadius: 6, offset: const Offset(0, 2))]
                                  : null,
                            ),
                            child: Text(
                              cat,
                              style: TextStyle(
                                fontSize: 11.5,
                                fontWeight: isSel ? FontWeight.w700 : FontWeight.w600,
                                color: isSel ? Colors.white : const Color(0xFF475569),
                              ),
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
        ),
      ),
      body: RefreshIndicator(
        color: AppTheme.brandOrange,
        backgroundColor: Colors.white,
        onRefresh: () async {
          await ctrl.fetchHomeData();
          await ctrl.fetchExploreStores();
        },
        child: ctrl.isLoading && allStores.isEmpty
            ? const Center(child: CircularProgressIndicator(color: AppTheme.brandOrange, strokeWidth: 2.5))
            : Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Stats & active filter label
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 6),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          '${filteredStores.length} Resto & Toko tersedia',
                          style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
                        ),
                        if (_selectedFilter != 'Semua' || _searchQuery.isNotEmpty)
                          GestureDetector(
                            onTap: () {
                              _searchCtrl.clear();
                              setState(() { _searchQuery = ''; _selectedFilter = 'Semua'; _sortBy = 'popular'; });
                            },
                            child: Row(
                              children: [
                                const Icon(Icons.filter_list_off_rounded, size: 13, color: AppTheme.brandOrange),
                                const SizedBox(width: 4),
                                const Text('Reset Filter',
                                    style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: AppTheme.brandOrange)),
                              ],
                            ),
                          ),
                      ],
                    ),
                  ),

                  Expanded(
                    child: filteredStores.isEmpty
                        ? _buildEmptyState()
                        : ListView.builder(
                            physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                            padding: const EdgeInsets.fromLTRB(14, 0, 14, 30),
                            itemCount: filteredStores.length,
                            itemBuilder: (context, index) {
                              final store = filteredStores[index] is Map<String, dynamic>
                                  ? filteredStores[index] as Map<String, dynamic>
                                  : Map<String, dynamic>.from(filteredStores[index] as Map);
                              return _buildStoreCard(context, store);
                            },
                          ),
                  ),
                ],
              ),
      ),
    );
  }

  Widget _buildStoreCard(BuildContext context, Map<String, dynamic> store) {
    final coverUrl = ApiConstants.formatImageUrl(
        store['cover_photo']?.toString() ?? store['logo']?.toString());
    final isOpen = store['is_open'] == 1 || store['is_open'] == true || store['is_open'] == '1';
    final storeId = int.tryParse(store['id']?.toString() ?? '0') ?? 0;
    final rating = double.tryParse(store['rating']?.toString() ?? '') ?? 5.0;
    final sLat = double.tryParse(store['latitude']?.toString() ?? '0') ?? 0.0;
    final sLng = double.tryParse(store['longitude']?.toString() ?? '0') ?? 0.0;
    final hasCoords = sLat != 0.0 && sLng != 0.0 && _userLat != 0.0 && _userLng != 0.0;
    final distKm = hasCoords ? _calcDistKm(sLat, sLng, _userLat, _userLng) : 0.0;
    final isClose = hasCoords && distKm <= 0.30;
    final distLabel = hasCoords
        ? (distKm < 1.0 ? '${(distKm * 1000).toInt()} m' : '${distKm.toStringAsFixed(1)} km')
        : (store['address']?.toString() ?? 'Cicalengka');
    final delivTime = store['delivery_time']?.toString() ?? '15-25 mnt';

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 8, offset: const Offset(0, 2)),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () {
            if (storeId > 0) {
              Navigator.push(context, MaterialPageRoute(builder: (_) => StoreDetailScreen(storeId: storeId)));
            }
          },
          child: Padding(
            padding: const EdgeInsets.all(10),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                // Store Thumbnail
                Stack(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(14),
                      child: CachedNetworkImage(
                        imageUrl: coverUrl,
                        height: 78,
                        width: 78,
                        fit: BoxFit.cover,
                        errorWidget: (_, __, ___) => Container(
                          height: 78,
                          width: 78,
                          decoration: BoxDecoration(
                            color: const Color(0xFFF1F5F9),
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: const Icon(Icons.storefront_rounded, size: 34, color: Color(0xFF94A3B8)),
                        ),
                      ),
                    ),
                    Positioned(
                      top: 5,
                      left: 5,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2.5),
                        decoration: BoxDecoration(
                          color: isOpen ? const Color(0xFF16A34A) : const Color(0xFF64748B),
                          borderRadius: BorderRadius.circular(5),
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

                // Store Details
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Name + Verified
                      Row(
                        children: [
                          Flexible(
                            child: Text(
                              store['name']?.toString() ?? 'Mitra Resto',
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 13.5,
                                color: Color(0xFF0F172A),
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          const SizedBox(width: 4),
                          const Icon(Icons.verified_rounded, size: 13, color: Color(0xFF0284C7)),
                        ],
                      ),
                      const SizedBox(height: 3),

                      // Distance + Delivery Time
                      Row(
                        children: [
                          const Icon(Icons.location_on_rounded, size: 12, color: Color(0xFF94A3B8)),
                          const SizedBox(width: 2),
                          Flexible(
                            child: Text(
                              '$distLabel • $delivTime',
                              style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 7),

                      // Rating + Promo Badges
                      Row(
                        children: [
                          // Rating Pill
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2.5),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFFFBEB),
                              borderRadius: BorderRadius.circular(7),
                              border: Border.all(color: const Color(0xFFFDE68A)),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.star_rounded, size: 12, color: Colors.amber),
                                const SizedBox(width: 2),
                                Text(
                                  rating.toStringAsFixed(1),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w800,
                                    fontSize: 10.5,
                                    color: Color(0xFF0F172A),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 5),

                          // Ongkir / Delivery badge
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2.5),
                            decoration: BoxDecoration(
                              color: isClose ? const Color(0xFFDCFCE7) : const Color(0xFFFEF2F2),
                              borderRadius: BorderRadius.circular(7),
                              border: Border.all(
                                  color: isClose ? const Color(0xFF86EFAC) : const Color(0xFFFECACA)),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  isClose ? Icons.storefront_rounded : Icons.two_wheeler_rounded,
                                  size: 11,
                                  color: isClose ? const Color(0xFF16A34A) : const Color(0xFFEF4444),
                                ),
                                const SizedBox(width: 3),
                                Text(
                                  isClose ? 'Gratis Ongkir' : 'Mitra Driver',
                                  style: TextStyle(
                                    color: isClose ? const Color(0xFF15803D) : const Color(0xFFEF4444),
                                    fontSize: 9.5,
                                    fontWeight: FontWeight.w700,
                                  ),
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
                const Icon(Icons.chevron_right_rounded, color: Color(0xFFCBD5E1), size: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 80, height: 80,
              decoration: BoxDecoration(
                color: const Color(0xFFFFF7ED),
                shape: BoxShape.circle,
                border: Border.all(color: const Color(0xFFFFEDD5), width: 2),
              ),
              child: const Icon(Icons.storefront_outlined, size: 38, color: AppTheme.brandOrange),
            ),
            const SizedBox(height: 18),
            const Text('Resto Tidak Ditemukan',
                style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: Color(0xFF0F172A))),
            const SizedBox(height: 8),
            const Text(
              'Tidak ada resto yang cocok dengan filter atau kata kunci pencarian yang kamu pilih.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 13, color: Color(0xFF64748B), height: 1.5),
            ),
            const SizedBox(height: 20),
            ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.brandOrange,
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 11),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              ),
              onPressed: () {
                _searchCtrl.clear();
                setState(() { _searchQuery = ''; _selectedFilter = 'Semua'; _sortBy = 'popular'; });
              },
              icon: const Icon(Icons.filter_list_off_rounded, size: 16),
              label: const Text('Reset Filter', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
            ),
          ],
        ),
      ),
    );
  }
}
