import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_alert.dart';
import '../controllers/customer_controller.dart';
import '../../common/screens/in_app_chat_modal.dart';
import '../../auth/controllers/auth_controller.dart';

class CustomerChatListScreen extends StatefulWidget {
  const CustomerChatListScreen({super.key});
  @override
  State<CustomerChatListScreen> createState() => _CustomerChatListScreenState();
}

class _CustomerChatListScreenState extends State<CustomerChatListScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchOrders();
      context.read<CustomerController>().fetchNotifications();
    });
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<CustomerController>();
    final auth = context.watch<AuthController>();
    final orders = ctrl.orders;
    final unread = ctrl.unreadNotifCount;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Chat', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: Color(0xFF0F172A))),
            if (unread > 0) Text('$unread pesan belum dibaca', style: const TextStyle(fontSize: 11, color: AppTheme.primaryRed, fontWeight: FontWeight.w600)),
          ],
        ),
        actions: [
          IconButton(
            onPressed: () => ctrl.fetchOrders(),
            icon: Container(padding: const EdgeInsets.all(7), decoration: const BoxDecoration(color: Color(0xFFF1F5F9), shape: BoxShape.circle), child: const Icon(Icons.refresh_rounded, size: 18, color: Color(0xFF475569))),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: RefreshIndicator(
        color: AppTheme.primaryRed,
        onRefresh: () async => await ctrl.fetchOrders(),
        child: orders.isEmpty
            ? ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(28),
                children: [
                  const SizedBox(height: 40),
                  Container(
                    width: 84, height: 84,
                    decoration: BoxDecoration(color: const Color(0xFFFFF7ED), shape: BoxShape.circle, border: Border.all(color: const Color(0xFFFFEDD5), width: 2)),
                    child: const Icon(Icons.chat_bubble_outline_rounded, color: AppTheme.primaryRed, size: 36),
                  ),
                  const SizedBox(height: 16),
                  const Text('Belum ada chat', textAlign: TextAlign.center, style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Color(0xFF0F172A))),
                  const SizedBox(height: 6),
                  const Text('Chat dengan toko & driver muncul\ndari pesanan kamu.', textAlign: TextAlign.center, style: TextStyle(fontSize: 12.5, color: Color(0xFF64748B), height: 1.4)),
                  const SizedBox(height: 18),
                  Center(
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primaryRed, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 12), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(25))),
                      onPressed: () => DefaultTabController.of(context) != null ? null : null,
                      child: const Text('Lihat Pesanan', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 12.5)),
                    ),
                  ),
                ],
              )
            : ListView.separated(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                itemCount: orders.length,
                separatorBuilder: (_, __) => const SizedBox(height: 10),
                itemBuilder: (_, i) {
                  final o = orders[i] as Map;
                  final code = (o['order_code'] ?? o['code'] ?? '-').toString();
                  final status = (o['status'] ?? 'pending').toString();
                  final storeName = (o['store_name'] ?? o['store'] ?? 'Toko').toString();
                  final total = o['total'] ?? o['grand_total'] ?? o['amount'] ?? 0;
                  final isActive = ['pending','confirmed','processing','handover','picked_up','on_the_way'].contains(status);
                  return InkWell(
                    onTap: () {
                      final uid = int.tryParse(auth.user?['id']?.toString() ?? '') ?? 0;
                      if (uid == 0) {
                        AppAlert.showWarning(context, title: 'Masuk dulu');
                        return;
                      }
                      InAppChatModal.show(
                        context,
                        orderCode: code,
                        currentUserId: uid,
                        currentUserRole: auth.role ?? 'customer',
                      );
                    },
                    borderRadius: BorderRadius.circular(16),
                    child: Container(
                      padding: const EdgeInsets.all(13),
                      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: const Color(0xFFE2E8F0))),
                      child: Row(
                        children: [
                          Container(width: 44, height: 44, decoration: BoxDecoration(color: AppTheme.primaryRed.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)), child: const Icon(Icons.storefront_rounded, color: AppTheme.primaryRed, size: 22)),
                          const SizedBox(width: 11),
                          Expanded(
                            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                              Text(storeName, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800, color: Color(0xFF0F172A))),
                              const SizedBox(height: 2),
                              Text('$code • $status', style: TextStyle(fontSize: 11, color: isActive ? const Color(0xFF16A34A) : const Color(0xFF64748B), fontWeight: FontWeight.w600)),
                              Text('Rp ${total.toString()}', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                            ]),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                            decoration: BoxDecoration(color: AppTheme.primaryRed, borderRadius: BorderRadius.circular(20)),
                            child: const Row(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.chat_bubble_rounded, size: 13, color: Colors.white), SizedBox(width: 5), Text('Chat', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Colors.white))]),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
      ),
    );
  }
}
