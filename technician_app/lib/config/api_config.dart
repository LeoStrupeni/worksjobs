class ApiConfig {
  // IMPORTANTE: Cambiar esta URL según tu entorno
  
  // Para emulador Android usando XAMPP local:
  // static const String baseUrl = 'http://10.0.2.2/panel/api';
  
  // Para dispositivo físico (WiFi - IP actual de tu PC):
  // static const String baseUrl = 'http://192.168.1.4/api';
  
  // Para producción:
  static const String baseUrl = 'https://tecnicos.strupeni.com.ar/api';

  // Endpoints
  static const String loginEndpoint = '/login';
  static const String logoutEndpoint = '/logout';
  static const String userEndpoint = '/user';
  
  // Jobs endpoints
  static const String todayJobsEndpoint = '/jobs/today';
  static const String upcomingJobsEndpoint = '/jobs/upcoming';
  static const String calendarJobsEndpoint = '/jobs/calendar';
  static const String jobDetailEndpoint = '/jobs';
  static const String clientsEndpoint = '/jobs/clients';
  static const String clientAddressesEndpoint = '/client/address';
  
  // CMS endpoints
  static const String flutterThemeEndpoint = '/flutter/theme';
  
  // Timeouts
  static const Duration connectionTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);
  
  // Headers
  static Map<String, String> getHeaders({String? token}) {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    
    return headers;
  }
}
