import 'package:flutter/material.dart';

/**
 * Custom Alerts - Strupeni Electrónica
 * Sistema de alertas reutilizable con el branding de la empresa
 * Similar al sistema de alertas de la versión web
 */

class CustomAlerts {
  // Color corporativo de Strupeni
  static const Color primaryColor = Color(0xFF00274E);
  static const Color secondaryColor = Color(0xFF004B87);

  /// Muestra un diálogo de carga con animación
  /// Equivalente a showSavingAlert() en la web
  /// Retorna una función para cerrar el diálogo
  static Future<void> showLoadingAlert(
    BuildContext context, {
    String? title,
    Widget? customContent,
  }) async {
    return showDialog(
      context: context,
      barrierDismissible: false, // No se puede cerrar tocando fuera
      barrierColor: Colors.black54,
      builder: (BuildContext context) {
        return PopScope(
          canPop: false, // No se puede cerrar con el botón atrás
          child: Dialog(
            backgroundColor: primaryColor,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(30),
            ),
            child: Padding(
              padding: const EdgeInsets.all(30),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Animación de carga circular con el color corporativo
                  const SizedBox(
                    width: 80,
                    height: 80,
                    child: CircularProgressIndicator(
                      strokeWidth: 6,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  ),
                  const SizedBox(height: 24),
                  if (customContent != null)
                    customContent
                  else
                    Text(
                      title ?? 'Guardando...',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.w600,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  const SizedBox(height: 8),
                  const Text(
                    'Por favor espera',
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 14,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  /// Muestra un diálogo de éxito
  /// Equivalente a showSuccessAlert() en la web
  static Future<void> showSuccessAlert(
    BuildContext context, {
    String title = 'Guardado exitoso',
    String message = 'Los cambios se guardaron correctamente',
    Duration duration = const Duration(seconds: 2),
    bool autoDismiss = true,
  }) async {
    final result = showDialog(
      context: context,
      barrierDismissible: !autoDismiss,
      builder: (BuildContext context) {
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Icono de éxito animado
                Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: Colors.green.shade50,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.check_circle,
                    size: 60,
                    color: Colors.green.shade600,
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: primaryColor,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 12),
                Text(
                  message,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                  ),
                  textAlign: TextAlign.center,
                ),
                if (!autoDismiss) ...[
                  const SizedBox(height: 20),
                  ElevatedButton(
                    onPressed: () => Navigator.of(context).pop(),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: primaryColor,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 32,
                        vertical: 12,
                      ),
                    ),
                    child: const Text(
                      'Aceptar',
                      style: TextStyle(color: Colors.white),
                    ),
                  ),
                ],
              ],
            ),
          ),
        );
      },
    );

    // Auto-cerrar después del tiempo especificado
    if (autoDismiss) {
      Future.delayed(duration, () {
        if (context.mounted) {
          Navigator.of(context, rootNavigator: true).pop();
        }
      });
    }

    return result;
  }

  /// Muestra un diálogo de error
  /// Equivalente a showErrorAlert() en la web
  static Future<void> showErrorAlert(
    BuildContext context, {
    String title = 'Error al guardar',
    String message = 'Ocurrió un error al guardar los cambios. Por favor intenta nuevamente.',
  }) {
    return showDialog(
      context: context,
      builder: (BuildContext context) {
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Icono de error
                Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: Colors.red.shade50,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.error_outline,
                    size: 60,
                    color: Colors.red.shade600,
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: primaryColor,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 12),
                Text(
                  message,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: () => Navigator.of(context).pop(),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.red.shade600,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 32,
                      vertical: 12,
                    ),
                  ),
                  child: const Text(
                    'Cerrar',
                    style: TextStyle(color: Colors.white),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  /// Muestra un diálogo de confirmación
  /// Equivalente a showConfirmAlert() en la web
  /// Retorna true si se confirmó, false si se canceló
  static Future<bool> showConfirmAlert(
    BuildContext context, {
    String title = '¿Estás seguro?',
    String message = 'Esta acción no se puede deshacer',
    String confirmText = 'Sí, confirmar',
    String cancelText = 'Cancelar',
  }) async {
    final result = await showDialog<bool>(
      context: context,
      builder: (BuildContext context) {
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Icono de advertencia
                Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: Colors.orange.shade50,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.warning_amber_rounded,
                    size: 60,
                    color: Colors.orange.shade600,
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: primaryColor,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 12),
                Text(
                  message,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 24),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => Navigator.of(context).pop(false),
                        style: OutlinedButton.styleFrom(
                          side: const BorderSide(color: primaryColor),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                          padding: const EdgeInsets.symmetric(vertical: 12),
                        ),
                        child: Text(
                          cancelText,
                          style: const TextStyle(color: primaryColor),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton(
                        onPressed: () => Navigator.of(context).pop(true),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: primaryColor,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                          padding: const EdgeInsets.symmetric(vertical: 12),
                        ),
                        child: Text(
                          confirmText,
                          style: const TextStyle(color: Colors.white),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );

    return result ?? false;
  }

  /// Muestra un diálogo de advertencia (warning)
  static Future<void> showWarningAlert(
    BuildContext context, {
    String title = 'Advertencia',
    String message = '',
  }) {
    return showDialog(
      context: context,
      builder: (BuildContext context) {
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Icono de advertencia
                Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: Colors.orange.shade50,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.warning_amber_rounded,
                    size: 60,
                    color: Colors.orange.shade600,
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: primaryColor,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 12),
                Text(
                  message,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: () => Navigator.of(context).pop(),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.orange.shade600,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 32,
                      vertical: 12,
                    ),
                  ),
                  child: const Text(
                    'Entendido',
                    style: TextStyle(color: Colors.white),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  /// Muestra un diálogo de información
  static Future<void> showInfoAlert(
    BuildContext context, {
    String title = 'Información',
    String message = '',
  }) {
    return showDialog(
      context: context,
      builder: (BuildContext context) {
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Icono de información
                Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: Colors.blue.shade50,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.info_outline,
                    size: 60,
                    color: Colors.blue.shade600,
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: primaryColor,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 12),
                Text(
                  message,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: () => Navigator.of(context).pop(),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 32,
                      vertical: 12,
                    ),
                  ),
                  child: const Text(
                    'Aceptar',
                    style: TextStyle(color: Colors.white),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  /// Función helper para ejecutar una operación async con loading alert
  /// Maneja automáticamente:
  /// - Mostrar loading
  /// - Ejecutar la operación
  /// - Mostrar éxito o error
  /// - Cerrar loading
  /// 
  /// Uso:
  /// ```dart
  /// await CustomAlerts.executeWithLoading(
  ///   context,
  ///   operation: () async {
  ///     await jobProvider.createJob(...);
  ///     return jobProvider.errorMessage == null;
  ///   },
  ///   successTitle: 'Tarea creada',
  ///   successMessage: 'La tarea se creó exitosamente',
  ///   errorTitle: 'Error al crear',
  ///   getErrorMessage: () => jobProvider.errorMessage,
  /// );
  /// ```
  static Future<bool> executeWithLoading(
    BuildContext context, {
    required Future<bool> Function() operation,
    String loadingMessage = 'Guardando...',
    String successTitle = 'Guardado exitoso',
    String successMessage = 'Los cambios se guardaron correctamente',
    String errorTitle = 'Error al guardar',
    String? Function()? getErrorMessage,
    bool showSuccessAlert = true,
    Duration successDuration = const Duration(seconds: 2),
  }) async {
    // Mostrar loading
    showLoadingAlert(context, title: loadingMessage);

    bool success = false;
    try {
      // Ejecutar operación
      success = await operation();
    } catch (e) {
      success = false;
      debugPrint('❌ Error en executeWithLoading: $e');
    }

    // Cerrar loading
    if (context.mounted) {
      Navigator.of(context, rootNavigator: true).pop();
    }

    // Mostrar resultado inmediatamente
    if (context.mounted) {
      if (success) {
        if (showSuccessAlert) {
          await CustomAlerts.showSuccessAlert(
            context,
            title: successTitle,
            message: successMessage,
            duration: successDuration,
          );
        }
      } else {
        String errorMessage = getErrorMessage?.call() ??
            'Ocurrió un error inesperado. Por favor intenta nuevamente.';
        
        await CustomAlerts.showErrorAlert(
          context,
          title: errorTitle,
          message: errorMessage,
        );
      }
    }

    return success;
  }

  // ===== MÉTODOS ALIAS PARA COMPATIBILIDAD =====
  
  /// Alias de showInfoAlert (acepta 3 argumentos posicionales: context, title, message)
  static Future<void> showInfo(
    BuildContext context,
    String title,
    String message,
  ) => showInfoAlert(context, title: title, message: message);

  /// Alias de showSuccessAlert (acepta 3 argumentos posicionales: context, title, message)
  static Future<void> showSuccess(
    BuildContext context,
    String title,
    String message, {
    Duration duration = const Duration(seconds: 2),
    bool autoDismiss = true,
  }) => showSuccessAlert(
        context,
        title: title,
        message: message,
        duration: duration,
        autoDismiss: autoDismiss,
      );

  /// Alias de showErrorAlert (acepta 3 argumentos posicionales: context, title, message)
  static Future<void> showError(
    BuildContext context,
    String title,
    String message,
  ) => showErrorAlert(context, title: title, message: message);

  /// Alias de showWarningAlert (acepta 3 argumentos posicionales: context, title, message)
  static Future<void> showWarning(
    BuildContext context,
    String title,
    String message,
  ) => showWarningAlert(context, title: title, message: message);

  /// Alias de showConfirmAlert (acepta argumentos posicionales + named)
  static Future<bool> showConfirmation(
    BuildContext context,
    String title,
    String message, {
    String confirmText = 'Sí, confirmar',
    String cancelText = 'Cancelar',
  }) => showConfirmAlert(
        context,
        title: title,
        message: message,
        confirmText: confirmText,
        cancelText: cancelText,
      );
}

/// Mixin para agregar funcionalidad de bloqueo de botones
/// Previene múltiples envíos de formularios
/// 
/// Uso:
/// ```dart
/// class _MyScreenState extends State<MyScreen> with ButtonLockMixin {
///   Future<void> _submitForm() async {
///     if (isButtonLocked) return; // Prevenir doble clic
///     lockButton();
///     
///     try {
///       // Tu lógica aquí
///       await someOperation();
///     } finally {
///       unlockButton();
///     }
///   }
/// }
/// ```
mixin ButtonLockMixin<T extends StatefulWidget> on State<T> {
  bool _isButtonLocked = false;

  bool get isButtonLocked => _isButtonLocked;

  void lockButton() {
    setState(() {
      _isButtonLocked = true;
    });
  }

  void unlockButton() {
    if (mounted) {
      setState(() {
        _isButtonLocked = false;
      });
    }
  }
}
