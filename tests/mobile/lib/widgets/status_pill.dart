import 'package:flutter/material.dart';

enum StatusTone { muted, accent, success, danger }

class StatusPill extends StatelessWidget {
  final String label;
  final StatusTone tone;

  const StatusPill({
    super.key,
    required this.label,
    this.tone = StatusTone.muted,
  });

  @override
  Widget build(BuildContext context) {
    final colors = _getColors();
    
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: colors.background,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: colors.border),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: colors.text,
          fontSize: 13,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  _Colors _getColors() {
    switch (tone) {
      case StatusTone.accent:
        return _Colors(
          background: const Color(0x24F97316),
          border: const Color(0x73F97316),
          text: const Color(0xFFFFD7B3),
        );
      case StatusTone.success:
        return _Colors(
          background: const Color(0x1F22C55E),
          border: const Color(0x7322C55E),
          text: const Color(0xFFC6F6D5),
        );
      case StatusTone.danger:
        return _Colors(
          background: const Color(0x1FF43F5E),
          border: const Color(0x73F43F5E),
          text: const Color(0xFFFFD2DB),
        );
      case StatusTone.muted:
      default:
        return _Colors(
          background: const Color(0x0AFFFFFF),
          border: const Color(0x1FFFFFFF),
          text: const Color(0xFFB7C2D6),
        );
    }
  }
}

class _Colors {
  final Color background;
  final Color border;
  final Color text;

  _Colors({
    required this.background,
    required this.border,
    required this.text,
  });
}
