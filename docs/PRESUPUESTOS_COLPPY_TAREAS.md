# Generación Automática de Presupuestos en Colppy al Cerrar Tareas

## Descripción General

Cuando se cierra una tarea en el sistema, se genera automáticamente un **presupuesto** (Tipo X - Cotización) en Colppy que incluye:
- Los productos asociados a la tarea
- Las notas agregadas durante la ejecución (como items adicionales)

## Flujo de Funcionamiento

```
1. Usuario cierra tarea (web/app móvil)
   ↓
2. JobController::closed() actualiza registro
   ↓
3. Llama a generarPresupuestoColppy($job_id)
   ↓
4. Valida que el cliente tenga idcolppy
   ↓
5. Construye items del presupuesto:
   - Productos de la tarea (job_products)
   - Notas como items adicionales (si existe producto genérico NOTA)
   ↓
6. ColppyService::crearFacturaVenta() envía a API
   ↓
7. Guarda idfactura en jobs.colppy_budget_id
   ↓
8. Log de resultado (éxito o error)
```

## Validaciones Previas

El sistema **NO genera presupuesto** si:
- La tarea no existe
- El cliente no tiene `idcolppy` configurado
- No hay productos ni notas en la tarea

Si alguna validación falla, se registra en logs pero **no interrumpe el cierre de la tarea**.

## Estructura del Presupuesto

### Valores Fijos (según Apéndice de Colppy)
- **idTipoFactura:** "X" (X = Presupuesto/Cotización)
- **idEstadoFactura:** "Borrador" (texto, no número)
- **idCondiciónPago:** "a 7 Dias" (exacto según Colppy, respeta mayúsculas)
- **idPlanCuenta:** "Ventas de mercaderías"
- **IVA:** 21% (configurable por item)
- **ImporteUnitario:** 1 (fijo para todos los items)
- **idMoneda:** 1 (Pesos argentinos)
- **idTipoComprobante:** 4
- **nroFactura1:** "0002"
- **nroFactura2:** "" (vacío, Colppy podría calcularlo automáticamente)

**IMPORTANTE:** 
- Los IDs numéricos (idTipoFactura, idEstadoFactura, idMoneda, idTipoComprobante) son valores numéricos según el apéndice
- idCondiciónPago es texto ("a 15 dias")
- unidadMedida debe ser "U" para unidades en Colppy

### Estructura de Parameters según API de Colppy

Los parámetros se envían directamente en `parameters`, no dentro de un objeto anidado:

```json
{
  "parameters": {
    "sesion": { "usuario": "...", "claveSesion": "..." },
    "descripcion": "Presupuesto generado desde tarea #123",
    "fechaFactura": "04-03-2026",
    "fechaPago": "04-03-2026",
    "idCliente": "116",
    "idCondiciónPago": "a 15 dias",
    "idEmpresa": "98",
    "idEstadoAnterior": "",
    "idEstadoFactura": "1",
    "idFactura": "",
    "idMoneda": "1",
    "idTipoComprobante": "4",
    "idTipoFactura": "X",
    "idUsuario": "",
    "netoGravado": "123.00",
    "netoNoGravado": "0.00",
    "nroFactura1": "0002",
    "nroFactura2": "",
    "percepcionIVA": "0.00",
    "percepcionIIBB": "0.00",
    "orderId": "",
    "itemsFactura": [...],
    "totalFactura": "148.83",
    "totalIVA": "25.83",
    "valorCambio": "1",
    "totalesiva": [...]
  }
}
```

### Items del Presupuesto

#### 1. Productos de la Tarea
Cada producto en `job_products` se convierte en un item:

```json
{
  "Descripcion": "Cable UTP Cat 6",
  "unidadMedida": "Metros",
  "ccosto1": "",
  "ccosto2": "",
  "Cantidad": 50,
  "ImporteUnitario": 1,
  "porcDesc": "0.00",
  "IVA": "21",
  "idPlanCuenta": "Ventas de mercaderías",
  "Comentario": ""
}
```

**Nota:** Los nombres de campos usan capitalización específica (`Descripcion`, `Cantidad`, `ImporteUnitario`, `Comentario`).

#### 2. Notas de la Tarea
Las notas se agregan como items adicionales si:
- Existe un producto genérico con código **NOTA** en la tabla `products`
- El producto NOTA tiene `colppy_id` configurado
- Cada nota se agrega como 1 unidad con texto truncado a 200 caracteres

### Cálculos Automáticos

El sistema calcula automáticamente:
- **netoGravado:** Suma de items con IVA > 0
- **netoNoGravado:** Suma de items con IVA = 0
- **totalIVA:** Suma de IVA de todos los items
- **totalFactura:** netoGravado + netoNoGravado + totalIVA + percepciones
- **totalesiva:** Desglose por alícuota (0%, 10.5%, 21%, 27%)

## Configuración Requerida

### 1. Base de Datos

Asegúrate de ejecutar la migración:
```bash
cd panel
php artisan migrate
```

Esto agrega la columna `colppy_budget_id` a la tabla `jobs`:
```sql
ALTER TABLE jobs 
ADD COLUMN colppy_budget_id VARCHAR(50) NULL 
AFTER closed_job_observation;
```

### 2. Producto Genérico para Notas (Opcional)

Si deseas que las notas se incluyan en el presupuesto, crea un producto genérico:

```sql
INSERT INTO products (codigo, descripcion, idcolppy, created_at, updated_at)
VALUES ('NOTA', 'Observación/Nota de tarea', 'ID_COLPPY_DEL_ITEM', NOW(), NOW());
```

Reemplaza `'ID_COLPPY_DEL_ITEM'` con el ID real del item en Colppy.

### 3. Configuración de Empresa

Agrega el nombre de la empresa en la tabla `api_config`:

```sql
INSERT INTO api_config (name, value, created_at, updated_at)
VALUES ('nombre_empresa_api', 'Tu Empresa S.A.', NOW(), NOW());
```

## Ejemplos de Uso

### Caso 1: Tarea con Productos

```
Tarea #123
Cliente: Acme Corp (idcolppy: "C001")
Productos:
  - Producto A (idcolppy: "P001", cantidad: 5)
  - Producto B (idcolppy: "P002", cantidad: 3)
  
Resultado:
✅ Se genera presupuesto en Colppy
✅ 2 items en la factura
✅ jobs.colppy_budget_id = "F12345"
```

### Caso 2: Tarea con Productos y Notas

```
Tarea #124
Cliente: Beta SA (idcolppy: "C002")
Productos:
  - Producto C (cantidad: 2)
Notas:
  - "Se realizó revisión completa"
  - "Cliente requiere seguimiento"

Resultado:
✅ Se genera presupuesto en Colppy
✅ 3 items: 1 producto + 2 notas
✅ Cada nota aparece con prefijo "Nota: ..."
```

### Caso 3: Cliente sin idcolppy

```
Tarea #125
Cliente: Sin Colppy (idcolppy: NULL)
Productos: 5 items

Resultado:
⚠️ NO se genera presupuesto
📝 Se registra en logs: "Cliente sin idcolppy"
✅ La tarea se cierra normalmente
```

## Logs y Monitoreo

### Logs de Éxito
```
[INFO] Presupuesto generado exitosamente en Colppy
{
  "job_id": 123,
  "idfactura": "F12345",
  "items_count": 3
}
```

### Logs de Validación
```
[INFO] Cliente sin idcolppy, no se genera presupuesto
{
  "job_id": 125,
  "client_id": 45
}
```

### Logs de Error
```
[ERROR] Error al crear presupuesto en Colppy
{
  "job_id": 126,
  "response": {
    "resultado": "ERROR",
    "mensaje": "Cliente no encontrado"
  }
}
```

## Consultas SQL Útiles

### Ver tareas con presupuesto generado
```sql
SELECT 
    j.id,
    j.job_description,
    c.nombre as cliente,
    j.colppy_budget_id,
    j.closed_datetime
FROM jobs j
INNER JOIN clients c ON j.client_id = c.id
WHERE j.colppy_budget_id IS NOT NULL
ORDER BY j.closed_datetime DESC;
```

### Ver tareas cerradas sin presupuesto
```sql
SELECT 
    j.id,
    j.job_description,
    c.nombre as cliente,
    c.idcolppy,
    j.closed_datetime
FROM jobs j
INNER JOIN clients c ON j.client_id = c.id
WHERE j.closed_datetime IS NOT NULL
AND j.colppy_budget_id IS NULL
ORDER BY j.closed_datetime DESC;
```

### Contar productos por tarea
```sql
SELECT 
    jp.jobs_id,
    COUNT(*) as total_productos,
    SUM(jp.quantity) as cantidad_total
FROM job_products jp
INNER JOIN products p ON jp.product_id = p.id
WHERE p.idcolppy IS NOT NULL
GROUP BY jp.jobs_id;
```

## Consideraciones Importantes

1. **No bloquea el cierre de tareas:** Si falla la generación del presupuesto, la tarea se cierra de todas formas

2. **Precio fijo:** Todos los items tienen precio unitario = 1. Esto debe ajustarse manualmente en Colppy si es necesario

3. **Sincronización unidireccional:** El presupuesto se crea en Colppy pero cambios posteriores en Colppy no se reflejan en el sistema

4. **Producto NOTA opcional:** Las notas solo se incluyen si existe el producto genérico configurado

5. **IVA 21% por defecto:** Todos los items usan 21% pero el sistema soporta múltiples alícuotas (0%, 10.5%, 21%, 27%)

6. **Estado Borrador:** Los presupuestos se crean en estado Borrador, permitiendo edición en Colppy antes de enviarlos al cliente

7. **Formato de fecha:** Las fechas deben enviarse en formato `dd-mm-yyyy` (ej: `04-03-2026`)

8. **Estructura de API:** Siguiendo estrictamente la documentación de Colppy `alta_facturaventa` v1.1

9. **Campo colppy_id vs idcolppy:** El modelo Client usa accessors para mapear `idcolppy` → `colppy_id` (columna real en BD)

## Troubleshooting

### El presupuesto no se genera

1. **Verificar logs:** Revisa `storage/logs/laravel.log` para mensajes de error
2. **Validar cliente:** Asegúrate que `clients.idcolppy` no sea NULL
3. **Verificar productos:** Los productos deben tener `idcolppy` configurado
4. **Revisar sesión Colppy:** Puede que la sesión haya expirado

### Items faltantes en el presupuesto

- **Productos:** Solo se incluyen los que tienen `idcolppy`
- **Notas:** Requieren producto genérico NOTA configurado
- **Verificar:**
  ```sql
  SELECT * FROM products WHERE idcolppy IS NULL;
  ```

### Error "Cliente no encontrado" en Colppy

El `idcolppy` del cliente no existe o es inválido en Colppy:
1. Sincroniza el cliente desde Colppy
2. Verifica que el ID sea correcto
3. Actualiza `clients.idcolppy` con el valor correcto

## Próximas Mejoras

- [ ] Agregar precios reales desde productos
- [ ] Permitir configurar tipo de factura (X, A, B, etc.)
- [ ] Sincronización bidireccional (actualizar desde Colppy)
- [ ] Enviar presupuesto por email automáticamente
- [ ] Dashboard de presupuestos generados
- [ ] Integración con proceso de aprobación

## Referencias

- [Integración Colppy](./INTEGRACION_COLPPY.md)
- [Implementación Colppy](./IMPLEMENTACION_COLPPY.md)
- [Flujo de Sincronización](./FLUJO_SINCRONIZACION.md)
- [API Endpoints](./API_ENDPOINTS.md)
