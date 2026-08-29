import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:latlong2/latlong.dart';

class RouteService {
  static final Map<String, List<LatLng>> _cache = {};

  /// Fetches real road routing geometry from OSRM following actual street paths
  static Future<List<LatLng>> getRoadRoute(LatLng start, LatLng end) async {
    // Return empty or straight if start and end are identical
    if ((start.latitude - end.latitude).abs() < 0.00001 &&
        (start.longitude - end.longitude).abs() < 0.00001) {
      return [start, end];
    }

    final cacheKey = '${start.latitude.toStringAsFixed(4)},${start.longitude.toStringAsFixed(4)}-${end.latitude.toStringAsFixed(4)},${end.longitude.toStringAsFixed(4)}';
    if (_cache.containsKey(cacheKey)) {
      return _cache[cacheKey]!;
    }

    try {
      final url = Uri.parse(
        'https://router.project-osrm.org/route/v1/driving/'
        '${start.longitude},${start.latitude};${end.longitude},${end.latitude}'
        '?overview=full&geometries=geojson',
      );

      final client = http.Client();
      final response = await client.get(url).timeout(const Duration(seconds: 4));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['code'] == 'Ok' && data['routes'] is List && (data['routes'] as List).isNotEmpty) {
          final coordinates = data['routes'][0]['geometry']['coordinates'] as List;
          final List<LatLng> points = coordinates.map<LatLng>((coord) {
            return LatLng((coord[1] as num).toDouble(), (coord[0] as num).toDouble());
          }).toList();

          if (points.isNotEmpty) {
            _cache[cacheKey] = points;
            return points;
          }
        }
      }
    } catch (_) {}

    // Fallback: direct line if OSRM is unreachable
    return [start, end];
  }
}
