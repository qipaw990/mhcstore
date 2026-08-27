class ApiConstants {
  // Production Base Domain URL
  static const String domainUrl = 'https://cicago.store';
  static const String baseUrl = '$domainUrl/api';
  static const String imageBaseUrl = domainUrl;

  // Auth Endpoints
  static const String login = '$baseUrl/login';
  static const String register = '$baseUrl/register';

  // Customer Endpoints
  static const String homeData = '$baseUrl/home-data';
  static const String banners = '$baseUrl/banners';
  static const String categories = '$baseUrl/categories';
  static const String modules = '$baseUrl/modules';
  static const String stores = '$baseUrl/stores';
  static const String products = '$baseUrl/products';
  static const String cart = '$baseUrl/cart';
  static const String cartAdd = '$domainUrl/cart/add';
  static const String checkout = '$baseUrl/checkout';
  static const String orders = '$baseUrl/orders';
  static const String wallet = '$baseUrl/wallet';
  static const String notifications = '$baseUrl/notifications';

  // Driver Endpoints
  static const String updateDriverLocation = '$baseUrl/delivery/update-location';
  static const String driverDashboard = '$baseUrl/delivery/dashboard';
  static const String acceptOrder = '$baseUrl/delivery/accept-order';
  static const String updateOrderStatus = '$baseUrl/delivery/update-order-status';

  // Merchant Endpoints
  static const String vendorDashboard = '$baseUrl/vendor/dashboard';
  static const String vendorOrders = '$baseUrl/vendor/orders';
  static const String vendorProducts = '$baseUrl/vendor/products';
  static const String updateProductStock = '$baseUrl/vendor/update-stock';
  static const String toggleStoreStatus = '$baseUrl/vendor/toggle-open';
  static const String updateStoreOrderStatus = '$baseUrl/vendor/orders/update-status';
}
