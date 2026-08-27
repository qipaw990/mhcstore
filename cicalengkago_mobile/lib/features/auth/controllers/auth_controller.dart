import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';

class AuthController extends ChangeNotifier {
  bool _isLoading = false;
  String? _errorMessage;
  Map<String, dynamic>? _user;
  String? _role;

  bool get isLoading => _isLoading;
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
        notifyListeners();
      } catch (_) {}
    }
  }

  Future<bool> login(String username, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    final response = await ApiService.post(ApiConstants.login, {
      'username': username,
      'password': password,
    });

    _isLoading = false;

    if (response['success'] == true && response['data'] != null) {
      final token = response['data']['token'] as String?;
      final userData = response['data']['user'] as Map<String, dynamic>?;

      if (token != null && userData != null) {
        _user = userData;
        _role = userData['role'] ?? 'customer';
        await ApiService.saveSession(token, userData);
        notifyListeners();
        return true;
      }
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

    final response = await ApiService.post(ApiConstants.register, {
      'name': name,
      'email': email,
      'phone': phone,
      'password': password,
    });

    _isLoading = false;

    if (response['success'] == true && response['data'] != null) {
      final token = response['data']['token'] as String?;
      final userData = response['data']['user'] as Map<String, dynamic>?;

      if (token != null && userData != null) {
        _user = userData;
        _role = userData['role'] ?? 'customer';
        await ApiService.saveSession(token, userData);
        notifyListeners();
        return true;
      }
    }

    _errorMessage = response['message'] ?? 'Registrasi gagal. Silakan coba lagi.';
    notifyListeners();
    return false;
  }

  Future<void> logout() async {
    _user = null;
    _role = null;
    await ApiService.clearSession();
    notifyListeners();
  }
}
