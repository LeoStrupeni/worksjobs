import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/job_provider.dart';
import '../models/job.dart';

class JobDetailScreen extends StatefulWidget {
  final int jobId;

  const JobDetailScreen({super.key, required this.jobId});

  @override
  State<JobDetailScreen> createState() => _JobDetailScreenState();
}

class _JobDetailScreenState extends State<JobDetailScreen> {
  final _noteController = TextEditingController();
  final _observationController = TextEditingController();

  @override
  void initState() {
    super.initState();
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
                final success = await context.read<JobProvider>().addNote(
                  widget.jobId,
                  _noteController.text.trim(),
                );
                
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                        success ? 'Nota añadida' : 'Error al añadir nota',
                      ),
                      backgroundColor: success ? Colors.green : Colors.red,
                    ),
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

  Future<void> _showCloseJobDialog() async {
    _observationController.clear();
    
    return showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cerrar Cita'),
        content: TextField(
          controller: _observationController,
          decoration: const InputDecoration(
            hintText: 'Observaciones finales...',
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
              if (_observationController.text.trim().isNotEmpty) {
                Navigator.pop(context);
                final success = await context.read<JobProvider>().closeJob(
                  widget.jobId,
                  _observationController.text.trim(),
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
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.green,
            ),
            child: const Text('Cerrar Cita'),
          ),
        ],
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle de Cita'),
      ),
      body: Consumer<JobProvider>(
        builder: (context, jobProvider, child) {
          if (jobProvider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          final job = jobProvider.selectedJob;
          
          if (job == null) {
            return const Center(
              child: Text('Cita no encontrada'),
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
                
                // Notas
                _buildNotesSection(jobProvider.notes),
                
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
            onPressed: _showAddNoteDialog,
            child: const Icon(Icons.note_add),
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
