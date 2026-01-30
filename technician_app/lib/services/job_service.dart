import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/job.dart';
import '../models/note.dart';
import '../models/job_file.dart';
import '../models/client.dart';
import 'auth_service.dart';

class JobService {
  final AuthService _authService = AuthService();

  // Obtener citas del día
  Future<Map<String, dynamic>> getTodayJobs() async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        print('❌ getTodayJobs: No autenticado');
        return {'success': false, 'message': 'No autenticado'};
      }

      print('📡 getTodayJobs: Llamando a ${ApiConfig.baseUrl}${ApiConfig.todayJobsEndpoint}');
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.todayJobsEndpoint}'),
        headers: ApiConfig.getHeaders(token: token),
      );

      print('📥 getTodayJobs: Status ${response.statusCode}');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        print('📄 getTodayJobs: Response data keys: ${data.keys}');
        
        if (data['success'] == true) {
          print('✅ getTodayJobs: ${data['count']} citas encontradas');
          final jobs = (data['data'] as List)
              .map((job) => Job.fromJson(job))
              .toList();
          
          print('🔑 getTodayJobs: Permissions data: ${data['permissions']}');
          
          return {
            'success': true,
            'jobs': jobs,
            'count': data['count'] ?? 0,
            'permissions': data['permissions'],
          };
        } else {
          print('⚠️ getTodayJobs: success=false en response');
        }
      } else {
        print('❌ getTodayJobs: Error HTTP ${response.statusCode}');
        print('📄 getTodayJobs: Body: ${response.body}');
      }
      
      return {'success': false, 'message': 'Error al obtener citas'};
    } catch (e) {
      print('❌ getTodayJobs: Exception: $e');
      return {'success': false, 'message': 'Error: ${e.toString()}'};
    }
  }

  // Obtener próximas citas
  Future<Map<String, dynamic>> getUpcomingJobs({int limit = 50}) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'No autenticado'};
      }

      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.upcomingJobsEndpoint}?limit=$limit'),
        headers: ApiConfig.getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true) {
          final jobs = (data['data'] as List)
              .map((job) => Job.fromJson(job))
              .toList();
          
          return {
            'success': true,
            'jobs': jobs,
            'count': data['count'] ?? 0,
            'permissions': data['permissions'],
          };
        }
      }
      
      return {'success': false, 'message': 'Error al obtener citas'};
    } catch (e) {
      return {'success': false, 'message': 'Error: ${e.toString()}'};
    }
  }

  // Obtener citas por rango de fechas
  Future<Map<String, dynamic>> getJobsByDateRange(String startDate, String endDate) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        print('❌ getJobsByDateRange: No autenticado');
        return {'success': false, 'message': 'No autenticado'};
      }

      final url = '${ApiConfig.baseUrl}${ApiConfig.calendarJobsEndpoint}?start_date=$startDate&end_date=$endDate';
      print('📡 getJobsByDateRange: $url');
      final response = await http.get(
        Uri.parse(url),
        headers: ApiConfig.getHeaders(token: token),
      );

      print('📥 getJobsByDateRange: Status ${response.statusCode}');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        print('📄 getJobsByDateRange: ${data['count']} citas');
        
        if (data['success'] == true) {
          final jobs = (data['data'] as List)
              .map((job) => Job.fromJson(job))
              .toList();
          
          return {
            'success': true,
            'jobs': jobs,
            'count': data['count'] ?? 0,            'permissions': data['permissions'],          };
        }
      } else {
        print('❌ getJobsByDateRange: Error HTTP ${response.statusCode}');
        print('📄 getJobsByDateRange: ${response.body}');
      }
      
      return {'success': false, 'message': 'Error al obtener citas'};
    } catch (e) {
      print('❌ getJobsByDateRange: Exception: $e');
      return {'success': false, 'message': 'Error: ${e.toString()}'};
    }
  }

  // Obtener detalle de una cita
  Future<Map<String, dynamic>> getJobDetail(int jobId) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'No autenticado'};
      }

      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId'),
        headers: ApiConfig.getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true) {
          final job = Job.fromJson(data['data']);
          final notes = data['data']['notes'] != null
              ? (data['data']['notes'] as List).map((n) => Note.fromJson(n)).toList()
              : <Note>[];
          final files = data['data']['files'] != null
              ? (data['data']['files'] as List).map((f) => JobFile.fromJson(f)).toList()
              : <JobFile>[];
          
          return {
            'success': true,
            'job': job,
            'notes': notes,
            'files': files,
          };
        }
      }
      
      return {'success': false, 'message': 'Error al obtener detalle'};
    } catch (e) {
      return {'success': false, 'message': 'Error: ${e.toString()}'};
    }
  }

  // Marcar llegada
  Future<Map<String, dynamic>> markArrival(int jobId, {double? lat, double? lng}) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'No autenticado'};
      }

      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId/arrival'),
        headers: ApiConfig.getHeaders(token: token),
        body: jsonEncode({
          'latitud': lat,
          'longitud': lng,
        }),
      );

      final data = jsonDecode(response.body);
      return data;
    } catch (e) {
      return {'success': false, 'message': 'Error: ${e.toString()}'};
    }
  }

  // Cerrar cita
  Future<Map<String, dynamic>> closeJob(int jobId, String observation, {double? lat, double? lng}) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'No autenticado'};
      }

      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId/close'),
        headers: ApiConfig.getHeaders(token: token),
        body: jsonEncode({
          'observation': observation,
          'latitud': lat,
          'longitud': lng,
        }),
      );

      final data = jsonDecode(response.body);
      return data;
    } catch (e) {
      return {'success': false, 'message': 'Error: ${e.toString()}'};
    }
  }

  // Añadir nota
  Future<Map<String, dynamic>> addNote(int jobId, String note) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'No autenticado'};
      }

      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId/notes'),
        headers: ApiConfig.getHeaders(token: token),
        body: jsonEncode({
          'note': note,
        }),
      );

      final data = jsonDecode(response.body);
      return data;
    } catch (e) {
      return {'success': false, 'message': 'Error: ${e.toString()}'};
    }
  }

  // Obtener notas
  Future<Map<String, dynamic>> getNotes(int jobId) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'No autenticado'};
      }

      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId/notes'),
        headers: ApiConfig.getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true) {
          final notes = (data['data'] as List)
              .map((note) => Note.fromJson(note))
              .toList();
          
          return {
            'success': true,
            'notes': notes,
          };
        }
      }
      
      return {'success': false, 'message': 'Error al obtener notas'};
    } catch (e) {
      return {'success': false, 'message': 'Error: ${e.toString()}'};
    }
  }

  // Obtener archivos
  Future<Map<String, dynamic>> getFiles(int jobId) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'No autenticado'};
      }

      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId/files'),
        headers: ApiConfig.getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true) {
          final files = (data['data'] as List)
              .map((file) => JobFile.fromJson(file))
              .toList();
          
          return {
            'success': true,
            'files': files,
          };
        }
      }
      
      return {'success': false, 'message': 'Error al obtener archivos'};
    } catch (e) {
      return {'success': false, 'message': 'Error: ${e.toString()}'};
    }
  }

  // Volver a pendiente (desmarcar llegada)
  Future<bool> backToPending(int jobId) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return false;
      }

      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId/back-to-pending'),
        headers: ApiConfig.getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['success'] == true;
      }
      
      return false;
    } catch (e) {
      print('❌ backToPending: Exception: $e');
      return false;
    }
  }

  // Eliminar tarea
  Future<bool> deleteJob(int jobId) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return false;
      }

      final response = await http.delete(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId'),
        headers: ApiConfig.getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['success'] == true;
      }
      
      return false;
    } catch (e) {
      print('❌ deleteJob: Exception: $e');
      return false;
    }
  }

  // Subir archivos/imágenes
  Future<bool> uploadFiles(int jobId, List<String> filePaths) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return false;
      }

      var request = http.MultipartRequest(
        'POST',
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId/files'),
      );

      request.headers.addAll(ApiConfig.getHeaders(token: token));

      // Agregar archivos
      for (var filePath in filePaths) {
        request.files.add(await http.MultipartFile.fromPath('files[]', filePath));
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['success'] == true;
      }
      
      return false;
    } catch (e) {
      print('❌ uploadFiles: Exception: $e');
      return false;
    }
  }

  // Buscar clientes
  Future<List<Client>> searchClients(String query) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return [];
      }

      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/clients?search=$query'),
        headers: ApiConfig.getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true) {
          final clients = (data['data'] as List)
              .map((client) => Client.fromJson(client))
              .toList();
          
          return clients;
        }
      }
      
      return [];
    } catch (e) {
      print('❌ searchClients: Exception: $e');
      return [];
    }
  }

  // Crear nueva tarea
  Future<Map<String, dynamic>> createJob({
    required int clientId,
    required DateTime visitDateTime,
    required String description,
  }) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'No autenticado'};
      }

      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}'),
        headers: ApiConfig.getHeaders(token: token),
        body: jsonEncode({
          'client_id': clientId,
          'visit_datetime': visitDateTime.toIso8601String(),
          'job_description': description,
        }),
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        final data = jsonDecode(response.body);
        return {
          'success': data['success'] == true,
          'message': data['message'] ?? 'Tarea creada',
          'job': data['data'] != null ? Job.fromJson(data['data']) : null,
        };
      }
      
      final data = jsonDecode(response.body);
      return {
        'success': false,
        'message': data['message'] ?? 'Error al crear tarea',
      };
    } catch (e) {
      print('❌ createJob: Exception: $e');
      return {'success': false, 'message': 'Error: ${e.toString()}'};
    }
  }
}

