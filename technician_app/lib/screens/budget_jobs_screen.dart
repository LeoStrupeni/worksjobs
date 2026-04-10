import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/budget_provider.dart';
import '../providers/auth_provider.dart';
import '../models/budget.dart';
import 'job_detail_screen.dart';

/// Pantalla para ver tareas asociadas y crear nuevas desde presupuesto
class BudgetJobsScreen extends StatefulWidget {
  final Budget budget;

  const BudgetJobsScreen({
    super.key,
    required this.budget,
  });

  @override
  State<BudgetJobsScreen> createState() => _BudgetJobsScreenState();
}

class _BudgetJobsScreenState extends State<BudgetJobsScreen> {
  bool _isLoadingJobs = false;
  List<Map<String, dynamic>> _jobs = [];

  @override
  void initState() {
    super.initState();
    _loadAssociatedJobs();
  }

  /// Cargar tareas asociadas
  Future<void> _loadAssociatedJobs() async {
    setState(() {
      _isLoadingJobs = true;
    });

    final budgetProvider = Provider.of<BudgetProvider>(context, listen: false);
    final result = await budgetProvider.getAssociatedJobs(widget.budget.idFactura ?? '');

    if (result['success'] == true) {
      setState(() {
        _jobs = List<Map<String, dynamic>>.from(result['jobs'] ?? []);
        _isLoadingJobs = false;
      });
    } else {
      setState(() {
        _isLoadingJobs = false;
      });
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Error al cargar tareas'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  /// Mostrar diálogo para crear nueva tarea
  Future<void> _showCreateJobDialog() async {
    final jobDescriptionController = TextEditingController();
    DateTime? selectedDate;
    TimeOfDay? selectedTime;
    
    // Obtener usuario actual
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final currentUser = authProvider.user;
    
    if (currentUser == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Error: No se pudo obtener usuario actual'),
            backgroundColor: Colors.red,
          ),
        );
      }
      return;
    }

    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext dialogContext) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text('Crear Tarea desde Presupuesto'),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Descripción
                    TextField(
                      controller: jobDescriptionController,
                      maxLines: 3,
                      maxLength: 500,
                      decoration: const InputDecoration(
                        labelText: 'Descripción de la tarea *',
                        hintText: 'Ej: Instalación de equipos según presupuesto',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Fecha de visita
                    Card(
                      child: ListTile(
                        leading: const Icon(Icons.calendar_today),
                        title: Text(
                          selectedDate == null
                              ? 'Fecha de visita *'
                              : DateFormat('dd/MM/yyyy').format(selectedDate!),
                        ),
                        trailing: const Icon(Icons.arrow_drop_down),
                        onTap: () async {
                          final DateTime? picked = await showDatePicker(
                            context: context,
                            initialDate: DateTime.now(),
                            firstDate: DateTime.now(),
                            lastDate: DateTime.now().add(const Duration(days: 365)),
                            locale: const Locale('es', 'ES'),
                          );
                          if (picked != null) {
                            setDialogState(() {
                              selectedDate = picked;
                            });
                          }
                        },
                      ),
                    ),
                    const SizedBox(height: 8),

                    // Hora de visita
                    Card(
                      child: ListTile(
                        leading: const Icon(Icons.access_time),
                        title: Text(
                          selectedTime == null
                              ? 'Hora de visita *'
                              : selectedTime!.format(context),
                        ),
                        trailing: const Icon(Icons.arrow_drop_down),
                        onTap: () async {
                          final TimeOfDay? picked = await showTimePicker(
                            context: context,
                            initialTime: TimeOfDay.now(),
                            builder: (BuildContext context, Widget? child) {
                              return MediaQuery(
                                data: MediaQuery.of(context).copyWith(
                                  alwaysUse24HourFormat: false,
                                ),
                                child: child!,
                              );
                            },
                          );
                          if (picked != null) {
                            setDialogState(() {
                              selectedTime = picked;
                            });
                          }
                        },
                      ),
                    ),
                    const SizedBox(height: 16),

                    const Text(
                      '* Campos obligatorios',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey,
                        fontStyle: FontStyle.italic,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'La tarea se asignará a: ${currentUser.name}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.blue.shade700,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(dialogContext).pop(null),
                  child: const Text('Cancelar'),
                ),
                ElevatedButton(
                  onPressed: () {
                    // Validar
                    if (jobDescriptionController.text.trim().isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('La descripción es obligatoria'),
                          backgroundColor: Colors.orange,
                        ),
                      );
                      return;
                    }

                    if (selectedDate == null || selectedTime == null) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('Debe seleccionar fecha y hora de visita'),
                          backgroundColor: Colors.orange,
                        ),
                      );
                      return;
                    }

                    // Combinar fecha y hora
                    final visitDateTime = DateTime(
                      selectedDate!.year,
                      selectedDate!.month,
                      selectedDate!.day,
                      selectedTime!.hour,
                      selectedTime!.minute,
                    );

                    Navigator.of(dialogContext).pop({
                      'job_description': jobDescriptionController.text.trim(),
                      'visit_datetime': visitDateTime.toIso8601String(),
                      'technician_ids': [currentUser.id], // Usuario actual
                    });
                  },
                  child: const Text('Crear'),
                ),
              ],
            );
          },
        );
      },
    );

    if (result != null) {
      await _createJob(result);
    }
  }

  /// Crear tarea desde presupuesto
  Future<void> _createJob(Map<String, dynamic> jobData) async {
    // Mostrar loading
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return const Center(
          child: Card(
            child: Padding(
              padding: EdgeInsets.all(20),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  CircularProgressIndicator(),
                  SizedBox(height: 16),
                  Text('Creando tarea...'),
                ],
              ),
            ),
          ),
        );
      },
    );

    final budgetProvider = Provider.of<BudgetProvider>(context, listen: false);
    final result = await budgetProvider.createJobFromBudget(
      budgetId: widget.budget.idFactura ?? '',
      jobDescription: jobData['job_description'],
      visitDatetime: jobData['visit_datetime'],
      technicianIds: jobData['technician_ids'],
    );

    // Cerrar loading
    if (mounted) Navigator.of(context).pop();

    if (result['success'] == true) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Tarea creada exitosamente'),
            backgroundColor: Colors.green,
          ),
        );
      }
      
      // Recargar lista de tareas
      await _loadAssociatedJobs();
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Error al crear tarea'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Tareas - ${widget.budget.nroFactura}'),
        elevation: 0,
        backgroundColor: const Color(0xFF00274E),  // ✅ Fondo azul oscuro
        foregroundColor: Colors.white,
      ),
      body: _isLoadingJobs
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                // Header con info del presupuesto
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  color: Colors.blue.shade50,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Presupuesto: ${widget.budget.nroFactura}',
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Cliente: ${widget.budget.clientName ?? 'N/A'}',
                        style: const TextStyle(fontSize: 14),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Total: \$${widget.budget.total?.toStringAsFixed(2) ?? '0.00'}',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.green,
                        ),
                      ),
                    ],
                  ),
                ),

                // Lista de tareas
                Expanded(
                  child: _jobs.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.assignment_outlined,
                                size: 64,
                                color: Colors.grey.shade400,
                              ),
                              const SizedBox(height: 16),
                              Text(
                                'No hay tareas asociadas',
                                style: TextStyle(
                                  fontSize: 16,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                              const SizedBox(height: 8),
                              const Text(
                                'Crea una tarea desde este presupuesto',
                                style: TextStyle(
                                  fontSize: 14,
                                  color: Colors.grey,
                                ),
                              ),
                            ],
                          ),
                        )
                      : RefreshIndicator(
                          onRefresh: _loadAssociatedJobs,
                          child: ListView.builder(
                            padding: const EdgeInsets.all(8),
                            itemCount: _jobs.length,
                            itemBuilder: (context, index) {
                              final job = _jobs[index];
                              return _buildJobCard(job);
                            },
                          ),
                        ),
                ),
              ],
            ),
      floatingActionButton: Consumer<BudgetProvider>(
        builder: (context, budgetProvider, child) {
          // Solo mostrar botón si tiene permiso para crear tareas
          if (!budgetProvider.canCreateJobs) {
            return const SizedBox.shrink();
          }
          
          return FloatingActionButton.extended(
            onPressed: _showCreateJobDialog,
            icon: const Icon(Icons.add),
            label: const Text('Nueva Tarea'),
            backgroundColor: Colors.green,
          );
        },
      ),
    );
  }

  /// Construir card de tarea
  Widget _buildJobCard(Map<String, dynamic> job) {
    final jobId = job['id'];
    final description = job['job_description'] ?? 'Sin descripción';
    final status = job['status'] ?? 'Pendiente';
    final clientName = job['client_name'] ?? 'Sin cliente';
    final visitDatetime = job['visit_datetime'];
    
    // Parse fecha
    DateTime? visitDate;
    if (visitDatetime != null) {
      try {
        visitDate = DateTime.parse(visitDatetime);
      } catch (e) {
        visitDate = null;
      }
    }

    // Color según estado (backend envía estado en español)
    Color statusColor;
    String statusText = status; // Usar el estado que viene del backend directamente
    IconData statusIcon;

    switch (status) {
      case 'En Lugar':
        statusColor = Colors.blue;
        statusIcon = Icons.location_on;
        break;
      case 'Cerrada':
        statusColor = Colors.green;
        statusIcon = Icons.check_circle;
        break;
      case 'Archivada':
        statusColor = Colors.grey;
        statusIcon = Icons.archive;
        break;
      case 'Pendiente':
      default:
        statusColor = Colors.orange;
        statusIcon = Icons.pending;
    }

    return Card(
      margin: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: statusColor.withOpacity(0.1),
          child: Icon(statusIcon, color: statusColor),
        ),
        title: Text(
          description,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 4),
            Text('Cliente: $clientName'),
            if (visitDate != null) ...[
              const SizedBox(height: 2),
              Row(
                children: [
                  const Icon(Icons.calendar_today, size: 14, color: Colors.grey),
                  const SizedBox(width: 4),
                  Text(
                    DateFormat('dd/MM/yyyy HH:mm').format(visitDate),
                    style: const TextStyle(fontSize: 12),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 4),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: statusColor.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                statusText,
                style: TextStyle(
                  color: statusColor,
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        ),
        isThreeLine: true,
        onTap: () async {
          // Navegar al detalle de la tarea
          await Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => JobDetailScreen(jobId: jobId),
            ),
          );
          
          // Recargar lista de tareas al volver
          if (mounted) {
            _loadAssociatedJobs();
          }
        },
      ),
    );
  }

  @override
  void dispose() {
    super.dispose();
  }
}
