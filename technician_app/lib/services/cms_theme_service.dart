import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/cms_theme.dart';

/// Servicio para obtener el tema desde el CMS
class CmsThemeService {
  /// Obtener el tema activo desde la API
  static Future<CmsTheme?> getActiveTheme() async {
    try {
      // Endpoint del tema CMS
      final url = Uri.parse('${ApiConfig.baseUrl}/flutter/theme');
      
      // print('🎨 Obteniendo tema CMS desde: $url');

      final response = await http.get(
        url,
        headers: ApiConfig.getHeaders(),
      ).timeout(
        ApiConfig.connectionTimeout,
        onTimeout: () {
          throw Exception('Timeout al obtener tema del CMS');
        },
      );

      // print('📡 Response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        
        // Parsear el campo 'config' si viene como String
        if (jsonData['config'] is String) {
          jsonData['config'] = json.decode(jsonData['config']);
        }
        
        final theme = CmsTheme.fromJson(jsonData);
        // print('✅ Tema cargado: ${theme.name} v${theme.version}');
        
        return theme;
      } else if (response.statusCode == 404) {
        // print('⚠️ No hay tema activo en el CMS');
        return null;
      } else {
        print('❌ Error al obtener tema: ${response.statusCode}');
        return null;
      }
    } catch (e) {
      print('❌ Exception al obtener tema CMS: $e');
      return null;
    }
  }

  /// Verificar si hay actualización del tema
  static Future<bool> checkForUpdate(String currentVersion) async {
    try {
      final theme = await getActiveTheme();
      if (theme == null) return false;
      
      // Comparar versiones
      return theme.version != currentVersion;
    } catch (e) {
      // print('Error verificando actualización: $e');
      return false;
    }
  }
}
