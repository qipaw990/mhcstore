import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

class DriverTransactionAlert {
  static final currencyFormat = NumberFormat.currency(
    locale: 'id_ID',
    symbol: 'Rp ',
    decimalDigits: 0,
  );

  /// Tampilkan notifikasi banner melayang di atas layar
  static void showFloatingBanner(
    BuildContext context, {
    required String title,
    required String message,
    required double amount,
    required String type, // 'credit' or 'debit'
    String? orderCode,
  }) {
    final overlay = Overlay.of(context);
    late OverlayEntry entry;

    final isCredit = type.toLowerCase() == 'credit';
    final amountColor = isCredit ? const Color(0xFF16A34A) : const Color(0xFFDC2626);
    final sign = isCredit ? '+' : '-';

    entry = OverlayEntry(
      builder: (ctx) => Positioned(
        top: MediaQuery.of(ctx).padding.top + 12,
        left: 16,
        right: 16,
        child: Material(
          color: Colors.transparent,
          child: TweenAnimationBuilder<double>(
            tween: Tween(begin: 0.0, end: 1.0),
            duration: const Duration(milliseconds: 350),
            curve: Curves.easeOutBack,
            builder: (context, val, child) {
              return Transform.translate(
                offset: Offset(0, (1 - val) * -40),
                child: Opacity(opacity: val.clamp(0.0, 1.0), child: child),
              );
            },
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: isCredit ? const Color(0xFF22C55E).withValues(alpha: 0.5) : const Color(0xFFEF4444).withValues(alpha: 0.5),
                  width: 1.5,
                ),
                boxShadow: [
                  BoxShadow(
                    color: isCredit ? const Color(0xFF22C55E).withValues(alpha: 0.25) : const Color(0xFFEF4444).withValues(alpha: 0.25),
                    blurRadius: 16,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: isCredit ? const Color(0xFF16A34A).withValues(alpha: 0.2) : const Color(0xFFDC2626).withValues(alpha: 0.2),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      isCredit ? Icons.account_balance_wallet_rounded : Icons.outbox_rounded,
                      color: isCredit ? const Color(0xFF4ADE80) : const Color(0xFFF87171),
                      size: 24,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              title,
                              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white),
                            ),
                            Text(
                              '$sign${currencyFormat.format(amount)}',
                              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: amountColor),
                            ),
                          ],
                        ),
                        const SizedBox(height: 2),
                        Text(
                          message,
                          style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );

    overlay.insert(entry);
    Future.delayed(const Duration(seconds: 4), () {
      if (entry.mounted) {
        entry.remove();
      }
    });
  }

  /// Tampilkan Dialog Popup Transaksi Lengkap
  static void showTransactionDialog(
    BuildContext context, {
    required String title,
    required String message,
    required double amount,
    required String category,
    required double currentBalance,
    String? referenceCode,
  }) {
    final isCredit = category != 'withdrawal';
    final sign = isCredit ? '+' : '-';
    final amountColor = isCredit ? const Color(0xFF16A34A) : const Color(0xFFDC2626);

    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        backgroundColor: Colors.white,
        child: Padding(
          padding: const EdgeInsets.all(22),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                  color: isCredit ? const Color(0xFFDCFCE7) : const Color(0xFFFEE2E2),
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: isCredit ? const Color(0xFF86EFAC) : const Color(0xFFFCA5A5),
                    width: 2,
                  ),
                ),
                child: Icon(
                  isCredit ? Icons.check_circle_rounded : Icons.account_balance_rounded,
                  color: isCredit ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                  size: 36,
                ),
              ),
              const SizedBox(height: 14),
              Text(
                title,
                style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 6),
              Text(
                message,
                style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Nominal Mutasi', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                        Text(
                          '$sign${currencyFormat.format(amount)}',
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: amountColor),
                        ),
                      ],
                    ),
                    if (referenceCode != null && referenceCode.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('No. Referensi', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                          Text(
                            '#$referenceCode',
                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                          ),
                        ],
                      ),
                    ],
                    const Divider(height: 18, color: Color(0xFFE2E8F0)),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Saldo Dompet Sekarang', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF334155))),
                        Text(
                          currencyFormat.format(currentBalance),
                          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF059669),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                  onPressed: () => Navigator.of(ctx).pop(),
                  child: const Text('Tutup', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
