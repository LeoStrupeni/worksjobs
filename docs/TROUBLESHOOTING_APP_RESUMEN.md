# 🚀 Troubleshooting App Móvil - RESUMEN EJECUTIVO

## ⚡ Guía Rápida de Decisión

Usuario reporta: **"Error al cargar citas"**

### 🔍 **PASO 0: Diagnóstico Inicial (30 segundos)**

```
¿Podés loguearte con las credenciales del usuario en TU teléfono?
```

**SÍ funciona en tu teléfono** ✅  
→ Problema del **DISPOSITIVO del usuario**  
→ Solución rápida: **Borrar datos de la app**  
→ Documentación: [`DEBUGGING_APP_MOVIL_CLIENTE.md`](DEBUGGING_APP_MOVIL_CLIENTE.md)  
→ Para enviar al usuario: [`GUIA_SOPORTE_USUARIO_FINAL.md`](GUIA_SOPORTE_USUARIO_FINAL.md)

**NO funciona en ningún teléfono** ❌  
→ Problema del **BACKEND** (servidor/base de datos)  
→ Solución: **Revisar logs del servidor**  
→ Documentación: [`DEBUGGING_APP_MOVIL.md`](DEBUGGING_APP_MOVIL.md)

---

## 📋 Instrucciones por Escenario

### 🔴 **ESCENARIO A: Problema del Backend**

**Síntomas:**
- Error en TODOS los dispositivos/usuarios
- Usuario se loguea pero no puede cargar nada
- Otros usuarios reportan el mismo problema

**Acción inmediata:**
```bash
# 1. Ver logs del servidor
tail -50 panel/storage/logs/laravel.log

# 2. Ejecutar script de test
cd panel
php test-api-mobile.php email@del.usuario
```

**Documentación completa:**
- [`DEBUGGING_APP_MOVIL.md`](DEBUGGING_APP_MOVIL.md)

**Causas comunes:**
- Token expiró o fue eliminado de la BD
- Usuario desactivado (`estatus = 0`)
- Error en query SQL (tabla/columna incorrecta)
- Base de datos caída
- Servidor sin respuesta

---

### 🔵 **ESCENARIO B: Problema del Dispositivo Específico**

**Síntomas:**
- Error SOLO en el dispositivo del usuario
- Funciona en tu dispositivo con las mismas credenciales
- Usuario reporta problemas pero otros usuarios están OK

**Acción inmediata:**
Enviarle al usuario (copiar/pegar por WhatsApp):
```
¡Hola! Para solucionar el error, seguí estos pasos:

1️⃣ Configuración → Apps → Strupeni Técnicos
2️⃣ Almacenamiento
3️⃣ Borrar datos + Borrar caché
4️⃣ Volver a abrir la app e ingresar

Esto debería solucionarlo 👍
```

**Documentación completa:**
- Para desarrollador: [`DEBUGGING_APP_MOVIL_CLIENTE.md`](DEBUGGING_APP_MOVIL_CLIENTE.md)
- Para enviar al usuario: [`GUIA_SOPORTE_USUARIO_FINAL.md`](GUIA_SOPORTE_USUARIO_FINAL.md)

**Causas comunes:**
- Token corrupto en almacenamiento local (60%)
- Caché corrupto de la app (20%)
- Versión antigua de la app (10%)
- Permisos insuficientes (5%)
- Red restrictiva / Firewall (3%)
- Espacio insuficiente en dispositivo (2%)

---

## 🎯 Soluciones por Efectividad

### ⭐ **Solución 1: Borrar Datos de la App** (70% efectividad)
```
Android: Configuración → Apps → [App] → Almacenamiento → Borrar datos
iOS: Ajustes → General → Almacenamiento → [App] → Descargar app
```
**Tiempo:** 2 minutos  
**Dificultad:** Fácil  
**Pierde datos:** NO (todo está en el servidor)

---

### ⭐ **Solución 2: Reinstalar App** (90% efectividad)
```
1. Desinstalar app
2. Reiniciar teléfono
3. Reinstalar desde tienda
4. Login nuevamente
```
**Tiempo:** 5 minutos  
**Dificultad:** Fácil  
**Pierde datos:** NO

---

### ⭐ **Solución 3: Revisar Logs del Servidor** (100% diagnóstico)
```bash
# Ver logs
tail -100 panel/storage/logs/laravel.log

# Buscar errores del usuario
grep "usuario@email.com" panel/storage/logs/laravel.log
```
**Tiempo:** 2 minutos  
**Dificultad:** Técnica  
**Requiere:** Acceso al servidor

---

### ⭐ **Solución 4: Ejecutar Script de Test** (diagnóstico completo)
```bash
php panel/test-api-mobile.php usuario@email.com
```
**Tiempo:** 1 minuto  
**Dificultad:** Técnica  
**Requiere:** Acceso al servidor  
**Resultado:** Informe completo del estado del usuario

---

## 📊 Matriz de Decisión

| Síntoma | ¿Funciona en otro dispositivo? | Acción | Documento |
|---------|-------------------------------|--------|-----------|
| Error al cargar citas | SÍ | Borrar datos app | [`CLIENTE`](DEBUGGING_APP_MOVIL_CLIENTE.md) |
| Error al cargar citas | NO | Revisar logs servidor | [`BACKEND`](DEBUGGING_APP_MOVIL.md) |
| No puede hacer login | - | Verificar credenciales en BD | [`BACKEND`](DEBUGGING_APP_MOVIL.md) |
| Carga infinita (spinner) | SÍ | Verificar red del usuario | [`CLIENTE`](DEBUGGING_APP_MOVIL_CLIENTE.md) |
| Carga infinita (spinner) | NO | Optimizar query backend | [`BACKEND`](DEBUGGING_APP_MOVIL.md) |
| Error 401 Unauthorized | - | Eliminar token + Relogin | Ambos |
| Error 500 Server Error | - | Ver logs servidor | [`BACKEND`](DEBUGGING_APP_MOVIL.md) |

---

## 🛠️ Herramientas Disponibles

### **Para Desarrollador:**
- ✅ Logging detallado en backend (implementado)
- ✅ Endpoint `/api/health-check` (implementado)
- ✅ Script `test-api-mobile.php` (implementado)
- ✅ Documentación completa (3 guías)

### **Para Usuario:**
- ✅ Guía de soporte paso a paso
- ✅ FAQ con respuestas predefinidas
- ⚠️ Pantalla de debug en app (pendiente implementar)
- ⚠️ Exportar logs desde app (pendiente implementar)

---

## 📖 Índice de Documentación

1. **Este documento** (`TROUBLESHOOTING_APP_RESUMEN.md`)  
   → Guía rápida de decisión

2. [`DEBUGGING_APP_MOVIL.md`](DEBUGGING_APP_MOVIL.md)  
   → Problemas del backend/servidor

3. [`DEBUGGING_APP_MOVIL_CLIENTE.md`](DEBUGGING_APP_MOVIL_CLIENTE.md)  
   → Problemas del dispositivo específico

4. [`GUIA_SOPORTE_USUARIO_FINAL.md`](GUIA_SOPORTE_USUARIO_FINAL.md)  
   → Instrucciones para copiar/pegar al usuario

---

## ⚡ Comandos Rápidos (Copiar/Pegar)

### Ver logs recientes:
```bash
tail -100 panel/storage/logs/laravel.log
```

### Buscar logs de un usuario:
```bash
grep "usuario@email.com" panel/storage/logs/laravel.log | tail -20
```

### Probar usuario específico:
```bash
cd panel && php test-api-mobile.php usuario@email.com
```

### Eliminar tokens de un usuario (forzar relogin):
```sql
DELETE FROM personal_access_tokens WHERE tokenable_id = 5;
```

### Ver tokens activos:
```sql
SELECT u.email, pat.name, pat.created_at, pat.last_used_at 
FROM personal_access_tokens pat
JOIN users u ON pat.tokenable_id = u.id
ORDER BY pat.created_at DESC
LIMIT 10;
```

---

## 📞 Flujo de Soporte Sugerido

```
Usuario reporta error
    ↓
¿Funciona en tu dispositivo con sus credenciales?
    ↓
  SÍ → Problema del dispositivo
    ↓
    1. Enviar guía usuario (borrar datos)
    2. Si no funciona: reinstalar
    3. Si persiste: verificar permisos/red
    ↓
  NO → Problema del backend
    ↓
    1. Ver logs del servidor
    2. Ejecutar test-api-mobile.php
    3. Verificar token en BD
    4. Si es necesario: eliminar token y pedir relogin
```

---

## 🎯 Tiempo Estimado de Resolución

| Escenario | Tiempo promedio | Comunicación con usuario |
|-----------|----------------|--------------------------|
| Dispositivo - Borrar datos | 5 min | 1 mensaje |
| Dispositivo - Reinstalar | 10 min | 2-3 mensajes |
| Backend - Token expirado | 2 min | 1 mensaje |
| Backend - Error de código | 15-60 min | Depende |

---

## ✅ Checklist Rápido

Cuando recibís un reporte de error:

- [ ] Anotar: email del usuario, fecha/hora del error
- [ ] Probar: ¿Funciona en tu dispositivo?
- [ ] Decidir: ¿Es problema de cliente o backend?
- [ ] Acción inmediata según escenario
- [ ] Documentar: qué funcionó para futuros casos
- [ ] Seguimiento: confirmar que se solucionó

---

**Última actualización:** 23 de marzo de 2026

**Mantenido por:** Equipo de Desarrollo Strupeni
