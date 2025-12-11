import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../theme/app_theme.dart';
import '../services/app_state.dart';
import '../services/api_service.dart';
import '../widgets/glass_card.dart';
import '../widgets/status_pill.dart';

class EnrollmentScreen extends StatefulWidget {
  const EnrollmentScreen({super.key});

  @override
  State<EnrollmentScreen> createState() => _EnrollmentScreenState();
}

class _EnrollmentScreenState extends State<EnrollmentScreen> {
  final _nameController = TextEditingController();
  final _picker = ImagePicker();
  File? _selectedImage;
  bool _isUploading = false;
  Map<String, dynamic>? _lastResponse;

  @override
  void initState() {
    super.initState();
    _loadEnrollments();
  }

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _loadEnrollments() async {
    final state = context.read<AppState>();
    final enrollments = await ApiService.getEnrollments(state.enrollmentsUrl);
    state.setEnrollments(enrollments);
  }

  Future<void> _pickImage(ImageSource source) async {
    final xFile = await _picker.pickImage(source: source, imageQuality: 90);
    if (xFile != null) {
      setState(() {
        _selectedImage = File(xFile.path);
      });
    }
  }

  Future<void> _enroll() async {
    if (_selectedImage == null) {
      _showSnackBar('Pilih gambar terlebih dahulu');
      return;
    }
    if (_nameController.text.trim().isEmpty) {
      _showSnackBar('Isi nama untuk enrollment');
      return;
    }

    setState(() => _isUploading = true);

    final state = context.read<AppState>();
    final result = await ApiService.enroll(
      apiUrl: state.enrollUrl,
      name: _nameController.text.trim(),
      imageFile: _selectedImage!,
    );

    setState(() {
      _isUploading = false;
      _lastResponse = result;
    });

    if (result['error'] != true) {
      _showSnackBar('Enrollment berhasil!');
      _nameController.clear();
      setState(() => _selectedImage = null);
      _loadEnrollments();
    } else {
      _showSnackBar('Gagal: ${result['message']}');
    }
  }

  void _showSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: AppTheme.panelColor,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Enrollment'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.home),
            onPressed: () => Navigator.pop(context),
          ),
        ],
      ),
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
                _buildEnrollForm(),
                const SizedBox(height: 16),
                _buildEnrollmentList(state),
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
            child: const Text(
              'ENROLLMENT CONSOLE',
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
            'Kelola Enrollment Wajah',
            style: Theme.of(context).textTheme.headlineLarge,
          ),
          const SizedBox(height: 4),
          Text(
            'Tambah wajah baru dan lihat daftar enrollment yang tersimpan.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              StatusPill(
                label: _isUploading ? 'Uploading…' : 'Siap upload',
                tone: _isUploading ? StatusTone.accent : StatusTone.muted,
              ),
              StatusPill(
                label: 'Enrollments: ${state.enrollments.length}',
                tone: StatusTone.success,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildEnrollForm() {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildBadge('Tambah Enrollment'),
          const SizedBox(height: 8),
          Text(
            'Upload foto + nama',
            style: Theme.of(context).textTheme.headlineMedium,
          ),
          const SizedBox(height: 4),
          Text(
            'Gunakan foto dengan wajah jelas. Model: ArcFace 512-d.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 16),
          
          // Image preview
          if (_selectedImage != null) ...[
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: Image.file(
                _selectedImage!,
                height: 200,
                width: double.infinity,
                fit: BoxFit.cover,
              ),
            ),
            const SizedBox(height: 12),
          ],
          
          // Image picker buttons
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => _pickImage(ImageSource.camera),
                  icon: const Icon(Icons.camera_alt),
                  label: const Text('Kamera'),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => _pickImage(ImageSource.gallery),
                  icon: const Icon(Icons.photo_library),
                  label: const Text('Galeri'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          
          // Name field
          const Text(
            'Nama',
            style: TextStyle(
              color: AppTheme.textColor,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 6),
          TextField(
            controller: _nameController,
            decoration: const InputDecoration(
              hintText: 'misal: Alice',
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Nama ini muncul ketika terdeteksi.',
            style: Theme.of(context).textTheme.labelSmall,
          ),
          const SizedBox(height: 16),
          
          // Action buttons
          Row(
            children: [
              ElevatedButton.icon(
                onPressed: _isUploading ? null : _enroll,
                icon: _isUploading 
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      )
                    : const Icon(Icons.save),
                label: Text(_isUploading ? 'Menyimpan...' : 'Simpan Enrollment'),
              ),
              const SizedBox(width: 10),
              OutlinedButton.icon(
                onPressed: _loadEnrollments,
                icon: const Icon(Icons.refresh),
                label: const Text('Refresh'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildEnrollmentList(AppState state) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildBadge('Daftar Enrollment'),
          const SizedBox(height: 8),
          Text(
            'Data tersimpan',
            style: Theme.of(context).textTheme.headlineMedium,
          ),
          const SizedBox(height: 4),
          Text(
            'Diambil dari database backend.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: state.enrollments.isEmpty
                ? [_buildTag('Memuat…')]
                : state.enrollments.map((e) => _buildTag(e['name'] ?? 'Unknown')).toList(),
          ),
        ],
      ),
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
            'Hasil upload akan tampil di sini.',
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
              _lastResponse != null
                  ? _formatJson(_lastResponse!)
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

  String _formatJson(Map<String, dynamic> json) {
    final buffer = StringBuffer('{\n');
    json.forEach((key, value) {
      buffer.writeln('  "$key": $value,');
    });
    buffer.write('}');
    return buffer.toString();
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
