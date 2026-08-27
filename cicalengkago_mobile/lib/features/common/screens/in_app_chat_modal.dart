import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/services/global_call_service.dart';

class InAppChatModal extends StatefulWidget {
  final String orderCode;
  final int currentUserId;
  final String currentUserRole;

  const InAppChatModal({
    super.key,
    required this.orderCode,
    required this.currentUserId,
    required this.currentUserRole,
  });

  static void show(BuildContext context, {required String orderCode, required int currentUserId, required String currentUserRole}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
        child: FractionallySizedBox(
          heightFactor: 0.82,
          child: InAppChatModal(
            orderCode: orderCode,
            currentUserId: currentUserId,
            currentUserRole: currentUserRole,
          ),
        ),
      ),
    );
  }

  @override
  State<InAppChatModal> createState() => _InAppChatModalState();
}

class _InAppChatModalState extends State<InAppChatModal> {
  final TextEditingController _msgCtrl = TextEditingController();
  final ScrollController _scrollCtrl = ScrollController();

  List<dynamic> _messages = [];
  Map<String, dynamic>? _partnerInfo;
  Timer? _pollTimer;
  bool _isLoading = true;
  bool _isSending = false;

  @override
  void initState() {
    super.initState();
    _fetchMessages();
    _startPolling();
  }

  void _startPolling() {
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(const Duration(seconds: 2), (_) {
      _fetchMessages(isPoll: true);
    });
  }

  Future<void> _fetchMessages({bool isPoll = false}) async {
    try {
      final url = '${ApiConstants.baseUrl}/chats/messages?order_code=${widget.orderCode}&mark_read=1&user_id=${widget.currentUserId}&user_role=${widget.currentUserRole}';
      final res = await http.get(Uri.parse(url));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['success'] == true && data['data'] != null) {
          final newMsgs = (data['data']['messages'] as List<dynamic>?) ?? [];
          final partner = data['data']['partner'] as Map<String, dynamic>?;

          if (mounted) {
            setState(() {
              _messages = newMsgs;
              if (partner != null) _partnerInfo = partner;
              _isLoading = false;
            });
            if (!isPoll) _scrollToBottom();
          }
        }
      }
    } catch (_) {
      if (mounted && !isPoll) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _sendMessage() async {
    final text = _msgCtrl.text.trim();
    if (text.isEmpty || _isSending) return;

    setState(() => _isSending = true);
    _msgCtrl.clear();

    try {
      final url = '${ApiConstants.baseUrl}/chats/send';
      final res = await http.post(
        Uri.parse(url),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'order_code': widget.orderCode,
          'message': text,
          'user_id': widget.currentUserId,
          'user_role': widget.currentUserRole,
        }),
      );

      if (res.statusCode == 200) {
        await _fetchMessages(isPoll: true);
        _scrollToBottom();
      }
    } catch (_) {}

    if (mounted) setState(() => _isSending = false);
  }

  void _scrollToBottom() {
    Future.delayed(const Duration(milliseconds: 100), () {
      if (_scrollCtrl.hasClients) {
        _scrollCtrl.animateTo(
          _scrollCtrl.position.maxScrollExtent,
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeOut,
        );
      }
    });
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _msgCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final partnerName = _partnerInfo?['name'] ?? 'Partner Pesanan';
    final partnerRole = _partnerInfo?['role_label'] ?? 'CicalengkaGO';
    final partnerAvatar = ApiConstants.formatImageUrl(_partnerInfo?['avatar'] ?? '');

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        children: [
          // Drag indicator
          Container(
            margin: const EdgeInsets.only(top: 10, bottom: 4),
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: const Color(0xFFCBD5E1),
              borderRadius: BorderRadius.circular(2),
            ),
          ),

          // Header Bar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            decoration: const BoxDecoration(
              border: Border(bottom: BorderSide(color: Color(0xFFF1F5F9))),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 20,
                  backgroundColor: const Color(0xFFFEE2E2),
                  backgroundImage: partnerAvatar.isNotEmpty ? NetworkImage(partnerAvatar) : null,
                  child: partnerAvatar.isEmpty ? const Icon(Icons.person, color: AppTheme.primaryRed, size: 22) : null,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        partnerName,
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        partnerRole,
                        style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                      ),
                    ],
                  ),
                ),
                // Voice Call Action Button
                GestureDetector(
                  onTap: () {
                    GlobalCallService.instance.openCallScreen(
                      context,
                      orderCode: widget.orderCode,
                      isIncoming: false,
                    );
                  },
                  child: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFEFF6FF),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.phone_in_talk_rounded, color: Color(0xFF2563EB), size: 18),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton(
                  icon: const Icon(Icons.close_rounded, color: Color(0xFF64748B)),
                  onPressed: () => Navigator.of(context).pop(),
                ),
              ],
            ),
          ),

          // Message List
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryRed))
                : _messages.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              padding: const EdgeInsets.all(16),
                              decoration: const BoxDecoration(
                                color: Color(0xFFF8FAFC),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.chat_bubble_outline_rounded, size: 36, color: Color(0xFF94A3B8)),
                            ),
                            const SizedBox(height: 12),
                            const Text('Belum ada pesan', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                            const Text('Tanyakan posisi atau pesanan di sini', style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
                          ],
                        ),
                      )
                    : ListView.builder(
                        controller: _scrollCtrl,
                        padding: const EdgeInsets.all(16),
                        itemCount: _messages.length,
                        itemBuilder: (context, index) {
                          final msg = _messages[index];
                          final senderId = int.tryParse(msg['sender_id']?.toString() ?? '0') ?? 0;
                          final isMe = (senderId == widget.currentUserId);
                          final msgText = msg['message'] ?? '';
                          final timeStr = msg['created_at'] != null ? msg['created_at'].toString().split(' ').last.substring(0, 5) : '';

                          return Align(
                            alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
                            child: Container(
                              margin: const EdgeInsets.only(bottom: 10),
                              constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.75),
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                              decoration: BoxDecoration(
                                color: isMe ? AppTheme.primaryRed : const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.only(
                                  topLeft: const Radius.circular(16),
                                  topRight: const Radius.circular(16),
                                  bottomLeft: Radius.circular(isMe ? 16 : 4),
                                  bottomRight: Radius.circular(isMe ? 4 : 16),
                                ),
                              ),
                              child: Column(
                                crossAxisAlignment: isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    msgText,
                                    style: TextStyle(
                                      color: isMe ? Colors.white : const Color(0xFF0F172A),
                                      fontSize: 13,
                                      height: 1.3,
                                    ),
                                  ),
                                  const SizedBox(height: 3),
                                  Text(
                                    timeStr,
                                    style: TextStyle(
                                      color: isMe ? Colors.white70 : const Color(0xFF94A3B8),
                                      fontSize: 9.5,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
          ),

          // Input Bar
          Container(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
            decoration: const BoxDecoration(
              color: Colors.white,
              border: Border(top: BorderSide(color: Color(0xFFF1F5F9))),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(24),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: TextField(
                      controller: _msgCtrl,
                      decoration: const InputDecoration(
                        hintText: 'Ketik pesan...',
                        hintStyle: TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
                        border: InputBorder.none,
                      ),
                      onSubmitted: (_) => _sendMessage(),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                GestureDetector(
                  onTap: _sendMessage,
                  child: Container(
                    width: 44,
                    height: 44,
                    decoration: const BoxDecoration(
                      color: AppTheme.primaryRed,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.send_rounded, color: Colors.white, size: 20),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
