import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/product.dart';
import '../utils/debug_logger.dart';
import '../utils/network_helper.dart';
import 'auth_service.dart';

class ProductService {
  final AuthService _authService = AuthService();

  /// Buscar productos y servicios
  /// 
  /// [search] - Término de búsqueda (opcional)
  /// [tipo] - Filtro por tipo: 'P' (Producto), 'S' (Servicio), o null para ambos
  /// [limit] - Número máximo de resultados (default: 50)
  Future<Map<String, dynamic>> searchProductsAndServices({
    String? search,
    String? tipo,
    int limit = 50,
  }) async {
    await DebugLogger.instance.info(
      '🔍 Buscando productos/servicios...',
      category: 'PRODUCTS',
      data: {'search': search, 'tipo': tipo, 'limit': limit},
    );

    try {
      final token = await _authService.getToken();

      if (token == null) {
        await DebugLogger.instance.error(
          '❌ No hay token de autenticación',
          category: 'PRODUCTS',
        );
        return {
          'success': false,
          'errorCode': ApiErrorCode.NO_TOKEN,
          'message': ApiErrorCode.getMessage(ApiErrorCode.NO_TOKEN),
        };
      }

      // Construir URL con parámetros
      String url = '${ApiConfig.baseUrl}${ApiConfig.productsServicesEndpoint}';
      List<String> params = [];

      if (search != null && search.isNotEmpty) {
        params.add('search=${Uri.encodeComponent(search)}');
      }
      if (tipo != null && tipo.isNotEmpty) {
        params.add('tipo=$tipo');
      }
      params.add('limit=$limit');

      if (params.isNotEmpty) {
        url += '?${params.join('&')}';
      }

      // Usar NetworkHelper con retry
      final result = await NetworkHelper.getWithRetry(
        Uri.parse(url),
        headers: ApiConfig.getHeaders(token: token),
        maxRetries: 2,
        logCategory: 'PRODUCTS',
      );

      if (!result.success) {
        return {
          'success': false,
          'errorCode': result.errorCode,
          'message': result.userMessage,
        };
      }

      final response = result.data as http.Response;
      final data = jsonDecode(response.body);

      if (data['success'] == true) {
        final products = (data['data'] as List)
            .map((product) => Product.fromJson(product))
            .toList();

        await DebugLogger.instance.success(
          '✅ ${products.length} productos/servicios encontrados',
          category: 'PRODUCTS',
          data: {'count': products.length},
        );

        return {
          'success': true,
          'products': products,
          'count': products.length,
        };
      } else {
        await DebugLogger.instance.warning(
          '⚠️ API retornó success=false',
          category: 'PRODUCTS',
          data: {'message': data['message']},
        );
      }

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': data['message'] ?? 'Error al buscar productos',
      };
    } catch (e, stackTrace) {
      await DebugLogger.instance.error(
        '❌ Error al buscar productos/servicios: $e',
        category: 'PRODUCTS',
        data: {'stackTrace': stackTrace.toString()},
      );

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': 'Error al buscar productos: $e',
      };
    }
  }

  /// Buscar solo productos (tipo 'P')
  Future<Map<String, dynamic>> searchProducts({
    String? search,
    int limit = 50,
  }) {
    return searchProductsAndServices(
      search: search,
      tipo: 'P',
      limit: limit,
    );
  }

  /// Buscar solo servicios (tipo 'S')
  Future<Map<String, dynamic>> searchServices({
    String? search,
    int limit = 50,
  }) {
    return searchProductsAndServices(
      search: search,
      tipo: 'S',
      limit: limit,
    );
  }
}
