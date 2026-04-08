import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/budget.dart';
import '../models/client.dart';
import '../utils/debug_logger.dart';
import '../utils/network_helper.dart';
import 'auth_service.dart';

class BudgetService {
  final AuthService _authService = AuthService();

  /// Obtener lista de presupuestos
  /// 
  /// [page] - Página a obtener (default: 1)
  /// [limit] - Resultados por página (default: 20)
  Future<Map<String, dynamic>> getBudgets({
    int page = 1,
    int limit = 20,
  }) async {
    await DebugLogger.instance.info(
      '📋 Obteniendo presupuestos...',
      category: 'BUDGETS',
      data: {'page': page, 'limit': limit},
    );

    try {
      final token = await _authService.getToken();

      if (token == null) {
        await DebugLogger.instance.error(
          '❌ No hay token de autenticación',
          category: 'BUDGETS',
        );
        return {
          'success': false,
          'errorCode': ApiErrorCode.NO_TOKEN,
          'message': ApiErrorCode.getMessage(ApiErrorCode.NO_TOKEN),
        };
      }

      final url = '${ApiConfig.baseUrl}${ApiConfig.budgetsEndpoint}'
          '?page=$page&limit=$limit';

      final result = await NetworkHelper.getWithRetry(
        Uri.parse(url),
        headers: ApiConfig.getHeaders(token: token),
        maxRetries: 2,
        logCategory: 'BUDGETS',
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
        final budgets = (data['data'] as List)
            .map((budget) => Budget.fromJson(budget))
            .toList();

        await DebugLogger.instance.success(
          '✅ ${budgets.length} presupuestos obtenidos',
          category: 'BUDGETS',
          data: {'count': budgets.length, 'total': data['total']},
        );

        return {
          'success': true,
          'budgets': budgets,
          'total': data['total'] ?? 0,
          'page': data['page'] ?? page,
          'limit': data['limit'] ?? limit,
        };
      }

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': data['message'] ?? 'Error al obtener presupuestos',
      };
    } catch (e) {
      await DebugLogger.instance.error(
        '❌ Error al obtener presupuestos',
        category: 'BUDGETS',
      );

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': 'Error al obtener presupuestos: $e',
      };
    }
  }

  /// Obtener detalle de un presupuesto
  Future<Map<String, dynamic>> getBudgetDetail(int budgetId) async {
    await DebugLogger.instance.info(
      '📄 Obteniendo detalle del presupuesto #$budgetId...',
      category: 'BUDGETS',
    );

    try {
      final token = await _authService.getToken();

      if (token == null) {
        return {
          'success': false,
          'errorCode': ApiErrorCode.NO_TOKEN,
          'message': ApiErrorCode.getMessage(ApiErrorCode.NO_TOKEN),
        };
      }

      final url = '${ApiConfig.baseUrl}${ApiConfig.budgetsEndpoint}/$budgetId';

      final result = await NetworkHelper.getWithRetry(
        Uri.parse(url),
        headers: ApiConfig.getHeaders(token: token),
        maxRetries: 2,
        logCategory: 'BUDGETS',
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
        final budget = Budget.fromJson(data['data']);

        await DebugLogger.instance.success(
          '✅ Detalle del presupuesto obtenido',
          category: 'BUDGETS',
        );

        return {
          'success': true,
          'budget': budget,
        };
      }

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': data['message'] ?? 'Error al obtener detalle',
      };
    } catch (e) {
      await DebugLogger.instance.error(
        '❌ Error al obtener detalle del presupuesto',
        category: 'BUDGETS',
      );

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': 'Error al obtener detalle: $e',
      };
    }
  }

  /// Crear un nuevo presupuesto
  Future<Map<String, dynamic>> createBudget({
    required int clientId,
    required String fecha,
    required List<Map<String, dynamic>> items,
    String? observaciones,
  }) async {
    await DebugLogger.instance.info(
      '➕ Creando presupuesto...',
      category: 'BUDGETS',
      data: {
        'clientId': clientId,
        'fecha': fecha,
        'itemsCount': items.length,
      },
    );

    try {
      final token = await _authService.getToken();

      if (token == null) {
        return {
          'success': false,
          'errorCode': ApiErrorCode.NO_TOKEN,
          'message': ApiErrorCode.getMessage(ApiErrorCode.NO_TOKEN),
        };
      }

      final body = {
        'client_id': clientId,
        'fecha': fecha,
        'items': items,
        if (observaciones != null && observaciones.isNotEmpty)
          'observaciones': observaciones,
      };

      await DebugLogger.instance.info(
        '📤 Enviando datos del presupuesto...',
        category: 'BUDGETS',
        data: {'body': body},
      );

      final result = await NetworkHelper.postWithRetry(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.budgetsEndpoint}'),
        headers: ApiConfig.getHeaders(token: token),
        body: jsonEncode(body),
        maxRetries: 3, // Más reintentos para creación
        logCategory: 'BUDGETS',
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
        final budget = Budget.fromJson(data['data']);

        await DebugLogger.instance.success(
          '✅ Presupuesto creado exitosamente',
          category: 'BUDGETS',
          data: {
            'budgetId': budget.id,
            'nroFactura': budget.nroFactura,
          },
        );

        return {
          'success': true,
          'budget': budget,
          'message': data['message'] ?? 'Presupuesto creado exitosamente',
        };
      }

      await DebugLogger.instance.warning(
        '⚠️ API retornó success=false',
        category: 'BUDGETS',
        data: {'response': data},
      );

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': data['message'] ?? 'Error al crear presupuesto',
      };
    } catch (e) {
      await DebugLogger.instance.error(
        '❌ Error al crear presupuesto',
        category: 'BUDGETS',
      );

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': 'Error al crear presupuesto: $e',
      };
    }
  }

  /// Crear un nuevo cliente con datos de AFIP
  Future<Map<String, dynamic>> createClientWithAFIP({
    required String cuit,
  }) async {
    await DebugLogger.instance.info(
      '👤 Creando cliente con CUIT $cuit...',
      category: 'BUDGETS',
    );

    try {
      final token = await _authService.getToken();

      if (token == null) {
        return {
          'success': false,
          'errorCode': ApiErrorCode.NO_TOKEN,
          'message': ApiErrorCode.getMessage(ApiErrorCode.NO_TOKEN),
        };
      }

      final body = {'cuit': cuit};

      final result = await NetworkHelper.postWithRetry(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.createClientEndpoint}'),
        headers: ApiConfig.getHeaders(token: token),
        body: jsonEncode(body),
        maxRetries: 2,
        logCategory: 'BUDGETS',
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
        final client = Client.fromJson(data['data']);

        await DebugLogger.instance.success(
          '✅ Cliente creado exitosamente',
          category: 'BUDGETS',
          data: {'clientId': client.id, 'name': client.name},
        );

        return {
          'success': true,
          'client': client,
          'message': data['message'] ?? 'Cliente creado exitosamente',
        };
      }

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': data['message'] ?? 'Error al crear cliente',
      };
    } catch (e) {
      await DebugLogger.instance.error(
        '❌ Error al crear cliente',
        category: 'BUDGETS',
      );

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': 'Error al crear cliente: $e',
      };
    }
  }
  
  /// Obtener tareas disponibles para asociar a presupuesto
  /// (Sin presupuesto y no cerradas)
  /// 
  /// [search] - Búsqueda opcional por ID o descripción
  Future<Map<String, dynamic>> getAvailableJobs({String search = ''}) async {
    await DebugLogger.instance.info(
      '🔍 Obteniendo tareas disponibles...',
      category: 'BUDGETS',
      data: {'search': search},
    );

    try {
      final token = await _authService.getToken();

      if (token == null) {
        await DebugLogger.instance.error(
          '❌ No hay token de autenticación',
          category: 'BUDGETS',
        );
        return {
          'success': false,
          'errorCode': ApiErrorCode.NO_TOKEN,
          'message': ApiErrorCode.getMessage(ApiErrorCode.NO_TOKEN),
        };
      }

      var url = '${ApiConfig.baseUrl}${ApiConfig.budgetsEndpoint}/available-jobs';
      if (search.isNotEmpty) {
        url += '?search=${Uri.encodeComponent(search)}';
      }

      final result = await NetworkHelper.getWithRetry(
        Uri.parse(url),
        headers: ApiConfig.getHeaders(token: token),
        maxRetries: 2,
        logCategory: 'BUDGETS',
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
        final jobs = data['data'] ?? [];

        await DebugLogger.instance.success(
          '✅ ${jobs.length} tarea(s) disponible(s) obtenida(s)',
          category: 'BUDGETS',
          data: {'count': jobs.length},
        );

        return {
          'success': true,
          'data': jobs,
        };
      }

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': data['message'] ?? 'Error al obtener tareas',
      };
    } catch (e) {
      await DebugLogger.instance.error(
        '❌ Error al obtener tareas disponibles',
        category: 'BUDGETS',
      );

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': 'Error al obtener tareas: $e',
      };
    }
  }
  
  /// Asociar tareas a un presupuesto
  /// 
  /// [budgetId] - ID del presupuesto
  /// [budgetNumber] - Número del presupuesto (ej: 0002-00000046)
  /// [jobIds] - Lista de IDs de tareas a asociar
  Future<Map<String, dynamic>> associateJobsToBudget({
    required String budgetId,
    required String budgetNumber,
    required List<int> jobIds,
  }) async {
    await DebugLogger.instance.info(
      '🔗 Asociando tareas al presupuesto...',
      category: 'BUDGETS',
      data: {
        'budgetId': budgetId,
        'budgetNumber': budgetNumber,
        'jobIds': jobIds,
      },
    );

    try {
      final token = await _authService.getToken();

      if (token == null) {
        await DebugLogger.instance.error(
          '❌ No hay token de autenticación',
          category: 'BUDGETS',
        );
        return {
          'success': false,
          'errorCode': ApiErrorCode.NO_TOKEN,
          'message': ApiErrorCode.getMessage(ApiErrorCode.NO_TOKEN),
        };
      }

      final url = '${ApiConfig.baseUrl}${ApiConfig.budgetsEndpoint}/$budgetId/associate-jobs';

      final result = await NetworkHelper.postWithRetry(
        Uri.parse(url),
        headers: ApiConfig.getHeaders(token: token),
        body: jsonEncode({
          'job_ids': jobIds,
          'budget_number': budgetNumber,
        }),
        maxRetries: 2,
        logCategory: 'BUDGETS',
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
        await DebugLogger.instance.success(
          '✅ Tareas asociadas correctamente',
          category: 'BUDGETS',
          data: data['data'],
        );

        return {
          'success': true,
          'message': data['message'] ?? 'Tareas asociadas correctamente',
          'data': data['data'],
        };
      }

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': data['message'] ?? 'Error al asociar tareas',
      };
    } catch (e) {
      await DebugLogger.instance.error(
        '❌ Error al asociar tareas',
        category: 'BUDGETS',
      );

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': 'Error al asociar tareas: $e',
      };
    }
  }
  
  /// Descargar PDF del presupuesto
  /// 
  /// [budgetId] - ID del presupuesto
  /// Retorna los bytes del PDF
  Future<Map<String, dynamic>> downloadBudgetPdf({
    required int budgetId,
  }) async {
    await DebugLogger.instance.info(
      '📄 Descargando PDF del presupuesto...',
      category: 'BUDGETS',
      data: {'budgetId': budgetId},
    );

    try {
      final token = await _authService.getToken();

      if (token == null) {
        await DebugLogger.instance.error(
          '❌ No hay token de autenticación',
          category: 'BUDGETS',
        );
        return {
          'success': false,
          'errorCode': ApiErrorCode.NO_TOKEN,
          'message': ApiErrorCode.getMessage(ApiErrorCode.NO_TOKEN),
        };
      }

      final url = '${ApiConfig.baseUrl}${ApiConfig.budgetsEndpoint}/$budgetId/pdf';

      await DebugLogger.instance.info(
        '📡 Llamando a API para descargar PDF',
        category: 'BUDGETS',
        data: {'url': url},
      );

      final result = await NetworkHelper.getWithRetry(
        Uri.parse(url),
        headers: ApiConfig.getHeaders(token: token),
        maxRetries: 2,
        logCategory: 'BUDGETS',
      );

      if (!result.success) {
        return {
          'success': false,
          'errorCode': result.errorCode,
          'message': result.userMessage,
        };
      }

      final response = result.data as http.Response;

      // Verificar content-type
      final contentType = response.headers['content-type'] ?? '';

      if (contentType.contains('application/pdf')) {
        // Es un PDF, devolver bytes
        await DebugLogger.instance.success(
          '✅ PDF descargado correctamente',
          category: 'BUDGETS',
          data: {'size': response.bodyBytes.length},
        );

        return {
          'success': true,
          'pdf_bytes': response.bodyBytes,
          'content_type': contentType,
        };
      } else {
        // Respuesta JSON (probablemente error)
        final data = jsonDecode(response.body);
        final errorMessage = data['message'] ?? 'Error al descargar PDF';

        await DebugLogger.instance.error(
          '❌ Error: $errorMessage',
          category: 'BUDGETS',
        );

        return {
          'success': false,
          'errorCode': ApiErrorCode.UNKNOWN,
          'message': errorMessage,
        };
      }
    } catch (e) {
      await DebugLogger.instance.error(
        '❌ Error al descargar PDF',
        category: 'BUDGETS',
      );

      return {
        'success': false,
        'errorCode': ApiErrorCode.UNKNOWN,
        'message': 'Error al descargar PDF: $e',
      };
    }
  }
}
