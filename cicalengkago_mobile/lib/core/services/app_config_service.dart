import 'package:flutter/foundation.dart';
import '../constants/zone_constants.dart';
import '../constants/api_constants.dart';
import '../network/api_service.dart';
import 'package:latlong2/latlong.dart';

/// AppConfig — holds all dynamic config fetched from /api/app-config
class AppConfig {
  // App Info
  final String appName;
  final String tagline;
  final String version;
  final String supportPhone;
  final String supportEmail;
  final String currencySymbol;
  final String currencyCode;
  final bool maintenanceMode;

  // Location defaults (center of service area)
  final double defaultLat;
  final double defaultLng;
  final String defaultLocationName;

  // Delivery
  final double deliveryMinCharge;
  final double deliveryPerKmCharge;
  final double freeDeliveryOver;
  final double taxPercent;

  // Wallet
  final bool walletEnabled;
  final List<int> walletTopupNominals;
  final double walletTransferFee;
  final double walletMinTransferPeer;
  final double walletMinTransferBank;
  final double walletMinTopup;

  // Zones
  final List<ZoneInfo> zones;

  // Payment
  final List<Map<String, dynamic>> inhouseBanks;
  final bool dokuEnabled;

  const AppConfig({
    required this.appName,
    required this.tagline,
    required this.version,
    required this.supportPhone,
    required this.supportEmail,
    required this.currencySymbol,
    required this.currencyCode,
    required this.maintenanceMode,
    required this.defaultLat,
    required this.defaultLng,
    required this.defaultLocationName,
    required this.deliveryMinCharge,
    required this.deliveryPerKmCharge,
    required this.freeDeliveryOver,
    required this.taxPercent,
    required this.walletEnabled,
    required this.walletTopupNominals,
    required this.walletTransferFee,
    required this.walletMinTransferPeer,
    required this.walletMinTransferBank,
    required this.walletMinTopup,
    required this.zones,
    required this.inhouseBanks,
    required this.dokuEnabled,
  });

  /// Safe fallback used before API response arrives
  factory AppConfig.defaults() => AppConfig(
        appName: 'CicalengkaGO',
        tagline: 'Pesan Antar & Belanja Praktis',
        version: '1.0.0',
        supportPhone: '',
        supportEmail: '',
        currencySymbol: 'Rp',
        currencyCode: 'IDR',
        maintenanceMode: false,
        defaultLat: ZoneConstants.fallbackLat,
        defaultLng: ZoneConstants.fallbackLng,
        defaultLocationName: 'Cicalengka, Bandung',
        deliveryMinCharge: 5000,
        deliveryPerKmCharge: 2500,
        freeDeliveryOver: 100000,
        taxPercent: 0,
        walletEnabled: false,
        walletTopupNominals: const [10000, 20000, 50000, 100000, 200000, 500000],
        walletTransferFee: 1500,
        walletMinTransferPeer: 1000,
        walletMinTransferBank: 10000,
        walletMinTopup: 10000,
        zones: const [],
        inhouseBanks: const [],
        dokuEnabled: false,
      );

  factory AppConfig.fromJson(Map<String, dynamic> json) {
    final app      = json['app']      as Map<String, dynamic>? ?? {};
    final location = json['location'] as Map<String, dynamic>? ?? {};
    final delivery = json['delivery'] as Map<String, dynamic>? ?? {};
    final wallet   = json['wallet']   as Map<String, dynamic>? ?? {};
    final payment  = json['payment']  as Map<String, dynamic>? ?? {};
    final zoneList = json['zones']    as List<dynamic>? ?? [];

    final nominals = (wallet['topup_nominals'] as List<dynamic>?)
            ?.map((e) => (e as num).toInt())
            .toList() ??
        [10000, 20000, 50000, 100000, 200000, 500000];

    final banks = (payment['inhouse_banks'] as List<dynamic>?)
            ?.map((e) => Map<String, dynamic>.from(e as Map))
            .toList() ??
        [];

    return AppConfig(
      appName:              app['name']?.toString()            ?? 'CicalengkaGO',
      tagline:              app['tagline']?.toString()         ?? 'Pesan Antar & Belanja Praktis',
      version:              app['version']?.toString()         ?? '1.0.0',
      supportPhone:         app['support_phone']?.toString()   ?? '',
      supportEmail:         app['support_email']?.toString()   ?? '',
      currencySymbol:       app['currency_symbol']?.toString() ?? 'Rp',
      currencyCode:         app['currency_code']?.toString()   ?? 'IDR',
      maintenanceMode:      app['maintenance_mode'] == true,
      defaultLat:           _d(location['default_lat'],  ZoneConstants.fallbackLat),
      defaultLng:           _d(location['default_lng'],  ZoneConstants.fallbackLng),
      defaultLocationName:  location['default_name']?.toString() ?? 'Cicalengka, Bandung',
      deliveryMinCharge:    _d(delivery['min_charge'],         5000),
      deliveryPerKmCharge:  _d(delivery['per_km_charge'],      2500),
      freeDeliveryOver:     _d(delivery['free_delivery_over'], 100000),
      taxPercent:           _d(delivery['tax_percent'],        0),
      walletEnabled:        wallet['enabled'] == true,
      walletTopupNominals:  nominals,
      walletTransferFee:    _d(wallet['transfer_fee'],         1500),
      walletMinTransferPeer:_d(wallet['min_transfer_peer'],    1000),
      walletMinTransferBank:_d(wallet['min_transfer_bank'],    10000),
      walletMinTopup:       _d(wallet['min_topup'],            10000),
      zones:                zoneList.map(ZoneInfo.fromJson).toList(),
      inhouseBanks:         banks,
      dokuEnabled:          payment['doku_enabled'] == true,
    );
  }

  static double _d(dynamic v, double fallback) =>
      v == null ? fallback : double.tryParse(v.toString()) ?? fallback;
}

/// Lightweight zone info from API
class ZoneInfo {
  final int id;
  final String name;
  final List<LatLng> polygon;
  final double minDeliveryCharge;
  final double perKmDeliveryCharge;
  final double centerLat;
  final double centerLng;

  const ZoneInfo({
    required this.id,
    required this.name,
    required this.polygon,
    required this.minDeliveryCharge,
    required this.perKmDeliveryCharge,
    required this.centerLat,
    required this.centerLng,
  });

  factory ZoneInfo.fromJson(dynamic raw) {
    final m = Map<String, dynamic>.from(raw as Map);
    final coordList = m['polygon_coordinates'] as List<dynamic>? ?? [];
    final polygon = coordList.map<LatLng>((e) {
      final coord = e as Map;
      return LatLng(
        double.tryParse(coord['lat']?.toString() ?? '0') ?? 0,
        double.tryParse(coord['lng']?.toString() ?? '0') ?? 0,
      );
    }).toList();

    return ZoneInfo(
      id:                  (m['id'] as num?)?.toInt() ?? 0,
      name:                m['name']?.toString() ?? '',
      polygon:             polygon,
      minDeliveryCharge:   double.tryParse(m['min_delivery_charge']?.toString() ?? '5000') ?? 5000,
      perKmDeliveryCharge: double.tryParse(m['per_km_delivery_charge']?.toString() ?? '2500') ?? 2500,
      centerLat:           double.tryParse(m['center_latitude']?.toString() ?? '0') ?? 0,
      centerLng:           double.tryParse(m['center_longitude']?.toString() ?? '0') ?? 0,
    );
  }
}

/// Singleton service — call [init] once at app startup (SplashScreen).
/// All other widgets read via [AppConfigService.instance.config].
class AppConfigService {
  AppConfigService._();
  static final AppConfigService instance = AppConfigService._();

  AppConfig _config = AppConfig.defaults();
  bool _initialized = false;

  /// Current cached config. Falls back to defaults until [init] completes.
  AppConfig get config => _config;

  /// True once first successful fetch is done.
  bool get isInitialized => _initialized;

  // ── Convenience getters ───────────────────────────────────────────────────
  String get appName              => _config.appName;
  String get tagline              => _config.tagline;
  String get version              => _config.version;
  double get defaultLat           => _config.defaultLat;
  double get defaultLng           => _config.defaultLng;
  String get defaultLocationName  => _config.defaultLocationName;
  double get deliveryMinCharge    => _config.deliveryMinCharge;
  double get deliveryPerKmCharge  => _config.deliveryPerKmCharge;
  double get freeDeliveryOver     => _config.freeDeliveryOver;
  bool   get walletEnabled        => _config.walletEnabled;
  List<int> get walletTopupNominals    => _config.walletTopupNominals;
  double get walletTransferFee         => _config.walletTransferFee;
  double get walletMinTransferPeer     => _config.walletMinTransferPeer;
  double get walletMinTransferBank     => _config.walletMinTransferBank;
  double get walletMinTopup            => _config.walletMinTopup;
  List<ZoneInfo> get zones             => _config.zones;
  List<Map<String, dynamic>> get inhouseBanks => _config.inhouseBanks;
  String get currencySymbol            => _config.currencySymbol;
  bool   get maintenanceMode           => _config.maintenanceMode;

  /// Fetch config from backend and cache it. Safe to call multiple times —
  /// only re-fetches on cold-start (when [_initialized] is false) unless
  /// [force] is true.
  Future<void> init({bool force = false}) async {
    if (_initialized && !force) return;

    try {
      final res = await ApiService.get(ApiConstants.appConfig)
          .timeout(const Duration(seconds: 10));

      if (res['success'] == true && res['data'] is Map) {
        _config = AppConfig.fromJson(Map<String, dynamic>.from(res['data'] as Map));
        _initialized = true;
        debugPrint('[AppConfigService] Config loaded: '
            'app=${_config.appName}, zones=${_config.zones.length}');
      }
    } catch (e) {
      debugPrint('[AppConfigService] Failed to fetch config, using defaults: $e');
      // Keep defaults — do NOT set _initialized = true so next launch retries
    }
  }

  /// Force a refresh (e.g. after admin changes settings).
  Future<void> refresh() => init(force: true);
}
