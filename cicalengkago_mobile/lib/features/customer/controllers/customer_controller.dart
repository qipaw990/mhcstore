import 'package:flutter/material.dart';
import 'package:latlong2/latlong.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/constants/zone_constants.dart';
import '../../../core/network/api_service.dart';

class CustomerController extends ChangeNotifier {
  bool _isLoading = false;
  List<dynamic> _modules = [];
  List<dynamic> _banners = [];
  List<dynamic> _categories = [];
  List<dynamic> _coupons = [];
  List<dynamic> _stores = [];
  List<dynamic> _topRatedStores = [];
  List<dynamic> _recommendedProducts = [];
  List<dynamic> _discountedProducts = [];
  final List<dynamic> _products = [];
  Map<String, dynamic>? _cart;
  List<dynamic> _orders = [];
  Map<String, dynamic>? _wallet;
  Map<String, dynamic>? _profile;
  List<dynamic> _notifications = [];
  int _unreadNotifCount = 0;
  String? _lastCartError;
  Map<String, dynamic>? _zoneConfig;

  bool get isLoading => _isLoading;
  String? get lastCartError => _lastCartError;
  List<dynamic> get modules => _modules;
  List<dynamic> get banners => _banners;
  List<dynamic> get categories => _categories;
  List<dynamic> get coupons => _coupons;
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

  int get cartCount {
    if (_cart == null) return 0;

    final List<dynamic> allItems = [];
    final Set<String> seenKeys = {};

    void addItem(dynamic item) {
      if (item is Map) {
        final key = (item['id'] ?? item['cart_id'] ?? item['product_id'])?.toString();
        if (key != null && key.isNotEmpty) {
          if (!seenKeys.contains(key)) {
            seenKeys.add(key);
            allItems.add(item);
          }
        } else {
          allItems.add(item);
        }
      }
    }

    // 1. Direct items list
    if (_cart!['items'] is List && (_cart!['items'] as List).isNotEmpty) {
      for (var item in (_cart!['items'] as List)) {
        addItem(item);
      }
    } else if (_cart!['stores'] is List) {
      // 2. Stores grouped list (only if direct items list is empty)
      final List storesList = _cart!['stores'] as List;
      for (var s in storesList) {
        if (s is Map && s['items'] is List) {
          for (var item in (s['items'] as List)) {
            addItem(item);
          }
        }
      }
    }

    // 3. Sum total quantity across deduplicated items
    if (allItems.isNotEmpty) {
      int sumQty = 0;
      for (var item in allItems) {
        final qty = int.tryParse(item['quantity']?.toString() ?? '1') ?? 1;
        sumQty += qty;
      }
      return sumQty;
    }

    // 4. Fallback count fields
    if (_cart!['count'] != null) {
      return int.tryParse(_cart!['count'].toString()) ?? 0;
    }
    if (_cart!['cart_count'] != null) {
      return int.tryParse(_cart!['cart_count'].toString()) ?? 0;
    }

    return 0;
  }

  List<dynamic> get cartItems {
    if (_cart == null) return [];

    final List<dynamic> allItems = [];

    if (_cart!['items'] is List && (_cart!['items'] as List).isNotEmpty) {
      return _cart!['items'] as List<dynamic>;
    }

    if (_cart!['stores'] is List) {
      final List storesList = _cart!['stores'] as List;
      for (var s in storesList) {
        if (s is Map && s['items'] is List) {
          allItems.addAll(s['items'] as List);
        }
      }
    }

    return allItems;
  }

  double get cartSubtotal {
    if (_cart == null) return 0.0;

    final val = _cart!['grand_subtotal'] ?? _cart!['subtotal'] ?? _cart!['grand_total'] ?? _cart!['total'];
    if (val != null) {
      final parsed = double.tryParse(val.toString());
      if (parsed != null && parsed > 0) return parsed;
    }

    final items = cartItems;
    if (items.isNotEmpty) {
      double sum = 0.0;
      for (var item in items) {
        final price = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
        final qty = double.tryParse(item['quantity']?.toString() ?? '1') ?? 1.0;
        final total = double.tryParse(item['item_total']?.toString() ?? '') ?? (price * qty);
        sum += total;
      }
      return sum;
    }

    return 0.0;
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
        _coupons = data['coupons'] as List<dynamic>? ?? data['vouchers'] as List<dynamic>? ?? [];
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
    } catch (_) {}

    await Future.wait([fetchCart(), fetchWallet(), fetchNotifications()]);

    _isLoading = false;
    notifyListeners();
  }

  Future<void> fetchExploreStores() async {
    _isLoading = true;
    notifyListeners();
    try {
      final res = await ApiService.get(ApiConstants.exploreStores);
      if (res['success'] == true && res['data'] != null) {
        if (res['data'] is List) {
          _stores = res['data'] as List<dynamic>;
          if (_topRatedStores.isEmpty) _topRatedStores = _stores;
        } else if (res['data'] is Map && res['data']['stores'] is List) {
          _stores = res['data']['stores'] as List<dynamic>;
          if (_topRatedStores.isEmpty) _topRatedStores = _stores;
        }
      }
    } catch (_) {}
    _isLoading = false;
    notifyListeners();
  }

  // ── CART METHODS (Sesuaian dengan web.php routes /cart/add, /cart/update-qty, /cart/remove, /cart/clear) ──

  Future<void> fetchCart() async {
    try {
      final res = await ApiService.get(ApiConstants.cart);
      if (res['success'] == true && res['data'] != null && res['data'] is Map<String, dynamic>) {
        _cart = res['data'] as Map<String, dynamic>;
      } else if (res['cart'] != null && res['cart'] is Map<String, dynamic>) {
        _cart = res['cart'] as Map<String, dynamic>;
      } else if (res['data'] is Map<String, dynamic>) {
        _cart = res['data'] as Map<String, dynamic>;
      } else if (res.containsKey('items') || res.containsKey('stores') || res.containsKey('count')) {
        _cart = res;
      }
    } catch (_) {}
    notifyListeners();
  }

  Future<bool> addToCart(int productId, int quantity, {String? notes}) async {
    _lastCartError = null;
    try {
      final Map<String, String> fields = {
        'product_id': productId.toString(),
        'quantity': quantity.toString(),
      };
      if (notes != null && notes.isNotEmpty) {
        fields['item_notes'] = notes;
        fields['notes'] = notes;
      }

      final res = await ApiService.postForm(ApiConstants.cartAdd, fields);

      await fetchCart();

      if (res['success'] == true || res['status'] == 'success') {
        _lastCartError = null;
        notifyListeners();
        return true;
      } else {
        _lastCartError = res['message']?.toString() ?? 'Gagal menambahkan ke keranjang';
      }
    } catch (e) {
      _lastCartError = 'Terjadi kesalahan koneksi: $e';
    }

    await fetchCart();
    notifyListeners();
    return false;
  }

  Future<bool> updateCartQty(int productId, int quantity, {int? cartId}) async {
    try {
      final Map<String, String> fields = {
        'product_id': productId.toString(),
        'quantity': quantity.toString(),
      };
      if (cartId != null && cartId > 0) {
        fields['cart_id'] = cartId.toString();
      }
      final res = await ApiService.postForm(ApiConstants.cartUpdateQty, fields);

      await fetchCart();

      if (res['success'] == true || res['status'] == 'success') {
        return true;
      }
    } catch (_) {}

    await fetchCart();
    return false;
  }

  Future<bool> removeFromCart(int productId, {int? cartId}) async {
    try {
      final Map<String, String> fields = {
        'product_id': productId.toString(),
      };
      if (cartId != null && cartId > 0) {
        fields['cart_id'] = cartId.toString();
      }
      final res = await ApiService.postForm(ApiConstants.cartRemove, fields);

      await fetchCart();

      if (res['success'] == true || res['status'] == 'success') {
        return true;
      }
    } catch (_) {}

    await fetchCart();
    return false;
  }

  Future<bool> clearCart() async {
    try {
      final res = await ApiService.postForm(ApiConstants.cartClear, {});
      await fetchCart();
      if (res['success'] == true || res['status'] == 'success') {
        _cart = null;
        notifyListeners();
        return true;
      }
    } catch (_) {}

    await fetchCart();
    return false;
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

  // ── ORDER & CHECKOUT METHODS (Sesuaian dengan web.php routes /orders/place) ──

  Future<Map<String, dynamic>> placeOrder({
    required int storeId,
    required String deliveryAddress,
    required double lat,
    required double lng,
    required String paymentMethod, // 'cod', 'wallet', 'midtrans'
    String? note,
    String? couponCode,
    String deliveryType = 'driver', // 'driver' or 'merchant'
    double? distanceKm,
  }) async {
    try {
      final res = await ApiService.postForm(ApiConstants.placeOrder, {
        'store_id': storeId.toString(),
        'delivery_address': deliveryAddress,
        'address': deliveryAddress,
        'lat': lat.toString(),
        'lng': lng.toString(),
        'latitude': lat.toString(),
        'longitude': lng.toString(),
        'payment_method': paymentMethod,
        'order_note': note ?? '',
        'order_notes': note ?? '',
        'coupon_code': couponCode ?? '',
        'delivery_type': deliveryType,
        if (distanceKm != null) 'distance_km': distanceKm.toString(),
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

  Future<Map<String, dynamic>> validateCoupon(String code, double subtotal) async {
    try {
      final res = await ApiService.postForm(ApiConstants.validateCoupon, {
        'coupon_code': code,
        'code': code,
        'subtotal': subtotal.toString(),
        'amount': subtotal.toString(),
      });
      return res;
    } catch (e) {
      return {'success': false, 'message': 'Gagal memverifikasi voucher: $e'};
    }
  }

  Future<void> fetchCoupons() async {
    try {
      final res = await ApiService.get(ApiConstants.coupons);
      if (res['success'] == true && res['data'] != null) {
        _coupons = res['data'] as List<dynamic>;
        notifyListeners();
      }
    } catch (_) {}
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
        final data = res['data'];
        if (data is Map<String, dynamic>) {
          if (data['user'] is Map<String, dynamic>) {
            _profile = data['user'] as Map<String, dynamic>;
          } else {
            _profile = data;
          }
          if (data['wallet'] is Map<String, dynamic>) {
            _wallet = data['wallet'] as Map<String, dynamic>;
          }
        }
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<bool> updateProfile(Map<String, dynamic> data, {String? avatarPath}) async {
    try {
      final fields = <String, String>{};
      data.forEach((key, val) {
        if (val != null) fields[key] = val.toString();
      });
      final res = await ApiService.postForm(
        ApiConstants.updateProfile,
        fields,
        fileFieldName: avatarPath != null ? 'avatar' : null,
        filePath: avatarPath,
      );
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
