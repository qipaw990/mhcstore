import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/network/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';

class OrderTrackingScreen extends StatefulWidget {
  final String orderCode;
  const OrderTrackingScreen({super.key, required this.orderCode});

  @override
  State<OrderTrackingScreen> createState() => _OrderTrackingScreenState();
}

class _OrderTrackingScreenState extends State<OrderTrackingScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _orderData;

  @override
  void initState() {
    super.initState();
    _fetchTracking();
  }

  Future<void> _fetchTracking() async {
    final res = await ApiService.get('${ApiConstants.orders}/${widget.orderCode}');
    if (res['success'] == true && res['data'] != null) {
      setState(() {
        _orderData = res['data'];
        _isLoading = false;
      });
    } else {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(title: const Text('Pelacakan Pesanan')),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    final order = _orderData ?? {};
    final status = order['order_status'] ?? 'pending';

    // Map Center (Cicalengka area default)
    final LatLng customerPos = const LatLng(-6.9835, 107.8335);

    return Scaffold(
      appBar: AppBar(
        title: Text('Pesanan #${order['order_code'] ?? widget.orderCode}'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => _fetchTracking(),
          ),
        ],
      ),
      body: Column(
        children: [
          // Live Map View (FlutterMap OpenStreetMap)
          Expanded(
            child: FlutterMap(
              options: MapOptions(
                initialCenter: customerPos,
                initialZoom: 15.0,
              ),
              children: [
                TileLayer(
                  urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                  userAgentPackageName: 'com.cicalengkago.mobile',
                ),
                MarkerLayer(
                  markers: [
                    Marker(
                      point: customerPos,
                      width: 50,
                      height: 50,
                      child: Container(
                        decoration: BoxDecoration(
                          color: AppTheme.primaryRed,
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: AppTheme.primaryRed.withOpacity(0.4),
                              blurRadius: 10,
                            ),
                          ],
                        ),
                        child: const Icon(Icons.person_pin_circle, color: Colors.white, size: 30),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // Order Status Details Card
          Container(
            padding: const EdgeInsets.all(20),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              boxShadow: [
                BoxShadow(color: Colors.black12, blurRadius: 10, offset: Offset(0, -4)),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Status Pengantaran', style: TextStyle(fontSize: 12, color: Colors.grey)),
                        Text(
                          status.toString().toUpperCase(),
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.primaryRed,
                          ),
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: Colors.green[50],
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.delivery_dining, color: Colors.green, size: 18),
                          SizedBox(width: 4),
                          Text(
                            'Driver Menuju Lokasi',
                            style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 11),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const CircleAvatar(
                    backgroundColor: Color(0xFFF1F5F9),
                    child: Icon(Icons.motorcycle, color: AppTheme.darkSlate),
                  ),
                  title: Text(
                    order['delivery_man_name'] ?? 'Kurir CicalengkaGO',
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  subtitle: Text(order['delivery_man_phone'] ?? 'Driver Aktif'),
                  trailing: IconButton(
                    icon: const Icon(Icons.phone, color: Colors.green),
                    onPressed: () {},
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
