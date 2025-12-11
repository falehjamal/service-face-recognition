import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class BoundingBoxPainter extends CustomPainter {
  final Map<String, dynamic> result;
  final Size previewSize;

  BoundingBoxPainter({
    required this.result,
    required this.previewSize,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final bbox = result['bbox'];
    if (bbox == null) return;

    final left = bbox['left'];
    final top = bbox['top'];
    final right = bbox['right'];
    final bottom = bbox['bottom'];
    final detScore = bbox['det_score'];

    if (left == null || top == null || right == null || bottom == null) return;

    // Scale factors
    final scaleX = size.width / previewSize.width;
    final scaleY = size.height / previewSize.height;

    // Calculate scaled coordinates
    final scaledLeft = (left as num).toDouble() * scaleX;
    final scaledTop = (top as num).toDouble() * scaleY;
    final scaledWidth = ((right as num) - (left as num)).toDouble() * scaleX;
    final scaledHeight = ((bottom as num) - (top as num)).toDouble() * scaleY;

    // Determine if match
    final isMatch = result['match'] == true;
    final color = isMatch ? AppTheme.successColor : AppTheme.accentColor;

    // Draw bounding box
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;

    canvas.drawRect(
      Rect.fromLTWH(scaledLeft, scaledTop, scaledWidth, scaledHeight),
      paint,
    );

    // Draw label
    final name = isMatch ? (result['name'] ?? 'Unknown') : 'Not Found';
    final scoreText = detScore != null ? ' · ${(detScore as num).toStringAsFixed(2)}' : '';
    final labelText = '$name$scoreText';

    final textPainter = TextPainter(
      text: TextSpan(
        text: labelText,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 14,
          fontWeight: FontWeight.w600,
          fontFamily: 'Space Grotesk',
        ),
      ),
      textDirection: TextDirection.ltr,
    )..layout();

    final labelY = (scaledTop - 24).clamp(0.0, size.height - 22);
    final padding = 6.0;

    // Draw label background
    final bgPaint = Paint()
      ..color = const Color(0xD90F172A);

    canvas.drawRect(
      Rect.fromLTWH(
        scaledLeft,
        labelY,
        textPainter.width + padding * 2,
        22,
      ),
      bgPaint,
    );

    // Draw label text
    textPainter.paint(canvas, Offset(scaledLeft + padding, labelY + 3));
  }

  @override
  bool shouldRepaint(covariant BoundingBoxPainter oldDelegate) {
    return result != oldDelegate.result;
  }
}
