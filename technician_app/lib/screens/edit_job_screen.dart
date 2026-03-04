import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:geolocator/geolocator.dart';
import 'dart:convert';
import '../providers/job_provider.dart';
import '../models/job.dart';
import '../models/address.dart';
import '../models/technician.dart';
import '../models/product.dart';
import '../services/auth_service.dart';
import '../utils/custom_alerts.dart';

class EditJobScreen extends StatefulWidget {
  final Job job;

  const EditJobScreen({super.key, required this.job});

  @override
  State<EditJobScreen> createState() => _EditJobScreenState();
}

class _EditJobScreenState extends State<EditJobScreen> with ButtonLockMixin {
  final _formKey = GlobalKey<FormState>();
  final _descriptionController = TextEditingController();
  final _productSearchController = TextEditingController();
  final _quantityController = TextEditingController(text: '1.00');
  
  late DateTime _selectedDate;
  late TimeOfDay _selectedTime;
  List<Address> _addresses = [];
  Address? _selectedAddress;
  bool _isLoadingAddresses = false;
  List<Technician> _technicians = [];
  List<int> _selectedTechnicianIds = [];
  
  // Productos
  List<Product> _productSearchResults = [];
  List<Product> _initialProducts = [];
  Product? _selectedProduct;
  String _selectedUnitType = 'Unidad';
  List<SelectedProduct> _selectedProducts = [];
  bool _isSearchingProducts = false;

  @override
  void initState() {
    super.initState();
    _descriptionController.text = widget.job.jobDescription ?? '';
    
    // Parsear fecha y hora
    if (widget.job.visitDatetime != null) {
      final visitDateTime = DateTime.parse(widget.job.visitDatetime!);
      _selectedDate = visitDateTime;
      _selectedTime = TimeOfDay(hour: visitDateTime.hour, minute: visitDateTime.minute);
    } else {
      _selectedDate = DateTime.now();
      _selectedTime = TimeOfDay.now();
    }
    
    // Cargar técnicos y direcciones del cliente
    _loadTechnicians();
    _loadClientAddresses();
    _loadInitialProducts();
    _loadProducts();
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    _productSearchController.dispose();
    _quantityController.dispose();
    super.dispose();
  }

  Future<void> _loadTechnicians() async {
    final authService = AuthService();
    final technicians = await authService.getTechnicians();
    setState(() {
      _technicians = technicians;
      // Pre-seleccionar técnicos asignados al job
      if (widget.job.technicians != null) {
        _selectedTechnicianIds = widget.job.technicians!
          .map((t) => t['id'] as int)
          .toList();
      }
    });
  }

  Future<void> _loadClientAddresses() async {
    setState(() {
      _isLoadingAddresses = true;
    });

    final jobProvider = context.read<JobProvider>();
    final addresses = await jobProvider.getClientAddresses(widget.job.clientId!);

    setState(() {
      _addresses = addresses;
      _isLoadingAddresses = false;
      
      // Seleccionar la dirección actual si está en la lista
      if (widget.job.addressId != null) {
        try {
          _selectedAddress = addresses.firstWhere((a) => a.id == widget.job.addressId);
        } catch (e) {
          // Si no se encuentra, dejar sin selección
          if (addresses.length == 1) {
            _selectedAddress = addresses[0];
          }
        }
      } else if (addresses.length == 1) {
        _selectedAddress = addresses[0];
      }
    });
  }

  Future<void> _loadInitialProducts() async {
    print('🔵 EDIT: _loadInitialProducts: Iniciando carga...');
    final authService = AuthService();
    final productsData = await authService.getProducts();
    print('🔵 EDIT: productsData recibidos: ${productsData.length}');
    final products = productsData.map((json) => Product.fromJson(json)).toList();
    print('🔵 EDIT: ${products.length} productos parseados');
    if (products.isNotEmpty) {
      print('🔵 EDIT: Primer producto: ${products[0].displayName}');
    }
    setState(() {
      _initialProducts = products;
      _productSearchResults = products; // Mostrar inicialmente los 10 productos del login
    });
    print('🔵 EDIT: Estado actualizado con ${_productSearchResults.length} productos');
  }

  Future<void> _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      locale: const Locale('es'),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF00274E),
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: Colors.black,
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      setState(() {
        _selectedDate = picked;
      });
    }
  }
  // Cargar productos existentes del job
  void _loadProducts() {
    print('🟠 EDIT: _loadProducts iniciando...');
    print('🟠 EDIT: widget.job.products = ${widget.job.products}');
    if (widget.job.products != null && widget.job.products!.isNotEmpty) {
      print('🟠 EDIT: Hay ${widget.job.products!.length} productos');
      print('🟠 EDIT: Tipo de widget.job.products[0]: ${widget.job.products![0].runtimeType}');
      try {
        setState(() {
          _selectedProducts = widget.job.products!.map((pData) {
            print('🟠 EDIT: Procesando producto: $pData');
            
            // Convertir quantity de manera segura (puede venir como String o num)
            double parsedQuantity = 1.0;
            final quantityValue = pData['quantity'];
            if (quantityValue is num) {
              parsedQuantity = quantityValue.toDouble();
            } else if (quantityValue is String) {
              parsedQuantity = double.tryParse(quantityValue) ?? 1.0;
            }
            
            // Convertir product_id de manera segura
            int productId;
            final productIdValue = pData['product_id'];
            if (productIdValue is int) {
              productId = productIdValue;
            } else if (productIdValue is String) {
              productId = int.tryParse(productIdValue) ?? 0;
            } else {
              productId = 0;
            }
            
            return SelectedProduct(
              product: Product(
                id: productId,
                codigo: pData['codigo'] as String,
                descripcion: pData['descripcion'] as String,
                isFromColppy: pData['is_from_colppy'] == 1 || pData['is_from_colppy'] == '1' || pData['is_from_colppy'] == true,
              ),
              unitType: pData['unit_type'] as String? ?? 'Unidad',
              quantity: parsedQuantity,
              uniqueId: pData['id']?.toString() ?? DateTime.now().millisecondsSinceEpoch.toString(),
            );
          }).toList();
        });
        print('✅ EDIT: Productos cargados: ${_selectedProducts.length}');
      } catch (e, stack) {
        print('❌ EDIT: Error al cargar productos: $e');
        print('❌ EDIT: Stack: $stack');
      }
    } else {
      print('🟠 EDIT: No hay productos para cargar');
    }
  }

  // Buscar productos
  Future<void> _searchProducts(String query) async {
    if (query.length < 2) {
      setState(() {
        _productSearchResults = _initialProducts; // Mostrar productos iniciales
      });
      return;
    }

    setState(() {
      _isSearchingProducts = true;
    });

    print('🔍 Buscando productos: "$query"');
    final jobProvider = context.read<JobProvider>();
    final results = await jobProvider.searchProducts(query);
    print('📦 Productos encontrados: ${results.length}');

    setState(() {
      _productSearchResults = results;
      _isSearchingProducts = false;
    });

    if (results.isEmpty && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('No se encontraron productos con "$query"'),
          backgroundColor: Colors.orange,
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }

  // Agregar producto seleccionado a la lista
  void _addProduct() {
    if (_selectedProduct == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Debes seleccionar un producto'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final quantity = double.tryParse(_quantityController.text);
    if (quantity == null || quantity <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('La cantidad debe ser mayor a 0'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    // Verificar si ya existe (permitimos duplicados pero avisamos)
    final exists = _selectedProducts.any((p) => p.product.id == _selectedProduct!.id);
    if (exists) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Este producto ya está agregado (se permite duplicados)'),
          backgroundColor: Colors.blue,
          duration: Duration(seconds: 2),
        ),
      );
    }

    setState(() {
      _selectedProducts.add(SelectedProduct(
        product: _selectedProduct!,
        unitType: _selectedUnitType,
        quantity: quantity,
      ));
      
      // Limpiar selección
      _selectedProduct = null;
      _productSearchController.clear();
      _productSearchResults = [];
      _quantityController.text = '1.00';
      _selectedUnitType = 'Unidad';
    });

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Producto agregado'),
        backgroundColor: Colors.green,
        duration: Duration(seconds: 1),
      ),
    );
  }

  // Eliminar producto de la lista
  void _removeProduct(String uniqueId) {
    setState(() {
      _selectedProducts.removeWhere((p) => p.uniqueId == uniqueId);
    });

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Producto eliminado'),
        backgroundColor: Colors.grey,
        duration: Duration(seconds: 1),
      ),
    );
  }
  Future<void> _selectTime() async {
    final TimeOfDay? picked = await showTimePicker(
      context: context,
      initialTime: _selectedTime,
    );

    if (picked != null) {
      setState(() {
        _selectedTime = picked;
      });
    }
  }

  Future<void> _addNewAddress() async {
    // Mostrar diálogo para agregar dirección
    final result = await showDialog<Map<String, String>>(
      context: context,
      builder: (context) {
        final streetController = TextEditingController();
        final numberController = TextEditingController();
        final cityController = TextEditingController();
        final detailController = TextEditingController();
        
        return AlertDialog(
          title: const Text('Nueva Dirección'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: streetController,
                  decoration: const InputDecoration(
                    labelText: 'Calle *',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: numberController,
                  decoration: const InputDecoration(
                    labelText: 'Número *',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: cityController,
                  decoration: const InputDecoration(
                    labelText: 'Ciudad *',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: detailController,
                  decoration: const InputDecoration(
                    labelText: 'Detalle (opcional)',
                    border: OutlineInputBorder(),
                    hintText: 'Piso, depto, etc.',
                  ),
                  maxLines: 2,
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
                if (streetController.text.trim().isEmpty ||
                    numberController.text.trim().isEmpty ||
                    cityController.text.trim().isEmpty) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Completa los campos obligatorios'),
                      backgroundColor: Colors.orange,
                    ),
                  );
                  return;
                }
                
                Navigator.pop(context, {
                  'street': streetController.text.trim(),
                  'number': numberController.text.trim(),
                  'city': cityController.text.trim(),
                  'detail': detailController.text.trim(),
                });
              },
              child: const Text('Guardar'),
            ),
          ],
        );
      },
    );
    
    if (result != null && mounted) {
      // Llamar al backend para crear la dirección
      try {
        final jobProvider = Provider.of<JobProvider>(context, listen: false);
        final clientId = widget.job.clientId;
        
        if (clientId == null) {
          throw Exception('Cliente no válido');
        }
        
        final newAddress = await jobProvider.createClientAddress(
          clientId,
          result['street']!,
          result['number']!,
          result['city']!,
          result['detail']!,
        );
        
        if (newAddress != null && mounted) {
          // Recargar las direcciones del cliente
          final addresses = await jobProvider.getClientAddresses(clientId);
          
          setState(() {
            _addresses = addresses;
            _selectedAddress = newAddress;
          });
          
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Dirección agregada exitosamente'),
                backgroundColor: Colors.green,
              ),
            );
          }
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Error al crear dirección: $e'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    }
  }

  Future<Map<String, dynamic>?> _getCurrentLocation() async {
    try {
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('⚠️ Permiso de ubicación denegado'),
                backgroundColor: Colors.orange,
              ),
            );
          }
          return null;
        }
      }

      if (permission == LocationPermission.deniedForever) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('⚠️ Los permisos de ubicación están permanentemente denegados'),
              backgroundColor: Colors.orange,
            ),
          );
        }
        return null;
      }

      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      return {
        'latitude': position.latitude,
        'longitude': position.longitude,
        'jsongeolocation': jsonEncode({
          'latitude': position.latitude,
          'longitude': position.longitude,
          'accuracy': position.accuracy,
          'timestamp': position.timestamp.toIso8601String(),
        }),
      };
    } catch (e) {
      print('❌ Error obteniendo ubicación: $e');
      return null;
    }
  }

  Future<void> _updateJob() async {
    // Prevenir múltiples envíos
    if (isButtonLocked) return;

    if (!_formKey.currentState!.validate()) {
      return;
    }

    if (_selectedAddress == null) {
      await CustomAlerts.showInfoAlert(
        context,
        title: 'Selecciona una dirección',
        message: 'Debes seleccionar una dirección antes de continuar',
      );
      return;
    }

    // Bloquear botón para prevenir doble clic
    lockButton();

    try {
      // Combinar fecha y hora
      final visitDateTime = DateTime(
        _selectedDate.year,
        _selectedDate.month,
        _selectedDate.day,
        _selectedTime.hour,
        _selectedTime.minute,
      );

      print('🔄 _updateJob: Iniciando actualización...');
      
      // Obtener ubicación GPS con timeout
      Map<String, dynamic>? location;
      try {
        print('📍 _updateJob: Obteniendo ubicación GPS...');
        location = await _getCurrentLocation().timeout(
          const Duration(seconds: 10),
          onTimeout: () {
            print('⏱️ _updateJob: Timeout obteniendo ubicación');
            return null;
          },
        );
        print('📍 _updateJob: Ubicación obtenida: $location');
      } catch (e) {
        print('❌ _updateJob: Error obteniendo ubicación: $e');
        location = null;
      }

      print('🚀 _updateJob: Llamando a jobProvider.updateJob...');
      final jobProvider = context.read<JobProvider>();
      
      // Ejecutar operación con alertas automáticas
      final success = await CustomAlerts.executeWithLoading(
        context,
        operation: () async {
          final result = await jobProvider.updateJob(
            jobId: widget.job.id!,
            addressId: _selectedAddress!.id,
            visitDateTime: visitDateTime,
            description: _descriptionController.text.trim(),
            latitude: location?['latitude'],
            longitude: location?['longitude'],
            jsonGeolocation: location?['jsongeolocation'],
            technicianIds: _selectedTechnicianIds.isNotEmpty ? _selectedTechnicianIds : null,
            products: _selectedProducts.isNotEmpty ? _selectedProducts : null,
          );
          return result;
        },
        loadingMessage: 'Actualizando tarea...',
        successTitle: 'Tarea actualizada',
        successMessage: 'La tarea se actualizó exitosamente',
        errorTitle: 'Error al actualizar',
        getErrorMessage: () => jobProvider.errorMessage ?? 'Error inesperado al actualizar la tarea',
      );
      
      print('📊 _updateJob: Resultado: $success');

      if (mounted && success) {
        // Esperar a que se cierre el alert de éxito antes de cerrar la pantalla
        await Future.delayed(const Duration(milliseconds: 2500));
        if (mounted) {
          Navigator.pop(context, true);
        }
      }
    } finally {
      // Desbloquear botón
      unlockButton();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        flexibleSpace: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.centerLeft,
              end: Alignment.centerRight,
              colors: [Color(0xFF00274E), Color(0xFF004B87)],
            ),
          ),
        ),
        title: const Text('Editar Tarea', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.check, color: Colors.white),
            onPressed: _updateJob,
            tooltip: 'Guardar',
          ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Cliente (solo lectura)
            Card(
              elevation: 3,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              shadowColor: const Color(0xFF00274E).withOpacity(0.3),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Cliente',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    ListTile(
                      leading: CircleAvatar(
                        child: Text(widget.job.clientName?[0].toUpperCase() ?? 'C'),
                      ),
                      title: Text(widget.job.clientName ?? 'Cliente'),
                      subtitle: const Text('No se puede cambiar el cliente'),
                    ),
                  ],
                ),
              ),
            ),
            
            const SizedBox(height: 16),
            
            // Direcciones del cliente
            Card(
              elevation: 3,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              shadowColor: const Color(0xFF00274E).withOpacity(0.3),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Dirección',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    
                    if (_isLoadingAddresses)
                      const Center(
                        child: Padding(
                          padding: EdgeInsets.all(16),
                          child: CircularProgressIndicator(),
                        ),
                      )
                    else if (_addresses.isEmpty)
                      Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          children: [
                            const Text(
                              'Este cliente no tiene direcciones registradas.',
                              style: TextStyle(color: Colors.orange),
                            ),
                            const SizedBox(height: 12),
                            ElevatedButton.icon(
                              onPressed: () => _addNewAddress(),
                              icon: const Icon(Icons.add_location),
                              label: const Text('Agregar Dirección'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF00274E),
                              ),
                            ),
                          ],
                        ),
                      )
                    else ...[
                      DropdownButtonFormField<Address>(
                        value: _selectedAddress,
                        decoration: const InputDecoration(
                          border: OutlineInputBorder(),
                          hintText: 'Selecciona una dirección',
                        ),
                        items: _addresses.map((address) {
                          return DropdownMenuItem<Address>(
                            value: address,
                            child: Text(
                              address.fullAddress,
                              overflow: TextOverflow.ellipsis,
                              maxLines: 2,
                            ),
                          );
                        }).toList(),
                        isExpanded: true,
                        onChanged: (Address? value) {
                          setState(() {
                            _selectedAddress = value;
                          });
                        },
                        validator: (value) {
                          if (value == null) {
                            return 'Debes seleccionar una dirección';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 12),
                      Center(
                        child: TextButton.icon(
                          onPressed: () => _addNewAddress(),
                          icon: const Icon(Icons.add_location),
                          label: const Text('Agregar Nueva Dirección'),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            
            // Fecha y Hora
            Card(
              elevation: 3,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              shadowColor: const Color(0xFF00274E).withOpacity(0.3),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Fecha y Hora de Visita',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    
                    Row(
                      children: [
                        Expanded(
                          child: InkWell(
                            onTap: _selectDate,
                            child: Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                border: Border.all(color: Colors.grey.shade300),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.calendar_today),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      DateFormat('d MMM yyyy', 'es').format(_selectedDate),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: InkWell(
                            onTap: _selectTime,
                            child: Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                border: Border.all(color: Colors.grey.shade300),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.access_time),
                                  const SizedBox(width: 8),
                                  Text(_selectedTime.format(context)),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            
            const SizedBox(height: 16),
            
            // Descripción
            Card(
              elevation: 3,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              shadowColor: const Color(0xFF00274E).withOpacity(0.3),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Descripción del Trabajo',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _descriptionController,
                      decoration: const InputDecoration(
                        hintText: 'Describe el trabajo a realizar...',
                        border: OutlineInputBorder(),
                      ),
                      maxLines: 5,
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'La descripción es requerida';
                        }
                        return null;
                      },
                    ),
                  ],
                ),
              ),
            ),
            
            const SizedBox(height: 16),
            
            // Técnicos (Opcional)
            Card(
              elevation: 3,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              shadowColor: const Color(0xFF00274E).withOpacity(0.3),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.engineering, color: Color(0xFF00274E)),
                        SizedBox(width: 8),
                        Text(
                          'Técnicos',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        SizedBox(width: 4),
                        Text(
                          '(Opcional)',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    if (_technicians.isEmpty)
                      const Text(
                        'Cargando técnicos...',
                        style: TextStyle(color: Colors.grey),
                      )
                    else
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: _technicians.map((tech) {
                          final isSelected = _selectedTechnicianIds.contains(tech.id);
                          return FilterChip(
                            label: Text(tech.name),
                            selected: isSelected,
                            onSelected: (selected) {
                              setState(() {
                                if (selected) {
                                  _selectedTechnicianIds.add(tech.id);
                                } else {
                                  _selectedTechnicianIds.remove(tech.id);
                                }
                              });
                            },
                            selectedColor: const Color(0xFF00274E).withOpacity(0.2),
                            checkmarkColor: const Color(0xFF00274E),
                          );
                        }).toList(),
                      ),
                  ],
                ),
              ),
            ),
            
            const SizedBox(height: 16),
            
            // Productos (Opcional)
            Card(
              elevation: 3,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              shadowColor: const Color(0xFF00274E).withOpacity(0.3),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.inventory_2, color: Color(0xFF00274E)),
                        SizedBox(width: 8),
                        Text(
                          'Productos',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        SizedBox(width: 4),
                        Text(
                          '(Opcional)',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    
                    // Buscar producto
                    TextField(
                      controller: _productSearchController,
                      decoration: const InputDecoration(
                        labelText: 'Buscar producto',
                        hintText: 'Escribe el código o nombre...',
                        prefixIcon: Icon(Icons.search),
                        border: OutlineInputBorder(),
                      ),
                      onChanged: _searchProducts,
                    ),
                    
                    // Resultados de búsqueda
                    if (_isSearchingProducts)
                      const Padding(
                        padding: EdgeInsets.all(16.0),
                        child: Center(child: CircularProgressIndicator()),
                      ),
                    
                    if (_productSearchResults.isNotEmpty && !_isSearchingProducts)
                      Container(
                        constraints: const BoxConstraints(maxHeight: 220),
                        margin: const EdgeInsets.only(top: 8),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          border: Border.all(color: const Color(0xFF00274E).withOpacity(0.3), width: 2),
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.grey.withOpacity(0.2),
                              spreadRadius: 1,
                              blurRadius: 4,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                              decoration: BoxDecoration(
                                color: const Color(0xFF00274E).withOpacity(0.1),
                                borderRadius: const BorderRadius.only(
                                  topLeft: Radius.circular(10),
                                  topRight: Radius.circular(10),
                                ),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.inventory_2, size: 16, color: Color(0xFF00274E)),
                                  const SizedBox(width: 8),
                                  Text(
                                    '${_productSearchResults.length} producto${_productSearchResults.length != 1 ? 's' : ''} encontrado${_productSearchResults.length != 1 ? 's' : ''}',
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.bold,
                                      color: Color(0xFF00274E),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Expanded(
                              child: ListView.separated(
                                shrinkWrap: true,
                                itemCount: _productSearchResults.length,
                                separatorBuilder: (context, index) => Divider(height: 1, color: Colors.grey.shade300),
                                itemBuilder: (context, index) {
                                  final product = _productSearchResults[index];
                                  final isSelected = _selectedProduct?.id == product.id;
                                  return InkWell(
                                    onTap: () {
                                      setState(() {
                                        _selectedProduct = product;
                                        _productSearchController.text = product.displayName;
                                        _productSearchResults = [];
                                      });
                                    },
                                    child: Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                      color: isSelected ? const Color(0xFF00274E).withOpacity(0.1) : null,
                                      child: Row(
                                        children: [
                                          CircleAvatar(
                                            radius: 16,
                                            backgroundColor: isSelected 
                                              ? const Color(0xFF00274E) 
                                              : Colors.grey.shade300,
                                            child: Icon(
                                              Icons.inventory_2,
                                              size: 16,
                                              color: isSelected ? Colors.white : Colors.grey.shade600,
                                            ),
                                          ),
                                          const SizedBox(width: 12),
                                          Expanded(
                                            child: Column(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              children: [
                                                Text(
                                                  product.codigo,
                                                  style: TextStyle(
                                                    fontWeight: FontWeight.bold,
                                                    fontSize: 13,
                                                    color: isSelected ? const Color(0xFF00274E) : Colors.black87,
                                                  ),
                                                ),
                                                const SizedBox(height: 2),
                                                Text(
                                                  product.descripcion,
                                                  style: TextStyle(
                                                    fontSize: 12,
                                                    color: Colors.grey.shade700,
                                                  ),
                                                  maxLines: 2,
                                                  overflow: TextOverflow.ellipsis,
                                                ),
                                              ],
                                            ),
                                          ),
                                          if (isSelected)
                                            const Icon(
                                              Icons.check_circle,
                                              color: Color(0xFF00274E),
                                              size: 24,
                                            ),
                                        ],
                                      ),
                                    ),
                                  );
                                },
                              ),
                            ),
                          ],
                        ),
                      ),
                    
                    const SizedBox(height: 12),
                    
                    // Tipo de unidad y cantidad
                    Row(
                      children: [
                        Expanded(
                          flex: 2,
                          child: DropdownButtonFormField<String>(
                            value: _selectedUnitType,
                            decoration: const InputDecoration(
                              labelText: 'Tipo',
                              border: OutlineInputBorder(),
                            ),
                            items: const [
                              DropdownMenuItem(value: 'Unidad', child: Text('Unidad')),
                              DropdownMenuItem(value: 'Rollo', child: Text('Rollo')),
                              DropdownMenuItem(value: 'Metros', child: Text('Metros')),
                            ],
                            onChanged: (value) {
                              if (value != null) {
                                setState(() {
                                  _selectedUnitType = value;
                                });
                              }
                            },
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          flex: 2,
                          child: TextField(
                            controller: _quantityController,
                            decoration: const InputDecoration(
                              labelText: 'Cantidad',
                              border: OutlineInputBorder(),
                            ),
                            keyboardType: const TextInputType.numberWithOptions(decimal: true),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          height: 56,
                          width: 56,
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFF00274E), Color(0xFF004B87)],
                            ),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: IconButton(
                            onPressed: _addProduct,
                            icon: const Icon(Icons.add, color: Colors.white),
                            tooltip: 'Agregar producto',
                          ),
                        ),
                      ],
                    ),
                    
                    // Lista de productos agregados
                    if (_selectedProducts.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      const Divider(),
                      const SizedBox(height: 8),
                      const Text(
                        'Productos agregados:',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                        ),
                      ),
                      const SizedBox(height: 8),
                      ListView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: _selectedProducts.length,
                        itemBuilder: (context, index) {
                          final selectedProduct = _selectedProducts[index];
                          return Card(
                            margin: const EdgeInsets.only(bottom: 8),
                            child: ListTile(
                              title: Text(
                                selectedProduct.product.displayName,
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                              subtitle: Text(
                                '${selectedProduct.unitType} - Cantidad: ${selectedProduct.quantity.toStringAsFixed(2)}',
                              ),
                              trailing: IconButton(
                                icon: const Icon(Icons.delete, color: Colors.red),
                                onPressed: () => _removeProduct(selectedProduct.uniqueId),
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
            
            const SizedBox(height: 24),
            
            // Botón guardar
            Container(
              width: double.infinity,
              height: 56,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.centerLeft,
                  end: Alignment.centerRight,
                  colors: isButtonLocked 
                    ? [Colors.grey.shade400, Colors.grey.shade500]
                    : [const Color(0xFF00274E), const Color(0xFF004B87)],
                ),
                borderRadius: BorderRadius.circular(30),
                boxShadow: [
                  BoxShadow(
                    color: isButtonLocked 
                      ? Colors.grey.withOpacity(0.3)
                      : const Color(0xFF00274E).withOpacity(0.4),
                    blurRadius: 8,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: ElevatedButton.icon(
                onPressed: isButtonLocked ? null : _updateJob,
                icon: Icon(
                  isButtonLocked ? Icons.lock : Icons.save, 
                  color: Colors.white,
                ),
                label: Text(
                  isButtonLocked ? 'Guardando...' : 'Actualizar Tarea',
                  style: const TextStyle(
                    fontSize: 16, 
                    fontWeight: FontWeight.bold, 
                    color: Colors.white,
                  ),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.transparent,
                  shadowColor: Colors.transparent,
                  disabledBackgroundColor: Colors.transparent,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(30),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
