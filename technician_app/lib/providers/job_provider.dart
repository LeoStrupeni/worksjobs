import 'package:flutter/foundation.dart';
import '../models/job.dart';
import '../models/note.dart';
import '../models/job_file.dart';
import '../models/job_permissions.dart';
import '../models/client.dart';
import '../models/address.dart';
import '../models/product.dart';
import '../services/job_service.dart';
import 'package:geolocator/geolocator.dart';

class JobProvider with ChangeNotifier {
  final JobService _jobService = JobService();
  
  // Getter público para acceder al servicio
  JobService get jobService => _jobService;
  
  List<Job> _todayJobs = [];
  List<Job> _upcomingJobs = [];
  List<Job> _calendarJobs = [];
  Job? _selectedJob;
  List<Note> _notes = [];
  List<JobFile> _files = [];
  JobPermissions? _permissions;
  
  bool _isLoading = false;
  String? _errorMessage;

  List<Job> get todayJobs => _todayJobs;
  List<Job> get upcomingJobs => _upcomingJobs;
  List<Job> get calendarJobs => _calendarJobs;
  Job? get selectedJob => _selectedJob;
  List<Note> get notes => _notes;
  List<JobFile> get files => _files;
  JobPermissions? get permissions => _permissions;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  // Obtener citas del día
  Future<void> fetchTodayJobs() async {
    // print('🔵 JobProvider.fetchTodayJobs: Iniciando...');
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _jobService.getTodayJobs();
      // print('📦 JobProvider.fetchTodayJobs: Result success=${result['success']}');
      
      if (result['success'] == true) {
        _todayJobs = result['jobs'];
        // print('✅ JobProvider.fetchTodayJobs: ${_todayJobs.length} citas guardadas en _todayJobs');
        // print('📋 JobProvider.fetchTodayJobs: Jobs IDs: ${_todayJobs.map((j) => j.id).toList()}');
        if (result['permissions'] != null) {
          _permissions = JobPermissions.fromJson(result['permissions']);
        }
      } else {
        _errorMessage = result['message'];
        print('❌ JobProvider.fetchTodayJobs: Error - $_errorMessage');
      }
    } catch (e) {
      _errorMessage = e.toString();
      print('❌ JobProvider.fetchTodayJobs: Exception - $e');
    } finally {
      _isLoading = false;
      // print('🔵 JobProvider.fetchTodayJobs: Finalizando, isLoading=$_isLoading, todayJobs.length=${_todayJobs.length}');
      notifyListeners();
    }
  }

  // Obtener próximas citas
  Future<void> fetchUpcomingJobs({int limit = 50}) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _jobService.getUpcomingJobs(limit: limit);
      
      if (result['success'] == true) {
        _upcomingJobs = result['jobs'];
        if (result['permissions'] != null) {
          _permissions = JobPermissions.fromJson(result['permissions']);
        }
      } else {
        _errorMessage = result['message'];
      }
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Obtener citas por rango de fechas
  Future<void> fetchJobsByDateRange(String startDate, String endDate) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _jobService.getJobsByDateRange(startDate, endDate);
      
      if (result['success'] == true) {
        _calendarJobs = result['jobs'];
      } else {
        _errorMessage = result['message'];
      }
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Obtener detalle de una cita
  Future<void> fetchJobDetail(int jobId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      // print('🔍 JobProvider: Solicitando detalle del job $jobId');
      final result = await _jobService.getJobDetail(jobId);
      
      // print('📦 JobProvider: Resultado recibido - success: ${result['success']}');
      
      if (result['success'] == true) {
        _selectedJob = result['job'];
        _notes = result['notes'] ?? [];
        _files = result['files'] ?? [];
        // print('✅ JobProvider: Job cargado correctamente - ${_selectedJob?.clientName}');
      } else {
        _errorMessage = result['message'] ?? 'Error desconocido al cargar la cita';
        print('❌ JobProvider: Error - $_errorMessage');
      }
    } catch (e) {
      _errorMessage = 'Error de conexión: ${e.toString()}';
      print('💥 JobProvider: Exception - $_errorMessage');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Marcar llegada
  Future<bool> markArrival(int jobId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      // Obtener ubicación actual
      Position? position = await _getCurrentLocation();
      
      final result = await _jobService.markArrival(
        jobId,
        lat: position?.latitude,
        lng: position?.longitude,
      );
      
      if (result['success'] == true) {
        // Refrescar la cita
        await fetchJobDetail(jobId);
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = result['message'];
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Revertir llegada
  Future<bool> revertArrival(int jobId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _jobService.revertArrival(jobId);
      
      if (result['success'] == true) {
        // Refrescar la cita
        await fetchJobDetail(jobId);
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = result['message'];
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Cerrar cita
  Future<bool> closeJob(int jobId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      // Obtener ubicación actual
      Position? position = await _getCurrentLocation();
      
      final result = await _jobService.closeJob(
        jobId,
        lat: position?.latitude,
        lng: position?.longitude,
      );
      
      if (result['success'] == true) {
        // Refrescar la cita
        await fetchJobDetail(jobId);
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = result['message'];
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Añadir nota
  Future<bool> addNote(int jobId, String note) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _jobService.addNote(jobId, note);
      
      if (result['success'] == true) {
        // Refrescar notas
        await fetchJobDetail(jobId);
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = result['message'];
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Eliminar nota
  Future<bool> deleteNote(int jobId, int noteId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _jobService.deleteNote(noteId);
      
      if (result['success'] == true) {
        // Refrescar notas
        await fetchJobDetail(jobId);
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = result['message'];
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Eliminar archivo/imagen
  Future<bool> deleteFile(int jobId, int fileId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _jobService.deleteFile(fileId);
      
      if (result['success'] == true) {
        // Refrescar archivos
        await fetchJobDetail(jobId);
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = result['message'];
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Obtener ubicación actual
  Future<Position?> _getCurrentLocation() async {
    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      
      if (!serviceEnabled) {
        return null;
      }

      LocationPermission permission = await Geolocator.checkPermission();
      
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          return null;
        }
      }
      
      if (permission == LocationPermission.deniedForever) {
        return null;
      }

      return await Geolocator.getCurrentPosition();
    } catch (e) {
      // print('Error al obtener ubicación: $e');
      return null;
    }
  }

  // Limpiar error
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  // Limpiar cita seleccionada
  void clearSelectedJob() {
    _selectedJob = null;
    _notes = [];
    _files = [];
    notifyListeners();
  }

  // Volver a pendiente
  Future<bool> backToPending(int jobId) async {
    _isLoading = true;
    notifyListeners();

    try {
      final success = await _jobService.backToPending(jobId);
      
      if (success) {
        // Actualizar job en las listas
        await fetchTodayJobs();
      }
      
      _isLoading = false;
      notifyListeners();
      return success;
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Eliminar tarea
  Future<bool> deleteJob(int jobId) async {
    _isLoading = true;
    notifyListeners();

    try {
      final success = await _jobService.deleteJob(jobId);
      
      if (success) {
        // Actualizar listas
        await fetchTodayJobs();
      }
      
      _isLoading = false;
      notifyListeners();
      return success;
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Subir archivos
  Future<bool> uploadFiles(int jobId, List<String> filePaths) async {
    _isLoading = true;
    notifyListeners();

    try {
      final success = await _jobService.uploadFiles(jobId, filePaths);
      
      if (success && _selectedJob?.id == jobId) {
        // Recargar archivos si es el job seleccionado
        await fetchJobDetail(jobId);
      }
      
      _isLoading = false;
      notifyListeners();
      return success;
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Buscar clientes
  Future<List<Client>> searchClients(String query) async {
    try {
      return await _jobService.searchClients(query);
    } catch (e) {
      return [];
    }
  }

  // Obtener direcciones de un cliente
  Future<List<Address>> getClientAddresses(int clientId) async {
    try {
      return await _jobService.getClientAddresses(clientId);
    } catch (e) {
      return [];
    }
  }

  // Crear nueva dirección para un cliente
  Future<Address?> createClientAddress(
    int clientId,
    String street,
    String number,
    String city,
    String detail,
  ) async {
    try {
      return await _jobService.createClientAddress(
        clientId,
        street,
        number,
        city,
        detail,
      );
    } catch (e) {
      rethrow;
    }
  }

  // Crear nueva tarea
  Future<bool> createJob({
    required int clientId,
    required int addressId,
    required DateTime visitDateTime,
    required String description,
    double? latitude,
    double? longitude,
    String? jsonGeolocation,
    List<int>? technicianIds,
    List<SelectedProduct>? products,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _jobService.createJob(
        clientId: clientId,
        addressId: addressId,
        visitDateTime: visitDateTime,
        description: description,
        latitude: latitude,
        longitude: longitude,
        jsonGeolocation: jsonGeolocation,
        technicianIds: technicianIds,
        products: products,
      );
      
      if (result['success'] == true) {
        // Actualizar lista de tareas
        await fetchTodayJobs();
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = result['message'];
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Actualizar tarea existente
  Future<bool> updateJob({
    required int jobId,
    required int addressId,
    required DateTime visitDateTime,
    required String description,
    double? latitude,
    double? longitude,
    String? jsonGeolocation,
    List<int>? technicianIds,
    List<SelectedProduct>? products,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _jobService.updateJob(
        jobId: jobId,
        addressId: addressId,
        visitDateTime: visitDateTime,
        description: description,
        latitude: latitude,
        longitude: longitude,
        jsonGeolocation: jsonGeolocation,
        technicianIds: technicianIds,
        products: products,
      );
      
      if (result['success'] == true) {
        // Actualizar listas
        await fetchTodayJobs();
        await fetchUpcomingJobs();
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = result['message'];
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Actualizar solo los técnicos de una tarea
  Future<bool> updateJobTechnicians(int jobId, List<int> technicianIds) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _jobService.updateJobTechnicians(
        jobId,
        technicianIds.isNotEmpty ? technicianIds : null,
      );
      
      if (result['success'] == true) {
        // Refrescar el detalle del job
        if (_selectedJob?.id == jobId) {
          await fetchJobDetail(jobId);
        }
        return true;
      } else {
        _errorMessage = result['message'] ?? 'Error al actualizar técnicos';
        return false;
      }
    } catch (e) {
      _errorMessage = 'Error: ${e.toString()}';
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Buscar productos
  Future<List<Product>> searchProducts(String query, {String? tipo}) async {
    try {
      return await _jobService.searchProducts(query, tipo: tipo);
    } catch (e) {
      print('❌ searchProducts (Provider): $e');
      return [];
    }
  }

  // Generar PDF de trabajo realizado
  Future<Map<String, dynamic>> generateJobPDF(
    int jobId,
    Map<String, dynamic> config,
  ) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _jobService.generateJobPDF(jobId, config);
      
      if (result['success'] == true) {
        return result;
      } else {
        _errorMessage = result['message'] ?? 'Error al generar PDF';
        return result;
      }
    } catch (e) {
      _errorMessage = 'Error: ${e.toString()}';
      return {
        'success': false,
        'message': _errorMessage,
      };
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
