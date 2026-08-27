import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';

class LocationService {
  /// Default center position (Cicalengka, Kab. Bandung)
  static const LatLng defaultPosition = LatLng(-6.9835, 107.8335);

  /// 2-Stage Progressive Location Fetcher:
  /// Stage 1: High Accuracy GPS (Satellites) with 7s timeout
  /// Stage 2: Low Accuracy Network Triangulation (Cell/Wi-Fi) fallback
  static Future<LatLng> getCurrentPosition() async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      return defaultPosition;
    }

    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        return defaultPosition;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      return defaultPosition;
    }

    // Stage 1: Try High Accuracy GPS
    try {
      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: const Duration(seconds: 7),
      );
      return LatLng(position.latitude, position.longitude);
    } catch (_) {
      // Stage 2 Fallback: Low Accuracy (Network/Wi-Fi)
      try {
        Position position = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.low,
          timeLimit: const Duration(seconds: 8),
        );
        return LatLng(position.latitude, position.longitude);
      } catch (__) {
        return defaultPosition;
      }
    }
  }
}
