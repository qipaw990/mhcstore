import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';

class CustomerController extends ChangeNotifier {
  bool _isLoading = false;
  List<dynamic> _modules = [];
  List<dynamic> _stores = [];
  List<dynamic> _products = [];
  Map<String, dynamic>? _cart;
  List<dynamic> _orders = [];
  Map<String, dynamic>? _wallet;

  bool get isLoading => _isLoading;
  List<dynamic> get modules => _modules;
  List<dynamic> get stores => _stores;
  List<dynamic> get products => _products;
  Map<String, dynamic>? get cart => _cart;
  List<dynamic> get orders => _orders;
  Map<String, dynamic>? get wallet => _wallet;

  Future<void> fetchHomeData() async {
    _isLoading = true;
    notifyListeners();

    try {
      final modRes = await ApiService.get(ApiConstants.modules);
      if (modRes['success'] == true && modRes['data'] != null) {
        _modules = modRes['data'] as List<dynamic>;
      }

      final storeRes = await ApiService.get('${ApiConstants.stores}?module_id=1');
      if (storeRes['success'] == true && storeRes['data'] != null) {
        _stores = storeRes['data'] as List<dynamic>;
      }

      final prodRes = await ApiService.get(ApiConstants.products);
      if (prodRes['success'] == true && prodRes['data'] != null) {
        _products = prodRes['data'] as List<dynamic>;
      }

      await fetchCart();
      await fetchWallet();
    } catch (_) {}

    _isLoading = false;
    notifyListeners();
  }

  Future<void> fetchCart() async {
    final res = await ApiService.get(ApiConstants.cart);
    if (res['success'] == true && res['data'] != null) {
      _cart = res['data'] as Map<String, dynamic>;
      notifyListeners();
    }
  }

  Future<void> fetchOrders() async {
    _isLoading = true;
    notifyListeners();

    final res = await ApiService.get(ApiConstants.orders);
    if (res['success'] == true && res['data'] != null) {
      _orders = res['data'] as List<dynamic>;
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> fetchWallet() async {
    final res = await ApiService.get(ApiConstants.wallet);
    if (res['success'] == true && res['data'] != null) {
      _wallet = res['data'] as Map<String, dynamic>;
      notifyListeners();
    }
  }
}
