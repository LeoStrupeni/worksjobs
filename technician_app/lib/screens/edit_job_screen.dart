import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:geolocator/geolocator.dart';
import 'dart:convert';
import '../providers/job_provider.dart';
import '../models/job.dart';
import '../models/address.dart';
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
  
  late DateTime _selectedDate;
  late TimeOfDay _selectedTime;
  List<Address> _addresses = [];
  Address? _selectedAddress;
  bool _isLoadingAddresses = false;

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
    
    // Cargar direcciones del cliente
    _loadClientAddresses();
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    super.dispose();
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
