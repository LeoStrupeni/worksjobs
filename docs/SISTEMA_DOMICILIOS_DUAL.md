# Sistema de Domicilios Dual

## Descripción

Sistema implementado para manejar domicilios de clientes de **dos orígenes distintos**:
- **Clientes locales**: Alta desde el CMS, guardados en tabla `clients`
- **Clientes externos**: Obtenidos desde Colppy API u otras fuentes externas

## Arquitectura de Tablas

### 1. `clients_address` (Clientes Locales)

Tabla para domicilios de clientes locales con **Foreign Key**.

**Campos**:
- `id` (PK)
- `client_id` (FK → `clients.id`) ⚠️ **ENTERO con FK**
- `country`, `state`, `cp`, `city`
- `address_street`, `address_nro`, `address_apartament`, `address_detail`
- `created_at`, `updated_at`, `deleted_at`

**Uso**: Clientes dados de alta en el CMS, con ID numérico secuencial.

---

### 2. `clients_address_external` (Clientes Externos)

Tabla para domicilios de clientes externos **sin Foreign Key**.

**Campos**:
- `id` (PK)
- `external_client_id` (STRING) ⚠️ **TEXTO sin FK**, ejemplo: `'colppy_123'`
- `country`, `state`, `cp`, `city`
- `address_street`, `address_nro`, `address_apartament`, `address_detail`
- `created_at`, `updated_at`, `deleted_at`

**Uso**: Clientes de Colppy API o futuras integraciones. El ID incluye prefijo del sistema origen.

---

## Lógica de Detección Automática

Todos los métodos detectan automáticamente el origen del cliente por su ID:

```php
// Detectar origen
$isExternal = is_string($client_id) && (strpos($client_id, 'colppy_') === 0);

if ($isExternal) {
    // Usar clients_address_external
    $addresses = ClientAddressExternal::where('external_client_id', $client_id)->get();
} else {
    // Usar clients_address (clientes locales)
    $addresses = Clients_Addres::where('client_id', $client_id)->get();
}
```

---

## Controladores Adaptados

### ✅ ClientController.php

- **getAddress($client_id)**: Lee de tabla correcta según origen
- **storeAddress(Request, $client_id)**: Guarda en tabla correcta según origen
- **detroyAddress($id)**: Busca en ambas tablas para soft delete

### ✅ ApiJobController.php

- **getClientAddresses($clientId)**: API móvil, soporta ambos orígenes
- **createClientAddress(Request)**: API móvil, valida y guarda en tabla correcta

---

## Ejemplos de Uso

### Crear domicilio para cliente local (ID: 45)

```php
POST /client/address
{
    "client_id": 45,
    "address_street": "San Martín",
    "city": "Rosario"
}
```
→ Se guarda en `clients_address` con FK válida.

---

### Crear domicilio para cliente de Colppy (ID: colppy_123)

```php
POST /client/address
{
    "client_id": "colppy_123",
    "address_street": "Mitre",
    "city": "Buenos Aires"
}
```
→ Se guarda en `clients_address_external` sin FK.

---

## Flujo Frontend (client.js)

1. Usuario hace clic en "Domicilios" → envía `client_id` completo (sea `45` o `'colppy_123'`)
2. AJAX a `/client/address/{client_id}`
3. Backend detecta origen automáticamente y consulta tabla correcta
4. Respuesta en formato idéntico: `{ datos: [...] }`
5. Frontend renderiza sin cambios, **transparente al usuario**

---

## Ventajas de Esta Arquitectura

✅ **Sin modificar FK existente**: `clients_address` mantiene integridad referencial  
✅ **Escalable**: Futuras APIs (ej: `'mercadolibre_456'`) solo agregan prefijo  
✅ **Código DRY**: Lógica de detección centralizada, se reutiliza en todos los métodos  
✅ **Transparente**: Frontend NO cambia, solo pasa el ID completo  
✅ **Performance**: Índice en `external_client_id` para búsquedas rápidas  

---

## Consideraciones

⚠️ **No mezclar IDs**: Clientes locales SIEMPRE ID numérico, externos SIEMPRE con prefijo  
⚠️ **Prefijo consistente**: Usar `'colppy_'` para Colppy en todo el sistema  
⚠️ **Soft Deletes**: Ambas tablas usan `deleted_at`, método `detroyAddress()` busca en ambas  

---

## Migración Aplicada

```bash
php artisan migrate
# Migrated: 2026_02_18_160000_create_clients_address_external_table (655.75ms)
```

**Archivo**: `database/migrations/2026_02_18_160000_create_clients_address_external_table.php`  
**Modelo**: `app/Models/ClientAddressExternal.php`

---

## Futuras Extensiones

Para agregar otra fuente de clientes externos:

1. Decidir prefijo (ej: `'ml_'` para MercadoLibre)
2. Usar mismo modelo `ClientAddressExternal`
3. Actualizar detección en controladores:
   ```php
   $isExternal = is_string($client_id) && 
                 (strpos($client_id, 'colppy_') === 0 || 
                  strpos($client_id, 'ml_') === 0);
   ```

¡Sistema listo para producción! 🚀
