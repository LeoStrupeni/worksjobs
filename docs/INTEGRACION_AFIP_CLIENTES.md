# Integración AFIP y Alta de Clientes en Colppy

**Fecha de implementación**: 06/04/2026  
**Versión**: 1.0  
**Estado**: ✅ Implementado y Testeado

---

## 📋 Resumen

Se implementó la integración completa con la API de Colppy para:
1. **Obtener datos fiscales de terceros desde AFIP** por CUIT
2. **Crear clientes en Colppy** usando datos de AFIP o datos manuales
3. **Sincronizar automáticamente** los clientes creados en la base de datos local

Esta funcionalidad es **esencial** para la creación de presupuestos, ya que Colppy requiere que los clientes existan en su sistema antes de generar facturas/presupuestos.

---

## 🔧 Componentes Implementados

### 1. ColppyService - Nuevos Métodos

**Archivo**: `panel/app/Services/ColppyService.php`

#### `obtenerDatosTerceroDeAfip(string $cuit): array`

Consulta los datos fiscales de un CUIT en AFIP a través de la API de Colppy.

**Parámetros**:
- `$cuit` (string): CUIT del tercero (con o sin guiones, se limpia automáticamente)

**Retorna**:
```php
[
    'success' => true,
    'data' => [
        'nombre' => 'RAZON SOCIAL O NOMBRE',
        'tipoPersona' => 'FISICA' | 'JURIDICA',
        'numeroDocumento' => '',
        'impuestos' => [10, 30, ...],
        'domicilioFiscal' => [
            'codPostal' => '2000',
            'descripcionProvincia' => 'SANTA FE',
            'direccion' => 'CALLE 123',
            'idProvincia' => 12,
            'localidad' => 'ROSARIO',
            'tipoDomicilio' => 'FISCAL',
            'provincia' => 'Santa Fé'
        ],
        'idCondicionIva' => 4,  // 1=Resp.Insc, 3=Cons.Final, 4=Monotributo
        'condicionIva' => [
            'value' => 4,
            'label' => 'Monotributo'
        ],
        'pais' => 'Argentina'
    ],
    'mensaje' => 'Datos obtenidos correctamente'
]
```

**Ejemplo de uso**:
```php
$colppyService = new ColppyService();
$resultado = $colppyService->obtenerDatosTerceroDeAfip('20327342585');

if ($resultado['success']) {
    $nombre = $resultado['data']['nombre'];
    $direccion = $resultado['data']['domicilioFiscal']['direccion'];
    // ...
}
```

---

#### `crearCliente(array $datosCliente): array`

Crea un cliente en Colppy con los datos proporcionados.

**Parámetros**:
```php
$datosCliente = [
    // OBLIGATORIOS
    'razon_social' => 'Nombre o Razón Social',
    'nombre_fantasia' => 'Nombre de Fantasía',
    
    // OPCIONALES
    'cuit' => '20327342585',
    'dni' => '32734258',
    'email' => 'cliente@example.com',
    'telefono' => '123456789',
    'direccion' => 'Calle 123',
    'ciudad' => 'Rosario',
    'provincia' => 'Santa Fe',
    'codigo_postal' => '2000',
    'pais' => 'Argentina',
    'id_condicion_iva' => 4  // ID de condición IVA de Colppy
];
```

**Retorna**:
```php
[
    'success' => true,
    'idCliente' => '13695377',  // ID del cliente en Colppy
    'mensaje' => 'Cliente creado correctamente'
]
```

**Ejemplo de uso**:
```php
$colppyService = new ColppyService();

// Opción 1: Con datos de AFIP
$datosAfip = $colppyService->obtenerDatosTerceroDeAfip('20327342585');
if ($datosAfip['success']) {
    $cliente = [
        'razon_social' => $datosAfip['data']['nombre'],
        'nombre_fantasia' => $datosAfip['data']['nombre'],
        'cuit' => '20327342585',
        'direccion' => $datosAfip['data']['domicilioFiscal']['direccion'],
        'ciudad' => $datosAfip['data']['domicilioFiscal']['localidad'],
        'provincia' => $datosAfip['data']['domicilioFiscal']['provincia'],
        'codigo_postal' => $datosAfip['data']['domicilioFiscal']['codPostal'],
        'id_condicion_iva' => $datosAfip['data']['idCondicionIva']
    ];
    
    $resultado = $colppyService->crearCliente($cliente);
}

// Opción 2: Con datos manuales
$cliente = [
    'razon_social' => 'Juan Pérez',
    'nombre_fantasia' => 'Juan Pérez',
    'email' => 'juan@example.com',
    'telefono' => '123456789'
];

$resultado = $colppyService->crearCliente($cliente);
```

---

### 2. ApiBudgetController - Método Actualizado

**Archivo**: `panel/app/Http/Controllers/Api/ApiBudgetController.php`

#### `createClient(Request $request): JsonResponse`

Endpoint API para crear clientes desde la app móvil con integración automática de AFIP y Colppy.

**Endpoint**: `POST /api/clients`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body - Opción 1 (Solo CUIT)**:
```json
{
  "cuit": "20327342585"
}
```

**Body - Opción 2 (Datos manuales)**:
```json
{
  "first_name": "Juan",
  "last_name": "Pérez",
  "email": "juan@example.com",
  "phone": "123456789",
  "address": "Calle 123",
  "city": "Rosario",
  "state": "Santa Fe",
  "postal_code": "2000",
  "cuit": "20327342585"
}
```

**Flujo de Funcionamiento**:

1. **Si se proporciona CUIT**:
   - Consulta datos en AFIP vía `obtenerDatosTerceroDeAfip()`
   - Si encuentra datos, los usa para crear el cliente
   - Si falla AFIP, usa datos manuales (fallback)

2. **Crear en Colppy**:
   - Llama a `crearCliente()` con los datos obtenidos
   - Obtiene el `idColppy` del cliente creado

3. **Guardar localmente**:
   - Crea registro en tabla `clients`
   - Marca `is_from_colppy = 1`
   - Guarda `idcolppy` para futuras referencias

**Respuesta Exitosa** (201 Created):
```json
{
  "success": true,
  "message": "Cliente creado correctamente en Colppy y sincronizado localmente",
  "data": {
    "id": 123,
    "idColppy": "13695377",
    "first_name": "LEONARDO DANIEL",
    "last_name": "STRUPENI",
    "full_name": "LEONARDO DANIEL STRUPENI",
    "email": null,
    "phone": null,
    "cuit": "20327342585",
    "datos_afip": {
      "nombre": "LEONARDO DANIEL STRUPENI",
      "tipoPersona": "FISICA",
      "domicilioFiscal": {...},
      "idCondicionIva": 4,
      "condicionIva": {
        "value": 4,
        "label": "Monotributo"
      }
    }
  }
}
```

**Respuesta Error** (400):
```json
{
  "success": false,
  "message": "Error al crear cliente en Colppy: El CUIT ya existe",
  "datos_afip": {...}
}
```

---

## ✅ Pruebas Realizadas

### CUIT Verificados en AFIP

Se realizaron pruebas exitosas con los siguientes CUIT:

| CUIT | Nombre | Tipo Persona | Condición IVA | Estado |
|------|--------|--------------|---------------|--------|
| 20327342585 | LEONARDO DANIEL STRUPENI | FISICA | Monotributo | ✅ Creado |
| 30703088534 | MERCADOLIBRE S.R.L. | JURIDICA | Resp. Insc. | ✅ Consultado |
| 20290017379 | FEDERICO LISANDRO STRUPENI | FISICA | Resp. Insc. | ✅ Consultado |
| 27142060286 | PATRICIA SUSANA SCHMITT | FISICA | Cons. Final | ✅ Creado |

### Clientes Creados en Colppy

| CUIT | Nombre | ID Colppy | Fecha Creación |
|------|--------|-----------|----------------|
| 20327342585 | LEONARDO DANIEL STRUPENI | 13695368 | 06/04/2026 18:16:28 |
| 27142060286 | PATRICIA SUSANA SCHMITT | 13695377 | 06/04/2026 18:16:47 |

---

## 🔄 Flujo Completo: Crear Presupuesto con Cliente Nuevo

```
┌─────────────────────┐
│   App Móvil/Web     │
│ Quiere crear        │
│ presupuesto para    │
│ CUIT: 20327342585   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ POST /api/clients   │
│ { cuit: "..." }     │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────┐
│ ApiBudgetController         │
│  ::createClient()           │
│                             │
│ 1. Consulta AFIP            │
│    obtenerDatosTerceroDeAfip│
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ Colppy API - AFIP           │
│ Retorna:                    │
│ - Nombre                    │
│ - Domicilio Fiscal          │
│ - Condición IVA             │
│ - Impuestos                 │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ ApiBudgetController         │
│                             │
│ 2. Crea en Colppy          │
│    crearCliente()           │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ Colppy API - Alta Cliente   │
│ Retorna:                    │
│ - idCliente: "13695377"     │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ ApiBudgetController         │
│                             │
│ 3. Guarda localmente        │
│    Client::create([...])    │
│    is_from_colppy = 1       │
│    idcolppy = "13695377"    │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ Respuesta a App             │
│ Cliente creado con éxito    │
│ ID local + ID Colppy        │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ POST /api/budgets           │
│ Crear presupuesto           │
│ client_id: 123 (local)      │
│ → idcolppy: "13695377"      │
└─────────────────────────────┘
```

---

## 📚 Campos de info_otra Requeridos por Colppy

La API de Colppy requiere los siguientes campos en `info_otra` (pueden estar vacíos):

```php
[
    'Activo' => '1',                    // OBLIGATORIO
    'FechaAlta' => '',                  // OBLIGATORIO (vacío = auto)
    'DirFiscal' => '',                  // Opcional
    'DirFiscalCiudad' => '',           // Opcional
    'DirFiscalCodigoPostal' => '',     // Opcional
    'DirFiscalProvincia' => '',        // Opcional
    'DirFiscalPais' => '',             // Opcional
    'idCondicionPago' => '',           // OBLIGATORIO (vacío = default)
    'idCondicionIva' => '',            // Opcional (se puede pasar)
    'porcentajeIVA' => '',             // Opcional
    'idPlanCuenta' => '',              // OBLIGATORIO (vacío = default)
    'CuentaCredito' => '',             // Opcional
    'DirEnvio' => '',                  // Opcional
    'DirEnvioCiudad' => '',            // Opcional
    'DirEnvioCodigoPostal' => '',      // Opcional
    'DirEnvioProvincia' => '',         // Opcional
    'DirEnvioPais' => ''               // Opcional
]
```

**Nota**: Aunque algunos campos son "opcionales", Colppy requiere que estén presentes en el request (pueden estar vacíos).

---

## 🔐 Condiciones de IVA en Colppy

| ID | Descripción |
|----|-------------|
| 1  | Responsable Inscripto |
| 3  | Consumidor Final |
| 4  | Monotributo |
| 5  | Exento |
| 6  | No Responsable |

---

## ⚠️ Consideraciones Importantes

### 1. CUIT Duplicado
Si el CUIT ya existe en Colppy, la API retorna un error:
```
"El CUIT ya existe; está siendo usado para otro cliente"
```

**Recomendación**: Antes de crear, verificar si el cliente ya existe consultando la lista de clientes.

### 2. Datos Obligatorios
Para crear un cliente en Colppy son **OBLIGATORIOS**:
- `RazonSocial`
- `NombreFantasia`
- Campos de `info_otra` (aunque estén vacíos)

### 3. Campos Vacíos vs NULL
Colppy requiere strings vacíos `''` en lugar de `null` para campos opcionales.

### 4. Formato de ID Cliente
Colppy puede devolver el ID en diferentes formatos:
- `idCliente` (con C mayúscula) ← Común
- `idcliente` (todo minúsculas) ← Según versión API

El método `crearCliente()` maneja ambos casos.

---

## 🚀 Próximos Pasos

1. **Validación de duplicados**: Implementar verificación antes de crear
2. **Sincronización inversa**: Actualizar clientes existentes con datos de AFIP
3. **Caché de consultas AFIP**: Evitar consultas repetidas del mismo CUIT
4. **Interfaz UI**: Crear pantalla en app móvil para alta de clientes
5. **Masivo**: Importación de clientes por lote desde Excel con AFIP

---

## 📝 Tests Sugeridos

```bash
# Probar consulta AFIP
curl -X POST http://localhost/api/clients \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"cuit": "20327342585"}'

# Probar creación manual
curl -X POST http://localhost/api/clients \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Juan",
    "last_name": "Pérez",
    "email": "juan@test.com",
    "phone": "123456789",
    "cuit": "20123456789"
  }'
```

---

## 📖 Referencias

- **Documentación Colppy API**: Operación `obtener_datos_tercero_de_afip`
- **Documentación Colppy API**: Operación `alta_cliente`
- **Service**: `panel/app/Services/ColppyService.php`
- **Controller**: `panel/app/Http/Controllers/Api/ApiBudgetController.php`
- **Route**: `POST /api/clients` (protegida con `auth:sanctum`)

---

**Última actualización**: 06/04/2026  
**Autor**: GitHub Copilot (strupeni-dev agent)
