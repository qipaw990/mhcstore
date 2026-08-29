class ApiConstants {
  // Base domain — sesuai cicago.store production
  static const String domainUrl    = 'https://cicago.store';
  static const String baseUrl      = domainUrl;
  static const String imageBaseUrl = domainUrl;
  static const String agoraAppId   = 'a34217d291644e47afe172d16c14d17b';

  /// Helper pintar untuk memformat URL Gambar agar selalu valid
  static String formatImageUrl(String? rawUrl) {
    if (rawUrl == null || rawUrl.trim().isEmpty) {
      return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80';
    }
    String path = rawUrl.trim();
    if (path.startsWith('http://') || path.startsWith('https://')) {
      return path;
    }
    if (path.startsWith('public/')) {
      path = path.substring(7);
    } else if (path.startsWith('/public/')) {
      path = path.substring(8);
    }
    if (!path.startsWith('/')) {
      path = '/$path';
    }
    return '$domainUrl$path';
  }

  // ==========================================
  // API Extension Routes (prefix /api/)
  // Hanya endpoint khusus ApiController yang pakai /api/
  // ==========================================
  static const String homeData       = '$domainUrl/api/home-data';
  static const String banners        = '$domainUrl/api/banners';
  static const String categories     = '$domainUrl/api/categories';
  static const String coupons        = '$domainUrl/api/coupons';
  static const String vouchers       = '$domainUrl/api/coupons';
  static const String validateCoupon = '$domainUrl/api/coupons/validate';

  // Fallbacks
  static const String modules  = '$domainUrl/api/home-data';
  static const String stores   = '$domainUrl/explore-stores';
  static const String products = '$domainUrl/api/home-data';

  // ==========================================
  // Auth Routes  (POST /login, /register, /verify-otp, /resend-otp)
  // ==========================================
  static const String login     = '$domainUrl/login';
  static const String register  = '$domainUrl/register';
  static const String verifyOtp = '$domainUrl/verify-otp';
  static const String resendOtp = '$domainUrl/resend-otp';
  static const String logout    = '$domainUrl/logout';

  // ==========================================
  // Customer Routes  (prefix: /)
  // ==========================================
  static const String storeDetail    = '$domainUrl/stores';          // + /{id}
  static const String exploreStores  = '$domainUrl/explore-stores';
  static const String search         = '$domainUrl/search';

  // Cart — sesuai web.php: /cart, /cart/add, /cart/update-qty, /cart/remove, /cart/clear
  static const String cart          = '$domainUrl/cart';
  static const String cartAdd       = '$domainUrl/cart/add';
  static const String cartUpdateQty = '$domainUrl/cart/update-qty';
  static const String cartRemove    = '$domainUrl/cart/remove';
  static const String cartClear     = '$domainUrl/cart/clear';

  // Checkout & Orders — sesuai web.php
  static const String checkout       = '$domainUrl/checkout';
  static const String placeOrder     = '$domainUrl/orders/place';
  static const String validateCoupon = '$domainUrl/api/coupons/validate';
  static const String orders         = '$domainUrl/orders';           // GET list
  static const String liveOrdersList = '$domainUrl/orders/live-list'; // GET polling
  static const String orderTracking  = '$domainUrl/orders';           // + /{code}/tracking
  static const String liveTracking   = '$domainUrl/orders';           // + /{code}/live-tracking
  static const String orderDetail    = '$domainUrl/orders';           // + /{code}
  static const String cancelUnpaid   = '$domainUrl/orders/cancel-unpaid';
  static const String orderReview    = '$domainUrl/orders/review';

  // Wallet & Payment
  static const String wallet              = '$domainUrl/wallet';
  static const String walletTopup         = '$domainUrl/wallet/topup-midtrans';
  static const String paymentTopupSnap    = '$domainUrl/payment/topup-snap';
  static const String paymentVerify       = '$domainUrl/payment/verify';
  static const String paymentSimulate     = '$domainUrl/payment/simulate-sandbox-success';
  static const String paymentNotification = '$domainUrl/payment/midtrans/notification';

  static const String walletTransfer      = '$domainUrl/wallet/transfer';
  static const String paymentTransfer     = '$domainUrl/api/payment/transfer';

  // In-House Payment System (Kode Unik + QRIS Otomatis)
  static const String paymentBanks         = '$domainUrl/api/payment/banks';
  static const String paymentCreateInvoice = '$domainUrl/api/payment/create-invoice';
  static const String paymentCheckInvoice  = '$domainUrl/api/payment/check-invoice';
  static const String paymentSimulatePay   = '$domainUrl/api/payment/simulate-pay';

  // Profile & Notifications
  static const String customerProfile = '$domainUrl/profile';
  static const String updateProfile   = '$domainUrl/profile/update';
  static const String notifications   = '$domainUrl/notifications';

  // Chat
  static const String chatMessages  = '$domainUrl/chats/messages';
  static const String chatSend      = '$domainUrl/chats/send';
  static const String chatRead      = '$domainUrl/chats/read';
  static const String chatUnread    = '$domainUrl/chats/unread-count';

  // ==========================================
  // Delivery / Driver Routes  (prefix: /delivery/)
  // Sesuai web.php grup DeliveryMiddleware
  // ==========================================
  static const String driverDashboard       = '$domainUrl/delivery';
  static const String driverLiveDashboard   = '$domainUrl/delivery/live-dashboard';
  static const String driverToggleOnline    = '$domainUrl/delivery/toggle-online';
  static const String acceptOrder           = '$domainUrl/delivery/accept-order';
  static const String updateOrderStatus     = '$domainUrl/delivery/update-status';
  static const String updateDriverLocation  = '$domainUrl/delivery/update-location';
  static const String driverBatchStatus     = '$domainUrl/delivery/batch-status';
  static const String driverEarnings        = '$domainUrl/delivery/earnings';
  static const String driverWithdraw        = '$domainUrl/delivery/withdraw';
  static const String driverProfile         = '$domainUrl/delivery/profile';
  static const String driverUpdateProfile   = '$domainUrl/delivery/profile/update';

  // ==========================================
  // Vendor / Merchant Routes  (prefix: /vendor/)
  // ==========================================
  static const String vendorDashboard          = '$domainUrl/vendor';
  static const String vendorToggleStatus       = '$domainUrl/vendor/toggle-status';
  static const String toggleStoreStatus        = '$domainUrl/vendor/toggle-status';
  static const String vendorOrders             = '$domainUrl/vendor/orders';
  static const String vendorCheckNewOrders     = '$domainUrl/vendor/orders/check-new';
  static const String updateStoreOrderStatus   = '$domainUrl/vendor/orders/update-status';
  static const String vendorProducts           = '$domainUrl/vendor/products';
  static const String vendorSaveProduct        = '$domainUrl/vendor/products/save';
  static const String vendorDeleteProduct      = '$domainUrl/vendor/products/delete';
  static const String toggleProductStatus      = '$domainUrl/vendor/products/toggle-status';
  static const String updateProductStock       = '$domainUrl/vendor/products/toggle-status';
  static const String vendorWallet             = '$domainUrl/vendor/wallet';
  static const String vendorWithdraw           = '$domainUrl/vendor/wallet/withdraw';
  static const String vendorProfile            = '$domainUrl/vendor/profile';
  static const String vendorUpdateProfile      = '$domainUrl/vendor/profile/update';
}
