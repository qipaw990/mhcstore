import 'dart:async';
import 'package:flutter/material.dart';
import 'package:latlong2/latlong.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/constants/zone_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/services/location_service.dart';
import '../../../main.dart';
import '../widgets/driver_transaction_alert.dart';

class DriverController extends ChangeNotifier {
  bool _isOnline = false;
  bool _isLoading = false;
  LatLng _currentLocation = LocationService.defaultPosition;
  List<dynamic> _availableOrders = [];
  Map<String, dynamic>? _activeTrip;
  Map<String, dynamic>? _earnings;
  Map<String, dynamic>? _driverProfile;
  Map<String, dynamic>? _zoneConfig;
  List<dynamic> _reviews = [];
  List<dynamic> _orderHistory = [];
  bool _isLoadingHistory = false;
  int _historyDeliveredCount = 0;
  int _historyCanceledCount = 0;
  double _historyTotalEarnings = 0.0;
  double _historyTotalKm = 0.0;
  Map<String, dynamic>? _selectedOrderDetail;
  bool _isLoadingOrderDetail = false;
  Timer? _gpsBroadcastTimer;
  Timer? _radarPollTimer;

  // Transaction tracking for automatic alerts
  double _lastKnownBalance = -1.0;
  final Set<String> _knownTxIds = {};

  double _heading = 0.0;

  bool get isOnline => _isOnline;
  bool get isLoading => _isLoading;
  LatLng get currentLocation => _currentLocation;
  double get heading => _heading;
  List<dynamic> get availableOrders => _availableOrders;
  Map<String, dynamic>? get activeTrip => _activeTrip;
  Map<String, dynamic>? get earnings => _earnings;
  Map<String, dynamic>? get driverProfile => _driverProfile;
  List<dynamic> get reviews => _reviews;
  List<dynamic> get orderHistory => _orderHistory;
  bool get isLoadingHistory => _isLoadingHistory;
  int get historyDeliveredCount => _historyDeliveredCount;
  int get historyCanceledCount => _historyCanceledCount;
  double get historyTotalEarnings => _historyTotalEarnings;
  double get historyTotalKm => _historyTotalKm;
  Map<String, dynamic>? get selectedOrderDetail => _selectedOrderDetail;
  bool get isLoadingOrderDetail => _isLoadingOrderDetail;
  List<dynamic> get deliveredOrders => (_earnings?['delivered_orders'] is List) ? (_earnings!['delivered_orders'] as List) : [];

  // Derived stats from earnings & dashboard
  double get walletBalance {
    final b1 = double.tryParse(_earnings?['wallet_balance']?.toString() ?? '');
    final b2 = double.tryParse(_earnings?['wallet']?['balance']?.toString() ?? '');
    return b1 ?? b2 ?? 0.0;
  }

  int get totalOrders {
    final t1 = int.tryParse(_earnings?['total_orders']?.toString() ?? '');
    final t2 = int.tryParse(_earnings?['wallet']?['total_orders']?.toString() ?? '');
    final t3 = int.tryParse(_earnings?['driver']?['total_orders']?.toString() ?? '');
    final t4 = int.tryParse(_driverProfile?['total_orders']?.toString() ?? '');
    final t5 = int.tryParse(_driverProfile?['driver']?['total_orders']?.toString() ?? '');
    return t1 ?? t2 ?? t3 ?? t4 ?? t5 ?? 0;
  }

  double get driverRating {
    final rc = int.tryParse(_earnings?['reviews_count']?.toString() ?? _earnings?['wallet']?['reviews_count']?.toString() ?? '0') ?? 0;
    if (rc == 0 && _reviews.isEmpty) return 5.0;
    final r1 = double.tryParse(_earnings?['rating']?.toString() ?? '');
    final r2 = double.tryParse(_earnings?['wallet']?['rating']?.toString() ?? '');
    final r3 = double.tryParse(_driverProfile?['rating']?.toString() ?? '');
    return r1 ?? r2 ?? r3 ?? 5.0;
  }

  double get rating => driverRating;

  Map<String, dynamic>? get zoneConfig => _zoneConfig;
  double get zoneMinDeliveryCharge =>
      double.tryParse(_zoneConfig?['min_delivery_charge']?.toString() ?? '5000') ?? 5000.0;
  double get zonePerKmDeliveryCharge =>
      double.tryParse(_zoneConfig?['per_km_delivery_charge']?.toString() ?? '2500') ?? 2500.0;
  String get zoneName =>
      _zoneConfig?['name']?.toString() ?? 'Zona Cicalengka Raya';

  List<LatLng> get zonePolygon {
    final rawList = _zoneConfig?['polygon_coordinates'];
    if (rawList is List && rawList.length >= 3) {
      final List<LatLng> parsed = [];
      for (final item in rawList) {
        if (item is List && item.length >= 2) {
          final lat = double.tryParse(item[0].toString());
          final lng = double.tryParse(item[1].toString());
          if (lat != null && lng != null) {
            parsed.add(LatLng(lat, lng));
          }
        } else if (item is Map) {
          final lat = double.tryParse((item['lat'] ?? item['latitude'])?.toString() ?? '');
          final lng = double.tryParse((item['lng'] ?? item['longitude'])?.toString() ?? '');
          if (lat != null && lng != null) {
            parsed.add(LatLng(lat, lng));
          }
        }
      }
      if (parsed.length >= 3) return parsed;
    }
    return ZoneConstants.cicalengkaZonePolygon;
  }

  Future<void> fetchZoneConfig({int zoneId = 1}) async {
    try {
      final res = await ApiService.get('${ApiConstants.zoneConfig}?zone_id=$zoneId');
      if (res['success'] == true && res['data'] is Map) {
        _zoneConfig = Map<String, dynamic>.from(res['data'] as Map);
        notifyListeners();
      }
    } catch (_) {}
  }

  void toggleOnline(bool status) {
    _isOnline = status;
    if (_isOnline) {
      startGpsBroadcaster();
      startRadarPolling();
      fetchRadarData();
    } else {
      stopGpsBroadcaster();
      stopRadarPolling();
    }
    _syncOnlineStatus(status);
    notifyListeners();
  }

  Future<void> _syncOnlineStatus(bool status) async {
    try {
      await ApiService.postForm(ApiConstants.driverToggleOnline, {
        'online': status ? '1' : '0',
      });
    } catch (_) {}
  }

  DriverController() {
    _initGpsTracking();
  }

  StreamSubscription<dynamic>? _positionStreamSub;
  LatLng? _previousLocation;

  void _initGpsTracking() {
    refreshLocation();
    try {
      _positionStreamSub?.cancel();
      _positionStreamSub = LocationService.getPositionStream().listen((position) {
        final newLoc = LatLng(position.latitude, position.longitude);
        if (position.heading > 0) {
          _heading = position.heading;
        } else if (_previousLocation != null &&
            (_previousLocation!.latitude != newLoc.latitude || _previousLocation!.longitude != newLoc.longitude)) {
          final computed = LocationService.calculateBearing(_previousLocation!, newLoc);
          if (computed > 0) _heading = computed;
        }
        _previousLocation = _currentLocation;
        _currentLocation = newLoc;
        notifyListeners();
        if (_isOnline) {
          _broadcastLocation(_currentLocation);
        }
      });
    } catch (_) {}
  }

  Future<void> refreshLocation() async {
    try {
      final pos = await LocationService.getCurrentPosition();
      _currentLocation = pos;
      notifyListeners();
      if (_isOnline) {
        await _broadcastLocation(pos);
      }
    } catch (_) {}
  }

  Future<void> _broadcastLocation(LatLng pos) async {
    try {
      await ApiService.postForm(ApiConstants.updateDriverLocation, {
        'lat': pos.latitude.toString(),
        'lng': pos.longitude.toString(),
      });
    } catch (_) {}
  }

  void startGpsBroadcaster() {
    // 1. Broadcast immediately on start
    refreshLocation();

    // 2. Ensure stream is listening
    if (_positionStreamSub == null) {
      _initGpsTracking();
    }

    // 3. Fallback Periodic heartbeat every 8 seconds
    _gpsBroadcastTimer?.cancel();
    _gpsBroadcastTimer = Timer.periodic(const Duration(seconds: 8), (_) async {
      if (_isOnline) {
        await refreshLocation();
      }
    });
  }

  void stopGpsBroadcaster() {
    _gpsBroadcastTimer?.cancel();
    _gpsBroadcastTimer = null;
  }

  void startRadarPolling() {
    _radarPollTimer?.cancel();
    _radarPollTimer = Timer.periodic(const Duration(seconds: 3), (_) async {
      await fetchRadarData(silent: true);
    });
  }

  void stopRadarPolling() {
    _radarPollTimer?.cancel();
    _radarPollTimer = null;
  }

  Future<void> fetchRadarData({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      notifyListeners();
    }

    try {
      debugPrint('[DriverController] fetchRadarData: Requesting ${ApiConstants.driverDashboard}...');
      final res = await ApiService.get(ApiConstants.driverDashboard);
      debugPrint('[DriverController] fetchRadarData: Server Response: $res');

      if (res['success'] == true && res['data'] != null) {
        final data = res['data'] as Map<String, dynamic>;
        final activeData = data['active_trip'] ?? data['active_order'];
        if (activeData is Map<String, dynamic>) {
          _activeTrip = activeData;
          debugPrint('[DriverController] Active Trip DETECTED: ${_activeTrip?['order_code']} (Status: ${_activeTrip?['order_status']})');
        } else {
          _activeTrip = null;
          debugPrint('[DriverController] No Active Trip.');
        }

        // Available orders for grabs
        final availList = (data['available_orders'] as List<dynamic>?) ?? [];
        _availableOrders = availList;
        debugPrint('[DriverController] Available Orders Count: ${_availableOrders.length}');

        if (data['driver'] != null) {
          _driverProfile = data['driver'] as Map<String, dynamic>;
        }

        if (data['wallet'] != null) {
          if (_earnings != null) {
            final updated = Map<String, dynamic>.from(_earnings!);
            updated['wallet'] = data['wallet'];
            if (data['driver'] != null) {
              updated['driver'] = data['driver'];
            }
            _earnings = updated;
          } else {
            fetchEarnings(silent: true);
          }
        } else if (data['wallet_balance'] != null) {
          if (_earnings != null && _earnings!['wallet'] is Map) {
            final updated = Map<String, dynamic>.from(_earnings!);
            final wMap = Map<String, dynamic>.from(updated['wallet'] as Map);
            wMap['balance'] = data['wallet_balance'];
            if (data['total_orders'] != null) {
              wMap['total_orders'] = data['total_orders'];
            }
            updated['wallet'] = wMap;
            _earnings = updated;
          } else {
            fetchEarnings(silent: true);
          }
        }

        if (data['reviews'] != null && data['reviews'] is List) {
          _reviews = data['reviews'] as List<dynamic>;
        }

        // Sync online status from server
        final serverOnline = data['is_online'] ?? data['driver']?['is_online'];
        if (serverOnline != null) {
          _isOnline = serverOnline == true || serverOnline == 1 || serverOnline == '1';
          debugPrint('[DriverController] Online status synced: $_isOnline');
        }

        // Auto-start polling if online and timer not active
        if (_isOnline && (_radarPollTimer == null || !_radarPollTimer!.isActive)) {
          startRadarPolling();
          startGpsBroadcaster();
        }
      } else {
        debugPrint('[DriverController] fetchRadarData failed: ${res['message']}');
      }
    } catch (e) {
      debugPrint('[DriverController] fetchRadarData Exception: $e');
    }

    if (!silent) {
      _isLoading = false;
    }
    notifyListeners();
  }

  Future<void> fetchEarnings({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      notifyListeners();
    }

    try {
      final res = await ApiService.get(ApiConstants.driverEarnings);
      if (res['success'] == true && res['data'] != null) {
        _earnings = Map<String, dynamic>.from(res['data'] as Map<String, dynamic>);
        if (_earnings?['reviews'] != null && _earnings!['reviews'] is List) {
          _reviews = _earnings!['reviews'] as List<dynamic>;
        }

        // Check for new transactions and trigger transaction alerts
        _checkTransactionsList(_earnings?['transactions']);
        _checkBalanceAlert(walletBalance);
      }
    } catch (_) {}

    if (!silent) {
      _isLoading = false;
    }
    notifyListeners();
  }

  void _checkBalanceAlert(double newBalance) {
    if (_lastKnownBalance >= 0 && newBalance > _lastKnownBalance) {
      final diff = newBalance - _lastKnownBalance;
      final ctx = rootNavigatorKey.currentContext;
      if (ctx != null && ctx.mounted) {
        DriverTransactionAlert.showFloatingBanner(
          ctx,
          title: 'Komisi Masuk! 💰',
          message: 'Saldo dompet driver Anda telah bertambah.',
          amount: diff,
          type: 'credit',
        );
      }
    }
    _lastKnownBalance = newBalance;
  }

  void _checkTransactionsList(dynamic transactions) {
    if (transactions is! List) return;
    if (_knownTxIds.isEmpty) {
      for (final tx in transactions) {
        if (tx is Map && tx['id'] != null) {
          _knownTxIds.add(tx['id'].toString());
        }
      }
      return;
    }

    for (final tx in transactions) {
      if (tx is Map && tx['id'] != null) {
        final txId = tx['id'].toString();
        if (!_knownTxIds.contains(txId)) {
          _knownTxIds.add(txId);
          final ctx = rootNavigatorKey.currentContext;
          if (ctx != null && ctx.mounted) {
            final amt = double.tryParse(tx['amount']?.toString() ?? '0') ?? 0.0;
            final type = tx['type']?.toString() ?? 'credit';
            final desc = tx['description']?.toString() ?? 'Mutasi Dompet';
            final title = type == 'credit' ? 'Saldo Masuk 💰' : 'Penarikan Saldo 📤';
            DriverTransactionAlert.showFloatingBanner(
              ctx,
              title: title,
              message: desc,
              amount: amt,
              type: type,
            );
          }
        }
      }
    }
  }

  Future<void> fetchProfile() async {
    try {
      final res = await ApiService.get(ApiConstants.driverProfile);
      if (res['success'] == true && res['data'] != null) {
        _driverProfile = res['data'] as Map<String, dynamic>;
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<void> fetchOrderHistory({String status = 'all', bool silent = false}) async {
    if (!silent) {
      _isLoadingHistory = true;
      notifyListeners();
    }
    try {
      final uri = '${ApiConstants.driverOrdersHistory}?status=$status';
      final res = await ApiService.get(uri);
      if (res['success'] == true && res['data'] != null) {
        final d = res['data'] as Map<String, dynamic>;
        _orderHistory = (d['orders'] is List) ? (d['orders'] as List) : [];
        _historyDeliveredCount = int.tryParse(d['total_delivered']?.toString() ?? '0') ?? 0;
        _historyCanceledCount = int.tryParse(d['total_canceled']?.toString() ?? '0') ?? 0;
        _historyTotalEarnings = double.tryParse(d['total_earnings']?.toString() ?? '0') ?? 0.0;
        _historyTotalKm = double.tryParse(d['total_km']?.toString() ?? '0') ?? 0.0;
      }
    } catch (e) {
      debugPrint('[DriverController] fetchOrderHistory Error: $e');
    } finally {
      _isLoadingHistory = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>?> fetchOrderDetail(dynamic orderIdOrCode) async {
    _isLoadingOrderDetail = true;
    _selectedOrderDetail = null;
    notifyListeners();

    try {
      final uri = '${ApiConstants.driverOrderDetail}?id=$orderIdOrCode&order_code=$orderIdOrCode';
      final res = await ApiService.get(uri);
      if (res['success'] == true && res['data'] != null && res['data']['order'] != null) {
        _selectedOrderDetail = Map<String, dynamic>.from(res['data']['order'] as Map);
        return _selectedOrderDetail;
      }
    } catch (e) {
      debugPrint('[DriverController] fetchOrderDetail Error: $e');
    } finally {
      _isLoadingOrderDetail = false;
      notifyListeners();
    }
    return null;
  }

  String? _lastErrorMessage;
  String? get lastErrorMessage => _lastErrorMessage;

  Future<bool> acceptOrder(int orderId) async {
    _isLoading = true;
    _lastErrorMessage = null;
    notifyListeners();

    try {
      final res = await ApiService.postForm(ApiConstants.acceptOrder, {
        'order_id': orderId.toString(),
      });

      _isLoading = false;
      if (res['success'] == true) {
        if (res['data'] != null && (res['data']['active_trip'] != null || res['data']['active_order'] != null)) {
          final tripMap = res['data']['active_trip'] ?? res['data']['active_order'];
          if (tripMap is Map<String, dynamic>) {
            _activeTrip = tripMap;
            debugPrint('[DriverController] acceptOrder: activeTrip set to ${_activeTrip?['order_code']}');
          }
        }
        await fetchRadarData();
        notifyListeners();
        return true;
      } else {
        _lastErrorMessage = res['message']?.toString();
      }
    } catch (e) {
      _isLoading = false;
      _lastErrorMessage = 'Koneksi terganggu. Silakan coba lagi.';
      debugPrint('[DriverController] acceptOrder Exception: $e');
    }

    notifyListeners();
    return false;
  }

  Future<bool> updateTripStatus(int orderId, String status, {String? otpCode}) async {
    _lastErrorMessage = null;
    try {
      final fields = <String, String>{
        'order_id': orderId.toString(),
        'status': status,
      };
      if (otpCode != null) fields['otp'] = otpCode;

      final res = await ApiService.postForm(ApiConstants.updateOrderStatus, fields);
      if (res['success'] == true) {
        if (res['data'] is Map<String, dynamic>) {
          final d = res['data'] as Map<String, dynamic>;
          if (d['wallet'] != null) {
            _earnings ??= {};
            _earnings!['wallet'] = d['wallet'];
            _earnings!['wallet_balance'] = d['wallet_balance'];
          }
        }
        await fetchRadarData(silent: true);
        await fetchEarnings(silent: true);
        notifyListeners();
        return true;
      } else {
        _lastErrorMessage = res['message']?.toString() ?? 'Gagal memperbarui status pengantaran.';
      }
    } catch (e) {
      _lastErrorMessage = 'Gagal menghubungi server. Periksa koneksi internet Anda.';
    }
    notifyListeners();
    return false;
  }

  Future<bool> submitWithdraw({
    required String bankName,
    required String accountNumber,
    required String accountHolder,
    required double amount,
  }) async {
    _lastErrorMessage = null;
    try {
      final res = await ApiService.postForm(ApiConstants.driverWithdraw, {
        'bank_name': bankName,
        'account_number': accountNumber,
        'account_holder': accountHolder,
        'amount': amount.toString(),
      });

      if (res['success'] == true) {
        final ctx = rootNavigatorKey.currentContext;
        if (ctx != null && ctx.mounted) {
          try {
            DriverTransactionAlert.showFloatingBanner(
              ctx,
              title: 'Penarikan Diajukan 📤',
              message: 'Permintaan tarik saldo ke $bankName ($accountNumber) berhasil dikirim.',
              amount: amount,
              type: 'debit',
            );
          } catch (_) {}
        }
        await fetchEarnings(silent: true);
        notifyListeners();
        return true;
      } else {
        _lastErrorMessage = res['message']?.toString() ?? 'Gagal mengajukan penarikan.';
      }
    } catch (e) {
      _lastErrorMessage = 'Gagal menghubungi server. Periksa koneksi internet Anda.';
      debugPrint('[DriverController] submitWithdraw Exception: $e');
    }
    notifyListeners();
    return false;
  }

  Future<bool> updateProfile({
    required String name,
    required String email,
    required String phone,
    required String vehicleType,
    required String vehicleNumber,
    String? currentPassword,
    String? newPassword,
    String? confirmPassword,
  }) async {
    _lastErrorMessage = null;
    try {
      final fields = <String, String>{
        'name': name,
        'email': email,
        'phone': phone,
        'vehicle_type': vehicleType,
        'vehicle_number': vehicleNumber,
      };
      if (currentPassword != null && currentPassword.isNotEmpty) {
        fields['current_password'] = currentPassword;
      }
      if (newPassword != null && newPassword.isNotEmpty) {
        fields['new_password'] = newPassword;
      }
      if (confirmPassword != null && confirmPassword.isNotEmpty) {
        fields['confirm_password'] = confirmPassword;
      }

      final res = await ApiService.postForm(ApiConstants.driverUpdateProfile, fields);
      if (res['success'] == true) {
        _lastErrorMessage = null;
        await fetchProfile();
        return true;
      } else {
        _lastErrorMessage = res['message']?.toString() ?? 'Gagal memperbarui kata sandi.';
      }
    } catch (e) {
      _lastErrorMessage = 'Terjadi kesalahan koneksi. Silakan coba lagi.';
    }
    return false;
  }

  @override
  void dispose() {
    stopGpsBroadcaster();
    stopRadarPolling();
    super.dispose();
  }
}
