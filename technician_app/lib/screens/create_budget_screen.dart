import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/budget_provider.dart';
import '../providers/job_provider.dart';
import '../services/product_service.dart';
import '../services/budget_service.dart';
import '../models/product.dart';
import '../models/client.dart';
import '../models/budget_item.dart';
import '../utils/custom_alerts.dart';
import 'budget_detail_screen.dart';

class CreateBudgetScreen extends StatefulWidget {
  const CreateBudgetScreen({super.key});

  @override
  State<CreateBudgetScreen> createState() => _CreateBudgetScreenState();
}

class _CreateBudgetScreenState extends State<CreateBudgetScreen> {
  final ProductService _productService = ProductService();
  final BudgetService _budgetService = BudgetService();
  
  // Form controllers
  final _clientSearchController = TextEditingController();
  final _cuitController = TextEditingController();
  final _productSearchController = TextEditingController();
  final _descriptionController = TextEditingController();

  // Estado
  Client? _selectedClient;
  List<BudgetItem> _items = [];
  List<Product> _productSearchResults = [];
  List<Client> _clientSearchResults = [];
  bool _isSearchingProducts = false;
  bool _isSearchingClients = false;
  bool _isCreatingClient = false;
  bool _isCreating = false; // ✅ Prevenir múltiples presiones en botón crear
  bool _showProductSearch = false;
  String? _tipoFilter; // null = todos, 'P' = productos, 'S' = servicios

  @override
  void dispose() {
    _clientSearchController.dispose();
    _cuitController.dispose();
    _productSearchController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  // Calcular total
  double get _total {
    return _items.fold(0.0, (sum, item) => sum + item.subtotal);
  }

  // Buscar clientes (IGUAL QUE EN CREATE_JOB)
  Future<void> _searchClients(String query) async {
    if (query.length < 2) {
      setState(() => _clientSearchResults = []);
      return;
    }

    setState(() => _isSearchingClients = true);

    final jobProvider = context.read<JobProvider>();
    final results = await jobProvider.searchClients(query);

    setState(() {
      _clientSearchResults = results;
      _isSearchingClients = false;
    });

    if (results.isEmpty && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('No se encontraron clientes con "$query"'),
          backgroundColor: Colors.orange,
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }

  // Buscar productos/servicios
  Future<void> _searchProductsServices(String query) async {
    if (query.isEmpty) {
      setState(() => _productSearchResults = []);
      return;
    }

    setState(() => _isSearchingProducts = true);

    final result = await _productService.searchProductsAndServices(
      search: query,
      tipo: _tipoFilter,
      limit: 20,
    );

    setState(() {
      _isSearchingProducts = false;
      if (result['success'] == true) {
        _productSearchResults = result['products'] ?? [];
      } else {
        _productSearchResults = [];
      }
    });
  }

  // Agregar item a la lista
  void _addItem(Product product) {
    showDialog(
      context: context,
      builder: (context) => _AddItemDialog(
        product: product,
        onAdd: (quantity, unitType, unitPrice) {
          final subtotal = quantity * unitPrice;
          final item = BudgetItem(
            productId: product.id,
            codigo: product.codigo ?? '',
            descripcion: product.descripcion ?? '',
            tipoItem: product.tipoItem,
            unitType: unitType,
            quantity: quantity,
            unitPrice: unitPrice,
            subtotal: subtotal,
          );

          setState(() {
            _items.add(item);
            _productSearchResults = [];
            _productSearchController.clear();
            _showProductSearch = false;
          });
        },
      ),
    );
  }

  // Eliminar item
  void _removeItem(int index) {
    setState(() => _items.removeAt(index));
  }

  // Seleccionar cliente de la búsqueda
  void _selectClient(Client client) {
    setState(() {
      _selectedClient = client;
      _clientSearchResults = [];
      _clientSearchController.clear();
    });
  }

  // Crear cliente con AFIP
  Future<void> _createClientWithAFIP() async {
    final cuit = _cuitController.text.trim();

    if (cuit.isEmpty) {
      CustomAlerts.showWarning(
        context,
        'CUIT requerido',
        'Ingresa el CUIT del cliente para obtener sus datos de AFIP.',
      );
      return;
    }

    if (cuit.length != 11) {
      CustomAlerts.showWarning(
        context,
        'CUIT inválido',
        'El CUIT debe tener 11 dígitos sin guiones. Ejemplo: 20123456789',
      );
      return;
    }

    setState(() => _isCreatingClient = true);

    final result = await _budgetService.createClientWithAFIP(cuit: cuit);

    setState(() => _isCreatingClient = false);

    if (!mounted) return;

    if (result['success'] == true) {
      setState(() {
        _selectedClient = result['client'];
        _cuitController.clear();
      });

      CustomAlerts.showSuccess(
        context,
        '✅ Cliente creado',
        'Sus datos se obtuvieron desde AFIP automáticamente',
      );
    } else {
      CustomAlerts.showError(
        context,
        'Error al crear cliente',
        result['message'] ?? 'No se pudieron obtener datos desde AFIP',
      );
    }
  }

  // Crear presupuesto
  Future<void> _createBudget() async {
    // ✅ Prevenir múltiples presiones
    if (_isCreating) {
      debugPrint('⚠️ Ya se está creando un presupuesto');
      return;
    }

    // Validaciones
    if (_selectedClient == null) {
      CustomAlerts.showWarning(
        context,
        'Cliente requerido',
        'Selecciona o crea un cliente para continuar.',
      );
      return;
    }

    if (_items.isEmpty) {
      CustomAlerts.showWarning(
        context,
        'Items requeridos',
        'Agrega al menos un producto o servicio.',
      );
      return;
    }

    // Confirmación simple
    final confirm = await CustomAlerts.showConfirmation(
      context,
      '¿Crear presupuesto?',
      'Cliente: ${_selectedClient!.name}\n'
      '${_items.length} ${_items.length == 1 ? 'item' : 'items'}\n'
      'Total: ${NumberFormat.currency(symbol: '\$', decimalDigits: 2).format(_total)}\n'
      '+ Impuestos',
    );

    if (confirm != true) return;

    // ✅ Marcar como creando y actualizar UI
    setState(() {
      _isCreating = true;
    });

    try {
      // Preparar items para envío
      final itemsData = _items.map((item) {
        return {
          'product_id': item.productId,
          'codigo': item.codigo,
          'descripcion': item.descripcion,
          'tipo_item': item.tipoItem,
          'unit_type': item.unitType,
          'quantity': item.quantity,
          'unit_price': item.unitPrice,
          'subtotal': item.subtotal,
        };
      }).toList();

      // Crear presupuesto (fecha automática: hoy)
      final provider = Provider.of<BudgetProvider>(context, listen: false);

      // ✅ AGREGAR: Mostrar loading mientras se crea
      CustomAlerts.showLoadingAlert(context, title: 'Creando presupuesto...');

      final result = await provider.createBudget(
      clientId: _selectedClient!.id!,
      fecha: DateFormat('yyyy-MM-dd').format(DateTime.now()), // Fecha automática
      items: itemsData,
      description: _descriptionController.text.trim().isEmpty 
          ? null 
          : _descriptionController.text.trim(),
    );

    // ✅ AGREGAR: Cerrar loading
    if (mounted) Navigator.pop(context);

    if (!mounted) return;

    if (result['success'] == true) {
      // Mostrar mensaje de éxito y esperar a que se cierre automáticamente
      await CustomAlerts.showSuccess(
        context,
        '✅ Presupuesto creado!',
        'Nro: ${result['budget'].nroFactura}',
      );

      // Volver a la lista de presupuestos (después de que se cierra el mensaje)
      if (mounted) Navigator.pop(context);
      
      // La lista se actualiza automáticamente en background (ver BudgetProvider.createBudget)
    } else {
      CustomAlerts.showError(
        context,
        'Error al crear presupuesto',
        result['message'] ?? 'Error desconocido',
      );
    }
    } finally {
      // ✅ SIEMPRE resetear flag, incluso si hay error
      if (mounted) {
        setState(() {
          _isCreating = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final formatter = NumberFormat.currency(symbol: '\$', decimalDigits: 2);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Nuevo Presupuesto'),
        elevation: 0,
        backgroundColor: const Color(0xFF00274E),  // ✅ Fondo azul oscuro
        foregroundColor: Colors.white,
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
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Cliente
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Cliente',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.grey[800],
                      ),
                    ),
                    const SizedBox(height: 12),

                    if (_selectedClient != null)
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.blue[50],
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.blue[200]!),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.person, color: Colors.blue),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    _selectedClient!.name ?? '',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  if (_selectedClient!.cuit != null)
                                    Text(
                                      'CUIT: ${_selectedClient!.cuit}',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Colors.grey[700],
                                      ),
                                    ),
                                ],
                              ),
                            ),
                            IconButton(
                              icon: const Icon(Icons.close, size: 20),
                              onPressed: () =>
                                  setState(() => _selectedClient = null),
                            ),
                          ],
                        ),
                      )
                    else
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Búsqueda de cliente
                          TextField(
                            controller: _clientSearchController,
                            decoration: InputDecoration(
                              labelText: 'Buscar Cliente',
                              hintText: 'Nombre o CUIT...',
                              prefixIcon: const Icon(Icons.search),
                              suffixIcon: _clientSearchController.text.isNotEmpty
                                  ? IconButton(
                                      icon: const Icon(Icons.close),
                                      onPressed: () {
                                        _clientSearchController.clear();
                                        setState(() => _clientSearchResults = []);
                                      },
                                    )
                                  : null,
                              border: const OutlineInputBorder(),
                            ),
                            onChanged: _searchClients,
                          ),
                          const SizedBox(height: 8),

                          // Resultados de búsqueda de clientes
                          if (_isSearchingClients)
                            const Center(
                              child: Padding(
                                padding: EdgeInsets.all(16),
                                child: CircularProgressIndicator(),
                              ),
                            )
                          else if (_clientSearchResults.isNotEmpty)
                            Container(
                              constraints: const BoxConstraints(maxHeight: 200),
                              decoration: BoxDecoration(
                                border: Border.all(color: Colors.grey[300]!),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: ListView.builder(
                                shrinkWrap: true,
                                itemCount: _clientSearchResults.length,
                                itemBuilder: (context, index) {
                                  final client = _clientSearchResults[index];
                                  return ListTile(
                                    leading: const Icon(Icons.person, size: 20),
                                    title: Text(client.name ?? ''),
                                    subtitle: client.cuit != null
                                        ? Text('CUIT: ${client.cuit}')
                                        : null,
                                    onTap: () => _selectClient(client),
                                  );
                                },
                              ),
                            ),
                          
                          const SizedBox(height: 12),
                          const Divider(),
                          const SizedBox(height: 12),
                          
                          // Crear nuevo con AFIP
                          Text(
                            'O crear nuevo cliente con CUIT de AFIP',
                            style: TextStyle(
                              fontSize: 13,
                              color: Colors.grey[700],
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(
                                child: TextField(
                                  controller: _cuitController,
                                  decoration: const InputDecoration(
                                    labelText: 'CUIT (sin guiones)',
                                    hintText: '20123456789',
                                    prefixIcon: Icon(Icons.badge),
                                    border: OutlineInputBorder(),
                                  ),
                                  keyboardType: TextInputType.number,
                                  inputFormatters: [
                                    FilteringTextInputFormatter.digitsOnly,
                                    LengthLimitingTextInputFormatter(11),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 8),
                              ElevatedButton(
                                onPressed: _isCreatingClient
                                    ? null
                                    : _createClientWithAFIP,
                                style: ElevatedButton.styleFrom(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 16,
                                    vertical: 16,
                                  ),
                                ),
                                child: _isCreatingClient
                                    ? const SizedBox(
                                        width: 20,
                                        height: 20,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                        ),
                                      )
                                    : const Text('Alta\nAFIP', textAlign: TextAlign.center),
                              ),
                            ],
                          ),
                        ],
                      ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Descripción del presupuesto
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Descripción (opcional)',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: Colors.grey[800],
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _descriptionController,
                      decoration: const InputDecoration(
                        hintText: 'Ej: Presupuesto para reparación...',
                        border: OutlineInputBorder(),
                        contentPadding: EdgeInsets.all(12),
                      ),
                      maxLines: 2,
                      maxLength: 500,
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Items
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Items (${_items.length})',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey[800],
                          ),
                        ),
                        TextButton.icon(
                          onPressed: () =>
                              setState(() => _showProductSearch = true),
                          icon: const Icon(Icons.add),
                          label: const Text('Agregar'),
                        ),
                      ],
                    ),

                    if (_showProductSearch) ...[
                      const SizedBox(height: 12),
                      // Filtro tipo
                      Row(
                        children: [
                          const Text('Mostrar: ', style: TextStyle(fontSize: 12)),
                          const SizedBox(width: 8),
                          ChoiceChip(
                            label: const Text('Todos', style: TextStyle(fontSize: 12)),
                            selected: _tipoFilter == null,
                            onSelected: (selected) {
                              setState(() {
                                _tipoFilter = null;
                                if (_productSearchController.text.isNotEmpty) {
                                  _searchProductsServices(
                                      _productSearchController.text);
                                }
                              });
                            },
                          ),
                          const SizedBox(width: 4),
                          ChoiceChip(
                            label: const Text('Productos', style: TextStyle(fontSize: 12)),
                            selected: _tipoFilter == 'P',
                            onSelected: (selected) {
                              setState(() {
                                _tipoFilter = 'P';
                                if (_productSearchController.text.isNotEmpty) {
                                  _searchProductsServices(
                                      _productSearchController.text);
                                }
                              });
                            },
                          ),
                          const SizedBox(width: 4),
                          ChoiceChip(
                            label: const Text('Servicios', style: TextStyle(fontSize: 12)),
                            selected: _tipoFilter == 'S',
                            onSelected: (selected) {
                              setState(() {
                                _tipoFilter = 'S';
                                if (_productSearchController.text.isNotEmpty) {
                                  _searchProductsServices(
                                      _productSearchController.text);
                                }
                              });
                            },
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      // Búsqueda
                      TextField(
                        controller: _productSearchController,
                        decoration: InputDecoration(
                          labelText: 'Buscar producto o servicio',
                          prefixIcon: const Icon(Icons.search),
                          suffixIcon: _productSearchController.text.isNotEmpty
                              ? IconButton(
                                  icon: const Icon(Icons.close),
                                  onPressed: () {
                                    _productSearchController.clear();
                                    setState(() {
                                      _productSearchResults = [];
                                      _showProductSearch = false;
                                    });
                                  },
                                )
                              : null,
                          border: const OutlineInputBorder(),
                        ),
                        onChanged: (value) {
                          _searchProductsServices(value);
                        },
                      ),
                      const SizedBox(height: 8),

                      // Resultados de búsqueda
                      if (_isSearchingProducts)
                        const Center(
                          child: Padding(
                            padding: EdgeInsets.all(16),
                            child: CircularProgressIndicator(),
                          ),
                        )
                      else if (_productSearchResults.isNotEmpty)
                        Container(
                          constraints: const BoxConstraints(maxHeight: 300),
                          child: ListView.builder(
                            shrinkWrap: true,
                            itemCount: _productSearchResults.length,
                            itemBuilder: (context, index) {
                              final product = _productSearchResults[index];
                              return ListTile(
                                leading: Icon(
                                  product.tipoItem == 'S'
                                      ? Icons.home_repair_service
                                      : Icons.inventory_2,
                                  color: product.tipoItem == 'S'
                                      ? Colors.orange
                                      : Colors.blue,
                                ),
                                title: Text(product.descripcion ?? ''),
                                subtitle: Text(product.codigo ?? ''),
                                trailing: Text(
                                  formatter.format(product.precio ?? 0),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                onTap: () => _addItem(product),
                              );
                            },
                          ),
                        ),
                      const SizedBox(height: 8),
                    ],

                    // Lista de items agregados
                    if (_items.isNotEmpty) ...[
                      const Divider(),
                      ListView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: _items.length,
                        itemBuilder: (context, index) {
                          final item = _items[index];
                          return Card(
                            margin: const EdgeInsets.symmetric(vertical: 4),
                            color: Colors.grey[50],
                            child: ListTile(
                              leading: Icon(
                                item.isService
                                    ? Icons.home_repair_service
                                    : Icons.inventory_2,
                                color: item.isService
                                    ? Colors.orange
                                    : Colors.blue,
                                size: 20,
                              ),
                              title: Text(
                                item.descripcion,
                                style: const TextStyle(fontSize: 13),
                              ),
                              subtitle: Text(
                                '${item.quantity} ${item.unitType} × ${formatter.format(item.unitPrice)}',
                                style: const TextStyle(fontSize: 11),
                              ),
                              trailing: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(
                                    formatter.format(item.subtotal),
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      color: Colors.green,
                                    ),
                                  ),
                                  IconButton(
                                    icon: const Icon(
                                      Icons.delete,
                                      size: 20,
                                      color: Colors.red,
                                    ),
                                    onPressed: () => _removeItem(index),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Total
            if (_items.isNotEmpty)
              Card(
                color: Colors.green[50],
                elevation: 4,
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'TOTAL',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            formatter.format(_total),
                            style: TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                              color: Colors.green[700],
                            ),
                          ),
                          Text(
                            '+ Impuestos',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey[600],
                              fontStyle: FontStyle.italic,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),

            const SizedBox(height: 80),
          ],
        ),
      ),
    );
  }
}

// Dialog para agregar item con cantidad y precio
class _AddItemDialog extends StatefulWidget {
  final Product product;
  final Function(double, String, double) onAdd;

  const _AddItemDialog({
    required this.product,
    required this.onAdd,
  });

  @override
  State<_AddItemDialog> createState() => _AddItemDialogState();
}

class _AddItemDialogState extends State<_AddItemDialog> {
  final _quantityController = TextEditingController(text: '1');
  final _priceController = TextEditingController();
  String _unitType = 'Unidad';

  @override
  void initState() {
    super.initState();
    _priceController.text = (widget.product.precio ?? 0).toString();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Agregar Item'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.product.descripcion ?? '',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            Text(
              widget.product.codigo ?? '',
              style: TextStyle(color: Colors.grey[600], fontSize: 12),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _quantityController,
              decoration: const InputDecoration(
                labelText: 'Cantidad',
                border: OutlineInputBorder(),
              ),
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              value: _unitType,
              decoration: const InputDecoration(
                labelText: 'Unidad',
                border: OutlineInputBorder(),
              ),
              items: const [
                DropdownMenuItem(value: 'Unidad', child: Text('Unidad')),
                DropdownMenuItem(value: 'Rollo', child: Text('Rollo')),
                DropdownMenuItem(value: 'Metros', child: Text('Metros')),
              ],
              onChanged: (value) {
                if (value != null) setState(() => _unitType = value);
              },
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _priceController,
              decoration: const InputDecoration(
                labelText: 'Precio Unitario',
                prefixText: '\$ ',
                border: OutlineInputBorder(),
              ),
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Cancelar'),
        ),
        ElevatedButton(
          onPressed: () {
            final quantity = double.tryParse(_quantityController.text) ?? 0;
            final price = double.tryParse(_priceController.text) ?? 0;

            if (quantity <= 0 || price < 0) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Cantidad y precio deben ser válidos'),
                  backgroundColor: Colors.orange,
                ),
              );
              return;
            }

            widget.onAdd(quantity, _unitType, price);
            Navigator.pop(context);
          },
          child: const Text('Agregar'),
        ),
      ],
    );
  }
}
