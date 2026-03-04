import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:geolocator/geolocator.dart';
import 'dart:convert';
import '../providers/job_provider.dart';
import '../models/client.dart';
import '../models/address.dart';
import '../models/technician.dart';
import '../models/product.dart';
import '../services/auth_service.dart';
import '../utils/custom_alerts.dart';

class CreateJobScreen extends StatefulWidget {
  const CreateJobScreen({super.key});

  @override
  State<CreateJobScreen> createState() => _CreateJobScreenState();
}

class _CreateJobScreenState extends State<CreateJobScreen> with ButtonLockMixin {
  final _formKey = GlobalKey<FormState>();
  final _descriptionController = TextEditingController();
  final _searchController = TextEditingController();
  final _productSearchController = TextEditingController();
  final _quantityController = TextEditingController(text: '1.00');
  
  Client? _selectedClient;
  Address? _selectedAddress;
  DateTime _selectedDate = DateTime.now();
  TimeOfDay _selectedTime = TimeOfDay.now();
  List<Client> _searchResults = [];
  List<Address> _addresses = [];
  bool _isSearching = false;
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
    _loadTechnicians();
    _loadInitialProducts();
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    _searchController.dispose();
    _productSearchController.dispose();
    _quantityController.dispose();
    super.dispose();
  }

  Future<void> _loadTechnicians() async {
    final authService = AuthService();
    final technicians = await authService.getTechnicians();
    setState(() {
      _technicians = technicians;
    });
  }

  Future<void> _loadInitialProducts() async {
    print('🔵 _loadInitialProducts: Iniciando carga...');
    final authService = AuthService();
    final productsData = await authService.getProducts();
    print('🔵 _loadInitialProducts: productsData recibidos: ${productsData.length}');
    final products = productsData.map((json) => Product.fromJson(json)).toList();
    print('🔵 _loadInitialProducts: ${products.length} productos parseados');
    if (products.isNotEmpty) {
      print('🔵 Primer producto: ${products[0].displayName}');
    }
    setState(() {
      _initialProducts = products;
      _productSearchResults = products; // Mostrar inicialmente los 10 productos del login
    });
    print('🔵 _loadInitialProducts: Estado actualizado con ${_productSearchResults.length} productos');
  }

  Future<void> _searchClients(String query) async {
    if (query.length < 2) {
      setState(() {
        _searchResults = [];
      });
      return;
    }

    setState(() {
      _isSearching = true;
    });

    print('🔍🔍🔍 BÚSQUEDA DE CLIENTE: "$query"');
    final jobProvider = context.read<JobProvider>();
    final results = await jobProvider.searchClients(query);
    print('📋📋📋 RESULTADOS: ${results.length} clientes encontrados');
    if (results.isNotEmpty) {
      print('👤 Primer resultado: ${results[0].name}');
    }

    setState(() {
      _searchResults = results;
      _isSearching = false;
    });
    
    // Mostrar mensaje si no hay resultados
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

  Future<void> _loadClientAddresses(int clientId) async {
    print('🏠🏠🏠 _loadClientAddresses: Cargando direcciones del cliente $clientId');
    setState(() {
      _isLoadingAddresses = true;
      _selectedAddress = null;
      _addresses = [];
    });

    final jobProvider = context.read<JobProvider>();
    print('🏠🏠🏠 Llamando a jobProvider.getClientAddresses...');
    final addresses = await jobProvider.getClientAddresses(clientId);
    print('🏠🏠🏠 Direcciones recibidas: ${addresses.length}');
    for (var addr in addresses) {
      print('   - ${addr.fullAddress}');
    }

    setState(() {
      _addresses = addresses;
      _isLoadingAddresses = false;
      
      // Si solo hay una dirección, seleccionarla automáticamente
      if (addresses.length == 1) {
        _selectedAddress = addresses[0];
        print('✅ Dirección auto-seleccionada: ${addresses[0].fullAddress}');
      }
    });
    
    if (addresses.isEmpty && mounted) {
      print('⚠️⚠️⚠️ No se encontraron direcciones para el cliente $clientId');
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Este cliente no tiene direcciones. Agrega una nueva.'),
          backgroundColor: Colors.orange,
          duration: Duration(seconds: 3),
        ),
      );
    }
  }

  Future<void> _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      locale: const Locale('es'),
    );

    if (picked != null) {
      setState(() {
        _selectedDate = picked;
      });
    }
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
    if (_selectedClient == null) return;
    
    // Mostrar diálogo simple para agregar dirección
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
        final newAddress = await jobProvider.createClientAddress(
          _selectedClient!.id,
          result['street']!,
          result['number']!,
          result['city']!,
          result['detail']!,
        );
        
        if (newAddress != null && mounted) {
          // Recargar las direcciones del cliente
          await _loadClientAddresses(_selectedClient!.id);
          
          // Seleccionar automáticamente la nueva dirección
          setState(() {
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

  // Buscar productos
  Future<void> _searchProducts(String query) async {
    print('🔍 _searchProducts llamado con query: "$query"');
    if (query.length < 2) {
      print('🔍 Query < 2, mostrando productos iniciales: ${_initialProducts.length}');
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

  Future<Map<String, dynamic>?> _getCurrentLocation() async {
    try {
      // Verificar permisos
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          print('⚠️ Permiso de ubicación denegado');
          return null;
        }
      }
      
      if (permission == LocationPermission.deniedForever) {
        print('⚠️ Permiso de ubicación denegado permanentemente');
        return null;
      }

      // Obtener posición actual
      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      
      print('📍 Ubicación obtenida: ${position.latitude}, ${position.longitude}');
      
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

  Future<void> _createJob() async {
    // Prevenir múltiples envíos
    if (isButtonLocked) return;

    if (!_formKey.currentState!.validate()) {
      return;
    }

    if (_selectedClient == null) {
      await CustomAlerts.showInfoAlert(
        context,
        title: 'Selecciona un cliente',
        message: 'Debes seleccionar un cliente antes de continuar',
      );
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

      print('🔄 _createJob: Iniciando creación...');
      
      final jobProvider = context.read<JobProvider>();
      
      // Mostrar loading INMEDIATAMENTE y ejecutar todo dentro
      final success = await CustomAlerts.executeWithLoading(
        context,
        operation: () async {
          // Obtener ubicación GPS dentro del loading
          Map<String, dynamic>? location;
          try {
            print('📍 _createJob: Obteniendo ubicación GPS...');
            location = await _getCurrentLocation().timeout(
              const Duration(seconds: 10),
              onTimeout: () {
                print('⏱️ _createJob: Timeout obteniendo ubicación');
                return null;
              },
            );
            print('📍 _createJob: Ubicación obtenida: $location');
          } catch (e) {
            print('❌ _createJob: Error obteniendo ubicación: $e');
            location = null;
          }

          print('🚀 _createJob: Llamando a jobProvider.createJob...');
          final result = await jobProvider.createJob(
            clientId: _selectedClient!.id,
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
        loadingMessage: 'Creando tarea...',
        successTitle: 'Tarea creada',
        successMessage: 'La tarea se creó exitosamente',
        errorTitle: 'Error al crear',
        getErrorMessage: () => jobProvider.errorMessage ?? 'Error inesperado al crear la tarea',
      );
      
      print('📊 _createJob: Resultado: $success');

      if (mounted && success) {
        // Cerrar la pantalla después de un momento
        if (mounted) {
          Navigator.pop(context, true); // Retornar true para indicar que se creó
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
        title: const Text('Nueva Tarea', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.check, color: Colors.white),
            onPressed: _createJob,
            tooltip: 'Guardar',
          ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Búsqueda de cliente
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
                    
                    if (_selectedClient == null) ...[
                      TextField(
                        controller: _searchController,
                        decoration: InputDecoration(
                          hintText: 'Buscar cliente por nombre, email o teléfono...',
                          prefixIcon: const Icon(Icons.search),
                          suffixIcon: _isSearching
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: Padding(
                                    padding: EdgeInsets.all(12),
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  ),
                                )
                              : null,
                          border: const OutlineInputBorder(),
                        ),
                        onChanged: _searchClients,
                      ),
                      
                      if (_searchResults.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Container(
                          constraints: const BoxConstraints(maxHeight: 200),
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.grey.shade300),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: ListView.builder(
                            shrinkWrap: true,
                            itemCount: _searchResults.length,
                            itemBuilder: (context, index) {
                              final client = _searchResults[index];
                              return ListTile(
                                title: Text(client.name),
                                subtitle: Text(client.email ?? client.phone ?? ''),
                                onTap: () {
                                  setState(() {
                                    _selectedClient = client;
                                    _searchResults = [];
                                    _searchController.clear();
                                  });
                                  // Cargar direcciones del cliente seleccionado
                                  _loadClientAddresses(client.id);
                                },
                              );
                            },
                          ),
                        ),
                      ],
                    ] else ...[
                      ListTile(
                        leading: CircleAvatar(
                          child: Text(_selectedClient!.name[0].toUpperCase()),
                        ),
                        title: Text(_selectedClient!.name),
                        subtitle: Text(_selectedClient!.email ?? _selectedClient!.phone ?? ''),
                        trailing: IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () {
                            setState(() {
                              _selectedClient = null;
                              _selectedAddress = null;
                              _addresses = [];
                            });
                          },
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
            
            const SizedBox(height: 16),
            
            // Direcciones del cliente
            if (_selectedClient != null) ...[
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
                        Column(
                          children: [
                            const Padding(
                              padding: EdgeInsets.all(16),
                              child: Text(
                                'Este cliente no tiene direcciones registradas.',
                                style: TextStyle(color: Colors.orange, fontSize: 14),
                                textAlign: TextAlign.center,
                              ),
                            ),
                            ElevatedButton.icon(
                              onPressed: () => _addNewAddress(),
                              icon: const Icon(Icons.add_location),
                              label: const Text('Agregar Dirección'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF00274E),
                                foregroundColor: Colors.white,
                              ),
                            ),
                          ],
                        )
                      else ...[
                        DropdownButtonFormField<Address>(
                          value: _selectedAddress,
                          decoration: const InputDecoration(
                            border: OutlineInputBorder(),
                            hintText: 'Selecciona una dirección',
                          ),
                          isExpanded: true,
                          items: _addresses.map((address) {
                            return DropdownMenuItem<Address>(
                              value: address,
                              child: Text(
                                address.fullAddress,
                                maxLines: 3,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(fontSize: 13),
                              ),
                            );
                          }).toList(),
                          selectedItemBuilder: (BuildContext context) {
                            return _addresses.map((address) {
                              return Text(
                                address.fullAddress,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(fontSize: 13),
                              );
                            }).toList();
                          },
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
                        OutlinedButton.icon(
                          onPressed: () => _addNewAddress(),
                          icon: const Icon(Icons.add_location),
                          label: const Text('Agregar Nueva Dirección'),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: const Color(0xFF00274E),
                            side: const BorderSide(color: Color(0xFF00274E)),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],
            
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
                        'No hay técnicos disponibles',
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
                onPressed: isButtonLocked ? null : _createJob,
                icon: Icon(
                  isButtonLocked ? Icons.lock : Icons.save, 
                  color: Colors.white,
                ),
                label: Text(
                  isButtonLocked ? 'Guardando...' : 'Crear Tarea',
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
