# FIX: Pantalla Gris al Crear Presupuesto

**Fecha**: 10/04/2026  
**Versión afectada**: v1.3.0+30  
**Versión fix**: v1.3.1+31

---

## 🐛 Problema Identificado

### Síntomas
- ✅ Backend crea presupuesto exitosamente (HTTP 201)
- ✅ Presupuesto visible en Colppy con idFactura válido
- ❌ Pantalla de app se pone gris después de crear
- ❌ No aparece mensaje de éxito
- ❌ Vuelve al formulario de nuevo presupuesto
- ❌ Usuario no sabe que el presupuesto fue creado

### Causa Raíz

**Archivo**: `technician_app/lib/providers/budget_provider.dart`  
**Métodos afectados**: `createBudget()` y `updateBudget()`

**Problema**:
```dart
if (result['success'] == true) {
  _currentBudget = result['budget'];
  
  // ❌ PROBLEMA: Si fetchBudgets falla, todo el createBudget devuelve error
  await fetchBudgets(page: 1);  // Bloquea con 'await'
  
  return {
    'success': true,
    'budget': _currentBudget,
  };
} 
// Si fetchBudgets lanza excepción →
catch (e) {
  // ❌ Devuelve error aunque el presupuesto se creó exitosamente
  return {'success': false, 'message': 'Error inesperado: $e'};
}
```

**Escenario típico de fallo**:
1. Backend crea presupuesto exitosamente (HTTP 201) ✅
2. `BudgetService.createBudget()` devuelve `success: true` ✅
3. `BudgetProvider.createBudget()` recibe el éxito ✅
4. Provider llama `await fetchBudgets(page: 1)` para refrescar lista
5. **fetchBudgets() falla** (timeout, error de red, presupuesto aún no indexado)
6. Excepción capturada en catch block
7. Provider devuelve `{'success': false, 'message': 'Error inesperado'}` ❌
8. UI recibe `success: false` y no navega al detalle
9. Loading dialog se queda colgado → **pantalla gris** ❌

---

## ✅ Solución Aplicada

### Cambios en `budget_provider.dart`

**createBudget()** - Líneas ~255-275:
```dart
if (result['success'] == true) {
  _currentBudget = result['budget'];
  _errorMessage = null;

  await DebugLogger.instance.success(
    '✅ Presupuesto creado: ${_currentBudget?.nroFactura}',
    category: 'BUDGET_PROVIDER',
  );

  // ✅ FIX: Recargar lista en background sin bloquear
  // Si falla fetchBudgets, no afecta el resultado del createBudget
  fetchBudgets(page: 1).catchError((e) {
    DebugLogger.instance.warning(
      '⚠️ No se pudo recargar lista después de crear (no crítico)',
      category: 'BUDGET_PROVIDER',
    );
  });

  return {
    'success': true,
    'budget': _currentBudget,
    'message': result['message'],
  };
}
```

**updateBudget()** - Líneas ~355-375:
```dart
if (result['success'] == true) {
  _currentBudget = result['budget'];
  _errorMessage = null;

  await DebugLogger.instance.success(
    '✅ Presupuesto actualizado: ${_currentBudget?.nroFactura}',
    category: 'BUDGET_PROVIDER',
  );

  // ✅ FIX: Recargar lista en background sin bloquear
  fetchBudgets(page: 1).catchError((e) {
    DebugLogger.instance.warning(
      '⚠️ No se pudo recargar lista después de actualizar (no crítico)',
      category: 'BUDGET_PROVIDER',
    );
  });

  return {
    'success': true,
    'budget': _currentBudget,
    'message': result['message'],
  };
}
```

**¿Qué hace?**:
- Elimina el `await` antes de `fetchBudgets()`
- Ejecuta la recarga de lista en **background** (no bloqueante)
- Agrega `.catchError()` para manejar fallos silenciosamente
- Si fetchBudgets falla, solo registra warning pero NO afecta el resultado
- El método devuelve éxito inmediatamente aunque la lista no se recargue

---

## 🎯 Beneficios del Fix

1. ✅ **Éxito no depende de recarga de lista**: Si el presupuesto se creó, devuelve éxito
2. ✅ **UI muestra éxito correctamente**: Aparece mensaje "✅ Presupuesto creado!"
3. ✅ **Navega al detalle**: Redirige a `BudgetDetailScreen`
4. ✅ **No más pantalla gris**: Loading se cierra correctamente
5. ✅ **Lista se actualiza eventualmente**: Si fetchBudgets funciona, la lista se refresca
6. ⚠️ **Degradación elegante**: Si fetchBudgets falla, el usuario ve el detalle del presupuesto recién creado y puede volver a la lista manualmente

---

## 🧪 Testing

### Escenario 1: Red estable
1. Crear presupuesto desde app
2. **Esperado**: 
   - ✅ Mensaje de éxito
   - ✅ Navega al detalle
   - ✅ Lista se actualiza en background
   - ✅ Al volver, presupuesto aparece en lista

### Escenario 2: Red lenta/inestable
1. Crear presupuesto desde app
2. **Esperado**:
   - ✅ Mensaje de éxito (aunque fetchBudgets tarde)
   - ✅ Navega al detalle
   - ⚠️ Lista puede no actualizarse inmediatamente
   - ✅ Al hacer pull-to-refresh, presupuesto aparece

### Escenario 3: Red se cae después de crear
1. Crear presupuesto
2. Red falla justo después
3. **Esperado**:
   - ✅ Mensaje de éxito (presupuesto creado)
   - ✅ Navega al detalle
   - ⚠️ Lista no se actualiza (advertencia en logs)
   - ✅ Al volver con red, lista se refresca

---

## ⚠️ Problemas Pendientes

### ~~1. Múltiples presiones en botón "Crear"~~ ✅ RESUELTO

**Ubicación**: `technician_app/lib/screens/create_budget_screen.dart`

**Problema**: 
- Usuario puede presionar botón múltiples veces rápidamente
- Cada presión genera una petición al backend
- Se crean múltiples presupuestos duplicados

**Solución aplicada**:

**1. Agregar flag de control** (línea ~40):
```dart
bool _isCreating = false; // ✅ Prevenir múltiples presiones en botón crear
```

**2. Validar en método `_createBudget()`** (líneas ~210-217):
```dart
Future<void> _createBudget() async {
  // ✅ Prevenir múltiples presiones
  if (_isCreating) {
    debugPrint('⚠️ Ya se está creando un presupuesto');
    return;
  }
  
  // Validaciones...
  if (_selectedClient == null) { ... }
  if (_items.isEmpty) { ... }
  
  // Confirmación...
  final confirm = await CustomAlerts.showConfirmation(...);
  if (confirm != true) return;
  
  // ✅ Marcar como creando
  setState(() {
    _isCreating = true;
  });
  
  try {
    // ... código de creación ...
    final result = await provider.createBudget(...);
    
    // Cerrar loading
    if (mounted) Navigator.pop(context);
    
    // Manejar resultado...
  } finally {
    // ✅ SIEMPRE resetear flag
    if (mounted) {
      setState(() {
        _isCreating = false;
      });
    }
  }
}
```

**3. Actualizar botón en AppBar** (líneas ~310-328):
```dart
actions: [
  TextButton.icon(
    onPressed: _items.isNotEmpty && _selectedClient != null && !_isCreating
        ? _createBudget
        : null,
    icon: _isCreating
        ? const SizedBox(
            width: 16,
            height: 16,
            child: CircularProgressIndicator(
              strokeWidth: 2,
              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          )
        : const Icon(Icons.check, color: Colors.white),
    label: Text(
      _isCreating ? 'CREANDO...' : 'CREAR',
      style: const TextStyle(
        color: Colors.white,
        fontWeight: FontWeight.bold,
      ),
    ),
  ),
],
```

**Beneficios**:
- ✅ Botón se deshabilita al presionar
- ✅ Muestra indicator de progreso visual
- ✅ Texto cambia a "CREANDO..."
- ✅ Previene múltiples peticiones simultáneas
- ✅ Flag se resetea incluso si hay error (finally block)

**Mismo fix aplicado a**: `edit_budget_screen.dart` (botón "GUARDAR")

---

## 📋 Checklist de Despliegue

- [x] Fix aplicado en `budget_provider.dart` → createBudget
- [x] Fix aplicado en `budget_provider.dart` → updateBudget
- [x] Fix aplicado en `create_budget_screen.dart` → debounce button
- [x] Fix aplicado en `edit_budget_screen.dart` → debounce button
- [x] Incrementar versión a v1.3.1+31
- [ ] Compilar APK de testing
- [ ] Probar escenario de éxito (red estable)
- [ ] Probar escenario de red lenta
- [ ] Verificar que no hay errores de compilación
- [ ] Desplegar a producción

---

## 📝 Notas Técnicas

### NetworkHelper ya acepta HTTP 201
El `NetworkHelper._executeWithRetry()` correctamente verifica:
```dart
if (response.statusCode >= 200 && response.statusCode < 300) {
  return ApiResult(success: true, data: response);
}
```

HTTP 201 (Created) está en el rango 200-299, así que se trata como éxito. No es necesario modificar NetworkHelper.

### BudgetService funciona correctamente
El `BudgetService.createBudget()` correctamente parsea la respuesta:
```dart
if (!result.success) {
  return {'success': false, ...};
}

final response = result.data as http.Response;
final data = jsonDecode(response.body);

if (data['success'] == true) {
  final budget = Budget.fromJson(data['data']);
  return {'success': true, 'budget': budget, ...};
}
```

El problema estaba únicamente en el Provider.

---

## 🔍 Debugging

Si el problema persiste, revisar logs de Flutter:

```dart
// En BudgetProvider.createBudget()
await DebugLogger.instance.success(
  '✅ Presupuesto creado: ${_currentBudget?.nroFactura}',
  category: 'BUDGET_PROVIDER',
);

// Si aparece advertencia:
'⚠️ No se pudo recargar lista después de crear (no crítico)'
```

Y verificar logs de backend:
```
[YYYY-MM-DD HH:MM:SS] ✅ Presupuesto creado exitosamente
idFactura: "XXXXXXXX"
nroFactura: "XXXX-XXXXXXXX"
```

Si ambos muestran éxito pero la app falla, el problema está en `create_budget_screen.dart`.

---

**Documentado por**: Agente de Desarrollo  
**Revisado**: Pendiente  
**Estado**: Fix aplicado, testing pendiente
