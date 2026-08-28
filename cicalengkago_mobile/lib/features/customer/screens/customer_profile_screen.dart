import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
import '../../../core/widgets/uber_pill_button.dart';
import '../../auth/controllers/auth_controller.dart';
import '../../auth/screens/login_screen.dart';
import '../controllers/customer_controller.dart';

class CustomerProfileScreen extends StatefulWidget {
  const CustomerProfileScreen({super.key});

  @override
  State<CustomerProfileScreen> createState() => _CustomerProfileScreenState();
}

class _CustomerProfileScreenState extends State<CustomerProfileScreen> {
  bool _isEditing = false;
  bool _isSaving = false;
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final ctrl = context.read<CustomerController>();
      ctrl.fetchProfile();
      ctrl.fetchNotifications();
    });
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authCtrl = context.watch<AuthController>();
    final ctrl = context.watch<CustomerController>();
    final user = authCtrl.user ?? ctrl.profile;

    if (!authCtrl.isLoggedIn) {
      return _buildGuestView(context);
    }

    final name = user?['name'] ?? user?['username'] ?? 'Pelanggan CicalengkaGO';
    final email = user?['email'] ?? '-';
    final phone = user?['phone'] ?? user?['no_hp'] ?? '-';
    final rawAvatar = user?['avatar'] ?? user?['profile_photo_url'];
    final avatarUrl = (rawAvatar != null && rawAvatar.toString().isNotEmpty)
        ? ApiConstants.formatImageUrl(rawAvatar.toString())
        : null;

    if (_isEditing && _nameCtrl.text.isEmpty && name != 'Pelanggan CicalengkaGO') {
      _nameCtrl.text = name;
      _phoneCtrl.text = phone == '-' ? '' : phone;
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: CustomScrollView(
        physics: const BouncingScrollPhysics(),
        slivers: [
          // Header Profile Bar
          SliverAppBar(
            pinned: true,
            expandedHeight: 210,
            backgroundColor: AppTheme.inkBlack,
            elevation: 0,
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  ),
                ),
                child: SafeArea(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const SizedBox(height: 12),
                      // Avatar Container
                      Stack(
                        children: [
                          Container(
                            width: 86,
                            height: 86,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              border: Border.all(color: Colors.white, width: 3),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.25),
                                  blurRadius: 16,
                                  offset: const Offset(0, 4),
                                ),
                              ],
                            ),
                            child: ClipOval(
                              child: avatarUrl != null
                                  ? CachedNetworkImage(
                                      imageUrl: avatarUrl,
                                      fit: BoxFit.cover,
                                      placeholder: (_, __) => _defaultAvatar(name),
                                      errorWidget: (_, __, ___) => _defaultAvatar(name),
                                    )
                                  : _defaultAvatar(name),
                            ),
                          ),
                          Positioned(
                            bottom: 0,
                            right: 0,
                            child: GestureDetector(
                              onTap: () {
                                setState(() {
                                  _isEditing = true;
                                  _nameCtrl.text = name;
                                  _phoneCtrl.text = phone == '-' ? '' : phone;
                                });
                              },
                              child: Container(
                                padding: const EdgeInsets.all(6),
                                decoration: const BoxDecoration(
                                  color: AppTheme.primaryRed,
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(
                                  Icons.edit_rounded,
                                  color: Colors.white,
                                  size: 14,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      Text(
                        name,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 19,
                          fontWeight: FontWeight.bold,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          email,
                          style: const TextStyle(color: Colors.white70, fontSize: 11.5, fontWeight: FontWeight.w500),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            actions: [
              IconButton(
                icon: const Icon(Icons.logout_rounded, color: Colors.white),
                tooltip: 'Keluar',
                onPressed: () => _confirmLogout(context, authCtrl),
              ),
            ],
          ),

          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
              child: Column(
                children: [
                  if (_isEditing) ...[
                    _buildEditForm(context, ctrl, authCtrl),
                  ] else ...[
                    _buildInfoCard(context, name, email, phone),
                  ],

                  const SizedBox(height: 18),
                  _buildMenuSection(context, ctrl, authCtrl),
                  const SizedBox(height: 32),

                  // App Version Info Footer
                  Center(
                    child: Column(
                      children: const [
                        CicalengkaGoLogo(size: 32, borderRadius: 10),
                        SizedBox(height: 8),
                        Text(
                          'CicalengkaGO App v2.4.0 (Production)',
                          style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                        ),
                        SizedBox(height: 2),
                        Text(
                          'Dibuat dengan ❤️ untuk Masyarakat Cicalengka',
                          style: TextStyle(fontSize: 10, color: Color(0xFFCBD5E1)),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 40),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _defaultAvatar(String name) {
    final initial = name.trim().isNotEmpty ? name.trim()[0].toUpperCase() : 'C';
    return Container(
      color: AppTheme.primaryRed,
      child: Center(
        child: Text(
          initial,
          style: const TextStyle(color: Colors.white, fontSize: 34, fontWeight: FontWeight.bold),
        ),
      ),
    );
  }

  Widget _buildInfoCard(BuildContext context, String name, String email, String phone) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 12,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        children: [
          _infoRow(Icons.person_outline_rounded, 'Nama Lengkap', name),
          const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 56),
          _infoRow(Icons.email_outlined, 'Email Terdaftar', email),
          const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 56),
          _infoRow(Icons.phone_android_rounded, 'No. Telepon / WhatsApp', phone),
          const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 56),
          Padding(
            padding: const EdgeInsets.all(12),
            child: InkWell(
              onTap: () {
                setState(() {
                  _isEditing = true;
                  _nameCtrl.text = name == 'Pelanggan CicalengkaGO' ? '' : name;
                  _phoneCtrl.text = phone == '-' ? '' : phone;
                });
              },
              borderRadius: BorderRadius.circular(14),
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.edit_rounded, color: AppTheme.inkBlack, size: 16),
                    SizedBox(width: 8),
                    Text(
                      'Ubah Informasi Profil',
                      style: TextStyle(color: AppTheme.inkBlack, fontSize: 13, fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: AppTheme.inkBlack, size: 19),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEditForm(BuildContext context, CustomerController ctrl, AuthController authCtrl) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppTheme.primaryRed.withValues(alpha: 0.3)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.edit_note_rounded, color: AppTheme.primaryRed, size: 22),
              SizedBox(width: 8),
              Text(
                'Perbarui Data Profil',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
              ),
            ],
          ),
          const SizedBox(height: 16),
          _formField('Nama Lengkap', _nameCtrl, Icons.person_outline_rounded),
          const SizedBox(height: 14),
          _formField('No. Telepon / WhatsApp', _phoneCtrl, Icons.phone_android_rounded, keyboardType: TextInputType.phone),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: _isSaving ? null : () => setState(() => _isEditing = false),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    side: const BorderSide(color: Color(0xFFCBD5E1)),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  child: const Text('Batal', style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton(
                  onPressed: _isSaving
                      ? null
                      : () async {
                          if (_nameCtrl.text.trim().isEmpty) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Nama lengkap tidak boleh kosong.'),
                                backgroundColor: AppTheme.primaryRed,
                              ),
                            );
                            return;
                          }

                          setState(() => _isSaving = true);
                          final ok = await ctrl.updateProfile({
                            'name': _nameCtrl.text.trim(),
                            'phone': _phoneCtrl.text.trim(),
                          });
                          setState(() => _isSaving = false);

                          if (ok && context.mounted) {
                            await authCtrl.updateUser({
                              'name': _nameCtrl.text.trim(),
                              'phone': _phoneCtrl.text.trim(),
                            });

                            setState(() => _isEditing = false);
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Profil Anda berhasil diperbarui!'),
                                backgroundColor: Color(0xFF10B981),
                                behavior: SnackBarBehavior.floating,
                              ),
                            );
                          }
                        },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.inkBlack,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    elevation: 0,
                  ),
                  child: _isSaving
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Simpan Perubahan', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _formField(String label, TextEditingController ctrl, IconData icon, {TextInputType? keyboardType}) {
    return TextField(
      controller: ctrl,
      keyboardType: keyboardType,
      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: AppTheme.inkBlack),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: const TextStyle(fontSize: 12.5, color: Color(0xFF64748B)),
        prefixIcon: Icon(icon, color: AppTheme.inkBlack, size: 20),
        filled: true,
        fillColor: const Color(0xFFF8FAFC),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: AppTheme.inkBlack, width: 2),
        ),
      ),
    );
  }

  Widget _buildMenuSection(BuildContext context, CustomerController ctrl, AuthController authCtrl) {
    final menuItems = [
      {
        'icon': Icons.location_on_rounded,
        'label': 'Alamat Pengantaran Saya',
        'sub': 'Atur alamat utama pengiriman makanan & kurir',
        'color': const Color(0xFF2563EB),
        'onTap': () => _showDeliveryAddressModal(context),
      },
      {
        'icon': Icons.notifications_rounded,
        'label': 'Notifikasi & Info Promo',
        'sub': 'Pemberitahuan status pesanan dan diskon',
        'color': const Color(0xFFD97706),
        'onTap': () => _showNotificationsModal(context, ctrl),
      },
      {
        'icon': Icons.help_outline_rounded,
        'label': 'Bantuan & FAQ',
        'sub': 'Pertanyaan umum dan kontak Customer Service',
        'color': const Color(0xFF059669),
        'onTap': () => _showFaqModal(context),
      },
      {
        'icon': Icons.info_outline_rounded,
        'label': 'Tentang CicalengkaGO',
        'sub': 'Informasi aplikasi, syarat & ketentuan',
        'color': const Color(0xFF7C3AED),
        'onTap': () => _showAboutModal(context),
      },
    ];

    return Container(
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
          ...menuItems.asMap().entries.map((entry) {
            final idx = entry.key;
            final item = entry.value;
            return Column(
              children: [
                Material(
                  color: Colors.transparent,
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                    onTap: item['onTap'] as VoidCallback,
                    leading: Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: (item['color'] as Color).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(item['icon'] as IconData, color: item['color'] as Color, size: 20),
                    ),
                    title: Text(
                      item['label'] as String,
                      style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
                    ),
                    subtitle: Text(
                      item['sub'] as String,
                      style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                    ),
                    trailing: const Icon(Icons.chevron_right_rounded, color: Color(0xFFCBD5E1), size: 20),
                  ),
                ),
                if (idx < menuItems.length - 1) const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 64),
              ],
            );
          }),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          // Logout Option
          Material(
            color: Colors.transparent,
            child: ListTile(
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              onTap: () => _confirmLogout(context, authCtrl),
              leading: Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: const Color(0xFFFEE2E2),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.logout_rounded, color: AppTheme.primaryRed, size: 20),
              ),
              title: const Text(
                'Keluar dari Akun',
                style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
              ),
              subtitle: const Text('Keluar dari sesi aplikasi CicalengkaGO', style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
              trailing: const Icon(Icons.chevron_right_rounded, color: Color(0xFFFCA5A5), size: 20),
            ),
          ),
        ],
      ),
    );
  }

  // ── MODAL: Alamat Pengantaran ───────────────────────────────────────
  void _showDeliveryAddressModal(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.all(20),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
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
            const Text(
              'Alamat Pengantaran Utama',
              style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
            ),
            const SizedBox(height: 4),
            const Text(
              'Alamat yang digunakan saat memesan makanan dan jasa di Cicalengka',
              style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(color: const Color(0xFFDBEAFE), borderRadius: BorderRadius.circular(12)),
                    child: const Icon(Icons.location_on_rounded, color: Color(0xFF2563EB), size: 22),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: const [
                        Text('Alamat Default (Cicalengka)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppTheme.inkBlack)),
                        SizedBox(height: 2),
                        Text(
                          'Kecamatan Cicalengka, Kabupaten Bandung, Jawa Barat 40395',
                          style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                        ),
                      ],
                    ),
                  ),
                  const Icon(Icons.check_circle_rounded, color: Color(0xFF10B981), size: 22),
                ],
              ),
            ),
            const SizedBox(height: 20),
            UberPillButton(
              label: 'Tutup',
              icon: Icons.close_rounded,
              onPressed: () => Navigator.pop(ctx),
            ),
            const SizedBox(height: 10),
          ],
        ),
      ),
    );
  }

  // ── MODAL: Notifikasi ──────────────────────────────────────────────
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
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
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
                  'Notifikasi Saya',
                  style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
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

  // ── MODAL: Bantuan & FAQ ───────────────────────────────────────────
  void _showFaqModal(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.8),
        padding: const EdgeInsets.all(20),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
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
            const Text(
              'Bantuan & Pertanyaan Umum',
              style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
            ),
            const SizedBox(height: 4),
            const Text('Temukan jawaban cepat untuk pertanyaanmu tentang CicalengkaGO', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
            const SizedBox(height: 16),
            Expanded(
              child: ListView(
                children: [
                  _faqExpansionTile('Cara Memesan Kuliner & Produk', 'Pilih menu makanan atau produk mitra CicalengkaGO favoritmu, atur kuantitas, dan klik "Tambah ke Keranjang". Buka keranjang lalu tekan "Lanjut Checkout".'),
                  _faqExpansionTile('Metode Pembayaran yang Tersedia', 'CicalengkaGO mendukung pembayaran Cash on Delivery (COD/Bayar di Tempat), Saldo Wallet CicalengkaPay, serta QRIS dan Transfer Bank.'),
                  _faqExpansionTile('Berapa Biaya Pengantaran Ongkir?', 'Biaya ongkir dihitung secara otomatis berdasarkan jarak lokasi mitra toko ke lokasi pengantaran Anda di wilayah Cicalengka.'),
                  _faqExpansionTile('Hubungi Layanan Pelanggan (CS)', 'Butuh bantuan langsung? Tim Customer Service CicalengkaGO siap melayani Anda melalui WhatsApp atau telepon resmi CicalengkaGO.'),
                ],
              ),
            ),
            const SizedBox(height: 14),
            UberPillButton(
              label: 'Selesai',
              icon: Icons.check_circle_outline_rounded,
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
        title: Text(title, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppTheme.inkBlack)),
        children: [
          Padding(
            padding: const EdgeInsets.only(left: 16, right: 16, bottom: 14),
            child: Text(content, style: const TextStyle(fontSize: 12, color: Color(0xFF475569), height: 1.4)),
          ),
        ],
      ),
    );
  }

  // ── MODAL: Tentang CicalengkaGO ─────────────────────────────────────
  void _showAboutModal(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
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
              'CicalengkaGO Mobile',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
            ),
            const Text(
              'Versi 2.4.0 (Production Build)',
              style: TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
            ),
            const SizedBox(height: 14),
            const Text(
              'Platform Super App Layanan Antar Kuliner, Belanja Pasar, Kurir Pengiriman, dan Transportasi Lokal Terpercaya untuk Wilayah Cicalengka & Sekitarnya.',
              style: TextStyle(fontSize: 12.5, color: Color(0xFF475569), height: 1.45),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            UberPillButton(
              label: 'Tutup',
              icon: Icons.close_rounded,
              onPressed: () => Navigator.pop(ctx),
            ),
            const SizedBox(height: 10),
          ],
        ),
      ),
    );
  }

  Widget _buildGuestView(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(36),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const CicalengkaGoLogo(size: 76, borderRadius: 22),
              const SizedBox(height: 24),
              const Text(
                'Masuk ke Akun CicalengkaGO',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: AppTheme.inkBlack),
              ),
              const SizedBox(height: 8),
              const Text(
                'Nikmati kemudahan pesan makanan, cek riwayat transaksi, dan simpan alamat impianmu.',
                style: TextStyle(fontSize: 13, color: Color(0xFF64748B), height: 1.4),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 28),
              UberPillButton(
                label: 'Masuk / Daftar Akun Baru',
                icon: Icons.login_rounded,
                onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen())),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _confirmLogout(BuildContext context, AuthController authCtrl) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Konfirmasi Keluar', style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: AppTheme.inkBlack)),
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
            onPressed: () {
              Navigator.pop(ctx);
              authCtrl.logout();
            },
            child: const Text('Keluar Akun', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }
}
