import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/controllers/auth_controller.dart';
import '../controllers/driver_controller.dart';

class DriverProfileScreen extends StatefulWidget {
  const DriverProfileScreen({super.key});

  @override
  State<DriverProfileScreen> createState() => _DriverProfileScreenState();
}

class _DriverProfileScreenState extends State<DriverProfileScreen> {
  bool _isEditing = false;
  bool _showPasswordSection = false;

  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _vehicleTypeCtrl = TextEditingController();
  final _vehicleNumberCtrl = TextEditingController();

  final _currentPasswordCtrl = TextEditingController();
  final _newPasswordCtrl = TextEditingController();
  final _confirmPasswordCtrl = TextEditingController();

  bool _obscureCurrentPassword = true;
  bool _obscureNewPassword = true;
  bool _obscureConfirmPassword = true;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DriverController>().fetchProfile();
    });
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _vehicleTypeCtrl.dispose();
    _vehicleNumberCtrl.dispose();
    _currentPasswordCtrl.dispose();
    _newPasswordCtrl.dispose();
    _confirmPasswordCtrl.dispose();
    super.dispose();
  }

  void _populateFormFields(Map<String, dynamic>? user, Map<String, dynamic>? driver) {
    _nameCtrl.text = user?['name'] ?? '';
    _emailCtrl.text = user?['email'] ?? '';
    _phoneCtrl.text = user?['phone'] ?? '';
    _vehicleTypeCtrl.text = driver?['vehicle_type'] ?? 'Motor Honda Beat';
    _vehicleNumberCtrl.text = driver?['vehicle_number'] ?? 'D 1234 CCG';
  }

  @override
  Widget build(BuildContext context) {
    final authCtrl = context.watch<AuthController>();
    final driverCtrl = context.watch<DriverController>();
    final profileData = driverCtrl.driverProfile;
    final user = profileData?['user'] as Map<String, dynamic>? ?? authCtrl.user;
    final driver = profileData?['driver'] as Map<String, dynamic>? ?? {};
    final reviews = (profileData?['reviews'] as List<dynamic>?) ?? driverCtrl.reviews;

    final name = user?['name'] ?? 'Mitra Driver';
    final email = user?['email'] ?? '-';
    final phone = user?['phone'] ?? '-';
    final avatar = user?['avatar'] as String?;
    final vehicleType = driver['vehicle_type'] ?? 'Motor Honda Beat';
    final vehicleNumber = driver['vehicle_number'] ?? 'D 1234 CCG';
    final rating = driverCtrl.driverRating;

    return RefreshIndicator(
      color: AppTheme.primaryRed,
      onRefresh: () => driverCtrl.fetchProfile(),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // 1. Header Card - Driver Info Card (Matching views/delivery/profile.php)
            _buildDriverHeaderCard(name, phone, vehicleType, vehicleNumber, avatar, driverCtrl),
            const SizedBox(height: 12),

            // 2. Rating & Customer Reviews Card
            _buildRatingReviewsCard(rating, reviews),
            const SizedBox(height: 12),

            // 3. Edit Form or View Profile Info
            if (_isEditing)
              _buildEditProfileForm(user, driver, driverCtrl)
            else
              _buildProfileInfoCard(name, email, phone, vehicleType, vehicleNumber, user, driver),

            const SizedBox(height: 16),

            // 4. Menu & Logout Card
            _buildActionMenuCard(context, authCtrl),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _buildDriverHeaderCard(
    String name,
    String phone,
    String vehicleType,
    String vehicleNumber,
    String? avatar,
    DriverController driverCtrl,
  ) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E293B)),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 10,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Stack(
            children: [
              CircleAvatar(
                radius: 38,
                backgroundColor: const Color(0xFF450A0A),
                backgroundImage: avatar != null ? NetworkImage(ApiConstants.formatImageUrl(avatar)) : null,
                child: avatar == null
                    ? const Icon(Icons.person_rounded, color: Color(0xFFEF4444), size: 40)
                    : null,
              ),
              Positioned(
                bottom: 0,
                right: 0,
                child: Container(
                  width: 16,
                  height: 16,
                  decoration: BoxDecoration(
                    color: driverCtrl.isOnline ? const Color(0xFF22C55E) : const Color(0xFFEF4444),
                    shape: BoxShape.circle,
                    border: Border.all(color: const Color(0xFF0F172A), width: 2),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            name,
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
          ),
          const SizedBox(height: 2),
          Text(
            phone,
            style: const TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8)),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
            decoration: BoxDecoration(
              color: const Color(0xFF450A0A),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: const Color(0xFF7F1D1D)),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.two_wheeler_rounded, color: Color(0xFFFCA5A5), size: 14),
                const SizedBox(width: 6),
                Text(
                  '$vehicleType ($vehicleNumber)',
                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFFFCA5A5)),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRatingReviewsCard(double rating, List<dynamic> reviews) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E293B)),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 10,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: const BoxDecoration(
              color: Color(0xFF451A03),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.star_rounded, color: Color(0xFFFBBF24), size: 24),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text(
                      '${rating.toStringAsFixed(1)} ★',
                      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFFFDE68A)),
                    ),
                    const SizedBox(width: 6),
                    const Text('Kepuasan Pelanggan', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Colors.white)),
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  '${reviews.length} Ulasan Terverifikasi',
                  style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                ),
              ],
            ),
          ),
          const Icon(Icons.chevron_right_rounded, color: Color(0xFF64748B), size: 20),
        ],
      ),
    );
  }

  Widget _buildProfileInfoCard(
    String name,
    String email,
    String phone,
    String vehicleType,
    String vehicleNumber,
    Map<String, dynamic>? user,
    Map<String, dynamic>? driver,
  ) {
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E293B)),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 10,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Detail Informasi Driver',
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1E293B),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: const Color(0xFF334155)),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.lock_rounded, size: 11, color: Color(0xFF94A3B8)),
                      const SizedBox(width: 4),
                      Text('Terkunci', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF94A3B8))),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFF1E293B)),
          _infoTile(Icons.person_rounded, 'Nama Lengkap Driver', name),
          const Divider(height: 1, color: Color(0xFF1E293B), indent: 50),
          _infoTile(Icons.email_rounded, 'Alamat Email', email),
          const Divider(height: 1, color: Color(0xFF1E293B), indent: 50),
          _infoTile(Icons.phone_rounded, 'Nomor HP / WhatsApp', phone),
          const Divider(height: 1, color: Color(0xFF1E293B), indent: 50),
          _infoTile(Icons.motorcycle_rounded, 'Tipe Kendaraan', vehicleType),
          const Divider(height: 1, color: Color(0xFF1E293B), indent: 50),
          _infoTile(Icons.badge_rounded, 'Plat Nomor Kendaraan', vehicleNumber),
          const SizedBox(height: 10),
          
          // Lock security notice
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFF334155)),
              ),
              child: const Row(
                children: [
                  Icon(Icons.verified_user_rounded, color: Color(0xFF38BDF8), size: 16),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Data identitas & kendaraan diverifikasi resmi oleh Admin. Untuk ubah nomor/kendaraan, hubungi CS.',
                      style: TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),

          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF1E293B),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12), side: const BorderSide(color: Color(0xFF334155))),
                ),
                onPressed: () {
                  _currentPasswordCtrl.clear();
                  _newPasswordCtrl.clear();
                  _confirmPasswordCtrl.clear();
                  setState(() => _isEditing = true);
                },
                icon: const Icon(Icons.lock_reset_rounded, size: 16),
                label: const Text('Ganti Kata Sandi (Password)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
              ),
            ),
          ),
          const SizedBox(height: 8),
        ],
      ),
    );
  }

  Widget _infoTile(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: const BoxDecoration(
              color: Color(0xFF450A0A),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: const Color(0xFFEF4444), size: 16),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
                Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEditProfileForm(
    Map<String, dynamic>? user,
    Map<String, dynamic>? driver,
    DriverController driverCtrl,
  ) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E293B)),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 10,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Row(
                children: [
                  Icon(Icons.lock_reset_rounded, color: Colors.white, size: 20),
                  SizedBox(width: 6),
                  Text('Ganti Kata Sandi Driver', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white)),
                ],
              ),
              IconButton(
                icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF94A3B8)),
                onPressed: () => setState(() => _isEditing = false),
              ),
            ],
          ),
          const Divider(height: 16, color: Color(0xFF1E293B)),

          const Text(
            'Demi keamanan akun mitra, hanya kata sandi yang dapat diubah secara mandiri.',
            style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
          ),
          const SizedBox(height: 14),

          _buildPasswordField('Kata Sandi Saat Ini', _currentPasswordCtrl, _obscureCurrentPassword, () {
            setState(() => _obscureCurrentPassword = !_obscureCurrentPassword);
          }),
          const SizedBox(height: 12),
          _buildPasswordField('Kata Sandi Baru (Minimal 6 Karakter)', _newPasswordCtrl, _obscureNewPassword, () {
            setState(() => _obscureNewPassword = !_obscureNewPassword);
          }),
          const SizedBox(height: 12),
          _buildPasswordField('Konfirmasi Kata Sandi Baru', _confirmPasswordCtrl, _obscureConfirmPassword, () {
            setState(() => _obscureConfirmPassword = !_obscureConfirmPassword);
          }),
          const SizedBox(height: 16),

          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    side: const BorderSide(color: Color(0xFF334155)),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: () => setState(() => _isEditing = false),
                  child: const Text('Batal', style: TextStyle(color: Color(0xFF94A3B8))),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFEF4444),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: _isSaving
                      ? null
                      : () async {
                          final currentPass = _currentPasswordCtrl.text.trim();
                          final newPass = _newPasswordCtrl.text.trim();
                          final confirmPass = _confirmPasswordCtrl.text.trim();

                          if (currentPass.isEmpty) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Kata sandi saat ini wajib diisi.')),
                            );
                            return;
                          }

                          if (newPass.length < 6) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Kata sandi baru minimal 6 karakter.')),
                            );
                            return;
                          }

                          if (newPass != confirmPass) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Konfirmasi kata sandi baru tidak cocok.')),
                            );
                            return;
                          }

                          final messenger = ScaffoldMessenger.of(context);
                          setState(() => _isSaving = true);
                          final ok = await driverCtrl.updateProfile(
                            name: user?['name'] ?? '',
                            email: user?['email'] ?? '',
                            phone: user?['phone'] ?? '',
                            vehicleType: driver?['vehicle_type'] ?? '',
                            vehicleNumber: driver?['vehicle_number'] ?? '',
                            currentPassword: currentPass,
                            newPassword: newPass,
                            confirmPassword: confirmPass,
                          );
                          setState(() => _isSaving = false);

                          if (mounted) {
                            if (ok) {
                              setState(() => _isEditing = false);
                              messenger.showSnackBar(
                                const SnackBar(
                                  content: Text('✅ Kata sandi berhasil diperbarui!'),
                                  backgroundColor: Colors.green,
                                ),
                              );
                            } else {
                              final errMsg = driverCtrl.lastErrorMessage ?? 'Gagal memperbarui kata sandi.';
                              showDialog(
                                context: context,
                                builder: (ctx) => AlertDialog(
                                  backgroundColor: const Color(0xFF0F172A),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20), side: const BorderSide(color: Color(0xFF1E293B))),
                                  title: const Row(
                                    children: [
                                      Icon(Icons.warning_amber_rounded, color: Color(0xFFEF4444), size: 24),
                                      SizedBox(width: 8),
                                      Text(
                                        'Gagal Ubah Sandi',
                                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                                      ),
                                    ],
                                  ),
                                  content: Text(
                                    errMsg,
                                    style: const TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
                                  ),
                                  actions: [
                                    ElevatedButton(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: const Color(0xFFEF4444),
                                        foregroundColor: Colors.white,
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                      ),
                                      onPressed: () => Navigator.of(ctx).pop(),
                                      child: const Text('Tutup', style: TextStyle(fontWeight: FontWeight.bold)),
                                    ),
                                  ],
                                ),
                              );
                            }
                          }
                        },
                  icon: _isSaving
                      ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Icon(Icons.save_rounded, size: 16),
                  label: Text(_isSaving ? 'Menyimpan...' : 'Simpan Kata Sandi', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTextField(String label, TextEditingController ctrl, IconData icon, {TextInputType? keyboardType}) {
    return TextField(
      controller: ctrl,
      keyboardType: keyboardType,
      style: const TextStyle(fontSize: 12.5, color: Colors.white),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: const TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8)),
        prefixIcon: Icon(icon, color: const Color(0xFFEF4444), size: 18),
        filled: true,
        fillColor: const Color(0xFF1E293B),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF334155))),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF334155))),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      ),
    );
  }

  Widget _buildPasswordField(String label, TextEditingController ctrl, bool obscure, VoidCallback onToggle) {
    return TextField(
      controller: ctrl,
      obscureText: obscure,
      style: const TextStyle(fontSize: 12.5, color: Colors.white),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: const TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8)),
        prefixIcon: const Icon(Icons.lock_outline_rounded, color: Color(0xFFEF4444), size: 18),
        filled: true,
        fillColor: const Color(0xFF1E293B),
        suffixIcon: IconButton(
          icon: Icon(obscure ? Icons.visibility_off_rounded : Icons.visibility_rounded, size: 18, color: const Color(0xFF94A3B8)),
          onPressed: onToggle,
        ),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF334155))),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF334155))),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      ),
    );
  }

  Widget _buildActionMenuCard(BuildContext context, AuthController authCtrl) {
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E293B)),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 10,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(16),
        clipBehavior: Clip.antiAlias,
        child: Column(
          children: [
            ListTile(
              onTap: () {},
              leading: Container(
                width: 32,
                height: 32,
                decoration: const BoxDecoration(color: Color(0xFF064E3B), shape: BoxShape.circle),
                child: const Icon(Icons.help_outline_rounded, color: Color(0xFF34D399), size: 18),
              ),
              title: const Text('Bantuan & FAQ Kurir', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Colors.white)),
              trailing: const Icon(Icons.chevron_right_rounded, size: 18, color: Color(0xFF64748B)),
            ),
            const Divider(height: 1, color: Color(0xFF1E293B), indent: 50),
            ListTile(
              onTap: () {},
              leading: Container(
                width: 32,
                height: 32,
                decoration: const BoxDecoration(color: Color(0xFF1E3A8A), shape: BoxShape.circle),
                child: const Icon(Icons.verified_user_rounded, color: Color(0xFF60A5FA), size: 18),
              ),
              title: const Text('Ketentuan & Syarat Layanan', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Colors.white)),
              trailing: const Icon(Icons.chevron_right_rounded, size: 18, color: Color(0xFF64748B)),
            ),
            const Divider(height: 1, color: Color(0xFF1E293B), indent: 50),
            ListTile(
              onTap: () => _confirmLogout(context, authCtrl),
              leading: Container(
                width: 32,
                height: 32,
                decoration: const BoxDecoration(color: Color(0xFF450A0A), shape: BoxShape.circle),
                child: const Icon(Icons.logout_rounded, color: Color(0xFFEF4444), size: 18),
              ),
              title: const Text('Keluar dari Akun Driver', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFFEF4444))),
              trailing: const Icon(Icons.chevron_right_rounded, size: 18, color: Color(0xFFEF4444)),
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
        backgroundColor: const Color(0xFF0F172A),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20), side: const BorderSide(color: Color(0xFF1E293B))),
        title: const Text('Konfirmasi Keluar', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white)),
        content: const Text('Yakin ingin keluar dari akun Driver CicalengkaGO?', style: TextStyle(fontSize: 12.5, color: Color(0xFF94A3B8))),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal', style: TextStyle(color: Color(0xFF94A3B8)))),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFEF4444),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () {
              Navigator.pop(ctx);
              authCtrl.logout();
            },
            child: const Text('Keluar', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
          ),
        ],
      ),
    );
  }
}
