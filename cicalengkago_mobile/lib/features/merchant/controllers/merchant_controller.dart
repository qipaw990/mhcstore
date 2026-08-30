import 'package:flutter/material.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';

class MerchantController extends ChangeNotifier {
  bool _isLoading = false;
  bool _isOpen = true;
  Map<String, dynamic>? _store;
  Map<String, dynamic>? _stats;
  List<dynamic> _orders = [];
  List<dynamic> _products = [];
  List<dynamic> _reviews = [];
  Map<String, dynamic>? _vendorUser;
  Map<String, dynamic>? _wallet;
  List<dynamic> _transactions = [];
  List<dynamic> _withdrawRequests = [];
  double _totalWithdrawn = 0.0;
  double _pendingWithdrawn = 0.0;

  // Analytics & Insights State
  bool _isAnalyticsLoading = false;
  Map<String, dynamic>? _analyticsKpi;
  List<dynamic> _analyticsDailyTrends = [];
  List<dynamic> _analyticsTopProducts = [];
  List<dynamic> _analyticsPaymentBreakdown = [];
  List<dynamic> _analyticsDeliveryBreakdown = [];

  bool get isLoading => _isLoading;
  bool get isOpen => _isOpen;
  Map<String, dynamic>? get store => _store;
  Map<String, dynamic>? get vendorUser => _vendorUser;
  Map<String, dynamic>? get stats => _stats;
  List<dynamic> get orders => _orders;
  List<dynamic> get products => _products;
  List<dynamic> get reviews => _reviews;
  Map<String, dynamic>? get wallet => _wallet;
  List<dynamic> get transactions => _transactions;
  List<dynamic> get withdrawRequests => _withdrawRequests;
  double get totalWithdrawn => _totalWithdrawn;
  double get pendingWithdrawn => _pendingWithdrawn;

  bool get isAnalyticsLoading => _isAnalyticsLoading;
  Map<String, dynamic>? get analyticsKpi => _analyticsKpi;
  List<dynamic> get analyticsDailyTrends => _analyticsDailyTrends;
  List<dynamic> get analyticsTopProducts => _analyticsTopProducts;
  List<dynamic> get analyticsPaymentBreakdown => _analyticsPaymentBreakdown;
  List<dynamic> get analyticsDeliveryBreakdown => _analyticsDeliveryBreakdown;

  Future<void> fetchAnalytics({bool silent = false}) async {
    if (!silent) {
      _isAnalyticsLoading = true;
      notifyListeners();
    }

    try {
      final res = await ApiService.get(ApiConstants.vendorAnalytics);
      if (res['success'] == true && res['data'] != null) {
        final data = res['data'];
        _analyticsKpi = data['kpi'] as Map<String, dynamic>?;
        _analyticsDailyTrends = (data['daily_trends'] as List<dynamic>?) ?? [];
        _analyticsTopProducts = (data['top_products'] as List<dynamic>?) ?? [];
        _analyticsPaymentBreakdown = (data['payment_breakdown'] as List<dynamic>?) ?? [];
        _analyticsDeliveryBreakdown = (data['delivery_breakdown'] as List<dynamic>?) ?? [];
        if (data['store'] != null) {
          _store = data['store'] as Map<String, dynamic>?;
        }
      }
    } catch (_) {}

    _isAnalyticsLoading = false;
    notifyListeners();
  }

  Future<void> fetchDashboardData() async {
    _isLoading = true;
    notifyListeners();

    try {
      final res = await ApiService.get(ApiConstants.vendorDashboard);
      if (res['success'] == true && res['data'] != null) {
        final data = res['data'];
        _store = data['store'] as Map<String, dynamic>?;
        _stats = data['stats'] as Map<String, dynamic>?;
        _orders = (data['orders'] as List<dynamic>?) ?? [];
        _reviews = (data['reviews'] as List<dynamic>?) ?? [];
        _wallet = data['wallet'] as Map<String, dynamic>?;
        _isOpen = (_store?['is_open']?.toString() == '1' || _store?['is_open'] == true || _store?['is_open'] == 1);
      }
      await fetchProducts(silent: true);
      await fetchWallet(silent: true);
      await fetchProfile(silent: true);
    } catch (_) {}

    _isLoading = false;
    notifyListeners();
  }

  Future<void> fetchProfile({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      notifyListeners();
    }

    try {
      final res = await ApiService.get(ApiConstants.vendorProfile);
      if (res['success'] == true && res['data'] != null) {
        final data = res['data'];
        if (data['store'] != null) {
          _store = data['store'] as Map<String, dynamic>?;
        }
        if (data['user'] != null) {
          _vendorUser = data['user'] as Map<String, dynamic>?;
        }
        if (_store != null) {
          _isOpen = (_store?['is_open']?.toString() == '1' || _store?['is_open'] == true || _store?['is_open'] == 1);
        }
      }
    } catch (_) {}

    if (!silent) {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchProducts({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      notifyListeners();
    }

    try {
      final res = await ApiService.get(ApiConstants.vendorProducts);
      if (res['success'] == true && res['data'] != null) {
        _products = (res['data']['products'] as List<dynamic>?) ?? [];
        if (res['data']['store'] != null) {
          _store = res['data']['store'] as Map<String, dynamic>?;
        }
      }
    } catch (_) {}

    if (!silent) {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchOrders({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      notifyListeners();
    }

    try {
      final res = await ApiService.get(ApiConstants.vendorOrders);
      if (res['success'] == true && res['data'] != null) {
        _orders = (res['data']['orders'] as List<dynamic>?) ?? [];
        if (res['data']['store'] != null) {
          _store = res['data']['store'] as Map<String, dynamic>?;
        }
      }
    } catch (_) {}

    if (!silent) {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchWallet({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      notifyListeners();
    }

    try {
      final res = await ApiService.get(ApiConstants.vendorWallet);
      if (res['success'] == true && res['data'] != null) {
        final data = res['data'];
        _wallet = data['wallet'] as Map<String, dynamic>?;
        _transactions = (data['transactions'] as List<dynamic>?) ?? [];
        _withdrawRequests = (data['withdraw_requests'] as List<dynamic>?) ?? [];
        _totalWithdrawn = double.tryParse(data['total_withdrawn']?.toString() ?? '0') ?? 0.0;
        _pendingWithdrawn = double.tryParse(data['pending_withdrawn']?.toString() ?? '0') ?? 0.0;
      }
    } catch (_) {}

    if (!silent) {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> toggleStoreOpenStatus(bool status) async {
    if (_store == null) {
      _isOpen = false;
      notifyListeners();
      return false;
    }
    _isOpen = status;
    notifyListeners();

    try {
      final res = await ApiService.post(ApiConstants.vendorToggleStatus, {});
      if (res['success'] == true) {
        if (_store != null) {
          _store!['is_open'] = status ? 1 : 0;
        }
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> toggleProductStock(int productId, bool inStock) async {
    try {
      final res = await ApiService.post(ApiConstants.toggleProductStatus, {
        'id': productId.toString(),
        'field': 'stock',
      });

      if (res['success'] == true) {
        await fetchProducts(silent: true);
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> toggleProductStatus(int productId) async {
    try {
      final res = await ApiService.post(ApiConstants.toggleProductStatus, {
        'id': productId.toString(),
        'field': 'status',
      });

      if (res['success'] == true) {
        await fetchProducts(silent: true);
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> saveProduct(Map<String, String> fields, {String? imagePath}) async {
    try {
      final res = await ApiService.postForm(
        ApiConstants.vendorSaveProduct,
        fields,
        fileFieldName: imagePath != null ? 'image' : null,
        filePath: imagePath,
      );

      if (res['success'] == true) {
        await fetchProducts(silent: true);
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> deleteProduct(int productId) async {
    try {
      final res = await ApiService.post(ApiConstants.vendorDeleteProduct, {
        'id': productId.toString(),
      });

      if (res['success'] == true) {
        await fetchProducts(silent: true);
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<bool> updateOrderStatus(int orderId, String status, {String? deliveryType}) async {
    try {
      final res = await ApiService.post(ApiConstants.updateStoreOrderStatus, {
        'order_id': orderId.toString(),
        'status': status,
        'delivery_type': ?deliveryType,
      });

      if (res['success'] == true) {
        await fetchDashboardData();
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<Map<String, dynamic>> requestWithdraw({
    required double amount,
    required String bankName,
    required String accountNumber,
    required String accountHolder,
  }) async {
    try {
      final res = await ApiService.post(ApiConstants.vendorWithdraw, {
        'amount': amount.toString(),
        'bank_name': bankName,
        'account_number': accountNumber,
        'account_holder': accountHolder,
      });

      if (res['success'] == true) {
        await fetchWallet(silent: true);
        return {'success': true, 'message': res['message'] ?? 'Pengajuan penarikan dana berhasil!'};
      } else {
        return {'success': false, 'message': res['message'] ?? 'Gagal mengajukan penarikan dana.'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan jaringan.'};
    }
  }

  Future<bool> updateStoreProfile(Map<String, String> fields, {String? logoPath}) async {
    try {
      final res = await ApiService.postForm(
        ApiConstants.vendorUpdateProfile,
        fields,
        fileFieldName: logoPath != null ? 'store_logo' : null,
        filePath: logoPath,
      );

      if (res['success'] == true) {
        await fetchDashboardData();
        return true;
      }
    } catch (_) {}
    return false;
  }
}
