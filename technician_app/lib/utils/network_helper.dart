import 'dart:io';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'debug_logger.dart';

/// Códigos de Error Específicos para la App
class ApiErrorCode {
  static const String NO_TOKEN = 'NO_TOKEN';
  static const String TOKEN_EXPIRED = 'TOKEN_EXPIRED';
  static const String NO_INTERNET = 'NO_INTERNET';
  static const String TIMEOUT = 'TIMEOUT';
  static const String SERVER_ERROR = 'SERVER_ERROR';
  static const String NOT_FOUND = 'NOT_FOUND';
  static const String FORBIDDEN = 'FORBIDDEN';
  static const String UNAUTHORIZED = 'UNAUTHORIZED';
  static const String BAD_REQUEST = 'BAD_REQUEST';
  static const String UNKNOWN = 'UNKNOWN';

  /// Obtener mensaje user-friendly para cada código de error
  static String getMessage(String code) {
    switch (code) {
      case NO_TOKEN:
        return 'No has iniciado sesión. Por favor, inicia sesión nuevamente.';
      case TOKEN_EXPIRED:
        return 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.';
      case NO_INTERNET:
        return 'Sin conexión a internet. Verifica tu conexión e intenta de nuevo.';
      case TIMEOUT:
        return 'La petición tardó demasiado. Verifica tu conexión e intenta de nuevo.';
      case SERVER_ERROR:
        return 'Error en el servidor. Intenta de nuevo más tarde.';
      case NOT_FOUND:
        return 'No se encontró el recurso solicitado.';
      case FORBIDDEN:
        return 'No tienes permisos para realizar esta acción.';
      case UNAUTHORIZED:
        return 'No autorizado. Por favor, inicia sesión nuevamente.';
      case BAD_REQUEST:
        return 'Petición inválida. Verifica los datos e intenta de nuevo.';
      default:
        return 'Error desconocido. Intenta de nuevo.';
    }
  }
}

/// Resultado de una petición API
class ApiResult {
  final bool success;
  final dynamic data;
  final String? errorCode;
  final String? errorMessage;
  final int attempt;

  ApiResult({
    required this.success,
    this.data,
    this.errorCode,
    this.errorMessage,
    this.attempt = 1,
  });

  bool get isNoToken => errorCode == ApiErrorCode.NO_TOKEN;
  bool get isUnauthorized =>
      errorCode == ApiErrorCode.UNAUTHORIZED ||
      errorCode == ApiErrorCode.TOKEN_EXPIRED;
  bool get isNoInternet => errorCode == ApiErrorCode.NO_INTERNET;
  bool get isTimeout => errorCode == ApiErrorCode.TIMEOUT;
  bool get isServerError => errorCode == ApiErrorCode.SERVER_ERROR;

  String get userMessage {
    if (errorMessage != null) return errorMessage!;
    if (errorCode != null) return ApiErrorCode.getMessage(errorCode!);
    return 'Error desconocido';
  }
}

/// Utilidad para peticiones HTTP con retry automático
class NetworkHelper {
  /// Timeout por defecto: 30 segundos
  static const Duration defaultTimeout = Duration(seconds: 30);

  /// Máximo de reintentos por defecto
  static const int defaultMaxRetries = 2;

  /// Delay entre reintentos (aumenta exponencialmente)
  static const Duration initialRetryDelay = Duration(milliseconds: 500);

  /// Realizar petición GET con retry automático
  static Future<ApiResult> getWithRetry(
    Uri url, {
    required Map<String, String> headers,
    Duration timeout = defaultTimeout,
    int maxRetries = defaultMaxRetries,
    String? logCategory,
  }) async {
    return _executeWithRetry(
      () => http.get(url, headers: headers).timeout(timeout),
      url: url.toString(),
      method: 'GET',
      maxRetries: maxRetries,
      logCategory: logCategory,
    );
  }

  /// Realizar petición POST con retry automático
  static Future<ApiResult> postWithRetry(
    Uri url, {
    required Map<String, String> headers,
    required String body,
    Duration timeout = defaultTimeout,
    int maxRetries = defaultMaxRetries,
    String? logCategory,
  }) async {
    return _executeWithRetry(
      () => http.post(url, headers: headers, body: body).timeout(timeout),
      url: url.toString(),
      method: 'POST',
      maxRetries: maxRetries,
      logCategory: logCategory,
    );
  }

  /// Realizar petición PUT con retry automático
  static Future<ApiResult> putWithRetry(
    Uri url, {
    required Map<String, String> headers,
    required String body,
    Duration timeout = defaultTimeout,
    int maxRetries = defaultMaxRetries,
    String? logCategory,
  }) async {
    return _executeWithRetry(
      () => http.put(url, headers: headers, body: body).timeout(timeout),
      url: url.toString(),
      method: 'PUT',
      maxRetries: maxRetries,
      logCategory: logCategory,
    );
  }

  /// Realizar petición DELETE con retry automático
  static Future<ApiResult> deleteWithRetry(
    Uri url, {
    required Map<String, String> headers,
    Duration timeout = defaultTimeout,
    int maxRetries = defaultMaxRetries,
    String? logCategory,
  }) async {
    return _executeWithRetry(
      () => http.delete(url, headers: headers).timeout(timeout),
      url: url.toString(),
      method: 'DELETE',
      maxRetries: maxRetries,
      logCategory: logCategory,
    );
  }

  /// Ejecutar petición con sistema de retry
  static Future<ApiResult> _executeWithRetry(
    Future<http.Response> Function() request, {
    required String url,
    required String method,
    required int maxRetries,
    String? logCategory,
  }) async {
    int attempt = 0;
    Duration retryDelay = initialRetryDelay;

    while (attempt <= maxRetries) {
      attempt++;

      try {
        await DebugLogger.instance.network(
          '🌐 $method $url (intento $attempt/${maxRetries + 1})',
          data: {'url': url, 'method': method, 'attempt': attempt},
        );

        final response = await request();

        await DebugLogger.instance.network(
          '📥 $method $url → HTTP ${response.statusCode}',
          data: {
            'status': response.statusCode,
            'attempt': attempt,
            'bodyLength': response.body.length,
          },
        );

        // Petición exitosa
        if (response.statusCode >= 200 && response.statusCode < 300) {
          await DebugLogger.instance.success(
            '✅ $method $url successful (attempt $attempt)',
            category: logCategory ?? 'NETWORK',
          );

          return ApiResult(
            success: true,
            data: response,
            attempt: attempt,
          );
        }

        // Errores HTTP específicos
        final errorCode = _getErrorCodeFromStatus(response.statusCode);

        // Si es un error que NO debería reintentarse, retornar inmediatamente
        if (_shouldNotRetry(errorCode)) {
          await DebugLogger.instance.error(
            '❌ $method $url failed: $errorCode (no retry)',
            category: logCategory ?? 'NETWORK',
            data: {'status': response.statusCode, 'body': response.body},
          );

          return ApiResult(
            success: false,
            errorCode: errorCode,
            data: response,
            attempt: attempt,
          );
        }

        // Si hay más reintentos disponibles, continuar
        if (attempt <= maxRetries) {
          await DebugLogger.instance.warning(
            '⚠️ $method $url failed with $errorCode, retrying in ${retryDelay.inMilliseconds}ms...',
            category: logCategory ?? 'NETWORK',
          );

          await Future.delayed(retryDelay);
          retryDelay *= 2; // Exponential backoff
          continue;
        }

        // Sin más reintentos
        await DebugLogger.instance.error(
          '❌ $method $url failed after $attempt attempts: $errorCode',
          category: logCategory ?? 'NETWORK',
          data: {'status': response.statusCode},
        );

        return ApiResult(
          success: false,
          errorCode: errorCode,
          data: response,
          attempt: attempt,
        );
      } on SocketException catch (e) {
        // Sin conexión a internet
        await DebugLogger.instance.error(
          '❌ $method $url: No internet connection',
          category: logCategory ?? 'NETWORK',
          data: {'error': e.toString(), 'attempt': attempt},
        );

        if (attempt <= maxRetries) {
          await Future.delayed(retryDelay);
          retryDelay *= 2;
          continue;
        }

        return ApiResult(
          success: false,
          errorCode: ApiErrorCode.NO_INTERNET,
          errorMessage: 'Sin conexión a internet',
          attempt: attempt,
        );
      } on TimeoutException catch (e) {
        // Timeout
        await DebugLogger.instance.error(
          '❌ $method $url: Timeout',
          category: logCategory ?? 'NETWORK',
          data: {'error': e.toString(), 'attempt': attempt},
        );

        if (attempt <= maxRetries) {
          await Future.delayed(retryDelay);
          retryDelay *= 2;
          continue;
        }

        return ApiResult(
          success: false,
          errorCode: ApiErrorCode.TIMEOUT,
          errorMessage: 'La petición tardó demasiado',
          attempt: attempt,
        );
      } catch (e, stackTrace) {
        // Error desconocido
        await DebugLogger.instance.error(
          '❌ $method $url: Unknown error',
          category: logCategory ?? 'NETWORK',
          data: {
            'error': e.toString(),
            'stackTrace': stackTrace.toString(),
            'attempt': attempt,
          },
        );

        if (attempt <= maxRetries) {
          await Future.delayed(retryDelay);
          retryDelay *= 2;
          continue;
        }

        return ApiResult(
          success: false,
          errorCode: ApiErrorCode.UNKNOWN,
          errorMessage: e.toString(),
          attempt: attempt,
        );
      }
    }

    // Nunca debería llegar aquí, pero por si acaso
    return ApiResult(
      success: false,
      errorCode: ApiErrorCode.UNKNOWN,
      errorMessage: 'Error desconocido después de $attempt intentos',
      attempt: attempt,
    );
  }

  /// Obtener código de error desde HTTP status code
  static String _getErrorCodeFromStatus(int statusCode) {
    switch (statusCode) {
      case 400:
        return ApiErrorCode.BAD_REQUEST;
      case 401:
        return ApiErrorCode.UNAUTHORIZED;
      case 403:
        return ApiErrorCode.FORBIDDEN;
      case 404:
        return ApiErrorCode.NOT_FOUND;
      case 500:
      case 502:
      case 503:
      case 504:
        return ApiErrorCode.SERVER_ERROR;
      default:
        return ApiErrorCode.UNKNOWN;
    }
  }

  /// Determinar si NO se debe reintentar para este código de error
  static bool _shouldNotRetry(String errorCode) {
    // No reintentar errores de autenticación/autorización, bad request, not found
    return errorCode == ApiErrorCode.UNAUTHORIZED ||
        errorCode == ApiErrorCode.FORBIDDEN ||
        errorCode == ApiErrorCode.BAD_REQUEST ||
        errorCode == ApiErrorCode.NOT_FOUND;
  }

  /// Verificar si hay conexión a internet
  static Future<bool> hasInternetConnection() async {
    try {
      final result = await InternetAddress.lookup('google.com');
      return result.isNotEmpty && result[0].rawAddress.isNotEmpty;
    } on SocketException catch (_) {
      return false;
    }
  }
}
