import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';

class PpobService {
  static Future<Map<String,dynamic>> catalog({String? category, String? brand}) async {
    var url = ApiConstants.ppobCatalog;
    final qs = <String>[];
    if (category != null && category.isNotEmpty) qs.add('category=${Uri.encodeComponent(category)}');
    if (brand != null && brand.isNotEmpty) qs.add('brand=${Uri.encodeComponent(brand)}');
    if (qs.isNotEmpty) url += '?${qs.join('&')}';
    return ApiService.get(url);
  }
  static Future<Map<String,dynamic>> categories() => ApiService.get(ApiConstants.ppobCategories);
  static Future<Map<String,dynamic>> purchase({required String sku, required String customerNo}) {
    return ApiService.post(ApiConstants.ppobPurchase, {'buyer_sku_code': sku, 'customer_no': customerNo});
  }
  static Future<Map<String,dynamic>> history() => ApiService.get(ApiConstants.ppobHistory);
  static Future<Map<String,dynamic>> status(String refId) => ApiService.get('${ApiConstants.ppobStatus}/$refId');
}
