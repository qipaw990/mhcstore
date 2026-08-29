import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';

class LocationService {
  /// Default center position (Cicalengka, Kab. Bandung)
  static const LatLng defaultPosition = LatLng(-6.9835, 107.8335);

  /// Multi-Stage Location Fetcher:
  /// Stage 1: Quick check Last Known Position for instant response
  /// Stage 2: High Accuracy GPS (Satellites)
  /// Stage 3: Low Accuracy Network Triangulation (Cell/Wi-Fi) fallback
  static Future<LatLng> getCurrentPosition() async {
    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        final last = await Geolocator.getLastKnownPosition();
        if (last != null) return LatLng(last.latitude, last.longitude);
        return defaultPosition;
      }

      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          final last = await Geolocator.getLastKnownPosition();
          if (last != null) return LatLng(last.latitude, last.longitude);
          return defaultPosition;
        }
      }

      if (permission == LocationPermission.deniedForever) {
        final last = await Geolocator.getLastKnownPosition();
        if (last != null) return LatLng(last.latitude, last.longitude);
        return defaultPosition;
      }

      // Stage 1: Try High Accuracy GPS
      try {
        Position position = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.bestForNavigation,
          timeLimit: const Duration(seconds: 8),
        );
        return LatLng(position.latitude, position.longitude);
      } catch (_) {
        // Stage 2 Fallback: Last Known Position
        final last = await Geolocator.getLastKnownPosition();
        if (last != null) return LatLng(last.latitude, last.longitude);

        // Stage 3 Fallback: Medium / Low Accuracy
        try {
          Position position = await Geolocator.getCurrentPosition(
            desiredAccuracy: LocationAccuracy.medium,
            timeLimit: const Duration(seconds: 6),
          );
          return LatLng(position.latitude, position.longitude);
        } catch (__) {
          return defaultPosition;
        }
      }
    } catch (_) {
      return defaultPosition;
    }
  }

  /// Live continuous real-time navigation position stream (0 distance filter)
  static Stream<Position> getPositionStream() {
    LocationSettings locationSettings;
    if (defaultTargetPlatform == TargetPlatform.android) {
      locationSettings = AndroidSettings(
        accuracy: LocationAccuracy.bestForNavigation,
        distanceFilter: 0,
        intervalDuration: const Duration(milliseconds: 1000),
      );
    } else if (defaultTargetPlatform == TargetPlatform.iOS || defaultTargetPlatform == TargetPlatform.macOS) {
      locationSettings = AppleSettings(
        accuracy: LocationAccuracy.bestForNavigation,
        activityType: ActivityType.automotiveNavigation,
        distanceFilter: 0,
        pauseLocationUpdatesAutomatically: false,
      );
    } else {
      locationSettings = const LocationSettings(
        accuracy: LocationAccuracy.bestForNavigation,
        distanceFilter: 0,
      );
    }
    return Geolocator.getPositionStream(locationSettings: locationSettings);
  }
}
