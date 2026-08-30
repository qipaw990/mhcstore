import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';
import 'package:url_launcher/url_launcher.dart';
import '../utils/currency_formatter.dart';

class ReceiptPrinterService {
  /// Generate and print thermal receipt (58mm/80mm roll format) with CicalengkaGO logo
  static Future<void> printReceipt(Map<String, dynamic> receipt) async {
    final doc = pw.Document();

    // Load CicalengkaGO logo from assets
    pw.ImageProvider? logoImage;
    try {
      final logoBytes = await rootBundle.load('assets/images/app_logo.png');
      logoImage = pw.MemoryImage(logoBytes.buffer.asUint8List());
    } catch (_) {
      logoImage = null;
    }

    final storeName = receipt['store_name']?.toString() ?? 'Toko Mitra CicalengkaGO';
    final storeAddress = receipt['store_address']?.toString() ?? 'Cicalengka, Kab. Bandung';
    final storePhone = receipt['store_phone']?.toString() ?? '';
    final orderCode = receipt['order_code']?.toString() ?? 'POS-${DateTime.now().millisecondsSinceEpoch}';
    final createdAt = receipt['created_at']?.toString() ?? DateFormat('dd/MM/yyyy HH:mm').format(DateTime.now());
    final customerName = receipt['customer_name']?.toString() ?? 'Pelanggan Langsung';
    final paymentMethod = (receipt['payment_method']?.toString().toLowerCase() ?? 'cash');
    final paymentLabel = paymentMethod == 'cash' || paymentMethod == 'cod' ? 'TUNAI' : (paymentMethod == 'qris' ? 'QRIS' : 'TRANSFER');

    final items = (receipt['items'] as List<dynamic>?) ?? [];
    final subtotal = double.tryParse(receipt['order_amount']?.toString() ?? receipt['total_amount']?.toString() ?? '0') ?? 0.0;
    final discount = double.tryParse(receipt['discount_amount']?.toString() ?? '0') ?? 0.0;
    final totalAmount = double.tryParse(receipt['total_amount']?.toString() ?? '0') ?? 0.0;
    final cashGiven = double.tryParse(receipt['cash_given']?.toString() ?? totalAmount.toString()) ?? totalAmount;
    final changeAmount = double.tryParse(receipt['change_amount']?.toString() ?? '0') ?? 0.0;

    // Standard 58mm Thermal Roll: 58mm width (approx 164 pt), dynamic height
    const rollWidth = 58 * PdfPageFormat.mm;

    doc.addPage(
      pw.Page(
        pageFormat: const PdfPageFormat(rollWidth, double.infinity, marginAll: 8),
        build: (pw.Context context) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.center,
            children: [
              // CicalengkaGO Logo Header
              if (logoImage != null) ...[
                pw.Center(
                  child: pw.Image(logoImage, height: 36, fit: pw.BoxFit.contain),
                ),
                pw.SizedBox(height: 2),
              ],

              // Divider platform branding
              pw.Container(
                width: double.infinity,
                padding: const pw.EdgeInsets.symmetric(vertical: 2),
                decoration: const pw.BoxDecoration(color: PdfColors.black),
                child: pw.Center(
                  child: pw.Text(
                    'CicalengkaGO POS',
                    style: pw.TextStyle(fontSize: 7, fontWeight: pw.FontWeight.bold, color: PdfColors.white),
                    textAlign: pw.TextAlign.center,
                  ),
                ),
              ),
              pw.SizedBox(height: 4),

              // Store Name
              pw.Text(
                storeName.toUpperCase(),
                style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 11),
                textAlign: pw.TextAlign.center,
              ),
              if (storeAddress.isNotEmpty) ...[
                pw.SizedBox(height: 1),
                pw.Text(
                  storeAddress,
                  style: const pw.TextStyle(fontSize: 7.5, color: PdfColors.grey700),
                  textAlign: pw.TextAlign.center,
                ),
              ],
              if (storePhone.isNotEmpty) ...[
                pw.SizedBox(height: 1),
                pw.Text(
                  'Telp: $storePhone',
                  style: const pw.TextStyle(fontSize: 7.5, color: PdfColors.grey700),
                  textAlign: pw.TextAlign.center,
                ),
              ],
              pw.SizedBox(height: 4),
              pw.Text('----------------------------------------', style: const pw.TextStyle(fontSize: 7)),
              pw.SizedBox(height: 2),

              // Transaction Meta
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Text('No: $orderCode', style: pw.TextStyle(fontSize: 7.5, fontWeight: pw.FontWeight.bold)),
                  pw.Text(createdAt, style: const pw.TextStyle(fontSize: 7)),
                ],
              ),
              pw.SizedBox(height: 1),
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Text('Kasir: Toko Mitra', style: const pw.TextStyle(fontSize: 7)),
                  pw.Text('Plg: $customerName', style: const pw.TextStyle(fontSize: 7)),
                ],
              ),
              pw.SizedBox(height: 2),
              pw.Text('========================================', style: const pw.TextStyle(fontSize: 7)),
              pw.SizedBox(height: 3),

              // Items List
              ...items.map((it) {
                final name = it['product_name']?.toString() ?? 'Item';
                final qty = int.tryParse(it['quantity']?.toString() ?? '1') ?? 1;
                final price = double.tryParse(it['price']?.toString() ?? '0') ?? 0.0;
                final lineTotal = price * qty;

                return pw.Padding(
                  padding: const pw.EdgeInsets.only(bottom: 3),
                  child: pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.start,
                    children: [
                      pw.Text(name, style: pw.TextStyle(fontSize: 8, fontWeight: pw.FontWeight.bold)),
                      pw.Row(
                        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                        children: [
                          pw.Text('$qty x ${CurrencyFormatter.formatRupiah(price)}', style: const pw.TextStyle(fontSize: 7.5)),
                          pw.Text(CurrencyFormatter.formatRupiah(lineTotal), style: const pw.TextStyle(fontSize: 7.5)),
                        ],
                      ),
                    ],
                  ),
                );
              }),

              pw.SizedBox(height: 2),
              pw.Text('----------------------------------------', style: const pw.TextStyle(fontSize: 7)),
              pw.SizedBox(height: 2),

              // Financial Calculations
              _buildThermalRow('Subtotal:', CurrencyFormatter.formatRupiah(subtotal)),
              if (discount > 0)
                _buildThermalRow('Diskon:', '-${CurrencyFormatter.formatRupiah(discount)}'),
              _buildThermalRow('TOTAL:', CurrencyFormatter.formatRupiah(totalAmount), isBold: true),
              pw.SizedBox(height: 2),
              _buildThermalRow('Metode:', paymentLabel),
              if (paymentMethod == 'cash' || paymentMethod == 'cod') ...[
                _buildThermalRow('Tunai:', CurrencyFormatter.formatRupiah(cashGiven)),
                _buildThermalRow('Kembali:', CurrencyFormatter.formatRupiah(changeAmount), isBold: true),
              ],

              pw.SizedBox(height: 6),
              pw.Text('========================================', style: const pw.TextStyle(fontSize: 7)),
              pw.SizedBox(height: 4),

              // Footer
              pw.Text(
                'Terima Kasih Atas Kunjungan Anda!',
                style: pw.TextStyle(fontSize: 7.5, fontWeight: pw.FontWeight.bold),
                textAlign: pw.TextAlign.center,
              ),
              pw.SizedBox(height: 2),
              pw.Text(
                'Berbelanja lebih mudah lewat CicalengkaGO',
                style: const pw.TextStyle(fontSize: 6.5, color: PdfColors.grey600),
                textAlign: pw.TextAlign.center,
              ),
              pw.Text(
                'cicago.store',
                style: pw.TextStyle(fontSize: 6.5, color: PdfColors.grey600, fontWeight: pw.FontWeight.bold),
                textAlign: pw.TextAlign.center,
              ),
              pw.SizedBox(height: 8),
            ],
          );
        },
      ),
    );

    await Printing.layoutPdf(
      onLayout: (PdfPageFormat format) async => doc.save(),
      name: 'Struk-$orderCode.pdf',
    );
  }

  static pw.Widget _buildThermalRow(String label, String value, {bool isBold = false}) {
    return pw.Padding(
      padding: const pw.EdgeInsets.symmetric(vertical: 0.5),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Text(
            label,
            style: pw.TextStyle(
              fontSize: 7.5,
              fontWeight: isBold ? pw.FontWeight.bold : pw.FontWeight.normal,
            ),
          ),
          pw.Text(
            value,
            style: pw.TextStyle(
              fontSize: 7.5,
              fontWeight: isBold ? pw.FontWeight.bold : pw.FontWeight.normal,
            ),
          ),
        ],
      ),
    );
  }

  /// Format and share receipt via WhatsApp
  static Future<void> shareViaWhatsApp(Map<String, dynamic> receipt, {String? phone}) async {
    final storeName = receipt['store_name']?.toString() ?? 'Toko Mitra CicalengkaGO';
    final orderCode = receipt['order_code']?.toString() ?? 'POS-${DateTime.now().millisecondsSinceEpoch}';
    final createdAt = receipt['created_at']?.toString() ?? DateFormat('dd/MM/yyyy HH:mm').format(DateTime.now());
    final customerName = receipt['customer_name']?.toString() ?? 'Pelanggan Langsung';
    final paymentMethod = (receipt['payment_method']?.toString().toLowerCase() ?? 'cash');
    final paymentLabel = paymentMethod == 'cash' || paymentMethod == 'cod' ? 'Tunai' : (paymentMethod == 'qris' ? 'QRIS' : 'Transfer');

    final items = (receipt['items'] as List<dynamic>?) ?? [];
    final totalAmount = double.tryParse(receipt['total_amount']?.toString() ?? '0') ?? 0.0;
    final cashGiven = double.tryParse(receipt['cash_given']?.toString() ?? totalAmount.toString()) ?? totalAmount;
    final changeAmount = double.tryParse(receipt['change_amount']?.toString() ?? '0') ?? 0.0;

    final buffer = StringBuffer();
    buffer.writeln('🧾 *STRUK PEMBELIAN - ${storeName.toUpperCase()}*');
    buffer.writeln('No. Struk: `$orderCode`');
    buffer.writeln('Waktu: $createdAt');
    buffer.writeln('Pelanggan: $customerName');
    buffer.writeln('----------------------------------------');

    for (var it in items) {
      final name = it['product_name']?.toString() ?? 'Item';
      final qty = int.tryParse(it['quantity']?.toString() ?? '1') ?? 1;
      final price = double.tryParse(it['price']?.toString() ?? '0') ?? 0.0;
      buffer.writeln('• $name x$qty = ${CurrencyFormatter.formatRupiah(price * qty)}');
    }

    buffer.writeln('----------------------------------------');
    buffer.writeln('*Total: ${CurrencyFormatter.formatRupiah(totalAmount)}*');
    buffer.writeln('Metode Bayar: $paymentLabel');
    if (paymentMethod == 'cash' || paymentMethod == 'cod') {
      buffer.writeln('Tunai Diterima: ${CurrencyFormatter.formatRupiah(cashGiven)}');
      buffer.writeln('Kembalian: ${CurrencyFormatter.formatRupiah(changeAmount)}');
    }
    buffer.writeln('----------------------------------------');
    buffer.writeln('Terima kasih telah berbelanja di *$storeName*! 💚');
    buffer.writeln('_Didukung oleh CicalengkaGO | cicago.store_');

    final encodedText = Uri.encodeComponent(buffer.toString());
    String targetPhone = (phone ?? '').replaceAll(RegExp(r'[^0-9]'), '');
    if (targetPhone.startsWith('0')) {
      targetPhone = '62${targetPhone.substring(1)}';
    }

    final url = targetPhone.isNotEmpty
        ? Uri.parse('https://wa.me/$targetPhone?text=$encodedText')
        : Uri.parse('https://api.whatsapp.com/send?text=$encodedText');

    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    }
  }
}
