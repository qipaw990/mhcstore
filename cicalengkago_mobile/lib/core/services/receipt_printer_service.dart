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

    final storeName = (receipt['store_name'] ?? receipt['store']?['name'] ?? 'Toko Mitra CicalengkaGO').toString();
    final storeAddress = (receipt['store_address'] ?? receipt['store']?['address'] ?? '').toString();
    final storePhone = (receipt['store_phone'] ?? receipt['store']?['phone'] ?? '').toString();
    final orderCode = (receipt['order_code'] ?? '#${receipt['id'] ?? 'POS'}').toString();
    final createdAt = receipt['created_at'] != null
        ? receipt['created_at'].toString()
        : DateFormat('dd/MM/yyyy HH:mm').format(DateTime.now());
    final customerName = (receipt['customer_name'] ?? receipt['customer']?['name'] ?? receipt['user_name'] ?? 'Pelanggan Langsung').toString();
    final customerPhone = (receipt['customer_phone'] ?? receipt['customer']?['phone'] ?? '').toString();
    final paymentMethod = (receipt['payment_method']?.toString().toLowerCase() ?? 'cash');
    final paymentLabel = paymentMethod == 'cash' || paymentMethod == 'cod'
        ? 'TUNAI'
        : (paymentMethod == 'qris' ? 'QRIS' : (paymentMethod == 'wallet' ? 'CicalengkaPay' : 'TRANSFER'));

    final items = (receipt['items'] as List<dynamic>?) ?? [];

    // Calculate sum of items
    double itemsSum = 0.0;
    for (var it in items) {
      final qty = int.tryParse(it['quantity']?.toString() ?? it['qty']?.toString() ?? '1') ?? 1;
      final price = double.tryParse(it['price']?.toString() ?? '0') ?? 0.0;
      itemsSum += (price * qty);
    }

    final subtotal = double.tryParse(receipt['order_amount']?.toString() ?? receipt['subtotal']?.toString() ?? itemsSum.toString()) ?? itemsSum;
    final deliveryFee = double.tryParse(receipt['delivery_charge']?.toString() ?? receipt['delivery_fee']?.toString() ?? '0') ?? 0.0;
    final serviceFee = double.tryParse(receipt['service_fee']?.toString() ?? receipt['app_fee']?.toString() ?? '0') ?? 0.0;
    final discount = double.tryParse(receipt['discount_amount']?.toString() ?? receipt['coupon_discount_amount']?.toString() ?? '0') ?? 0.0;

    double computedTotal = subtotal + deliveryFee + serviceFee - discount;
    final totalAmount = double.tryParse(receipt['total_amount']?.toString() ?? computedTotal.toString()) ?? computedTotal;
    final cashGiven = double.tryParse(receipt['cash_given']?.toString() ?? totalAmount.toString()) ?? totalAmount;
    final changeAmount = double.tryParse(receipt['change_amount']?.toString() ?? '0') ?? (cashGiven > totalAmount ? cashGiven - totalAmount : 0.0);

    // Standard 58mm Thermal Roll: 58mm width (approx 164 pt), 4mm margins (approx 142 pt printable width)
    const rollWidth = 58 * PdfPageFormat.mm;
    const pageFormat = PdfPageFormat(rollWidth, double.infinity, marginAll: 4 * PdfPageFormat.mm);

    doc.addPage(
      pw.Page(
        pageFormat: pageFormat,
        build: (pw.Context context) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.center,
            children: [
              // CicalengkaGO Logo Header
              if (logoImage != null) ...[
                pw.Center(
                  child: pw.Image(logoImage, height: 32, fit: pw.BoxFit.contain),
                ),
                pw.SizedBox(height: 2),
              ],

              // Platform badge
              pw.Container(
                width: double.infinity,
                padding: const pw.EdgeInsets.symmetric(vertical: 2),
                decoration: pw.BoxDecoration(
                  color: PdfColors.black,
                  borderRadius: const pw.BorderRadius.all(pw.Radius.circular(2)),
                ),
                child: pw.Center(
                  child: pw.Text(
                    'CicalengkaGO Official Store',
                    style: pw.TextStyle(fontSize: 6.5, fontWeight: pw.FontWeight.bold, color: PdfColors.white),
                    textAlign: pw.TextAlign.center,
                  ),
                ),
              ),
              pw.SizedBox(height: 4),

              // Store Name
              pw.Text(
                storeName.toUpperCase(),
                style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 10.5),
                textAlign: pw.TextAlign.center,
              ),
              if (storeAddress.isNotEmpty) ...[
                pw.SizedBox(height: 1),
                pw.Text(
                  storeAddress,
                  style: const pw.TextStyle(fontSize: 6.8, color: PdfColors.grey700),
                  textAlign: pw.TextAlign.center,
                ),
              ],
              if (storePhone.isNotEmpty) ...[
                pw.SizedBox(height: 1),
                pw.Text(
                  'Telp: $storePhone',
                  style: const pw.TextStyle(fontSize: 6.8, color: PdfColors.grey700),
                  textAlign: pw.TextAlign.center,
                ),
              ],

              pw.SizedBox(height: 2),
              _buildDashedDivider(),

              // Transaction Meta Info
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Text('No: $orderCode', style: pw.TextStyle(fontSize: 7.5, fontWeight: pw.FontWeight.bold)),
                  pw.Text(createdAt, style: const pw.TextStyle(fontSize: 6.8, color: PdfColors.grey800)),
                ],
              ),
              pw.SizedBox(height: 1),
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Text('Kasir: Mitra Toko', style: const pw.TextStyle(fontSize: 6.8, color: PdfColors.grey800)),
                  pw.Expanded(
                    child: pw.Text(
                      'Plg: $customerName',
                      style: const pw.TextStyle(fontSize: 6.8, color: PdfColors.grey800),
                      textAlign: pw.TextAlign.right,
                      maxLines: 1,
                    ),
                  ),
                ],
              ),
              if (customerPhone.isNotEmpty && customerPhone != '-') ...[
                pw.SizedBox(height: 1),
                pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                  children: [
                    pw.Text('Telp Plg:', style: const pw.TextStyle(fontSize: 6.8, color: PdfColors.grey800)),
                    pw.Text(customerPhone, style: const pw.TextStyle(fontSize: 6.8, color: PdfColors.grey800)),
                  ],
                ),
              ],

              _buildDashedDivider(),
              pw.SizedBox(height: 1),

              // Items Header
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Text('ITEM', style: pw.TextStyle(fontSize: 7, fontWeight: pw.FontWeight.bold, color: PdfColors.grey800)),
                  pw.Text('TOTAL', style: pw.TextStyle(fontSize: 7, fontWeight: pw.FontWeight.bold, color: PdfColors.grey800)),
                ],
              ),
              pw.SizedBox(height: 2),
              pw.Divider(thickness: 0.5, color: PdfColors.grey400, height: 1),
              pw.SizedBox(height: 3),

              // Items List
              ...items.map((it) {
                final name = (it['product_name'] ?? it['name'] ?? 'Item').toString();
                final qty = int.tryParse(it['quantity']?.toString() ?? it['qty']?.toString() ?? '1') ?? 1;
                final price = double.tryParse(it['price']?.toString() ?? '0') ?? 0.0;
                final lineTotal = price * qty;
                final varName = (it['variation_name'] ?? '').toString();
                final addonsText = (it['addons_text'] ?? '').toString();

                return pw.Padding(
                  padding: const pw.EdgeInsets.only(bottom: 3),
                  child: pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.start,
                    children: [
                      pw.Text(
                        name,
                        style: pw.TextStyle(fontSize: 7.8, fontWeight: pw.FontWeight.bold),
                        maxLines: 2,
                      ),
                      if (varName.isNotEmpty)
                        pw.Text('  * Varian: $varName', style: const pw.TextStyle(fontSize: 6.2, color: PdfColors.grey700)),
                      if (addonsText.isNotEmpty)
                        pw.Text('  * Topping: $addonsText', style: const pw.TextStyle(fontSize: 6.2, color: PdfColors.grey700)),
                      pw.Row(
                        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                        children: [
                          pw.Text(
                            '$qty x ${CurrencyFormatter.formatRupiah(price)}',
                            style: const pw.TextStyle(fontSize: 7.2, color: PdfColors.grey800),
                          ),
                          pw.Text(
                            CurrencyFormatter.formatRupiah(lineTotal),
                            style: pw.TextStyle(fontSize: 7.5, fontWeight: pw.FontWeight.bold),
                          ),
                        ],
                      ),
                    ],
                  ),
                );
              }),

              _buildDashedDivider(),

              // Financial Breakdown
              _buildThermalRow('Subtotal:', CurrencyFormatter.formatRupiah(subtotal)),
              if (deliveryFee > 0)
                _buildThermalRow('Ongkir Pengantaran:', CurrencyFormatter.formatRupiah(deliveryFee)),
              if (serviceFee > 0)
                _buildThermalRow('Biaya Layanan:', CurrencyFormatter.formatRupiah(serviceFee)),
              if (discount > 0)
                _buildThermalRow('Diskon:', '-${CurrencyFormatter.formatRupiah(discount)}', valueColor: PdfColors.red700),

              pw.SizedBox(height: 2),
              pw.Divider(thickness: 0.8, color: PdfColors.black, height: 1),
              pw.SizedBox(height: 2),

              _buildThermalRow('TOTAL:', CurrencyFormatter.formatRupiah(totalAmount), isBold: true, fontSize: 8.5),
              pw.SizedBox(height: 2),
              _buildThermalRow('Metode Bayar:', paymentLabel),

              if (paymentMethod == 'cash' || paymentMethod == 'cod') ...[
                _buildThermalRow('Tunai Diterima:', CurrencyFormatter.formatRupiah(cashGiven)),
                _buildThermalRow('Kembalian:', CurrencyFormatter.formatRupiah(changeAmount), isBold: true),
              ],

              _buildDashedDivider(),
              pw.SizedBox(height: 2),

              // Footer Notes
              pw.Text(
                'Terima Kasih Atas Kunjungan Anda!',
                style: pw.TextStyle(fontSize: 7.5, fontWeight: pw.FontWeight.bold),
                textAlign: pw.TextAlign.center,
              ),
              pw.SizedBox(height: 2),
              pw.Text(
                'Simpan struk ini sebagai bukti pembayaran sah',
                style: const pw.TextStyle(fontSize: 6.2, color: PdfColors.grey700),
                textAlign: pw.TextAlign.center,
              ),
              pw.Text(
                'Pesan lebih mudah & cepat di CicalengkaGO',
                style: const pw.TextStyle(fontSize: 6.2, color: PdfColors.grey700),
                textAlign: pw.TextAlign.center,
              ),
              pw.SizedBox(height: 1),
              pw.Text(
                'cicago.store',
                style: pw.TextStyle(fontSize: 6.8, color: PdfColors.black, fontWeight: pw.FontWeight.bold),
                textAlign: pw.TextAlign.center,
              ),
              pw.SizedBox(height: 6),
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

  /// Custom clean dashed divider that doesn't wrap or cause formatting bugs
  static pw.Widget _buildDashedDivider({double thickness = 0.6, PdfColor color = PdfColors.grey700}) {
    return pw.Container(
      margin: const pw.EdgeInsets.symmetric(vertical: 3),
      child: pw.LayoutBuilder(
        builder: (context, constraints) {
          final boxWidth = constraints?.maxWidth ?? 140;
          const dashWidth = 3.0;
          const dashSpace = 2.0;
          final dashCount = (boxWidth / (dashWidth + dashSpace)).floor();
          return pw.Row(
            mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
            children: List.generate(dashCount, (_) {
              return pw.SizedBox(
                width: dashWidth,
                height: thickness,
                child: pw.DecoratedBox(
                  decoration: pw.BoxDecoration(color: color),
                ),
              );
            }),
          );
        },
      ),
    );
  }

  static pw.Widget _buildThermalRow(
    String label,
    String value, {
    bool isBold = false,
    double fontSize = 7.2,
    PdfColor? valueColor,
  }) {
    return pw.Padding(
      padding: const pw.EdgeInsets.symmetric(vertical: 0.8),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Text(
            label,
            style: pw.TextStyle(
              fontSize: fontSize,
              fontWeight: isBold ? pw.FontWeight.bold : pw.FontWeight.normal,
              color: PdfColors.black,
            ),
          ),
          pw.Text(
            value,
            style: pw.TextStyle(
              fontSize: fontSize,
              fontWeight: isBold ? pw.FontWeight.bold : pw.FontWeight.normal,
              color: valueColor ?? PdfColors.black,
            ),
          ),
        ],
      ),
    );
  }

  /// Format and share receipt via WhatsApp
  static Future<void> shareViaWhatsApp(Map<String, dynamic> receipt, {String? phone}) async {
    final storeName = (receipt['store_name'] ?? receipt['store']?['name'] ?? 'Toko Mitra CicalengkaGO').toString();
    final orderCode = (receipt['order_code'] ?? '#${receipt['id'] ?? 'POS'}').toString();
    final createdAt = receipt['created_at'] != null
        ? receipt['created_at'].toString()
        : DateFormat('dd/MM/yyyy HH:mm').format(DateTime.now());
    final customerName = (receipt['customer_name'] ?? receipt['customer']?['name'] ?? receipt['user_name'] ?? 'Pelanggan Langsung').toString();
    final paymentMethod = (receipt['payment_method']?.toString().toLowerCase() ?? 'cash');
    final paymentLabel = paymentMethod == 'cash' || paymentMethod == 'cod'
        ? 'Tunai'
        : (paymentMethod == 'qris' ? 'QRIS' : (paymentMethod == 'wallet' ? 'CicalengkaPay' : 'Transfer'));

    final items = (receipt['items'] as List<dynamic>?) ?? [];

    double itemsSum = 0.0;
    for (var it in items) {
      final qty = int.tryParse(it['quantity']?.toString() ?? it['qty']?.toString() ?? '1') ?? 1;
      final price = double.tryParse(it['price']?.toString() ?? '0') ?? 0.0;
      itemsSum += (price * qty);
    }

    final subtotal = double.tryParse(receipt['order_amount']?.toString() ?? receipt['subtotal']?.toString() ?? itemsSum.toString()) ?? itemsSum;
    final deliveryFee = double.tryParse(receipt['delivery_charge']?.toString() ?? receipt['delivery_fee']?.toString() ?? '0') ?? 0.0;
    final serviceFee = double.tryParse(receipt['service_fee']?.toString() ?? receipt['app_fee']?.toString() ?? '0') ?? 0.0;
    final discount = double.tryParse(receipt['discount_amount']?.toString() ?? receipt['coupon_discount_amount']?.toString() ?? '0') ?? 0.0;

    double computedTotal = subtotal + deliveryFee + serviceFee - discount;
    final totalAmount = double.tryParse(receipt['total_amount']?.toString() ?? computedTotal.toString()) ?? computedTotal;
    final cashGiven = double.tryParse(receipt['cash_given']?.toString() ?? totalAmount.toString()) ?? totalAmount;
    final changeAmount = double.tryParse(receipt['change_amount']?.toString() ?? '0') ?? (cashGiven > totalAmount ? cashGiven - totalAmount : 0.0);

    final buffer = StringBuffer();
    buffer.writeln('🧾 *STRUK PEMBELIAN - ${storeName.toUpperCase()}*');
    buffer.writeln('No. Struk: `$orderCode`');
    buffer.writeln('Waktu: $createdAt');
    buffer.writeln('Pelanggan: $customerName');
    buffer.writeln('────────────────────────');

    for (var it in items) {
      final name = (it['product_name'] ?? it['name'] ?? 'Item').toString();
      final qty = int.tryParse(it['quantity']?.toString() ?? it['qty']?.toString() ?? '1') ?? 1;
      final price = double.tryParse(it['price']?.toString() ?? '0') ?? 0.0;
      buffer.writeln('• $name x$qty = ${CurrencyFormatter.formatRupiah(price * qty)}');
    }

    buffer.writeln('────────────────────────');
    buffer.writeln('Subtotal: ${CurrencyFormatter.formatRupiah(subtotal)}');
    if (deliveryFee > 0) {
      buffer.writeln('Ongkir: ${CurrencyFormatter.formatRupiah(deliveryFee)}');
    }
    if (serviceFee > 0) {
      buffer.writeln('Biaya Layanan: ${CurrencyFormatter.formatRupiah(serviceFee)}');
    }
    if (discount > 0) {
      buffer.writeln('Diskon: -${CurrencyFormatter.formatRupiah(discount)}');
    }
    buffer.writeln('*Total: ${CurrencyFormatter.formatRupiah(totalAmount)}*');
    buffer.writeln('Metode Bayar: $paymentLabel');
    if (paymentMethod == 'cash' || paymentMethod == 'cod') {
      buffer.writeln('Tunai Diterima: ${CurrencyFormatter.formatRupiah(cashGiven)}');
      buffer.writeln('Kembalian: ${CurrencyFormatter.formatRupiah(changeAmount)}');
    }
    buffer.writeln('────────────────────────');
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
