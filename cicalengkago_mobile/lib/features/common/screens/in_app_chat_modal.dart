import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/services/global_call_service.dart';

class InAppChatModal extends StatefulWidget {
  final String? orderCode;
  final int? storeId;
  final String? initialStoreName;
  final String? initialStoreLogo;
  final int currentUserId;
  final String currentUserRole;
  /// 'driver' | 'store' | 'vendor' | '' — menentukan chat room mana yang dibuka
  final String targetRole;

  const InAppChatModal({
    super.key,
    this.orderCode,
    this.storeId,
    this.initialStoreName,
    this.initialStoreLogo,
    required this.currentUserId,
    required this.currentUserRole,
    this.targetRole = '',
  });

  static void show(
    BuildContext context, {
    String? orderCode,
    int? storeId,
    String? initialStoreName,
    String? initialStoreLogo,
    required int currentUserId,
    required String currentUserRole,
    String targetRole = '',
  }) {
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
            storeId: storeId,
            initialStoreName: initialStoreName,
            initialStoreLogo: initialStoreLogo,
            currentUserId: currentUserId,
            currentUserRole: currentUserRole,
            targetRole: targetRole,
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

  XFile? _selectedImage;
  Uint8List? _selectedImageBytes;

  bool get _isStoreChat =>
      (widget.orderCode == null || widget.orderCode!.isEmpty) &&
      widget.storeId != null &&
      widget.storeId! > 0;

  /// Resolusi target_role efektif:
  /// - Jika ini store chat (tanpa orderCode) → 'store'
  /// - Jika targetRole eksplisit disediakan → gunakan itu
  /// - Jika ada storeId dalam order chat → 'vendor'
  /// - Default → 'driver'
  String get _effectiveTargetRole {
    if (_isStoreChat) return 'store';
    if (widget.targetRole.isNotEmpty) return widget.targetRole;
    return 'driver';
  }

  @override
  void initState() {
    super.initState();
    if (widget.initialStoreName != null && widget.initialStoreName!.isNotEmpty) {
      _partnerInfo = {
        'name': widget.initialStoreName,
        'role_label': 'Mitra Toko / Resto',
        'avatar': widget.initialStoreLogo ?? '',
      };
    }
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
      final String url;
      if (_isStoreChat) {
        url = '${ApiConstants.baseUrl}/chats/store-messages?store_id=${widget.storeId}&mark_read=1&user_id=${widget.currentUserId}&user_role=${widget.currentUserRole}';
      } else {
        final targetParam = '&target_role=${_effectiveTargetRole}';
        final storeParam = (widget.storeId != null && widget.storeId! > 0) ? '&store_id=${widget.storeId}' : '';
        url = '${ApiConstants.baseUrl}/chats/messages?order_code=${widget.orderCode ?? ''}&mark_read=1&user_id=${widget.currentUserId}&user_role=${widget.currentUserRole}$targetParam$storeParam';
      }

      final res = await http.get(
        Uri.parse(url),
        headers: {'Accept': 'application/json'},
      );

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

  Future<void> _pickImage(ImageSource source) async {
    try {
      final picker = ImagePicker();
      final picked = await picker.pickImage(
        source: source,
        maxWidth: 1200,
        maxHeight: 1200,
        imageQuality: 80,
      );
      if (picked != null) {
        final bytes = await picked.readAsBytes();
        if (mounted) {
          setState(() {
            _selectedImage = picked;
            _selectedImageBytes = bytes;
          });
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gagal memilih foto: $e'),
            backgroundColor: AppTheme.primaryRed,
          ),
        );
      }
    }
  }

  void _showImageSourcePicker() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 36,
                  height: 4,
                  decoration: BoxDecoration(
                    color: const Color(0xFFCBD5E1),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              const Text(
                'Kirim Foto',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
              ),
              const SizedBox(height: 12),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: const BoxDecoration(
                    color: Color(0xFFFEE2E2),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.camera_alt_rounded, color: AppTheme.primaryRed),
                ),
                title: const Text('Ambil Foto Kamera', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                subtitle: const Text('Gunakan kamera secara langsung', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                onTap: () {
                  Navigator.of(ctx).pop();
                  _pickImage(ImageSource.camera);
                },
              ),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: const BoxDecoration(
                    color: Color(0xFFEFF6FF),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.photo_library_rounded, color: Color(0xFF2563EB)),
                ),
                title: const Text('Pilih dari Galeri', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                subtitle: const Text('Pilih gambar dari album HP', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                onTap: () {
                  Navigator.of(ctx).pop();
                  _pickImage(ImageSource.gallery);
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showFullImagePreview(String imageUrl) {
    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(12),
        child: Stack(
          alignment: Alignment.topRight,
          children: [
            InteractiveViewer(
              minScale: 0.5,
              maxScale: 4.0,
              child: Center(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: CachedNetworkImage(
                    imageUrl: imageUrl,
                    fit: BoxFit.contain,
                    placeholder: (_, __) => const Center(
                      child: CircularProgressIndicator(color: Colors.white),
                    ),
                    errorWidget: (_, __, ___) => const Center(
                      child: Icon(Icons.broken_image_rounded, color: Colors.white, size: 48),
                    ),
                  ),
                ),
              ),
            ),
            Positioned(
              top: 8,
              right: 8,
              child: IconButton(
                icon: const CircleAvatar(
                  backgroundColor: Colors.black54,
                  radius: 18,
                  child: Icon(Icons.close_rounded, color: Colors.white, size: 20),
                ),
                onPressed: () => Navigator.of(ctx).pop(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _sendMessage() async {
    final text = _msgCtrl.text.trim();
    final imageToSend = _selectedImage;
    final bytesToSend = _selectedImageBytes;

    if ((text.isEmpty && imageToSend == null) || _isSending) return;

    setState(() {
      _isSending = true;
      _selectedImage = null;
      _selectedImageBytes = null;
    });
    _msgCtrl.clear();

    try {
      final String url;
      if (_isStoreChat) {
        url = '${ApiConstants.baseUrl}/chats/store-send';
      } else {
        url = '${ApiConstants.baseUrl}/chats/send';
      }

      if (imageToSend != null && bytesToSend != null) {
        final request = http.MultipartRequest('POST', Uri.parse(url));
        request.headers['Accept'] = 'application/json';

        if (_isStoreChat) {
          request.fields['store_id'] = widget.storeId.toString();
          request.fields['message'] = text;
          request.fields['user_id'] = widget.currentUserId.toString();
          request.fields['user_role'] = widget.currentUserRole;
        } else {
          request.fields['order_code'] = widget.orderCode ?? '';
          request.fields['message'] = text;
          request.fields['user_id'] = widget.currentUserId.toString();
          request.fields['user_role'] = widget.currentUserRole;
          request.fields['target_role'] = _effectiveTargetRole;
          if (widget.storeId != null && widget.storeId! > 0) {
            request.fields['store_id'] = widget.storeId.toString();
          }
        }

        final multipartFile = http.MultipartFile.fromBytes(
          'file',
          bytesToSend,
          filename: imageToSend.name.isNotEmpty ? imageToSend.name : 'chat_photo.jpg',
        );
        request.files.add(multipartFile);

        final streamed = await request.send();
        final response = await http.Response.fromStream(streamed);

        if (response.statusCode == 200) {
          await _fetchMessages(isPoll: true);
          _scrollToBottom();
        }
      } else {
        final Map<String, dynamic> bodyPayload;

        if (_isStoreChat) {
          bodyPayload = {
            'store_id': widget.storeId,
            'message': text,
            'user_id': widget.currentUserId,
            'user_role': widget.currentUserRole,
          };
        } else {
          bodyPayload = {
            'order_code': widget.orderCode ?? '',
            'message': text,
            'user_id': widget.currentUserId,
            'user_role': widget.currentUserRole,
            'target_role': _effectiveTargetRole,
            if (widget.storeId != null && widget.storeId! > 0) 'store_id': widget.storeId,
          };
        }

        final res = await http.post(
          Uri.parse(url),
          headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
          body: jsonEncode(bodyPayload),
        );

        if (res.statusCode == 200) {
          await _fetchMessages(isPoll: true);
          _scrollToBottom();
        }
      }
    } catch (e) {
      debugPrint('[InAppChat] Error sending: $e');
    }

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
    final partnerName = _partnerInfo?['name'] ?? (_isStoreChat ? 'Mitra Toko' : 'Partner Pesanan');
    final partnerRole = _partnerInfo?['role_label'] ?? (_isStoreChat ? 'Mitra Merchant' : 'CicalengkaGO');
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
                if (widget.orderCode != null && widget.orderCode!.isNotEmpty)
                  IconButton(
                    icon: const Icon(Icons.phone_in_talk_rounded, color: Color(0xFF2563EB), size: 21),
                    tooltip: 'Telepon In-App',
                    onPressed: () {
                      final oCode = widget.orderCode!;
                      final role = widget.currentUserRole;
                      Navigator.of(context).pop();
                      GlobalCallService.instance.openCallScreen(
                        context,
                        orderCode: oCode,
                        isIncoming: false,
                        callerRole: role,
                      );
                    },
                  ),
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
                          final senderRole = (msg['sender_role'] ?? '').toString().toLowerCase();
                          final myRole = widget.currentUserRole.toLowerCase();
                          final bool isMe;
                          if (widget.currentUserId > 0 && senderId > 0) {
                            isMe = (senderId == widget.currentUserId);
                          } else if (myRole.isNotEmpty && senderRole.isNotEmpty) {
                            final isDriverRole = (myRole == 'driver' || myRole == 'delivery_man') &&
                                (senderRole == 'driver' || senderRole == 'delivery_man');
                            final isMerchantRole = (myRole == 'vendor' || myRole == 'merchant' || myRole == 'store') &&
                                (senderRole == 'vendor' || senderRole == 'merchant' || senderRole == 'store');
                            final isCustomerRole = (myRole == 'customer' && senderRole == 'customer');
                            isMe = (myRole == senderRole) || isDriverRole || isMerchantRole || isCustomerRole;
                          } else {
                            isMe = false;
                          }
                          final msgText = (msg['message'] ?? '').toString().trim();
                          final rawFile = (msg['file'] ?? '').toString().trim();
                          final hasImage = rawFile.isNotEmpty;
                          final imageUrl = hasImage ? ApiConstants.formatImageUrl(rawFile) : '';
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
                                  if (hasImage && imageUrl.isNotEmpty) ...[
                                    GestureDetector(
                                      onTap: () => _showFullImagePreview(imageUrl),
                                      child: ClipRRect(
                                        borderRadius: BorderRadius.circular(10),
                                        child: ConstrainedBox(
                                          constraints: const BoxConstraints(
                                            maxHeight: 220,
                                            maxWidth: 240,
                                          ),
                                          child: CachedNetworkImage(
                                            imageUrl: imageUrl,
                                            fit: BoxFit.cover,
                                            placeholder: (context, url) => Container(
                                              width: 180,
                                              height: 140,
                                              color: isMe ? Colors.white24 : const Color(0xFFE2E8F0),
                                              child: const Center(
                                                child: SizedBox(
                                                  width: 24,
                                                  height: 24,
                                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                                ),
                                              ),
                                            ),
                                            errorWidget: (context, url, error) => Container(
                                              padding: const EdgeInsets.all(10),
                                              color: isMe ? Colors.white12 : const Color(0xFFE2E8F0),
                                              child: Row(
                                                mainAxisSize: MainAxisSize.min,
                                                children: [
                                                  Icon(Icons.broken_image_rounded, size: 18, color: isMe ? Colors.white70 : Colors.grey),
                                                  const SizedBox(width: 6),
                                                  Text(
                                                    'Gagal memuat foto',
                                                    style: TextStyle(fontSize: 11, color: isMe ? Colors.white70 : Colors.grey),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ),
                                        ),
                                      ),
                                    ),
                                    if (msgText.isNotEmpty) const SizedBox(height: 6),
                                  ],
                                  if (msgText.isNotEmpty)
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

          // Selected Image Preview Strip
          if (_selectedImageBytes != null)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: const BoxDecoration(
                color: Color(0xFFF8FAFC),
                border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
              ),
              child: Row(
                children: [
                  Stack(
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(10),
                        child: Image.memory(
                          _selectedImageBytes!,
                          width: 54,
                          height: 54,
                          fit: BoxFit.cover,
                        ),
                      ),
                      Positioned(
                        top: 2,
                        right: 2,
                        child: GestureDetector(
                          onTap: () => setState(() {
                            _selectedImage = null;
                            _selectedImageBytes = null;
                          }),
                          child: Container(
                            decoration: const BoxDecoration(
                              color: Colors.black54,
                              shape: BoxShape.circle,
                            ),
                            padding: const EdgeInsets.all(2),
                            child: const Icon(Icons.close, color: Colors.white, size: 12),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Foto siap dikirim. Tambahkan pesan atau langsung tekan tombol kirim.',
                      style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                    ),
                  ),
                ],
              ),
            ),

          // Input Bar (Wrapped in SafeArea agar tidak tertutup navigation bar HP)
          SafeArea(
            top: false,
            child: Container(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
              decoration: const BoxDecoration(
                color: Colors.white,
                border: Border(top: BorderSide(color: Color(0xFFF1F5F9), width: 1.5)),
                boxShadow: [
                  BoxShadow(
                    color: Color(0x0A000000),
                    blurRadius: 6,
                    offset: Offset(0, -2),
                  ),
                ],
              ),
              child: Row(
                children: [
                  // Tombol Lampirkan Foto (Desain kontras tinggi, background merah muda, border merah tegas)
                  Material(
                    color: Colors.transparent,
                    child: InkWell(
                      borderRadius: BorderRadius.circular(22),
                      onTap: _isSending ? null : _showImageSourcePicker,
                      child: Container(
                        width: 42,
                        height: 42,
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEE2E2),
                          shape: BoxShape.circle,
                          border: Border.all(color: const Color(0xFFEF4444), width: 1.5),
                          boxShadow: const [
                            BoxShadow(
                              color: Color(0x22EF4444),
                              blurRadius: 4,
                              offset: Offset(0, 2),
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.add_a_photo_rounded,
                          color: AppTheme.primaryRed,
                          size: 21,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),

                  // Kotak Input Teks + Ikon Kamera Cepat
                  Expanded(
                    child: Container(
                      padding: const EdgeInsets.only(left: 14, right: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(24),
                        border: Border.all(color: const Color(0xFFCBD5E1), width: 1),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _msgCtrl,
                              decoration: const InputDecoration(
                                hintText: 'Ketik pesan...',
                                hintStyle: TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
                                border: InputBorder.none,
                                isDense: true,
                                contentPadding: EdgeInsets.symmetric(vertical: 11),
                              ),
                              onSubmitted: (_) => _sendMessage(),
                            ),
                          ),
                          IconButton(
                            icon: const Icon(Icons.camera_alt_rounded, color: Color(0xFF64748B), size: 20),
                            tooltip: 'Kamera',
                            padding: const EdgeInsets.all(6),
                            constraints: const BoxConstraints(),
                            onPressed: _isSending ? null : () => _pickImage(ImageSource.camera),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),

                  // Tombol Kirim Pesan
                  GestureDetector(
                    onTap: _isSending ? null : _sendMessage,
                    child: Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: _isSending ? Colors.grey : AppTheme.primaryRed,
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: _isSending ? const Color(0x339E9E9E) : const Color(0x4DEE2737),
                            blurRadius: 6,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: _isSending
                          ? const Center(
                              child: SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                              ),
                            )
                          : const Icon(Icons.send_rounded, color: Colors.white, size: 20),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
