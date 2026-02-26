import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:image_picker/image_picker.dart';
import '../providers/job_provider.dart';
import '../models/job.dart';
import '../models/technician.dart';
import '../services/auth_service.dart';
import '../utils/custom_alerts.dart';

class JobDetailScreen extends StatefulWidget {
  final int jobId;

  const JobDetailScreen({super.key, required this.jobId});

  @override
  State<JobDetailScreen> createState() => _JobDetailScreenState();
}

class _JobDetailScreenState extends State<JobDetailScreen> {
  final _noteController = TextEditingController();
  final _observationController = TextEditingController();
  List<Technician> _technicians = [];
  List<int> _selectedTechnicianIdsForClose = [];

  @override
  void initState() {
    super.initState();
    _loadTechnicians();
    Future.microtask(() {
      context.read<JobProvider>().fetchJobDetail(widget.jobId);
    });
  }

  @override
  void dispose() {
    _noteController.dispose();
    _observationController.dispose();
    super.dispose();
  }

  Future<void> _loadTechnicians() async {
    final authService = AuthService();
    final technicians = await authService.getTechnicians();
    setState(() {
      _technicians = technicians;
    });
  }

  Future<void> _showOptionsDialog() async {
    return showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Añadir contenido'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.note_add, color: Color(0xFF00274E)),
              title: const Text('Agregar Nota'),
              onTap: () {
                Navigator.pop(context);
                _showAddNoteDialog();
              },
            ),
            ListTile(
              leading: const Icon(Icons.add_photo_alternate, color: Color(0xFF00274E)),
              title: const Text('Agregar Foto'),
              onTap: () {
                Navigator.pop(context);
                _showAddPhotoDialog();
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showAddPhotoDialog() async {
    final ImagePicker picker = ImagePicker();
    
    return showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Seleccionar Foto'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.camera_alt, color: Color(0xFF00274E)),
              title: const Text('Tomar Foto'),
              onTap: () async {
                Navigator.pop(context);
                final XFile? photo = await picker.pickImage(
                  source: ImageSource.camera,
                  maxWidth: 1920,
                  maxHeight: 1080,
                  imageQuality: 85,
                );
                if (photo != null) {
                  await _uploadPhoto(photo.path);
                }
              },
            ),
            ListTile(
              leading: const Icon(Icons.photo_library, color: Color(0xFF00274E)),
              title: const Text('Seleccionar de Galería'),
              onTap: () async {
                Navigator.pop(context);
                final XFile? photo = await picker.pickImage(
                  source: ImageSource.gallery,
                  maxWidth: 1920,
                  maxHeight: 1080,
                  imageQuality: 85,
                );
                if (photo != null) {
                  await _uploadPhoto(photo.path);
                }
              },
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
        ],
      ),
    );
  }

  void _showImageViewer(int initialIndex, List files) {
    showDialog(
      context: context,
      builder: (context) => Dialog(
        backgroundColor: Colors.black,
        insetPadding: EdgeInsets.zero,
        child: Stack(
          children: [
            PageView.builder(
              itemCount: files.length,
              controller: PageController(initialPage: initialIndex),
              itemBuilder: (context, index) {
                final file = files[index];
                final imageUrl = 'https://tecnicos.strupeni.com.ar/storage/${file.name}';
                return InteractiveViewer(
                  child: Center(
                    child: Image.network(
                      imageUrl,
                      fit: BoxFit.contain,
                      errorBuilder: (context, error, stackTrace) {
                        return Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.error, color: Colors.white, size: 64),
                              SizedBox(height: 16),
                              Text(
                                'Error al cargar imagen',
                                style: TextStyle(color: Colors.white),
                              ),
                            ],
                          ),
                        );
                      },
                      loadingBuilder: (context, child, loadingProgress) {
                        if (loadingProgress == null) return child;
                        return Center(
                          child: CircularProgressIndicator(
                            value: loadingProgress.expectedTotalBytes != null
                                ? loadingProgress.cumulativeBytesLoaded / loadingProgress.expectedTotalBytes!
                                : null,
                            color: Colors.white,
                          ),
                        );
                      },
                    ),
                  ),
                );
              },
            ),
            Positioned(
              top: 16,
              right: 16,
              child: IconButton(
                icon: Icon(Icons.close, color: Colors.white, size: 32),
                onPressed: () => Navigator.pop(context),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _uploadPhoto(String filePath) async {
    final jobProvider = context.read<JobProvider>();
    
    // Mostrar indicador de carga
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Row(
            children: [
              SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                ),
              ),
              SizedBox(width: 16),
              Text('Subiendo foto...'),
            ],
          ),
          duration: Duration(seconds: 10),
        ),
      );
    }

    final success = await jobProvider.uploadFiles(widget.jobId, [filePath]);
    
    if (mounted) {
      ScaffoldMessenger.of(context).hideCurrentSnackBar();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            success ? '✅ Foto subida exitosamente' : '❌ Error al subir foto',
          ),
          backgroundColor: success ? Colors.green : Colors.red,
        ),
      );
    }
  }

  Future<void> _showAddNoteDialog() async {
    _noteController.clear();
    
    return showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Añadir Nota'),
        content: TextField(
          controller: _noteController,
          decoration: const InputDecoration(
            hintText: 'Escribe tu nota aquí...',
            border: OutlineInputBorder(),
          ),
          maxLines: 4,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          ElevatedButton(
            onPressed: () async {
              if (_noteController.text.trim().isNotEmpty) {
                Navigator.pop(context);
                
                if (mounted) {
                  await CustomAlerts.executeWithLoading(
                    context,
                    operation: () async {
                      return await context.read<JobProvider>().addNote(
                        widget.jobId,
                        _noteController.text.trim(),
                      );
                    },
                    loadingMessage: 'Agregando nota...',
                    successTitle: 'Nota agregada',
                    successMessage: 'La nota se agregó correctamente',
                    errorTitle: 'Error al agregar nota',
                    getErrorMessage: () => context.read<JobProvider>().errorMessage ?? 'No se pudo agregar la nota',
                  );
                }
              }
            },
            child: const Text('Guardar'),
          ),
        ],
      ),
    );
  }

  Future<void> _showEditTechniciansDialog() async {
    final job = context.read<JobProvider>().selectedJob;
    if (job == null) return;
    
    List<int> selectedIds = [];
    
    // Pre-seleccionar técnicos ya asignados
    if (job.technicians != null) {
      selectedIds = job.technicians!
        .map((t) => t['id'] as int)
        .toList();
    }
    
    return showDialog(
      context: context,
      barrierDismissible: true,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Row(
            children: [
              Icon(Icons.engineering, color: Color(0xFF00274E)),
              SizedBox(width: 8),
              Text('Gestionar Técnicos'),
            ],
          ),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Seleccione los técnicos asignados a esta tarea:',
                  style: TextStyle(fontSize: 14, color: Colors.grey),
                ),
                const SizedBox(height: 16),
                if (_technicians.isEmpty)
                  const Padding(
                    padding: EdgeInsets.all(16.0),
                    child: Center(
                      child: Text(
                        'No hay técnicos disponibles',
                        style: TextStyle(color: Colors.grey),
                      ),
                    ),
                  )
                else
                  Container(
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    padding: const EdgeInsets.all(8),
                    child: Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: _technicians.map((tech) {
                        final isSelected = selectedIds.contains(tech.id);
                        return FilterChip(
                          label: Text(tech.name),
                          selected: isSelected,
                          onSelected: (selected) {
                            setDialogState(() {
                              if (selected) {
                                selectedIds.add(tech.id);
                              } else {
                                selectedIds.remove(tech.id);
                              }
                            });
                          },
                          selectedColor: const Color(0xFF00274E).withOpacity(0.2),
                          checkmarkColor: const Color(0xFF00274E),
                        );
                      }).toList(),
                    ),
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
              onPressed: () async {
                Navigator.pop(context);
                
                // Necesitamos crear un endpoint simple para solo actualizar técnicos
                // Por ahora usaremos updateJob con los datos actuales
                final jobProvider = context.read<JobProvider>();
                
                // Guardar técnicos mediante API
                try {
                  final success = await CustomAlerts.executeWithLoading(
                    context,
                    operation: () async {
                      // Llamar directamente al JobService para actualizar solo técnicos
                      final result = await jobProvider.jobService.updateJobTechnicians(
                        job.id!,
                        selectedIds.isNotEmpty ? selectedIds : null,
                      );
                      return result['success'] == true;
                    },
                    loadingMessage: 'Actualizando técnicos...',
                    successTitle: 'Técnicos actualizados',
                    successMessage: 'Los técnicos se actualizaron correctamente',
                    errorTitle: 'Error',
                    getErrorMessage: () => 'No se pudo actualizar los técnicos',
                  );
                  
                  if (success) {
                    // Recargar el detalle de la tarea
                    await jobProvider.fetchJobDetail(widget.jobId);
                  }
                } catch (e) {
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text('Error: $e'),
                        backgroundColor: Colors.red,
                      ),
                    );
                  }
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF00274E),
              ),
              child: const Text('Guardar'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showCloseJobDialog() async {
    _observationController.clear();
    _selectedTechnicianIdsForClose.clear();
    
    // Pre-seleccionar técnicos ya asignados a la tarea
    final job = context.read<JobProvider>().selectedJob;
    if (job?.technicians != null) {
      _selectedTechnicianIdsForClose = job!.technicians!
        .map((t) => t['id'] as int)
        .toList();
    }
    
    return showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Cerrar Cita'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextField(
                  controller: _observationController,
                  decoration: const InputDecoration(
                    labelText: 'Observaciones finales',
                    hintText: 'Ingrese las observaciones...',
                    border: OutlineInputBorder(),
                  ),
                  maxLines: 4,
                ),
                const SizedBox(height: 16),
                const Row(
                  children: [
                    Icon(Icons.engineering, color: Color(0xFF00274E), size: 20),
                    SizedBox(width: 8),
                    Text(
                      'Técnicos *',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                if (_technicians.isEmpty)
                  const Padding(
                    padding: EdgeInsets.all(8.0),
                    child: Text(
                      'No hay técnicos disponibles',
                      style: TextStyle(color: Colors.grey),
                    ),
                  )
                else
                  Container(
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    padding: const EdgeInsets.all(8),
                    child: Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: _technicians.map((tech) {
                        final isSelected = _selectedTechnicianIdsForClose.contains(tech.id);
                        return FilterChip(
                          label: Text(tech.name),
                          selected: isSelected,
                          onSelected: (selected) {
                            setDialogState(() {
                              if (selected) {
                                _selectedTechnicianIdsForClose.add(tech.id);
                              } else {
                                _selectedTechnicianIdsForClose.remove(tech.id);
                              }
                            });
                          },
                          selectedColor: const Color(0xFF00274E).withOpacity(0.2),
                          checkmarkColor: const Color(0xFF00274E),
                        );
                      }).toList(),
                    ),
                  ),
                if (_selectedTechnicianIdsForClose.isEmpty)
                  const Padding(
                    padding: EdgeInsets.only(top: 8.0),
                    child: Text(
                      'Debe seleccionar al menos un técnico',
                      style: TextStyle(color: Colors.red, fontSize: 12),
                    ),
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
              onPressed: () async {
                // Validar que haya observaciones
                if (_observationController.text.trim().isEmpty) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Debe ingresar observaciones'),
                      backgroundColor: Colors.orange,
                    ),
                  );
                  return;
                }
                
                // Validar que haya al menos un técnico seleccionado
                if (_selectedTechnicianIdsForClose.isEmpty) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Debe seleccionar al menos un técnico'),
                      backgroundColor: Colors.orange,
                    ),
                  );
                  return;
                }
                
                Navigator.pop(context);
                final success = await context.read<JobProvider>().closeJob(
                  widget.jobId,
                  _observationController.text.trim(),
                  _selectedTechnicianIdsForClose,
                );
                
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                        success ? 'Cita cerrada exitosamente' : 'Error al cerrar cita',
                      ),
                      backgroundColor: success ? Colors.green : Colors.red,
                    ),
                  );
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.green,
              ),
              child: const Text('Cerrar Cita'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _handleMarkArrival() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Marcar Llegada'),
        content: const Text('¿Confirmas que has llegado al lugar de la cita?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Confirmar'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      final success = await context.read<JobProvider>().markArrival(widget.jobId);
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              success ? 'Llegada registrada' : 'Error al registrar llegada',
            ),
            backgroundColor: success ? Colors.green : Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _handleRevertArrival() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Volver a Pendiente'),
        content: const Text('¿Deseas revertir el arribo y volver la tarea a estado pendiente?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.orange,
            ),
            child: const Text('Revertir'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      final success = await context.read<JobProvider>().revertArrival(widget.jobId);
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              success ? 'Tarea devuelta a pendiente' : 'Error al revertir arribo',
            ),
            backgroundColor: success ? Colors.orange : Colors.red,
          ),
        );
      }
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
        title: const Text('Detalle de Cita', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Consumer<JobProvider>(
        builder: (context, jobProvider, child) {
          if (jobProvider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          // Mostrar error si existe
          if (jobProvider.errorMessage != null) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.error_outline, color: Colors.red, size: 64),
                  const SizedBox(height: 16),
                  Text(
                    'Error al cargar la cita',
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Text(
                      jobProvider.errorMessage!,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: Colors.grey),
                    ),
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: () {
                      context.read<JobProvider>().fetchJobDetail(widget.jobId);
                    },
                    icon: const Icon(Icons.refresh),
                    label: const Text('Reintentar'),
                  ),
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Volver'),
                  ),
                ],
              ),
            );
          }

          final job = jobProvider.selectedJob;
          
          if (job == null) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.info_outline, color: Colors.orange, size: 64),
                  const SizedBox(height: 16),
                  const Text('Cita no encontrada'),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Volver'),
                  ),
                ],
              ),
            );
          }

          return SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Estado de la cita
                _buildStatusBanner(job),
                
                // Información del cliente
                _buildSection(
                  'Cliente',
                  [
                    _buildInfoRow(Icons.person, job.clientName ?? 'Sin nombre'),
                    if (job.clientEmail != null)
                      _buildInfoRow(Icons.email, job.clientEmail!),
                    if (job.clientPhone != null)
                      _buildInfoRow(Icons.phone, job.clientPhone!),
                  ],
                ),
                
                // Dirección
                if (job.fullAddress != null)
                  _buildSection(
                    'Dirección',
                    [
                      _buildInfoRow(Icons.location_on, job.fullAddress!),
                    ],
                  ),
                
                // Información de la cita
                _buildSection(
                  'Detalles de la Cita',
                  [
                    if (job.visitDatetime != null)
                      _buildInfoRow(
                        Icons.calendar_today,
                        _formatDateTime(job.visitDatetime!),
                      ),
                    if (job.jobDescription != null && job.jobDescription!.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Descripción:',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(job.jobDescription!),
                          ],
                        ),
                      ),
                  ],
                ),
                
                // Historial
                if (job.arrivalDatetime != null || job.closedDatetime != null)
                  _buildSection(
                    'Historial',
                    [
                      if (job.arrivalDatetime != null)
                        _buildInfoRow(
                          Icons.check_circle,
                          'Llegada: ${_formatDateTime(job.arrivalDatetime!)}',
                        ),
                      if (job.closedDatetime != null)
                        _buildInfoRow(
                          Icons.done_all,
                          'Cerrado: ${_formatDateTime(job.closedDatetime!)}',
                        ),
                      if (job.closedJobObservation != null && 
                          job.closedJobObservation!.isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Observaciones finales:',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 14,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(job.closedJobObservation!),
                            ],
                          ),
                        ),
                    ],
                  ),
                
                // Técnicos asignados
                _buildTechniciansSection(job),
                
                // Notas
                _buildNotesSection(jobProvider.notes),
                
                // Imágenes/Archivos
                _buildImagesSection(jobProvider.files),
                
                const SizedBox(height: 80), // Espacio para los botones flotantes
              ],
            ),
          );
        },
      ),
      bottomNavigationBar: Consumer<JobProvider>(
        builder: (context, jobProvider, child) {
          final job = jobProvider.selectedJob;
          if (job == null) return const SizedBox.shrink();
          
          return _buildActionButtons(job);
        },
      ),
      floatingActionButton: Consumer<JobProvider>(
        builder: (context, jobProvider, child) {
          final job = jobProvider.selectedJob;
          final permissions = jobProvider.permissions;
          
          // No mostrar botón si la cita está cerrada o no hay permisos
          if (job == null || job.isClosed || permissions == null || !permissions.canAddNote) {
            return const SizedBox.shrink();
          }
          
          return FloatingActionButton(
            onPressed: _showOptionsDialog,
            child: const Icon(Icons.add),
          );
        },
      ),
    );
  }

  Widget _buildStatusBanner(Job job) {
    Color color;
    IconData icon;
    
    if (job.isClosed) {
      color = Colors.grey;
      icon = Icons.done_all;
    } else if (job.isInPlace) {
      color = Colors.green;
      icon = Icons.location_on;
    } else {
      color = Colors.orange;
      icon = Icons.schedule;
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      color: color,
      child: Row(
        children: [
          Icon(icon, color: Colors.white, size: 28),
          const SizedBox(width: 12),
          Text(
            job.status ?? 'Desconocido',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSection(String title, List<Widget> children) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Text(
            title,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          color: Colors.grey[100],
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: children,
          ),
        ),
      ],
    );
  }

  Widget _buildInfoRow(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Colors.grey[600]),
          const SizedBox(width: 8),
          Expanded(child: Text(text)),
        ],
      ),
    );
  }

  Widget _buildNotesSection(List notes) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Notas',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              Text(
                '${notes.length}',
                style: TextStyle(
                  fontSize: 16,
                  color: Colors.grey[600],
                ),
              ),
            ],
          ),
        ),
        if (notes.isEmpty)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(32),
            color: Colors.grey[100],
            child: Center(
              child: Text(
                'No hay notas',
                style: TextStyle(color: Colors.grey[600]),
              ),
            ),
          )
        else
          ...notes.map((note) => Container(
                width: double.infinity,
                margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.blue[50],
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.blue[200]!),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      note.note,
                      style: const TextStyle(fontSize: 14),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${note.userName ?? 'Usuario'} - ${note.createdAt != null ? _formatDateTime(note.createdAt!) : ''}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              )),
      ],
    );
  }

  Widget _buildImagesSection(List files) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Imágenes',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              Text(
                '${files.length}',
                style: TextStyle(
                  fontSize: 16,
                  color: Colors.grey[600],
                ),
              ),
            ],
          ),
        ),
        if (files.isEmpty)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(32),
            color: Colors.grey[100],
            child: Center(
              child: Column(
                children: [
                  Icon(Icons.add_photo_alternate, size: 48, color: Colors.grey[400]),
                  const SizedBox(height: 8),
                  Text(
                    'No hay imágenes',
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Presiona el botón + para agregar fotos',
                    style: TextStyle(color: Colors.grey[500], fontSize: 12),
                  ),
                ],
              ),
            ),
          )
        else
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: SizedBox(
              height: 150,
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                itemCount: files.length,
                itemBuilder: (context, index) {
                  final file = files[index];
                  final imageUrl = 'https://tecnicos.strupeni.com.ar/storage/${file.name}';
                  
                  return GestureDetector(
                    onTap: () => _showImageViewer(index, files),
                    child: Container(
                      margin: const EdgeInsets.only(right: 12),
                      width: 120,
                      decoration: BoxDecoration(
                        color: Colors.grey[200],
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey[300]!),
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: Image.network(
                          imageUrl,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) {
                            return Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.image, size: 40, color: Colors.grey[400]),
                                const SizedBox(height: 4),
                                Padding(
                                  padding: const EdgeInsets.all(4),
                                  child: Text(
                                    file.originalName ?? 'Imagen',
                                    style: TextStyle(fontSize: 10, color: Colors.grey[600]),
                                    textAlign: TextAlign.center,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            );
                          },
                          loadingBuilder: (context, child, loadingProgress) {
                            if (loadingProgress == null) return child;
                            return Center(
                              child: CircularProgressIndicator(
                                value: loadingProgress.expectedTotalBytes != null
                                    ? loadingProgress.cumulativeBytesLoaded / loadingProgress.expectedTotalBytes!
                                    : null,
                              ),
                            );
                          },
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildTechniciansSection(Job job) {
    final hasTechnicians = job.technicians != null && job.technicians!.isNotEmpty;
    
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Row(
                children: [
                  Icon(Icons.engineering, color: Color(0xFF00274E)),
                  SizedBox(width: 8),
                  Text(
                    'Técnicos Asignados',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
              // Botón para editar técnicos (solo si no está cerrada)
              if (!job.isClosed)
                IconButton(
                  onPressed: _showEditTechniciansDialog,
                  icon: const Icon(Icons.edit, size: 20),
                  color: const Color(0xFF00274E),
                  tooltip: 'Gestionar Técnicos',
                ),
            ],
          ),
        ),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          color: Colors.grey[100],
          child: hasTechnicians
              ? Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: job.technicians!.map((tech) {
                    return Chip(
                      avatar: const CircleAvatar(
                        backgroundColor: Color(0xFF00274E),
                        child: Icon(
                          Icons.engineering,
                          size: 16,
                          color: Colors.white,
                        ),
                      ),
                      label: Text(tech['name'] ?? ''),
                      backgroundColor: const Color(0xFF00274E).withOpacity(0.1),
                    );
                  }).toList(),
                )
              : Center(
                  child: Column(
                    children: [
                      Icon(Icons.engineering_outlined, size: 48, color: Colors.grey[400]),
                      const SizedBox(height: 8),
                      Text(
                        'No hay técnicos asignados',
                        style: TextStyle(color: Colors.grey[600]),
                      ),
                      if (!job.isClosed) ...[
                        const SizedBox(height: 8),
                        TextButton.icon(
                          onPressed: _showEditTechniciansDialog,
                          icon: const Icon(Icons.add),
                          label: const Text('Asignar Técnicos'),
                        ),
                      ],
                    ],
                  ),
                ),
        ),
      ],
    );
  }

  Widget _buildActionButtons(Job job) {
    if (job.isClosed) {
      return const SizedBox.shrink();
    }

    return Consumer<JobProvider>(
      builder: (context, jobProvider, child) {
        final permissions = jobProvider.permissions;
        
        // Si no hay permisos, no mostrar botones
        if (permissions == null) {
          return const SizedBox.shrink();
        }

        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.1),
                blurRadius: 4,
                offset: const Offset(0, -2),
              ),
            ],
          ),
          child: Row(
            children: [
              // Botón "Marcar Llegada" - solo si tiene permiso de update
              if (!job.isInPlace && !job.isClosed && permissions.canMarkArrival)
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _handleMarkArrival,
                    icon: const Icon(Icons.location_on),
                    label: const Text('Marcar Llegada'),
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
              
              // Botón "Volver a Pendiente" - solo si ha arribado pero no cerrado
              if (job.isInPlace && !job.isClosed && permissions.canMarkArrival) ...[
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _handleRevertArrival,
                    icon: const Icon(Icons.undo),
                    label: const Text('Volver a Pendiente'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.orange,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
              ],
              
              // Botón "Cerrar Cita" - solo si tiene permiso de update
              if (job.isInPlace && !job.isClosed && permissions.canCloseJob) ...[
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _showCloseJobDialog,
                    icon: const Icon(Icons.done_all),
                    label: const Text('Cerrar Cita'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.green,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  String _formatDateTime(String datetime) {
    try {
      final date = DateTime.parse(datetime);
      return DateFormat('dd/MM/yyyy HH:mm').format(date);
    } catch (e) {
      return datetime;
    }
  }
}
