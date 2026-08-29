import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/cicalengkago_logo.dart';
import '../../../core/widgets/app_alert.dart';
import '../../../core/widgets/require_auth_widget.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/customer_controller.dart';
import 'in_app_payment_screen.dart';
import 'payment_invoice_screen.dart';

class CustomerWalletScreen extends StatefulWidget {
  const CustomerWalletScreen({super.key});

  @override
  State<CustomerWalletScreen> createState() => _CustomerWalletScreenState();
}

class _CustomerWalletScreenState extends State<CustomerWalletScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CustomerController>().fetchWallet();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<CustomerController>();
    final walletData = ctrl.wallet;
    final balance = double.tryParse(
            walletData?['wallet']?['balance']?.toString() ?? '0') ??
        0.0;
    final transactions =
        (walletData?['transactions'] as List<dynamic>?) ?? [];
    final topupLogs =
        (walletData?['topup_logs'] as List<dynamic>?) ?? [];

    return RequireAuthWidget(
      title: 'Dompet CicalengkaPay',
      subtitle: 'Masuk ke akun Anda untuk mengecek saldo, riwayat transaksi, dan melakukan isi ulang saldo (top-up).',
      icon: Icons.account_balance_wallet_rounded,
      child: Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: CustomScrollView(
        slivers: [
          // App Bar
          SliverAppBar(
            pinned: true,
            backgroundColor: Colors.white,
            foregroundColor: const Color(0xFF0F172A),
            title: const Text('Dompet CicalengkaPay',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            actions: [
              IconButton(
                icon: const Icon(Icons.refresh_rounded, color: AppTheme.primaryRed),
                onPressed: () => ctrl.fetchWallet(),
              ),
            ],
          ),

          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  // Balance Card
                  _buildBalanceCard(balance, context),
                  const SizedBox(height: 16),

                  // Quick TopUp Grid
                  _buildQuickTopUpGrid(context),
                  const SizedBox(height: 16),

                  // Tabs
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: TabBar(
                      controller: _tabController,
                      labelColor: AppTheme.primaryRed,
                      unselectedLabelColor: const Color(0xFF64748B),
                      indicator: BoxDecoration(
                        color: const Color(0xFFFEE2E2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      dividerColor: Colors.transparent,
                      padding: const EdgeInsets.all(4),
                      labelStyle: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold),
                      tabs: [
                        Tab(
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.receipt_rounded, size: 14),
                              const SizedBox(width: 5),
                              Text('Mutasi (${transactions.length})'),
                            ],
                          ),
                        ),
                        Tab(
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.history_rounded, size: 14),
                              const SizedBox(width: 5),
                              Text('Top Up (${topupLogs.length})'),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Tab content area (fixed height within scroll)
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: SizedBox(
                height: transactions.isEmpty && topupLogs.isEmpty ? 200 : 600,
                child: TabBarView(
                  controller: _tabController,
                  children: [
                    _buildMutasiTab(transactions),
                    _buildTopUpTab(topupLogs),
                  ],
                ),
              ),
            ),
          ),

          const SliverPadding(padding: EdgeInsets.only(bottom: 40)),
        ],
      ),
    ),
  );
}

  Widget _buildBalanceCard(double balance, BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF262626), Color(0xFF000000)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.3),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Text('Cicalengka',
                          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 15)),
                      const Text('Pay',
                          style: TextStyle(color: Color(0xFFFFE4E6), fontWeight: FontWeight.w900, fontSize: 15)),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: const Text('E-WALLET',
                            style: TextStyle(color: AppTheme.primaryRed, fontSize: 8, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  const Text('SALDO UTAMA AKTIF',
                      style: TextStyle(color: Colors.white54, fontSize: 9, fontWeight: FontWeight.w700, letterSpacing: 0.5)),
                  const SizedBox(height: 2),
                  Text(
                    CurrencyFormatter.formatRupiah(balance),
                    style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w900),
                  ),
                ],
              ),
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.2),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.shield_rounded, color: Colors.white, size: 20),
              ),
            ],
          ),
          const SizedBox(height: 16),
          const Divider(color: Colors.white24, height: 1),
          const SizedBox(height: 14),
          // Action buttons row: ONLY Top Up and Kirim Uang
          Row(
            children: [
              Expanded(
                child: Material(
                  color: Colors.transparent,
                  child: InkWell(
                    onTap: () => _showTopUpSheet(context),
                    borderRadius: BorderRadius.circular(14),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.18),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: Colors.white.withOpacity(0.25)),
                      ),
                      child: const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.add_circle_rounded, color: Colors.white, size: 20),
                          SizedBox(width: 8),
                          Text(
                            'Top Up',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 0.3,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Material(
                  color: Colors.transparent,
                  child: InkWell(
                    onTap: () => _showSendMoneySheet(context),
                    borderRadius: BorderRadius.circular(14),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                      decoration: BoxDecoration(
                        color: AppTheme.primaryRed.withOpacity(0.85),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: Colors.white.withOpacity(0.3)),
                      ),
                      child: const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.send_rounded, color: Colors.white, size: 18),
                          SizedBox(width: 8),
                          Text(
                            'Kirim Uang',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 0.3,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildQuickTopUpGrid(BuildContext context) {
    final nominals = [
      {'amount': 20000, 'label': 'Rp 20.000', 'tag': 'Hemat', 'tagColor': const Color(0xFF16A34A)},
      {'amount': 50000, 'label': 'Rp 50.000', 'tag': 'Populer', 'tagColor': const Color(0xFF2563EB)},
      {'amount': 100000, 'label': 'Rp 100.000', 'tag': 'Favorit', 'tagColor': const Color(0xFFD97706)},
      {'amount': 200000, 'label': 'Rp 200.000', 'tag': 'Sultan', 'tagColor': const Color(0xFF7C3AED)},
    ];

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
          Row(
            children: [
              Container(
                width: 32,
                height: 32,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(colors: [Color(0xFFF59E0B), Color(0xFFD97706)]),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.bolt_rounded, color: Colors.white, size: 18),
              ),
              const SizedBox(width: 10),
              const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Isi Saldo Instan', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                  Text('Bebas biaya admin • Langsung masuk', style: TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                ],
              ),
            ],
          ),
          const SizedBox(height: 14),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              mainAxisSpacing: 8,
              crossAxisSpacing: 8,
              childAspectRatio: 2.6,
            ),
            itemCount: nominals.length,
            itemBuilder: (_, i) {
              final item = nominals[i];
              final color = item['tagColor'] as Color;
              return GestureDetector(
                onTap: () => _initiateTopUp(context, item['amount'] as int),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: color.withOpacity(0.3)),
                    boxShadow: [BoxShadow(color: color.withOpacity(0.05), blurRadius: 6)],
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        item['label'] as String,
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: color.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          item['tag'] as String,
                          style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: color),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
          const SizedBox(height: 10),
          GestureDetector(
            onTap: () => _showTopUpSheet(context),
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 10),
              decoration: BoxDecoration(
                color: const Color(0xFFFFF5F5),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFFECDD3), style: BorderStyle.values[1]),
              ),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.edit_rounded, color: AppTheme.primaryRed, size: 14),
                  SizedBox(width: 6),
                  Text('Masukkan Nominal Lainnya', style: TextStyle(color: AppTheme.primaryRed, fontSize: 12, fontWeight: FontWeight.bold)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showTopUpSheet(BuildContext context) {
    final amountCtrl = TextEditingController();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
        child: Container(
          padding: const EdgeInsets.all(20),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Isi Saldo CicalengkaPay', style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
              const SizedBox(height: 4),
              const Text('Minimal Rp 10.000', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
              const SizedBox(height: 16),
              TextField(
                controller: amountCtrl,
                keyboardType: TextInputType.number,
                autofocus: true,
                style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                decoration: InputDecoration(
                  prefixText: 'Rp ',
                  prefixStyle: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: AppTheme.primaryRed),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppTheme.primaryRed, width: 2)),
                  focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppTheme.primaryRed, width: 2)),
                ),
              ),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.inkBlack,
                    foregroundColor: AppTheme.onPrimary,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: const StadiumBorder(),
                    elevation: 0,
                  ),
                  onPressed: () {
                    final amount = int.tryParse(amountCtrl.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;
                    if (amount < 10000) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Minimal top up Rp 10.000')),
                      );
                      return;
                    }
                    Navigator.pop(ctx);
                    _initiateTopUp(context, amount);
                  },
                  child: const Text('Lanjut bayar', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _initiateTopUp(BuildContext context, int amount) async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
        child: CircularProgressIndicator(color: AppTheme.primaryRed),
      ),
    );

    try {
      final res = await ApiService.post(ApiConstants.paymentTopupSnap, {
        'amount': amount,
      });

      if (context.mounted) {
        Navigator.pop(context); // Dismiss loading dialog
      }

      if (res['success'] == true && res['data'] != null) {
        final redirectUrl = res['data']['redirect_url']?.toString() ??
            'https://app.sandbox.midtrans.com/snap/v2/vtweb/${res['data']['snap_token']}';
        final orderId = res['data']['order_id']?.toString() ?? '';

        if (context.mounted) {
          final completed = await Navigator.push<bool>(
            context,
            MaterialPageRoute(
              builder: (_) => InAppPaymentScreen(
                paymentUrl: redirectUrl,
                orderId: orderId,
                amount: amount.toDouble(),
                title: 'Top Up CicalengkaPay',
                onPaymentComplete: () {
                  context.read<CustomerController>().fetchWallet();
                },
              ),
            ),
          );

          if (completed == true && context.mounted) {
            context.read<CustomerController>().fetchWallet();
          }
        }
        return;
      }

      if (context.mounted) {
        AppAlert.showError(
          context,
          title: 'Gagal Membuat Tiket',
          message: res['message'] ?? 'Gagal membuat tiket pembayaran Top-Up via Midtrans.',
        );
      }
    } catch (e) {
      if (context.mounted) {
        Navigator.pop(context);
        AppAlert.showError(context, title: 'Error', message: e.toString());
      }
    }
  }

  void _showSendMoneySheet(BuildContext context) {
    final phoneCtrl = TextEditingController();
    final amountCtrl = TextEditingController();
    final noteCtrl = TextEditingController();
    int selectedAmount = 0;
    bool isSubmitting = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (modalCtx, setModalState) {
            return Container(
              padding: EdgeInsets.only(
                left: 20,
                right: 20,
                top: 20,
                bottom: MediaQuery.of(modalCtx).viewInsets.bottom + 24,
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
                    // Header
                    Center(
                      child: Container(
                        width: 40,
                        height: 4,
                        decoration: BoxDecoration(
                          color: const Color(0xFFE2E8F0),
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: AppTheme.primaryRed.withOpacity(0.1),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.send_rounded, color: AppTheme.primaryRed, size: 20),
                        ),
                        const SizedBox(width: 10),
                        const Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Kirim Uang / Transfer',
                              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                            ),
                            Text(
                              'Transfer instan ke sesama pengguna CicalengkaPay',
                              style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),

                    // Input Nomor HP Penerima
                    const Text('Nomor WhatsApp / HP Penerima *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                    const SizedBox(height: 6),
                    TextField(
                      controller: phoneCtrl,
                      keyboardType: TextInputType.phone,
                      decoration: InputDecoration(
                        hintText: 'Contoh: 081234567890',
                        hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                        prefixIcon: const Icon(Icons.phone_android_rounded, color: Color(0xFF64748B), size: 20),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppTheme.primaryRed)),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Input Nominal Transfer
                    const Text('Nominal Kirim (Rp) *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                    const SizedBox(height: 6),
                    TextField(
                      controller: amountCtrl,
                      keyboardType: TextInputType.number,
                      onChanged: (val) {
                        setModalState(() {
                          selectedAmount = int.tryParse(val.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;
                        });
                      },
                      decoration: InputDecoration(
                        hintText: 'Min. Rp 1.000',
                        hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                        prefixIcon: const Icon(Icons.payments_rounded, color: Color(0xFF16A34A), size: 20),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppTheme.primaryRed)),
                      ),
                    ),
                    const SizedBox(height: 10),

                    // Quick Nominal Chips
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [10000, 25000, 50000, 100000, 200000].map((nom) {
                        final isSelected = selectedAmount == nom;
                        return InkWell(
                          onTap: () {
                            setModalState(() {
                              selectedAmount = nom;
                              amountCtrl.text = nom.toString();
                            });
                          },
                          borderRadius: BorderRadius.circular(20),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            decoration: BoxDecoration(
                              color: isSelected ? AppTheme.primaryRed : const Color(0xFFF1F5F9),
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(
                                color: isSelected ? AppTheme.primaryRed : const Color(0xFFE2E8F0),
                              ),
                            ),
                            child: Text(
                              CurrencyFormatter.formatRupiah(nom.toDouble()),
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: isSelected ? Colors.white : const Color(0xFF334155),
                              ),
                            ),
                          ),
                        );
                      }).toList(),
                    ),
                    const SizedBox(height: 16),

                    // Catatan (Opsional)
                    const Text('Catatan / Pesan (Opsional)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                    const SizedBox(height: 6),
                    TextField(
                      controller: noteCtrl,
                      decoration: InputDecoration(
                        hintText: 'Tulis pesan singkat...',
                        hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                        prefixIcon: const Icon(Icons.note_alt_rounded, color: Color(0xFF64748B), size: 20),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppTheme.primaryRed)),
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Submit Button
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        onPressed: isSubmitting
                            ? null
                            : () async {
                                final phone = phoneCtrl.text.trim();
                                final rawAmt = int.tryParse(amountCtrl.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? selectedAmount;
                                final note = noteCtrl.text.trim();

                                if (phone.isEmpty) {
                                  AppAlert.showWarning(context, title: 'Nomor HP Kosong', message: 'Masukkan nomor HP/WhatsApp penerima transfer.');
                                  return;
                                }

                                if (rawAmt < 1000) {
                                  AppAlert.showWarning(context, title: 'Nominal Kurang', message: 'Nominal transfer minimal Rp 1.000.');
                                  return;
                                }

                                setModalState(() => isSubmitting = true);

                                try {
                                  final res = await ApiService.postForm(ApiConstants.paymentTransfer, {
                                    'recipient_phone': phone,
                                    'amount': rawAmt.toString(),
                                    'notes': note,
                                  });

                                  setModalState(() => isSubmitting = false);

                                  if (modalCtx.mounted) {
                                    Navigator.pop(modalCtx);
                                  }

                                  if (context.mounted) {
                                    if (res['success'] == true) {
                                      AppAlert.showSuccess(
                                        context,
                                        title: 'Transfer Berhasil! 🎉',
                                        message: res['message'] ?? 'Saldo berhasil dikirimkan.',
                                      );
                                      context.read<CustomerController>().fetchWallet();
                                    } else {
                                      AppAlert.showError(
                                        context,
                                        title: 'Transfer Gagal',
                                        message: res['message'] ?? 'Gagal mengirim saldo. Periksa nomor tujuan dan saldo Anda.',
                                      );
                                    }
                                  }
                                } catch (err) {
                                  setModalState(() => isSubmitting = false);
                                  if (modalCtx.mounted) {
                                    Navigator.pop(modalCtx);
                                  }
                                  if (context.mounted) {
                                    AppAlert.showError(context, title: 'Error Transfer', message: err.toString());
                                  }
                                }
                              },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppTheme.primaryRed,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        child: isSubmitting
                            ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                            : const Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.send_rounded, size: 18),
                                  SizedBox(width: 8),
                                  Text('Kirim Uang Sekarang', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                                ],
                              ),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildMutasiTab(List<dynamic> transactions) {
    if (transactions.isEmpty) {
      return _emptyState(Icons.receipt_outlined, 'Belum Ada Mutasi', 'Transaksi saldo akan tercatat di sini.');
    }
    return ListView.separated(
      padding: const EdgeInsets.only(top: 12, bottom: 16),
      itemCount: transactions.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (_, i) => _buildTransactionCard(transactions[i]),
    );
  }

  Widget _buildTopUpTab(List<dynamic> logs) {
    if (logs.isEmpty) {
      return _emptyState(Icons.wallet_outlined, 'Belum Ada Tiket Top Up', 'Pilih nominal di atas untuk isi saldo.');
    }
    return ListView.separated(
      padding: const EdgeInsets.only(top: 12, bottom: 16),
      itemCount: logs.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (_, i) => _buildTopUpCard(logs[i]),
    );
  }

  Widget _buildTransactionCard(Map<String, dynamic> tx) {
    final category = tx['category'] ?? tx['type'] ?? 'credit';
    final isCredit = tx['type'] == 'credit';
    final amount = double.tryParse(tx['amount']?.toString() ?? '0') ?? 0;

    Color color;
    IconData icon;
    String title;

    if (category == 'topup') {
      color = const Color(0xFF10B981);
      icon = Icons.add_circle_rounded;
      title = 'Top Up CicalengkaPay';
    } else if (category == 'order_payment') {
      color = AppTheme.primaryRed;
      icon = Icons.shopping_bag_rounded;
      title = 'Pembayaran Pesanan';
    } else if (category == 'refund' || category == 'order_refund') {
      color = const Color(0xFF2563EB);
      icon = Icons.replay_rounded;
      title = 'Refund Pengembalian Dana';
    } else {
      color = isCredit ? const Color(0xFF10B981) : AppTheme.primaryRed;
      icon = isCredit ? Icons.arrow_downward_rounded : Icons.arrow_upward_rounded;
      title = isCredit ? 'Saldo Masuk' : 'Saldo Keluar';
    }

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFF1F5F9)),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                Text(tx['description'] ?? '', style: const TextStyle(fontSize: 10, color: Color(0xFF64748B)), overflow: TextOverflow.ellipsis),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '${isCredit ? '+' : '-'}${CurrencyFormatter.formatRupiah(amount)}',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: color),
              ),
              Container(
                margin: const EdgeInsets.only(top: 3),
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  isCredit ? 'Masuk' : 'Keluar',
                  style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: color),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTopUpCard(Map<String, dynamic> log) {
    final status = log['status'] ?? 'pending';
    final amount = double.tryParse(log['amount']?.toString() ?? '0') ?? 0;

    Color color;
    String label;
    IconData icon;

    if (status == 'success') {
      color = const Color(0xFF059669);
      label = 'Berhasil';
      icon = Icons.check_circle_rounded;
    } else if (status == 'failed' || status == 'canceled') {
      color = AppTheme.primaryRed;
      label = status == 'canceled' ? 'Dibatalkan' : 'Gagal';
      icon = Icons.cancel_rounded;
    } else {
      color = const Color(0xFFD97706);
      label = 'Menunggu';
      icon = Icons.hourglass_empty_rounded;
    }

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.2)),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(color: color.withOpacity(0.1), shape: BoxShape.circle),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Top Up CicalengkaPay', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                Text(log['topup_code'] ?? '', style: const TextStyle(fontSize: 10, color: Color(0xFF64748B), fontFamily: 'monospace')),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '${status == 'success' ? '+' : ''}${CurrencyFormatter.formatRupiah(amount)}',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: color),
              ),
              Container(
                margin: const EdgeInsets.only(top: 3),
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(6)),
                child: Text(label, style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: color)),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _emptyState(IconData icon, String title, String subtitle) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(30),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const CicalengkaGoLogo(size: 60, borderRadius: 18),
            const SizedBox(height: 12),
            Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
            const SizedBox(height: 4),
            Text(subtitle, style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}
