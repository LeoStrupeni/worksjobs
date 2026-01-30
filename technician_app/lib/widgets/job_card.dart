import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import '../models/job.dart';
import '../models/job_permissions.dart';
import '../providers/job_provider.dart';

class JobCard extends StatelessWidget {
  final Job job;
  final JobPermissions permissions;
  final VoidCallback onTap;
  final VoidCallback? onRefresh;

  const JobCard({
    super.key,
    required this.job,
    required this.permissions,
    required this.onTap,
    this.onRefresh,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      elevation: 2,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header con estado
              Row(
                children: [
                  _buildStatusIndicator(),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      job.clientName ?? 'Cliente sin nombre',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  Icon(
                    Icons.chevron_right,
                    color: Colors.grey[400],
                  ),
                ],
              ),
              
              const SizedBox(height: 8),
              
              // Hora de visita
              if (job.visitDatetime != null)
                Row(
                  children: [
                    Icon(Icons.access_time, size: 16, color: Colors.grey[600]),
                    const SizedBox(width: 4),
                    Text(
                      _formatTime(job.visitDatetime!),
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              
              const SizedBox(height: 4),
              
              // Descripción (preview)
              if (job.jobDescription != null && job.jobDescription!.isNotEmpty)
                Text(
                  job.jobDescription!.length > 60
                      ? '${job.jobDescription!.substring(0, 60)}...'
                      : job.jobDescription!,
                  style: TextStyle(
                    fontSize: 13,
                    color: Colors.grey[700],
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              
              const SizedBox(height: 8),
              
              // Tags de estado
              Wrap(
                spacing: 4,
                children: [
                  _buildStatusChip(),
                  if (job.isInPlace && !job.isClosed)
                    Chip(
                      label: const Text('En lugar'),
                      backgroundColor: Colors.green[100],
                      labelStyle: TextStyle(
                        fontSize: 11,
                        color: Colors.green[800],
                      ),
                      padding: EdgeInsets.zero,
                      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    ),
                ],
              ),
              
              const SizedBox(height: 8),
              
              // Botones de acción
              _buildActionButtons(context),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildActionButtons(BuildContext context) {
    final roles = permissions.roles;
    final isAdmin = roles.contains('sistema') || roles.contains('admin');
    
    print('🔑 JobCard buttons - Job ${job.id}: create=${permissions.create}, read=${permissions.read}, update=${permissions.update}, delete=${permissions.delete}, roles=$roles');
    
    return Wrap(
      spacing: 4,
      runSpacing: 4,
      children: [
        // Volver a pendiente (solo sistema/admin, si arrival != null y no está cerrado)
        if (isAdmin && permissions.update && job.isInPlace && !job.isClosed)
          _buildIconButton(
            icon: Icons.undo,
            color: Colors.red,
            onPressed: () => _handleBackToPending(context),
            tooltip: 'Volver a pendiente',
          ),
        
        // Ver detalle (read permission)
        if (permissions.read)
          _buildIconButton(
            icon: Icons.visibility,
            color: Colors.blue,
            onPressed: onTap,
            tooltip: 'Ver tarea',
          ),
        
        // Editar (update permission Y arrival == null)
        if (permissions.update && !job.isInPlace && !job.isClosed)
          _buildIconButton(
            icon: Icons.edit,
            color: Colors.blue,
            onPressed: () => _handleEditJob(context),
            tooltip: 'Editar tarea',
          ),
        
        // Eliminar (delete permission Y arrival == null)
        if (permissions.delete && !job.isInPlace && !job.isClosed)
          _buildIconButton(
            icon: Icons.delete,
            color: Colors.red,
            onPressed: () => _handleDeleteJob(context),
            tooltip: 'Eliminar tarea',
          ),
        
        // Marcar Arribo (solo si arrival == null)
        if (!job.isInPlace && !job.isClosed)
          _buildIconButton(
            icon: Icons.location_on,
            color: Colors.orange,
            onPressed: () => _handleMarkArrival(context),
            tooltip: 'Marcar arribo',
          ),
        
        // Agregar nota (SIEMPRE visible)
        _buildIconButton(
          icon: Icons.note_add,
          color: Colors.green,
          onPressed: () => _handleAddNote(context),
          tooltip: 'Agregar nota',
        ),
        
        // Ver notas (si hay notas) - por ahora siempre mostrar
        _buildIconButton(
          icon: Icons.notes,
          color: Colors.blue,
          onPressed: () => _handleViewNotes(context),
          tooltip: 'Ver notas',
        ),
        
        // Agregar imágenes (update permission - SIN importar arrival)
        if (permissions.update)
          _buildIconButton(
            icon: Icons.camera_alt,
            color: Colors.green,
            onPressed: () => _handleAddImages(context),
            tooltip: 'Agregar imágenes',
          ),
        
        // Cerrar tarea (solo si NO está cerrado)
        if (!job.isClosed)
          _buildIconButton(
            icon: Icons.assignment_turned_in,
            color: Colors.black87,
            onPressed: () => _handleCloseJob(context),
            tooltip: 'Cerrar tarea',
          ),
      ],
    );
  }

  Widget _buildIconButton({
    required IconData icon,
    required Color color,
    required VoidCallback onPressed,
    required String tooltip,
  }) {
    return Tooltip(
      message: tooltip,
      child: Material(
        color: color,
        borderRadius: BorderRadius.circular(8),
        child: InkWell(
          onTap: onPressed,
          borderRadius: BorderRadius.circular(8),
          child: Container(
            padding: const EdgeInsets.all(8),
            child: Icon(icon, color: Colors.white, size: 20),
          ),
        ),
      ),
    );
  }

  Widget _buildStatusIndicator() {
    return Container(
      width: 12,
      height: 12,
      decoration: BoxDecoration(
        color: _getStatusColor(),
        shape: BoxShape.circle,
      ),
    );
  }

  Widget _buildStatusChip() {
    Color color = _getStatusColor();
    
    return Chip(
      label: Text(job.status ?? 'Desconocido'),
      backgroundColor: color.withOpacity(0.2),
      labelStyle: TextStyle(
        fontSize: 11,
        color: color,
        fontWeight: FontWeight.bold,
      ),
      padding: EdgeInsets.zero,
      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
    );
  }

  Color _getStatusColor() {
    switch (job.colorStatus?.toLowerCase()) {
      case 'black':
        return Colors.black87;
      case 'green':
        return Colors.green;
      case 'red':
        return Colors.red;
      case 'orange':
        return Colors.orange;
      case 'blue':
        return Colors.blue;
      default:
        return Colors.grey;
    }
  }

  String _formatTime(String dateTime) {
    try {
      final date = DateTime.parse(dateTime);
      return DateFormat('HH:mm', 'es').format(date);
    } catch (e) {
      return '';
    }
  }

  // HANDLERS
  
  Future<void> _handleMarkArrival(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Marcar Llegada'),
        content: const Text('¿Deseas marcar tu llegada al lugar de trabajo?'),
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

    if (confirmed == true && context.mounted) {
      final success = await context.read<JobProvider>().markArrival(job.id);
      
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              success ? '✅ Llegada registrada' : '❌ Error al registrar llegada',
            ),
            backgroundColor: success ? Colors.green : Colors.red,
          ),
        );
        
        if (success && onRefresh != null) {
          onRefresh!();
        }
      }
    }
  }

  Future<void> _handleCloseJob(BuildContext context) async {
    final observationController = TextEditingController();
    
    final result = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cerrar Cita'),
        content: TextField(
          controller: observationController,
          decoration: const InputDecoration(
            hintText: 'Observaciones finales (requerido)...',
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
            onPressed: () {
              if (observationController.text.trim().isNotEmpty) {
                Navigator.pop(context, observationController.text.trim());
              } else {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Las observaciones son requeridas'),
                    backgroundColor: Colors.orange,
                  ),
                );
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
            child: const Text('Cerrar Cita'),
          ),
        ],
      ),
    );

    if (result != null && context.mounted) {
      final success = await context.read<JobProvider>().closeJob(job.id, result);
      
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              success ? '✅ Cita cerrada exitosamente' : '❌ Error al cerrar cita',
            ),
            backgroundColor: success ? Colors.green : Colors.red,
          ),
        );
        
        if (success && onRefresh != null) {
          onRefresh!();
        }
      }
    }
  }

  Future<void> _handleAddNote(BuildContext context) async {
    final noteController = TextEditingController();
    
    final result = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Agregar Nota'),
        content: TextField(
          controller: noteController,
          decoration: const InputDecoration(
            hintText: 'Escribe tu nota...',
            border: OutlineInputBorder(),
          ),
          maxLines: 4,
          autofocus: true,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          ElevatedButton(
            onPressed: () {
              if (noteController.text.trim().isNotEmpty) {
                Navigator.pop(context, noteController.text.trim());
              }
            },
            child: const Text('Guardar'),
          ),
        ],
      ),
    );

    if (result != null && context.mounted) {
      final success = await context.read<JobProvider>().addNote(job.id, result);
      
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              success ? '✅ Nota agregada' : '❌ Error al agregar nota',
            ),
            backgroundColor: success ? Colors.green : Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _handleAddImages(BuildContext context) async {
    final ImagePicker picker = ImagePicker();
    
    // Mostrar opciones
    final source = await showDialog<ImageSource>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Agregar Imágenes'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.camera_alt),
              title: const Text('Tomar foto'),
              onTap: () => Navigator.pop(context, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library),
              title: const Text('Galería'),
              onTap: () => Navigator.pop(context, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );

    if (source != null && context.mounted) {
      try {
        // Permitir selección múltiple solo desde galería
        if (source == ImageSource.gallery) {
          final List<XFile> images = await picker.pickMultiImage();
          
          if (images.isNotEmpty && context.mounted) {
            final filePaths = images.map((e) => e.path).toList();
            final success = await context.read<JobProvider>().uploadFiles(job.id, filePaths);
            
            if (context.mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(
                    success 
                      ? '✅ ${images.length} imagen(es) subida(s) exitosamente' 
                      : '❌ Error al subir imágenes',
                  ),
                  backgroundColor: success ? Colors.green : Colors.red,
                ),
              );
              
              if (success && onRefresh != null) {
                onRefresh!();
              }
            }
          }
        } else {
          // Tomar foto individual
          final XFile? photo = await picker.pickImage(source: source);
          
          if (photo != null && context.mounted) {
            final success = await context.read<JobProvider>().uploadFiles(job.id, [photo.path]);
            
            if (context.mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(
                    success ? '✅ Imagen subida exitosamente' : '❌ Error al subir imagen',
                  ),
                  backgroundColor: success ? Colors.green : Colors.red,
                ),
              );
              
              if (success && onRefresh != null) {
                onRefresh!();
              }
            }
          }
        }
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('❌ Error: ${e.toString()}'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    }
  }

  Future<void> _handleBackToPending(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Volver a Pendiente'),
        content: const Text('¿Deseas desmarcar la llegada y volver esta tarea a estado pendiente?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Confirmar'),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      final success = await context.read<JobProvider>().backToPending(job.id);
      
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              success ? '✅ Tarea vuelta a pendiente' : '❌ Error al volver a pendiente',
            ),
            backgroundColor: success ? Colors.green : Colors.red,
          ),
        );
        
        if (success && onRefresh != null) {
          onRefresh!();
        }
      }
    }
  }

  void _handleViewNotes(BuildContext context) {
    // Por ahora redirigir al detalle de la tarea donde se ven las notas
    onTap();
  }

  Future<void> _handleDeleteJob(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar Tarea'),
        content: Text('¿Estás seguro que deseas eliminar la tarea de ${job.clientName}?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Eliminar'),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      final success = await context.read<JobProvider>().deleteJob(job.id);
      
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              success ? '✅ Tarea eliminada exitosamente' : '❌ Error al eliminar tarea',
            ),
            backgroundColor: success ? Colors.green : Colors.red,
          ),
        );
        
        if (success && onRefresh != null) {
          onRefresh!();
        }
      }
    }
  }

  void _handleEditJob(BuildContext context) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('✏️ Funcionalidad de editar en desarrollo'),
        backgroundColor: Colors.blue,
      ),
    );
  }
}
