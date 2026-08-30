import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/uber_pill_button.dart';
import '../../../core/widgets/location_picker_modal.dart';
import '../../auth/controllers/auth_controller.dart';
import '../../auth/screens/splash_screen.dart';
import '../controllers/merchant_controller.dart';

class StoreSettingsScreen extends StatefulWidget {
  const StoreSettingsScreen({super.key});

  @override
  State<StoreSettingsScreen> createState() => _StoreSettingsScreenState();
}

class _StoreSettingsScreenState extends State<StoreSettingsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final ctrl = context.read<MerchantController>();
      ctrl.fetchDashboardData();
      ctrl.fetchProfile();
      ctrl.fetchWallet();
    });
  }

  void _showWithdrawModal(BuildContext context, double balance) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _MerchantWithdrawBottomSheet(balance: balance),
    );
  }

  void _showEditProfileModal(BuildContext context, Map<String, dynamic> store) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _EditStoreProfileBottomSheet(store: store),
    );
  }

  Future<void> _launchWhatsAppSupport() async {
    const url = 'https://wa.me/6285158397756?text=Halo%20CS%20CicalengkaGO%2C%20saya%20mitra%20merchant%20toko%20ingin%20berkonsultasi.';
    try {
      final uri = Uri.parse(url);
      if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
        await launchUrl(uri);
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final merchantCtrl = context.watch<MerchantController>();
    final authCtrl = context.watch<AuthController>();
    final store = merchantCtrl.store ?? {};
    final wallet = merchantCtrl.wallet ?? {};
    final balance = double.tryParse(wallet['balance']?.toString() ?? '0') ?? 0.0;
    final totalWithdrawn = merchantCtrl.totalWithdrawn;
    final reviews = merchantCtrl.reviews;
    final rating = store['rating']?.toString() ?? '4.8';
    final reviewsCount = store['reviews_count']?.toString() ?? '${reviews.length}';

    final rawLogo = store['logo']?.toString();
    final logoUrl = ApiConstants.formatImageUrl(rawLogo);

    return RefreshIndicator(
      onRefresh: () async {
        await merchantCtrl.fetchDashboardData();
        await merchantCtrl.fetchWallet();
      },
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // ── KARTU DOMPET VENDOR CICALENGKAPAY ──
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.12),
                  blurRadius: 12,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: const [
                        Icon(Icons.account_balance_wallet_rounded, color: Color(0xFF38BDF8), size: 18),
                        SizedBox(width: 8),
                        Text(
                          'Dompet Pendapatan Resto',
                          style: TextStyle(fontSize: 12.5, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: const Color(0xFF334155),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Text(
                        'Komisi 90%',
                        style: TextStyle(color: Color(0xFF38BDF8), fontSize: 10, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  CurrencyFormatter.formatRupiah(balance),
                  style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: -0.5),
                ),
                const SizedBox(height: 14),
                const Divider(height: 1, color: Color(0xFF334155)),
                const SizedBox(height: 14),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Total Dicairkan', style: TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                        const SizedBox(height: 2),
                        Text(
                          CurrencyFormatter.formatRupiah(totalWithdrawn),
                          style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Color(0xFFE2E8F0)),
                        ),
                      ],
                    ),
                    ElevatedButton.icon(
                      onPressed: () => _showWithdrawModal(context, balance),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.primaryRed,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 0,
                      ),
                      icon: const Icon(Icons.payments_outlined, size: 16),
                      label: const Text('Tarik Saldo', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // ── KARTU PROFIL TOKO ──
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Informasi Resto / Toko',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                    ),
                    IconButton(
                      icon: const Icon(Icons.edit_outlined, color: Color(0xFF2563EB), size: 18),
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                      tooltip: 'Edit Profil Toko',
                      onPressed: () => _showEditProfileModal(context, store),
                    ),
                  ],
                ),
                const Divider(height: 18),
                Row(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: CachedNetworkImage(
                        imageUrl: logoUrl,
                        width: 50,
                        height: 50,
                        fit: BoxFit.cover,
                        errorWidget: (_, __, ___) => Container(
                          width: 50,
                          height: 50,
                          color: const Color(0xFFF1F5F9),
                          child: const Icon(Icons.storefront_rounded, color: Color(0xFF94A3B8), size: 24),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            store['name'] ?? 'Mitra Resto',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                          ),
                          const SizedBox(height: 3),
                          Row(
                            children: [
                              const Icon(Icons.star_rounded, size: 15, color: Colors.amber),
                              const SizedBox(width: 3),
                              Text(
                                '$rating ($reviewsCount ulasan)',
                                style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF475569)),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                _infoRow(Icons.location_on_outlined, store['address'] ?? 'Cicalengka, Kab. Bandung'),
                const SizedBox(height: 6),
                _infoRow(Icons.phone_outlined, store['phone'] ?? store['vendor_phone'] ?? '-'),
                const SizedBox(height: 6),
                _infoRow(Icons.access_time_rounded, '${store['opening_time'] ?? '08:00'} - ${store['closing_time'] ?? '22:00'} WIB'),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // ── ULASAN TERBARU PELANGGAN ──
          if (reviews.isNotEmpty) ...[
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Ulasan Pelanggan Terbaru',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Color(0xFF0F172A)),
                      ),
                      Text(
                        '⭐ $rating',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFFD97706)),
                      ),
                    ],
                  ),
                  const Divider(height: 16),
                  ...reviews.take(3).map((r) {
                    final rMap = r is Map ? r : {};
                    final custName = rMap['customer_name'] ?? 'Pelanggan';
                    final comment = rMap['comment'] ?? 'Pelayanan sangat memuaskan!';
                    final rRating = rMap['rating']?.toString() ?? '5';
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          CircleAvatar(
                            radius: 14,
                            backgroundColor: const Color(0xFFF1F5F9),
                            child: const Icon(Icons.person, size: 16, color: Color(0xFF64748B)),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(custName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF1E293B))),
                                    Text('⭐ $rRating', style: const TextStyle(fontSize: 11, color: Colors.amber, fontWeight: FontWeight.bold)),
                                  ],
                                ),
                                const SizedBox(height: 2),
                                Text(comment, style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B), height: 1.3)),
                              ],
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                ],
              ),
            ),
            const SizedBox(height: 16),
          ],

          // ── KARTU BANTUAN CS ──
          InkWell(
            onTap: _launchWhatsAppSupport,
            borderRadius: BorderRadius.circular(16),
            child: Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFF0FDF4),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFF86EFAC)),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: const BoxDecoration(color: Color(0xFF22C55E), shape: BoxShape.circle),
                    child: const Icon(Icons.chat_rounded, color: Colors.white, size: 16),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: const [
                        Text('Pusat Bantuan Mitra Merchant', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF15803D))),
                        SizedBox(height: 2),
                        Text('0851-5839-7756 • WhatsApp CS 24 Jam', style: TextStyle(fontSize: 11, color: Color(0xFF166534))),
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right_rounded, color: Color(0xFF15803D)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 14),

          // ── TOMBOL LOGOUT MITRA ──
          OutlinedButton.icon(
            onPressed: () {
              showDialog(
                context: context,
                builder: (dialogCtx) => AlertDialog(
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                  title: Row(
                    children: const [
                      Icon(Icons.logout_rounded, color: Color(0xFFDC2626), size: 22),
                      SizedBox(width: 8),
                      Text('Keluar Akun Mitra?', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    ],
                  ),
                  content: const Text(
                    'Apakah Anda yakin ingin keluar dari akun mitra resto ini?',
                    style: TextStyle(fontSize: 13, color: Color(0xFF475569)),
                  ),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(dialogCtx),
                      child: const Text('Batal', style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.bold)),
                    ),
                    ElevatedButton(
                      onPressed: () async {
                        Navigator.pop(dialogCtx);
                        await authCtrl.logout();
                        if (context.mounted) {
                          Navigator.pushAndRemoveUntil(
                            context,
                            MaterialPageRoute(builder: (_) => const SplashScreen()),
                            (route) => false,
                          );
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFDC2626),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        elevation: 0,
                      ),
                      child: const Text('Ya, Keluar', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
              );
            },
            style: OutlinedButton.styleFrom(
              foregroundColor: const Color(0xFFDC2626),
              side: const BorderSide(color: Color(0xFFFCA5A5)),
              padding: const EdgeInsets.symmetric(vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            ),
            icon: const Icon(Icons.logout_rounded, size: 18),
            label: const Text('Keluar Akun Mitra', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
          ),
        ],
      ),
    );
  }

  Widget _infoRow(IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, size: 15, color: const Color(0xFF64748B)),
        const SizedBox(width: 8),
        Expanded(
          child: Text(text, style: const TextStyle(fontSize: 12, color: Color(0xFF475569)), maxLines: 1, overflow: TextOverflow.ellipsis),
        ),
      ],
    );
  }
}

// ── MODAL: TARIK DANA SALDO VENDOR ──
class _MerchantWithdrawBottomSheet extends StatefulWidget {
  final double balance;
  const _MerchantWithdrawBottomSheet({required this.balance});

  @override
  State<_MerchantWithdrawBottomSheet> createState() => _MerchantWithdrawBottomSheetState();
}

class _MerchantWithdrawBottomSheetState extends State<_MerchantWithdrawBottomSheet> {
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _amountCtrl = TextEditingController();
  final TextEditingController _accNumberCtrl = TextEditingController();
  final TextEditingController _accHolderCtrl = TextEditingController();
  String _selectedBank = 'BCA';
  bool _isSubmitting = false;

  final List<String> _bankOptions = ['BCA', 'BRI', 'Mandiri', 'BNI', 'BSI', 'GoPay', 'OVO', 'DANA', 'ShopeePay'];

  @override
  void dispose() {
    _amountCtrl.dispose();
    _accNumberCtrl.dispose();
    _accHolderCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.85),
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Form(
        key: _formKey,
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
            const SizedBox(height: 14),
            const Text(
              'Tarik Saldo Pendapatan Resto',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 4),
            Text(
              'Saldo Aktif: ${CurrencyFormatter.formatRupiah(widget.balance)}',
              style: const TextStyle(fontSize: 12, color: Color(0xFF16A34A), fontWeight: FontWeight.bold),
            ),
            const Divider(height: 20),

            Expanded(
              child: ListView(
                children: [
                  const Text('Nominal Penarikan (Min. Rp 10.000) *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                  const SizedBox(height: 5),
                  TextFormField(
                    controller: _amountCtrl,
                    keyboardType: TextInputType.number,
                    validator: (v) {
                      final val = double.tryParse(v ?? '0') ?? 0;
                      if (val < 10000) return 'Minimal penarikan adalah Rp 10.000';
                      if (val > widget.balance) return 'Saldo tidak mencukupi';
                      return null;
                    },
                    decoration: _inputDecoration('Contoh: 100000'),
                  ),
                  const SizedBox(height: 12),

                  const Text('Tujuan Bank / E-Wallet *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                  const SizedBox(height: 5),
                  DropdownButtonFormField<String>(
                    value: _selectedBank,
                    items: _bankOptions.map((b) => DropdownMenuItem(value: b, child: Text(b))).toList(),
                    onChanged: (v) => setState(() => _selectedBank = v ?? 'BCA'),
                    decoration: _inputDecoration(''),
                  ),
                  const SizedBox(height: 12),

                  const Text('Nomor Rekening / No. E-Wallet *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                  const SizedBox(height: 5),
                  TextFormField(
                    controller: _accNumberCtrl,
                    keyboardType: TextInputType.number,
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Nomor rekening wajib diisi' : null,
                    decoration: _inputDecoration('Contoh: 1234567890'),
                  ),
                  const SizedBox(height: 12),

                  const Text('Nama Pemilik Rekening *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                  const SizedBox(height: 5),
                  TextFormField(
                    controller: _accHolderCtrl,
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Nama pemilik rekening wajib diisi' : null,
                    decoration: _inputDecoration('Sesuai buku tabungan / akun'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            UberPillButton(
              label: _isSubmitting ? 'Memproses...' : 'Ajukan Penarikan Dana',
              icon: Icons.check_circle_outline_rounded,
              onPressed: _isSubmitting
                  ? null
                  : () async {
                      if (!_formKey.currentState!.validate()) return;
                      setState(() => _isSubmitting = true);

                      final amount = double.tryParse(_amountCtrl.text.trim()) ?? 0;
                      final res = await context.read<MerchantController>().requestWithdraw(
                            amount: amount,
                            bankName: _selectedBank,
                            accountNumber: _accNumberCtrl.text.trim(),
                            accountHolder: _accHolderCtrl.text.trim(),
                          );

                      if (mounted) setState(() => _isSubmitting = false);

                      if (res['success'] == true && context.mounted) {
                        Navigator.pop(context);
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text(res['message']), backgroundColor: const Color(0xFF10B981)),
                        );
                      } else if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text(res['message'] ?? 'Gagal penarikan'), backgroundColor: AppTheme.primaryRed),
                        );
                      }
                    },
            ),
          ],
        ),
      ),
    );
  }

  InputDecoration _inputDecoration(String hint) {
    return InputDecoration(
      hintText: hint,
      hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
      filled: true,
      fillColor: const Color(0xFFF8FAFC),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppTheme.primaryRed)),
    );
  }
}

// ── MODAL: EDIT PROFIL RESTO & TITIK LOKASI PETA ──
class _EditStoreProfileBottomSheet extends StatefulWidget {
  final Map<String, dynamic> store;
  const _EditStoreProfileBottomSheet({required this.store});

  @override
  State<_EditStoreProfileBottomSheet> createState() => _EditStoreProfileBottomSheetState();
}

class _EditStoreProfileBottomSheetState extends State<_EditStoreProfileBottomSheet> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameCtrl;
  late TextEditingController _phoneCtrl;
  late TextEditingController _addressCtrl;
  late double _lat;
  late double _lng;
  late final MapController _mapCtrl;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _mapCtrl = MapController();
    _nameCtrl = TextEditingController(text: widget.store['name']?.toString() ?? '');
    _phoneCtrl = TextEditingController(text: widget.store['phone']?.toString() ?? widget.store['vendor_phone']?.toString() ?? '');
    _addressCtrl = TextEditingController(text: widget.store['address']?.toString() ?? '');

    _lat = double.tryParse(widget.store['latitude']?.toString() ?? '') ?? -6.9840;
    _lng = double.tryParse(widget.store['longitude']?.toString() ?? '') ?? 107.8340;
    if (_lat == 0 || _lng == 0) {
      _lat = -6.9840;
      _lng = 107.8340;
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _addressCtrl.dispose();
    _mapCtrl.dispose();
    super.dispose();
  }

  Future<void> _openMapPicker() async {
    final result = await LocationPickerModal.show(
      context,
      initialLat: _lat,
      initialLng: _lng,
    );

    if (result != null && mounted) {
      setState(() {
        _lat = (result['lat'] as num).toDouble();
        _lng = (result['lng'] as num).toDouble();
        final addr = result['address']?.toString() ?? '';
        if (addr.isNotEmpty && (_addressCtrl.text.isEmpty || _addressCtrl.text == 'Cicalengka, Kab. Bandung')) {
          _addressCtrl.text = addr;
        }
      });
      try {
        _mapCtrl.move(LatLng(_lat, _lng), 16.0);
      } catch (_) {}
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.90),
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Form(
        key: _formKey,
        child: ListView(
          shrinkWrap: true,
          children: [
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
                const Text(
                  'Edit Profil & Titik Lokasi Resto',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF64748B)),
                  onPressed: () => Navigator.pop(context),
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                ),
              ],
            ),
            const Divider(height: 20),

            const Text('Nama Resto / Toko *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
            const SizedBox(height: 5),
            TextFormField(
              controller: _nameCtrl,
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Nama resto wajib diisi' : null,
              decoration: _inputDecoration('Contoh: Warung Nasi Bu Imas'),
            ),
            const SizedBox(height: 12),

            const Text('No. WhatsApp Resto *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
            const SizedBox(height: 5),
            TextFormField(
              controller: _phoneCtrl,
              keyboardType: TextInputType.phone,
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Nomor HP/WA wajib diisi' : null,
              decoration: _inputDecoration('Contoh: 081234567890'),
            ),
            const SizedBox(height: 12),

            const Text('Alamat Lengkap Toko *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
            const SizedBox(height: 5),
            TextFormField(
              controller: _addressCtrl,
              maxLines: 2,
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Alamat toko wajib diisi' : null,
              decoration: _inputDecoration('Alamat penjemputan pesanan oleh driver...'),
            ),
            const SizedBox(height: 14),

            // ── TITIK LOKASI PETA & GESER PIN ──
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Titik Koordinat Toko di Peta (GPS) *',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155)),
                ),
                InkWell(
                  onTap: _openMapPicker,
                  child: Row(
                    children: const [
                      Icon(Icons.edit_location_alt_rounded, size: 14, color: Color(0xFF2563EB)),
                      SizedBox(width: 4),
                      Text(
                        'Geser Pin di Peta',
                        style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF2563EB)),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),

            // Preview Mini Map Card
            ClipRRect(
              borderRadius: BorderRadius.circular(14),
              child: Container(
                height: 145,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFCBD5E1)),
                ),
                child: Stack(
                  children: [
                    FlutterMap(
                      mapController: _mapCtrl,
                      options: MapOptions(
                        initialCenter: LatLng(_lat, _lng),
                        initialZoom: 15.5,
                        interactionOptions: const InteractionOptions(
                          flags: InteractiveFlag.pinchZoom | InteractiveFlag.drag,
                        ),
                        onPositionChanged: (pos, hasGesture) {
                          if (hasGesture && pos.center != null) {
                            setState(() {
                              _lat = pos.center!.latitude;
                              _lng = pos.center!.longitude;
                            });
                          }
                        },
                      ),
                      children: [
                        TileLayer(
                          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                          userAgentPackageName: 'com.cicalengkago.cicalengkago_mobile',
                        ),
                        MarkerLayer(
                          markers: [
                            Marker(
                              point: LatLng(_lat, _lng),
                              width: 40,
                              height: 40,
                              child: const Icon(
                                Icons.location_on_rounded,
                                color: Color(0xFFDC2626),
                                size: 38,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),

                    // Overlay Action Button to Open Full Interactive Map
                    Positioned(
                      bottom: 8,
                      right: 8,
                      child: ElevatedButton.icon(
                        onPressed: _openMapPicker,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF0F172A),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          elevation: 3,
                        ),
                        icon: const Icon(Icons.fullscreen_rounded, size: 16),
                        label: const Text('Buka Peta & Geser Pin', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 6),

            // Coordinate Info Chip
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFFF1F5F9),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.my_location_rounded, size: 14, color: Color(0xFF64748B)),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      'Koordinat: ${_lat.toStringAsFixed(6)}, ${_lng.toStringAsFixed(6)}',
                      style: const TextStyle(fontSize: 11, fontFamily: 'monospace', color: Color(0xFF334155), fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),

            UberPillButton(
              label: _isSaving ? 'Menyimpan...' : 'Simpan Profil & Titik Lokasi',
              icon: Icons.check_circle_outline_rounded,
              onPressed: _isSaving
                  ? null
                  : () async {
                      if (!_formKey.currentState!.validate()) return;
                      setState(() => _isSaving = true);

                      final authUser = context.read<AuthController>().user;
                      final merchantUser = context.read<MerchantController>().vendorUser;
                      final email = authUser?['email']?.toString() ?? merchantUser?['email']?.toString() ?? '';

                      final ok = await context.read<MerchantController>().updateStoreProfile({
                        'store_name': _nameCtrl.text.trim(),
                        'store_phone': _phoneCtrl.text.trim(),
                        'store_address': _addressCtrl.text.trim(),
                        'latitude': _lat.toString(),
                        'longitude': _lng.toString(),
                        'name': _nameCtrl.text.trim(),
                        'phone': _phoneCtrl.text.trim(),
                        'email': email,
                      });

                      if (mounted) setState(() => _isSaving = false);

                      if (ok && context.mounted) {
                        Navigator.pop(context);
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Profil dan titik lokasi resto berhasil diperbarui!'), backgroundColor: Color(0xFF10B981)),
                        );
                      } else if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Gagal menyimpan profil resto. Silakan coba lagi.'), backgroundColor: AppTheme.primaryRed),
                        );
                      }
                    },
            ),
          ],
        ),
      ),
    );
  }

  InputDecoration _inputDecoration(String hint) {
    return InputDecoration(
      hintText: hint,
      hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
      filled: true,
      fillColor: const Color(0xFFF8FAFC),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppTheme.primaryRed)),
    );
  }
}
