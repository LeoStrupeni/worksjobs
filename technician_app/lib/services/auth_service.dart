import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';
import '../models/user.dart';
import '../models/technician.dart';

class AuthService {
  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'user_data';
  static const String _savedEmailKey = 'saved_email';
  static const String _savedPasswordKey = 'saved_password';
  static const String _techniciansKey = 'technicians_list';
  static const String _productsKey = 'products_list';

  // Login
  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.loginEndpoint}'),
        headers: ApiConfig.getHeaders(),
        body: jsonEncode({
          'email': email,
          'password': password,
        }),
      );

      final data = jsonDecode(response.body);
      // print('🟢 Login response received');
      // print('🟢 Success: ${data['success']}');
      // print('🟢 Data keys: ${data['data']?.keys.toList()}');

      if (response.statusCode == 200 && data['success'] == true) {
        // Guardar token y datos del usuario
        await _saveToken(data['data']['token']);
        await _saveUser(data['data']['user']);
        
        // Guardar técnicos si vienen en la respuesta
        if (data['data']['technicians'] != null) {
          // print('🟢 Técnicos en respuesta: ${data['data']['technicians'].length}');
          await _saveTechnicians(data['data']['technicians']);
        } else {
          // print('🟢 NO hay técnicos en la respuesta');
        }
        
        // Guardar productos si vienen en la respuesta
        if (data['data']['products'] != null) {
          // print('🟢 Productos en respuesta: ${data['data']['products'].length}');
          // print('🟢 Primer producto: ${data['data']['products'].isNotEmpty ? data['data']['products'][0] : 'vacío'}');
          await _saveProducts(data['data']['products']);
        } else {
          // print('🟢 NO hay productos en la respuesta');
        }
        
        // DEBUG: Verificar permisos recibidos
        if (data['data']['user']['permissions'] != null) {
          // print('🔐 PERMISOS RECIBIDOS: ${data['data']['user']['permissions']}');
          final permissions = List<String>.from(data['data']['user']['permissions']);
          // print('🔐 Tiene create share: ${permissions.contains('create share')}');
          // print('🔐 Tiene create pdf: ${permissions.contains('create pdf')}');
        } else {
          // print('🔐 NO HAY PERMISOS EN LA RESPUESTA');
        }
        
        return {
          'success': true,
          'user': User.fromJson(data['data']['user']),
          'token': data['data']['token'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al iniciar sesión',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  // Logout
  Future<Map<String, dynamic>> logout() async {
    try {
      final token = await getToken();
      
      if (token != null) {
        await http.post(
          Uri.parse('${ApiConfig.baseUrl}${ApiConfig.logoutEndpoint}'),
          headers: ApiConfig.getHeaders(token: token),
        );
      }

      // Limpiar datos locales
      await clearSession();
      
      return {
        'success': true,
        'message': 'Sesión cerrada exitosamente',
      };
    } catch (e) {
      // Aunque falle la petición, limpiar localmente
      await clearSession();
      return {
        'success': true,
        'message': 'Sesión cerrada localmente',
      };
    }
  }

  // Guardar token
  Future<void> _saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  // Guardar usuario
  Future<void> _saveUser(Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, jsonEncode(user));
  }

  // Guardar técnicos
  Future<void> _saveTechnicians(List<dynamic> technicians) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_techniciansKey, jsonEncode(technicians));
  }

  // Obtener técnicos guardados
  Future<List<Technician>> getTechnicians() async {
    final prefs = await SharedPreferences.getInstance();
    final techniciansJson = prefs.getString(_techniciansKey);
    
    if (techniciansJson != null) {
      final List<dynamic> decoded = jsonDecode(techniciansJson);
      return decoded.map((json) => Technician.fromJson(json)).toList();
    }
    
    return [];
  }

  // Guardar productos
  // Guardar productos
  Future<void> _saveProducts(List<dynamic> products) async {
    // print('🟢 AuthService._saveProducts: Guardando ${products.length} productos');
    // if (products.isNotEmpty) {
    //   print('🟢 AuthService._saveProducts: Primer producto: ${products[0]}');
    // }
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_productsKey, jsonEncode(products));
    // print('🟢 AuthService._saveProducts: Productos guardados exitosamente');
  }

  // Obtener productos guardados
  Future<List<Map<String, dynamic>>> getProducts() async {
    // print('🟢 AuthService.getProducts: Obteniendo productos guardados...');
    final prefs = await SharedPreferences.getInstance();
    final productsJson = prefs.getString(_productsKey);
    // print('🟢 AuthService.getProducts: productsJson = ${productsJson != null ? 'existe' : 'null'}');
    
    if (productsJson != null) {
      final List<dynamic> decoded = jsonDecode(productsJson);
      // print('🟢 AuthService.getProducts: ${decoded.length} productos decodificados');
      // if (decoded.isNotEmpty) {
      //   print('🟢 AuthService.getProducts: Primer producto: $decoded[0]');
      // }
      return decoded.cast<Map<String, dynamic>>();
    }
    
    // print('🟢 AuthService.getProducts: Retornando lista vacía');
    return [];
  }

  // Obtener token
  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  // Obtener usuario guardado
  Future<User?> getSavedUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userJson = prefs.getString(_userKey);
    
    if (userJson != null) {
      return User.fromJson(jsonDecode(userJson));
    }
    
    return null;
  }

  // Verificar si está autenticado
  Future<bool> isAuthenticated() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  // Limpiar sesión
  Future<void> clearSession() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
    await prefs.remove(_techniciansKey);
    await prefs.remove(_productsKey);
  }

  // Obtener información del usuario actual desde la API
  Future<Map<String, dynamic>> getCurrentUser() async {
    try {
      final token = await getToken();
      
      if (token == null) {
        return {
          'success': false,
          'message': 'No hay token de autenticación',
        };
      }

      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.userEndpoint}'),
        headers: ApiConfig.getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        final userJson = jsonDecode(response.body);
        await _saveUser(userJson);
        
        return {
          'success': true,
          'user': userJson,  // Devolver JSON raw, no el objeto parseado
        };
      } else {
        return {
          'success': false,
          'message': 'Error al obtener datos del usuario',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  // Guardar credenciales
  Future<void> saveCredentials(String email, String password) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_savedEmailKey, email);
    await prefs.setString(_savedPasswordKey, password);
  }

  // Obtener credenciales guardadas
  Future<Map<String, String>?> getSavedCredentials() async {
    final prefs = await SharedPreferences.getInstance();
    final email = prefs.getString(_savedEmailKey);
    final password = prefs.getString(_savedPasswordKey);
    
    if (email != null && password != null) {
      return {
        'email': email,
        'password': password,
      };
    }
    
    return null;
  }

  // Limpiar credenciales guardadas
  Future<void> clearSavedCredentials() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_savedEmailKey);
    await prefs.remove(_savedPasswordKey);
  }
}
