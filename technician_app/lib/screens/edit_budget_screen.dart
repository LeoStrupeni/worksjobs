import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/budget_provider.dart';
import '../providers/job_provider.dart';
import '../services/product_service.dart';
import '../models/product.dart';
import '../models/budget.dart';
import '../models/budget_item.dart';
import '../utils/custom_alerts.dart';
import 'budget_detail_screen.dart';

class EditBudgetScreen extends StatefulWidget {
  final Budget budget;

  const EditBudgetScreen({
    super.key,
    required this.budget,
  });

  @override
  State<EditBudgetScreen> createState() => _EditBudgetScreenState();
}

class _EditBudgetScreenState extends State<EditBudgetScreen> {
  final ProductService _productService = ProductService();
  
  // Form controllers
  final _productSearchController = TextEditingController();
  final _descriptionController = TextEditingController();

  // Estado
  List<BudgetItem> _items = [];
  List<Product> _productSearchResults = [];
  bool _isSearchingProducts = false;
  bool _showProductSearch = false;
  String? _tipoFilter; // null = todos, 'P' = productos, 'S' = servicios

  @override
  void initState() {
    super.initState();
    
    // Cargar items existentes
    if (widget.budget.items != null) {
      _items = widget.budget.items!.map((item) => BudgetItem(
        productId: item.productId,
        codigo: item.codigo ?? '',
        descripcion: item.descripcion ?? '',
        tipoItem: item.tipoItem,
        unitType: item.unitType ?? 'Unidad',
        quantity: item.quantity ?? 1.0,
        unitPrice: item.unitPrice ?? 0.0,
        subtotal: item.subtotal ?? 0.0,
      )).toList();
    }
    
    // Cargar descripción si existe
    _descriptionController.text = widget.budget.observaciones ?? '';
  }

  @override
  void dispose() {
    _productSearchController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  // Calcular total
  double get _total {
    return _items.fold(0.0, (sum, item) => sum + item.subtotal);
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

  // Editar item existente
  void _editItem(int index) {
    final item = _items[index];
    showDialog(
      context: context,
      builder: (context) => _EditItemDialog(
        item: item,
        onUpdate: (quantity, unitType, unitPrice) {
          final subtotal = quantity * unitPrice;
          setState(() {
            _items[index] = BudgetItem(
              productId: item.productId,
              codigo: item.codigo,
              descripcion: item.descripcion,
              tipoItem: item.tipoItem,
              unitType: unitType,
              quantity: quantity,
              unitPrice: unitPrice,
              subtotal: subtotal,
            );
          });
        },
      ),
    );
  }

  // Eliminar item
  void _removeItem(int index) {
    setState(() => _items.removeAt(index));
  }

  // Actualizar presupuesto
  Future<void> _updateBudget() async {
    // Validaciones
    if (_items.isEmpty) {
      CustomAlerts.showWarning(
        context,
        'Items requeridos',
        'Debes tener al menos un producto o servicio.',
      );
      return;
    }

    // Confirmación
    final confirm = await CustomAlerts.showConfirmation(
      context,
      '¿Actualizar presupuesto?',
      '${_items.length} ${_items.length == 1 ? 'item' : 'items'}\n'
      'Total: ${NumberFormat.currency(symbol: '\$', decimalDigits: 2).format(_total)}',
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

    final provider = Provider.of<BudgetProvider>(context, listen: false);

    // Mostrar loading
    CustomAlerts.showLoadingAlert(context, title: 'Actualizando presupuesto...');

    final result = await provider.updateBudget(
      idFactura: widget.budget.idFactura ?? '',
      clientId: widget.budget.clientId ?? 0,
      items: itemsData,
      description: _descriptionController.text.trim().isEmpty 
          ? null 
          : _descriptionController.text.trim(),
    );

    // Cerrar loading
    if (mounted) Navigator.pop(context);

    if (!mounted) return;

    if (result['success'] == true) {
      CustomAlerts.showSuccess(
        context,
        '✅ Presupuesto actualizado',
        'Los cambios se guardaron correctamente',
      );

      // Volver al listado (pop hasta llegar al listado)
      Navigator.of(context).popUntil((route) => route.isFirst);
    } else {
      CustomAlerts.showError(
        context,
        'Error al actualizar',
        result['message'] ?? 'Error desconocido',
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final formatter = NumberFormat.currency(symbol: '\$', decimalDigits: 2);

    return Scaffold(
      appBar: AppBar(
        title: Text('Editar Presupuesto ${widget.budget.nroFactura ?? ''}'),
        elevation: 0,
        backgroundColor: const Color(0xFF00274E),
        foregroundColor: Colors.white,
        actions: [
          TextButton.icon(
            onPressed: _items.isNotEmpty ? _updateBudget : null,
            icon: const Icon(Icons.check, color: Colors.white),
            label: const Text(
              'GUARDAR',
              style: TextStyle(
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
            // Información del cliente (solo lectura)
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
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.grey[100],
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.grey[300]!),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.person, color: Colors.grey),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  widget.budget.clientName ?? '',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                if (widget.budget.clientCuit != null)
                                  Text(
                                    'CUIT: ${widget.budget.clientCuit}',
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.grey[700],
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        ],
                      ),
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
                              ),
                              title: Text(
                                item.descripcion,
                                style: const TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                              subtitle: Text(
                                '${item.quantity} ${item.unitType} × ${formatter.format(item.unitPrice)}',
                                style: const TextStyle(fontSize: 12),
                              ),
                              trailing: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(
                                    formatter.format(item.subtotal),
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  IconButton(
                                    icon: const Icon(Icons.edit, size: 18),
                                    onPressed: () => _editItem(index),
                                    padding: EdgeInsets.zero,
                                    constraints: const BoxConstraints(),
                                  ),
                                  IconButton(
                                    icon: const Icon(Icons.delete, size: 18, color: Colors.red),
                                    onPressed: () => _removeItem(index),
                                    padding: EdgeInsets.zero,
                                    constraints: const BoxConstraints(),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                      const Divider(),
                      // Total
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'TOTAL',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            Text(
                              formatter.format(_total),
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                color: Colors.green,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ] else
                      const Padding(
                        padding: EdgeInsets.all(16),
                        child: Center(
                          child: Text(
                            'Aún no hay items agregados',
                            style: TextStyle(color: Colors.grey),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// Dialog para agregar item
class _AddItemDialog extends StatefulWidget {
  final Product product;
  final Function(double quantity, String unitType, double unitPrice) onAdd;

  const _AddItemDialog({
    required this.product,
    required this.onAdd,
  });

  @override
  State<_AddItemDialog> createState() => _AddItemDialogState();
}

class _AddItemDialogState extends State<_AddItemDialog> {
  final _quantityController = TextEditingController(text: '1.00');
  final _priceController = TextEditingController();
  String _selectedUnitType = 'Unidad';

  @override
  void initState() {
    super.initState();
    _priceController.text = (widget.product.precio ?? 0).toStringAsFixed(2);
  }

  @override
  void dispose() {
    _quantityController.dispose();
    _priceController.dispose();
    super.dispose();
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
              'Código: ${widget.product.codigo ?? ''}',
              style: const TextStyle(fontSize: 12, color: Colors.grey),
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              value: _selectedUnitType,
              decoration: const InputDecoration(
                labelText: 'Tipo de unidad',
                border: OutlineInputBorder(),
              ),
              items: ['Unidad', 'Rollo', 'Metros']
                  .map((type) => DropdownMenuItem(
                        value: type,
                        child: Text(type),
                      ))
                  .toList(),
              onChanged: (value) {
                setState(() => _selectedUnitType = value!);
              },
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _quantityController,
              decoration: const InputDecoration(
                labelText: 'Cantidad',
                border: OutlineInputBorder(),
              ),
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'^\d+\.?\d{0,2}')),
              ],
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _priceController,
              decoration: const InputDecoration(
                labelText: 'Precio Unitario',
                border: OutlineInputBorder(),
                prefixText: '\$ ',
              ),
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'^\d+\.?\d{0,2}')),
              ],
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
            final quantity = double.tryParse(_quantityController.text) ?? 1.0;
            final price = double.tryParse(_priceController.text) ?? 0.0;
            
            if (quantity <= 0) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('La cantidad debe ser mayor a 0')),
              );
              return;
            }
            
            widget.onAdd(quantity, _selectedUnitType, price);
            Navigator.pop(context);
          },
          child: const Text('Agregar'),
        ),
      ],
    );
  }
}

// Dialog para editar item
class _EditItemDialog extends StatefulWidget {
  final BudgetItem item;
  final Function(double quantity, String unitType, double unitPrice) onUpdate;

  const _EditItemDialog({
    required this.item,
    required this.onUpdate,
  });

  @override
  State<_EditItemDialog> createState() => _EditItemDialogState();
}

class _EditItemDialogState extends State<_EditItemDialog> {
  late final TextEditingController _quantityController;
  late final TextEditingController _priceController;
  late String _selectedUnitType;

  @override
  void initState() {
    super.initState();
    _quantityController = TextEditingController(
      text: widget.item.quantity.toStringAsFixed(2),
    );
    _priceController = TextEditingController(
      text: widget.item.unitPrice.toStringAsFixed(2),
    );
    _selectedUnitType = widget.item.unitType;
  }

  @override
  void dispose() {
    _quantityController.dispose();
    _priceController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Editar Item'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.item.descripcion,
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            Text(
              'Código: ${widget.item.codigo}',
              style: const TextStyle(fontSize: 12, color: Colors.grey),
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              value: _selectedUnitType,
              decoration: const InputDecoration(
                labelText: 'Tipo de unidad',
                border: OutlineInputBorder(),
              ),
              items: ['Unidad', 'Rollo', 'Metros']
                  .map((type) => DropdownMenuItem(
                        value: type,
                        child: Text(type),
                      ))
                  .toList(),
              onChanged: (value) {
                setState(() => _selectedUnitType = value!);
              },
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _quantityController,
              decoration: const InputDecoration(
                labelText: 'Cantidad',
                border: OutlineInputBorder(),
              ),
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'^\d+\.?\d{0,2}')),
              ],
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _priceController,
              decoration: const InputDecoration(
                labelText: 'Precio Unitario',
                border: OutlineInputBorder(),
                prefixText: '\$ ',
              ),
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'^\d+\.?\d{0,2}')),
              ],
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
            final quantity = double.tryParse(_quantityController.text) ?? 1.0;
            final price = double.tryParse(_priceController.text) ?? 0.0;
            
            if (quantity <= 0) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('La cantidad debe ser mayor a 0')),
              );
              return;
            }
            
            widget.onUpdate(quantity, _selectedUnitType, price);
            Navigator.pop(context);
          },
          child: const Text('Guardar'),
        ),
      ],
    );
  }
}
