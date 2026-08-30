import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:permission_handler/permission_handler.dart';
import '../theme/app_theme.dart';

class BarcodeScannerModal extends StatefulWidget {
  final String title;
  const BarcodeScannerModal({
    super.key,
    this.title = 'Arahkan Kamera ke Barcode',
  });

  static Future<String?> scan(BuildContext context, {String title = 'Arahkan Kamera ke Barcode'}) {
    return showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => BarcodeScannerModal(title: title),
    );
  }

  @override
  State<BarcodeScannerModal> createState() => _BarcodeScannerModalState();
}

class _BarcodeScannerModalState extends State<BarcodeScannerModal> with SingleTickerProviderStateMixin, WidgetsBindingObserver {
  MobileScannerController? _scannerController;
  final TextEditingController _manualInputCtrl = TextEditingController();
  bool _isManualMode = false;
  bool _hasScanned = false;
  bool _hasCameraPermission = false;
  bool _isCheckingPermission = true;

  late AnimationController _animController;
  late Animation<double> _laserAnim;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);

    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1800),
    )..repeat(reverse: true);

    _laserAnim = Tween<double>(begin: 0.05, end: 0.95).animate(
      CurvedAnimation(parent: _animController, curve: Curves.easeInOut),
    );

    _checkAndRequestCameraPermission();
  }

  Future<void> _checkAndRequestCameraPermission() async {
    setState(() => _isCheckingPermission = true);
    var status = await Permission.camera.status;

    if (!status.isGranted) {
      status = await Permission.camera.request();
    }

    if (status.isGranted) {
      _initScanner();
      if (mounted) {
        setState(() {
          _hasCameraPermission = true;
          _isCheckingPermission = false;
        });
      }
    } else {
      if (mounted) {
        setState(() {
          _hasCameraPermission = false;
          _isCheckingPermission = false;
        });
      }
    }
  }

  void _initScanner() {
    _scannerController?.dispose();
    _scannerController = MobileScannerController(
      detectionSpeed: DetectionSpeed.noDuplicates,
      facing: CameraFacing.back,
      torchEnabled: false,
    );
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (_scannerController == null || !_hasCameraPermission) return;
    if (state == AppLifecycleState.inactive || state == AppLifecycleState.paused) {
      _scannerController?.stop();
    } else if (state == AppLifecycleState.resumed) {
      _scannerController?.start();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _animController.dispose();
    _scannerController?.dispose();
    _manualInputCtrl.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) {
    if (_hasScanned) return;
    final List<Barcode> barcodes = capture.barcodes;
    for (final barcode in barcodes) {
      final code = barcode.rawValue?.trim() ?? '';
      if (code.isNotEmpty) {
        _hasScanned = true;
        Navigator.pop(context, code);
        break;
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final mediaHeight = MediaQuery.of(context).size.height;

    return Container(
      height: mediaHeight * 0.85,
      decoration: const BoxDecoration(
        color: Color(0xFF0F172A),
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      child: ClipRRect(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
        child: Column(
          children: [
            // Top Drag Handle & Header
            Container(
              padding: const EdgeInsets.fromLTRB(20, 14, 16, 12),
              color: const Color(0xFF1E293B),
              child: Column(
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: const Color(0xFF64748B),
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(6),
                            decoration: BoxDecoration(
                              color: AppTheme.primaryRed.withValues(alpha: 0.2),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.qr_code_scanner_rounded, color: AppTheme.primaryRed, size: 20),
                          ),
                          const SizedBox(width: 10),
                          Text(
                            widget.title,
                            style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: Colors.white),
                          ),
                        ],
                      ),
                      IconButton(
                        icon: const Icon(Icons.close_rounded, color: Colors.white70, size: 22),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            // Scanner Body / Manual Input / Permission View
            Expanded(
              child: _isCheckingPermission
                  ? const Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          CircularProgressIndicator(color: AppTheme.primaryRed),
                          SizedBox(height: 14),
                          Text('Menyiapkan kamera scanner...', style: TextStyle(color: Colors.white70, fontSize: 13)),
                        ],
                      ),
                    )
                  : (!_hasCameraPermission && !_isManualMode)
                      ? Container(
                          color: const Color(0xFF0F172A),
                          padding: const EdgeInsets.all(24),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Container(
                                padding: const EdgeInsets.all(16),
                                decoration: BoxDecoration(
                                  color: Colors.red.withValues(alpha: 0.15),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.no_photography_rounded, color: Color(0xFFEF4444), size: 48),
                              ),
                              const SizedBox(height: 16),
                              const Text(
                                'Izin Kamera Dibutuhkan',
                                style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                              ),
                              const SizedBox(height: 8),
                              const Text(
                                'Aplikasi memerlukan izin akses kamera untuk membaca barcode produk secara langsung.',
                                style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12.5),
                                textAlign: TextAlign.center,
                              ),
                              const SizedBox(height: 20),
                              SizedBox(
                                width: double.infinity,
                                child: ElevatedButton.icon(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: AppTheme.primaryRed,
                                    foregroundColor: Colors.white,
                                    padding: const EdgeInsets.symmetric(vertical: 12),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                  ),
                                  icon: const Icon(Icons.camera_alt_rounded, size: 18),
                                  label: const Text('Beri Izin Akses Kamera', style: TextStyle(fontWeight: FontWeight.bold)),
                                  onPressed: () async {
                                    final res = await Permission.camera.request();
                                    if (res.isGranted) {
                                      _checkAndRequestCameraPermission();
                                    } else if (res.isPermanentlyDenied) {
                                      openAppSettings();
                                    }
                                  },
                                ),
                              ),
                              const SizedBox(height: 10),
                              TextButton.icon(
                                style: TextButton.styleFrom(foregroundColor: const Color(0xFF60A5FA)),
                                icon: const Icon(Icons.keyboard_rounded, size: 16),
                                label: const Text('Gunakan Ketik Manual'),
                                onPressed: () => setState(() => _isManualMode = true),
                              ),
                            ],
                          ),
                        )
                      : _isManualMode
                          ? Container(
                              color: const Color(0xFF0F172A),
                              padding: const EdgeInsets.all(24),
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.keyboard_alt_outlined, color: Color(0xFF60A5FA), size: 48),
                                  const SizedBox(height: 12),
                                  const Text(
                                    'Ketik Kode Barcode / SKU',
                                    style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                                  ),
                                  const SizedBox(height: 6),
                                  const Text(
                                    'Masukkan nomor barcode produk secara manual jika label barcode rusak atau tidak terbaca.',
                                    style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                                    textAlign: TextAlign.center,
                                  ),
                                  const SizedBox(height: 20),
                                  TextField(
                                    controller: _manualInputCtrl,
                                    autofocus: true,
                                    style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.bold, letterSpacing: 1),
                                    decoration: InputDecoration(
                                      hintText: 'Contoh: 8991234567890',
                                      hintStyle: const TextStyle(color: Color(0xFF64748B), fontSize: 13),
                                      prefixIcon: const Icon(Icons.qr_code_2_rounded, color: Color(0xFF60A5FA)),
                                      filled: true,
                                      fillColor: const Color(0xFF1E293B),
                                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFF334155))),
                                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppTheme.primaryRed, width: 1.5)),
                                    ),
                                    onSubmitted: (val) {
                                      final text = val.trim();
                                      if (text.isNotEmpty) {
                                        Navigator.pop(context, text);
                                      }
                                    },
                                  ),
                                  const SizedBox(height: 16),
                                  SizedBox(
                                    width: double.infinity,
                                    child: ElevatedButton(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: AppTheme.primaryRed,
                                        foregroundColor: Colors.white,
                                        padding: const EdgeInsets.symmetric(vertical: 14),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                      ),
                                      onPressed: () {
                                        final text = _manualInputCtrl.text.trim();
                                        if (text.isNotEmpty) {
                                          Navigator.pop(context, text);
                                        }
                                      },
                                      child: const Text('Gunakan Kode Barcode', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                    ),
                                  ),
                                ],
                              ),
                            )
                          : Stack(
                              children: [
                                if (_scannerController != null)
                                  MobileScanner(
                                    controller: _scannerController!,
                                    onDetect: _onDetect,
                                    errorBuilder: (context, error) {
                                      return Center(
                                        child: Column(
                                          mainAxisAlignment: MainAxisAlignment.center,
                                          children: [
                                            const Icon(Icons.videocam_off_outlined, color: Colors.white54, size: 48),
                                            const SizedBox(height: 10),
                                            Text(
                                              'Kamera tidak dapat diakses: ${error.errorCode.name}',
                                              style: const TextStyle(color: Colors.white70, fontSize: 12),
                                            ),
                                            const SizedBox(height: 14),
                                            ElevatedButton(
                                              style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primaryRed),
                                              onPressed: () => setState(() => _isManualMode = true),
                                              child: const Text('Gunakan Input Manual'),
                                            ),
                                          ],
                                        ),
                                      );
                                    },
                                  ),

                                // Viewfinder Overlay Window with Glowing Corners
                                Center(
                                  child: Container(
                                    width: 270,
                                    height: 200,
                                    decoration: BoxDecoration(
                                      border: Border.all(color: Colors.white.withValues(alpha: 0.35), width: 1.5),
                                      borderRadius: BorderRadius.circular(18),
                                    ),
                                    child: Stack(
                                      children: [
                                        // Animated Laser Line
                                        AnimatedBuilder(
                                          animation: _laserAnim,
                                          builder: (context, child) {
                                            return Positioned(
                                              top: 200 * _laserAnim.value,
                                              left: 10,
                                              right: 10,
                                              child: Container(
                                                height: 2.5,
                                                decoration: BoxDecoration(
                                                  color: AppTheme.primaryRed,
                                                  boxShadow: [
                                                    BoxShadow(
                                                      color: AppTheme.primaryRed.withValues(alpha: 0.85),
                                                      blurRadius: 8,
                                                      spreadRadius: 2,
                                                    ),
                                                  ],
                                                ),
                                              ),
                                            );
                                          },
                                        ),

                                        // Top-Left Corner
                                        Positioned(
                                          top: -1,
                                          left: -1,
                                          child: Container(
                                            width: 26,
                                            height: 26,
                                            decoration: const BoxDecoration(
                                              border: Border(
                                                top: BorderSide(color: AppTheme.primaryRed, width: 4),
                                                left: BorderSide(color: AppTheme.primaryRed, width: 4),
                                              ),
                                              borderRadius: BorderRadius.only(topLeft: Radius.circular(16)),
                                            ),
                                          ),
                                        ),

                                        // Top-Right Corner
                                        Positioned(
                                          top: -1,
                                          right: -1,
                                          child: Container(
                                            width: 26,
                                            height: 26,
                                            decoration: const BoxDecoration(
                                              border: Border(
                                                top: BorderSide(color: AppTheme.primaryRed, width: 4),
                                                right: BorderSide(color: AppTheme.primaryRed, width: 4),
                                              ),
                                              borderRadius: BorderRadius.only(topRight: Radius.circular(16)),
                                            ),
                                          ),
                                        ),

                                        // Bottom-Left Corner
                                        Positioned(
                                          bottom: -1,
                                          left: -1,
                                          child: Container(
                                            width: 26,
                                            height: 26,
                                            decoration: const BoxDecoration(
                                              border: Border(
                                                bottom: BorderSide(color: AppTheme.primaryRed, width: 4),
                                                left: BorderSide(color: AppTheme.primaryRed, width: 4),
                                              ),
                                              borderRadius: BorderRadius.only(bottomLeft: Radius.circular(16)),
                                            ),
                                          ),
                                        ),

                                        // Bottom-Right Corner
                                        Positioned(
                                          bottom: -1,
                                          right: -1,
                                          child: Container(
                                            width: 26,
                                            height: 26,
                                            decoration: const BoxDecoration(
                                              border: Border(
                                                bottom: BorderSide(color: AppTheme.primaryRed, width: 4),
                                                right: BorderSide(color: AppTheme.primaryRed, width: 4),
                                              ),
                                              borderRadius: BorderRadius.only(bottomRight: Radius.circular(16)),
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),

                                // Flashlight & Camera Switch Floating Controls
                                if (_scannerController != null)
                                  Positioned(
                                    top: 16,
                                    right: 16,
                                    child: Column(
                                      children: [
                                        IconButton(
                                          style: IconButton.styleFrom(
                                            backgroundColor: Colors.black.withValues(alpha: 0.55),
                                          ),
                                          icon: ValueListenableBuilder<MobileScannerState>(
                                            valueListenable: _scannerController!,
                                            builder: (context, state, child) {
                                              final isOn = state.torchState == TorchState.on;
                                              return Icon(
                                                isOn ? Icons.flash_on_rounded : Icons.flash_off_rounded,
                                                color: isOn ? const Color(0xFFFBBF24) : Colors.white,
                                                size: 20,
                                              );
                                            },
                                          ),
                                          onPressed: () => _scannerController?.toggleTorch(),
                                        ),
                                        const SizedBox(height: 8),
                                        IconButton(
                                          style: IconButton.styleFrom(
                                            backgroundColor: Colors.black.withValues(alpha: 0.55),
                                          ),
                                          icon: const Icon(Icons.cameraswitch_rounded, color: Colors.white, size: 20),
                                          onPressed: () => _scannerController?.switchCamera(),
                                        ),
                                      ],
                                    ),
                                  ),
                              ],
                            ),
            ),

            // Bottom Mode Switcher
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
              color: const Color(0xFF1E293B),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    _isManualMode ? 'Mode Manual Aktif' : 'Posisikan barcode dalam kotak',
                    style: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                  ),
                  TextButton.icon(
                    style: TextButton.styleFrom(
                      foregroundColor: const Color(0xFF60A5FA),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    ),
                    icon: Icon(_isManualMode ? Icons.camera_alt_rounded : Icons.keyboard_rounded, size: 16),
                    label: Text(
                      _isManualMode ? 'Buka Kamera' : 'Ketik Manual',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                    ),
                    onPressed: () {
                      setState(() {
                        _isManualMode = !_isManualMode;
                        if (!_isManualMode && !_hasCameraPermission) {
                          _checkAndRequestCameraPermission();
                        }
                      });
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
