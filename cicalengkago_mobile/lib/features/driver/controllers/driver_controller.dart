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
  Timer? _gpsBroadcastTimer;

  bool get isOnline => _isOnline;
  bool get isLoading => _isLoading;
  LatLng get currentLocation => _currentLocation;
  List<dynamic> get availableOrders => _availableOrders;
  Map<String, dynamic>? get activeTrip => _activeTrip;

  void toggleOnline(bool status) {
    _isOnline = status;
    if (_isOnline) {
      startGpsBroadcaster();
      fetchRadarData();
    } else {
      stopGpsBroadcaster();
    }
    notifyListeners();
  }

  void startGpsBroadcaster() {
    _gpsBroadcastTimer?.cancel();
    _gpsBroadcastTimer = Timer.periodic(const Duration(seconds: 15), (_) async {
      if (_isOnline) {
        final pos = await LocationService.getCurrentPosition();
        _currentLocation = pos;
        await ApiService.post(ApiConstants.updateDriverLocation, {
          'lat': pos.latitude.toString(),
          'lng': pos.longitude.toString(),
        });
        notifyListeners();
      }
    });
  }

  void stopGpsBroadcaster() {
    _gpsBroadcastTimer?.cancel();
  }

  Future<void> fetchRadarData() async {
    _isLoading = true;
    notifyListeners();

    final res = await ApiService.get(ApiConstants.driverDashboard);
    if (res['success'] == true && res['data'] != null) {
      _availableOrders = (res['data']['available_orders'] as List<dynamic>?) ?? [];
      _activeTrip = res['data']['active_trip'] as Map<String, dynamic>?;
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> acceptOrder(int orderId) async {
    _isLoading = true;
    notifyListeners();

    final res = await ApiService.post(ApiConstants.acceptOrder, {
      'order_id': orderId.toString(),
    });

    _isLoading = false;
    if (res['success'] == true) {
      await fetchRadarData();
      return true;
    }

    notifyListeners();
    return false;
  }

  Future<bool> updateTripStatus(int orderId, String status) async {
    final res = await ApiService.post(ApiConstants.updateOrderStatus, {
      'order_id': orderId.toString(),
      'status': status,
    });

    if (res['success'] == true) {
      await fetchRadarData();
      return true;
    }
    return false;
  }

  @override
  void dispose() {
    stopGpsBroadcaster();
    super.dispose();
  }
}
