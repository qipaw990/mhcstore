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
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Stack(
            children: [
              CircleAvatar(
                radius: 38,
                backgroundColor: const Color(0xFFFEE2E2),
                backgroundImage: avatar != null ? NetworkImage(ApiConstants.formatImageUrl(avatar)) : null,
                child: avatar == null
                    ? const Icon(Icons.person_rounded, color: AppTheme.primaryRed, size: 40)
                    : null,
              ),
              Positioned(
                bottom: 0,
                right: 0,
                child: Container(
                  width: 16,
                  height: 16,
                  decoration: BoxDecoration(
                    color: driverCtrl.isOnline ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 2),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            name,
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
          ),
          const SizedBox(height: 2),
          Text(
            phone,
            style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
            decoration: BoxDecoration(
              color: const Color(0xFFFEF2F2),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: const Color(0xFFFCA5A5)),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.two_wheeler_rounded, color: AppTheme.primaryRed, size: 14),
                const SizedBox(width: 6),
                Text(
                  '$vehicleType ($vehicleNumber)',
                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
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
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Container(
                      width: 36,
                      height: 36,
                      decoration: const BoxDecoration(
                        color: Color(0xFFFEF3C7),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.star_rounded, color: Color(0xFFD97706), size: 20),
                    ),
                    const SizedBox(width: 10),
                    const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Rating Saya & Ulasan Pelanggan',
                          style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        ),
                        Text(
                          'Penilaian kepuasan pengantaran pembeli',
                          style: TextStyle(fontSize: 10, color: Color(0xFF64748B)),
                        ),
                      ],
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEF3C7),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: const Color(0xFFFCD34D)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.star_rounded, color: Color(0xFFD97706), size: 12),
                      const SizedBox(width: 4),
                      Text(
                        '${rating.toStringAsFixed(1)} / 5.0',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF92400E)),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),

          if (reviews.isEmpty)
            Padding(
              padding: const EdgeInsets.all(20),
              child: Center(
                child: Column(
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: const BoxDecoration(
                        color: Color(0xFFFEF3C7),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.chat_bubble_outline_rounded, color: Color(0xFFD97706), size: 22),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Belum Ada Ulasan Driver',
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    ),
                    const SizedBox(height: 2),
                    const Text(
                      'Penilaian dari pelanggan yang Anda antar orderannya akan muncul di sini.',
                      style: TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              padding: const EdgeInsets.all(12),
              itemCount: reviews.length > 5 ? 5 : reviews.length,
              separatorBuilder: (ctx, index) => const SizedBox(height: 8),
              itemBuilder: (ctx, i) => _buildReviewItem(reviews[i]),
            ),
        ],
      ),
    );
  }

  Widget _buildReviewItem(Map<String, dynamic> rev) {
    final customerName = rev['customer_name'] ?? 'Pelanggan';
    final customerAvatar = rev['customer_avatar'] as String?;
    final ratingVal = int.tryParse(rev['rating']?.toString() ?? '5') ?? 5;
    final comment = rev['comment'] ?? '';
    final orderCode = rev['order_code'] ?? '#ORD';

    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 14,
                backgroundColor: const Color(0xFFE2E8F0),
                backgroundImage: customerAvatar != null ? NetworkImage(ApiConstants.formatImageUrl(customerAvatar)) : null,
                child: customerAvatar == null ? const Icon(Icons.person_rounded, size: 16, color: Color(0xFF64748B)) : null,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(customerName, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                    Text(orderCode.toString(), style: const TextStyle(fontSize: 9.5, color: Color(0xFF94A3B8))),
                  ],
                ),
              ),
              Row(
                children: List.generate(
                  5,
                  (idx) => Icon(
                    idx < ratingVal ? Icons.star_rounded : Icons.star_border_rounded,
                    color: const Color(0xFFF59E0B),
                    size: 14,
                  ),
                ),
              ),
            ],
          ),
          if (comment.isNotEmpty) ...[
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(8),
                border: const Border(left: BorderSide(color: Color(0xFFF59E0B), width: 3)),
              ),
              child: Text(
                '"$comment"',
                style: const TextStyle(fontSize: 10.5, color: Color(0xFF334155), fontStyle: FontStyle.italic),
              ),
            ),
          ],
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
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 2),
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
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: const Color(0xFFCBD5E1)),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.lock_rounded, size: 11, color: Color(0xFF64748B)),
                      SizedBox(width: 4),
                      Text('Terkunci', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          _infoTile(Icons.person_rounded, 'Nama Lengkap Driver', name),
          const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 50),
          _infoTile(Icons.email_rounded, 'Alamat Email', email),
          const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 50),
          _infoTile(Icons.phone_rounded, 'Nomor HP / WhatsApp', phone),
          const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 50),
          _infoTile(Icons.motorcycle_rounded, 'Tipe Kendaraan', vehicleType),
          const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 50),
          _infoTile(Icons.badge_rounded, 'Plat Nomor Kendaraan', vehicleNumber),
          const SizedBox(height: 10),
          
          // Lock security notice
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: const Row(
                children: [
                  Icon(Icons.verified_user_rounded, color: Color(0xFF0284C7), size: 16),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Data identitas & kendaraan diverifikasi resmi oleh Admin. Untuk ubah nomor/kendaraan, hubungi CS.',
                      style: TextStyle(fontSize: 10, color: Color(0xFF475569)),
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
                  backgroundColor: const Color(0xFF0F172A),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
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
              color: Color(0xFFFEF2F2),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: AppTheme.primaryRed, size: 16),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
                Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
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
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 2),
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
                  Icon(Icons.lock_reset_rounded, color: Color(0xFF0F172A), size: 20),
                  SizedBox(width: 6),
                  Text('Ganti Kata Sandi Driver', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                ],
              ),
              IconButton(
                icon: const Icon(Icons.close_rounded, size: 20),
                onPressed: () => setState(() => _isEditing = false),
              ),
            ],
          ),
          const Divider(height: 16),

          const Text(
            'Demi keamanan akun mitra, hanya kata sandi yang dapat diubah secara mandiri.',
            style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
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
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: () => setState(() => _isEditing = false),
                  child: const Text('Batal', style: TextStyle(color: Color(0xFF64748B))),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF0F172A),
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
                                const SnackBar(content: Text('✅ Kata sandi berhasil diperbarui!'), backgroundColor: Colors.green),
                              );
                            } else {
                              messenger.showSnackBar(
                                const SnackBar(content: Text('Gagal memperbarui kata sandi. Periksa kata sandi saat ini.'), backgroundColor: Colors.red),
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
      style: const TextStyle(fontSize: 12.5),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
        prefixIcon: Icon(icon, color: AppTheme.primaryRed, size: 18),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      ),
    );
  }

  Widget _buildPasswordField(String label, TextEditingController ctrl, bool obscure, VoidCallback onToggle) {
    return TextField(
      controller: ctrl,
      obscureText: obscure,
      style: const TextStyle(fontSize: 12.5),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
        prefixIcon: const Icon(Icons.lock_outline_rounded, color: AppTheme.primaryRed, size: 18),
        suffixIcon: IconButton(
          icon: Icon(obscure ? Icons.visibility_off_rounded : Icons.visibility_rounded, size: 18, color: const Color(0xFF94A3B8)),
          onPressed: onToggle,
        ),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      ),
    );
  }

  Widget _buildActionMenuCard(BuildContext context, AuthController authCtrl) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 2),
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
                decoration: const BoxDecoration(color: Color(0xFFDCFCE7), shape: BoxShape.circle),
                child: const Icon(Icons.help_outline_rounded, color: Color(0xFF16A34A), size: 18),
              ),
              title: const Text('Bantuan & FAQ Kurir', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
              trailing: const Icon(Icons.chevron_right_rounded, size: 18, color: Color(0xFF94A3B8)),
            ),
            const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 50),
            ListTile(
              onTap: () {},
              leading: Container(
                width: 32,
                height: 32,
                decoration: const BoxDecoration(color: Color(0xFFDBEAFE), shape: BoxShape.circle),
                child: const Icon(Icons.verified_user_rounded, color: Color(0xFF2563EB), size: 18),
              ),
              title: const Text('Ketentuan & Syarat Layanan', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
              trailing: const Icon(Icons.chevron_right_rounded, size: 18, color: Color(0xFF94A3B8)),
            ),
            const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 50),
            ListTile(
              onTap: () => _confirmLogout(context, authCtrl),
              leading: Container(
                width: 32,
                height: 32,
                decoration: const BoxDecoration(color: Color(0xFFFEF2F2), shape: BoxShape.circle),
                child: const Icon(Icons.logout_rounded, color: AppTheme.primaryRed, size: 18),
              ),
              title: const Text('Keluar dari Akun Driver', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: AppTheme.primaryRed)),
              trailing: const Icon(Icons.chevron_right_rounded, size: 18, color: AppTheme.primaryRed),
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
        title: const Text('Konfirmasi Keluar', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        content: const Text('Yakin ingin keluar dari akun Driver CicalengkaGO?', style: TextStyle(fontSize: 12.5)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryRed,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () {
              Navigator.pop(ctx);
              authCtrl.logout();
            },
            child: const Text('Keluar', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }
}
