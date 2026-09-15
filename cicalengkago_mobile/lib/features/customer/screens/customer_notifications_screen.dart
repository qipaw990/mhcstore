import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../controllers/customer_controller.dart';

class CustomerNotificationsScreen extends StatefulWidget {
  const CustomerNotificationsScreen({super.key});

  @override
  State<CustomerNotificationsScreen> createState() => _CustomerNotificationsScreenState();
}

class _CustomerNotificationsScreenState extends State<CustomerNotificationsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchNotifications();
    });
  }

  IconData _getNotifIcon(String? type) {
    switch (type) {
      case 'order': return Icons.receipt_rounded;
      case 'promo': return Icons.local_offer_rounded;
      case 'payment': return Icons.credit_card_rounded;
      case 'delivery': return Icons.delivery_dining_rounded;
      default: return Icons.notifications_rounded;
    }
  }

  Color _getNotifColor(String? type) {
    switch (type) {
      case 'order':    return AppTheme.brandOrange;
      case 'promo':    return AppTheme.brandAmber;
      case 'payment':  return AppTheme.successGreen;
      case 'delivery': return AppTheme.infoBlu;
      default:         return const Color(0xFF7C3AED);
    }
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<CustomerController>();
    final notifications = ctrl.notifications;
    final unreadCount = notifications.where((n) => n['is_read'] != true && n['is_read'] != 1).length;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 1,
        centerTitle: false,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Notifikasi',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w800,
                color: Color(0xFF0F172A),
                letterSpacing: -0.3,
              ),
            ),
            if (unreadCount > 0)
              Text(
                '$unreadCount pesan belum dibaca',
                style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w500, color: AppTheme.brandOrange),
              ),
          ],
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: IconButton(
              tooltip: 'Perbarui',
              icon: Container(
                padding: const EdgeInsets.all(7),
                decoration: const BoxDecoration(color: Color(0xFFF1F5F9), shape: BoxShape.circle),
                child: const Icon(Icons.refresh_rounded, color: Color(0xFF475569), size: 18),
              ),
              onPressed: () => ctrl.fetchNotifications(),
            ),
          ),
        ],
      ),
      body: CustomScrollView(
        slivers: [

          if (notifications.isEmpty)
            SliverFillRemaining(
              hasScrollBody: false,
              child: Center(
                child: Padding(
                  padding: const EdgeInsets.all(40),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        width: 88,
                        height: 88,
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFF7ED),
                          shape: BoxShape.circle,
                          border: Border.all(color: const Color(0xFFFFEDD5), width: 2),
                        ),
                        child: const Icon(Icons.notifications_off_outlined, color: AppTheme.brandOrange, size: 40),
                      ),
                      const SizedBox(height: 20),
                      const Text('Belum Ada Notifikasi',
                          style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: Color(0xFF0F172A))),
                      const SizedBox(height: 8),
                      const Text(
                        'Promo, update pesanan, dan info penting\nakan muncul di sini.',
                        style: TextStyle(fontSize: 13, color: Color(0xFF64748B), height: 1.5),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                ),
              ),
            )
          else
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
              sliver: SliverList(
                delegate: SliverChildBuilderDelegate(
                  (context, index) {
                    final notif = notifications[index];
                    final isRead = notif['is_read'] == true || notif['is_read'] == 1;
                    final type = notif['type']?.toString();
                    final color = _getNotifColor(type);
                    return _NotifCard(
                      notif: notif, isRead: isRead, color: color,
                      icon: _getNotifIcon(type), formatTime: _formatTime,
                    );
                  },
                  childCount: notifications.length,
                ),
              ),
            ),
        ],
      ),
    );
  }

  String _formatTime(String? dateStr) {
    if (dateStr == null) return '';
    try {
      final dt = DateTime.parse(dateStr).toLocal();
      final now = DateTime.now();
      final diff = now.difference(dt);
      if (diff.inMinutes < 1)  return 'Baru saja';
      if (diff.inMinutes < 60) return '${diff.inMinutes} mnt lalu';
      if (diff.inHours < 24)   return '${diff.inHours} jam lalu';
      if (diff.inDays < 7)     return '${diff.inDays} hari lalu';
      return '${dt.day}/${dt.month}/${dt.year}';
    } catch (_) {
      return dateStr;
    }
  }
}

// ─── Notif Card Widget ────────────────────────────────────────────────────────
class _NotifCard extends StatelessWidget {
  final Map<String, dynamic> notif;
  final bool isRead;
  final Color color;
  final IconData icon;
  final String Function(String?) formatTime;

  const _NotifCard({
    required this.notif,
    required this.isRead,
    required this.color,
    required this.icon,
    required this.formatTime,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: isRead ? Colors.white : const Color(0xFFFFF3EC),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: isRead ? AppTheme.cardBorder : AppTheme.brandOrange.withValues(alpha: 0.3),
          width: isRead ? 1 : 1.5,
        ),
        boxShadow: AppTheme.cardShadow,
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Icon circle
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(icon, color: color, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          notif['title'] ?? 'Notifikasi',
                          style: TextStyle(
                            fontSize: 13.5,
                            fontWeight: isRead ? FontWeight.w600 : FontWeight.w800,
                            color: AppTheme.textInk,
                          ),
                        ),
                      ),
                      if (!isRead)
                        Container(
                          width: 9, height: 9,
                          decoration: BoxDecoration(
                            color: AppTheme.brandOrange,
                            shape: BoxShape.circle,
                            boxShadow: [BoxShadow(color: AppTheme.brandOrange.withValues(alpha: 0.4), blurRadius: 4)],
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    notif['message'] ?? notif['body'] ?? '',
                    style: const TextStyle(fontSize: 12, color: AppTheme.textBody, height: 1.4),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Icon(Icons.access_time_rounded, size: 11, color: AppTheme.textMute),
                      const SizedBox(width: 3),
                      Text(
                        formatTime(notif['created_at']?.toString()),
                        style: const TextStyle(fontSize: 10.5, color: AppTheme.textMute),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
