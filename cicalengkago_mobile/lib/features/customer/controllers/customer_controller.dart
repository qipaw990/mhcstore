import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';

class CustomerController extends ChangeNotifier {
  bool _isLoading = false;
  List<dynamic> _modules = [];
  List<dynamic> _banners = [];
  List<dynamic> _categories = [];
  List<dynamic> _stores = [];
  List<dynamic> _topRatedStores = [];
  List<dynamic> _recommendedProducts = [];
  List<dynamic> _discountedProducts = [];
  List<dynamic> _products = [];
  Map<String, dynamic>? _cart;
  List<dynamic> _orders = [];
  Map<String, dynamic>? _wallet;

  bool get isLoading => _isLoading;
  List<dynamic> get modules => _modules;
  List<dynamic> get banners => _banners;
  List<dynamic> get categories => _categories;
  List<dynamic> get stores => _stores;
  List<dynamic> get topRatedStores => _topRatedStores.isNotEmpty ? _topRatedStores : _stores;
  List<dynamic> get recommendedProducts => _recommendedProducts.isNotEmpty ? _recommendedProducts : _products;
  List<dynamic> get discountedProducts => _discountedProducts;
  List<dynamic> get products => _products;
  Map<String, dynamic>? get cart => _cart;
  List<dynamic> get orders => _orders;
  Map<String, dynamic>? get wallet => _wallet;

  Future<void> fetchHomeData() async {
    _isLoading = true;
    notifyListeners();

    try {
      final homeRes = await ApiService.get('${ApiConstants.homeData}?module_id=1');
      if (homeRes['success'] == true && homeRes['data'] != null) {
        final data = homeRes['data'] as Map<String, dynamic>;
        _modules = data['modules'] as List<dynamic>? ?? [];
        _banners = data['banners'] as List<dynamic>? ?? [];
        _categories = data['categories'] as List<dynamic>? ?? [];
        _topRatedStores = data['top_rated_stores'] as List<dynamic>? ?? [];
        _recommendedProducts = data['recommended_products'] as List<dynamic>? ?? [];
        _discountedProducts = data['discounted_products'] as List<dynamic>? ?? [];
      } else {
        // Fallback APIs
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
      }

      await fetchCart();
      await fetchWallet();
    } catch (_) {}

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> addToCart(int productId, int quantity) async {
    try {
      final res = await ApiService.post(ApiConstants.cartAdd, {
        'product_id': productId,
        'quantity': quantity,
      });

      if (res['success'] == true) {
        await fetchCart();
        return true;
      }
    } catch (_) {}
    return false;
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
