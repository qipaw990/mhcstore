import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
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

  void init(BuildContext context, {int? userId, String? orderCode}) {
    _navigatorContext = context;
    if (userId != null) _userId = userId;
    if (orderCode != null) _orderCode = orderCode;

    WidgetsBinding.instance.addObserver(this);
    startPolling();
  }

  void updateContext(BuildContext context) {
    _navigatorContext = context;
  }

  void setUserAndOrder({int? userId, String? orderCode}) {
    if (userId != null) _userId = userId;
    if (orderCode != null) _orderCode = orderCode;
  }

  void startPolling() {
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(const Duration(seconds: 3), (_) {
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
      startPolling();
    }
  }

  Future<void> checkIncomingCall() async {
    try {
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
      } else {
        return;
      }

      final res = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 4));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['success'] == true && data['data'] != null && data['data']['active_call'] != null) {
          final call = data['data']['active_call'] as Map<String, dynamic>;
          final callStatus = call['status'];

          if (callStatus == 'calling' || callStatus == 'connected') {
            _activeCallData = call;
            notifyListeners();

            // Auto show call UI if receiving an incoming call or active call
            if (!_isCallScreenOpen) {
              final isIncoming = (callStatus == 'calling' && _userId != null && (call['receiver_id'] == _userId || call['receiver_id'].toString() == _userId.toString()));
              if (isIncoming) {
                openCallScreen(_navigatorContext, orderCode: call['order_code'] ?? _orderCode ?? '', isIncoming: true, callData: call);
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
    } catch (_) {}
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
    if (_isCallScreenOpen) return;
    final targetContext = (context != null && context.mounted) ? context : rootNavigatorKey.currentContext;
    if (targetContext == null) return;

    _isCallScreenOpen = true;

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

