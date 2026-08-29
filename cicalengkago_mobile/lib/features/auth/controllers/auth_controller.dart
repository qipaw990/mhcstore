import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';

class AuthController extends ChangeNotifier {
  bool _isLoading = false;
  bool _isInitialized = false;
  String? _errorMessage;
  Map<String, dynamic>? _user;
  String? _role;

  bool get isLoading => _isLoading;
  bool get isInitialized => _isInitialized;
  String? get errorMessage => _errorMessage;
  Map<String, dynamic>? get user => _user;
  String? get role => _role;
  bool get isLoggedIn => _user != null;

  Future<void> checkSavedSession() async {
    final prefs = await SharedPreferences.getInstance();
    final userStr = prefs.getString('user_data');
    final roleStr = prefs.getString('user_role');

    if (userStr != null && userStr.isNotEmpty) {
      try {
        _user = jsonDecode(userStr);
        _role = roleStr ?? _user?['role'] ?? 'customer';
      } catch (_) {}
    }
    _isInitialized = true;
    notifyListeners();
  }

  Future<bool> login(String username, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    final response = await ApiService.postForm(ApiConstants.login, {
      'username': username,
      'password': password,
    });

    _isLoading = false;

    if (response['success'] == true) {
      final token = response['token']?.toString() ?? response['data']?['token']?.toString();
      final userData = (response['data']?['user'] ?? response['data'] ?? response['user']) as Map<String, dynamic>?;

      if (userData != null) {
        _user = userData;
        _role = userData['role']?.toString().toLowerCase() ?? 'customer';
        await ApiService.saveSession(userData, token: token);
        notifyListeners();
        return true;
      }
    }

    if (response['user'] != null) {
      final userData = response['user'] as Map<String, dynamic>;
      final token = response['token']?.toString();
      _user = userData;
      _role = userData['role']?.toString().toLowerCase() ?? 'customer';
      await ApiService.saveSession(userData, token: token);
      notifyListeners();
      return true;
    }

    _errorMessage = response['message'] ?? 'Login gagal. Periksa username dan password Anda.';
    notifyListeners();
    return false;
  }

  Future<bool> register({
    required String name,
    required String email,
    required String phone,
    required String password,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    final response = await ApiService.postForm(ApiConstants.register, {
      'name': name,
      'email': email,
      'phone': phone,
      'password': password,
    });

    _isLoading = false;

    if (response['success'] == true) {
      final token = response['token']?.toString() ?? response['data']?['token']?.toString();
      final userData = (response['data']?['user'] ?? response['data'] ?? response['user'])
          as Map<String, dynamic>?;

      if (userData != null) {
        _user = userData;
        _role = userData['role'] ?? 'customer';
        await ApiService.saveSession(userData, token: token);
        notifyListeners();
        return true;
      }
    }

    _errorMessage = response['message'] ?? 'Registrasi gagal. Silakan coba lagi.';
    notifyListeners();
    return false;
  }

  Future<void> updateUser(Map<String, dynamic> updatedData) async {
    if (_user != null) {
      _user = {..._user!, ...updatedData};
    } else {
      _user = updatedData;
    }
    await ApiService.saveSession(_user!);
    notifyListeners();
  }

  Future<void> logout() async {
    _user = null;
    _role = null;
    // Panggil endpoint logout server agar session dihapus di PHP
    try {
      await ApiService.get(ApiConstants.logout);
    } catch (_) {}
    await ApiService.clearSession();
    notifyListeners();
  }
}
