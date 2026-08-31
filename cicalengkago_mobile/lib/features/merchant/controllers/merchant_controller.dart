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
  List<dynamic> _analyticsRecentOrders = [];

  // Smart Business Insights State
  bool _isSmartInsightsLoading = false;
  Map<String, dynamic>? _smartInsights;

  // Daily Settlement (Tutup Kasir) State
  bool _isSettlementLoading = false;
  Map<String, dynamic>? _dailySettlement;

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
  List<dynamic> get analyticsRecentOrders => _analyticsRecentOrders;

  bool get isSmartInsightsLoading => _isSmartInsightsLoading;
  Map<String, dynamic>? get smartInsights => _smartInsights;

  bool get isSettlementLoading => _isSettlementLoading;
  Map<String, dynamic>? get dailySettlement => _dailySettlement;

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
        _analyticsRecentOrders = (data['recent_orders'] as List<dynamic>?) ?? [];
        if (data['store'] != null) {
          _store = data['store'] as Map<String, dynamic>?;
        }
      }
    } catch (_) {}

    _isAnalyticsLoading = false;
    notifyListeners();
  }

  Future<void> fetchSmartInsights({bool silent = false}) async {
    if (!silent) {
      _isSmartInsightsLoading = true;
      notifyListeners();
    }

    try {
      final res = await ApiService.get(ApiConstants.vendorSmartInsights);
      if (res['success'] == true && res['data'] != null) {
        _smartInsights = res['data'] as Map<String, dynamic>?;
      }
    } catch (_) {}

    _isSmartInsightsLoading = false;
    notifyListeners();
  }

  Future<void> fetchDailySettlement({String? date, bool silent = false}) async {
    if (!silent) {
      _isSettlementLoading = true;
      notifyListeners();
    }

    try {
      final url = date != null ? '${ApiConstants.vendorDailySettlement}?date=$date' : ApiConstants.vendorDailySettlement;
      final res = await ApiService.get(url);
      if (res['success'] == true && res['data'] != null) {
        _dailySettlement = res['data'] as Map<String, dynamic>?;
      }
    } catch (_) {}

    _isSettlementLoading = false;
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
      await fetchSmartInsights(silent: true);
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
    }
    notifyListeners();
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
    }
    notifyListeners();
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
    }
    notifyListeners();
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
    }
    notifyListeners();
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

  Future<Map<String, dynamic>> updateStoreProfile(Map<String, String> fields, {String? logoPath}) async {
    try {
      final res = await ApiService.postForm(
        ApiConstants.vendorUpdateProfile,
        fields,
        fileFieldName: logoPath != null ? 'store_logo' : null,
        filePath: logoPath,
      );

      if (res['success'] == true) {
        if (res['data'] != null && res['data']['store'] != null) {
          _store = Map<String, dynamic>.from(res['data']['store']);
        }
        await fetchProfile();
        await fetchDashboardData();
        notifyListeners();
        return {'success': true, 'message': res['message'] ?? 'Profil dan pengaturan resto berhasil diperbarui!'};
      } else {
        return {'success': false, 'message': res['message'] ?? 'Gagal menyimpan profil resto.'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan jaringan: $e'};
    }
  }

  Future<Map<String, dynamic>> posCheckout({
    required List<Map<String, dynamic>> items,
    required String paymentMethod,
    double cashGiven = 0.0,
    double discountAmount = 0.0,
    String customerName = 'Pelanggan Langsung (POS)',
    String customerPhone = '-',
    String notes = '',
  }) async {
    try {
      final res = await ApiService.post(ApiConstants.vendorPosCheckout, {
        'items': items,
        'payment_method': paymentMethod,
        'cash_given': cashGiven.toString(),
        'discount_amount': discountAmount.toString(),
        'customer_name': customerName,
        'customer_phone': customerPhone,
        'notes': notes,
      });

      if (res['success'] == true && res['data'] != null) {
        await fetchProducts(silent: true);
        await fetchDashboardData();
        return {'success': true, 'data': res['data']};
      }
      return {'success': false, 'message': res['message'] ?? 'Gagal memproses transaksi kasir POS.'};
    } catch (e) {
      return {'success': false, 'message': 'Terjadi kesalahan jaringan saat checkout POS.'};
    }
  }

  /// Cari produk berdasarkan barcode — untuk modal Stok Masuk
  Future<Map<String, dynamic>> findProductByBarcode(String barcode) async {
    try {
      final res = await ApiService.get(
        '${ApiConstants.vendorFindByBarcode}?barcode=${Uri.encodeComponent(barcode)}',
      );
      if (res['success'] == true && res['data'] != null) {
        return {
          'success': true,
          'found': res['data']['found'] == true,
          'product': res['data']['product'],
        };
      }
    } catch (_) {}
    return {'success': false, 'found': false, 'product': null};
  }

  /// Tambah stok masuk + update HPP (markup dipertahankan otomatis)
  Future<Map<String, dynamic>> stockIn({
    required int productId,
    required int qtyIn,
    double newHpp = 0,
  }) async {
    try {
      final res = await ApiService.post(ApiConstants.vendorStockIn, {
        'product_id': productId.toString(),
        'qty_in': qtyIn.toString(),
        'new_hpp': newHpp.toString(),
      });
      if (res['success'] == true) {
        await fetchProducts(silent: true);
        return {'success': true, 'data': res['data'], 'message': res['message'] ?? 'Stok berhasil ditambahkan!'};
      }
      return {'success': false, 'message': res['message'] ?? 'Gagal menambah stok.'};
    } catch (_) {
      return {'success': false, 'message': 'Terjadi kesalahan jaringan.'};
    }
  }

  /// Jalankan migrasi HPP sekali di server (panggil saat pertama kali)
  Future<void> migrateHpp() async {
    try {
      await ApiService.get(ApiConstants.vendorMigrateHpp);
    } catch (_) {}
  }
}
