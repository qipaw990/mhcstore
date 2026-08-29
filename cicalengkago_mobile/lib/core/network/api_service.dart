import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// ApiService — CicalengkaGO PHP Session-Based Backend
///
/// Backend menggunakan PHP $_SESSION untuk autentikasi, bukan JWT.
/// Setiap request harus membawa Cookie: PHPSESSID=xxx agar server
/// mengenali user yang sudah login.
class ApiService {
  // Key untuk menyimpan session cookie di SharedPreferences
  static const _cookieKey = 'php_session_cookie';
  static const _userDataKey = 'user_data';
  static const _userRoleKey = 'user_role';

  // ── Simpan cookie dari response header Set-Cookie ──────────────────
  static Future<void> _saveCookiesFromResponse(http.Response response) async {
    final setCookie = response.headers['set-cookie'];
    if (setCookie != null && setCookie.isNotEmpty) {
      // Ambil bagian "PHPSESSID=xxx" saja (sebelum ;)
      final cookiePart = setCookie.split(';').first.trim();
      if (cookiePart.isNotEmpty) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(_cookieKey, cookiePart);
      }
    }
  }

  // ── Baca cookie yang tersimpan ──────────────────────────────────────
  // ── Baca cookie & token yang tersimpan ──────────────────────────────
  static Future<String?> _getSavedCookie() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_cookieKey);
  }

  static Future<String?> _getSavedToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('api_token');
  }

  static Future<String> _getGuestSessionId() async {
    final prefs = await SharedPreferences.getInstance();
    String? guestId = prefs.getString('guest_session_id');
    if (guestId == null || guestId.isEmpty) {
      guestId = 'guest_${DateTime.now().millisecondsSinceEpoch}_${(DateTime.now().microsecondsSinceEpoch % 10000)}';
      await prefs.setString('guest_session_id', guestId);
    }
    return guestId;
  }

  static Future<String?> _getSavedUserId() async {
    final prefs = await SharedPreferences.getInstance();
    final rawUser = prefs.getString(_userDataKey);
    if (rawUser != null && rawUser.isNotEmpty) {
      try {
        final decoded = jsonDecode(rawUser);
        if (decoded is Map && decoded['id'] != null) {
          return decoded['id'].toString();
        }
      } catch (_) {}
    }
    return null;
  }

  // ── Headers standar untuk semua request ───────────────────────────
  static Future<Map<String, String>> _buildHeaders({bool isJson = true}) async {
    final cookie = await _getSavedCookie();
    final token = await _getSavedToken();
    final userId = await _getSavedUserId();
    final guestSessionId = await _getGuestSessionId();

    return {
      if (isJson) 'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest', // Agar backend tahu ini AJAX
      if (cookie != null && cookie.isNotEmpty) 'Cookie': cookie,
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
      if (userId != null && userId.isNotEmpty) 'X-User-ID': userId,
      'X-Session-ID': guestSessionId,
    };
  }

  // ── Simpan session setelah login berhasil ──────────────────────────
  static Future<void> saveSession(Map<String, dynamic> user, {String? token}) async {
    final prefs = await SharedPreferences.getInstance();
    final apiToken = token ?? user['api_token']?.toString();
    if (apiToken != null && apiToken.isNotEmpty) {
      await prefs.setString('api_token', apiToken);
    }
    await prefs.setString(_userRoleKey, user['role']?.toString() ?? 'customer');
    await prefs.setString(_userDataKey, jsonEncode(user));
  }

  // ── Legacy compat: beberapa controller masih kirim token ────────────
  static Future<void> saveSessionWithToken(String token, Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('api_token', token);
    await prefs.setString(_userRoleKey, user['role']?.toString() ?? 'customer');
    await prefs.setString(_userDataKey, jsonEncode(user));
  }

  // ── Hapus semua session (logout) ───────────────────────────────────
  static Future<void> clearSession() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }

  // ── GET Request ───────────────────────────────────────────────────
  static Future<Map<String, dynamic>> get(String url) async {
    try {
      final userId = await _getSavedUserId();
      Uri uri = Uri.parse(url);
      if (userId != null && userId.isNotEmpty && !uri.queryParameters.containsKey('user_id')) {
        final Map<String, String> qParams = Map.from(uri.queryParameters);
        qParams['user_id'] = userId;
        uri = uri.replace(queryParameters: qParams);
      }

      final headers = await _buildHeaders(isJson: false);
      final response = await http
          .get(uri, headers: headers)
          .timeout(const Duration(seconds: 20));

      await _saveCookiesFromResponse(response);
      return _parseResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Kesalahan koneksi: $e'};
    }
  }

  // ── POST Request (JSON body) ───────────────────────────────────────
  static Future<Map<String, dynamic>> post(
    String url,
    Map<String, dynamic> body,
  ) async {
    try {
      final userId = await _getSavedUserId();
      final Map<String, dynamic> updatedBody = Map.from(body);
      if (userId != null && userId.isNotEmpty && !updatedBody.containsKey('user_id')) {
        updatedBody['user_id'] = userId;
      }

      final headers = await _buildHeaders(isJson: true);
      final response = await http
          .post(Uri.parse(url), headers: headers, body: jsonEncode(updatedBody))
          .timeout(const Duration(seconds: 20));

      await _saveCookiesFromResponse(response);
      return _parseResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Kesalahan koneksi: $e'};
    }
  }

  // ── POST multipart/form-data (untuk upload file, atau form PHP lama) ─
  static Future<Map<String, dynamic>> postForm(
    String url,
    Map<String, String> fields, {
    String? fileFieldName,
    String? filePath,
  }) async {
    try {
      final cookie = await _getSavedCookie();
      final token = await _getSavedToken();
      final userId = await _getSavedUserId();
      final guestSessionId = await _getGuestSessionId();

      final request = http.MultipartRequest('POST', Uri.parse(url));
      request.headers.addAll({
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        if (cookie != null && cookie.isNotEmpty) 'Cookie': cookie,
        if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
        if (userId != null && userId.isNotEmpty) 'X-User-ID': userId,
        'X-Session-ID': guestSessionId,
      });

      final Map<String, String> updatedFields = Map.from(fields);
      if (userId != null && userId.isNotEmpty && !updatedFields.containsKey('user_id')) {
        updatedFields['user_id'] = userId;
      }
      request.fields.addAll(updatedFields);

      if (fileFieldName != null && filePath != null && filePath.isNotEmpty) {
        final multipartFile = await http.MultipartFile.fromPath(fileFieldName, filePath);
        request.files.add(multipartFile);
      }

      final streamed = await request.send().timeout(const Duration(seconds: 30));
      final response = await http.Response.fromStream(streamed);

      await _saveCookiesFromResponse(response);
      return _parseResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Kesalahan koneksi: $e'};
    }
  }

  // ── Parse response body ────────────────────────────────────────────
  static Map<String, dynamic> _parseResponse(http.Response response) {
    try {
      final body = response.body.trim();
      if (body.isEmpty) {
        return {
          'success': response.statusCode >= 200 && response.statusCode < 300,
          'message': 'Response kosong dari server',
        };
      }

      // Kalau bukan JSON (e.g., HTML redirect dari middleware), return error
      if (!body.startsWith('{') && !body.startsWith('[')) {
        // Redirect ke login = session expired
        if (response.statusCode == 302 || body.contains('<html')) {
          return {'success': false, 'message': 'Sesi berakhir, silakan login ulang.', 'unauthenticated': true};
        }
        return {'success': false, 'message': 'Respon server tidak valid (bukan JSON)'};
      }

      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      // Array response → wrap
      return {
        'success': response.statusCode >= 200 && response.statusCode < 300,
        'data': decoded,
      };
    } catch (_) {
      return {'success': false, 'message': 'Gagal memproses respon server'};
    }
  }
}
