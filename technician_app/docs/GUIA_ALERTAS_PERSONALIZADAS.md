# 🚨 Guía de Alertas Personalizadas - Strupeni Electrónica

Sistema de alertas personalizadas implementado para mantener consistencia con el branding corporativo y mejorar la experiencia de usuario en toda la aplicación.

## 📋 Índice

1. [Características](#características)
2. [Instalación](#instalación)
3. [Tipos de Alertas](#tipos-de-alertas)
4. [Ejemplos de Uso](#ejemplos-de-uso)
5. [Bloqueo de Botones](#bloqueo-de-botones)
6. [Migración desde alertas antiguas](#migración-desde-alertas-antiguas)

---

## ✨ Características

- ✅ **Alertas de carga** con animación circular y branding corporativo
- ✅ **Alertas de éxito, error, información y confirmación** personalizadas
- ✅ **Función helper `executeWithLoading`** que maneja automáticamente el ciclo completo de una operación
- ✅ **Mixin `ButtonLockMixin`** para prevenir múltiples envíos de formularios
- ✅ **Consistencia visual** con los colores corporativos de Strupeni (#00274E)
- ✅ **Protección contra doble clic** en botones de guardado

---

## 🔧 Instalación

El sistema ya está implementado en:
- ✅ `lib/utils/custom_alerts.dart` - Archivo principal de alertas
- ✅ `lib/screens/create_job_screen.dart` - Creación de tareas
- ✅ `lib/screens/edit_job_screen.dart` - Edición de tareas
- ✅ `lib/widgets/job_card.dart` - Operaciones en tarjetas de trabajo

Para usar en otras pantallas, simplemente importa:

```dart
import '../utils/custom_alerts.dart';
```

---

## 📱 Tipos de Alertas

### 1. Loading Alert (Alerta de Carga)

Muestra un indicador de progreso circular. Equivalente a `showSavingAlert()` de la web.

```dart
// Mostrar
CustomAlerts.showLoadingAlert(
  context,
  title: 'Guardando...',
);

// Cerrar (cuando la operación termina)
Navigator.of(context, rootNavigator: true).pop();
```

### 2. Success Alert (Alerta de Éxito)

```dart
await CustomAlerts.showSuccessAlert(
  context,
  title: 'Guardado exitoso',
  message: 'Los cambios se guardaron correctamente',
  duration: Duration(seconds: 2), // Auto-cierra después de 2 segundos
);
```

### 3. Error Alert (Alerta de Error)

```dart
await CustomAlerts.showErrorAlert(
  context,
  title: 'Error al guardar',
  message: 'No se pudo conectar con el servidor. Intenta nuevamente.',
);
```

### 4. Info Alert (Alerta de Información)

```dart
await CustomAlerts.showInfoAlert(
  context,
  title: 'Información',
  message: 'Debes seleccionar un cliente antes de continuar',
);
```

### 5. Confirm Alert (Alerta de Confirmación)

```dart
final confirmed = await CustomAlerts.showConfirmAlert(
  context,
  title: '¿Estás seguro?',
  message: 'Esta acción no se puede deshacer',
  confirmText: 'Sí, confirmar',
  cancelText: 'Cancelar',
);

if (confirmed) {
  // Usuario confirmó
  print('Acción confirmada');
}
```

---

## 🎯 Ejemplos de Uso

### Ejemplo 1: Usar executeWithLoading (RECOMENDADO)

La forma más simple y recomendada. Maneja automáticamente loading, éxito y errores:

```dart
Future<void> _createTask() async {
  if (!_formKey.currentState!.validate()) return;

  final jobProvider = context.read<JobProvider>();
  
  final success = await CustomAlerts.executeWithLoading(
    context,
    operation: () async {
      // Tu lógica aquí
      return await jobProvider.createJob(
        clientId: _selectedClient!.id,
        description: _descriptionController.text,
      );
    },
    loadingMessage: 'Creando tarea...',
    successTitle: 'Tarea creada',
    successMessage: 'La tarea se creó exitosamente',
    errorTitle: 'Error al crear',
    getErrorMessage: () => jobProvider.errorMessage ?? 'Error inesperado',
  );

  if (success && mounted) {
    Navigator.pop(context, true);
  }
}
```

### Ejemplo 2: Control Manual

Si necesitas más control sobre el flujo:

```dart
Future<void> _uploadImage() async {
  // 1. Mostrar loading
  CustomAlerts.showLoadingAlert(context, title: 'Subiendo imagen...');

  try {
    // 2. Realizar operación
    final success = await jobProvider.uploadImage(imagePath);
    
    // 3. Cerrar loading
    if (mounted) {
      Navigator.of(context, rootNavigator: true).pop();
    }

    // 4. Mostrar resultado
    if (mounted) {
      if (success) {
        await CustomAlerts.showSuccessAlert(
          context,
          title: 'Imagen subida',
          message: 'La imagen se subió correctamente',
        );
      } else {
        await CustomAlerts.showErrorAlert(
          context,
          title: 'Error',
          message: 'No se pudo subir la imagen',
        );
      }
    }
  } catch (e) {
    if (mounted) {
      Navigator.of(context, rootNavigator: true).pop();
      await CustomAlerts.showErrorAlert(
        context,
        title: 'Error',
        message: e.toString(),
      );
    }
  }
}
```

### Ejemplo 3: Confirmación antes de eliminar

```dart
Future<void> _deleteTask() async {
  final confirmed = await CustomAlerts.showConfirmAlert(
    context,
    title: 'Eliminar tarea',
    message: '¿Estás seguro que deseas eliminar esta tarea?',
    confirmText: 'Sí, eliminar',
    cancelText: 'Cancelar',
  );

  if (confirmed && mounted) {
    final jobProvider = context.read<JobProvider>();
    
    final success = await CustomAlerts.executeWithLoading(
      context,
      operation: () => jobProvider.deleteJob(job.id!),
      loadingMessage: 'Eliminando...',
      successTitle: 'Eliminado',
      successMessage: 'La tarea fue eliminada',
      errorTitle: 'Error',
      getErrorMessage: () => jobProvider.errorMessage,
    );
    
    if (success && onRefresh != null) {
      onRefresh!();
    }
  }
}
```

---

## 🔒 Bloqueo de Botones

### Implementación con ButtonLockMixin

Para prevenir múltiples envíos de formularios, usa el mixin `ButtonLockMixin`:

```dart
class _MyFormScreenState extends State<MyFormScreen> with ButtonLockMixin {
  
  Future<void> _submitForm() async {
    // Prevenir múltiples clics
    if (isButtonLocked) return;
    
    // Bloquear botón
    lockButton();
    
    try {
      // Tu lógica aquí
      await someAsyncOperation();
    } finally {
      // Desbloquear botón
      unlockButton();
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return ElevatedButton(
      // Deshabilitar cuando está bloqueado
      onPressed: isButtonLocked ? null : _submitForm,
      child: Text(
        isButtonLocked ? 'Guardando...' : 'Guardar',
      ),
    );
  }
}
```

### Botón con indicador visual

```dart
Container(
  width: double.infinity,
  height: 56,
  decoration: BoxDecoration(
    gradient: LinearGradient(
      colors: isButtonLocked 
        ? [Colors.grey.shade400, Colors.grey.shade500]  // Gris cuando bloqueado
        : [Color(0xFF00274E), Color(0xFF004B87)],       // Colores corporativos
    ),
    borderRadius: BorderRadius.circular(30),
  ),
  child: ElevatedButton.icon(
    onPressed: isButtonLocked ? null : _submitForm,
    icon: Icon(
      isButtonLocked ? Icons.lock : Icons.save,
      color: Colors.white,
    ),
    label: Text(
      isButtonLocked ? 'Guardando...' : 'Guardar',
      style: TextStyle(color: Colors.white),
    ),
    style: ElevatedButton.styleFrom(
      backgroundColor: Colors.transparent,
      disabledBackgroundColor: Colors.transparent,
    ),
  ),
)
```

---

## 🔄 Migración desde alertas antiguas

### Antes (SnackBar)

```dart
ScaffoldMessenger.of(context).showSnackBar(
  SnackBar(
    content: Text('✅ Guardado exitosamente'),
    backgroundColor: Colors.green,
  ),
);
```

### Después (CustomAlerts)

```dart
await CustomAlerts.showSuccessAlert(
  context,
  title: 'Guardado exitoso',
  message: 'Los cambios se guardaron correctamente',
);
```

---

### Antes (AlertDialog simple)

```dart
final confirmed = await showDialog<bool>(
  context: context,
  builder: (context) => AlertDialog(
    title: Text('¿Confirmar?'),
    content: Text('¿Deseas continuar?'),
    actions: [
      TextButton(
        onPressed: () => Navigator.pop(context, false),
        child: Text('Cancelar'),
      ),
      ElevatedButton(
        onPressed: () => Navigator.pop(context, true),
        child: Text('Confirmar'),
      ),
    ],
  ),
);
```

### Después (CustomAlerts)

```dart
final confirmed = await CustomAlerts.showConfirmAlert(
  context,
  title: '¿Confirmar?',
  message: '¿Deseas continuar?',
);
```

---

## 📊 Flujo completo de una operación

```
Usuario hace clic en botón
          ↓
¿Botón bloqueado? → SÍ → Ignorar
          ↓ NO
    Bloquear botón
          ↓
Mostrar Loading Alert
          ↓
  Realizar operación async
          ↓
¿Éxito? → SÍ → Mostrar Success Alert
   ↓ NO
Mostrar Error Alert
          ↓
Desbloquear botón
          ↓
 Cerrar pantalla (si éxito)
```

---

## ✅ Buenas Prácticas

1. **Siempre usa `executeWithLoading`** cuando sea posible - maneja todo automáticamente
2. **Bloquea los botones** con `ButtonLockMixin` para prevenir doble clic
3. **Proporciona mensajes claros** en las alertas de error
4. **Verifica `mounted`** antes de mostrar alertas después de operaciones async
5. **Usa confirmación** para acciones destructivas (eliminar, cancelar, etc.)

---

## 🎨 Personalización

Los colores corporativos están definidos en `CustomAlerts`:

```dart
static const Color primaryColor = Color(0xFF00274E);
static const Color secondaryColor = Color(0xFF004B87);
```

Para cambiar los colores en toda la app, modifica estos valores una sola vez.

---

## 📝 Notas Importantes

- **No uses SnackBar** para operaciones importantes - usa CustomAlerts
- **No uses AlertDialog** genéricos - usa CustomAlerts para mantener consistencia
- **Siempre bloquea botones** durante operaciones async
- **Auto-cierre**: Success alerts se cierran automáticamente después de 2 segundos
- **Root Navigator**: El loading alert usa `rootNavigator: true` para cerrarse correctamente

---

## 🐛 Troubleshooting

### El loading no se cierra

Asegúrate de usar `rootNavigator: true`:

```dart
Navigator.of(context, rootNavigator: true).pop();
```

### El botón no se desbloquea

Usa siempre `try-finally` para garantizar que se desbloquee:

```dart
try {
  // operación
} finally {
  unlockButton();
}
```

### Errores de "context not mounted"

Verifica `mounted` antes de mostrar alertas:

```dart
if (mounted) {
  await CustomAlerts.showSuccessAlert(context, ...);
}
```

---

## 📚 Referencias

- **Archivo principal**: `lib/utils/custom_alerts.dart`
- **Ejemplos implementados**:
  - `lib/screens/create_job_screen.dart`
  - `lib/screens/edit_job_screen.dart`
  - `lib/widgets/job_card.dart`

---

¡Listo! Ahora tienes un sistema completo de alertas personalizadas que mantiene la consistencia visual y previene errores comunes. 🎉
