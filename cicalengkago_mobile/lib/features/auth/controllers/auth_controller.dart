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

  // OTP Login Verification State
  bool _requiresOtp = false;
  String? _otpPhoneMasked;
  String? _otpChannel;

  bool get isLoading => _isLoading;
  bool get isInitialized => _isInitialized;
  String? get errorMessage => _errorMessage;
  Map<String, dynamic>? get user => _user;
  String? get role => _role;
  bool get isLoggedIn => _user != null;
  bool get requiresOtp => _requiresOtp;
  String? get otpPhoneMasked => _otpPhoneMasked;
  String? get otpChannel => _otpChannel;

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

  void resetOtpState() {
    _requiresOtp = false;
    _otpPhoneMasked = null;
    _otpChannel = null;
    _errorMessage = null;
    notifyListeners();
  }

  Future<bool> login(String username, String password) async {
    _isLoading = true;
    _errorMessage = null;
    _requiresOtp = false;
    _otpPhoneMasked = null;
    _otpChannel = null;
    notifyListeners();

    final response = await ApiService.postForm(ApiConstants.login, {
      'username': username,
      'password': password,
    });

    _isLoading = false;

    // Check if OTP is required by backend setting
    final bool isOtpRequired = response['require_otp'] == true ||
        response['data']?['require_otp'] == true;

    if (isOtpRequired) {
      _requiresOtp = true;
      _otpPhoneMasked = response['phone_masked']?.toString() ??
          response['data']?['phone_masked']?.toString() ??
          username;
      _otpChannel = response['channel']?.toString() ??
          response['data']?['channel']?.toString() ??
          'whatsapp';
      notifyListeners();
      return true; // Successfully triggered OTP step
    }

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

  Future<bool> verifyOtp(String otp) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    final response = await ApiService.postForm(ApiConstants.verifyOtp, {
      'otp': otp,
    });

    _isLoading = false;

    if (response['success'] == true) {
      final token = response['token']?.toString() ?? response['data']?['token']?.toString();
      final userData = (response['data']?['user'] ?? response['data'] ?? response['user']) as Map<String, dynamic>?;

      if (userData != null) {
        _user = userData;
        _role = userData['role']?.toString().toLowerCase() ?? 'customer';
        _requiresOtp = false;
        _otpPhoneMasked = null;
        _otpChannel = null;
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
      _requiresOtp = false;
      _otpPhoneMasked = null;
      _otpChannel = null;
      await ApiService.saveSession(userData, token: token);
      notifyListeners();
      return true;
    }

    _errorMessage = response['message'] ?? 'Kode OTP tidak valid atau telah kedaluwarsa.';
    notifyListeners();
    return false;
  }

  Future<bool> resendOtp() async {
    final response = await ApiService.postForm(ApiConstants.resendOtp, {});
    if (response['success'] == true) {
      return true;
    }
    _errorMessage = response['message'] ?? 'Gagal mengirim ulang OTP.';
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
    _requiresOtp = false;
    _otpPhoneMasked = null;
    _otpChannel = null;
    // Panggil endpoint logout server agar session dihapus di PHP
    try {
      await ApiService.get(ApiConstants.logout);
    } catch (_) {}
    await ApiService.clearSession();
    notifyListeners();
  }
}
