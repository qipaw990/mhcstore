import 'dart:async';
import 'package:flutter/material.dart';
import 'package:latlong2/latlong.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/services/location_service.dart';

class DriverController extends ChangeNotifier {
  bool _isOnline = false;
  bool _isLoading = false;
  LatLng _currentLocation = LocationService.defaultPosition;
  List<dynamic> _availableOrders = [];
  Map<String, dynamic>? _activeTrip;
  Map<String, dynamic>? _earnings;
  Map<String, dynamic>? _driverProfile;
  List<dynamic> _reviews = [];
  Timer? _gpsBroadcastTimer;
  Timer? _radarPollTimer;

  bool get isOnline => _isOnline;
  bool get isLoading => _isLoading;
  LatLng get currentLocation => _currentLocation;
  List<dynamic> get availableOrders => _availableOrders;
  Map<String, dynamic>? get activeTrip => _activeTrip;
  Map<String, dynamic>? get earnings => _earnings;
  Map<String, dynamic>? get driverProfile => _driverProfile;
  List<dynamic> get reviews => _reviews;

  // Derived stats from earnings
  double get walletBalance => double.tryParse(_earnings?['wallet']?['balance']?.toString() ?? '0') ?? 0.0;
  int get totalOrders => int.tryParse(_earnings?['wallet']?['total_orders']?.toString() ?? '0') ?? 0;
  double get driverRating {
    final rc = int.tryParse(_earnings?['wallet']?['reviews_count']?.toString() ?? '0') ?? 0;
    if (rc == 0) return 5.0;
    return double.tryParse(_earnings?['wallet']?['rating']?.toString() ?? '5.0') ?? 5.0;
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

  void startGpsBroadcaster() {
    _gpsBroadcastTimer?.cancel();
    _gpsBroadcastTimer = Timer.periodic(const Duration(seconds: 15), (_) async {
      if (_isOnline) {
        try {
          final pos = await LocationService.getCurrentPosition();
          _currentLocation = pos;
          await ApiService.postForm(ApiConstants.updateDriverLocation, {
            'lat': pos.latitude.toString(),
            'lng': pos.longitude.toString(),
          });
          notifyListeners();
        } catch (_) {}
      }
    });
  }

  void stopGpsBroadcaster() {
    _gpsBroadcastTimer?.cancel();
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
      final res = await ApiService.get(ApiConstants.driverDashboard);
      if (res['success'] == true && res['data'] != null) {
        final data = res['data'] as Map<String, dynamic>;
        final activeData = data['active_trip'] ?? data['active_order'];
        if (activeData is Map<String, dynamic>) {
          _activeTrip = activeData;
        } else {
          _activeTrip = null;
        }

        // Available orders for grabs
        final availList = (data['available_orders'] as List<dynamic>?) ?? [];
        _availableOrders = availList;

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
            _earnings = {
              'wallet': data['wallet'],
              'driver': data['driver'],
            };
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
            _earnings = {
              'wallet': {
                'balance': data['wallet_balance'],
                'total_orders': data['total_orders'] ?? 0,
              },
              'driver': data['driver'] ?? _driverProfile,
            };
          }
        }

        if (data['reviews'] != null && data['reviews'] is List) {
          _reviews = data['reviews'] as List<dynamic>;
        }

        // Sync online status from server
        final serverOnline = data['is_online'] ?? data['driver']?['is_online'];
        if (serverOnline != null) {
          _isOnline = serverOnline == true || serverOnline == 1 || serverOnline == '1';
        }

        // Auto-start polling if online and timer not active
        if (_isOnline && (_radarPollTimer == null || !_radarPollTimer!.isActive)) {
          startRadarPolling();
          startGpsBroadcaster();
        }
      }
    } catch (_) {}

    if (!silent) {
      _isLoading = false;
    }
    notifyListeners();
  }

  Future<void> fetchEarnings() async {
    _isLoading = true;
    notifyListeners();

    try {
      final res = await ApiService.get(ApiConstants.driverEarnings);
      if (res['success'] == true && res['data'] != null) {
        _earnings = Map<String, dynamic>.from(res['data'] as Map<String, dynamic>);
        if (_earnings?['reviews'] != null && _earnings!['reviews'] is List) {
          _reviews = _earnings!['reviews'] as List<dynamic>;
        }
      }
    } catch (_) {}

    _isLoading = false;
    notifyListeners();
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
        await fetchRadarData();
        return true;
      } else {
        _lastErrorMessage = res['message']?.toString();
      }
    } catch (e) {
      _isLoading = false;
      _lastErrorMessage = 'Koneksi terganggu. Silakan coba lagi.';
    }

    notifyListeners();
    return false;
  }

  Future<bool> updateTripStatus(int orderId, String status, {String? otpCode}) async {
    try {
      final fields = <String, String>{
        'order_id': orderId.toString(),
        'status': status,
      };
      if (otpCode != null) fields['otp'] = otpCode;

      final res = await ApiService.postForm(ApiConstants.updateOrderStatus, fields);
      if (res['success'] == true) {
        await fetchRadarData();
        await fetchEarnings();
        notifyListeners();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> submitWithdraw({
    required String bankName,
    required String accountNumber,
    required String accountHolder,
    required double amount,
  }) async {
    try {
      final res = await ApiService.postForm(ApiConstants.driverWithdraw, {
        'bank_name': bankName,
        'account_number': accountNumber,
        'account_holder': accountHolder,
        'amount': amount.toString(),
      });
      if (res['success'] == true) {
        await fetchEarnings();
        return true;
      }
    } catch (_) {}
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
        await fetchProfile();
        return true;
      }
    } catch (_) {}
    return false;
  }

  @override
  void dispose() {
    stopGpsBroadcaster();
    stopRadarPolling();
    super.dispose();
  }
}
