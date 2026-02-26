import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/job.dart';
import '../models/note.dart';
import '../models/job_file.dart';
import '../models/client.dart';
import '../models/address.dart';
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
        print('❌ getJobDetail: No autenticado');
        return {'success': false, 'message': 'No autenticado'};
      }

      print('📡 getJobDetail: Obteniendo detalle del job $jobId');
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId'),
        headers: ApiConfig.getHeaders(token: token),
      );

      print('📥 getJobDetail: Status ${response.statusCode}');
      print('📄 getJobDetail: Response body: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success'] == true && data['job'] != null) {
          // El backend devuelve 'job' no 'data'
          if (data['job'] is! Map) {
            print('⚠️ getJobDetail: data["job"] no es un Map');
            return {'success': false, 'message': 'Formato de datos incorrecto'};
          }
          
          final job = Job.fromJson(data['job']);
          final notes = data['notes'] != null
              ? (data['notes'] as List).map((n) => Note.fromJson(n)).toList()
              : <Note>[];
          final files = data['files'] != null
              ? (data['files'] as List).map((f) => JobFile.fromJson(f)).toList()
              : <JobFile>[];
          
          print('✅ getJobDetail: Job obtenido - ${job.clientName}');
          return {
            'success': true,
            'job': job,
            'notes': notes,
            'files': files,
          };
        } else {
          print('⚠️ getJobDetail: success=false o job es null');
          return {'success': false, 'message': data['message'] ?? 'Error desconocido'};
        }
      }
      
      print('❌ getJobDetail: Status code no es 200');
      final errorData = jsonDecode(response.body);
      return {'success': false, 'message': errorData['message'] ?? 'Error al obtener detalle'};
    } catch (e, stackTrace) {
      print('❌ getJobDetail: Exception: $e');
      print('📚 getJobDetail: StackTrace: $stackTrace');
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

  // Revertir llegada (volver a pendiente)
  Future<Map<String, dynamic>> revertArrival(int jobId) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'No autenticado'};
      }

      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId/back-to-pending'),
        headers: ApiConfig.getHeaders(token: token),
      );

      final data = jsonDecode(response.body);
      return data;
    } catch (e) {
      return {'success': false, 'message': 'Error: ${e.toString()}'};
    }
  }

  // Cerrar cita
  Future<Map<String, dynamic>> closeJob(int jobId, String observation, {double? lat, double? lng, List<int>? technicianIds}) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'No autenticado'};
      }

      final Map<String, dynamic> bodyData = {
        'observation': observation,
      };
      
      if (lat != null) bodyData['latitud'] = lat;
      if (lng != null) bodyData['longitud'] = lng;
      if (technicianIds != null && technicianIds.isNotEmpty) {
        bodyData['technician_ids'] = technicianIds;
      }

      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId/close'),
        headers: ApiConfig.getHeaders(token: token),
        body: jsonEncode(bodyData),
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
        print('❌ uploadFiles: No hay token');
        return false;
      }

      final url = '${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}/$jobId/files';
      print('📡 uploadFiles: URL = $url');
      print('📂 uploadFiles: ${filePaths.length} archivo(s) a subir');
      
      var request = http.MultipartRequest('POST', Uri.parse(url));
      request.headers.addAll(ApiConfig.getHeaders(token: token));

      // Agregar archivos usando 'images[]' para coincidir con el backend
      for (var filePath in filePaths) {
        print('📎 uploadFiles: Agregando archivo: $filePath');
        request.files.add(await http.MultipartFile.fromPath('images[]', filePath));
      }

      print('🚀 uploadFiles: Enviando petición...');
      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      print('📥 uploadFiles: Status ${response.statusCode}');
      print('📄 uploadFiles: Response: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        print('✅ uploadFiles: Success = ${data['success']}');
        return data['success'] == true;
      }
      
      print('⚠️ uploadFiles: Error - Status ${response.statusCode}');
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
        print('❌❌❌ searchClients: NO HAY TOKEN');
        return [];
      }

      final url = '${ApiConfig.baseUrl}${ApiConfig.clientsEndpoint}?search=$query';
      print('🌐🌐🌐 searchClients URL: $url');
      final response = await http.get(
        Uri.parse(url),
        headers: ApiConfig.getHeaders(token: token),
      );

      print('📊📊📊 searchClients Status: ${response.statusCode}');
      print('📄📄📄 searchClients Response COMPLETO: ${response.body}');
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        print('🔑🔑🔑 searchClients Keys en response: ${data.keys}');
        
        // Intentar ambas estructuras posibles: {clients: []} o {data: []}
        List<dynamic>? clientsList;
        if (data['clients'] != null) {
          clientsList = data['clients'] as List;
          print('✅ Encontrado en data["clients"]');
        } else if (data['data'] != null) {
          clientsList = data['data'] as List;
          print('✅ Encontrado en data["data"]');
        } else if (data is List) {
          clientsList = data;
          print('✅ Response es un array directo');
        } else {
          print('❌❌❌ NO SE ENCONTRÓ LISTA DE CLIENTES EN LA RESPUESTA');
        }
        
        if (clientsList != null) {
          print('📝📝📝 Parseando ${clientsList.length} clientes...');
          final clients = clientsList
              .map((client) {
                print('   - Cliente: ${client['first_name']} ${client['last_name']}');
                return Client.fromJson(client);
              })
              .toList();
          
          print('✅✅✅ searchClients: ${clients.length} clientes parseados correctamente');
          return clients;
        }
      } else {
        print('❌❌❌ searchClients: Status code ${response.statusCode}');
        print('ERROR BODY: ${response.body}');
      }
      
      print('⚠️⚠️⚠️ searchClients: Retornando lista vacía');
      return [];
    } catch (e) {
      print('❌ searchClients: Exception: $e');
      return [];
    }
  }

  // Crear nueva tarea
  Future<Map<String, dynamic>> createJob({
    required int clientId,
    required int addressId,
    required DateTime visitDateTime,
    required String description,
    double? latitude,
    double? longitude,
    String? jsonGeolocation,
    List<int>? technicianIds,
  }) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        return {'success': false, 'message': 'No autenticado'};
      }

      print('📡 createJob: Creando tarea para cliente $clientId en dirección $addressId');
      print('📍 Ubicación: lat=$latitude, lon=$longitude');
      
      // Construir body solo con valores no nulos
      final Map<String, dynamic> bodyData = {
        'client_id': clientId,
        'address_id': addressId,
        'visit_datetime': visitDateTime.toIso8601String(),
        'job_description': description,
      };
      
      if (latitude != null) bodyData['latitude'] = latitude;
      if (longitude != null) bodyData['longitude'] = longitude;
      if (jsonGeolocation != null) bodyData['jsongeolocation'] = jsonGeolocation;
      if (technicianIds != null && technicianIds.isNotEmpty) {
        bodyData['technician_ids'] = technicianIds;
      }
      
      print('📦 createJob: Body: $bodyData');
      
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.jobDetailEndpoint}'),
        headers: ApiConfig.getHeaders(token: token),
        body: jsonEncode(bodyData),
      );

      print('📥 createJob: Status ${response.statusCode}');
      print('📄 createJob: Response: ${response.body}');
      
      if (response.statusCode == 200 || response.statusCode == 201 || response.statusCode == 302) {
        print('✅ createJob: Tarea creada exitosamente');
        return {
          'success': true,
          'message': 'Tarea creada correctamente',
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

  // Obtener direcciones de un cliente
  Future<List<Address>> getClientAddresses(int clientId) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        print('❌❌❌ getClientAddresses: NO HAY TOKEN');
        return [];
      }

      final url = '${ApiConfig.baseUrl}${ApiConfig.clientAddressesEndpoint}/$clientId';
      print('🌐🌐🌐 getClientAddresses URL: $url');
      print('🔑 Token presente: ${token.substring(0, 20)}...');
      
      final response = await http.get(
        Uri.parse(url),
        headers: ApiConfig.getHeaders(token: token),
      );

      print('📊📊📊 getClientAddresses Status: ${response.statusCode}');
      print('📄📄📄 getClientAddresses Response COMPLETO: ${response.body}');
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        print('🔑🔑🔑 Keys en response: ${data.keys}');
        
        if (data['datos'] != null) {
          final addresses = (data['datos'] as List)
              .map((address) {
                print('   - Dirección: ${address['address_street']} ${address['address_nro']}');
                return Address.fromJson(address);
              })
              .toList();
          
          print('✅✅✅ getClientAddresses: ${addresses.length} direcciones parseadas');
          return addresses;
        } else {
          print('❌❌❌ data["datos"] es null!');
        }
      } else {
        print('❌❌❌ Status no es 200, Body: ${response.body}');
      }
      
      print('⚠️⚠️⚠️ getClientAddresses: Retornando lista vacía');
      return [];
    } catch (e, stackTrace) {
      print('❌❌❌ getClientAddresses Exception: $e');
      print('📚 StackTrace: $stackTrace');
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
      final token = await _authService.getToken();
      if (token == null) {
        print('❌ createClientAddress: NO HAY TOKEN');
        return null;
      }

      final url = Uri.parse('${ApiConfig.baseUrl}/client/address');
      print('🌐 createClientAddress URL: $url');
      print('📝 Datos: client_id=$clientId, street=$street, number=$number, city=$city');

      final response = await http.post(
        url,
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'client_id': clientId,
          'address_street': street,
          'address_nro': number,
          'city': city,
          'address_detail': detail,
        }),
      );

      print('📊 createClientAddress Status: ${response.statusCode}');
      print('📄 Response: ${response.body}');

      if (response.statusCode == 201) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['address'] != null) {
          final address = Address.fromJson(data['address']);
          print('✅ Dirección creada: ${address.fullAddress}');
          return address;
        }
      }

      print('⚠️ Error al crear dirección: ${response.body}');
      return null;
    } catch (e, stackTrace) {
      print('❌ createClientAddress Exception: $e');
      print('📚 StackTrace: $stackTrace');
      rethrow;
    }
  }

  // Actualizar tarea existente
  Future<Map<String, dynamic>> updateJob({
    required int jobId,
    required int addressId,
    required DateTime visitDateTime,
    required String description,
    double? latitude,
    double? longitude,
    String? jsonGeolocation,
    List<int>? technicianIds,
  }) async {
    try {
      final token = await _authService.getToken();
      
      if (token == null) {
        print('❌ updateJob: No autenticado');
        return {'success': false, 'message': 'No autenticado'};
      }

      print('📡 updateJob: Actualizando job $jobId');
      
      // Construir body solo con valores no nulos
      final Map<String, dynamic> bodyData = {
        'address_id': addressId,
        'visit_datetime': visitDateTime.toIso8601String(),
        'job_description': description,
      };
      
      if (latitude != null) bodyData['latitude'] = latitude;
      if (longitude != null) bodyData['longitude'] = longitude;
      if (jsonGeolocation != null) bodyData['jsongeolocation'] = jsonGeolocation;
      if (technicianIds != null) {
        bodyData['technician_ids'] = technicianIds;
      }
      
      print('📦 updateJob: Body: $bodyData');
      
      final response = await http.put(
        Uri.parse('${ApiConfig.baseUrl}/jobs/$jobId'),
        headers: ApiConfig.getHeaders(token: token),
        body: jsonEncode(bodyData),
      );

      print('📥 updateJob: Status ${response.statusCode}');
      print('📄 updateJob: Response: ${response.body}');

      if (response.statusCode == 200 || response.statusCode == 302) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          print('✅ updateJob: Tarea actualizada exitosamente');
          return {'success': true};
        } else {
          print('⚠️ updateJob: ${data['message']}');
          return {'success': false, 'message': data['message']};
        }
      }

      return {'success': false, 'message': 'Error al actualizar la tarea'};
    } catch (e) {
      print('❌ updateJob: Exception: $e');
      return {'success': false, 'message': 'Error: ${e.toString()}'};
    }
  }
}

