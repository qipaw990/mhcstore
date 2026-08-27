import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/driver_controller.dart';

class DriverEarningsScreen extends StatefulWidget {
  const DriverEarningsScreen({super.key});

  @override
  State<DriverEarningsScreen> createState() => _DriverEarningsScreenState();
}

class _DriverEarningsScreenState extends State<DriverEarningsScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DriverController>().fetchEarnings();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = context.watch<DriverController>();
    final earnings = ctrl.earnings;
    final wallet = earnings?['wallet'] as Map<String, dynamic>? ?? {};
    final balance = double.tryParse(wallet['balance']?.toString() ?? '0') ?? 0.0;
    final totalEarned = double.tryParse(wallet['total_earned']?.toString() ?? '0') ?? 0.0;
    final totalWithdrawn = double.tryParse(earnings?['total_withdrawn']?.toString() ?? '0') ?? 0.0;
    final withdrawRequests = (earnings?['withdraw_requests'] as List<dynamic>?) ?? [];
    final transactions = (earnings?['transactions'] as List<dynamic>?) ?? [];
    final reviews = ctrl.reviews;

    if (ctrl.isLoading && earnings == null) {
      return const Center(child: CircularProgressIndicator(color: AppTheme.primaryRed));
    }

    return CustomScrollView(
      slivers: [
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                // Driver wallet balance card
                _buildWalletCard(balance, totalEarned, totalWithdrawn, context, ctrl),
                const SizedBox(height: 12),

                // Performance metrics
                _buildPerformanceRow(ctrl),
                const SizedBox(height: 12),

                // Tabs header
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
                            const Icon(Icons.account_balance_wallet_outlined, size: 14),
                            const SizedBox(width: 4),
                            Text('Penarikan (${withdrawRequests.length})'),
                          ],
                        ),
                      ),
                      Tab(
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.two_wheeler_rounded, size: 14),
                            const SizedBox(width: 4),
                            Text('Komisi (${transactions.length})'),
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

        // Tab content
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: SizedBox(
              height: 500,
              child: TabBarView(
                controller: _tabController,
                children: [
                  _buildWithdrawTab(withdrawRequests),
                  _buildCommissionTab(transactions),
                ],
              ),
            ),
          ),
        ),

        // Reviews section
        if (reviews.isNotEmpty) ...[
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: Colors.amber.withValues(alpha: 0.15),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.star_rounded, color: Colors.amber, size: 20),
                      ),
                      const SizedBox(width: 10),
                      const Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Rating & Ulasan Pelanggan', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                          Text('Penilaian kepuasan pengantaran', style: TextStyle(fontSize: 10, color: Color(0xFF64748B))),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                ],
              ),
            ),
          ),
          SliverList(
            delegate: SliverChildBuilderDelegate(
              (context, i) => Padding(
                padding: EdgeInsets.fromLTRB(16, 0, 16, i < reviews.length - 1 ? 8 : 24),
                child: _buildReviewCard(reviews[i]),
              ),
              childCount: reviews.length,
            ),
          ),
        ] else
          const SliverToBoxAdapter(child: SizedBox(height: 40)),
      ],
    );
  }

  Widget _buildWalletCard(double balance, double totalEarned, double totalWithdrawn, BuildContext context, DriverController ctrl) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFFEE2737), Color(0xFFB91C1C)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(color: const Color(0xFFEE2737).withValues(alpha: 0.3), blurRadius: 20, offset: const Offset(0, 8)),
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
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Text('DOMPET MITRA DRIVER', style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold, letterSpacing: 0.5)),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    CurrencyFormatter.formatRupiah(balance),
                    style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w900),
                  ),
                ],
              ),
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.account_balance_wallet_rounded, color: Colors.white, size: 22),
              ),
            ],
          ),

          const SizedBox(height: 16),

          // Mini stats
          Row(
            children: [
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Total Pendapatan', style: TextStyle(color: Colors.white60, fontSize: 10)),
                      Text(CurrencyFormatter.formatRupiah(totalEarned), style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Total Ditarik', style: TextStyle(color: Colors.white60, fontSize: 10)),
                      Text(CurrencyFormatter.formatRupiah(totalWithdrawn), style: const TextStyle(color: Colors.amber, fontSize: 12, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 14),
          const Divider(color: Colors.white24, height: 1),
          const SizedBox(height: 12),

          GestureDetector(
            onTap: () => _showWithdrawDialog(context, balance, ctrl),
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 11),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.arrow_upward_rounded, color: AppTheme.primaryRed, size: 16),
                  SizedBox(width: 8),
                  Text('Tarik Dana Komisi', style: TextStyle(color: AppTheme.primaryRed, fontSize: 13, fontWeight: FontWeight.bold)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPerformanceRow(DriverController ctrl) {
    return Row(
      children: [
        Expanded(
          child: _metricCard('85% Bersih', 'Bagi Hasil Driver', const Color(0xFF059669)),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _metricCard('Gratis (Rp 0)', 'Biaya Penarikan', const Color(0xFF2563EB)),
        ),
      ],
    );
  }

  Widget _metricCard(String value, String label, Color color) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          Text(value, style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: color)),
          const SizedBox(height: 2),
          Text(label, style: const TextStyle(fontSize: 10, color: Color(0xFF64748B))),
        ],
      ),
    );
  }

  Widget _buildWithdrawTab(List<dynamic> requests) {
    if (requests.isEmpty) {
      return _emptyState(Icons.arrow_upward_rounded, 'Belum Ada Penarikan', 'Ajukan penarikan saldo dari dompet driver Anda.');
    }
    return ListView.separated(
      padding: const EdgeInsets.only(top: 12, bottom: 16),
      itemCount: requests.length,
      separatorBuilder: (context, index) => const SizedBox(height: 8),
      itemBuilder: (_, i) => _buildWithdrawCard(requests[i]),
    );
  }

  Widget _buildWithdrawCard(Map<String, dynamic> wd) {
    final status = wd['status'] ?? 'pending';
    final amount = double.tryParse(wd['amount']?.toString() ?? '0') ?? 0;

    Color color;
    String label;
    IconData icon;

    if (status == 'approved') {
      color = const Color(0xFF059669);
      label = 'Berhasil Ditransfer';
      icon = Icons.check_circle_rounded;
    } else if (status == 'rejected') {
      color = AppTheme.primaryRed;
      label = 'Ditolak';
      icon = Icons.cancel_rounded;
    } else {
      color = const Color(0xFFD97706);
      label = 'Menunggu Transfer';
      icon = Icons.hourglass_empty_rounded;
    }

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.2)),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(color: const Color(0xFFFEE2E2), shape: BoxShape.circle),
            child: const Icon(Icons.account_balance_rounded, color: AppTheme.primaryRed, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(wd['bank_name'] ?? 'Bank', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                Text('${wd['account_number']} (${wd['account_holder']})',
                    style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)), overflow: TextOverflow.ellipsis),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text('-${CurrencyFormatter.formatRupiah(amount)}', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: color)),
              Container(
                margin: const EdgeInsets.only(top: 3),
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(6)),
                child: Row(
                  children: [
                    Icon(icon, color: color, size: 10),
                    const SizedBox(width: 3),
                    Text(label, style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: color)),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildCommissionTab(List<dynamic> transactions) {
    if (transactions.isEmpty) {
      return _emptyState(Icons.two_wheeler_rounded, 'Belum Ada Komisi', 'Selesaikan orderan pertama untuk mengumpulkan saldo.');
    }
    return ListView.separated(
      padding: const EdgeInsets.only(top: 12, bottom: 16),
      itemCount: transactions.length,
      separatorBuilder: (context, index) => const SizedBox(height: 8),
      itemBuilder: (_, i) => _buildCommissionCard(transactions[i]),
    );
  }

  Widget _buildCommissionCard(Map<String, dynamic> tx) {
    final isCredit = (tx['type'] ?? '') == 'credit';
    final amount = double.tryParse(tx['amount']?.toString() ?? '0') ?? 0;

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
              color: isCredit ? const Color(0xFFD1FAE5) : const Color(0xFFFEE2E2),
              shape: BoxShape.circle,
            ),
            child: Icon(
              isCredit ? Icons.two_wheeler_rounded : Icons.arrow_upward_rounded,
              color: isCredit ? const Color(0xFF059669) : AppTheme.primaryRed,
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(tx['description'] ?? 'Komisi Pengantaran',
                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
          ),
          Text(
            '${isCredit ? '+' : '-'}${CurrencyFormatter.formatRupiah(amount)}',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: isCredit ? const Color(0xFF059669) : AppTheme.primaryRed,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReviewCard(Map<String, dynamic> rev) {
    final rating = int.tryParse(rev['rating']?.toString() ?? '5') ?? 5;
    final customerName = rev['customer_name'] ?? 'Pelanggan';
    final comment = rev['comment'] ?? '';

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFFDE68A)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: const BoxDecoration(color: Color(0xFFF1F5F9), shape: BoxShape.circle),
                child: const Icon(Icons.person_rounded, color: Color(0xFF64748B), size: 20),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(customerName, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
              ),
              Row(
                children: List.generate(5, (i) => Icon(
                  i < rating ? Icons.star_rounded : Icons.star_outline_rounded,
                  color: Colors.amber,
                  size: 16,
                )),
              ),
            ],
          ),
          if (comment.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text('"$comment"', style: const TextStyle(fontSize: 12, color: Color(0xFF334155), fontStyle: FontStyle.italic)),
          ],
        ],
      ),
    );
  }

  void _showWithdrawDialog(BuildContext context, double balance, DriverController ctrl) {
    String selectedBank = 'GoPay Driver';
    final accCtrl = TextEditingController();
    final holderCtrl = TextEditingController();
    final amountCtrl = TextEditingController();

    final banks = ['GoPay Driver', 'DANA', 'OVO', 'ShopeePay', 'BCA', 'BRI', 'Mandiri', 'BNI', 'BSI'];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModal) => Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: const Color(0xFFE2E8F0), borderRadius: BorderRadius.circular(2)))),
                  const SizedBox(height: 16),
                  const Text('Tarik Dana Driver', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A))),
                  const SizedBox(height: 4),
                  Text('Saldo: ${CurrencyFormatter.formatRupiah(balance)}', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                  const SizedBox(height: 16),

                  // Bank select
                  DropdownButtonFormField<String>(
                    initialValue: selectedBank,
                    decoration: InputDecoration(
                      labelText: 'Tujuan Rekening / E-Wallet',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    items: banks.map((b) => DropdownMenuItem(value: b, child: Text(b))).toList(),
                    onChanged: (v) => setModal(() => selectedBank = v ?? banks[0]),
                  ),
                  const SizedBox(height: 12),

                  TextField(
                    controller: accCtrl,
                    decoration: InputDecoration(
                      labelText: 'Nomor Rekening / No. HP Akun',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    keyboardType: TextInputType.phone,
                  ),
                  const SizedBox(height: 12),

                  TextField(
                    controller: holderCtrl,
                    decoration: InputDecoration(
                      labelText: 'Nama Pemilik Akun / Rekening',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                  const SizedBox(height: 12),

                  TextField(
                    controller: amountCtrl,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      labelText: 'Nominal Penarikan (Rp)',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      suffixIcon: TextButton(
                        onPressed: () => amountCtrl.text = balance.toStringAsFixed(0),
                        child: const Text('Semua', style: TextStyle(color: AppTheme.primaryRed)),
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: const Color(0xFFD1FAE5),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.shield_rounded, color: Color(0xFF059669), size: 16),
                        SizedBox(width: 8),
                        Text('Biaya transfer Rp 0 (Gratis). Diproses segera.', style: TextStyle(fontSize: 11, color: Color(0xFF059669))),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.primaryRed,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      ),
                      onPressed: () async {
                        final acc = accCtrl.text.trim();
                        final holder = holderCtrl.text.trim();
                        final amount = double.tryParse(amountCtrl.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;

                        if (acc.isEmpty || holder.isEmpty || amount < 10000) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Lengkapi semua field. Minimal Rp 10.000')),
                          );
                          return;
                        }
                        if (amount > balance) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Nominal melebihi saldo tersedia!')),
                          );
                          return;
                        }

                        Navigator.pop(ctx);
                        final ok = await ctrl.submitWithdraw(
                          bankName: selectedBank,
                          accountNumber: acc,
                          accountHolder: holder,
                          amount: amount,
                        );

                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(ok ? '✅ Pengajuan penarikan berhasil!' : 'Gagal mengajukan penarikan.'),
                              backgroundColor: ok ? Colors.green : Colors.red,
                            ),
                          );
                        }
                      },
                      child: const Text('Ajukan Penarikan', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
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
            Container(
              width: 60,
              height: 60,
              decoration: const BoxDecoration(color: Color(0xFFF1F5F9), shape: BoxShape.circle),
              child: Icon(icon, color: const Color(0xFF94A3B8), size: 28),
            ),
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


