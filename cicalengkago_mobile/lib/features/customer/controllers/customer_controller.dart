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
  Map<String, dynamic>? _profile;
  List<dynamic> _notifications = [];
  int _unreadNotifCount = 0;

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
  Map<String, dynamic>? get profile => _profile;
  List<dynamic> get notifications => _notifications;
  int get unreadNotifCount => _unreadNotifCount;

  int get cartCount {
    if (_cart == null) return 0;
    final items = _cart!['items'] as List<dynamic>?;
    if (items == null) return 0;
    return items.fold<int>(0, (sum, item) {
      final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;
      return sum + qty;
    });
  }

  List<dynamic> get cartItems => (_cart?['items'] as List<dynamic>?) ?? [];

  double get cartSubtotal {
    if (_cart == null) return 0.0;
    return double.tryParse(_cart!['grand_total']?.toString() ?? _cart!['subtotal']?.toString() ?? '0') ?? 0.0;
  }

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
        final modRes = await ApiService.get(ApiConstants.homeData);
        if (modRes['success'] == true && modRes['data'] != null) {
          _modules = (modRes['data']['modules'] as List<dynamic>?) ?? [];
        }

        final storeRes = await ApiService.get(ApiConstants.exploreStores);
        if (storeRes['success'] == true && storeRes['data'] != null) {
          _stores = storeRes['data'] as List<dynamic>;
        }
      }

      await Future.wait([fetchCart(), fetchWallet(), fetchNotifications()]);
    } catch (_) {}

    _isLoading = false;
    notifyListeners();
  }

  // ── CART METHODS (Sesuaian dengan web.php routes /cart/add, /cart/update-qty, /cart/remove, /cart/clear) ──

  Future<void> fetchCart() async {
    try {
      final res = await ApiService.get(ApiConstants.cart);
      if (res['success'] == true && res['data'] != null) {
        _cart = res['data'] as Map<String, dynamic>;
      } else if (res['cart'] != null) {
        _cart = res['cart'] as Map<String, dynamic>;
      }
      notifyListeners();
    } catch (_) {}
  }

  Future<bool> addToCart(int productId, int quantity, {String? notes}) async {
    try {
      final res = await ApiService.postForm(ApiConstants.cartAdd, {
        'product_id': productId.toString(),
        'quantity': quantity.toString(),
        if (notes != null) 'notes': notes,
      });

      if (res['success'] == true) {
        await fetchCart();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> updateCartQty(int productId, int quantity) async {
    try {
      final res = await ApiService.postForm(ApiConstants.cartUpdateQty, {
        'product_id': productId.toString(),
        'quantity': quantity.toString(),
      });

      if (res['success'] == true) {
        await fetchCart();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> removeFromCart(int productId) async {
    try {
      final res = await ApiService.postForm(ApiConstants.cartRemove, {
        'product_id': productId.toString(),
      });

      if (res['success'] == true) {
        await fetchCart();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> clearCart() async {
    try {
      final res = await ApiService.postForm(ApiConstants.cartClear, {});
      if (res['success'] == true) {
        _cart = null;
        notifyListeners();
        return true;
      }
    } catch (_) {}
    return false;
  }

  // ── ORDER & CHECKOUT METHODS (Sesuaian dengan web.php routes /orders/place) ──

  Future<Map<String, dynamic>> placeOrder({
    required int storeId,
    required String deliveryAddress,
    required double lat,
    required double lng,
    required String paymentMethod, // 'cod', 'wallet', 'midtrans'
    String? note,
  }) async {
    try {
      final res = await ApiService.postForm(ApiConstants.placeOrder, {
        'store_id': storeId.toString(),
        'delivery_address': deliveryAddress,
        'lat': lat.toString(),
        'lng': lng.toString(),
        'payment_method': paymentMethod,
        if (note != null) 'order_note': note,
      });

      if (res['success'] == true) {
        await fetchCart();
        await fetchOrders();
      }
      return res;
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan sistem: $e'};
    }
  }

  Future<void> fetchOrders() async {
    _isLoading = true;
    notifyListeners();

    try {
      final res = await ApiService.get(ApiConstants.orders);
      if (res['success'] == true && res['data'] != null) {
        _orders = res['data'] as List<dynamic>;
      } else if (res['orders'] != null) {
        _orders = res['orders'] as List<dynamic>;
      }
    } catch (_) {}

    _isLoading = false;
    notifyListeners();
  }

  Future<Map<String, dynamic>?> fetchOrderTracking(String orderCode) async {
    try {
      final res = await ApiService.get('${ApiConstants.orderTracking}/$orderCode/tracking');
      if (res['success'] == true && res['data'] != null) {
        return res['data'] as Map<String, dynamic>;
      }
    } catch (_) {}
    return null;
  }

  Future<void> fetchWallet() async {
    try {
      final res = await ApiService.get(ApiConstants.wallet);
      if (res['success'] == true && res['data'] != null) {
        _wallet = res['data'] as Map<String, dynamic>;
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<void> fetchProfile() async {
    try {
      final res = await ApiService.get(ApiConstants.customerProfile);
      if (res['success'] == true && res['data'] != null) {
        _profile = res['data'] as Map<String, dynamic>;
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<bool> updateProfile(Map<String, dynamic> data) async {
    try {
      final fields = <String, String>{};
      data.forEach((key, val) => fields[key] = val?.toString() ?? '');
      final res = await ApiService.postForm(ApiConstants.updateProfile, fields);
      if (res['success'] == true) {
        await fetchProfile();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<void> fetchNotifications() async {
    try {
      final res = await ApiService.get(ApiConstants.notifications);
      if (res['success'] == true && res['data'] != null) {
        _notifications = res['data'] as List<dynamic>;
        _unreadNotifCount = _notifications.where((n) => n['is_read'] == false || n['is_read'] == 0).length;
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<bool> submitOrderReview({
    required int orderId,
    required String orderCode,
    required int storeRating,
    required String storeComment,
    int? driverRating,
    String? driverComment,
  }) async {
    try {
      final body = <String, String>{
        'order_id': orderId.toString(),
        'order_code': orderCode,
        'store_rating': storeRating.toString(),
        'store_comment': storeComment,
      };
      if (driverRating != null) body['dm_rating'] = driverRating.toString();
      if (driverComment != null) body['dm_comment'] = driverComment;

      final res = await ApiService.postForm(ApiConstants.orderReview, body);
      return res['success'] == true;
    } catch (_) {}
    return false;
  }
}
