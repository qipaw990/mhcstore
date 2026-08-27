import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../controllers/merchant_controller.dart';

class ProductManagementScreen extends StatelessWidget {
  const ProductManagementScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final merchantCtrl = context.watch<MerchantController>();
    final products = merchantCtrl.products;

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(16.0),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Menu Makanan (${products.length})',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              ElevatedButton.icon(
                onPressed: () {},
                icon: const Icon(Icons.add, size: 18),
                label: const Text('Tambah Menu'),
              ),
            ],
          ),
        ),
        Expanded(
          child: products.isEmpty
              ? const Center(child: Text('Belum ada menu produk terdaftar'))
              : ListView.builder(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: products.length,
                  itemBuilder: (context, index) {
                    final product = products[index];
                    final productId = int.parse(product['id'].toString());
                    final price = double.tryParse(product['price']?.toString() ?? '0') ?? 0.0;
                    final isAvailable = (product['status']?.toString() == '1');

                    final imgUrl = product['image'] != null && product['image'].toString().startsWith('http')
                        ? product['image']
                        : '${ApiConstants.imageBaseUrl}/${product['image'] ?? ''}';

                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: ListTile(
                        leading: ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: CachedNetworkImage(
                            imageUrl: imgUrl,
                            width: 50,
                            height: 50,
                            fit: BoxFit.cover,
                            errorWidget: (_, __, ___) => const Icon(Icons.fastfood),
                          ),
                        ),
                        title: Text(
                          product['name'] ?? 'Menu',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                        ),
                        subtitle: Text(
                          CurrencyFormatter.formatRupiah(price),
                          style: const TextStyle(color: AppTheme.primaryRed, fontWeight: FontWeight.bold),
                        ),
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              isAvailable ? 'Tersedia' : 'Habis',
                              style: TextStyle(
                                fontSize: 11,
                                color: isAvailable ? Colors.green : Colors.red,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            Switch(
                              value: isAvailable,
                              activeColor: Colors.green,
                              onChanged: (val) {
                                merchantCtrl.toggleProductStock(productId, val);
                              },
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
        ),
      ],
    );
  }
}
