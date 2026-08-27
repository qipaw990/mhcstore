import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
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
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final ctrl = context.read<CustomerController>();
      ctrl.fetchProfile();
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

    final name = user?['name'] ?? 'Pelanggan';
    final email = user?['email'] ?? '';
    final phone = user?['phone'] ?? '';
    final avatar = user?['avatar'];

    if (_isEditing && _nameCtrl.text.isEmpty) {
      _nameCtrl.text = name;
      _phoneCtrl.text = phone;
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: CustomScrollView(
        slivers: [
          // Header
          SliverAppBar(
            pinned: true,
            expandedHeight: 180,
            backgroundColor: AppTheme.primaryRed,
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    colors: [Color(0xFFEE2737), Color(0xFFC21827)],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  ),
                ),
                child: SafeArea(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const SizedBox(height: 20),
                      // Avatar
                      Container(
                        width: 80,
                        height: 80,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 3),
                          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.2), blurRadius: 15)],
                        ),
                        child: ClipOval(
                          child: avatar != null
                              ? Image.network(
                                  'https://cicago.store/$avatar',
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, __, ___) => _defaultAvatar(name),
                                )
                              : _defaultAvatar(name),
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(name,
                          style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
                      Text(email,
                          style: TextStyle(color: Colors.white.withOpacity(0.85), fontSize: 12)),
                    ],
                  ),
                ),
              ),
            ),
            actions: [
              if (authCtrl.isLoggedIn)
                IconButton(
                  icon: const Icon(Icons.logout_rounded, color: Colors.white),
                  onPressed: () => _confirmLogout(context, authCtrl),
                ),
            ],
          ),

          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  const SizedBox(height: 8),

                  if (_isEditing) ...[
                    _buildEditForm(ctrl),
                  ] else ...[
                    _buildInfoCard(name, email, phone),
                  ],

                  const SizedBox(height: 16),
                  _buildMenuSection(context, authCtrl),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _defaultAvatar(String name) {
    final initial = name.isNotEmpty ? name[0].toUpperCase() : 'C';
    return Container(
      color: AppTheme.primaryRed,
      child: Center(
        child: Text(initial, style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold)),
      ),
    );
  }

  Widget _buildInfoCard(String name, String email, String phone) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 10)],
      ),
      child: Column(
        children: [
          _infoRow(Icons.person_rounded, 'Nama', name),
          const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 56),
          _infoRow(Icons.email_rounded, 'Email', email.isEmpty ? '-' : email),
          const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 56),
          _infoRow(Icons.phone_rounded, 'No. HP', phone.isEmpty ? '-' : phone),
          const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 56),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: GestureDetector(
              onTap: () => setState(() => _isEditing = true),
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 10),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEE2E2),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.edit_rounded, color: AppTheme.primaryRed, size: 16),
                    SizedBox(width: 8),
                    Text('Edit Profil', style: TextStyle(color: AppTheme.primaryRed, fontSize: 13, fontWeight: FontWeight.bold)),
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
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: const Color(0xFFFEE2E2),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: AppTheme.primaryRed, size: 18),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600)),
              Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildEditForm(CustomerController ctrl) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Edit Profil', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
          const SizedBox(height: 16),
          _formField('Nama Lengkap', _nameCtrl, Icons.person_rounded),
          const SizedBox(height: 12),
          _formField('No. Telepon', _phoneCtrl, Icons.phone_rounded, keyboardType: TextInputType.phone),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () => setState(() => _isEditing = false),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('Batal'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton(
                  onPressed: () async {
                    final ok = await ctrl.updateProfile({
                      'name': _nameCtrl.text,
                      'phone': _phoneCtrl.text,
                    });
                    if (ok && mounted) {
                      setState(() => _isEditing = false);
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Profil berhasil diperbarui!'), backgroundColor: Colors.green),
                      );
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primaryRed,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('Simpan', style: TextStyle(fontWeight: FontWeight.bold)),
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
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: AppTheme.primaryRed, size: 20),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppTheme.primaryRed, width: 2),
        ),
      ),
    );
  }

  Widget _buildMenuSection(BuildContext context, AuthController authCtrl) {
    final menuItems = [
      {'icon': Icons.location_on_rounded, 'label': 'Alamat Pengantaran', 'color': const Color(0xFF2563EB), 'onTap': () {}},
      {'icon': Icons.notifications_rounded, 'label': 'Notifikasi', 'color': const Color(0xFFD97706), 'onTap': () {}},
      {'icon': Icons.help_outline_rounded, 'label': 'Bantuan & FAQ', 'color': const Color(0xFF059669), 'onTap': () {}},
      {'icon': Icons.info_outline_rounded, 'label': 'Tentang CicalengkaGO', 'color': const Color(0xFF7C3AED), 'onTap': () {}},
    ];

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          ...menuItems.map((item) => Column(
            children: [
              ListTile(
                onTap: item['onTap'] as VoidCallback,
                leading: Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: (item['color'] as Color).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(item['icon'] as IconData, color: item['color'] as Color, size: 18),
                ),
                title: Text(item['label'] as String, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                trailing: const Icon(Icons.chevron_right_rounded, color: Color(0xFFCBD5E1)),
              ),
              const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 56),
            ],
          )),
          // Logout
          ListTile(
            onTap: () => _confirmLogout(context, authCtrl),
            leading: Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: const Color(0xFFFEE2E2),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.logout_rounded, color: AppTheme.primaryRed, size: 18),
            ),
            title: const Text('Keluar', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppTheme.primaryRed)),
          ),
        ],
      ),
    );
  }

  Widget _buildGuestView(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(40),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const CicalengkaGoLogo(size: 72, borderRadius: 22),
              const SizedBox(height: 24),
              const Text('Masuk untuk Akses Penuh', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
              const SizedBox(height: 8),
              const Text(
                'Login untuk melihat profil, riwayat pesanan, dan fitur eksklusif lainnya.',
                style: TextStyle(fontSize: 13, color: Color(0xFF64748B)),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 32),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primaryRed,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen())),
                  child: const Text('Masuk / Daftar', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
                ),
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
        title: const Text('Konfirmasi Keluar', style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold)),
        content: const Text('Yakin ingin keluar dari akun CicalengkaGO?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primaryRed, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
            onPressed: () {
              Navigator.pop(ctx);
              authCtrl.logout();
            },
            child: const Text('Keluar'),
          ),
        ],
      ),
    );
  }
}
