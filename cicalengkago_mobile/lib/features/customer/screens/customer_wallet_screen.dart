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
          const SizedBox(height: 12),
          // Action buttons row
          Row(
            children: [
              _walletAction(Icons.add_circle_outline_rounded, 'Top Up', () => _showTopUpSheet(context)),
              _walletAction(Icons.qr_code_scanner_rounded, 'Bayar', () {}),
              _walletAction(Icons.history_rounded, 'Riwayat', () => _tabController.animateTo(0)),
              _walletAction(Icons.open_in_new_rounded, 'Web', () => launchUrl(Uri.parse('https://cicago.store/wallet'))),
            ],
          ),
        ],
      ),
    );
  }

  Widget _walletAction(IconData icon, String label, VoidCallback onTap) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Column(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.2),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: Colors.white, size: 20),
            ),
            const SizedBox(height: 4),
            Text(label, style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
          ],
        ),
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
    final selectedMethod = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Pilih Metode Pembayaran',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                      ),
                      Text(
                        'Nominal: ${CurrencyFormatter.formatRupiah(amount.toDouble())}',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFFEF4444)),
                      ),
                    ],
                  ),
                  IconButton(
                    icon: const Icon(Icons.close_rounded, size: 20),
                    onPressed: () => Navigator.pop(ctx),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              _buildPaymentOptionTile(
                ctx,
                code: 'QRIS',
                title: 'QRIS Otomatis (Rekomendasi)',
                subtitle: 'GoPay, OVO, DANA, BCA, Mandiri, BRI, ShopeePay',
                icon: Icons.qr_code_2_rounded,
                isRecommended: true,
              ),
              _buildPaymentOptionTile(
                ctx,
                code: 'BCA',
                title: 'Bank Central Asia (BCA)',
                subtitle: 'Transfer Bank Otomatis (Kode Unik)',
                icon: Icons.account_balance_rounded,
              ),
              _buildPaymentOptionTile(
                ctx,
                code: 'BRI',
                title: 'Bank BRI',
                subtitle: 'Transfer Bank Otomatis (Kode Unik)',
                icon: Icons.account_balance_rounded,
              ),
              _buildPaymentOptionTile(
                ctx,
                code: 'MANDIRI',
                title: 'Bank Mandiri',
                subtitle: 'Transfer Bank Otomatis (Kode Unik)',
                icon: Icons.account_balance_rounded,
              ),
              _buildPaymentOptionTile(
                ctx,
                code: 'DANA',
                title: 'DANA / E-Wallet',
                subtitle: 'Transfer saldo ke DANA Official',
                icon: Icons.account_balance_wallet_rounded,
              ),
              _buildPaymentOptionTile(
                ctx,
                code: 'MIDTRANS',
                title: 'Midtrans Payment Gateway',
                subtitle: 'Virtual Account & Kartu Kredit Otomatis',
                icon: Icons.payment_rounded,
              ),
            ],
          ),
        ),
      ),
    );

    if (selectedMethod == null || !context.mounted) return;

    // Handle Midtrans Snap Fallback
    if (selectedMethod == 'MIDTRANS') {
      _processMidtransTopUp(context, amount);
      return;
    }

    // Handle In-House Automated Transfer / QRIS with 3-digit Unique Code
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
        child: CircularProgressIndicator(color: AppTheme.primaryRed),
      ),
    );

    try {
      final res = await ApiService.post(ApiConstants.paymentCreateInvoice, {
        'amount': amount,
        'bank': selectedMethod,
        'type': 'topup',
      });

      if (context.mounted) {
        Navigator.pop(context); // Dismiss loading
      }

      if (res['success'] == true && res['data'] != null) {
        final invoiceData = Map<String, dynamic>.from(res['data'] as Map);
        if (context.mounted) {
          final paid = await Navigator.push<bool>(
            context,
            MaterialPageRoute(
              builder: (_) => PaymentInvoiceScreen(invoiceData: invoiceData),
            ),
          );

          if (paid == true && context.mounted) {
            context.read<CustomerController>().fetchWallet();
          }
        }
        return;
      }

      if (context.mounted) {
        AppAlert.showError(
          context,
          title: 'Gagal Membuat Tiket',
          message: res['message'] ?? 'Tidak dapat membuat tagihan pembayaran.',
        );
      }
    } catch (e) {
      if (context.mounted) {
        Navigator.pop(context);
        AppAlert.showError(context, title: 'Error', message: e.toString());
      }
    }
  }

  Widget _buildPaymentOptionTile(
    BuildContext ctx, {
    required String code,
    required String title,
    required String subtitle,
    required IconData icon,
    bool isRecommended = false,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: isRecommended ? const Color(0xFFFEF2F2) : const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isRecommended ? const Color(0xFFFCA5A5) : const Color(0xFFE2E8F0),
        ),
      ),
      child: ListTile(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 2),
        leading: Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: isRecommended ? const Color(0xFFEF4444) : Colors.white,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Icon(icon, color: isRecommended ? Colors.white : const Color(0xFF0F172A), size: 20),
        ),
        title: Row(
          children: [
            Flexible(
              child: Text(
                title,
                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            if (isRecommended) ...[
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                decoration: BoxDecoration(
                  color: const Color(0xFFEF4444),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: const Text('0% FEE', style: TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.w900)),
              ),
            ],
          ],
        ),
        subtitle: Text(
          subtitle,
          style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        trailing: const Icon(Icons.chevron_right_rounded, size: 20, color: Color(0xFF94A3B8)),
        onTap: () => Navigator.pop(ctx, code),
      ),
    );
  }

  Future<void> _processMidtransTopUp(BuildContext context, int amount) async {
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
        Navigator.pop(context);
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
        AppAlert.showError(context, title: 'Gagal', message: res['message'] ?? 'Gagal membuat tiket pembayaran Top-Up.');
      }
    } catch (e) {
      if (context.mounted) {
        Navigator.pop(context);
        AppAlert.showError(context, title: 'Error', message: e.toString());
      }
    }
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
