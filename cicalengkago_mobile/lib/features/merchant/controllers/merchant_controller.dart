import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';

class MerchantController extends ChangeNotifier {
  bool _isLoading = false;
  bool _isOpen = true;
  Map<String, dynamic>? _store;
  List<dynamic> _orders = [];
  List<dynamic> _products = [];

  bool get isLoading => _isLoading;
  bool get isOpen => _isOpen;
  Map<String, dynamic>? get store => _store;
  List<dynamic> get orders => _orders;
  List<dynamic> get products => _products;

  Future<void> fetchDashboardData() async {
    _isLoading = true;
    notifyListeners();

    final res = await ApiService.get(ApiConstants.vendorDashboard);
    if (res['success'] == true && res['data'] != null) {
      _store = res['data']['store'] as Map<String, dynamic>?;
      _orders = (res['data']['orders'] as List<dynamic>?) ?? [];
      _products = (res['data']['products'] as List<dynamic>?) ?? [];
      _isOpen = (_store?['is_open']?.toString() == '1');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> toggleStoreOpenStatus(bool status) async {
    _isOpen = status;
    notifyListeners();

    await ApiService.post(ApiConstants.toggleStoreStatus, {
      'is_open': status ? '1' : '0',
    });
  }

  Future<void> toggleProductStock(int productId, bool inStock) async {
    final res = await ApiService.post(ApiConstants.updateProductStock, {
      'product_id': productId.toString(),
      'status': inStock ? '1' : '0',
    });

    if (res['success'] == true) {
      fetchDashboardData();
    }
  }
}
