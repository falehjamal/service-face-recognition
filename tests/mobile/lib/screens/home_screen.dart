import 'dart:async';
import 'package:flutter/material.dart';
import 'package:camera/camera.dart';
import 'package:provider/provider.dart';

import '../main.dart';
import '../theme/app_theme.dart';
import '../services/app_state.dart';
import '../services/api_service.dart';
import '../widgets/bounding_box_painter.dart';
import '../widgets/status_pill.dart';
import '../widgets/glass_card.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> with WidgetsBindingObserver {
  CameraController? _cameraController;
  Timer? _detectTimer;
  bool _isProcessing = false;
  Map<String, dynamic>? _detectionResult;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _initCamera();
    _loadEnrollments();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _stopDetection();
    _cameraController?.dispose();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (_cameraController == null || !_cameraController!.value.isInitialized) {
      return;
    }
    if (state == AppLifecycleState.inactive) {
      _cameraController?.dispose();
    } else if (state == AppLifecycleState.resumed) {
      _initCamera();
    }
  }

  Future<void> _initCamera() async {
    if (cameras.isEmpty) {
      context.read<AppState>().setCameraReady(false);
      return;
    }

    // Use front camera if available
    final camera = cameras.firstWhere(
      (c) => c.lensDirection == CameraLensDirection.front,
      orElse: () => cameras.first,
    );

    _cameraController = CameraController(
      camera,
      ResolutionPreset.medium,
      enableAudio: false,
      imageFormatGroup: ImageFormatGroup.jpeg,
    );

    try {
      await _cameraController!.initialize();
      if (mounted) {
        context.read<AppState>().setCameraReady(true);
        setState(() {});
      }
    } catch (e) {
      context.read<AppState>().setCameraReady(false);
    }
  }

  Future<void> _loadEnrollments() async {
    final state = context.read<AppState>();
    final enrollments = await ApiService.getEnrollments(state.enrollmentsUrl);
    state.setEnrollments(enrollments);
  }

  void _startDetection() {
    if (_detectTimer != null) return;
    
    context.read<AppState>().setDetecting(true);
    _detectTimer = Timer.periodic(const Duration(milliseconds: 1500), (_) {
      _captureAndIdentify();
    });
  }

  void _stopDetection() {
    _detectTimer?.cancel();
    _detectTimer = null;
    context.read<AppState>().setDetecting(false);
    setState(() {
      _detectionResult = null;
    });
  }

  Future<void> _captureAndIdentify() async {
    if (_isProcessing || _cameraController == null || !_cameraController!.value.isInitialized) {
      return;
    }

    _isProcessing = true;
    
    try {
      final xFile = await _cameraController!.takePicture();
      final bytes = await xFile.readAsBytes();
      
      final state = context.read<AppState>();
      final result = await ApiService.identify(
        apiUrl: state.identifyUrl,
        imageBytes: bytes,
        threshold: state.threshold,
      );
      
      if (mounted) {
        setState(() {
          _detectionResult = result;
        });
        state.setLastResult(result);
      }
    } catch (e) {
      // Silently handle errors during detection
    } finally {
      _isProcessing = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    
    return Scaffold(
      body: Container(
        decoration: AppTheme.gradientBackground,
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildHeader(state),
                const SizedBox(height: 16),
                _buildEnrollmentInfo(state),
                const SizedBox(height: 16),
                _buildCameraSection(state),
                const SizedBox(height: 16),
                _buildLogSection(),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildHeader(AppState state) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: const Color(0x14FFFFFF),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              'PROTOTYPE CONSOLE',
              style: TextStyle(
                color: AppTheme.mutedColor,
                fontSize: 13,
                fontWeight: FontWeight.w600,
                letterSpacing: 0.4,
              ),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Realtime Face Recognition',
            style: Theme.of(context).textTheme.headlineLarge,
          ),
          const SizedBox(height: 4),
          Text(
            'Enroll foto sekali, lalu nyalakan deteksi live via kamera.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 12),
          _buildApiUrlField(state),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              StatusPill(
                label: state.isCameraReady ? 'Camera: aktif' : 'Camera: initializing…',
                tone: state.isCameraReady ? StatusTone.success : StatusTone.muted,
              ),
              StatusPill(
                label: state.isDetecting ? 'Live detect: berjalan' : 'Live detect: standby',
                tone: state.isDetecting ? StatusTone.accent : StatusTone.muted,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildApiUrlField(AppState state) {
    return Row(
      children: [
        Text(
          'API base URL',
          style: TextStyle(
            color: AppTheme.textColor,
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: TextFormField(
            initialValue: state.apiBaseUrl,
            decoration: const InputDecoration(
              isDense: true,
              contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            ),
            onChanged: state.setApiBaseUrl,
          ),
        ),
      ],
    );
  }

  Widget _buildEnrollmentInfo(AppState state) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildBadge('Info'),
          const SizedBox(height: 8),
          Text(
            'Identifikasi realtime',
            style: Theme.of(context).textTheme.headlineMedium,
          ),
          const SizedBox(height: 4),
          Text(
            'Gunakan kamera untuk mengenali wajah berdasarkan data enrollment.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: state.enrollments.isEmpty
                ? [_buildTag('Belum ada enrollment')]
                : state.enrollments.map((e) => _buildTag(e['name'] ?? 'Unknown')).toList(),
          ),
          const SizedBox(height: 12),
          GestureDetector(
            onTap: () => Navigator.pushNamed(context, '/enrollment').then((_) => _loadEnrollments()),
            child: Text(
              'Kelola enrollment →',
              style: TextStyle(
                color: AppTheme.accentColor,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCameraSection(AppState state) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildBadge('Live Recognize'),
          const SizedBox(height: 8),
          Text(
            'Pratinjau kamera + identifikasi',
            style: Theme.of(context).textTheme.headlineMedium,
          ),
          const SizedBox(height: 4),
          Text(
            'Kamera menyala di panel bawah; jalankan deteksi untuk menampilkan nama & skor.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 12),
          _buildCameraPreview(),
          const SizedBox(height: 12),
          Row(
            children: [
              ElevatedButton.icon(
                onPressed: state.isDetecting ? null : _startDetection,
                icon: const Icon(Icons.play_arrow),
                label: const Text('Mulai deteksi'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF0EA5E9),
                ),
              ),
              const SizedBox(width: 10),
              OutlinedButton(
                onPressed: state.isDetecting ? _stopDetection : null,
                child: const Text('Stop'),
              ),
            ],
          ),
          const SizedBox(height: 12),
          _buildThresholdSlider(state),
          const SizedBox(height: 8),
          Text(
            'Jika kamera belum tampil, pastikan izin sudah diberikan.',
            style: Theme.of(context).textTheme.labelSmall,
          ),
        ],
      ),
    );
  }

  Widget _buildCameraPreview() {
    if (_cameraController == null || !_cameraController!.value.isInitialized) {
      return Container(
        height: 300,
        decoration: BoxDecoration(
          color: const Color(0xFF111111),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0x24FFFFFF)),
        ),
        child: const Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              CircularProgressIndicator(color: AppTheme.accentColor),
              SizedBox(height: 12),
              Text('Initializing camera...', style: TextStyle(color: AppTheme.mutedColor)),
            ],
          ),
        ),
      );
    }

    return ClipRRect(
      borderRadius: BorderRadius.circular(14),
      child: AspectRatio(
        aspectRatio: 4 / 3,
        child: Stack(
          fit: StackFit.expand,
          children: [
            CameraPreview(_cameraController!),
            if (_detectionResult != null)
              CustomPaint(
                painter: BoundingBoxPainter(
                  result: _detectionResult!,
                  previewSize: Size(
                    _cameraController!.value.previewSize!.height,
                    _cameraController!.value.previewSize!.width,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildThresholdSlider(AppState state) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Threshold: ${state.threshold.toStringAsFixed(2)}',
          style: const TextStyle(
            color: AppTheme.textColor,
            fontWeight: FontWeight.w700,
          ),
        ),
        Slider(
          value: state.threshold,
          min: 0.1,
          max: 1.0,
          divisions: 18,
          activeColor: AppTheme.accentColor,
          onChanged: state.setThreshold,
        ),
        Text(
          'Gunakan 0.30-0.40 untuk ArcFace; lebih kecil = lebih ketat.',
          style: Theme.of(context).textTheme.labelSmall,
        ),
      ],
    );
  }

  Widget _buildLogSection() {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildBadge('Log'),
          const SizedBox(height: 8),
          Text(
            'Respons',
            style: Theme.of(context).textTheme.headlineMedium,
          ),
          const SizedBox(height: 4),
          Text(
            'Semua respons dari server akan tampil di sini.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFF0F172A),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0x14FFFFFF)),
            ),
            child: Text(
              _detectionResult != null
                  ? const JsonEncoder.withIndent('  ').convert(_detectionResult)
                  : '{ "result": "menunggu request" }',
              style: const TextStyle(
                fontFamily: 'monospace',
                fontSize: 13,
                color: Color(0xFFE2E8F0),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBadge(String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: const Color(0x0FFFFFFF),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        text.toUpperCase(),
        style: const TextStyle(
          color: Color(0xFFFFE4C7),
          fontSize: 12,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.3,
        ),
      ),
    );
  }

  Widget _buildTag(String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: const Color(0x14FFFFFF),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppTheme.strokeColor),
      ),
      child: Text(
        text,
        style: const TextStyle(
          color: AppTheme.mutedColor,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class JsonEncoder {
  final String? indent;
  const JsonEncoder.withIndent(this.indent);
  
  String convert(Object? object) {
    if (object == null) return 'null';
    try {
      return const JsonEncoder.withIndent('  ')._encode(object, 0);
    } catch (e) {
      return object.toString();
    }
  }
  
  String _encode(Object? value, int depth) {
    if (value == null) return 'null';
    if (value is bool || value is num) return value.toString();
    if (value is String) return '"$value"';
    if (value is List) {
      if (value.isEmpty) return '[]';
      final items = value.map((e) => '${indent! * (depth + 1)}${_encode(e, depth + 1)}').join(',\n');
      return '[\n$items\n${indent! * depth}]';
    }
    if (value is Map) {
      if (value.isEmpty) return '{}';
      final items = value.entries.map((e) => '${indent! * (depth + 1)}"${e.key}": ${_encode(e.value, depth + 1)}').join(',\n');
      return '{\n$items\n${indent! * depth}}';
    }
    return value.toString();
  }
}
