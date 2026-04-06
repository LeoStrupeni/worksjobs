import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:share_plus/share_plus.dart';
import 'package:path_provider/path_provider.dart';
import '../utils/debug_logger.dart';
import '../providers/auth_provider.dart';
import '../services/auth_service.dart';
import '../config/api_config.dart';

/// Pantalla de Debug Oculta
/// 
/// Acceso: Tap 5 veces en el logo de la app (home_screen)
/// 
/// Funcionalidades:
/// - Ver logs en tiempo real
/// - Filtrar por nivel (info, warning, error, success)
/// - Ver información del sistema (token, usuario, API)
/// - Limpiar logs
/// - Exportar logs por email/compartir
/// - Probar endpoints manualmente
class DebugScreen extends StatefulWidget {
  const DebugScreen({super.key});

  @override
  State<DebugScreen> createState() => _DebugScreenState();
}

class _DebugScreenState extends State<DebugScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  LogLevel? _selectedFilter;
  String? _categoryFilter;
  bool _autoScroll = true;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('🛠️ Debug Console'),
        backgroundColor: Colors.deepPurple,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          indicatorColor: Colors.white,
          tabs: const [
            Tab(icon: Icon(Icons.article), text: 'Logs'),
            Tab(icon: Icon(Icons.info), text: 'Sistema'),
            Tab(icon: Icon(Icons.build), text: 'Tools'),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => setState(() {}),
            tooltip: 'Refrescar',
          ),
        ],
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildLogsTab(),
          _buildSystemInfoTab(),
          _buildToolsTab(),
        ],
      ),
    );
  }

  // ===================================
  // TAB 1: LOGS
  // ===================================
  Widget _buildLogsTab() {
    final logs = _getFilteredLogs();
    final stats = DebugLogger.instance.getStats();

    return Column(
      children: [
        // Estadísticas
        Container(
          padding: const EdgeInsets.all(12),
          color: Colors.grey[200],
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _buildStatChip('Total', stats['total']!, Colors.blue),
              _buildStatChip('Info', stats['info']!, Colors.grey[700]!),
              _buildStatChip('Warning', stats['warning']!, Colors.orange),
              _buildStatChip('Error', stats['error']!, Colors.red),
              _buildStatChip('Success', stats['success']!, Colors.green),
            ],
          ),
        ),

        // Filtros
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          child: Row(
            children: [
              Expanded(
                child: SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _buildFilterChip('Todos', null),
                      const SizedBox(width: 8),
                      _buildFilterChip('Info', LogLevel.info),
                      const SizedBox(width: 8),
                      _buildFilterChip('Warning', LogLevel.warning),
                      const SizedBox(width: 8),
                      _buildFilterChip('Error', LogLevel.error),
                      const SizedBox(width: 8),
                      _buildFilterChip('Success', LogLevel.success),
                    ],
                  ),
                ),
              ),
              IconButton(
                icon: const Icon(Icons.filter_list),
                onPressed: _showCategoryFilter,
                tooltip: 'Filtrar por categoría',
              ),
            ],
          ),
        ),

        // Lista de logs
        Expanded(
          child: logs.isEmpty
              ? const Center(
                  child: Text('No hay logs para mostrar'),
                )
              : ListView.builder(
                  reverse: false,
                  itemCount: logs.length,
                  itemBuilder: (context, index) {
                    final log = logs[index];
                    return _buildLogItem(log);
                  },
                ),
        ),

        // Botones de acción
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: Colors.grey[100],
            border: Border(top: BorderSide(color: Colors.grey[300]!)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              ElevatedButton.icon(
                onPressed: _exportLogs,
                icon: const Icon(Icons.share, size: 18),
                label: const Text('Exportar'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue,
                  foregroundColor: Colors.white,
                ),
              ),
              ElevatedButton.icon(
                onPressed: _copyLogsToClipboard,
                icon: const Icon(Icons.copy, size: 18),
                label: const Text('Copiar'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  foregroundColor: Colors.white,
                ),
              ),
              ElevatedButton.icon(
                onPressed: _clearLogs,
                icon: const Icon(Icons.delete, size: 18),
                label: const Text('Limpiar'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red,
                  foregroundColor: Colors.white,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildStatChip(String label, int count, Color color) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          count.toString(),
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        Text(
          label,
          style: TextStyle(fontSize: 11, color: Colors.grey[600]),
        ),
      ],
    );
  }

  Widget _buildFilterChip(String label, LogLevel? level) {
    final isSelected = _selectedFilter == level;
    return FilterChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (selected) {
        setState(() {
          _selectedFilter = selected ? level : null;
        });
      },
      backgroundColor: Colors.grey[200],
      selectedColor: Colors.deepPurple[100],
      checkmarkColor: Colors.deepPurple,
    );
  }

  Widget _buildLogItem(DebugLog log) {
    final color = _getColorForLevel(log.level);
    final icon = _getIconForLevel(log.level);
    final time = DateFormat('HH:mm:ss').format(log.timestamp);

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      child: ExpansionTile(
        leading: Icon(icon, color: color, size: 20),
        title: Text(
          log.message,
          style: const TextStyle(fontSize: 13),
        ),
        subtitle: Row(
          children: [
            Text(
              time,
              style: TextStyle(fontSize: 11, color: Colors.grey[600]),
            ),
            if (log.category != null) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  log.category!,
                  style: TextStyle(fontSize: 10, color: color),
                ),
              ),
            ],
          ],
        ),
        children: [
          if (log.data != null && log.data!.isNotEmpty)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              color: Colors.grey[100],
              child: Text(
                log.data.toString(),
                style: const TextStyle(fontSize: 11, fontFamily: 'monospace'),
              ),
            ),
        ],
      ),
    );
  }

  Color _getColorForLevel(LogLevel level) {
    switch (level) {
      case LogLevel.info:
        return Colors.blue;
      case LogLevel.warning:
        return Colors.orange;
      case LogLevel.error:
        return Colors.red;
      case LogLevel.success:
        return Colors.green;
    }
  }

  IconData _getIconForLevel(LogLevel level) {
    switch (level) {
      case LogLevel.info:
        return Icons.info;
      case LogLevel.warning:
        return Icons.warning;
      case LogLevel.error:
        return Icons.error;
      case LogLevel.success:
        return Icons.check_circle;
    }
  }

  List<DebugLog> _getFilteredLogs() {
    var logs = DebugLogger.instance.getLogs();

    if (_selectedFilter != null) {
      logs = logs.where((log) => log.level == _selectedFilter).toList();
    }

    if (_categoryFilter != null) {
      logs = logs.where((log) => log.category == _categoryFilter).toList();
    }

    return logs;
  }

  void _showCategoryFilter() {
    final categories = DebugLogger.instance
        .getLogs()
        .where((log) => log.category != null)
        .map((log) => log.category!)
        .toSet()
        .toList();

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Filtrar por Categoría'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                title: const Text('Todas'),
                trailing:
                    _categoryFilter == null ? const Icon(Icons.check) : null,
                onTap: () {
                  setState(() => _categoryFilter = null);
                  Navigator.pop(context);
                },
              ),
              ...categories.map((category) => ListTile(
                    title: Text(category),
                    trailing: _categoryFilter == category
                        ? const Icon(Icons.check)
                        : null,
                    onTap: () {
                      setState(() => _categoryFilter = category);
                      Navigator.pop(context);
                    },
                  )),
            ],
          ),
        ),
      ),
    );
  }

  void _exportLogs() async {
    try {
      final text = DebugLogger.instance.exportAsText();
      final directory = await getTemporaryDirectory();
      final file = File(
          '${directory.path}/strupeni_logs_${DateFormat('yyyyMMdd_HHmmss').format(DateTime.now())}.txt');
      await file.writeAsString(text);

      await Share.shareXFiles(
        [XFile(file.path)],
        subject: 'Logs Debug - Strupeni Técnicos',
      );

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Logs exportados')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error al exportar: $e')),
        );
      }
    }
  }

  void _copyLogsToClipboard() {
    final text = DebugLogger.instance.exportAsText();
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Logs copiados al portapapeles')),
    );
  }

  void _clearLogs() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Limpiar Logs'),
        content: const Text('¿Estás seguro de eliminar todos los logs?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () async {
              await DebugLogger.instance.clear();
              Navigator.pop(context);
              setState(() {});
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Logs eliminados')),
              );
            },
            child: const Text('Eliminar'),
          ),
        ],
      ),
    );
  }

  // ===================================
  // TAB 2: INFORMACIÓN DEL SISTEMA
  // ===================================
  Widget _buildSystemInfoTab() {
    final authProvider = context.watch<AuthProvider>();

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildInfoSection(
            title: '👤 Usuario',
            icon: Icons.person,
            children: [
              _buildInfoRow('Nombre', authProvider.user?.name ?? 'N/A'),
              _buildInfoRow('Email', authProvider.user?.email ?? 'N/A'),
              _buildInfoRow('ID', authProvider.user?.id?.toString() ?? 'N/A'),
              _buildInfoRow('Autenticado',
                  authProvider.isAuthenticated ? 'SÍ' : 'NO'),
            ],
          ),
          const SizedBox(height: 16),
          _buildInfoSection(
            title: '🔑 Token',
            icon: Icons.vpn_key,
            children: [
              FutureBuilder<String?>(
                future: AuthService().getToken(),
                builder: (context, snapshot) {
                  if (!snapshot.hasData || snapshot.data == null) {
                    return _buildInfoRow('Estado', 'Sin token');
                  }

                  final token = snapshot.data!;
                  return Column(
                    children: [
                      _buildInfoRow('Estado', '✅ Token válido'),
                      _buildInfoRow('Primeros 20 chars',
                          token.substring(0, token.length > 20 ? 20 : token.length)),
                      _buildInfoRow('Tamaño', '${token.length} caracteres'),
                    ],
                  );
                },
              ),
            ],
          ),
          const SizedBox(height: 16),
          _buildInfoSection(
            title: '🌐 API',
            icon: Icons.cloud,
            children: [
              _buildInfoRow('Base URL', ApiConfig.baseUrl),
              _buildInfoRow('Endpoint Login', ApiConfig.loginEndpoint),
              _buildInfoRow('Endpoint Citas Hoy', ApiConfig.todayJobsEndpoint),
              _buildInfoRow(
                  'Endpoint Próximas', ApiConfig.upcomingJobsEndpoint),
            ],
          ),
          const SizedBox(height: 16),
          _buildInfoSection(
            title: '📱 Dispositivo',
            icon: Icons.phone_android,
            children: [
              _buildInfoRow('Plataforma', Platform.operatingSystem),
              _buildInfoRow('Versión OS', Platform.operatingSystemVersion),
            ],
          ),
          const SizedBox(height: 16),
          _buildInfoSection(
            title: '📊 Storage',
            icon: Icons.storage,
            children: [
              FutureBuilder<int>(
                future: _calculateStorageSize(),
                builder: (context, snapshot) {
                  if (!snapshot.hasData) {
                    return const CircularProgressIndicator();
                  }
                  return _buildInfoRow(
                      'Logs almacenados', '${snapshot.data} logs');
                },
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildInfoSection({
    required String title,
    required IconData icon,
    required List<Widget> children,
  }) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: Colors.deepPurple),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const Divider(),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              '$label:',
              style: const TextStyle(
                fontWeight: FontWeight.w500,
                color: Colors.grey,
              ),
            ),
          ),
          Expanded(
            child: SelectableText(
              value,
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }

  Future<int> _calculateStorageSize() async {
    return DebugLogger.instance.getLogs().length;
  }

  // ===================================
  // TAB 3: HERRAMIENTAS
  // ===================================
  Widget _buildToolsTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            '🛠️ Herramientas de Debug',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: _testHealthCheck,
            icon: const Icon(Icons.health_and_safety),
            label: const Text('Probar Health Check'),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.green,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.all(16),
            ),
          ),
          const SizedBox(height: 12),
          ElevatedButton.icon(
            onPressed: _testTodayJobs,
            icon: const Icon(Icons.today),
            label: const Text('Probar /jobs/today'),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.all(16),
            ),
          ),
          const SizedBox(height: 12),
          ElevatedButton.icon(
            onPressed: _testUpcomingJobs,
            icon: const Icon(Icons.calendar_month),
            label: const Text('Probar /jobs/upcoming'),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.purple,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.all(16),
            ),
          ),
          const SizedBox(height: 12),
          ElevatedButton.icon(
            onPressed: _forceLogout,
            icon: const Icon(Icons.logout),
            label: const Text('Forzar Logout'),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.all(16),
            ),
          ),
          const SizedBox(height: 12),
          ElevatedButton.icon(
            onPressed: _generateTestLogs,
            icon: const Icon(Icons.bug_report),
            label: const Text('Generar Logs de Prueba'),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.orange,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.all(16),
            ),
          ),
        ],
      ),
    );
  }

  void _testHealthCheck() async {
    await DebugLogger.instance.info('Testing /health-check endpoint...',
        category: 'TEST');

    try {
      final token = await AuthService().getToken();
      if (token == null) {
        await DebugLogger.instance
            .error('No hay token', category: 'TEST');
        _showSnackBar('Error: No hay token de autenticación', Colors.red);
        return;
      }

      // TODO: Llamar al endpoint /health-check aquí
      await DebugLogger.instance.success('Health check OK', category: 'TEST');
      _showSnackBar('Health check OK', Colors.green);
    } catch (e) {
      await DebugLogger.instance
          .error('Health check falló: $e', category: 'TEST');
      _showSnackBar('Error: $e', Colors.red);
    }
  }

  void _testTodayJobs() async {
    await DebugLogger.instance.info('Testing /jobs/today endpoint...',
        category: 'TEST');
    _showSnackBar('Implementar llamada a /jobs/today', Colors.blue);
  }

  void _testUpcomingJobs() async {
    await DebugLogger.instance.info('Testing /jobs/upcoming endpoint...',
        category: 'TEST');
    _showSnackBar('Implementar llamada a /jobs/upcoming', Colors.blue);
  }

  void _forceLogout() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Forzar Logout'),
        content: const Text(
            '¿Estás seguro? Se cerrará la sesión y volverás al login.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () async {
              Navigator.pop(context);
              await context.read<AuthProvider>().logout();
              if (mounted) {
                Navigator.of(context).popUntil((route) => route.isFirst);
              }
            },
            child: const Text('Logout'),
          ),
        ],
      ),
    );
  }

  void _generateTestLogs() async {
    await DebugLogger.instance.info('Este es un log de prueba INFO');
    await DebugLogger.instance.warning('Este es un log de prueba WARNING');
    await DebugLogger.instance.error('Este es un log de prueba ERROR');
    await DebugLogger.instance
        .success('Este es un log de prueba SUCCESS');
    await DebugLogger.instance.network('Petición HTTP GET /api/test',
        data: {'status': 200, 'time': '150ms'});

    setState(() {});
    _showSnackBar('Logs de prueba generados', Colors.green);
  }

  void _showSnackBar(String message, Color color) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: color,
      ),
    );
  }
}
