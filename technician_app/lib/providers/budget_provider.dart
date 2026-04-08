import 'package:flutter/material.dart';
import '../models/budget.dart';
import '../models/user.dart';
import '../services/budget_service.dart';
import '../services/auth_service.dart';
import '../utils/debug_logger.dart';

class BudgetProvider with ChangeNotifier {
  final BudgetService _budgetService = BudgetService();
  final AuthService _authService = AuthService();

  // Estado
  bool _isLoading = false;
  String? _errorMessage;
  List<Budget> _budgets = [];
  Budget? _currentBudget;
  int _currentPage = 1;
  int _totalBudgets = 0;
  final int _limit = 20;
  
  // Estado para tareas
  List<Map<String, dynamic>> _availableJobs = [];
  bool _isLoadingJobs = false;
  
  // Permisos del usuario
  User? _currentUser;
  bool _canCreateBudgets = false;
  bool _canReadBudgets = false;
  bool _canCreateClients = false;

  // Getters
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  List<Budget> get budgets => _budgets;
  Budget? get currentBudget => _currentBudget;
  int get currentPage => _currentPage;
  int get totalBudgets => _totalBudgets;
  int get totalPages => (_totalBudgets / _limit).ceil();
  bool get hasNextPage => _currentPage < totalPages;
  bool get hasPreviousPage => _currentPage > 1;
  
  // Getters para tareas
  List<Map<String, dynamic>> get availableJobs => _availableJobs;
  bool get isLoadingJobs => _isLoadingJobs;
  
  // Getters de permisos
  bool get canCreateBudgets => _canCreateBudgets;
  bool get canReadBudgets => _canReadBudgets;
  bool get canCreateClients => _canCreateClients;

  /// Cargar permisos del usuario desde el almacenamiento local
  Future<void> loadUserPermissions() async {
    try {
      final userData = await _authService.getCurrentUser();
      if (userData['success'] == true && userData['user'] != null) {
        _currentUser = User.fromJson(userData['user']);
        _canCreateBudgets = _currentUser!.canCreateBudgets;
        _canReadBudgets = _currentUser!.canReadBudgets;
        _canCreateClients = _currentUser!.canCreateClients;
        
        await DebugLogger.instance.info(
          '🔐 Permisos de presupuestos cargados: Crear=$_canCreateBudgets, Leer=$_canReadBudgets, Crear clientes=$_canCreateClients',
          category: 'BUDGET_PROVIDER',
        );
        
        notifyListeners();
      }
    } catch (e) {
      await DebugLogger.instance.error(
        '🔐 Error cargando permisos',
        category: 'BUDGET_PROVIDER',
      );
    }
  }

  /// Limpiar error
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  /// Obtener lista de presupuestos
  Future<void> fetchBudgets({int page = 1}) async {
    // Verificar permiso
    if (!_canReadBudgets && _currentUser != null) {
      _errorMessage = 'No tienes permiso para ver presupuestos';
      await DebugLogger.instance.warning(
        '🔐 Usuario sin permiso para leer presupuestos',
        category: 'BUDGET_PROVIDER',
      );
      notifyListeners();
      return;
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await DebugLogger.instance.info(
        '📋 Obteniendo presupuestos página $page...',
        category: 'BUDGET_PROVIDER',
      );

      final result = await _budgetService.getBudgets(
        page: page,
        limit: _limit,
      );

      if (result['success'] == true) {
        _budgets = result['budgets'] ?? [];
        _totalBudgets = result['total'] ?? 0;
        _currentPage = result['page'] ?? page;
        _errorMessage = null;

        await DebugLogger.instance.success(
          '✅ Presupuestos obtenidos: ${_budgets.length}',
          category: 'BUDGET_PROVIDER',
        );
      } else {
        _errorMessage = result['message'] ?? 'Error al cargar presupuestos';
        await DebugLogger.instance.error(
          '❌ Error: $_errorMessage',
          category: 'BUDGET_PROVIDER',
        );
      }
    } catch (e) {
      _errorMessage = 'Error inesperado: $e';
      await DebugLogger.instance.error(
        '❌ Error inesperado',
        category: 'BUDGET_PROVIDER',
      );
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Cargar siguiente página
  Future<void> nextPage() async {
    if (hasNextPage && !_isLoading) {
      await fetchBudgets(page: _currentPage + 1);
    }
  }

  /// Cargar página anterior
  Future<void> previousPage() async {
    if (hasPreviousPage && !_isLoading) {
      await fetchBudgets(page: _currentPage - 1);
    }
  }

  /// Obtener detalle de un presupuesto
  Future<void> fetchBudgetDetail(int budgetId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await DebugLogger.instance.info(
        '📄 Obteniendo detalle del presupuesto #$budgetId...',
        category: 'BUDGET_PROVIDER',
      );

      final result = await _budgetService.getBudgetDetail(budgetId);

      if (result['success'] == true) {
        _currentBudget = result['budget'];
        _errorMessage = null;

        await DebugLogger.instance.success(
          '✅ Detalle obtenido',
          category: 'BUDGET_PROVIDER',
        );
      } else {
        _errorMessage = result['message'] ?? 'Error al cargar detalle';
        await DebugLogger.instance.error(
          '❌ Error: $_errorMessage',
          category: 'BUDGET_PROVIDER',
        );
      }
    } catch (e) {
      _errorMessage = 'Error inesperado: $e';
      await DebugLogger.instance.error(
        '❌ Error inesperado',
        category: 'BUDGET_PROVIDER',
      );
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Crear un nuevo presupuesto
  Future<Map<String, dynamic>> createBudget({
    required int clientId,
    required String fecha,
    required List<Map<String, dynamic>> items,
    String? observaciones,
  }) async {
    // Verificar permiso
    if (!_canCreateBudgets) {
      await DebugLogger.instance.warning(
        '🔐 Usuario sin permiso para crear presupuestos',
        category: 'BUDGET_PROVIDER',
      );
      return {
        'success': false,
        'message': 'No tienes permiso para crear presupuestos',
      };
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await DebugLogger.instance.info(
        '➕ Creando presupuesto...',
        category: 'BUDGET_PROVIDER',
        data: {'clientId': clientId, 'itemsCount': items.length},
      );

      final result = await _budgetService.createBudget(
        clientId: clientId,
        fecha: fecha,
        items: items,
        observaciones: observaciones,
      );

      if (result['success'] == true) {
        _currentBudget = result['budget'];
        _errorMessage = null;

        await DebugLogger.instance.success(
          '✅ Presupuesto creado: ${_currentBudget?.nroFactura}',
          category: 'BUDGET_PROVIDER',
        );

        // Recargar lista
        await fetchBudgets(page: 1);

        return {
          'success': true,
          'budget': _currentBudget,
          'message': result['message'],
        };
      } else {
        _errorMessage = result['message'] ?? 'Error al crear presupuesto';
        await DebugLogger.instance.error(
          '❌ Error: $_errorMessage',
          category: 'BUDGET_PROVIDER',
        );

        return {
          'success': false,
          'message': _errorMessage,
        };
      }
    } catch (e) {
      _errorMessage = 'Error inesperado: $e';
      await DebugLogger.instance.error(
        '❌ Error inesperado',
        category: 'BUDGET_PROVIDER',

      );

      return {
        'success': false,
        'message': _errorMessage,
      };
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Limpiar presupuesto actual
  
  /// Obtener tareas disponibles para asociar a presupuesto
  /// (Sin presupuesto asociado y no cerradas)
  Future<bool> fetchAvailableJobs({String search = ''}) async {
    _isLoadingJobs = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await DebugLogger.instance.info(
        '🔍 Obteniendo tareas disponibles...',
        category: 'BUDGET_PROVIDER',
      );

      final result = await _budgetService.getAvailableJobs(search: search);

      if (result['success'] == true) {
        _availableJobs = List<Map<String, dynamic>>.from(result['data'] ?? []);
        
        await DebugLogger.instance.success(
          '✅ ${_availableJobs.length} tareas disponibles cargadas',
          category: 'BUDGET_PROVIDER',
        );
        
        return true;
      } else {
        _errorMessage = result['message'] ?? 'Error al cargar tareas';
        await DebugLogger.instance.error(
          '❌ Error: $_errorMessage',
          category: 'BUDGET_PROVIDER',
        );
        return false;
      }
    } catch (e) {
      _errorMessage = 'Error inesperado: $e';
      await DebugLogger.instance.error(
        '❌ Error inesperado al cargar tareas',
        category: 'BUDGET_PROVIDER',

      );
      return false;
    } finally {
      _isLoadingJobs = false;
      notifyListeners();
    }
  }
  
  /// Asociar tareas a un presupuesto
  Future<Map<String, dynamic>> associateJobsToBudget({
    required int budgetId,
    required String budgetNumber,
    required List<int> jobIds,
  }) async {
    if (jobIds.isEmpty) {
      return {
        'success': false,
        'message': 'Debe seleccionar al menos una tarea',
      };
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await DebugLogger.instance.info(
        '🔗 Asociando ${jobIds.length} tarea(s) al presupuesto $budgetNumber...',
        category: 'BUDGET_PROVIDER',
      );

      final result = await _budgetService.associateJobsToBudget(
        budgetId: budgetId.toString(),
        budgetNumber: budgetNumber,
        jobIds: jobIds,
      );

      if (result['success'] == true) {
        await DebugLogger.instance.success(
          '✅ Tareas asociadas correctamente',
          category: 'BUDGET_PROVIDER',
        );

        // Limpiar lista de tareas disponibles
        _availableJobs = [];
        
        // Recargar detalle del presupuesto si es el actual
        if (_currentBudget?.idFactura == budgetId.toString()) {
          await fetchBudgetDetail(budgetId);
        }

        return {
          'success': true,
          'message': result['message'] ?? 'Tareas asociadas correctamente',
          'data': result['data'],
        };
      } else {
        _errorMessage = result['message'] ?? 'Error al asociar tareas';
        await DebugLogger.instance.error(
          '❌ Error: $_errorMessage',
          category: 'BUDGET_PROVIDER',
        );

        return {
          'success': false,
          'message': _errorMessage,
        };
      }
    } catch (e) {
      _errorMessage = 'Error inesperado: $e';
      await DebugLogger.instance.error(
        '❌ Error inesperado al asociar tareas',
        category: 'BUDGET_PROVIDER',

      );

      return {
        'success': false,
        'message': _errorMessage,
      };
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
  
  /// Limpiar lista de tareas disponibles
  void clearAvailableJobs() {
    _availableJobs = [];
    notifyListeners();
  }
  void clearCurrentBudget() {
    _currentBudget = null;
    notifyListeners();
  }
  
  /// Descargar PDF del presupuesto
  /// Retorna los bytes del PDF si tiene éxito
  Future<Map<String, dynamic>> downloadBudgetPdf(int budgetId) async {
    try {
      await DebugLogger.instance.info(
        '📄 Descargando PDF del presupuesto #$budgetId...',
        category: 'BUDGET_PROVIDER',
      );

      final result = await _budgetService.downloadBudgetPdf(
        budgetId: budgetId,
      );

      if (result['success'] == true) {
        await DebugLogger.instance.success(
          '✅ PDF descargado correctamente',
          category: 'BUDGET_PROVIDER',
        );

        return {
          'success': true,
          'pdf_bytes': result['pdf_bytes'],
        };
      } else {
        final errorMessage = result['message'] ?? 'Error al descargar PDF';
        
        await DebugLogger.instance.error(
          '❌ Error: $errorMessage',
          category: 'BUDGET_PROVIDER',
        );

        return {
          'success': false,
          'message': errorMessage,
        };
      }
    } catch (e) {
      await DebugLogger.instance.error(
        '❌ Error inesperado al descargar PDF',
        category: 'BUDGET_PROVIDER',
      );

      return {
        'success': false,
        'message': 'Error inesperado: $e',
      };
    }
  }
}
