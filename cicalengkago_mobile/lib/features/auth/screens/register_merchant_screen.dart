import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import 'package:geolocator/geolocator.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
import '../../../core/widgets/uber_pill_button.dart';
import '../controllers/auth_controller.dart';

class RegisterMerchantScreen extends StatefulWidget {
  const RegisterMerchantScreen({super.key});

  @override
  State<RegisterMerchantScreen> createState() => _RegisterMerchantScreenState();
}

class _RegisterMerchantScreenState extends State<RegisterMerchantScreen> {
  final _formKey = GlobalKey<FormState>();

  final TextEditingController _nameCtrl = TextEditingController();
  final TextEditingController _phoneCtrl = TextEditingController();
  final TextEditingController _emailCtrl = TextEditingController();
  final TextEditingController _passwordCtrl = TextEditingController();
  final TextEditingController _storeNameCtrl = TextEditingController();
  final TextEditingController _storePhoneCtrl = TextEditingController();
  final TextEditingController _storeAddressCtrl = TextEditingController();
  final TextEditingController _latCtrl = TextEditingController(text: '-6.9840');
  final TextEditingController _lngCtrl = TextEditingController(text: '107.8340');

  String _selectedModuleId = '1';
  bool _obscurePass = true;
  bool _isDetectingGps = false;
  bool _isSuccessPending = false;
  String _registeredStoreName = '';

  File? _ktpFile;
  File? _logoFile;
  File? _coverFile;

  final List<Map<String, String>> _modules = [
    {'id': '1', 'name': 'Kuliner / Resto / Makanan'},
    {'id': '2', 'name': 'Mart / Sembako / Toko'},
    {'id': '3', 'name': 'Apotek & Kesehatan'},
    {'id': '4', 'name': 'Kebutuhan Lainnya'},
  ];

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _emailCtrl.dispose();
    _passwordCtrl.dispose();
    _storeNameCtrl.dispose();
    _storePhoneCtrl.dispose();
    _storeAddressCtrl.dispose();
    _latCtrl.dispose();
    _lngCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickImage(String type) async {
    final picker = ImagePicker();
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(16))),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.camera_alt_rounded, color: AppTheme.primaryRed),
                title: const Text('Ambil Foto Kamera', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5)),
                onTap: () => Navigator.pop(ctx, ImageSource.camera),
              ),
              ListTile(
                leading: const Icon(Icons.photo_library_rounded, color: Color(0xFF2563EB)),
                title: const Text('Pilih dari Galeri', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5)),
                onTap: () => Navigator.pop(ctx, ImageSource.gallery),
              ),
            ],
          ),
        ),
      ),
    );

    if (source == null) return;

    final picked = await picker.pickImage(
      source: source,
      maxWidth: 1024,
      maxHeight: 1024,
      imageQuality: 75,
    );
    if (picked != null) {
      setState(() {
        if (type == 'ktp') _ktpFile = File(picked.path);
        if (type == 'logo') _logoFile = File(picked.path);
        if (type == 'cover') _coverFile = File(picked.path);
      });
    }
  }

  Future<void> _detectGps() async {
    setState(() => _isDetectingGps = true);
    try {
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.whileInUse || permission == LocationPermission.always) {
        Position position = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.high,
          timeLimit: const Duration(seconds: 10),
        );
        _latCtrl.text = position.latitude.toStringAsFixed(6);
        _lngCtrl.text = position.longitude.toStringAsFixed(6);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Titik GPS toko berhasil dikalibrasi!'), backgroundColor: Color(0xFF16A34A)),
          );
        }
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Gagal deteksi GPS. Menggunakan titik default Cicalengka.'), backgroundColor: Colors.orange),
        );
      }
    } finally {
      if (mounted) setState(() => _isDetectingGps = false);
    }
  }

  Future<void> _openWhatsAppCS() async {
    final msg = 'Halo Admin CS CicalengkaGO, saya telah mengajukan pendaftaran toko "$_registeredStoreName" dan ingin konfirmasi proses review.';
    final url = 'https://wa.me/6285158397756?text=${Uri.encodeComponent(msg)}';
    try {
      final uri = Uri.parse(url);
      if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
        await launchUrl(uri);
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final authCtrl = context.watch<AuthController>();

    if (_isSuccessPending) {
      return _buildPendingReviewView();
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 1,
        title: const Text(
          'Daftar Mitra Merchant',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_rounded, size: 18, color: Color(0xFF0F172A)),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header Banner
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFFDC2626), Color(0xFF991B1B)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(18),
                  boxShadow: [
                    BoxShadow(color: AppTheme.primaryRed.withValues(alpha: 0.25), blurRadius: 10, offset: const Offset(0, 4)),
                  ],
                ),
                child: Row(
                  children: [
                    const CicalengkaGoLogo(size: 44, borderRadius: 12, showShadow: false),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: const [
                          Text(
                            'Buka Toko di CicalengkaGO',
                            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
                          ),
                          SizedBox(height: 3),
                          Text(
                            'Lengkapi dokumen KTP, foto toko, & radar pengantaran untuk mulai berjualan.',
                            style: TextStyle(fontSize: 11, color: Color(0xFFFCA5A5), height: 1.3),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),

              // ── SEKSI 1: DATA PEMILIK & FOTO KTP ──
              _buildSectionTitle(Icons.person_rounded, '1. Informasi Pemilik & KTP'),
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildLabel('Nama Lengkap Pemilik *'),
                    TextFormField(
                      controller: _nameCtrl,
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Nama pemilik wajib diisi' : null,
                      decoration: _inputDecoration('Nama sesuai KTP', Icons.person_outline_rounded),
                    ),
                    const SizedBox(height: 12),

                    _buildLabel('Nomor WhatsApp Pemilik *'),
                    TextFormField(
                      controller: _phoneCtrl,
                      keyboardType: TextInputType.phone,
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Nomor WhatsApp wajib diisi' : null,
                      decoration: _inputDecoration('Contoh: 081234567890', Icons.phone_android_rounded),
                    ),
                    const SizedBox(height: 12),

                    _buildLabel('Email Pemilik *'),
                    TextFormField(
                      controller: _emailCtrl,
                      keyboardType: TextInputType.emailAddress,
                      validator: (v) => (v == null || !v.contains('@')) ? 'Email tidak valid' : null,
                      decoration: _inputDecoration('nama@email.com', Icons.email_outlined),
                    ),
                    const SizedBox(height: 12),

                    _buildLabel('Kata Sandi Akun Login *'),
                    TextFormField(
                      controller: _passwordCtrl,
                      obscureText: _obscurePass,
                      validator: (v) => (v == null || v.length < 6) ? 'Kata sandi minimal 6 karakter' : null,
                      decoration: _inputDecoration('Minimal 6 karakter', Icons.lock_outline_rounded).copyWith(
                        suffixIcon: IconButton(
                          icon: Icon(_obscurePass ? Icons.visibility_off_outlined : Icons.visibility_outlined, size: 18, color: const Color(0xFF94A3B8)),
                          onPressed: () => setState(() => _obscurePass = !_obscurePass),
                        ),
                      ),
                    ),
                    const SizedBox(height: 14),

                    // Upload KTP Box
                    _buildLabel('Foto KTP Pemilik Toko *'),
                    _buildPhotoUploadBox(
                      file: _ktpFile,
                      icon: Icons.card_membership_rounded,
                      title: 'Upload Foto KTP Pemilik',
                      subtitle: 'Wajib untuk verifikasi identitas pemilik usaha',
                      onTap: () => _pickImage('ktp'),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),

              // ── SEKSI 2: PROFIL USAHA & FOTO TOKO ──
              _buildSectionTitle(Icons.storefront_rounded, '2. Profil Usaha & Foto Toko'),
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildLabel('Nama Toko / Resto / Warung *'),
                    TextFormField(
                      controller: _storeNameCtrl,
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Nama toko wajib diisi' : null,
                      decoration: _inputDecoration('Contoh: Warung Nasi Bu Imas', Icons.storefront_outlined),
                    ),
                    const SizedBox(height: 12),

                    _buildLabel('Kategori / Modul Usaha *'),
                    DropdownButtonFormField<String>(
                      value: _selectedModuleId,
                      items: _modules.map((m) => DropdownMenuItem(value: m['id'], child: Text(m['name']!, style: const TextStyle(fontSize: 12.5)))).toList(),
                      onChanged: (v) => setState(() => _selectedModuleId = v ?? '1'),
                      decoration: _inputDecoration('', Icons.category_outlined),
                    ),
                    const SizedBox(height: 12),

                    _buildLabel('Nomor WhatsApp Khusus Toko'),
                    TextFormField(
                      controller: _storePhoneCtrl,
                      keyboardType: TextInputType.phone,
                      decoration: _inputDecoration('Nomor kontak CS toko untuk pesanan', Icons.support_agent_rounded),
                    ),
                    const SizedBox(height: 12),

                    _buildLabel('Alamat Lengkap Toko / Titik Jemput *'),
                    TextFormField(
                      controller: _storeAddressCtrl,
                      maxLines: 2,
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Alamat lengkap toko wajib diisi' : null,
                      decoration: _inputDecoration('Jl. Raya Cicalengka No. ..., Patokan: ...', Icons.location_on_outlined),
                    ),
                    const SizedBox(height: 14),

                    // Upload Logo & Foto Toko
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _buildLabel('Logo Toko *'),
                              _buildPhotoUploadBox(
                                file: _logoFile,
                                icon: Icons.image_outlined,
                                title: 'Logo Toko',
                                subtitle: 'Avatar bulat',
                                height: 95,
                                onTap: () => _pickImage('logo'),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _buildLabel('Foto Depan Toko *'),
                              _buildPhotoUploadBox(
                                file: _coverFile,
                                icon: Icons.camera_alt_outlined,
                                title: 'Foto Toko',
                                subtitle: 'Tampak depan',
                                height: 95,
                                onTap: () => _pickImage('cover'),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),

              // ── SEKSI 3: RADAR DELIVERY & GPS KALIBRASI ──
              _buildSectionTitle(Icons.radar_rounded, '3. Radar Delivery & Kalibrasi GPS'),
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Radar Visualizer Card
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: const [
                                  Icon(Icons.radar_rounded, color: Color(0xFFEF4444), size: 20),
                                  SizedBox(width: 8),
                                  Text(
                                    'Radar Jangkauan Antar',
                                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.white),
                                  ),
                                ],
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: AppTheme.primaryRed,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: const Text(
                                  'Radius 5 KM',
                                  style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Container(
                                width: 36,
                                height: 36,
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.1),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.broadcast_on_home_rounded, color: Color(0xFF38BDF8), size: 18),
                              ),
                              const SizedBox(width: 10),
                              const Expanded(
                                child: Text(
                                  'Pesanan pelanggan dalam radius radar 5 km akan langsung tersambung otomatis ke toko Anda.',
                                  style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8), height: 1.3),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),

                    // Coordinates Inputs
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _latCtrl,
                            readOnly: true,
                            decoration: _inputDecoration('Latitude', Icons.explore_outlined),
                            style: const TextStyle(fontSize: 12),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: TextFormField(
                            controller: _lngCtrl,
                            readOnly: true,
                            decoration: _inputDecoration('Longitude', Icons.explore_outlined),
                            style: const TextStyle(fontSize: 12),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),

                    // GPS Button
                    OutlinedButton.icon(
                      onPressed: _isDetectingGps ? null : _detectGps,
                      style: OutlinedButton.styleFrom(
                        foregroundColor: const Color(0xFF2563EB),
                        side: const BorderSide(color: Color(0xFF93C5FD)),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                        minimumSize: const Size(double.infinity, 42),
                      ),
                      icon: _isDetectingGps
                          ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                          : const Icon(Icons.my_location_rounded, size: 16),
                      label: Text(
                        _isDetectingGps ? 'Mendeteksi Koordinat GPS...' : '📍 Kalibrasi GPS Titik Toko Saat Ini',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),

              // Review Notice Card
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF3C7),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFFDE68A)),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: const [
                    Icon(Icons.info_outline_rounded, size: 18, color: Color(0xFFB45309)),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Dokumen KTP, Logo, Foto Toko, dan Titik Lokasi akan diperiksa oleh Tim Admin CicalengkaGO sebelum toko dapat dibuka untuk menerima pesanan.',
                        style: TextStyle(fontSize: 11, color: Color(0xFF92400E), height: 1.3, fontWeight: FontWeight.w500),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Submit Button
              UberPillButton(
                label: authCtrl.isLoading ? 'Memproses Pendaftaran...' : 'Ajukan Pendaftaran Toko',
                icon: Icons.send_rounded,
                onPressed: authCtrl.isLoading
                    ? null
                    : () async {
                        if (!_formKey.currentState!.validate()) return;

                        if (_ktpFile == null) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Harap unggah Foto KTP Pemilik Toko.'), backgroundColor: AppTheme.primaryRed),
                          );
                          return;
                        }

                        final res = await authCtrl.registerMerchant(
                          name: _nameCtrl.text.trim(),
                          email: _emailCtrl.text.trim(),
                          phone: _phoneCtrl.text.trim(),
                          password: _passwordCtrl.text.trim(),
                          storeName: _storeNameCtrl.text.trim(),
                          storePhone: _storePhoneCtrl.text.trim(),
                          storeAddress: _storeAddressCtrl.text.trim(),
                          moduleId: _selectedModuleId,
                          latitude: _latCtrl.text.trim(),
                          longitude: _lngCtrl.text.trim(),
                          ktpPath: _ktpFile?.path,
                          logoPath: _logoFile?.path,
                          coverPath: _coverFile?.path,
                        );

                        if (res['success'] == true && mounted) {
                          setState(() {
                            _registeredStoreName = _storeNameCtrl.text.trim();
                            _isSuccessPending = true;
                          });
                        } else if (mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text(res['message'] ?? 'Gagal mendaftar'), backgroundColor: AppTheme.primaryRed),
                          );
                        }
                      },
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPhotoUploadBox({
    required File? file,
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
    double height = 110,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: height,
        width: double.infinity,
        decoration: BoxDecoration(
          color: const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: file != null ? const Color(0xFF16A34A) : const Color(0xFFCBD5E1), width: 1.5),
        ),
        child: file != null
            ? Stack(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Image.file(file, width: double.infinity, height: double.infinity, fit: BoxFit.cover),
                  ),
                  Positioned(
                    top: 4,
                    right: 4,
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: const BoxDecoration(color: Colors.black54, shape: BoxShape.circle),
                      child: const Icon(Icons.edit_rounded, color: Colors.white, size: 14),
                    ),
                  ),
                ],
              )
            : Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(icon, size: 24, color: const Color(0xFF64748B)),
                  const SizedBox(height: 4),
                  Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Color(0xFF1E293B))),
                  const SizedBox(height: 2),
                  Text(subtitle, style: const TextStyle(fontSize: 9.5, color: Color(0xFF94A3B8))),
                ],
              ),
      ),
    );
  }

  Widget _buildPendingReviewView() {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(20),
                decoration: const BoxDecoration(
                  color: Color(0xFFFEF3C7),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.hourglass_top_rounded, size: 54, color: Color(0xFFD97706)),
              ),
              const SizedBox(height: 20),
              const Text(
                'Pendaftaran Toko Diterima!',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF3C7),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Text(
                  '⏳ Menunggu Review Dokumen Admin',
                  style: TextStyle(color: Color(0xFFB45309), fontSize: 11.5, fontWeight: FontWeight.bold),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                'Dokumen KTP, Logo, Foto Toko, dan Radar Lokasi "$_registeredStoreName" sedang diperiksa oleh Tim Admin CicalengkaGO.',
                style: const TextStyle(fontSize: 12.5, color: Color(0xFF64748B), height: 1.4),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: _openWhatsAppCS,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF16A34A),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 20),
                  shape: const StadiumBorder(),
                  elevation: 0,
                ),
                icon: const Icon(Icons.chat_rounded, size: 16),
                label: const Text('Konfirmasi ke CS Admin (WhatsApp)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
              ),
              const SizedBox(height: 12),
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Kembali ke Halaman Utama', style: TextStyle(color: Color(0xFF64748B), fontSize: 12.5)),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSectionTitle(IconData icon, String title) {
    return Row(
      children: [
        Icon(icon, size: 16, color: AppTheme.primaryRed),
        const SizedBox(width: 6),
        Text(title, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF1E293B))),
      ],
    );
  }

  Widget _buildLabel(String label) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Text(label, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
    );
  }

  InputDecoration _inputDecoration(String hint, IconData icon) {
    return InputDecoration(
      hintText: hint,
      hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
      prefixIcon: Icon(icon, size: 17, color: const Color(0xFF94A3B8)),
      filled: true,
      fillColor: const Color(0xFFF8FAFC),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppTheme.primaryRed)),
    );
  }
}
