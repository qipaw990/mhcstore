import 'dart:convert';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';
import '../constants/api_constants.dart';
import '../network/api_service.dart';
import '../constants/zone_constants.dart';
import 'app_config_service.dart';

/// Result returned after a zone-check
class ZoneCheckResult {
  final bool isInsideZone;
  final String? zoneName;
  final String? errorMessage;
  final List<LatLng> polygon;

  const ZoneCheckResult({
    required this.isInsideZone,
    this.zoneName,
    this.errorMessage,
    this.polygon = const [],
  });
}

/// Service responsible for:
/// 1. Fetching all active zones from the backend / AppConfigService.
/// 2. Getting the user's current GPS position.
/// 3. Performing point-in-polygon checks against every active zone.
class ZoneGuardService {
  /// Fetch all active zones and check if [position] is in any of them.
  static Future<ZoneCheckResult> checkUserInZone(Position position) async {
    final userPoint = LatLng(position.latitude, position.longitude);

    // 1. Cek dari AppConfigService yang sudah ter-sinkron dari database
    final cachedZones = AppConfigService.instance.zones;
    if (cachedZones.isNotEmpty) {
      for (final zone in cachedZones) {
        if (zone.polygon.isNotEmpty && _isPointInPolygon(userPoint, zone.polygon)) {
          return ZoneCheckResult(
            isInsideZone: true,
            zoneName: zone.name,
            polygon: zone.polygon,
          );
        }
      }
      return ZoneCheckResult(
        isInsideZone: false,
        errorMessage: 'Lokasi Anda berada di luar area layanan ${AppConfigService.instance.appName}.',
      );
    }

    List<Map<String, dynamic>> zones = [];
    bool apiFailed = false;

    try {
      // Fetch all active zones jika config belum ada
      final res = await ApiService.get(ApiConstants.zoneConfig)
          .timeout(const Duration(seconds: 8));

      // API may return a single zone or a list
      if (res['success'] == true) {
        final data = res['data'];
        if (data is List) {
          zones = data.map((e) => Map<String, dynamic>.from(e as Map)).toList();
        } else if (data is Map) {
          zones = [Map<String, dynamic>.from(data)];
        }
      }
    } catch (_) {
      apiFailed = true;
    }

    // If API returned at least one zone, test them
    if (zones.isNotEmpty) {
      for (final zone in zones) {
        final rawCoords = zone['polygon_coordinates'] ?? zone['coordinates_json'];
        final polygon = _parseCoordinates(rawCoords);
        if (polygon.isNotEmpty && _isPointInPolygon(userPoint, polygon)) {
          return ZoneCheckResult(
            isInsideZone: true,
            zoneName: zone['name']?.toString() ?? ZoneConstants.defaultZoneName,
            polygon: polygon,
          );
        }
      }
      // User not in any zone
      return ZoneCheckResult(
        isInsideZone: false,
        errorMessage: 'Lokasi Anda berada di luar area layanan ${AppConfigService.instance.appName}.',
      );
    }

    // ── Fallback: Use hardcoded polygon if API unreachable ──────────────────
    if (apiFailed) {
      final fallbackPolygon = ZoneConstants.cicalengkaZonePolygon;
      if (_isPointInPolygon(userPoint, fallbackPolygon)) {
        return ZoneCheckResult(
          isInsideZone: true,
          zoneName: ZoneConstants.defaultZoneName,
          polygon: fallbackPolygon,
        );
      }
      return const ZoneCheckResult(
        isInsideZone: false,
        errorMessage: 'Lokasi Anda berada di luar area layanan CicalengkaGO.',
      );
    }

    // API returned no zones configured — allow access (fail open)
    return const ZoneCheckResult(isInsideZone: true, zoneName: 'Semua Area');
  }

  // ── Helpers ───────────────────────────────────────────────────────────────

  /// Parse coordinates from either a JSON string or a pre-decoded list.
  static List<LatLng> _parseCoordinates(dynamic raw) {
    try {
      List<dynamic> list;
      if (raw is String) {
        list = json.decode(raw) as List<dynamic>;
      } else if (raw is List) {
        list = raw;
      } else {
        return [];
      }
      return list.map<LatLng>((e) {
        final map = e as Map;
        final lat = double.tryParse(map['lat']?.toString() ?? '') ?? 0.0;
        final lng = double.tryParse(map['lng']?.toString() ?? '') ?? 0.0;
        return LatLng(lat, lng);
      }).toList();
    } catch (_) {
      return [];
    }
  }

  /// Ray-casting point-in-polygon algorithm.
  /// Returns true if [point] is inside [polygon].
  static bool _isPointInPolygon(LatLng point, List<LatLng> polygon) {
    if (polygon.length < 3) return false;

    final double px = point.longitude;
    final double py = point.latitude;
    bool inside = false;

    int j = polygon.length - 1;
    for (int i = 0; i < polygon.length; i++) {
      final double xi = polygon[i].longitude;
      final double yi = polygon[i].latitude;
      final double xj = polygon[j].longitude;
      final double yj = polygon[j].latitude;

      final bool intersect =
          ((yi > py) != (yj > py)) &&
          (px < (xj - xi) * (py - yi) / (yj - yi) + xi);

      if (intersect) inside = !inside;
      j = i;
    }
    return inside;
  }
}
