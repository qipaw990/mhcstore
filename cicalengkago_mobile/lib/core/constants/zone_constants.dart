import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

class ZoneConstants {
  static const String defaultZoneName = 'Zona Cicalengka Raya';
  static const double defaultMinFee = 5000.0;
  static const double defaultPerKmFee = 2500.0;

  /// Exact Server Polygon boundary for Zona Cicalengka Raya (from database `zones.coordinates_json`)
  static const List<LatLng> cicalengkaZonePolygon = [
    LatLng(-6.955741, 107.827375),
    LatLng(-6.961527, 107.860682),
    LatLng(-7.023885, 107.901530),
    LatLng(-7.030012, 107.797852),
    LatLng(-6.972775, 107.754607),
    LatLng(-6.955071, 107.804043),
  ];

  /// Returns a PolygonLayer widget ready to be included in any FlutterMap
  static PolygonLayer buildZonePolygonLayer({
    List<LatLng>? customCoords,
    String? label,
    Color fillColor = const Color(0x223B82F6),
    Color borderColor = const Color(0xFF2563EB),
    double strokeWidth = 2.0,
    bool isDotted = true,
  }) {
    return PolygonLayer(
      polygons: [
        Polygon(
          points: customCoords ?? cicalengkaZonePolygon,
          color: fillColor,
          borderColor: borderColor,
          borderStrokeWidth: strokeWidth,
          isDotted: isDotted,
          label: label ?? defaultZoneName,
          labelStyle: const TextStyle(
            fontSize: 10,
            fontWeight: FontWeight.bold,
            color: Color(0xFF1D4ED8),
          ),
        ),
      ],
    );
  }
}
