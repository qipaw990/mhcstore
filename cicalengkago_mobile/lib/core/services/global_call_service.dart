import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../constants/api_constants.dart';
import '../../features/common/screens/in_app_call_screen.dart';
import '../../main.dart';

class GlobalCallService extends ChangeNotifier with WidgetsBindingObserver {
  static final GlobalCallService instance = GlobalCallService._internal();
  factory GlobalCallService() => instance;
  GlobalCallService._internal();

  Timer? _pollTimer;
  Map<String, dynamic>? _activeCallData;
  bool _isCallScreenOpen = false;
  int? _userId;
  String? _orderCode;
  BuildContext? _navigatorContext;

  Map<String, dynamic>? get activeCallData => _activeCallData;
  bool get hasActiveCall => _activeCallData != null;
  int? get currentUserId => _userId;

  void init(BuildContext context, {int? userId, String? orderCode}) {
    _navigatorContext = context;
    if (userId != null && userId > 0) _userId = userId;
    if (orderCode != null && orderCode.isNotEmpty) _orderCode = orderCode;

    debugPrint('🔔 [GlobalCallService] Initialized (userId: $_userId, orderCode: $_orderCode)');

    WidgetsBinding.instance.addObserver(this);
    startPolling();
  }

  void updateContext(BuildContext context) {
    _navigatorContext = context;
  }

  void setUserAndOrder({int? userId, String? orderCode}) {
    if (userId != null && userId > 0) _userId = userId;
    if (orderCode != null && orderCode.isNotEmpty) _orderCode = orderCode;
    debugPrint('👤 [GlobalCallService] Updated user/order (userId: $_userId, orderCode: $_orderCode)');
  }

  void setCallScreenOpen(bool open) {
    _isCallScreenOpen = open;
  }

  void setCallScreenClosed() {
    _isCallScreenOpen = false;
    _activeCallData = null;
    notifyListeners();
  }

  Future<void> _ensureUserId() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      if (_userId == null || _userId! <= 0) {
        final directId = prefs.getString('user_id');
        if (directId != null && directId.isNotEmpty) {
          _userId = int.tryParse(directId);
        }
        if (_userId == null || _userId! <= 0) {
          final userStr = prefs.getString('user_data');
          if (userStr != null && userStr.isNotEmpty) {
            final u = jsonDecode(userStr);
            final id = int.tryParse(u['id']?.toString() ?? '');
            if (id != null && id > 0) {
              _userId = id;
            }
          }
        }
        if (_userId != null && _userId! > 0) {
          debugPrint('🔑 [GlobalCallService] Resolved userId from SharedPreferences: $_userId');
        }
      }

      if (_orderCode == null || _orderCode!.isEmpty) {
        final lastOrder = prefs.getString('active_order_code') ?? prefs.getString('last_order_code');
        if (lastOrder != null && lastOrder.isNotEmpty) {
          _orderCode = lastOrder;
          debugPrint('📦 [GlobalCallService] Resolved orderCode from SharedPreferences: $_orderCode');
        }
      }
    } catch (_) {}
  }

  void startPolling() {
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(const Duration(seconds: 2), (_) {
      checkIncomingCall();
    });
    checkIncomingCall();
  }

  void stopPolling() {
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      debugPrint('📱 [GlobalCallService] App resumed -> checking incoming calls immediately');
      startPolling();
    }
  }

  Future<void> checkIncomingCall() async {
    try {
      await _ensureUserId();

      String url = '${ApiConstants.baseUrl}/calls/poll';
      List<String> queryParams = [];
      if (_orderCode != null && _orderCode!.isNotEmpty) {
        queryParams.add('order_code=${Uri.encodeComponent(_orderCode!)}');
      }
      if (_userId != null && _userId! > 0) {
        queryParams.add('user_id=$_userId');
      }
      if (queryParams.isNotEmpty) {
        url += '?${queryParams.join('&')}';
      }

      final prefs = await SharedPreferences.getInstance();
      final cookie = prefs.getString('php_session_cookie');
      final headers = <String, String>{};
      if (cookie != null && cookie.isNotEmpty) {
        headers['Cookie'] = cookie;
      }

      final res = await http.get(Uri.parse(url), headers: headers).timeout(const Duration(seconds: 4));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['success'] == true && data['data'] != null && data['data']['active_call'] != null) {
          final call = data['data']['active_call'] as Map<String, dynamic>;
          final callStatus = call['status']?.toString();
          final callId = call['id'];
          final receiverId = int.tryParse(call['receiver_id']?.toString() ?? '0');
          final callerId = int.tryParse(call['caller_id']?.toString() ?? '0');

          debugPrint('📞 [GlobalCallService] Active call found (ID: $callId, status: $callStatus, caller: $callerId, receiver: $receiverId, myUserId: $_userId)');

          if (callStatus == 'calling' || callStatus == 'connected') {
            _activeCallData = call;
            notifyListeners();

            // Auto show call UI if receiving an incoming call and screen not yet open
            if (!_isCallScreenOpen) {
              final isCaller = (_userId != null && _userId! > 0 && callerId == _userId);
              final isReceiver = (_userId != null && _userId! > 0 && receiverId == _userId);
              final isMatchingOrder = (_orderCode != null && _orderCode!.isNotEmpty && _orderCode == call['order_code']);

              final isIncoming = (callStatus == 'calling' && !isCaller && (isReceiver || isMatchingOrder));
              
              if (isIncoming) {
                debugPrint('🚨 [GlobalCallService] INCOMING CALL DETECTED! Opening call popup for order ${call['order_code']} (Caller: ${call['caller_name']})...');
                openCallScreen(
                  _navigatorContext,
                  orderCode: call['order_code'] ?? _orderCode ?? '',
                  isIncoming: true,
                  initialPartnerName: call['caller_name'] ?? 'Panggilan Masuk',
                  initialPartnerAvatar: call['caller_avatar'] ?? '',
                  callData: call,
                );
              }
            }
          } else {
            if (_activeCallData != null) {
              _activeCallData = null;
              notifyListeners();
            }
          }
        } else {
          if (_activeCallData != null) {
            _activeCallData = null;
            notifyListeners();
          }
        }
      }
    } catch (e) {
      debugPrint('⚠️ [GlobalCallService] Poll error: $e');
    }
  }

  void openCallScreen(
    BuildContext? context, {
    required String orderCode,
    required bool isIncoming,
    String? callerRole,
    String? initialPartnerName,
    String? initialPartnerAvatar,
    Map<String, dynamic>? callData,
  }) {
    if (_isCallScreenOpen) {
      debugPrint('⚠️ [GlobalCallService] Call screen already open, skipping duplicate push');
      return;
    }
    final targetContext = (context != null && context.mounted) ? context : rootNavigatorKey.currentContext;
    if (targetContext == null) {
      debugPrint('❌ [GlobalCallService] Cannot open call screen: context is null');
      return;
    }

    _isCallScreenOpen = true;
    debugPrint('🚀 [GlobalCallService] Navigating to InAppCallScreen (isIncoming: $isIncoming, orderCode: $orderCode, partner: $initialPartnerName)');

    Navigator.of(targetContext, rootNavigator: true).push(
      MaterialPageRoute(
        builder: (_) => InAppCallScreen(
          orderCode: orderCode,
          isIncoming: isIncoming,
          callerRole: callerRole,
          initialPartnerName: initialPartnerName,
          initialPartnerAvatar: initialPartnerAvatar,
          callData: callData ?? _activeCallData,
        ),
      ),
    ).then((_) {
      debugPrint('🏁 [GlobalCallService] InAppCallScreen closed');
      _isCallScreenOpen = false;
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    stopPolling();
    super.dispose();
  }
}
