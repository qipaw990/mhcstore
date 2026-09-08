import 'package:flutter/material.dart';

/// Stub untuk platform web — tidak pernah dipanggil karena
/// InAppPaymentScreen.build() memeriksa kIsWeb terlebih dahulu
/// dan menampilkan halaman konfirmasi web alih-alih memanggil ini.
Widget buildNativePaymentScreen({
  required BuildContext context,
  required String paymentUrl,
  required String orderId,
  required double amount,
  required String title,
  required VoidCallback onPaymentComplete,
}) {
  // Tidak akan pernah sampai sini di platform web
  return const SizedBox.shrink();
}
