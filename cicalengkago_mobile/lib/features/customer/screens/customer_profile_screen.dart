import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:image_picker/image_picker.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
import '../../../core/widgets/require_auth_widget.dart';
import '../../../core/widgets/uber_pill_button.dart';
import '../../auth/controllers/auth_controller.dart';
import '../../auth/screens/login_screen.dart';
import '../../auth/screens/splash_screen.dart';
import '../controllers/customer_controller.dart';
import 'customer_orders_screen.dart';
import 'customer_wallet_screen.dart';
import 'vouchers_screen.dart';
import 'customer_notifications_screen.dart';
import 'package:url_launcher/url_launcher.dart';

class CustomerProfileScreen extends StatefulWidget {
  const CustomerProfileScreen({super.key});

  @override
  State<CustomerProfileScreen> createState() => _CustomerProfileScreenState();
}

class _CustomerProfileScreenState extends State<CustomerProfileScreen> {
  File? _selectedAvatarFile;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final ctrl = context.read<CustomerController>();
      final authCtrl = context.read<AuthController>();
      ctrl.fetchProfile().then((_) {
        if (mounted && ctrl.profile != null) {
          authCtrl.updateUser(ctrl.profile!);
        }
      });
      ctrl.fetchWallet();
      ctrl.fetchNotifications();
      ctrl.fetchCoupons();
    });
  }

  String _formatRupiah(num number) {
    final str = number.toInt().toString();
    final buffer = StringBuffer();
    for (int i = 0; i < str.length; i++) {
      if (i > 0 && (str.length - i) % 3 == 0) {
        buffer.write('.');
      }
      buffer.write(str[i]);
    }
    return 'Rp ${buffer.toString()}';
  }

  void _showEditProfileModal(
    BuildContext context,
    Map<String, dynamic>? user,
    CustomerController ctrl,
    AuthController authCtrl,
  ) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _EditProfileModalSheet(
        user: user,
        ctrl: ctrl,
        authCtrl: authCtrl,
        initialAvatarFile: _selectedAvatarFile,
        onAvatarUpdated: (file) {
          if (mounted) {
            setState(() {
              _selectedAvatarFile = file;
            });
          }
        },
      ),
    );
  }

  Future<void> _launchWhatsAppSupport({String? customMessage}) async {
    final message = customMessage ?? 'Halo CS CicalengkaGO, saya butuh bantuan mengenai aplikasi / pesanan saya.';
    final url = 'https://wa.me/6285158397756?text=${Uri.encodeComponent(message)}';
    try {
      final uri = Uri.parse(url);
      if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
        await launchUrl(uri);
      }
    } catch (e) {
      debugPrint('[WhatsAppSupport] Error launching WA: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    final authCtrl = context.watch<AuthController>();
    final ctrl = context.watch<CustomerController>();

    final Map<String, dynamic> mergedUser = {};
    if (authCtrl.user != null) {
      if (authCtrl.user!.containsKey('user') && authCtrl.user!['user'] is Map) {
        mergedUser.addAll(Map<String, dynamic>.from(authCtrl.user!['user'] as Map));
      } else {
        mergedUser.addAll(authCtrl.user!);
      }
    }
    if (ctrl.profile != null) {
      if (ctrl.profile!.containsKey('user') && ctrl.profile!['user'] is Map) {
        mergedUser.addAll(Map<String, dynamic>.from(ctrl.profile!['user'] as Map));
      } else {
        mergedUser.addAll(ctrl.profile!);
      }
    }

    final user = mergedUser;
    final name = user['name'] ?? user['username'] ?? 'Pengguna CicalengkaGO';
    final email = user['email'] ?? '-';
    final phone = user['phone'] ?? user['no_hp'] ?? '-';
    final rawAvatar = user['avatar'] ?? user['profile_photo_url'];

    String? avatarUrl;
    if (rawAvatar != null && rawAvatar.toString().trim().isNotEmpty) {
      avatarUrl = ApiConstants.formatImageUrl(rawAvatar.toString().trim());
    }

    final walletBalance = num.tryParse(ctrl.wallet?['balance']?.toString() ?? '0') ?? 0;

    return RequireAuthWidget(
      title: 'Profil Saya',
      subtitle: 'Masuk ke akun CicalengkaGO Anda untuk mengelola profil, alamat pengiriman, dan pengaturan akun.',
      icon: Icons.person_outline_rounded,
      child: Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0.5,
        titleSpacing: 16,
        title: const Text(
          'Akun Saya',
          style: TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 15,
            fontWeight: FontWeight.bold,
            letterSpacing: -0.3,
          ),
        ),
        actions: [
          InkWell(
            onTap: () => _showClubBenefitsModal(context),
            borderRadius: BorderRadius.circular(20),
            child: Container(
              margin: const EdgeInsets.only(right: 16),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFEE2737), Color(0xFFC61524)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFFEE2737).withValues(alpha: 0.25),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: const [
                  Icon(Icons.star_rounded, color: Color(0xFFFFC107), size: 13),
                  SizedBox(width: 4),
                  Text(
                    'CicalengkaClub',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
      body: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            // ── 1. USER PROFILE CARD ──
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.03),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Row(
                children: [
                  // Avatar with camera tap option
                  GestureDetector(
                    onTap: () => _showEditProfileModal(context, user, ctrl, authCtrl),
                    child: Stack(
                      children: [
                        Container(
                          width: 54,
                          height: 54,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border: Border.all(color: const Color(0xFFEE2737), width: 2),
                          ),
                          child: ClipOval(
                            child: _selectedAvatarFile != null
                                ? Image.file(_selectedAvatarFile!, fit: BoxFit.cover)
                                : (avatarUrl != null
                                    ? CachedNetworkImage(
                                        imageUrl: avatarUrl,
                                        fit: BoxFit.cover,
                                        placeholder: (_, __) => _defaultAvatar(name),
                                        errorWidget: (_, __, ___) => _defaultAvatar(name),
                                      )
                                    : _defaultAvatar(name)),
                          ),
                        ),
                        Positioned(
                          bottom: 0,
                          right: 0,
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: const BoxDecoration(
                              color: AppTheme.primaryRed,
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(
                              Icons.camera_alt_rounded,
                              color: Colors.white,
                              size: 11,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 14),

                  // Name, Phone, Email
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          name,
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF0F172A),
                            letterSpacing: -0.2,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            const Icon(Icons.phone_android_rounded, size: 11, color: Color(0xFF64748B)),
                            const SizedBox(width: 4),
                            Expanded(
                              child: Text(
                                phone,
                                style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                        if (email.isNotEmpty && email != '-') ...[
                          const SizedBox(height: 1.5),
                          Row(
                            children: [
                              const Icon(Icons.email_outlined, size: 11, color: Color(0xFF94A3B8)),
                              const SizedBox(width: 4),
                              Expanded(
                                child: Text(
                                  email,
                                  style: const TextStyle(fontSize: 10.5, color: Color(0xFF94A3B8)),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),

                  // Edit Profile Icon Button
                  Material(
                    color: const Color(0xFFF1F5F9),
                    shape: CircleBorder(side: BorderSide(color: const Color(0xFFCBD5E1))),
                    child: InkWell(
                      customBorder: const CircleBorder(),
                      onTap: () => _showEditProfileModal(context, user, ctrl, authCtrl),
                      child: Container(
                        width: 36,
                        height: 36,
                        alignment: Alignment.center,
                        child: const Icon(Icons.edit_rounded, color: AppTheme.primaryRed, size: 15),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 12),

            // ── 2. CICALENGKAPAY QUICK BALANCE CARD ──
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.03),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Row(
                children: [
                  // Wallet Icon Box
                  Container(
                    width: 38,
                    height: 38,
                    decoration: const BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: LinearGradient(
                        colors: [Color(0xFFEE2737), Color(0xFFC61524)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                    ),
                    child: const Icon(Icons.account_balance_wallet_rounded, color: Colors.white, size: 18),
                  ),
                  const SizedBox(width: 12),

                  // Label & Amount
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        RichText(
                          text: const TextSpan(
                            style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, letterSpacing: 0.5, color: Color(0xFF64748B)),
                            children: [
                              TextSpan(text: 'SALDO CICALENGKA'),
                              TextSpan(text: 'PAY', style: TextStyle(color: AppTheme.primaryRed)),
                            ],
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          _formatRupiah(walletBalance),
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w900,
                            color: Color(0xFF0F172A),
                            letterSpacing: -0.3,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),

                  // Topup Button
                  InkWell(
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const CustomerWalletScreen()),
                      );
                    },
                    borderRadius: BorderRadius.circular(20),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFFEE2737), Color(0xFFC61524)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFFEE2737).withValues(alpha: 0.25),
                            blurRadius: 6,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: const Text(
                        'Isi Saldo',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 12),

            // ── 3. MENU NAVIGATION LIST ──
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.03),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                children: [
                  // 1. Edit Profil & Kata Sandi
                  _buildMenuItem(
                    icon: Icons.manage_accounts_rounded,
                    iconBg: const Color(0xFFFEE2E2),
                    iconColor: const Color(0xFFEE2737),
                    title: 'Edit Profil & Kata Sandi',
                    subtitle: 'Ubah foto profil, nama & password',
                    onTap: () => _showEditProfileModal(context, user, ctrl, authCtrl),
                  ),
                  const Divider(height: 1, color: Color(0xFFF1F5F9)),

                  // 2. Riwayat Pesanan
                  _buildMenuItem(
                    icon: Icons.receipt_long_rounded,
                    iconBg: const Color(0xFFE0F2FE),
                    iconColor: const Color(0xFF0284C7),
                    title: 'Riwayat Pesanan',
                    subtitle: 'Cek daftar transaksi & status pengiriman',
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const CustomerOrdersScreen()),
                      );
                    },
                  ),
                  const Divider(height: 1, color: Color(0xFFF1F5F9)),

                  // 3. Saldo & Dompet CicalengkaPay
                  _buildMenuItem(
                    icon: Icons.account_balance_wallet_rounded,
                    iconBg: const Color(0xFFFEE2E2),
                    iconColor: const Color(0xFFEE2737),
                    title: 'Dompet CicalengkaPay',
                    subtitle: 'Kelola saldo, topup & mutasi transaksi',
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const CustomerWalletScreen()),
                      );
                    },
                  ),
                  const Divider(height: 1, color: Color(0xFFF1F5F9)),

                  // 4. Voucher & Promo Saya
                  _buildMenuItem(
                    icon: Icons.percent_rounded,
                    iconBg: const Color(0xFFFEF3C7),
                    iconColor: const Color(0xFFD97706),
                    title: 'Voucher & Promo Saya',
                    subtitle: 'Kupon diskon & penawaran menarik',
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const VouchersScreen()),
                      );
                    },
                  ),
                  const Divider(height: 1, color: Color(0xFFF1F5F9)),

                  // 5. Pusat Notifikasi
                  _buildMenuItem(
                    icon: Icons.notifications_rounded,
                    iconBg: const Color(0xFFF3E8FF),
                    iconColor: const Color(0xFF9333EA),
                    title: 'Pusat Notifikasi',
                    subtitle: 'Pesan masuk, update pesanan & promo',
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const CustomerNotificationsScreen()),
                      );
                    },
                  ),
                  const Divider(height: 1, color: Color(0xFFF1F5F9)),

                  // 6. Bantuan & CS 24 Jam
                  _buildMenuItem(
                    icon: Icons.support_agent_rounded,
                    iconBg: const Color(0xFFCCFBF1),
                    iconColor: const Color(0xFF0D9488),
                    title: 'Bantuan & CS 24 Jam',
                    subtitle: 'Pertanyaan umum & kontak bantuan',
                    onTap: () => _showFaqModal(context),
                  ),
                  const Divider(height: 1, color: Color(0xFFF1F5F9)),

                  // 7. Syarat & Ketentuan
                  _buildMenuItem(
                    icon: Icons.description_outlined,
                    iconBg: const Color(0xFFEDE9FE),
                    iconColor: const Color(0xFF7C3AED),
                    title: 'Syarat & Ketentuan Layanan',
                    subtitle: 'Kebijakan privasi & aturan pemakaian',
                    onTap: () => _showTermsModal(context),
                  ),
                  const Divider(height: 1, color: Color(0xFFF1F5F9)),

                  // 8. Tentang CicalengkaGO
                  _buildMenuItem(
                    icon: Icons.info_outline_rounded,
                    iconBg: const Color(0xFFE2E8F0),
                    iconColor: const Color(0xFF475569),
                    title: 'Tentang CicalengkaGO',
                    subtitle: 'Info aplikasi, versi & pengembang',
                    onTap: () => _showAboutModal(context),
                  ),
                  const Divider(height: 1, color: Color(0xFFF1F5F9)),

                  // 9. Keluar Akun
                  _buildMenuItem(
                    icon: Icons.logout_rounded,
                    iconBg: const Color(0xFFFFE4E6),
                    iconColor: const Color(0xFFE11D48),
                    title: 'Keluar Akun',
                    subtitle: 'Keluar dari sesi akun saat ini',
                    isDanger: true,
                    onTap: () => _confirmLogout(context, authCtrl),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 24),

            // ── 4. FOOTER APP VERSION INFO ──
            const Center(
              child: Text(
                'CicalengkaGO v3.6.0 • Platform Layanan Lokal Cicalengka',
                style: TextStyle(
                  fontSize: 10.5,
                  fontWeight: FontWeight.w500,
                  color: Color(0xFF94A3B8),
                ),
              ),
            ),

            const SizedBox(height: 32),
          ],
        ),
      ),
    ),
  );
}

  Widget _defaultAvatar(String name) {
    final initial = name.trim().isNotEmpty ? name.trim()[0].toUpperCase() : 'C';
    return Container(
      color: AppTheme.primaryRed,
      alignment: Alignment.center,
      child: Text(
        initial,
        style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold),
      ),
    );
  }

  Widget _buildMenuItem({
    required IconData icon,
    required Color iconBg,
    required Color iconColor,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
    bool isDanger = false,
  }) {
    return Material(
      color: Colors.transparent,
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
        onTap: onTap,
        leading: Container(
          width: 38,
          height: 38,
          decoration: BoxDecoration(
            color: iconBg,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: iconColor, size: 18),
        ),
        title: Text(
          title,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.bold,
            color: isDanger ? const Color(0xFFE11D48) : const Color(0xFF0F172A),
          ),
        ),
        subtitle: Text(
          subtitle,
          style: const TextStyle(fontSize: 10, color: Color(0xFF64748B)),
        ),
        trailing: Icon(
          Icons.chevron_right_rounded,
          color: isDanger ? const Color(0xFFE11D48) : const Color(0xFF94A3B8),
          size: 16,
        ),
      ),
    );
  }

  // ── MODAL: NOTIFIKASI ──
  void _showNotificationsModal(BuildContext context, CustomerController ctrl) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.75),
        padding: const EdgeInsets.all(20),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 36,
                height: 4,
                decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(10)),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Notifikasi & Promo',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(color: const Color(0xFFFEF3C7), borderRadius: BorderRadius.circular(20)),
                  child: Text(
                    '${ctrl.notifications.length} Info',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFFD97706)),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Expanded(
              child: ctrl.notifications.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: const [
                          Icon(Icons.notifications_none_rounded, size: 48, color: Color(0xFFCBD5E1)),
                          SizedBox(height: 12),
                          Text('Belum ada notifikasi baru', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                          SizedBox(height: 4),
                          Text('Semua info pesanan dan promo akan tampil di sini', style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8))),
                        ],
                      ),
                    )
                  : ListView.separated(
                      shrinkWrap: true,
                      itemCount: ctrl.notifications.length,
                      separatorBuilder: (_, __) => const Divider(height: 1, color: Color(0xFFF1F5F9)),
                      itemBuilder: (_, idx) {
                        final n = ctrl.notifications[idx];
                        return Material(
                          color: Colors.transparent,
                          child: ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: Container(
                              width: 38,
                              height: 38,
                              decoration: BoxDecoration(color: const Color(0xFFFEF3C7), borderRadius: BorderRadius.circular(10)),
                              child: const Icon(Icons.notifications_rounded, color: Color(0xFFD97706), size: 20),
                            ),
                            title: Text(n['title'] ?? 'Info CicalengkaGO', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                            subtitle: Text(n['message'] ?? '', style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B))),
                          ),
                        );
                      },
                    ),
            ),
            const SizedBox(height: 12),
            UberPillButton(
              label: 'Tutup Notifikasi',
              icon: Icons.close_rounded,
              onPressed: () => Navigator.pop(ctx),
            ),
          ],
        ),
      ),
    );
  }

  // ── MODAL: BANTUAN & FAQ ──
  void _showFaqModal(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.82),
        padding: const EdgeInsets.all(20),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 36,
                height: 4,
                decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(10)),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: const [
                Icon(Icons.support_agent_rounded, color: Color(0xFF0D9488), size: 22),
                SizedBox(width: 8),
                Text(
                  'Bantuan & CS 24 Jam',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
              ],
            ),
            const SizedBox(height: 4),
            const Text('Pertanyaan umum & kontak bantuan kendala aplikasi CicalengkaGO', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
            const SizedBox(height: 14),

            // CS Contact Card (Click to open WhatsApp directly!)
            InkWell(
              onTap: () {
                Navigator.pop(ctx);
                _launchWhatsAppSupport();
              },
              borderRadius: BorderRadius.circular(14),
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF0FDF4),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFF86EFAC)),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF22C55E).withValues(alpha: 0.1),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: const BoxDecoration(
                        color: Color(0xFF22C55E),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.chat_rounded, color: Colors.white, size: 18),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: const [
                          Text(
                            'Chat WhatsApp Customer Service',
                            style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFF15803D)),
                          ),
                          SizedBox(height: 2),
                          Text(
                            '0851-5839-7756 • Respon Cepat 24 Jam',
                            style: TextStyle(fontSize: 11, color: Color(0xFF166534), fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: const Color(0xFF22C55E),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            'Kirim WA',
                            style: TextStyle(color: Colors.white, fontSize: 10.5, fontWeight: FontWeight.bold),
                          ),
                          SizedBox(width: 3),
                          Icon(Icons.arrow_forward_ios_rounded, color: Colors.white, size: 8),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 14),

            Expanded(
              child: ListView(
                children: [
                  _faqExpansionTile('Cara Memesan Makanan & Produk', 'Pilih menu makanan atau produk mitra CicalengkaGO favoritmu, atur kuantitas, dan klik "Tambah ke Keranjang". Buka keranjang lalu tekan "Lanjut Checkout".'),
                  _faqExpansionTile('Metode Pembayaran yang Tersedia', 'CicalengkaGO mendukung pembayaran Cash on Delivery (COD/Bayar di Tempat), Saldo Wallet CicalengkaPay, serta QRIS dan Transfer Bank (Midtrans).'),
                  _faqExpansionTile('Berapa Biaya Pengantaran Ongkir?', 'Biaya ongkir dihitung secara otomatis berdasarkan jarak lokasi mitra toko ke lokasi pengantaran Anda di wilayah Cicalengka.'),
                  _faqExpansionTile('Bagaimana Cara Melakukan Top Up Saldo?', 'Buka menu Saldo CicalengkaPay > klik tombol "Isi Saldo" > masukkan nominal lalu pilih metode pembayaran Transfer Bank / QRIS.'),
                  _faqExpansionTile('Bagaimana Jika Pesanan Bermasalah?', 'Anda dapat menghubungi driver yang bertugas melalui nomor telepon yang tertera di halaman pelacakan atau hubungi CS kami.'),
                ],
              ),
            ),
            const SizedBox(height: 14),
            UberPillButton(
              label: 'Tutup Bantuan',
              icon: Icons.check_circle_outline_rounded,
              onPressed: () => Navigator.pop(ctx),
            ),
          ],
        ),
      ),
    );
  }

  // ── MODAL: CICALENGKACLUB BENEFITS ──
  void _showClubBenefitsModal(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.75),
        padding: const EdgeInsets.all(20),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 36,
                height: 4,
                decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(10)),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFFEE2737), Color(0xFFC61524)],
                    ),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.star_rounded, color: Color(0xFFFFC107), size: 20),
                ),
                const SizedBox(width: 10),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: const [
                    Text(
                      'CicalengkaClub Member',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    ),
                    Text(
                      'Keuntungan eksklusif pelanggan setia',
                      style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            Expanded(
              child: ListView(
                children: [
                  _benefitItem(
                    icon: Icons.local_shipping_rounded,
                    color: const Color(0xFF2563EB),
                    title: 'Diskon Ongkir Mingguan',
                    desc: 'Dapatkan potongan biaya pengantaran setiap memesan dari mitra terdekat di Cicalengka.',
                  ),
                  _benefitItem(
                    icon: Icons.percent_rounded,
                    color: const Color(0xFFD97706),
                    title: 'Voucher Promo Spesial',
                    desc: 'Akses khusus ke kode kupon diskon makanan dan promo flash sale.',
                  ),
                  _benefitItem(
                    icon: Icons.speed_rounded,
                    color: const Color(0xFF16A34A),
                    title: 'Prioritas Antrian Driver',
                    desc: 'Pesanan Anda diprioritaskan pencarian driver tercepat di jam-jam sibuk.',
                  ),
                  _benefitItem(
                    icon: Icons.loyalty_rounded,
                    color: const Color(0xFF9333EA),
                    title: 'CicalengkaPay Cashback Koin',
                    desc: 'Kumpulkan poin cashback setiap kali bertransaksi menggunakan CicalengkaPay.',
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
            UberPillButton(
              label: 'Mengerti',
              icon: Icons.check_circle_outline_rounded,
              onPressed: () => Navigator.pop(ctx),
            ),
          ],
        ),
      ),
    );
  }

  Widget _benefitItem({
    required IconData icon,
    required Color color,
    required String title,
    required String desc,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withOpacity(0.12),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: color, size: 18),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                const SizedBox(height: 2),
                Text(desc, style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B), height: 1.35)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ── MODAL: SYARAT & KETENTUAN ──
  void _showTermsModal(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.8),
        padding: const EdgeInsets.all(20),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 36,
                height: 4,
                decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(10)),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: const [
                Icon(Icons.description_outlined, color: Color(0xFF7C3AED), size: 22),
                SizedBox(width: 8),
                Text(
                  'Syarat & Ketentuan Layanan',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
              ],
            ),
            const SizedBox(height: 4),
            const Text('Ketentuan penggunaan platform CicalengkaGO', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
            const SizedBox(height: 16),
            Expanded(
              child: ListView(
                children: [
                  _faqExpansionTile('1. Penggunaan Layanan', 'Platform CicalengkaGO menghubungkan pelanggan dengan mitra penjual kuliner dan mitra pengemudi di wilayah Cicalengka dan sekitarnya.'),
                  _faqExpansionTile('2. Transaksi & Pembayaran', 'Pelanggan bertanggung jawab untuk membayar pesanan sesuai total tagihan resmi melalui metode pembayaran yang tersedia.'),
                  _faqExpansionTile('3. Pembatalan Pesanan', 'Pembatalan pesanan hanya dapat dilakukan sebelum mitra toko mulai menyiapkan pesanan atau sebelum driver menuju ke lokasi toko.'),
                  _faqExpansionTile('4. Perlindungan Data & Privasi', 'Data nomor telepon, alamat, dan identitas Anda dilindungi dengan standar enkripsi aman dan hanya digunakan untuk keperluan pengantaran pesanan.'),
                ],
              ),
            ),
            const SizedBox(height: 14),
            UberPillButton(
              label: 'Tutup',
              icon: Icons.check_circle_outline_rounded,
              onPressed: () => Navigator.pop(ctx),
            ),
          ],
        ),
      ),
    );
  }

  // ── MODAL: TENTANG CICALENGKAGO ──
  void _showAboutModal(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 36,
              height: 4,
              decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(10)),
            ),
            const SizedBox(height: 20),
            const CicalengkaGoLogo(size: 64, borderRadius: 18),
            const SizedBox(height: 14),
            const Text(
              'CicalengkaGO',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 4),
            const Text(
              'Versi 3.6.0 (Build 2026)',
              style: TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 14),
            const Text(
              'Aplikasi pesan antar makanan, pengiriman lokal, dan transaksi digital karya putra daerah untuk memajukan UMKM Cicalengka dan sekitarnya.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12.5, color: Color(0xFF475569), height: 1.45),
            ),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                color: const Color(0xFFF1F5F9),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.language_rounded, size: 14, color: AppTheme.primaryRed),
                  SizedBox(width: 6),
                  Text(
                    'https://cicago.store',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            UberPillButton(
              label: 'Tutup',
              icon: Icons.close_rounded,
              onPressed: () => Navigator.pop(ctx),
            ),
          ],
        ),
      ),
    );
  }

  Widget _faqExpansionTile(String title, String content) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: ExpansionTile(
        shape: const Border(),
        title: Text(title, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
        children: [
          Padding(
            padding: const EdgeInsets.only(left: 16, right: 16, bottom: 14),
            child: Text(content, style: const TextStyle(fontSize: 12, color: Color(0xFF475569), height: 1.4)),
          ),
        ],
      ),
    );
  }

  // ── GUEST VIEW (Match PHP line 157-190) ──
  Widget _buildGuestView(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0.5,
        title: const Text(
          'Akun Saya',
          style: TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 15,
            fontWeight: FontWeight.bold,
            letterSpacing: -0.3,
          ),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: const Color(0xFFE2E8F0)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.03),
                    blurRadius: 10,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: Column(
                children: [
                  // Icon Squircle Illustration
                  const CicalengkaGoLogo(size: 72, borderRadius: 22),
                  const SizedBox(height: 16),
                  const Text(
                    'Selamat Datang di CicalengkaGO!',
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w900,
                      color: Color(0xFF0F172A),
                      letterSpacing: -0.3,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Masuk ke akun Anda untuk menikmati transaksi pesan antar makanan, saldo CicalengkaPay, dan promo menarik setiap hari.',
                    style: TextStyle(
                      fontSize: 11.5,
                      color: Color(0xFF64748B),
                      height: 1.5,
                      fontWeight: FontWeight.w500,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 24),

                  // Button 1: Masuk Sekarang
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen())),
                      icon: const Icon(Icons.login_rounded, size: 16),
                      label: const Text('Masuk Sekarang', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 13)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.primaryRed,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(9999)),
                        elevation: 2,
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),

                  // Button 2: Daftar Akun Baru
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen())),
                      icon: const Icon(Icons.person_add_rounded, size: 15, color: AppTheme.primaryRed),
                      label: const Text('Daftar Akun Baru', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF0F172A))),
                      style: OutlinedButton.styleFrom(
                        backgroundColor: const Color(0xFFF1F5F9),
                        side: const BorderSide(color: Color(0xFFCBD5E1)),
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(9999)),
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),

                  const Divider(height: 1, color: Color(0xFFE2E8F0)),
                  const SizedBox(height: 14),

                  // Features Grid
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: const [
                      Icon(Icons.bolt_rounded, color: Color(0xFFD97706), size: 14),
                      SizedBox(width: 4),
                      Text('Cepat', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                      Padding(
                        padding: EdgeInsets.symmetric(horizontal: 8),
                        child: Text('•', style: TextStyle(color: Color(0xFFCBD5E1))),
                      ),
                      Icon(Icons.verified_user_rounded, color: Color(0xFF10B981), size: 14),
                      SizedBox(width: 4),
                      Text('Aman', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                      Padding(
                        padding: EdgeInsets.symmetric(horizontal: 8),
                        child: Text('•', style: TextStyle(color: Color(0xFFCBD5E1))),
                      ),
                      Icon(Icons.local_offer_rounded, color: AppTheme.primaryRed, size: 14),
                      SizedBox(width: 4),
                      Text('Hemat', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'CicalengkaGO v3.6.0 • Platform Layanan Lokal Cicalengka',
              style: TextStyle(fontSize: 10, fontWeight: FontWeight.w500, color: Color(0xFF94A3B8)),
            ),
          ],
        ),
      ),
    );
  }

  void _confirmLogout(BuildContext context, AuthController authCtrl) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Konfirmasi Keluar', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
        content: const Text('Apakah Anda yakin ingin keluar dari akun CicalengkaGO?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal', style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryRed,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              elevation: 0,
            ),
            onPressed: () async {
              Navigator.pop(ctx);
              await authCtrl.logout();
              if (context.mounted) {
                Navigator.pushAndRemoveUntil(
                  context,
                  MaterialPageRoute(builder: (_) => const SplashScreen()),
                  (route) => false,
                );
              }
            },
            child: const Text('Keluar Akun', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }
}

// ── DEDICATED STATEFUL WIDGET FOR EDIT PROFILE MODAL ──
class _EditProfileModalSheet extends StatefulWidget {
  final Map<String, dynamic>? user;
  final CustomerController ctrl;
  final AuthController authCtrl;
  final File? initialAvatarFile;
  final ValueChanged<File?> onAvatarUpdated;

  const _EditProfileModalSheet({
    required this.user,
    required this.ctrl,
    required this.authCtrl,
    required this.initialAvatarFile,
    required this.onAvatarUpdated,
  });

  @override
  State<_EditProfileModalSheet> createState() => _EditProfileModalSheetState();
}

class _EditProfileModalSheetState extends State<_EditProfileModalSheet> {
  late final TextEditingController _nameCtrl;
  late final TextEditingController _emailCtrl;
  late final TextEditingController _phoneCtrl;

  late final TextEditingController _currentPassCtrl;
  late final TextEditingController _newPassCtrl;
  late final TextEditingController _confirmPassCtrl;

  bool _showCurrentPass = false;
  bool _showNewPass = false;
  bool _showConfirmPass = false;
  bool _isSaving = false;

  File? _selectedAvatarFile;

  @override
  void initState() {
    super.initState();
    final user = widget.user;
    _nameCtrl = TextEditingController(text: user?['name'] ?? user?['username'] ?? '');
    _emailCtrl = TextEditingController(text: user?['email'] ?? '');
    _phoneCtrl = TextEditingController(text: user?['phone'] ?? user?['no_hp'] ?? '');

    _currentPassCtrl = TextEditingController();
    _newPassCtrl = TextEditingController();
    _confirmPassCtrl = TextEditingController();

    _selectedAvatarFile = widget.initialAvatarFile;
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _currentPassCtrl.dispose();
    _newPassCtrl.dispose();
    _confirmPassCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final picker = ImagePicker();
      final picked = await picker.pickImage(
        source: source,
        maxWidth: 800,
        maxHeight: 800,
        imageQuality: 85,
      );

      if (picked != null) {
        final file = File(picked.path);
        if (mounted) {
          setState(() {
            _selectedAvatarFile = file;
          });
        }
        widget.onAvatarUpdated(file);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gagal mengambil gambar: $e'),
            backgroundColor: AppTheme.primaryRed,
          ),
        );
      }
    }
  }

  void _showImageSourcePicker() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => Container(
        padding: const EdgeInsets.all(20),
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
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
            ),
            const SizedBox(height: 14),
            const Text(
              'Pilih Sumber Foto Profil',
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.bold,
                color: Color(0xFF0F172A),
              ),
            ),
            const SizedBox(height: 14),
            ListTile(
              leading: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEE2E2),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.photo_library_rounded, color: AppTheme.primaryRed),
              ),
              title: const Text(
                'Galeri Foto',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
              ),
              subtitle: const Text(
                'Pilih gambar yang sudah ada dari galeri',
                style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
              ),
              onTap: () {
                Navigator.pop(ctx);
                _pickImage(ImageSource.gallery);
              },
            ),
            const Divider(height: 1, color: Color(0xFFF1F5F9)),
            ListTile(
              leading: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFE0F2FE),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.camera_alt_rounded, color: Color(0xFF0284C7)),
              ),
              title: const Text(
                'Kamera',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
              ),
              subtitle: const Text(
                'Ambil foto baru langsung dari kamera',
                style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
              ),
              onTap: () {
                Navigator.pop(ctx);
                _pickImage(ImageSource.camera);
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _defaultAvatar(String name) {
    final initial = name.trim().isNotEmpty ? name.trim()[0].toUpperCase() : 'C';
    return Container(
      color: AppTheme.primaryRed,
      alignment: Alignment.center,
      child: Text(
        initial,
        style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final Map<String, dynamic> mergedUser = {};
    if (widget.authCtrl.user != null) {
      if (widget.authCtrl.user!.containsKey('user') && widget.authCtrl.user!['user'] is Map) {
        mergedUser.addAll(Map<String, dynamic>.from(widget.authCtrl.user!['user'] as Map));
      } else {
        mergedUser.addAll(widget.authCtrl.user!);
      }
    }
    if (widget.ctrl.profile != null) {
      if (widget.ctrl.profile!.containsKey('user') && widget.ctrl.profile!['user'] is Map) {
        mergedUser.addAll(Map<String, dynamic>.from(widget.ctrl.profile!['user'] as Map));
      } else {
        mergedUser.addAll(widget.ctrl.profile!);
      }
    }
    if (widget.user != null) {
      if (widget.user!.containsKey('user') && widget.user!['user'] is Map) {
        mergedUser.addAll(Map<String, dynamic>.from(widget.user!['user'] as Map));
      } else {
        mergedUser.addAll(widget.user!);
      }
    }

    final user = mergedUser;
    final name = user['name'] ?? user['username'] ?? 'Pengguna';
    final rawAvatar = user['avatar'] ?? user['profile_photo_url'];

    String? avatarUrl;
    if (rawAvatar != null && rawAvatar.toString().trim().isNotEmpty) {
      avatarUrl = ApiConstants.formatImageUrl(rawAvatar.toString().trim());
    }

    return Container(
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 16,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Pull indicator handle
            Center(
              child: Container(
                width: 36,
                height: 4,
                decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(10)),
              ),
            ),
            const SizedBox(height: 14),

            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: const [
                    Icon(Icons.manage_accounts_rounded, color: AppTheme.primaryRed, size: 20),
                    SizedBox(width: 8),
                    Text(
                      'Edit Profil Saya',
                      style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    ),
                  ],
                ),
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF64748B)),
                ),
              ],
            ),

            const SizedBox(height: 10),

            // 0. Foto Profil Avatar Section (Match Web views/customer/profile.php)
            _inputLabel('Foto Profil'),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                children: [
                  Stack(
                    children: [
                      Container(
                        width: 52,
                        height: 52,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          border: Border.all(color: AppTheme.primaryRed, width: 2),
                        ),
                        child: ClipOval(
                          child: _selectedAvatarFile != null
                              ? Image.file(_selectedAvatarFile!, fit: BoxFit.cover)
                              : (avatarUrl != null
                                  ? CachedNetworkImage(
                                      imageUrl: avatarUrl,
                                      fit: BoxFit.cover,
                                      placeholder: (_, __) => _defaultAvatar(name),
                                      errorWidget: (_, __, ___) => _defaultAvatar(name),
                                    )
                                  : _defaultAvatar(name)),
                        ),
                      ),
                      if (_selectedAvatarFile != null)
                        Positioned(
                          top: 0,
                          right: 0,
                          child: GestureDetector(
                            onTap: () {
                              setState(() => _selectedAvatarFile = null);
                              widget.onAvatarUpdated(null);
                            },
                            child: Container(
                              padding: const EdgeInsets.all(3),
                              decoration: const BoxDecoration(
                                color: Colors.red,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.close, color: Colors.white, size: 10),
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        OutlinedButton.icon(
                          onPressed: _showImageSourcePicker,
                          icon: const Icon(Icons.photo_camera_rounded, size: 14, color: AppTheme.primaryRed),
                          label: Text(
                            _selectedAvatarFile != null ? 'Ganti Foto Pilih' : 'Pilih Foto Baru',
                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                          ),
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                            side: const BorderSide(color: Color(0xFFCBD5E1)),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                        ),
                        const SizedBox(height: 2),
                        const Text(
                          'JPG, PNG, WEBP. Maks 2MB.',
                          style: TextStyle(fontSize: 9.5, color: Color(0xFF94A3B8)),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 12),

            // 1. Nama Lengkap
            _inputLabel('Nama Lengkap'),
            const SizedBox(height: 4),
            _modalTextField(_nameCtrl, 'Nama lengkap Anda', Icons.person_outline_rounded),
            const SizedBox(height: 12),

            // 2. Alamat Email
            _inputLabel('Alamat Email'),
            const SizedBox(height: 4),
            _modalTextField(_emailCtrl, 'Alamat email aktif', Icons.email_outlined, keyboardType: TextInputType.emailAddress),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF3C7),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: const [
                  Icon(Icons.info_outline_rounded, color: Color(0xFFD97706), size: 15),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Perubahan email butuh verifikasi kode OTP ke email baru Anda.',
                      style: TextStyle(fontSize: 10.5, color: Color(0xFF92400E), height: 1.35),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),

            // 3. No. WhatsApp
            _inputLabel('No. WhatsApp'),
            const SizedBox(height: 4),
            _modalTextField(_phoneCtrl, 'No. WhatsApp aktif', Icons.phone_android_rounded, keyboardType: TextInputType.phone),
            const SizedBox(height: 16),

            const Divider(height: 1, color: Color(0xFFE2E8F0)),
            const SizedBox(height: 14),

            // 4. Section Ubah Kata Sandi (Opsional)
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: const [
                Row(
                  children: [
                    Icon(Icons.key_rounded, color: AppTheme.primaryRed, size: 16),
                    SizedBox(width: 6),
                    Text(
                      'Kata Sandi (Opsional)',
                      style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    ),
                  ],
                ),
                Text(
                  'Kosongkan jika tidak diubah',
                  style: TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                ),
              ],
            ),
            const SizedBox(height: 10),

            // Pass 1: Current Password
            _passwordField(
              ctrl: _currentPassCtrl,
              hint: 'Kata sandi saat ini',
              isVisible: _showCurrentPass,
              onToggle: () => setState(() => _showCurrentPass = !_showCurrentPass),
            ),
            const SizedBox(height: 8),

            // Pass 2: New Password
            _passwordField(
              ctrl: _newPassCtrl,
              hint: 'Kata sandi baru (Min. 6)',
              isVisible: _showNewPass,
              onToggle: () => setState(() => _showNewPass = !_showNewPass),
            ),
            const SizedBox(height: 8),

            // Pass 3: Confirm New Password
            _passwordField(
              ctrl: _confirmPassCtrl,
              hint: 'Ulangi kata sandi baru',
              isVisible: _showConfirmPass,
              onToggle: () => setState(() => _showConfirmPass = !_showConfirmPass),
            ),

            const SizedBox(height: 22),

            // Action Buttons
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _isSaving ? null : () => Navigator.pop(context),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      side: const BorderSide(color: Color(0xFFCBD5E1)),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: const Text('Batal', style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.bold, fontSize: 12)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.save_rounded, size: 16),
                    label: _isSaving
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : const Text('Simpan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primaryRed,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 0,
                    ),
                    onPressed: _isSaving
                        ? null
                        : () async {
                            if (_nameCtrl.text.trim().isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Nama lengkap tidak boleh kosong.'), backgroundColor: AppTheme.primaryRed),
                              );
                              return;
                            }

                            if (_newPassCtrl.text.isNotEmpty) {
                              if (_newPassCtrl.text.length < 6) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Kata sandi baru minimal 6 karakter.'), backgroundColor: AppTheme.primaryRed),
                                );
                                return;
                              }
                              if (_newPassCtrl.text != _confirmPassCtrl.text) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('Konfirmasi kata sandi baru tidak cocok.'), backgroundColor: AppTheme.primaryRed),
                                );
                                return;
                              }
                            }

                            setState(() => _isSaving = true);
                            final payload = <String, String>{
                              'name': _nameCtrl.text.trim(),
                              'email': _emailCtrl.text.trim(),
                              'phone': _phoneCtrl.text.trim(),
                            };
                            if (_newPassCtrl.text.isNotEmpty) {
                              payload['current_password'] = _currentPassCtrl.text;
                              payload['new_password'] = _newPassCtrl.text;
                              payload['confirm_password'] = _confirmPassCtrl.text;
                            }

                            final ok = await widget.ctrl.updateProfile(
                              payload,
                              avatarPath: _selectedAvatarFile?.path,
                            );

                            if (mounted) {
                              setState(() => _isSaving = false);
                            }

                            if (ok && mounted) {
                              final updatedProfile = widget.ctrl.profile ?? {};
                              final avatarUrlStr = updatedProfile['avatar'] ?? updatedProfile['profile_photo_url'];
                              await widget.authCtrl.updateUser({
                                'name': _nameCtrl.text.trim(),
                                'email': _emailCtrl.text.trim(),
                                'phone': _phoneCtrl.text.trim(),
                                if (avatarUrlStr != null && avatarUrlStr.toString().isNotEmpty)
                                  'avatar': avatarUrlStr.toString(),
                              });

                              widget.onAvatarUpdated(null);

                              if (context.mounted) {
                                Navigator.pop(context);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text('Profil dan foto berhasil diperbarui!'),
                                    backgroundColor: Color(0xFF10B981),
                                    behavior: SnackBarBehavior.floating,
                                  ),
                                );
                              }
                            } else if (mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Gagal mengupdate profil. Periksa koneksi atau data Anda.'),
                                  backgroundColor: AppTheme.primaryRed,
                                ),
                              );
                            }
                          },
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _inputLabel(String label) {
    return Text(
      label,
      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
    );
  }

  Widget _modalTextField(TextEditingController ctrl, String hint, IconData icon, {TextInputType? keyboardType}) {
    return TextField(
      controller: ctrl,
      keyboardType: keyboardType,
      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF0F172A)),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
        prefixIcon: Icon(icon, color: const Color(0xFF64748B), size: 18),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        filled: true,
        fillColor: const Color(0xFFF8FAFC),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppTheme.primaryRed, width: 1.5),
        ),
      ),
    );
  }

  Widget _passwordField({
    required TextEditingController ctrl,
    required String hint,
    required bool isVisible,
    required VoidCallback onToggle,
  }) {
    return TextField(
      controller: ctrl,
      obscureText: !isVisible,
      style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Color(0xFF0F172A)),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8)),
        prefixIcon: const Icon(Icons.lock_outline_rounded, color: Color(0xFF64748B), size: 17),
        suffixIcon: IconButton(
          icon: Icon(isVisible ? Icons.visibility_off_rounded : Icons.visibility_rounded, color: const Color(0xFF94A3B8), size: 17),
          onPressed: onToggle,
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        filled: true,
        fillColor: const Color(0xFFF8FAFC),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppTheme.primaryRed, width: 1.5),
        ),
      ),
    );
  }
}
