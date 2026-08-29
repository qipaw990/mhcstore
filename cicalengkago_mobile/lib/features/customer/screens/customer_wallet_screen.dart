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
    String transferType = 'bank'; // 'bank', 'ewallet', 'cicalengkapay'
    String selectedBank = 'BCA';
    String selectedEwallet = 'DANA';

    final accountNumberCtrl = TextEditingController();
    final accountHolderCtrl = TextEditingController();
    final phoneCtrl = TextEditingController();
    final amountCtrl = TextEditingController();
    final noteCtrl = TextEditingController();

    int selectedAmount = 0;
    bool isSubmitting = false;

    final bankList = ['BCA', 'BRI', 'Mandiri', 'BNI', 'BSI', 'CIMB Niaga', 'Bank Jago', 'SeaBank', 'Permata'];
    final ewalletList = ['DANA', 'GoPay', 'OVO', 'ShopeePay', 'LinkAja', 'AstraPay'];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (modalCtx, setModalState) {
            final int rawAmount = int.tryParse(amountCtrl.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? selectedAmount;
            final double fee = (transferType == 'cicalengkapay') ? 0.0 : 1500.0;
            final double totalDeducted = rawAmount > 0 ? (rawAmount + fee) : 0.0;

            return Container(
              constraints: BoxConstraints(maxHeight: MediaQuery.of(modalCtx).size.height * 0.88),
              padding: EdgeInsets.only(
                left: 20,
                right: 20,
                top: 16,
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
                    // Handle bar
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
                    const SizedBox(height: 14),

                    // Header
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
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Kirim Uang / Transfer Saldo',
                                style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                              ),
                              Text(
                                'Tujuan Rekening Bank, E-Wallet, atau Sesama Akun',
                                style: TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    // Transfer Type Segment Tabs
                    Container(
                      padding: const EdgeInsets.all(4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          _buildTypeTab('bank', 'Rek. Bank', Icons.account_balance_rounded, transferType, (val) {
                            setModalState(() => transferType = val);
                          }),
                          _buildTypeTab('ewallet', 'E-Wallet', Icons.account_balance_wallet_rounded, transferType, (val) {
                            setModalState(() => transferType = val);
                          }),
                          _buildTypeTab('cicalengkapay', 'Sesama Pay', Icons.swap_horiz_rounded, transferType, (val) {
                            setModalState(() => transferType = val);
                          }),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    // 1. BANK TRANSFER FORM
                    if (transferType == 'bank') ...[
                      const Text('Pilih Bank Tujuan *', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                      const SizedBox(height: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: selectedBank,
                            isExpanded: true,
                            items: bankList.map((b) => DropdownMenuItem(value: b, child: Text(b, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)))).toList(),
                            onChanged: (val) {
                              if (val != null) setModalState(() => selectedBank = val);
                            },
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),

                      const Text('Nomor Rekening Bank *', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                      const SizedBox(height: 6),
                      TextField(
                        controller: accountNumberCtrl,
                        keyboardType: TextInputType.number,
                        decoration: InputDecoration(
                          hintText: 'Masukkan no. rekening tujuan',
                          hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                          prefixIcon: const Icon(Icons.credit_card_rounded, color: Color(0xFF64748B), size: 20),
                          filled: true,
                          fillColor: const Color(0xFFF8FAFC),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppTheme.primaryRed)),
                        ),
                      ),
                      const SizedBox(height: 12),

                      const Text('Nama Pemilik Rekening *', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                      const SizedBox(height: 6),
                      TextField(
                        controller: accountHolderCtrl,
                        textCapitalization: TextCapitalization.words,
                        decoration: InputDecoration(
                          hintText: 'Contoh: Budi Santoso',
                          hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                          prefixIcon: const Icon(Icons.person_outline_rounded, color: Color(0xFF64748B), size: 20),
                          filled: true,
                          fillColor: const Color(0xFFF8FAFC),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppTheme.primaryRed)),
                        ),
                      ),
                    ],

                    // 2. E-WALLET TRANSFER FORM
                    if (transferType == 'ewallet') ...[
                      const Text('Pilih E-Wallet Tujuan *', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                      const SizedBox(height: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: selectedEwallet,
                            isExpanded: true,
                            items: ewalletList.map((ew) => DropdownMenuItem(value: ew, child: Text(ew, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)))).toList(),
                            onChanged: (val) {
                              if (val != null) setModalState(() => selectedEwallet = val);
                            },
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),

                      const Text('Nomor HP Akun E-Wallet *', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                      const SizedBox(height: 6),
                      TextField(
                        controller: accountNumberCtrl,
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
                      const SizedBox(height: 12),

                      const Text('Nama Penerima / Akun E-Wallet *', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                      const SizedBox(height: 6),
                      TextField(
                        controller: accountHolderCtrl,
                        textCapitalization: TextCapitalization.words,
                        decoration: InputDecoration(
                          hintText: 'Nama terdaftar di akun e-wallet',
                          hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                          prefixIcon: const Icon(Icons.person_outline_rounded, color: Color(0xFF64748B), size: 20),
                          filled: true,
                          fillColor: const Color(0xFFF8FAFC),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppTheme.primaryRed)),
                        ),
                      ),
                    ],

                    // 3. CICALENGKAPAY PEER TRANSFER FORM
                    if (transferType == 'cicalengkapay') ...[
                      const Text('Nomor WhatsApp / HP Pengguna *', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
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
                    ],

                    const SizedBox(height: 14),

                    // Input Nominal Transfer
                    const Text('Nominal Kirim (Rp) *', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
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
                        hintText: transferType == 'cicalengkapay' ? 'Min. Rp 1.000' : 'Min. Rp 10.000',
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
                    const SizedBox(height: 8),

                    // Quick Nominal Chips
                    Wrap(
                      spacing: 6,
                      runSpacing: 6,
                      children: [20000, 50000, 100000, 200000, 500000].map((nom) {
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
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
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
                                fontSize: 10.5,
                                fontWeight: FontWeight.bold,
                                color: isSelected ? Colors.white : const Color(0xFF334155),
                              ),
                            ),
                          ),
                        );
                      }).toList(),
                    ),
                    const SizedBox(height: 12),

                    // Catatan (Opsional)
                    const Text('Catatan / Berita Transfer (Opsional)', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                    const SizedBox(height: 6),
                    TextField(
                      controller: noteCtrl,
                      decoration: InputDecoration(
                        hintText: 'Tulis pesan singkat...',
                        hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                        prefixIcon: const Icon(Icons.note_alt_rounded, color: Color(0xFF64748B), size: 20),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppTheme.primaryRed)),
                      ),
                    ),
                    const SizedBox(height: 14),

                    // Fee & Breakdown Summary Card
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Nominal Kirim', style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B))),
                              Text(CurrencyFormatter.formatRupiah(rawAmount.toDouble()), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Biaya Admin', style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B))),
                              Text(
                                fee > 0 ? CurrencyFormatter.formatRupiah(fee) : 'Gratis (Rp 0)',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  color: fee > 0 ? AppTheme.primaryRed : const Color(0xFF16A34A),
                                ),
                              ),
                            ],
                          ),
                          const Divider(height: 12, color: Color(0xFFE2E8F0)),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Total Saldo Terpotong', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                              Text(
                                CurrencyFormatter.formatRupiah(totalDeducted),
                                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: AppTheme.primaryRed),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Submit Button
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        onPressed: isSubmitting
                            ? null
                            : () async {
                                final accNum = accountNumberCtrl.text.trim();
                                final accHolder = accountHolderCtrl.text.trim();
                                final phone = phoneCtrl.text.trim();
                                final note = noteCtrl.text.trim();

                                if (transferType == 'bank') {
                                  if (accNum.isEmpty || accHolder.isEmpty) {
                                    AppAlert.showWarning(context, title: 'Data Belum Lengkap', message: 'Nomor rekening dan nama pemilik rekening wajib diisi.');
                                    return;
                                  }
                                  if (rawAmount < 10000) {
                                    AppAlert.showWarning(context, title: 'Nominal Kurang', message: 'Nominal transfer bank minimal Rp 10.000.');
                                    return;
                                  }
                                } else if (transferType == 'ewallet') {
                                  if (accNum.isEmpty || accHolder.isEmpty) {
                                    AppAlert.showWarning(context, title: 'Data Belum Lengkap', message: 'Nomor HP e-wallet dan nama akun wajib diisi.');
                                    return;
                                  }
                                  if (rawAmount < 10000) {
                                    AppAlert.showWarning(context, title: 'Nominal Kurang', message: 'Nominal transfer e-wallet minimal Rp 10.000.');
                                    return;
                                  }
                                } else {
                                  if (phone.isEmpty) {
                                    AppAlert.showWarning(context, title: 'Nomor HP Kosong', message: 'Masukkan nomor HP penerima transfer.');
                                    return;
                                  }
                                  if (rawAmount < 1000) {
                                    AppAlert.showWarning(context, title: 'Nominal Kurang', message: 'Nominal transfer minimal Rp 1.000.');
                                    return;
                                  }
                                }

                                setModalState(() => isSubmitting = true);

                                try {
                                  final body = {
                                    'transfer_type': transferType,
                                    'bank_name': selectedBank,
                                    'ewallet_name': selectedEwallet,
                                    'account_number': accNum,
                                    'account_holder': accHolder,
                                    'recipient_phone': phone,
                                    'amount': rawAmount.toString(),
                                    'notes': note,
                                  };

                                  final res = await ApiService.postForm(ApiConstants.paymentTransfer, body);
                                  setModalState(() => isSubmitting = false);

                                  if (modalCtx.mounted) {
                                    Navigator.pop(modalCtx);
                                  }

                                  if (context.mounted) {
                                    if (res['success'] == true) {
                                      AppAlert.showSuccess(
                                        context,
                                        title: 'Kirim Uang Berhasil! 🎉',
                                        message: res['message'] ?? 'Permintaan transfer berhasil diproses.',
                                      );
                                      context.read<CustomerController>().fetchWallet();
                                    } else {
                                      AppAlert.showError(
                                        context,
                                        title: 'Transfer Gagal',
                                        message: res['message'] ?? 'Gagal memproses kirim uang. Periksa saldo dan data rekening Anda.',
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
                            : Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.send_rounded, size: 18),
                                  const SizedBox(width: 8),
                                  Text(
                                    transferType == 'bank'
                                        ? 'Transfer ke Rekening Bank'
                                        : (transferType == 'ewallet' ? 'Transfer ke E-Wallet' : 'Kirim ke CicalengkaPay'),
                                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
                                  ),
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

  Widget _buildTypeTab(String key, String label, IconData icon, String activeKey, ValueChanged<String> onSelect) {
    final isActive = key == activeKey;
    return Expanded(
      child: GestureDetector(
        onTap: () => onSelect(key),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: isActive ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
            boxShadow: isActive ? [BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 4, offset: const Offset(0, 1))] : null,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 14, color: isActive ? AppTheme.primaryRed : const Color(0xFF64748B)),
              const SizedBox(width: 5),
              Text(
                label,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: isActive ? AppTheme.primaryRed : const Color(0xFF64748B),
                ),
              ),
            ],
          ),
        ),
      ),
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
    final category = (tx['category'] ?? tx['type'] ?? 'credit').toString().toLowerCase();
    final isCredit = tx['type'] == 'credit';
    final amount = double.tryParse(tx['amount']?.toString() ?? '0') ?? 0;
    final withdrawStatus = tx['withdraw_status']?.toString().toLowerCase();
    final description = (tx['description'] ?? '').toString();

    Color color;
    IconData icon;
    String title;
    String badgeText;
    Color badgeBg;
    Color badgeTextColor;

    if (withdrawStatus == 'pending' || (category == 'withdraw' && withdrawStatus == null)) {
      color = const Color(0xFFD97706);
      icon = Icons.hourglass_top_rounded;
      title = description.isNotEmpty ? description : 'Kirim Uang ke Rekening';
      badgeText = 'Menunggu Persetujuan Admin';
      badgeBg = const Color(0xFFFEF3C7);
      badgeTextColor = const Color(0xFFB45309);
    } else if (withdrawStatus == 'approved') {
      color = const Color(0xFF059669);
      icon = Icons.check_circle_rounded;
      title = description.isNotEmpty ? description : 'Transfer Berhasil';
      badgeText = 'Berhasil Ditransfer';
      badgeBg = const Color(0xFFDCFCE7);
      badgeTextColor = const Color(0xFF15803D);
    } else if (withdrawStatus == 'rejected') {
      color = AppTheme.primaryRed;
      icon = Icons.cancel_rounded;
      title = description.isNotEmpty ? description : 'Transfer Ditolak';
      badgeText = 'Ditolak (Saldo Dikembalikan)';
      badgeBg = const Color(0xFFFFE4E6);
      badgeTextColor = AppTheme.primaryRed;
    } else if (category == 'fee') {
      color = const Color(0xFF64748B);
      icon = Icons.receipt_rounded;
      title = 'Biaya Admin Layanan Transfer';
      badgeText = 'Biaya Admin';
      badgeBg = const Color(0xFFF1F5F9);
      badgeTextColor = const Color(0xFF475569);
    } else if (category == 'topup') {
      color = const Color(0xFF10B981);
      icon = Icons.add_circle_rounded;
      title = 'Top Up CicalengkaPay';
      badgeText = 'Saldo Masuk';
      badgeBg = const Color(0xFFDCFCE7);
      badgeTextColor = const Color(0xFF15803D);
    } else if (category == 'order_payment') {
      color = AppTheme.primaryRed;
      icon = Icons.shopping_bag_rounded;
      title = 'Pembayaran Pesanan';
      badgeText = 'Belanja';
      badgeBg = const Color(0xFFFFE4E6);
      badgeTextColor = AppTheme.primaryRed;
    } else if (category == 'refund' || category == 'order_refund') {
      color = const Color(0xFF2563EB);
      icon = Icons.replay_rounded;
      title = 'Refund Pengembalian Dana';
      badgeText = 'Dana Kembali';
      badgeBg = const Color(0xFFDBEAFE);
      badgeTextColor = const Color(0xFF1D4ED8);
    } else {
      color = isCredit ? const Color(0xFF10B981) : AppTheme.primaryRed;
      icon = isCredit ? Icons.arrow_downward_rounded : Icons.arrow_upward_rounded;
      title = description.isNotEmpty ? description : (isCredit ? 'Saldo Masuk' : 'Saldo Keluar');
      badgeText = isCredit ? 'Masuk' : 'Keluar';
      badgeBg = color.withOpacity(0.1);
      badgeTextColor = color;
    }

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: withdrawStatus == 'pending' ? const Color(0xFFFDE68A) : const Color(0xFFF1F5F9),
        ),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 6, offset: const Offset(0, 2)),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () => _showTransactionDetail(context, tx),
          borderRadius: BorderRadius.circular(14),
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.12),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(icon, color: color, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        withdrawStatus == 'pending'
                            ? 'Pengajuan sedang ditinjau Admin'
                            : (tx['created_at'] != null ? tx['created_at'].toString() : (tx['description'] ?? '')),
                        style: TextStyle(
                          fontSize: 10,
                          color: withdrawStatus == 'pending' ? const Color(0xFFD97706) : const Color(0xFF64748B),
                          fontWeight: withdrawStatus == 'pending' ? FontWeight.w600 : FontWeight.normal,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      '${isCredit ? '+' : '-'}${CurrencyFormatter.formatRupiah(amount)}',
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: color),
                    ),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2.5),
                      decoration: BoxDecoration(
                        color: badgeBg,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        badgeText,
                        style: TextStyle(fontSize: 8.5, fontWeight: FontWeight.bold, color: badgeTextColor),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
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
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withOpacity(0.2)),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 6, offset: const Offset(0, 2)),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () => _showTopUpDetail(context, log),
          borderRadius: BorderRadius.circular(14),
          child: Padding(
            padding: const EdgeInsets.all(12),
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
          ),
        ),
      ),
    );
  }

  void _showTransactionDetail(BuildContext context, Map<String, dynamic> tx) {
    final isCredit = tx['type'] == 'credit';
    final amount = double.tryParse(tx['amount']?.toString() ?? '0') ?? 0;
    final category = (tx['category'] ?? tx['type'] ?? 'credit').toString();
    final withdrawStatus = tx['withdraw_status']?.toString().toLowerCase();
    final refId = tx['reference_id']?.toString() ?? '-';
    final description = tx['description']?.toString() ?? '-';
    final createdAt = tx['created_at']?.toString() ?? '-';
    final bankName = tx['withdraw_bank']?.toString();
    final accNum = tx['withdraw_acc_num']?.toString();
    final accHolder = tx['withdraw_acc_holder']?.toString();
    final adminNotes = tx['withdraw_admin_notes']?.toString();

    String statusLabel = isCredit ? 'Saldo Masuk' : 'Saldo Keluar';
    Color statusColor = isCredit ? const Color(0xFF059669) : const Color(0xFF0F172A);
    Color statusBadgeBg = isCredit ? const Color(0xFFDCFCE7) : const Color(0xFFF1F5F9);

    if (withdrawStatus == 'pending' || (category == 'withdraw' && withdrawStatus == null)) {
      statusLabel = 'Menunggu Persetujuan Admin';
      statusColor = const Color(0xFFB45309);
      statusBadgeBg = const Color(0xFFFEF3C7);
    } else if (withdrawStatus == 'approved') {
      statusLabel = 'Transfer Berhasil & Selesai';
      statusColor = const Color(0xFF15803D);
      statusBadgeBg = const Color(0xFFDCFCE7);
    } else if (withdrawStatus == 'rejected') {
      statusLabel = 'Transfer Ditolak (Saldo Dikembalikan)';
      statusColor = AppTheme.primaryRed;
      statusBadgeBg = const Color(0xFFFFE4E6);
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          padding: const EdgeInsets.all(24),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Handle
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(color: const Color(0xFFE2E8F0), borderRadius: BorderRadius.circular(2)),
              ),
              const SizedBox(height: 18),

              // Top Icon
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(color: statusBadgeBg, shape: BoxShape.circle),
                child: Icon(
                  isCredit ? Icons.arrow_downward_rounded : Icons.arrow_upward_rounded,
                  color: statusColor,
                  size: 28,
                ),
              ),
              const SizedBox(height: 12),

              // Amount
              Text(
                '${isCredit ? '+' : '-'}${CurrencyFormatter.formatRupiah(amount)}',
                style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: statusColor),
              ),
              const SizedBox(height: 6),

              // Status Pill
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(color: statusBadgeBg, borderRadius: BorderRadius.circular(20)),
                child: Text(
                  statusLabel,
                  style: TextStyle(color: statusColor, fontSize: 11, fontWeight: FontWeight.bold),
                ),
              ),
              const SizedBox(height: 20),

              // Receipt Box
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  children: [
                    _buildReceiptRow('Kategori', category.toUpperCase()),
                    const Divider(height: 16, color: Color(0xFFE2E8F0)),
                    _buildReceiptRow(
                      'No. Referensi',
                      refId,
                      isCopyable: refId != '-',
                      onCopy: () {
                        Clipboard.setData(ClipboardData(text: refId));
                        AppAlert.showSuccess(context, title: 'Disalin', message: 'No. Referensi berhasil disalin.');
                      },
                    ),
                    if (bankName != null && bankName.isNotEmpty) ...[
                      const Divider(height: 16, color: Color(0xFFE2E8F0)),
                      _buildReceiptRow('Tujuan Transfer', '$bankName - $accNum\n(a.n $accHolder)'),
                    ],
                    const Divider(height: 16, color: Color(0xFFE2E8F0)),
                    _buildReceiptRow('Keterangan', description),
                    if (adminNotes != null && adminNotes.isNotEmpty) ...[
                      const Divider(height: 16, color: Color(0xFFE2E8F0)),
                      _buildReceiptRow('Catatan Admin', adminNotes),
                    ],
                    const Divider(height: 16, color: Color(0xFFE2E8F0)),
                    _buildReceiptRow('Waktu Transaksi', createdAt),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Close button
              SizedBox(
                width: double.infinity,
                height: 46,
                child: ElevatedButton(
                  onPressed: () => Navigator.pop(ctx),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF0F172A),
                    foregroundColor: Colors.white,
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  child: const Text('Tutup Rincian', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  void _showTopUpDetail(BuildContext context, Map<String, dynamic> log) {
    final code = log['topup_code']?.toString() ?? '-';
    final amount = double.tryParse(log['amount']?.toString() ?? '0') ?? 0;
    final status = log['status']?.toString().toLowerCase() ?? 'pending';
    final paymentMethod = log['payment_method']?.toString().toUpperCase() ?? 'MIDTRANS';
    final createdAt = log['created_at']?.toString() ?? '-';
    final snapToken = log['snap_token']?.toString();

    Color statusColor = const Color(0xFFD97706);
    Color statusBadgeBg = const Color(0xFFFEF3C7);
    String statusLabel = 'Menunggu Pembayaran';

    if (status == 'success') {
      statusColor = const Color(0xFF059669);
      statusBadgeBg = const Color(0xFFDCFCE7);
      statusLabel = 'Top Up Berhasil';
    } else if (status == 'failed' || status == 'canceled') {
      statusColor = AppTheme.primaryRed;
      statusBadgeBg = const Color(0xFFFFE4E6);
      statusLabel = status == 'canceled' ? 'Dibatalkan' : 'Gagal / Kadaluarsa';
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          padding: const EdgeInsets.all(24),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Handle
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(color: const Color(0xFFE2E8F0), borderRadius: BorderRadius.circular(2)),
              ),
              const SizedBox(height: 18),

              // Top Icon
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(color: statusBadgeBg, shape: BoxShape.circle),
                child: Icon(
                  status == 'success' ? Icons.check_circle_rounded : (status == 'pending' ? Icons.hourglass_top_rounded : Icons.cancel_rounded),
                  color: statusColor,
                  size: 28,
                ),
              ),
              const SizedBox(height: 12),

              // Amount
              Text(
                CurrencyFormatter.formatRupiah(amount),
                style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: statusColor),
              ),
              const SizedBox(height: 6),

              // Status Pill
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(color: statusBadgeBg, borderRadius: BorderRadius.circular(20)),
                child: Text(
                  statusLabel,
                  style: TextStyle(color: statusColor, fontSize: 11, fontWeight: FontWeight.bold),
                ),
              ),
              const SizedBox(height: 20),

              // Receipt Box
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  children: [
                    _buildReceiptRow(
                      'Kode Top-Up',
                      code,
                      isCopyable: true,
                      onCopy: () {
                        Clipboard.setData(ClipboardData(text: code));
                        AppAlert.showSuccess(context, title: 'Disalin', message: 'Kode Top Up berhasil disalin.');
                      },
                    ),
                    const Divider(height: 16, color: Color(0xFFE2E8F0)),
                    _buildReceiptRow('Metode', paymentMethod),
                    const Divider(height: 16, color: Color(0xFFE2E8F0)),
                    _buildReceiptRow('Waktu Pemesanan', createdAt),
                    const Divider(height: 16, color: Color(0xFFE2E8F0)),
                    _buildReceiptRow('Status Tiket', status.toUpperCase()),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Actions
              if (status == 'pending' && snapToken != null && snapToken.isNotEmpty) ...[
                SizedBox(
                  width: double.infinity,
                  height: 46,
                  child: ElevatedButton(
                    onPressed: () {
                      Navigator.pop(ctx);
                      final redirectUrl = 'https://app.sandbox.midtrans.com/snap/v2/vtweb/$snapToken';
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => InAppPaymentScreen(
                            paymentUrl: redirectUrl,
                            orderId: code,
                            amount: amount,
                            title: 'Lanjutkan Top Up CicalengkaPay',
                            onPaymentComplete: () {
                              context.read<CustomerController>().fetchWallet();
                            },
                          ),
                        ),
                      );
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primaryRed,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    child: const Text('Lanjutkan Pembayaran Sekarang', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(height: 10),
              ],

              SizedBox(
                width: double.infinity,
                height: 46,
                child: OutlinedButton(
                  onPressed: () => Navigator.pop(ctx),
                  style: OutlinedButton.styleFrom(
                    side: const BorderSide(color: Color(0xFFE2E8F0)),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                  child: const Text('Tutup Rincian', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF334155))),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildReceiptRow(String label, String value, {bool isCopyable = false, VoidCallback? onCopy}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B))),
        const SizedBox(width: 14),
        Flexible(
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Flexible(
                child: Text(
                  value,
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                  textAlign: TextAlign.right,
                ),
              ),
              if (isCopyable && onCopy != null) ...[
                const SizedBox(width: 6),
                InkWell(
                  onTap: onCopy,
                  child: const Icon(Icons.copy_rounded, size: 14, color: AppTheme.primaryRed),
                ),
              ],
            ],
          ),
        ),
      ],
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
