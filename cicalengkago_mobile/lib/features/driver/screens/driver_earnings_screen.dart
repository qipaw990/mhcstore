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
    final deliveredOrders = (earnings?['delivered_orders'] as List<dynamic>?) ?? ctrl.deliveredOrders;
    final reviews = ctrl.reviews;
    final commissionCount = transactions.isNotEmpty ? transactions.length : deliveredOrders.length;

    if (ctrl.isLoading && earnings == null) {
      return const Center(child: CircularProgressIndicator(color: AppTheme.primaryRed));
    }

    return Container(
      color: const Color(0xFF090D16),
      child: CustomScrollView(
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
                      color: const Color(0xFF0F172A),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFF1E293B)),
                    ),
                    child: TabBar(
                      controller: _tabController,
                      labelColor: const Color(0xFFEF4444),
                      unselectedLabelColor: const Color(0xFF94A3B8),
                      indicator: BoxDecoration(
                        color: const Color(0xFF450A0A),
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
                              Text('Komisi ($commissionCount)'),
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
                    _buildCommissionTab(transactions, deliveredOrders),
                  ],
                ),
              ),
            ),
          ),

          const SliverToBoxAdapter(child: SizedBox(height: 40)),
        ],
      ),
    );
  }

  Widget _buildWalletCard(double balance, double totalEarned, double totalWithdrawn, BuildContext context, DriverController ctrl) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFF334155)),
        boxShadow: const [
          BoxShadow(color: Colors.black45, blurRadius: 20, offset: Offset(0, 8)),
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
                      color: const Color(0xFFEF4444).withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Text('DOMPET MITRA DRIVER', style: TextStyle(color: Color(0xFFFCA5A5), fontSize: 9, fontWeight: FontWeight.bold, letterSpacing: 0.5)),
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
                  color: Colors.white.withValues(alpha: 0.1),
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
                    color: Colors.black.withValues(alpha: 0.25),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: Colors.white.withValues(alpha: 0.05)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Total Pendapatan', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 10)),
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
                    color: Colors.black.withValues(alpha: 0.25),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: Colors.white.withValues(alpha: 0.05)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Total Ditarik', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 10)),
                      Text(CurrencyFormatter.formatRupiah(totalWithdrawn), style: const TextStyle(color: Color(0xFFFBBF24), fontSize: 12, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 14),
          const Divider(color: Color(0xFF334155), height: 1),
          const SizedBox(height: 12),

          GestureDetector(
            onTap: () => _showWithdrawDialog(context, balance, ctrl),
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 11),
              decoration: BoxDecoration(
                color: const Color(0xFFEF4444),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.arrow_upward_rounded, color: Colors.white, size: 16),
                  SizedBox(width: 8),
                  Text('Tarik Dana Komisi', style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
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
          child: _metricCard('100% Bersih', 'Komisi Ongkir Utuh', const Color(0xFF34D399)),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _metricCard('Gratis (Rp 0)', 'Biaya Penarikan', const Color(0xFF60A5FA)),
        ),
      ],
    );
  }

  Widget _metricCard(String value, String label, Color color) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF1E293B)),
      ),
      child: Column(
        children: [
          Text(value, style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: color)),
          const SizedBox(height: 2),
          Text(label, style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
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
      color = const Color(0xFF34D399);
      label = 'Berhasil Ditransfer';
      icon = Icons.check_circle_rounded;
    } else if (status == 'rejected') {
      color = const Color(0xFFF87171);
      label = 'Ditolak';
      icon = Icons.cancel_rounded;
    } else {
      color = const Color(0xFFFBBF24);
      label = 'Menunggu Transfer';
      icon = Icons.hourglass_empty_rounded;
    }

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF1E293B)),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: const BoxDecoration(color: Color(0xFF450A0A), shape: BoxShape.circle),
            child: const Icon(Icons.account_balance_rounded, color: Color(0xFFEF4444), size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(wd['bank_name'] ?? 'Bank', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white)),
                Text('${wd['account_number']} (${wd['account_holder']})',
                    style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)), overflow: TextOverflow.ellipsis),
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
                decoration: BoxDecoration(color: color.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(6)),
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

  Widget _buildCommissionTab(List<dynamic> transactions, List<dynamic> deliveredOrders) {
    final list = transactions.isNotEmpty ? transactions : deliveredOrders;
    if (list.isEmpty) {
      return _emptyState(Icons.two_wheeler_rounded, 'Belum Ada Komisi', 'Selesaikan orderan pertama untuk mengumpulkan saldo.');
    }
    return ListView.separated(
      padding: const EdgeInsets.only(top: 12, bottom: 16),
      itemCount: list.length,
      separatorBuilder: (context, index) => const SizedBox(height: 8),
      itemBuilder: (_, i) {
        final item = list[i] is Map<String, dynamic>
            ? list[i] as Map<String, dynamic>
            : Map<String, dynamic>.from(list[i] as Map);
        return _buildCommissionCard(item);
      },
    );
  }

  Widget _buildCommissionCard(Map<String, dynamic> tx) {
    final isCredit = (tx['type'] ?? '') == 'credit';
    final amount = double.tryParse(tx['amount']?.toString() ?? '0') ?? 0;
    final orderCode = tx['order_code']?.toString();
    final storeName = tx['store_name']?.toString();
    final customerName = tx['customer_name']?.toString();

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFF1E293B)),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: isCredit ? const Color(0xFF064E3B) : const Color(0xFF450A0A),
              shape: BoxShape.circle,
            ),
            child: Icon(
              isCredit ? Icons.two_wheeler_rounded : Icons.arrow_upward_rounded,
              color: isCredit ? const Color(0xFF34D399) : const Color(0xFFF87171),
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  orderCode != null ? '#$orderCode' : (tx['description'] ?? 'Komisi Pengantaran'),
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white),
                ),
                if (storeName != null || customerName != null) ...[
                  const SizedBox(height: 2),
                  Text(
                    '${storeName ?? 'Toko'} → ${customerName ?? 'Pelanggan'}',
                    style: const TextStyle(fontSize: 10.5, color: Color(0xFF94A3B8)),
                    overflow: TextOverflow.ellipsis,
                  ),
                ] else if (tx['created_at'] != null) ...[
                  const SizedBox(height: 2),
                  Text(
                    tx['created_at'].toString(),
                    style: const TextStyle(fontSize: 9.5, color: Color(0xFF64748B)),
                  ),
                ],
              ],
            ),
          ),
          Text(
            '${isCredit ? '+' : '-'}${CurrencyFormatter.formatRupiah(amount)}',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: isCredit ? const Color(0xFF34D399) : const Color(0xFFF87171),
            ),
          ),
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
              color: Color(0xFF0F172A),
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              border: Border(top: BorderSide(color: Color(0xFF1E293B))),
            ),
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: const Color(0xFF334155), borderRadius: BorderRadius.circular(2)))),
                  const SizedBox(height: 16),
                  const Text('Tarik Dana Driver', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
                  const SizedBox(height: 4),
                  Text('Saldo: ${CurrencyFormatter.formatRupiah(balance)}', style: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8))),
                  const SizedBox(height: 16),

                  // Bank select
                  DropdownButtonFormField<String>(
                    initialValue: selectedBank,
                    dropdownColor: const Color(0xFF1E293B),
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    decoration: InputDecoration(
                      labelText: 'Tujuan Rekening / E-Wallet',
                      labelStyle: const TextStyle(color: Color(0xFF94A3B8)),
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF334155))),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF334155))),
                    ),
                    items: banks.map((b) => DropdownMenuItem(value: b, child: Text(b))).toList(),
                    onChanged: (v) => setModal(() => selectedBank = v ?? banks[0]),
                  ),
                  const SizedBox(height: 12),

                  TextField(
                    controller: accCtrl,
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    decoration: InputDecoration(
                      labelText: 'Nomor Rekening / HP E-Wallet',
                      labelStyle: const TextStyle(color: Color(0xFF94A3B8)),
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF334155))),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF334155))),
                    ),
                  ),
                  const SizedBox(height: 12),

                  TextField(
                    controller: holderCtrl,
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    decoration: InputDecoration(
                      labelText: 'Nama Pemilik Rekening',
                      labelStyle: const TextStyle(color: Color(0xFF94A3B8)),
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF334155))),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF334155))),
                    ),
                  ),
                  const SizedBox(height: 12),

                  TextField(
                    controller: amountCtrl,
                    keyboardType: TextInputType.number,
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    decoration: InputDecoration(
                      labelText: 'Nominal Penarikan (Rp)',
                      labelStyle: const TextStyle(color: Color(0xFF94A3B8)),
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF334155))),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF334155))),
                      suffixIcon: TextButton(
                        onPressed: () => amountCtrl.text = balance.toInt().toString(),
                        child: const Text('SEMUA', style: TextStyle(color: Color(0xFFEF4444), fontWeight: FontWeight.bold, fontSize: 11)),
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),

                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFEF4444),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      onPressed: () async {
                        final amt = double.tryParse(amountCtrl.text.trim()) ?? 0;
                        if (amt < 10000) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Minimal penarikan adalah Rp 10.000')),
                          );
                          return;
                        }
                        if (amt > balance) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Saldo dompet tidak mencukupi')),
                          );
                          return;
                        }
                        Navigator.pop(ctx);
                        final ok = await ctrl.submitWithdraw(
                          amount: amt,
                          bankName: selectedBank,
                          accountNumber: accCtrl.text.trim(),
                          accountHolder: holderCtrl.text.trim(),
                        );
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(ok ? 'Permintaan penarikan berhasil dikirim!' : 'Gagal mengajukan penarikan'),
                              backgroundColor: ok ? Colors.green : Colors.red,
                            ),
                          );
                        }
                      },
                      child: const Text('Kirim Pengajuan Penarikan', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
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
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: const BoxDecoration(
                color: Color(0xFF1E293B),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 32, color: const Color(0xFF64748B)),
            ),
            const SizedBox(height: 12),
            Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white)),
            const SizedBox(height: 4),
            Text(subtitle, style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}
