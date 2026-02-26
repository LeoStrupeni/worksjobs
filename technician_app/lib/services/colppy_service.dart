import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class ColppyService {
  static const String _sessionKeyPrefix = 'colppy_session_';
  static const String _sessionExpiryKeyPrefix = 'colppy_session_expiry_';

  /// Obtener clave de sesión de Colppy
  /// 
  /// Retorna un mapa con:
  /// {
  ///   'success': bool,
  ///   'claveSesion': String?,
  ///   'usuario': String?,
  ///   'idEmpresa': String?,
  ///   'message': String? (si hay error)
  /// }
  static Future<Map<String, dynamic>> obtenerSesion(String token) async {
    try {
      // Verificar si tenemos una sesión válida en caché local
      final sesionCacheada = await _obtenerSesionCacheada();
      if (sesionCacheada != null) {
        return {
          'success': true,
          'claveSesion': sesionCacheada['claveSesion'],
          'usuario': sesionCacheada['usuario'],
          'idEmpresa': sesionCacheada['idEmpresa'],
          'cached': true
        };
      }

      // Solicitar nueva sesión al backend
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.colppySessionEndpoint}'),
        headers: ApiConfig.getHeaders(token: token),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () => throw TimeoutException('Timeout obteniendo sesión'),
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        final claveSesion = data['data']['claveSesion'];
        final usuario = data['data']['usuario'];
        final idEmpresa = data['data']['idEmpresa'];

        // Guardar en caché local para futuras solicitudes
        await _guardarSesionEnCache(claveSesion, usuario, idEmpresa);

        return {
          'success': true,
          'claveSesion': claveSesion,
          'usuario': usuario,
          'idEmpresa': idEmpresa,
          'cached': false
        };
      }

      return {
        'success': false,
        'message': data['message'] ?? 'Error obteniendo sesión de Colppy'
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Error: ${e.toString()}'
      };
    }
  }

  /// Listar clientes de Colppy
  /// 
  /// Parámetros:
  /// - token: Token de autenticación
  /// - start: Indice de inicio (default: 0)
  /// - limit: Límite de resultados (default: 100)
  /// - filters: Lista de filtros en formato [{'field': 'Activo', 'op': '=', 'value': '1'}]
  /// - order: Lista de órdenes en formato [{'field': 'NombreFantasia', 'dir': 'asc'}]
  static Future<Map<String, dynamic>> listarClientes(
    String token, {
    int start = 0,
    int limit = 100,
    List<Map<String, dynamic>>? filters,
    List<Map<String, dynamic>>? order,
  }) async {
    try {
      String url = '${ApiConfig.baseUrl}${ApiConfig.colppyClientesEndpoint}'
          '?start=$start&limit=$limit';

      if (filters != null && filters.isNotEmpty) {
        url += '&filters=${Uri.encodeComponent(jsonEncode(filters))}';
      }

      if (order != null && order.isNotEmpty) {
        url += '&order=${Uri.encodeComponent(jsonEncode(order))}';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: ApiConfig.getHeaders(token: token),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () => throw TimeoutException('Timeout listando clientes'),
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'data': data['data'] ?? [],
          'metadata': data['metadata']
        };
      }

      return {
        'success': false,
        'message': data['message'] ?? 'Error listando clientes'
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Error: ${e.toString()}'
      };
    }
  }

  /// Obtener detalle de un cliente específico
  static Future<Map<String, dynamic>> obtenerCliente(
    String token,
    String idCliente,
  ) async {
    try {
      final response = await http.get(
        Uri.parse(
          '${ApiConfig.baseUrl}${ApiConfig.colppyClientesEndpoint}/$idCliente',
        ),
        headers: ApiConfig.getHeaders(token: token),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () => throw TimeoutException('Timeout obteniendo cliente'),
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'data': data['data']
        };
      }

      return {
        'success': false,
        'message': data['message'] ?? 'Error obteniendo cliente'
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Error: ${e.toString()}'
      };
    }
  }

  /// Hacer una llamada genérica a la API de Colppy
  static Future<Map<String, dynamic>> hacerLlamada(
    String token, {
    required String provision,
    required String operacion,
    Map<String, dynamic>? parameters,
  }) async {
    try {
      final Map<String, dynamic> body = {
        'provision': provision,
        'operacion': operacion,
      };

      if (parameters != null) {
        body['parameters'] = parameters;
      }

      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.colppyCallEndpoint}'),
        headers: ApiConfig.getHeaders(token: token),
        body: jsonEncode(body),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () => throw TimeoutException('Timeout en llamada a Colppy'),
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'data': data['data'] ?? [],
          'metadata': data['metadata']
        };
      }

      return {
        'success': false,
        'message': data['message'] ?? 'Error en llamada a Colppy'
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Error: ${e.toString()}'
      };
    }
  }

  /// Invalidar sesión actual
  static Future<Map<String, dynamic>> invalidarSesion(String token) async {
    try {
      final response = await http.post(
        Uri.parse(
          '${ApiConfig.baseUrl}${ApiConfig.colppyInvalidateSessionEndpoint}',
        ),
        headers: ApiConfig.getHeaders(token: token),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () => throw TimeoutException('Timeout invalidando sesión'),
      );

      // Limpiar caché local
      await _limpiarSesionEnCache();

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': data['success'] ?? true,
          'message': data['message'] ?? 'Sesión invalidada'
        };
      }

      return {
        'success': false,
        'message': 'Error invalidando sesión'
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Error: ${e.toString()}'
      };
    }
  }

  // ==================== Métodos privados para caché ====================

  /// Guardar sesión en caché local
  static Future<void> _guardarSesionEnCache(
    String claveSesion,
    String usuario,
    String idEmpresa,
  ) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final cacheKey = _generarCacheKey(usuario, idEmpresa);
      
      await Future.wait([
        prefs.setString(
          cacheKey,
          jsonEncode({
            'claveSesion': claveSesion,
            'usuario': usuario,
            'idEmpresa': idEmpresa,
          }),
        ),
        // Guardar timestamp de expiración (1 hora)
        prefs.setInt(
          '${_sessionExpiryKeyPrefix}$cacheKey',
          DateTime.now().add(const Duration(hours: 1)).millisecondsSinceEpoch,
        ),
      ]);
    } catch (e) {
      // Si hay error guardando, simplemente continuamos sin caché
      print('Error guardando sesión en caché: $e');
    }
  }

  /// Obtener sesión del caché local si es válida
  static Future<Map<String, dynamic>?> _obtenerSesionCacheada() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      
      // Buscar cualquier sesión válida en el caché
      final keys = prefs.getKeys();
      for (final key in keys) {
        if (key.startsWith(_sessionKeyPrefix)) {
          // Verficiar si no ha expirado
          final expiryKey = '${_sessionExpiryKeyPrefix}$key';
          final expiryTimestamp = prefs.getInt(expiryKey);
          
          if (expiryTimestamp != null &&
              expiryTimestamp > DateTime.now().millisecondsSinceEpoch) {
            // Sesión válida, decodificar y retornar
            final sessionJson = prefs.getString(key);
            if (sessionJson != null) {
              return jsonDecode(sessionJson) as Map<String, dynamic>;
            }
          }
        }
      }
      
      return null;
    } catch (e) {
      return null;
    }
  }

  /// Limpiar sesión del caché
  static Future<void> _limpiarSesionEnCache() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final keys = prefs.getKeys();
      
      final keysToDelete = keys
          .where((key) =>
              key.startsWith(_sessionKeyPrefix) ||
              key.startsWith(_sessionExpiryKeyPrefix))
          .toList();
      
      for (final key in keysToDelete) {
        await prefs.remove(key);
      }
    } catch (e) {
      print('Error limpiando sesión del caché: $e');
    }
  }

  /// Generar clave de caché
  static String _generarCacheKey(String usuario, String idEmpresa) {
    return '$_sessionKeyPrefix${usuario}_$idEmpresa';
  }
}

class TimeoutException implements Exception {
  final String message;

  TimeoutException(this.message);

  @override
  String toString() => message;
}
