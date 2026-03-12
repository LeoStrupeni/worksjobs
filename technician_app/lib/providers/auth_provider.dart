import 'package:flutter/foundation.dart';
import 'dart:async';
import '../models/user.dart';
import '../services/auth_service.dart';

class AuthProvider with ChangeNotifier {
  final AuthService _authService = AuthService();
  
  User? _user;
  String? _token;
  bool _isAuthenticated = false;
  bool _isLoading = false;
  String? _errorMessage;
  
  // Auto-logout por inactividad (15 minutos)
  Timer? _inactivityTimer;
  static const Duration _inactivityDuration = Duration(minutes: 15);

  User? get user => _user;
  String? get token => _token;
  bool get isAuthenticated => _isAuthenticated;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  // Inicializar: verificar si hay sesión guardada
  Future<void> initialize() async {
    _isLoading = true;
    notifyListeners();

    try {
      final authenticated = await _authService.isAuthenticated();
      
      if (authenticated) {
        _token = await _authService.getToken();
        _user = await _authService.getSavedUser();
        _isAuthenticated = true;
      }
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Login
  Future<bool> login(String email, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _authService.login(email, password);
      
      if (result['success'] == true) {
        _user = result['user'];
        _token = result['token'];
        _isAuthenticated = true;
        _isLoading = false;
        notifyListeners();
        _startInactivityTimer(); // Iniciar timer después del login
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

  // Resetear timer de inactividad
  void resetInactivityTimer() {
    if (_isAuthenticated) {
      _inactivityTimer?.cancel();
      _startInactivityTimer();
    }
  }

  // Iniciar timer de inactividad
  void _startInactivityTimer() {
    _inactivityTimer = Timer(_inactivityDuration, () {
      // print('⏰ Auto-logout por inactividad');
      logout();
    });
  }

  // Logout
  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();

    try {
      await _authService.logout();
    } finally {
      _inactivityTimer?.cancel(); // Cancelar timer al hacer logout
      _user = null;
      _token = null;
      _isAuthenticated = false;
      _errorMessage = null;
      _isLoading = false;
      notifyListeners();
    }
  }

  // Limpiar error
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  // Guardar credenciales
  Future<void> saveCredentials(String email, String password) async {
    await _authService.saveCredentials(email, password);
  }

  // Obtener credenciales guardadas
  Future<Map<String, String>?> getSavedCredentials() async {
    return await _authService.getSavedCredentials();
  }

  // Limpiar credenciales guardadas
  Future<void> clearSavedCredentials() async {
    await _authService.clearSavedCredentials();
  }

  @override
  void dispose() {
    _inactivityTimer?.cancel();
    super.dispose();
  }
}
