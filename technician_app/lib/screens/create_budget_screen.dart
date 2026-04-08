import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/budget_provider.dart';
import '../services/product_service.dart';
import '../services/budget_service.dart';
import '../services/job_service.dart';
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
  final JobService _jobService = JobService();
  
  // Form controllers
  final _searchController = TextEditingController();
  final _cuitController = TextEditingController();
  final _observacionesController = TextEditingController();

  // Estado
  Client? _selectedClient;
  DateTime _selectedDate = DateTime.now();
  List<BudgetItem> _items = [];
  List<Product> _searchResults = [];
  bool _isSearching = false;
  bool _isCreatingClient = false;
  bool _showProductSearch = false;
  String? _tipoFilter; // null = todos, 'P' = productos, 'S' = servicios

  @override
  void dispose() {
    _searchController.dispose();
    _cuitController.dispose();
    _observacionesController.dispose();
    super.dispose();
  }

  // Calcular total
  double get _total {
    return _items.fold(0.0, (sum, item) => sum + item.subtotal);
  }

  // Buscar productos/servicios
  Future<void> _searchProductsServices(String query) async {
    if (query.isEmpty) {
      setState(() => _searchResults = []);
      return;
    }

    setState(() => _isSearching = true);

    final result = await _productService.searchProductsAndServices(
      search: query,
      tipo: _tipoFilter,
      limit: 20,
    );

    setState(() {
      _isSearching = false;
      if (result['success'] == true) {
        _searchResults = result['products'] ?? [];
      } else {
        _searchResults = [];
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
            _searchResults = [];
            _searchController.clear();
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

  // Seleccionar cliente existente
  Future<void> _selectClient() async {
    final result = await _jobService.getClients();

    if (!mounted) return;

    if (result['success'] == true) {
      final clients = result['clients'] as List<Client>;

      if (clients.isEmpty) {
        CustomAlerts.showInfo(
          context,
          'No hay clientes',
          'Crea un cliente nuevo con su CUIT.',
        );
        return;
      }

      showDialog(
        context: context,
        builder: (context) => _ClientListDialog(
          clients: clients,
          onSelect: (client) {
            setState(() => _selectedClient = client);
          },
        ),
      );
    } else {
      if (!mounted) return;
      CustomAlerts.showError(
        context,
        'Error',
        result['message'] ?? 'Error al cargar clientes',
      );
    }
  }

  // Crear cliente con AFIP
  Future<void> _createClientWithAFIP() async {
    final cuit = _cuitController.text.trim();

    if (cuit.isEmpty) {
      CustomAlerts.showWarning(
        context,
        'CUIT requerido',
        'Ingresa el CUIT del cliente para obtener sus datos.',
      );
      return;
    }

    if (cuit.length != 11) {
      CustomAlerts.showWarning(
        context,
        'CUIT inválido',
        'El CUIT debe tener 11 dígitos sin guiones.',
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
        'Cliente creado',
        'Cliente agregado exitosamente',
      );
    } else {
      CustomAlerts.showError(
        context,
        'Error',
        result['message'] ?? 'Error al crear cliente',
      );
    }
  }

  // Seleccionar fecha
  Future<void> _selectDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
      locale: const Locale('es', 'ES'),
    );

    if (picked != null) {
      setState(() => _selectedDate = picked);
    }
  }

  // Crear presupuesto
  Future<void> _createBudget() async {
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

    // Confirmar creación
    final confirm = await CustomAlerts.showConfirmation(
      context,
      '¿Crear presupuesto?',
      'Total: ${NumberFormat.currency(symbol: '\$', decimalDigits: 2).format(_total)}\n'
      'Cliente: ${_selectedClient!.name}\n'
      '${_items.length} ${_items.length == 1 ? 'item' : 'items'}',
    );

    if (confirm != true) return;

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

    // Crear presupuesto
    final provider = Provider.of<BudgetProvider>(context, listen: false);

    final result = await provider.createBudget(
      clientId: _selectedClient!.id!,
      fecha: DateFormat('yyyy-MM-dd').format(_selectedDate),
      items: itemsData,
      observaciones: _observacionesController.text.trim().isEmpty
          ? null
          : _observacionesController.text.trim(),
    );

    if (!mounted) return;

    if (result['success'] == true) {
      CustomAlerts.showSuccess(
        context,
        '¡Presupuesto creado!',
        'Nro: ${result['budget'].nroFactura}',
      );

      // Navegar al detalle
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (context) => BudgetDetailScreen(
            budgetId: result['budget'].id,
          ),
        ),
      );
    } else {
      CustomAlerts.showError(
        context,
        'Error',
        result['message'] ?? 'Error al crear presupuesto',
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final formatter = NumberFormat.currency(symbol: '\$', decimalDigits: 2);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Nuevo Presupuesto'),
        elevation: 0,
        actions: [
          TextButton.icon(
            onPressed: _items.isNotEmpty && _selectedClient != null
                ? _createBudget
                : null,
            icon: const Icon(Icons.check, color: Colors.white),
            label: const Text(
              'CREAR',
              style: TextStyle(color: Colors.white),
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
                        children: [
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: _selectClient,
                              icon: const Icon(Icons.search),
                              label: const Text('Buscar Cliente Existente'),
                            ),
                          ),
                          const SizedBox(height: 8),
                          const Divider(),
                          const SizedBox(height: 8),
                          Text(
                            'O crear nuevo con CUIT',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey[600],
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
                                child: _isCreatingClient
                                    ? const SizedBox(
                                        width: 20,
                                        height: 20,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                        ),
                                      )
                                    : const Text('Alta AFIP'),
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

            // Fecha
            Card(
              child: ListTile(
                leading: const Icon(Icons.calendar_today, color: Colors.blue),
                title: const Text('Fecha'),
                subtitle: Text(
                  DateFormat('dd/MM/yyyy').format(_selectedDate),
                ),
                trailing: const Icon(Icons.edit),
                onTap: _selectDate,
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
                          const Text('Mostrar: '),
                          const SizedBox(width: 8),
                          ChoiceChip(
                            label: const Text('Todos'),
                            selected: _tipoFilter == null,
                            onSelected: (selected) {
                              setState(() {
                                _tipoFilter = null;
                                if (_searchController.text.isNotEmpty) {
                                  _searchProductsServices(
                                      _searchController.text);
                                }
                              });
                            },
                          ),
                          const SizedBox(width: 4),
                          ChoiceChip(
                            label: const Text('Productos'),
                            selected: _tipoFilter == 'P',
                            onSelected: (selected) {
                              setState(() {
                                _tipoFilter = 'P';
                                if (_searchController.text.isNotEmpty) {
                                  _searchProductsServices(
                                      _searchController.text);
                                }
                              });
                            },
                          ),
                          const SizedBox(width: 4),
                          ChoiceChip(
                            label: const Text('Servicios'),
                            selected: _tipoFilter == 'S',
                            onSelected: (selected) {
                              setState(() {
                                _tipoFilter = 'S';
                                if (_searchController.text.isNotEmpty) {
                                  _searchProductsServices(
                                      _searchController.text);
                                }
                              });
                            },
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      // Búsqueda
                      TextField(
                        controller: _searchController,
                        decoration: InputDecoration(
                          labelText: 'Buscar producto o servicio',
                          prefixIcon: const Icon(Icons.search),
                          suffixIcon: _searchController.text.isNotEmpty
                              ? IconButton(
                                  icon: const Icon(Icons.close),
                                  onPressed: () {
                                    _searchController.clear();
                                    setState(() {
                                      _searchResults = [];
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
                      if (_isSearching)
                        const Center(
                          child: Padding(
                            padding: EdgeInsets.all(16),
                            child: CircularProgressIndicator(),
                          ),
                        )
                      else if (_searchResults.isNotEmpty)
                        Container(
                          constraints: const BoxConstraints(maxHeight: 300),
                          child: ListView.builder(
                            shrinkWrap: true,
                            itemCount: _searchResults.length,
                            itemBuilder: (context, index) {
                              final product = _searchResults[index];
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

            // Observaciones
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  controller: _observacionesController,
                  decoration: const InputDecoration(
                    labelText: 'Observaciones (opcional)',
                    hintText: 'Notas adicionales sobre el presupuesto...',
                    border: OutlineInputBorder(),
                  ),
                  maxLines: 3,
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
                      Text(
                        formatter.format(_total),
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: Colors.green[700],
                        ),
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

// Dialog para seleccionar cliente existente
class _ClientListDialog extends StatefulWidget {
  final List<Client> clients;
  final Function(Client) onSelect;

  const _ClientListDialog({
    required this.clients,
    required this.onSelect,
  });

  @override
  State<_ClientListDialog> createState() => _ClientListDialogState();
}

class _ClientListDialogState extends State<_ClientListDialog> {
  final _searchController = TextEditingController();
  List<Client> _filteredClients = [];

  @override
  void initState() {
    super.initState();
    _filteredClients = widget.clients;
  }

  void _filterClients(String query) {
    setState(() {
      if (query.isEmpty) {
        _filteredClients = widget.clients;
      } else {
        _filteredClients = widget.clients.where((client) {
          final name = client.name?.toLowerCase() ?? '';
          final cuit = client.cuit ?? '';
          final search = query.toLowerCase();
          return name.contains(search) || cuit.contains(search);
        }).toList();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      child: Container(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.of(context).size.height * 0.7,
        ),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Seleccionar Cliente',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _searchController,
              decoration: const InputDecoration(
                labelText: 'Buscar',
                prefixIcon: Icon(Icons.search),
                border: OutlineInputBorder(),
              ),
              onChanged: _filterClients,
            ),
            const SizedBox(height: 16),
            Expanded(
              child: ListView.builder(
                itemCount: _filteredClients.length,
                itemBuilder: (context, index) {
                  final client = _filteredClients[index];
                  return ListTile(
                    leading: const Icon(Icons.person),
                    title: Text(client.name ?? ''),
                    subtitle: client.cuit != null
                        ? Text('CUIT: ${client.cuit}')
                        : null,
                    onTap: () {
                      widget.onSelect(client);
                      Navigator.pop(context);
                    },
                  );
                },
              ),
            ),
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Cancelar'),
              ),
            ),
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
