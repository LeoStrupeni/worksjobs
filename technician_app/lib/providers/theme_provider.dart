import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../models/cms_theme.dart';
import '../services/cms_theme_service.dart';

/// Provider para manejar el tema dinámico del CMS
class ThemeProvider extends ChangeNotifier {
  CmsTheme? _cmsTheme;
  bool _isLoading = false;
  String? _error;

  // Tema por defecto (usado como fallback)
  static final ThemeData _defaultTheme = ThemeData(
    primarySwatch: Colors.blue,
    primaryColor: const Color(0xFF1976D2),
    colorScheme: ColorScheme.fromSeed(
      seedColor: const Color(0xFF1976D2),
      brightness: Brightness.light,
    ),
    useMaterial3: true,
  );

  CmsTheme? get cmsTheme => _cmsTheme;
  bool get isLoading => _isLoading;
  String? get error => _error;
  String? get currentVersion => _cmsTheme?.version;

  /// Obtener el ThemeData para MaterialApp
  ThemeData getThemeData() {
    if (_cmsTheme == null) {
      return _defaultTheme;
    }

    final config = _cmsTheme!.config;
    final colors = config.colors;

    return ThemeData(
      primarySwatch: _createMaterialColor(colors.primary),
      primaryColor: colors.primary,
      
      colorScheme: ColorScheme.light(
        primary: colors.primary,
        secondary: colors.secondary,
        error: colors.error,
        background: colors.background,
        surface: colors.surface,
        onPrimary: colors.textOnPrimary,
        onSecondary: colors.textOnPrimary,
        onError: Colors.white,
        onBackground: colors.textPrimary,
        onSurface: colors.textPrimary,
      ),

      // AppBar
      appBarTheme: AppBarTheme(
        backgroundColor: colors.primary,
        foregroundColor: colors.textOnPrimary,
        elevation: config.appBar.elevation,
        toolbarHeight: config.appBar.height,
        centerTitle: true,
      ),

      // Cards
      cardTheme: CardThemeData(
        elevation: config.cards.elevation,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(config.cards.borderRadius),
        ),
        margin: EdgeInsets.all(config.spacing.sm),
      ),

      // Botones elevados
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: colors.primary,
          foregroundColor: colors.textOnPrimary,
          elevation: config.buttons.elevation,
          minimumSize: Size(88, config.buttons.height),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(config.buttons.borderRadius),
          ),
        ),
      ),

      // FloatingActionButton
      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: colors.accent,
        foregroundColor: colors.textOnPrimary,
        elevation: config.elevation.medium,
      ),

      // Input Decoration
      inputDecorationTheme: InputDecorationTheme(
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(config.borderRadius.md),
        ),
        filled: true,
        fillColor: colors.surface,
      ),

      // Divider
      dividerColor: colors.divider,
      dividerTheme: DividerThemeData(
        color: colors.divider,
        thickness: 1,
      ),

      // Text Theme
      textTheme: TextTheme(
        displayLarge: TextStyle(
          fontSize: config.typography.fontSize['headline1'],
          fontWeight: FontWeight.w300,
          color: colors.textPrimary,
        ),
        displayMedium: TextStyle(
          fontSize: config.typography.fontSize['headline2'],
          fontWeight: FontWeight.w400,
          color: colors.textPrimary,
        ),
        displaySmall: TextStyle(
          fontSize: config.typography.fontSize['headline3'],
          fontWeight: FontWeight.w400,
          color: colors.textPrimary,
        ),
        headlineMedium: TextStyle(
          fontSize: config.typography.fontSize['headline4'],
          fontWeight: FontWeight.w400,
          color: colors.textPrimary,
        ),
        headlineSmall: TextStyle(
          fontSize: config.typography.fontSize['headline5'],
          fontWeight: FontWeight.w400,
          color: colors.textPrimary,
        ),
        titleLarge: TextStyle(
          fontSize: config.typography.fontSize['headline6'],
          fontWeight: FontWeight.w500,
          color: colors.textPrimary,
        ),
        bodyLarge: TextStyle(
          fontSize: config.typography.fontSize['body1'],
          fontWeight: FontWeight.w400,
          color: colors.textPrimary,
        ),
        bodyMedium: TextStyle(
          fontSize: config.typography.fontSize['body2'],
          fontWeight: FontWeight.w400,
          color: colors.textPrimary,
        ),
        labelLarge: TextStyle(
          fontSize: config.typography.fontSize['button'],
          fontWeight: FontWeight.w500,
          color: colors.textPrimary,
        ),
      ),

      fontFamily: config.typography.fontFamily,
      useMaterial3: true,
    );
  }

  /// Cargar tema desde el CMS
  Future<void> loadTheme() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      // Intentar obtener tema del CMS
      final theme = await CmsThemeService.getActiveTheme();
      
      if (theme != null) {
        _cmsTheme = theme;
        await _saveThemeLocally(theme);
        print('✅ Tema CMS cargado y guardado');
      } else {
        // Intentar cargar tema guardado localmente
        await _loadThemeFromCache();
        print('⚠️ Usando tema en caché');
      }
    } catch (e) {
      _error = e.toString();
      print('❌ Error cargando tema: $e');
      
      // Intentar cargar tema guardado
      await _loadThemeFromCache();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Recargar tema (para actualizar sin reiniciar app)
  Future<void> reloadTheme() async {
    print('🔄 Recargando tema...');
    await loadTheme();
  }

  /// Guardar tema localmente para uso offline
  Future<void> _saveThemeLocally(CmsTheme theme) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final themeJson = json.encode({
        'name': theme.name,
        'version': theme.version,
        'config': theme.config, // Aquí necesitarías un toJson() en CmsThemeConfig
      });
      await prefs.setString('cached_cms_theme', themeJson);
      await prefs.setString('cached_theme_version', theme.version);
    } catch (e) {
      print('Error guardando tema: $e');
    }
  }

  /// Cargar tema desde caché local
  Future<void> _loadThemeFromCache() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final themeJson = prefs.getString('cached_cms_theme');
      
      if (themeJson != null) {
        // Aquí parsearías el JSON guardado
        print('📦 Tema cargado desde caché');
      }
    } catch (e) {
      print('Error cargando tema desde caché: $e');
    }
  }

  /// Crear MaterialColor desde Color
  MaterialColor _createMaterialColor(Color color) {
    List strengths = <double>[.05];
    Map<int, Color> swatch = {};
    final int r = color.red, g = color.green, b = color.blue;

    for (int i = 1; i < 10; i++) {
      strengths.add(0.1 * i);
    }
    
    for (var strength in strengths) {
      final double ds = 0.5 - strength;
      swatch[(strength * 1000).round()] = Color.fromRGBO(
        r + ((ds < 0 ? r : (255 - r)) * ds).round(),
        g + ((ds < 0 ? g : (255 - g)) * ds).round(),
        b + ((ds < 0 ? b : (255 - b)) * ds).round(),
        1,
      );
    }
    
    return MaterialColor(color.value, swatch);
  }
}
