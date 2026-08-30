import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../controllers/auth_controller.dart';
import 'register_merchant_screen.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;

  void _handleRegister() async {
    final name = _nameController.text.trim();
    final email = _emailController.text.trim();
    final phone = _phoneController.text.trim();
    final password = _passwordController.text.trim();

    if (name.isEmpty || email.isEmpty || phone.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Silakan lengkapi semua data pendaftaran')),
      );
      return;
    }

    final authController = context.read<AuthController>();
    final success = await authController.register(
      name: name,
      email: email,
      phone: phone,
      password: password,
    );

    if (mounted) {
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Row(
              children: [
                Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
                SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Pendaftaran akun berhasil! Silakan masuk.',
                    style: TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            backgroundColor: const Color(0xFF16A34A),
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            duration: const Duration(seconds: 2),
          ),
        );
        if (Navigator.canPop(context)) {
          Navigator.pop(context);
        }
      } else if (authController.errorMessage != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.error_outline_rounded, color: Colors.white, size: 20),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    authController.errorMessage!,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            backgroundColor: const Color(0xFFDC2626),
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final authController = context.watch<AuthController>();

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text('Buat Akun Pelanggan', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          physics: const BouncingScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 20.0, vertical: 16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Bergabung di CicalengkaGO 🚀',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 4),
              Text(
                'Nikmati kemudahan pesan makanan, belanja, dan pengantaran instan',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      fontSize: 11,
                      color: const Color(0xFF64748B),
                    ),
              ),
              const SizedBox(height: 16),

              TextField(
                controller: _nameController,
                style: const TextStyle(fontSize: 13),
                decoration: const InputDecoration(
                  labelText: 'Nama Lengkap',
                  isDense: true,
                  prefixIcon: Icon(Icons.person_outline, size: 20),
                ),
              ),
              const SizedBox(height: 12),

              TextField(
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                style: const TextStyle(fontSize: 13),
                decoration: const InputDecoration(
                  labelText: 'Alamat Email',
                  isDense: true,
                  prefixIcon: Icon(Icons.email_outlined, size: 20),
                ),
              ),
              const SizedBox(height: 12),

              TextField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                style: const TextStyle(fontSize: 13),
                decoration: const InputDecoration(
                  labelText: 'Nomor WhatsApp / HP',
                  isDense: true,
                  prefixIcon: Icon(Icons.phone_outlined, size: 20),
                ),
              ),
              const SizedBox(height: 12),

              TextField(
                controller: _passwordController,
                obscureText: _obscurePassword,
                style: const TextStyle(fontSize: 13),
                decoration: InputDecoration(
                  labelText: 'Kata Sandi (Minimal 6 karakter)',
                  isDense: true,
                  prefixIcon: const Icon(Icons.lock_outline, size: 20),
                  suffixIcon: IconButton(
                    icon: Icon(
                      _obscurePassword ? Icons.visibility_off : Icons.visibility,
                      size: 20,
                    ),
                    onPressed: () {
                      setState(() {
                        _obscurePassword = !_obscurePassword;
                      });
                    },
                  ),
                ),
              ),
              const SizedBox(height: 20),

              SizedBox(
                width: double.infinity,
                height: 46,
                child: ElevatedButton(
                  onPressed: authController.isLoading ? null : _handleRegister,
                  child: authController.isLoading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : const Text(
                          'DAFTAR PELANGGAN SEKARANG',
                          style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, letterSpacing: 0.5),
                        ),
                ),
              ),
              const SizedBox(height: 16),
              Center(
                child: TextButton.icon(
                  onPressed: () {
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const RegisterMerchantScreen(),
                      ),
                    );
                  },
                  icon: const Icon(Icons.storefront_rounded, size: 16, color: Color(0xFF2563EB)),
                  label: const Text(
                    'Ingin Buka Usaha? Daftar Sebagai Mitra Toko',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF2563EB)),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
