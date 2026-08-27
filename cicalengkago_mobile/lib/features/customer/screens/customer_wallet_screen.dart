import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/customer_controller.dart';

class CustomerWalletScreen extends StatefulWidget {
  const CustomerWalletScreen({super.key});

  @override
  State<CustomerWalletScreen> createState() => _CustomerWalletScreenState();
}

class _CustomerWalletScreenState extends State<CustomerWalletScreen> {
  final _amountController = TextEditingController();
  bool _isTopupLoading = false;

  void _handleTopup() async {
    final amountText = _amountController.text.trim();
    final amount = double.tryParse(amountText) ?? 0.0;

    if (amount < 10000) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nominal top up minimal Rp 10.000')),
      );
      return;
    }

    setState(() {
      _isTopupLoading = true;
    });

    final res = await ApiService.post('${ApiConstants.baseUrl}/payment/topup-snap', {
      'amount': amount.toString(),
    });

    setState(() {
      _isTopupLoading = false;
    });

    if (res['success'] == true && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Permintaan Top-Up berhasil dibuat! Silakan bayar via QRIS/Bank.')),
      );
      context.read<CustomerController>().fetchWallet();
      _amountController.clear();
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['message'] ?? 'Gagal memproses topup'),
          backgroundColor: Colors.redAccent,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final customerCtrl = context.watch<CustomerController>();
    final wallet = customerCtrl.wallet?['wallet'];
    final transactions = (customerCtrl.wallet?['transactions'] as List<dynamic>?) ?? [];
    final double balance = (wallet?['balance'] != null)
        ? double.parse(wallet['balance'].toString())
        : 0.0;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dompet CicalengkaPay'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Balance Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFEE2737), Color(0xFF991B1B)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Saldo Utama CicalengkaPay', style: TextStyle(color: Colors.white70, fontSize: 13)),
                  const SizedBox(height: 6),
                  Text(
                    CurrencyFormatter.formatRupiah(balance),
                    style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            const Text('Isi Saldo CicalengkaPay', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 12),

            TextField(
              controller: _amountController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Nominal Isi Saldo (Rp)',
                prefixText: 'Rp ',
                hintText: '100000',
              ),
            ),
            const SizedBox(height: 12),

            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [10000, 50000, 100000, 200000].map((val) {
                return OutlinedButton(
                  onPressed: () {
                    _amountController.text = val.toString();
                  },
                  child: Text('${val ~/ 1000}rb'),
                );
              }).toList(),
            ),
            const SizedBox(height: 16),

            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _isTopupLoading ? null : _handleTopup,
                child: _isTopupLoading
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text('PROSES TOP UP SALDO'),
              ),
            ),

            const SizedBox(height: 28),
            const Text('Riwayat Transaksi Dompet', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 12),

            if (transactions.isEmpty)
              const Center(child: Text('Belum ada transaksi dompet'))
            else
              ListView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: transactions.length,
                itemBuilder: (context, index) {
                  final tx = transactions[index];
                  final isCredit = tx['transaction_type'] == 'credit' || tx['transaction_type'] == 'topup';
                  final amount = double.tryParse(tx['amount']?.toString() ?? '0') ?? 0.0;

                  return Card(
                    margin: const EdgeInsets.only(bottom: 10),
                    child: ListTile(
                      leading: CircleAvatar(
                        backgroundColor: isCredit ? Colors.green[50] : Colors.red[50],
                        child: Icon(
                          isCredit ? Icons.add_circle : Icons.remove_circle,
                          color: isCredit ? Colors.green : Colors.red,
                        ),
                      ),
                      title: Text(
                        tx['description'] ?? (isCredit ? 'Topup Saldo' : 'Pembayaran Order'),
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                      ),
                      subtitle: Text(
                        CurrencyFormatter.formatDate(tx['created_at'] ?? ''),
                        style: const TextStyle(fontSize: 11),
                      ),
                      trailing: Text(
                        '${isCredit ? '+' : '-'}${CurrencyFormatter.formatRupiah(amount)}',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: isCredit ? Colors.green : Colors.red,
                        ),
                      ),
                    ),
                  );
                },
              ),
          ],
        ),
      ),
    );
  }
}
