# 📚 Ejemplos de Uso - API Colppy

## 1️⃣ Flutter: Obtener y Mostrar Lista de Clientes

### Servicio/Proveedor

```dart
// lib/providers/colppy_clients_provider.dart
import 'package:flutter/material.dart';
import 'package:technician_app/services/colppy_service.dart';
import 'package:technician_app/models/colppy_cliente.dart';
import 'package:technician_app/services/auth_service.dart';

class ColppyClientsProvider extends ChangeNotifier {
  List<ColppyCliente> clientes = [];
  bool cargando = false;
  String error = '';
  int totalClientes = 0;
  int paginaActual = 0;
  static const int clientesPorPagina = 50;

  /// Cargar clientes de Colppy
  Future<void> cargarClientes({
    int start = 0,
    List<Map<String, dynamic>>? filtros,
    List<Map<String, dynamic>>? orden,
  }) async {
    cargando = true;
    error = '';
    notifyListeners();

    try {
      // Obtener token de autenticación
      final token = await AuthService.getToken();
      if (token == null) {
        error = 'No autenticado';
        cargando = false;
        notifyListeners();
        return;
      }

      // Llamar servicio de Colppy
      final resultado = await ColppyService.listarClientes(
        token,
        start: start,
        limit: clientesPorPagina,
        filters: filtros,
        order: orden,
      );

      if (resultado['success']) {
        final response = ColppyClientesResponse.fromJson(resultado['data']);
        clientes = response.clientes;
        totalClientes = response.total;
        paginaActual = start ~/ clientesPorPagina;
        error = '';
      } else {
        error = resultado['message'] ?? 'Error desconocido';
        clientes = [];
      }
    } catch (e) {
      error = 'Error: $e';
      clientes = [];
    }

    cargando = false;
    notifyListeners();
  }

  /// Ir a la siguiente página
  Future<void> siguientePagina({
    List<Map<String, dynamic>>? filtros,
  }) async {
    int proximoStart = (paginaActual + 1) * clientesPorPagina;
    if (proximoStart < totalClientes) {
      await cargarClientes(start: proximoStart, filtros: filtros);
    }
  }

  /// Ir a la página anterior
  Future<void> paginaAnterior({
    List<Map<String, dynamic>>? filtros,
  }) async {
    if (paginaActual > 0) {
      int proximoStart = (paginaActual - 1) * clientesPorPagina;
      await cargarClientes(start: proximoStart, filtros: filtros);
    }
  }

  /// Obtener un cliente específico
  Future<ColppyCliente?> obtenerClienteDetalle(String idCliente) async {
    try {
      final token = await AuthService.getToken();
      if (token == null) return null;

      final resultado = await ColppyService.obtenerCliente(token, idCliente);

      if (resultado['success']) {
        return ColppyCliente.fromJson(resultado['data']);
      }
      return null;
    } catch (e) {
      print('Error obteniendo cliente: $e');
      return null;
    }
  }

  /// Buscar clientes activos
  Future<void> buscarClientesActivos() async {
    await cargarClientes(
      filtros: [
        {'field': 'Activo', 'op': '=', 'value': '1'}
      ],
      orden: [
        {'field': 'NombreFantasia', 'dir': 'asc'}
      ],
    );
  }

  /// Buscar por CUIT
  Future<void> buscarPorCuit(String cuit) async {
    await cargarClientes(
      filtros: [
        {'field': 'CUIT', 'op': '=', 'value': cuit}
      ],
    );
  }
}
```

### Widget de Pantalla

```dart
// lib/screens/clientes_colppy_screen.dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:technician_app/providers/colppy_clients_provider.dart';

class ClientesColppyScreen extends StatefulWidget {
  const ClientesColppyScreen({Key? key}) : super(key: key);

  @override
  State<ClientesColppyScreen> createState() => _ClientesColppyScreenState();
}

class _ClientesColppyScreenState extends State<ClientesColppyScreen> {
  late final TextEditingController _searchController;

  @override
  void initState() {
    super.initState();
    _searchController = TextEditingController();
    
    // Cargar clientes cuando se abre la pantalla
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ColppyClientsProvider>().buscarClientesActivos();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Clientes (Colppy)'),
        elevation: 0,
      ),
      body: Consumer<ColppyClientsProvider>(
        builder: (context, provider, child) {
          if (provider.cargando) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.error.isNotEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.error_outline, size: 48, color: Colors.red),
                  const SizedBox(height: 16),
                  Text(
                    'Error: ${provider.error}',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Colors.red),
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () => provider.buscarClientesActivos(),
                    child: const Text('Reintentar'),
                  )
                ],
              ),
            );
          }

          if (provider.clientes.isEmpty) {
            return const Center(
              child: Text('No se encontraron clientes'),
            );
          }

          return Column(
            children: [
              // Barra de búsqueda
              Padding(
                padding: const EdgeInsets.all(16.0),
                child: TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    hintText: 'Buscar por CUIT...',
                    prefixIcon: const Icon(Icons.search),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  onSubmitted: (value) {
                    if (value.isNotEmpty) {
                      provider.buscarPorCuit(value);
                    }
                  },
                ),
              ),

              // Lista de clientes
              Expanded(
                child: ListView.builder(
                  itemCount: provider.clientes.length,
                  itemBuilder: (context, index) {
                    final cliente = provider.clientes[index];
                    return ClienteCard(cliente: cliente);
                  },
                ),
              ),

              // Paginación
              Padding(
                padding: const EdgeInsets.all(16.0),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    ElevatedButton.icon(
                      onPressed: provider.paginaActual > 0
                          ? () => provider.paginaAnterior()
                          : null,
                      icon: const Icon(Icons.arrow_back),
                      label: const Text('Anterior'),
                    ),
                    Text(
                      'Página ${provider.paginaActual + 1} '
                      '(${provider.clientes.length}/${provider.totalClientes})',
                    ),
                    ElevatedButton.icon(
                      onPressed: ((provider.paginaActual + 1) *
                                  provider.clientesPorPagina) <
                              provider.totalClientes
                          ? () => provider.siguientePagina()
                          : null,
                      icon: const Icon(Icons.arrow_forward),
                      label: const Text('Siguiente'),
                    ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class ClienteCard extends StatelessWidget {
  final ColppyCliente cliente;

  const ClienteCard({Key? key, required this.cliente}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: cliente.activo ? Colors.green : Colors.red,
          child: Icon(
            cliente.activo ? Icons.check : Icons.close,
            color: Colors.white,
          ),
        ),
        title: Text(cliente.nombreMostrar),
        subtitle: Text(cliente.cuit ?? 'Sin CUIT'),
        trailing: const Icon(Icons.arrow_forward_ios),
        onTap: () {
          // Ir a detalle del cliente
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => ClienteDetalleScreen(
                idCliente: cliente.idCliente,
              ),
            ),
          );
        },
      ),
    );
  }
}
```

---

## 2️⃣ Laravel: Endpoint Personalizado

### Crear un controlador personalizado

```php
// app/Http/Controllers/Api/ClientesColppyController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ColppyService;
use Illuminate\Http\Request;

class ClientesColppyController extends Controller
{
    private ColppyService $colppyService;

    public function __construct(ColppyService $colppyService)
    {
        $this->colppyService = $colppyService;
    }

    /**
     * Obtener clientes con filtros predefinidos
     * GET /api/clientes-activos
     */
    public function obtenerClientesActivos()
    {
        $resultado = $this->colppyService->listarClientes(
            start: 0,
            limit: 100,
            filtros: [
                ['field' => 'Activo', 'op' => '=', 'value' => '1']
            ],
            orden: [
                ['field' => 'NombreFantasia', 'dir' => 'asc']
            ]
        );

        if ($resultado['success']) {
            return response()->json([
                'success' => true,
                'clientes' => $resultado['datos'],
                'total' => count($resultado['datos'])
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $resultado['mensaje']
        ], 400);
    }

    /**
     * Buscar cliente por CUIT
     * GET /api/clientes-por-cuit?cuit=30-69224359-1
     */
    public function buscarPorCuit(Request $request)
    {
        $cuit = $request->query('cuit');

        if (!$cuit) {
            return response()->json([
                'success' => false,
                'message' => 'CUIT requerido'
            ], 400);
        }

        $resultado = $this->colppyService->listarClientes(
            filtros: [
                ['field' => 'CUIT', 'op' => '=', 'value' => $cuit]
            ]
        );

        if ($resultado['success']) {
            return response()->json([
                'success' => true,
                'clientes' => $resultado['datos']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Cliente no encontrado'
        ], 404);
    }

    /**
     * Obtener detalle completo de un cliente
     * GET /api/cliente-detalle?id=123
     */
    public function obtenerDetalle(Request $request)
    {
        $idCliente = $request->query('id');

        if (!$idCliente) {
            return response()->json([
                'success' => false,
                'message' => 'ID cliente requerido'
            ], 400);
        }

        $resultado = $this->colppyService->obtenerCliente($idCliente);

        if ($resultado['success']) {
            return response()->json([
                'success' => true,
                'cliente' => $resultado['datos'][0] ?? $resultado['datos']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error obteniendo cliente'
        ], 400);
    }

    /**
     * Sincronizar clientes a tabla local
     * POST /api/sincronizar-clientes
     */
    public function sincronizarClientes()
    {
        $resultado = $this->colppyService->listarClientes(
            start: 0,
            limit: 1000,
            filtros: [
                ['field' => 'Activo', 'op' => '=', 'value' => '1']
            ]
        );

        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Error sincronizando clientes'
            ], 400);
        }

        // Aquí iría la lógica para guardar en tabla local si lo necesitas
        $clientes = $resultado['datos'];

        return response()->json([
            'success' => true,
            'message' => 'Sincronización completada',
            'total' => count($clientes)
        ]);
    }
}
```

### Agregar rutas

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Colppy endpoints existentes
    Route::prefix('colppy')->group(function () {
        Route::post('/session', [ApiColppyController::class, 'obtenerSesion']);
        Route::get('/clientes', [ApiColppyController::class, 'listarClientes']);
        Route::get('/clientes/{idCliente}', [ApiColppyController::class, 'obtenerCliente']);
        Route::post('/call', [ApiColppyController::class, 'hacerLlamada']);
        Route::post('/invalidate-session', [ApiColppyController::class, 'invalidarSesion']);
    });

    // Endpoints personalizados
    Route::get('/clientes-activos', [ClientesColppyController::class, 'obtenerClientesActivos']);
    Route::get('/clientes-por-cuit', [ClientesColppyController::class, 'buscarPorCuit']);
    Route::get('/cliente-detalle', [ClientesColppyController::class, 'obtenerDetalle']);
    Route::post('/sincronizar-clientes', [ClientesColppyController::class, 'sincronizarClientes']);
});
```

---

## 3️⃣ Casos de Uso Avanzados

### Caso 1: Buscar clientes modificados en el último mes

```dart
// Flutter
final resultado = await ColppyService.listarClientes(
  token,
  filtros: [
    {
      'field': 'FechaModificacion',
      'op': '>=',
      'value': DateTime.now().subtract(Duration(days: 30)).toString().split(' ')[0]
    },
    {
      'field': 'Activo',
      'op': '=',
      'value': '1'
    }
  ],
  order: [
    {'field': 'FechaModificacion', 'dir': 'desc'}
  ],
);
```

### Caso 2: Paginación eficiente en Flutter

```dart
class ClientesPaginadosProvider extends ChangeNotifier {
  final List<ColppyCliente> _clientesCacheados = [];
  int _totalClientes = 0;
  int _paginaActual = 0;
  static const _clientesPorPagina = 50;

  Future<void> cargarMas() async {
    final token = await AuthService.getToken();
    if (token == null) return;

    final resultado = await ColppyService.listarClientes(
      token,
      start: _paginaActual * _clientesPorPagina,
      limit: _clientesPorPagina,
    );

    if (resultado['success']) {
      final response = ColppyClientesResponse.fromJson(resultado['data']);
      _clientesCacheados.addAll(response.clientes);
      _totalClientes = response.total;
      _paginaActual++;
      notifyListeners();
    }
  }

  bool get hasMasClientes => 
      (_paginaActual * _clientesPorPagina) < _totalClientes;

  List<ColppyCliente> get clientes => _clientesCacheados;
}
```

### Caso 3: Sincronización en background (Laravel)

```php
// Comando personalizado
php artisan make:command SincronizarClientesColppy

// app/Console/Commands/SincronizarClientesColppy.php
class SincronizarClientesColppy extends Command
{
    protected $signature = 'colppy:sincronizar-clientes';
    protected $description = 'Sincronizar clientes desde Colppy';

    public function handle()
    {
        $colppy = new ColppyService();
        
        $resultado = $colppy->listarClientes(limit: 1000);
        
        if ($resultado['success']) {
            $this->info('Clientes sincronizados: ' . count($resultado['datos']));
        } else {
            $this->error('Error: ' . $resultado['mensaje']);
        }
    }
}

// Agregar a schedule en app/Console/Kernel.php
$schedule->command('colppy:sincronizar-clientes')->hourly();
```

---

## 4️⃣ Manejo de Errores Completo

### Flutter

```dart
Future<void> cargarClientesConManejo() async {
  try {
    setState(() => cargando = true);

    final token = await AuthService.getToken();
    if (token == null) {
      throw Exception('No autenticado');
    }

    final resultado = await ColppyService.listarClientes(token);

    if (!resultado['success']) {
      // Mostrar error específico
      _mostrarError(resultado['message'] ?? 'Error desconocido');
      return;
    }

    final clientes = ColppyClientesResponse.fromJson(resultado['data']);
    
    setState(() {
      this.clientes = clientes.clientes;
      error = '';
    });

  } on TimeoutException {
    _mostrarError('Tiempo de espera agotado. Intenta de nuevo.');
  } on SocketException {
    _mostrarError('Sin conexión a internet');
  } catch (e) {
    _mostrarError('Error: ${e.toString()}');
  } finally {
    setState(() => cargando = false);
  }
}

void _mostrarError(String mensaje) {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text(mensaje),
      backgroundColor: Colors.red,
      duration: const Duration(seconds: 5),
    ),
  );
}
```

### Laravel

```php
try {
    $resultado = $this->colppyService->listarClientes();
    
    if (!$resultado['success']) {
        \Log::warning('Error en Colppy', [
            'mensaje' => $resultado['mensaje'],
            'usuario' => auth()->id()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'No se pudieron cargar los clientes'
        ], 400);
    }
    
    return response()->json([
        'success' => true,
        'data' => $resultado['datos']
    ]);
    
} catch (\Exception $e) {
    \Log::error('Excepción en ColppyService', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    return response()->json([
        'success' => false,
        'message' => 'Error interno del servidor'
    ], 500);
}
```

---

**Última actualización**: 16 de febrero de 2026
