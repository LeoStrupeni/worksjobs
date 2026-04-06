import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:intl/intl.dart';

/// Sistema de Logging Local para Debug
/// 
/// Guarda los últimos 100 logs en el dispositivo para debugging
/// Los logs persisten entre sesiones y se pueden ver en la pantalla de debug
class DebugLogger {
  static const String _logsKey = 'debug_logs';
  static const int _maxLogs = 100;
  static DebugLogger? _instance;
  
  List<DebugLog> _logs = [];
  bool _isInitialized = false;

  // Singleton
  static DebugLogger get instance {
    _instance ??= DebugLogger._internal();
    return _instance!;
  }

  DebugLogger._internal();

  /// Inicializar el logger (cargar logs guardados)
  Future<void> initialize() async {
    if (_isInitialized) return;
    
    try {
      final prefs = await SharedPreferences.getInstance();
      final logsJson = prefs.getString(_logsKey);
      
      if (logsJson != null) {
        final List<dynamic> decoded = jsonDecode(logsJson);
        _logs = decoded.map((log) => DebugLog.fromJson(log)).toList();
      }
      
      _isInitialized = true;
    } catch (e) {
      print('❌ Error al cargar logs: $e');
      _logs = [];
      _isInitialized = true;
    }
  }

  /// Agregar un log
  Future<void> log(String message, {
    LogLevel level = LogLevel.info,
    String? category,
    Map<String, dynamic>? data,
  }) async {
    if (!_isInitialized) {
      await initialize();
    }

    final log = DebugLog(
      message: message,
      level: level,
      category: category,
      timestamp: DateTime.now(),
      data: data,
    );

    _logs.insert(0, log); // Agregar al principio (más reciente primero)

    // Mantener solo los últimos 100 logs
    if (_logs.length > _maxLogs) {
      _logs = _logs.sublist(0, _maxLogs);
    }

    await _saveLogs();

    // Imprimir en consola también
    _printLog(log);
  }

  /// Logs por nivel
  Future<void> info(String message, {String? category, Map<String, dynamic>? data}) async {
    await log(message, level: LogLevel.info, category: category, data: data);
  }

  Future<void> warning(String message, {String? category, Map<String, dynamic>? data}) async {
    await log(message, level: LogLevel.warning, category: category, data: data);
  }

  Future<void> error(String message, {String? category, Map<String, dynamic>? data}) async {
    await log(message, level: LogLevel.error, category: category, data: data);
  }

  Future<void> success(String message, {String? category, Map<String, dynamic>? data}) async {
    await log(message, level: LogLevel.success, category: category, data: data);
  }

  Future<void> network(String message, {Map<String, dynamic>? data}) async {
    await log(message, level: LogLevel.info, category: 'NETWORK', data: data);
  }

  Future<void> auth(String message, {Map<String, dynamic>? data}) async {
    await log(message, level: LogLevel.info, category: 'AUTH', data: data);
  }

  /// Guardar logs en SharedPreferences
  Future<void> _saveLogs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final logsJson = jsonEncode(_logs.map((log) => log.toJson()).toList());
      await prefs.setString(_logsKey, logsJson);
    } catch (e) {
      print('❌ Error al guardar logs: $e');
    }
  }

  /// Imprimir log en consola con formato
  void _printLog(DebugLog log) {
    final emoji = _getEmojiForLevel(log.level);
    final category = log.category != null ? '[${log.category}] ' : '';
    final time = DateFormat('HH:mm:ss').format(log.timestamp);
    print('$emoji $time $category${log.message}');
    
    if (log.data != null && log.data!.isNotEmpty) {
      print('   📋 Data: ${log.data}');
    }
  }

  String _getEmojiForLevel(LogLevel level) {
    switch (level) {
      case LogLevel.info:
        return 'ℹ️';
      case LogLevel.warning:
        return '⚠️';
      case LogLevel.error:
        return '❌';
      case LogLevel.success:
        return '✅';
    }
  }

  /// Obtener todos los logs
  List<DebugLog> getLogs() {
    return List.from(_logs);
  }

  /// Obtener logs filtrados por nivel
  List<DebugLog> getLogsByLevel(LogLevel level) {
    return _logs.where((log) => log.level == level).toList();
  }

  /// Obtener logs filtrados por categoría
  List<DebugLog> getLogsByCategory(String category) {
    return _logs.where((log) => log.category == category).toList();
  }

  /// Limpiar todos los logs
  Future<void> clear() async {
    _logs.clear();
    await _saveLogs();
  }

  /// Exportar logs como texto
  String exportAsText() {
    final buffer = StringBuffer();
    buffer.writeln('=== DEBUG LOGS - Strupeni Técnicos ===');
    buffer.writeln('Generado: ${DateFormat('dd/MM/yyyy HH:mm:ss').format(DateTime.now())}');
    buffer.writeln('Total logs: ${_logs.length}');
    buffer.writeln('');

    for (final log in _logs) {
      final emoji = _getEmojiForLevel(log.level);
      final category = log.category != null ? '[${log.category}] ' : '';
      final time = DateFormat('dd/MM HH:mm:ss').format(log.timestamp);
      
      buffer.writeln('$emoji $time $category${log.message}');
      
      if (log.data != null && log.data!.isNotEmpty) {
        buffer.writeln('   Data: ${log.data}');
      }
      buffer.writeln('');
    }

    return buffer.toString();
  }

  /// Obtener estadísticas de logs
  Map<String, int> getStats() {
    return {
      'total': _logs.length,
      'info': _logs.where((l) => l.level == LogLevel.info).length,
      'warning': _logs.where((l) => l.level == LogLevel.warning).length,
      'error': _logs.where((l) => l.level == LogLevel.error).length,
      'success': _logs.where((l) => l.level == LogLevel.success).length,
    };
  }
}

/// Modelo de un log
class DebugLog {
  final String message;
  final LogLevel level;
  final String? category;
  final DateTime timestamp;
  final Map<String, dynamic>? data;

  DebugLog({
    required this.message,
    required this.level,
    this.category,
    required this.timestamp,
    this.data,
  });

  Map<String, dynamic> toJson() {
    return {
      'message': message,
      'level': level.toString().split('.').last,
      'category': category,
      'timestamp': timestamp.toIso8601String(),
      'data': data,
    };
  }

  factory DebugLog.fromJson(Map<String, dynamic> json) {
    return DebugLog(
      message: json['message'] as String,
      level: _logLevelFromString(json['level'] as String),
      category: json['category'] as String?,
      timestamp: DateTime.parse(json['timestamp'] as String),
      data: json['data'] as Map<String, dynamic>?,
    );
  }

  static LogLevel _logLevelFromString(String level) {
    switch (level) {
      case 'info':
        return LogLevel.info;
      case 'warning':
        return LogLevel.warning;
      case 'error':
        return LogLevel.error;
      case 'success':
        return LogLevel.success;
      default:
        return LogLevel.info;
    }
  }
}

/// Niveles de log
enum LogLevel {
  info,
  warning,
  error,
  success,
}
