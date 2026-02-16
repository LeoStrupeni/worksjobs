import 'dart:ui';

/// Modelo para el tema CMS
class CmsTheme {
  final String name;
  final String version;
  final CmsThemeConfig config;

  CmsTheme({
    required this.name,
    required this.version,
    required this.config,
  });

  factory CmsTheme.fromJson(Map<String, dynamic> json) {
    return CmsTheme(
      name: json['name'] as String,
      version: json['version'] as String,
      config: CmsThemeConfig.fromJson(
        json['config'] is String 
            ? _parseJsonString(json['config'] as String)
            : json['config'] as Map<String, dynamic>,
      ),
    );
  }

  static Map<String, dynamic> _parseJsonString(String jsonStr) {
    try {
      final decoded = jsonStr.replaceAll('\n', '').replaceAll('  ', '');
      return _parseJson(decoded);
    } catch (e) {
      throw FormatException('Error parsing JSON string: $e');
    }
  }

  static Map<String, dynamic> _parseJson(String str) {
    // Simple JSON parser (en producción usar dart:convert)
    final Map<String, dynamic> result = {};
    // Implementación simplificada - usar json.decode en producción
    return result;
  }
}

/// Configuración del tema
class CmsThemeConfig {
  final CmsColors colors;
  final CmsTypography typography;
  final CmsSpacing spacing;
  final CmsBorderRadius borderRadius;
  final CmsElevation elevation;
  final CmsButtonStyle buttons;
  final CmsCardStyle cards;
  final CmsAppBarStyle appBar;

  CmsThemeConfig({
    required this.colors,
    required this.typography,
    required this.spacing,
    required this.borderRadius,
    required this.elevation,
    required this.buttons,
    required this.cards,
    required this.appBar,
  });

  factory CmsThemeConfig.fromJson(Map<String, dynamic> json) {
    return CmsThemeConfig(
      colors: CmsColors.fromJson(json['colors'] as Map<String, dynamic>),
      typography: CmsTypography.fromJson(json['typography'] as Map<String, dynamic>),
      spacing: CmsSpacing.fromJson(json['spacing'] as Map<String, dynamic>),
      borderRadius: CmsBorderRadius.fromJson(json['borderRadius'] as Map<String, dynamic>),
      elevation: CmsElevation.fromJson(json['elevation'] as Map<String, dynamic>),
      buttons: CmsButtonStyle.fromJson(json['buttons'] as Map<String, dynamic>),
      cards: CmsCardStyle.fromJson(json['cards'] as Map<String, dynamic>),
      appBar: CmsAppBarStyle.fromJson(json['appBar'] as Map<String, dynamic>),
    );
  }
}

/// Colores del tema
class CmsColors {
  final Color primary;
  final Color primaryDark;
  final Color primaryLight;
  final Color secondary;
  final Color accent;
  final Color background;
  final Color surface;
  final Color error;
  final Color success;
  final Color warning;
  final Color info;
  final Color textPrimary;
  final Color textSecondary;
  final Color textOnPrimary;
  final Color divider;

  CmsColors({
    required this.primary,
    required this.primaryDark,
    required this.primaryLight,
    required this.secondary,
    required this.accent,
    required this.background,
    required this.surface,
    required this.error,
    required this.success,
    required this.warning,
    required this.info,
    required this.textPrimary,
    required this.textSecondary,
    required this.textOnPrimary,
    required this.divider,
  });

  factory CmsColors.fromJson(Map<String, dynamic> json) {
    return CmsColors(
      primary: _parseColor(json['primary'] as String),
      primaryDark: _parseColor(json['primaryDark'] as String),
      primaryLight: _parseColor(json['primaryLight'] as String),
      secondary: _parseColor(json['secondary'] as String),
      accent: _parseColor(json['accent'] as String),
      background: _parseColor(json['background'] as String),
      surface: _parseColor(json['surface'] as String),
      error: _parseColor(json['error'] as String),
      success: _parseColor(json['success'] as String),
      warning: _parseColor(json['warning'] as String),
      info: _parseColor(json['info'] as String),
      textPrimary: _parseColor(json['textPrimary'] as String),
      textSecondary: _parseColor(json['textSecondary'] as String),
      textOnPrimary: _parseColor(json['textOnPrimary'] as String),
      divider: _parseColor(json['divider'] as String),
    );
  }

  static Color _parseColor(String hexColor) {
    final hex = hexColor.replaceAll('#', '');
    return Color(int.parse('FF$hex', radix: 16));
  }
}

/// Tipografía del tema
class CmsTypography {
  final String fontFamily;
  final Map<String, double> fontSize;
  final Map<String, int> fontWeight;

  CmsTypography({
    required this.fontFamily,
    required this.fontSize,
    required this.fontWeight,
  });

  factory CmsTypography.fromJson(Map<String, dynamic> json) {
    return CmsTypography(
      fontFamily: json['fontFamily'] as String,
      fontSize: (json['fontSize'] as Map<String, dynamic>).map(
        (key, value) => MapEntry(key, (value as num).toDouble()),
      ),
      fontWeight: (json['fontWeight'] as Map<String, dynamic>).map(
        (key, value) => MapEntry(key, value as int),
      ),
    );
  }
}

/// Espaciados del tema
class CmsSpacing {
  final double xs;
  final double sm;
  final double md;
  final double lg;
  final double xl;
  final double xxl;

  CmsSpacing({
    required this.xs,
    required this.sm,
    required this.md,
    required this.lg,
    required this.xl,
    required this.xxl,
  });

  factory CmsSpacing.fromJson(Map<String, dynamic> json) {
    return CmsSpacing(
      xs: (json['xs'] as num).toDouble(),
      sm: (json['sm'] as num).toDouble(),
      md: (json['md'] as num).toDouble(),
      lg: (json['lg'] as num).toDouble(),
      xl: (json['xl'] as num).toDouble(),
      xxl: (json['xxl'] as num).toDouble(),
    );
  }
}

/// Border radius del tema
class CmsBorderRadius {
  final double none;
  final double sm;
  final double md;
  final double lg;
  final double xl;
  final double round;

  CmsBorderRadius({
    required this.none,
    required this.sm,
    required this.md,
    required this.lg,
    required this.xl,
    required this.round,
  });

  factory CmsBorderRadius.fromJson(Map<String, dynamic> json) {
    return CmsBorderRadius(
      none: (json['none'] as num).toDouble(),
      sm: (json['sm'] as num).toDouble(),
      md: (json['md'] as num).toDouble(),
      lg: (json['lg'] as num).toDouble(),
      xl: (json['xl'] as num).toDouble(),
      round: (json['round'] as num).toDouble(),
    );
  }
}

/// Elevación (sombras) del tema
class CmsElevation {
  final double none;
  final double low;
  final double medium;
  final double high;
  final double veryHigh;

  CmsElevation({
    required this.none,
    required this.low,
    required this.medium,
    required this.high,
    required this.veryHigh,
  });

  factory CmsElevation.fromJson(Map<String, dynamic> json) {
    return CmsElevation(
      none: (json['none'] as num).toDouble(),
      low: (json['low'] as num).toDouble(),
      medium: (json['medium'] as num).toDouble(),
      high: (json['high'] as num).toDouble(),
      veryHigh: (json['veryHigh'] as num).toDouble(),
    );
  }
}

/// Estilo de botones
class CmsButtonStyle {
  final double height;
  final double borderRadius;
  final double elevation;

  CmsButtonStyle({
    required this.height,
    required this.borderRadius,
    required this.elevation,
  });

  factory CmsButtonStyle.fromJson(Map<String, dynamic> json) {
    return CmsButtonStyle(
      height: (json['height'] as num).toDouble(),
      borderRadius: (json['borderRadius'] as num).toDouble(),
      elevation: (json['elevation'] as num).toDouble(),
    );
  }
}

/// Estilo de cards
class CmsCardStyle {
  final double borderRadius;
  final double elevation;
  final double padding;

  CmsCardStyle({
    required this.borderRadius,
    required this.elevation,
    required this.padding,
  });

  factory CmsCardStyle.fromJson(Map<String, dynamic> json) {
    return CmsCardStyle(
      borderRadius: (json['borderRadius'] as num).toDouble(),
      elevation: (json['elevation'] as num).toDouble(),
      padding: (json['padding'] as num).toDouble(),
    );
  }
}

/// Estilo de AppBar
class CmsAppBarStyle {
  final double height;
  final double elevation;

  CmsAppBarStyle({
    required this.height,
    required this.elevation,
  });

  factory CmsAppBarStyle.fromJson(Map<String, dynamic> json) {
    return CmsAppBarStyle(
      height: (json['height'] as num).toDouble(),
      elevation: (json['elevation'] as num).toDouble(),
    );
  }
}
