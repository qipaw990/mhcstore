import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:http/http.dart' as http;
import '../theme/app_theme.dart';
import '../services/location_service.dart';
import '../constants/zone_constants.dart';

class LocationPickerModal extends StatefulWidget {
  final double initialLat;
  final double initialLng;

  const LocationPickerModal({
    super.key,
    this.initialLat = -6.9835,
    this.initialLng = 107.8335,
  });

  static Future<Map<String, dynamic>?> show(
    BuildContext context, {
    double initialLat = -6.9835,
    double initialLng = 107.8335,
  }) {
    return showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => LocationPickerModal(
        initialLat: initialLat,
        initialLng: initialLng,
      ),
    );
  }

  @override
  State<LocationPickerModal> createState() => _LocationPickerModalState();
}

class _LocationPickerModalState extends State<LocationPickerModal> {
  late final MapController _mapController;
  late double _pickedLat;
  late double _pickedLng;
  String _addressText = 'Mendeteksi alamat titik lokasi...';
  bool _isGeocoding = false;
  bool _isLocatingGps = false;

  @override
  void initState() {
    super.initState();
    _mapController = MapController();
    _pickedLat = widget.initialLat;
    _pickedLng = widget.initialLng;

    // Trigger initial reverse geocode
    _reverseGeocode(_pickedLat, _pickedLng);
  }

  @override
  void dispose() {
    _mapController.dispose();
    super.dispose();
  }

  Future<void> _reverseGeocode(double lat, double lng) async {
    setState(() {
      _isGeocoding = true;
      _addressText = 'Mencari alamat lokasi...';
    });

    try {
      final url = Uri.parse(
          'https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lng&accept-language=id');
      final response = await http
          .get(url, headers: {'User-Agent': 'CicalengkaGO-Mobile/1.0'}).timeout(
              const Duration(seconds: 4));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is Map && data['display_name'] != null) {
          if (mounted) {
            setState(() {
              _addressText = data['display_name'].toString();
              _isGeocoding = false;
            });
            return;
          }
        }
      }
    } catch (_) {}

    if (mounted) {
      setState(() {
        _addressText =
            'Wilayah Cicalengka, Kab. Bandung (GPS: ${lat.toStringAsFixed(5)}, ${lng.toStringAsFixed(5)})';
        _isGeocoding = false;
      });
    }
  }

  Future<void> _goToCurrentGpsLocation() async {
    setState(() => _isLocatingGps = true);
    try {
      final pos = await LocationService.getCurrentPosition();
      if (mounted) {
        final newLat = pos.latitude;
        final newLng = pos.longitude;
        setState(() {
          _pickedLat = newLat;
          _pickedLng = newLng;
          _isLocatingGps = false;
        });
        _mapController.move(LatLng(newLat, newLng), 16.5);
        _reverseGeocode(newLat, newLng);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _isLocatingGps = false);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Gagal mendapatkan lokasi GPS terkini.'),
            backgroundColor: AppTheme.primaryRed,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final mediaQuery = MediaQuery.of(context);
    final modalHeight = mediaQuery.size.height * 0.85;

    return Container(
      height: modalHeight,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        children: [
          // Header Bar
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: const [
                    Icon(Icons.map_rounded, color: AppTheme.primaryRed, size: 20),
                    SizedBox(width: 8),
                    Text(
                      'Pilih Titik Lokasi di Peta',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                  ],
                ),
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF64748B)),
                ),
              ],
            ),
          ),

          const Divider(height: 1, color: Color(0xFFE2E8F0)),

          // Map View with Floating Marker & Map Controls
          Expanded(
            child: Stack(
              children: [
                FlutterMap(
                  mapController: _mapController,
                  options: MapOptions(
                    initialCenter: LatLng(_pickedLat, _pickedLng),
                    initialZoom: 16.0,
                    onPositionChanged: (position, hasGesture) {
                      if (hasGesture && position.center != null) {
                        final center = position.center!;
                        _pickedLat = center.latitude;
                        _pickedLng = center.longitude;
                      }
                    },
                    onMapEvent: (event) {
                      if (event is MapEventMoveEnd) {
                        _reverseGeocode(_pickedLat, _pickedLng);
                      }
                    },
                  ),
                  children: [
                    TileLayer(
                      urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                      userAgentPackageName: 'com.cicalengkago.mobile',
                      errorTileCallback: (tile, error, stackTrace) {
                        // Silent handling for aborted/cancelled tile requests
                      },
                      evictErrorTileStrategy: EvictErrorTileStrategy.none,
                    ),
                    ZoneConstants.buildZonePolygonLayer(
                      label: 'Batas Zona Cicalengka',
                      fillColor: const Color(0x183B82F6),
                      borderColor: const Color(0xFF2563EB),
                      strokeWidth: 2.0,
                    ),
                  ],
                ),

                // Center Fixed Pin Indicator
                Center(
                  child: Padding(
                    padding: const EdgeInsets.only(bottom: 36),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                          decoration: BoxDecoration(
                            color: const Color(0xFF0F172A),
                            borderRadius: BorderRadius.circular(20),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.2),
                                blurRadius: 6,
                              ),
                            ],
                          ),
                          child: const Text(
                            'Geser Peta ke Lokasi Anda',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        const SizedBox(height: 4),
                        Container(
                          width: 52,
                          height: 52,
                          decoration: const BoxDecoration(
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(color: Color(0x44DC2626), blurRadius: 10, spreadRadius: 2),
                            ],
                          ),
                          child: ClipOval(
                            child: Image.asset(
                              'assets/images/customer_home_marker.png',
                              fit: BoxFit.cover,
                              errorBuilder: (context, error, stackTrace) => const Icon(
                                Icons.location_on_rounded,
                                color: AppTheme.primaryRed,
                                size: 44,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                // GPS My Location Floating Button
                Positioned(
                  bottom: 16,
                  right: 16,
                  child: FloatingActionButton.small(
                    heroTag: 'my_location_btn',
                    backgroundColor: Colors.white,
                    foregroundColor: AppTheme.primaryRed,
                    elevation: 4,
                    onPressed: _isLocatingGps ? null : _goToCurrentGpsLocation,
                    child: _isLocatingGps
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.primaryRed),
                          )
                        : const Icon(Icons.my_location_rounded, size: 20),
                  ),
                ),
              ],
            ),
          ),

          // Address Card & Confirmation Button
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.08),
                  blurRadius: 10,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(6),
                      decoration: const BoxDecoration(
                        color: Color(0xFFFEE2E2),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.pin_drop_rounded,
                        color: AppTheme.primaryRed,
                        size: 16,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Lokasi Pengantaran Terpilih:',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF64748B),
                            ),
                          ),
                          const SizedBox(height: 2),
                          _isGeocoding
                              ? Row(
                                  children: const [
                                    SizedBox(
                                      width: 12,
                                      height: 12,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 1.5,
                                        color: AppTheme.primaryRed,
                                      ),
                                    ),
                                    SizedBox(width: 6),
                                    Text(
                                      'Mendapatkan alamat lokasi...',
                                      style: TextStyle(
                                        fontSize: 11,
                                        color: Color(0xFF94A3B8),
                                      ),
                                    ),
                                  ],
                                )
                              : Text(
                                  _addressText,
                                  style: const TextStyle(
                                    fontSize: 12.5,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFF0F172A),
                                  ),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () {
                      Navigator.pop(context, {
                        'lat': _pickedLat,
                        'lng': _pickedLng,
                        'address': _addressText,
                      });
                    },
                    icon: const Icon(Icons.check_circle_rounded, size: 18),
                    label: const Text(
                      'Gunakan Lokasi Ini',
                      style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primaryRed,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      elevation: 0,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
