import 'package:flutter/material.dart';
import '../../../core/utils/currency_formatter.dart';

class DriverEarningsScreen extends StatelessWidget {
  const DriverEarningsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Earnings Overview Banner
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF059669), Color(0xFF047857)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Pendapatan Hari Ini', style: TextStyle(color: Colors.white70, fontSize: 13)),
                const SizedBox(height: 4),
                Text(
                  CurrencyFormatter.formatRupiah(145000),
                  style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 12),
                const Row(
                  children: [
                    Icon(Icons.check_circle, color: Colors.white70, size: 16),
                    SizedBox(width: 4),
                    Text('Total 12 Trip Pengantaran Selesai', style: TextStyle(color: Colors.white, fontSize: 12)),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Rating Card
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Row(
                children: [
                  Container(
                    width: 50,
                    height: 50,
                    decoration: BoxDecoration(
                      color: Colors.amber[100],
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.star, color: Colors.amber, size: 30),
                  ),
                  const SizedBox(width: 14),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Rating Performa Driver', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                      Row(
                        children: const [
                          Text('4.9 ', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.amber)),
                          Text('/ 5.0 (98% Kepuasan Pelanggan)', style: TextStyle(fontSize: 12, color: Colors.grey)),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
