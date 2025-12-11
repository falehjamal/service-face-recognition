import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  // Colors from the web version
  static const Color bgColor = Color(0xFF0B1221);
  static const Color cardColor = Color(0x14FFFFFF);
  static const Color strokeColor = Color(0x1FFFFFFF);
  static const Color textColor = Color(0xFFE5ECFF);
  static const Color mutedColor = Color(0xFFB7C2D6);
  static const Color accentColor = Color(0xFFF97316);
  static const Color successColor = Color(0xFF22C55E);
  static const Color dangerColor = Color(0xFFF43F5E);
  static const Color panelColor = Color(0x990F172A);

  static ThemeData get darkTheme {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      scaffoldBackgroundColor: bgColor,
      colorScheme: const ColorScheme.dark(
        primary: accentColor,
        secondary: successColor,
        surface: cardColor,
        error: dangerColor,
      ),
      textTheme: GoogleFonts.spaceGroteskTextTheme(
        ThemeData.dark().textTheme.copyWith(
          headlineLarge: const TextStyle(
            fontSize: 34,
            fontWeight: FontWeight.bold,
            color: textColor,
            letterSpacing: -0.4,
          ),
          headlineMedium: const TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.bold,
            color: textColor,
          ),
          bodyLarge: const TextStyle(
            fontSize: 16,
            color: textColor,
          ),
          bodyMedium: const TextStyle(
            fontSize: 14,
            color: mutedColor,
          ),
          labelSmall: const TextStyle(
            fontSize: 12,
            color: mutedColor,
          ),
        ),
      ),
      cardTheme: CardThemeData(
        color: cardColor,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: strokeColor),
        ),
        elevation: 0,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: accentColor,
          foregroundColor: bgColor,
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 15,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: mutedColor,
          side: const BorderSide(color: strokeColor),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: const Color(0x0DFFFFFF),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: strokeColor),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0x24FFFFFF)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0x99F97316)),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        labelStyle: const TextStyle(color: mutedColor),
        hintStyle: const TextStyle(color: mutedColor),
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          fontSize: 20,
          fontWeight: FontWeight.bold,
          color: textColor,
        ),
      ),
    );
  }

  // Gradient decorations
  static BoxDecoration get gradientBackground {
    return const BoxDecoration(
      gradient: RadialGradient(
        center: Alignment(-0.64, -0.6),
        radius: 0.5,
        colors: [Color(0x29F97316), Colors.transparent],
      ),
    );
  }

  static BoxDecoration get glassCard {
    return BoxDecoration(
      color: cardColor,
      borderRadius: BorderRadius.circular(16),
      border: Border.all(color: strokeColor),
      boxShadow: [
        BoxShadow(
          color: Color(0x59000000),
          blurRadius: 60,
          offset: const Offset(0, 22),
        ),
      ],
    );
  }
}
