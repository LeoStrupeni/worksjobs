import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import '../models/job.dart';
import '../models/job_permissions.dart';
import '../providers/job_provider.dart';
import '../screens/edit_job_screen.dart';
import '../utils/custom_alerts.dart';

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
      margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      elevation: 3,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
      ),
      shadowColor: const Color(0xFF00274E).withOpacity(0.3),
      color: _getCardBackgroundColor(),
      child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(20),
          child: Padding(
            padding: const EdgeInsets.all(16),
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
              
              // Dirección
              if (job.fullAddress != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 4),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(Icons.location_on, size: 16, color: Colors.grey[600]),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _formatAddress(job.fullAddress!),
                              style: TextStyle(
                                fontSize: 13,
                                color: Colors.grey[700],
                                height: 1.3,
                              ),
                              maxLines: 4,
                              overflow: TextOverflow.ellipsis,
                            ),
                            // Descripción como subtexto
                            if (job.jobDescription != null && job.jobDescription!.isNotEmpty)
                              Padding(
                                padding: const EdgeInsets.only(top: 4),
                                child: Text(
                                  job.jobDescription!.length > 80
                                      ? '${job.jobDescription!.substring(0, 80)}...'
                                      : job.jobDescription!,
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey[500],
                                    fontStyle: FontStyle.italic,
                                  ),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              
              const SizedBox(height: 8),
              
              // Tags de estado
              _buildStatusChip(),
              
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
    
    return Column(
      children: [
        // Botones principales grandes
        if (permissions.update && !job.isInPlace && !job.isClosed)
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () => _handleMarkArrival(context),
              icon: const Icon(Icons.location_on),
              label: const Text('Marcar Arribo'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.orange,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
          ),
        
        if (permissions.update && !job.isInPlace && !job.isClosed)
          const SizedBox(height: 8),
        
        if (permissions.read)
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: onTap,
              icon: const Icon(Icons.visibility),
              label: const Text('Ver Detalles'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.blue,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
          ),
        
        const SizedBox(height: 8),
        
        if (permissions.update && !job.isClosed)
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () => _handleCloseJob(context),
              icon: const Icon(Icons.assignment_turned_in),
              label: const Text('Cerrar Tarea'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.black87,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
          ),
        
        const SizedBox(height: 12),
        const Divider(),
        const SizedBox(height: 8),
        
        // Botones secundarios pequeños en fila
        Row(
          children: [
            if (permissions.update)
              Expanded(
                child: OutlinedButton(
                  onPressed: () => _handleAddNote(context),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.green,
                    side: const BorderSide(color: Colors.green),
                    padding: const EdgeInsets.symmetric(vertical: 8),
                  ),
                  child: const Icon(Icons.note_add, size: 20),
                ),
              ),
            if (permissions.update && permissions.read)
              const SizedBox(width: 8),
            if (permissions.read)
              Expanded(
                child: OutlinedButton(
                  onPressed: () => _handleViewNotes(context),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.blue,
                    side: const BorderSide(color: Colors.blue),
                    padding: const EdgeInsets.symmetric(vertical: 8),
                  ),
                  child: const Icon(Icons.notes, size: 20),
                ),
              ),
            if ((permissions.update || permissions.read) && permissions.update) ...[
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton(
                  onPressed: () => _handleAddImages(context),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.green,
                    side: const BorderSide(color: Colors.green),
                    padding: const EdgeInsets.symmetric(vertical: 8),
                  ),
                  child: const Icon(Icons.camera_alt, size: 20),
                ),
              ),
            ],
          ],
        ),
        
        // Menú dropdown para opciones avanzadas
        // Solo mostrar si hay al menos una opción disponible
        if (!job.isClosed && (
            (permissions.update && job.isInPlace) || // Volver a pendiente
            (isAdmin && permissions.update && !job.isInPlace) || // Editar
            (isAdmin && permissions.delete && !job.isInPlace) // Eliminar
          ))
          PopupMenuButton<String>(
            child: Container(
              margin: const EdgeInsets.only(top: 8),
              padding: const EdgeInsets.symmetric(vertical: 10),
              decoration: BoxDecoration(
                color: const Color(0xFF00274E).withOpacity(0.08),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.more_horiz, color: Color(0xFF00274E)),
                  SizedBox(width: 4),
                  Text(
                    'Más opciones',
                    style: TextStyle(
                      color: Color(0xFF00274E),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
            itemBuilder: (context) => [
              if (permissions.update && job.isInPlace && !job.isClosed)
                const PopupMenuItem(
                  value: 'backToPending',
                  child: Row(
                    children: [
                      Icon(Icons.undo, color: Colors.red, size: 20),
                      SizedBox(width: 8),
                      Text('Volver a pendiente'),
                    ],
                  ),
                ),
              if (isAdmin && permissions.update && !job.isInPlace && !job.isClosed)
                const PopupMenuItem(
                  value: 'edit',
                  child: Row(
                    children: [
                      Icon(Icons.edit, color: Colors.blue, size: 20),
                      SizedBox(width: 8),
                      Text('Editar'),
                    ],
                  ),
                ),
              if (isAdmin && permissions.delete && !job.isInPlace && !job.isClosed)
                const PopupMenuItem(
                  value: 'delete',
                  child: Row(
                    children: [
                      Icon(Icons.delete, color: Colors.red, size: 20),
                      SizedBox(width: 8),
                      Text('Eliminar'),
                    ],
                  ),
                ),
            ],
            onSelected: (value) {
              switch (value) {
                case 'backToPending':
                  _handleBackToPending(context);
                  break;
                case 'edit':
                  _handleEditJob(context);
                  break;
                case 'delete':
                  _handleDeleteJob(context);
                  break;
              }
            },
          ),
      ],
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
    String statusText = job.status ?? 'Desconocido';
    
    // Si está pendiente y vencida, mostrar "Vencida" en negro
    if (job.isOverdue) {
      color = Colors.black;
      statusText = 'Vencida';
    }
    
    return Chip(
      label: Text(statusText),
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

  Color _getCardBackgroundColor() {
    // Cerradas - Negro sutil
    if (job.isClosed) {
      return const Color(0xFFE0E0E0); // Gris muy claro con tono frío
    }
    
    // En lugar - Verde sutil (similar al pills pero diferente)
    if (job.isInPlace && !job.isClosed) {
      return const Color(0xFFE8F5E9); // Verde muy claro
    }
    
    // Pendientes vencidas - Rojo sutil
    if (job.isOverdue) {
      return const Color(0xFFFFEBEE); // Rojo muy claro
    }
    
    // Pendientes normales - Gris (color original)
    return const Color(0xFFD6DEE6);
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
    final confirmed = await CustomAlerts.showConfirmAlert(
      context,
      title: 'Marcar Llegada',
      message: '¿Deseas marcar tu llegada al lugar de trabajo?',
      confirmText: 'Confirmar',
      cancelText: 'Cancelar',
    );

    if (confirmed && context.mounted) {
      final jobProvider = context.read<JobProvider>();
      
      final success = await CustomAlerts.executeWithLoading(
        context,
        operation: () => jobProvider.markArrival(job.id!),
        loadingMessage: 'Registrando llegada...',
        successTitle: 'Llegada registrada',
        successMessage: 'Tu llegada fue registrada exitosamente',
        errorTitle: 'Error al registrar',
        getErrorMessage: () => jobProvider.errorMessage ?? 'No se pudo registrar la llegada',
      );
      
      if (success && onRefresh != null) {
        onRefresh!();
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
      final success = await context.read<JobProvider>().closeJob(job.id!, result);
      
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
      final success = await CustomAlerts.executeWithLoading(
        context,
        operation: () async {
          return await context.read<JobProvider>().addNote(job.id!, result);
        },
        loadingMessage: 'Agregando nota...',
        successTitle: 'Nota agregada',
        successMessage: 'La nota se agregó correctamente',
        errorTitle: 'Error al agregar nota',
        getErrorMessage: () => context.read<JobProvider>().errorMessage ?? 'No se pudo agregar la nota',
      );
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
            final jobProvider = context.read<JobProvider>();
            
            final success = await CustomAlerts.executeWithLoading(
              context,
              operation: () => jobProvider.uploadFiles(job.id!, filePaths),
              loadingMessage: 'Subiendo ${images.length} imagen(es)...',
              successTitle: 'Imágenes subidas',
              successMessage: '${images.length} imagen(es) subida(s) exitosamente',
              errorTitle: 'Error al subir',
              getErrorMessage: () => jobProvider.errorMessage ?? 'No se pudieron subir las imágenes',
            );
            
            if (success && onRefresh != null) {
              onRefresh!();
            }
          }
        } else {
          // Tomar foto individual
          final XFile? photo = await picker.pickImage(source: source);
          
          if (photo != null && context.mounted) {
            final jobProvider = context.read<JobProvider>();
            
            final success = await CustomAlerts.executeWithLoading(
              context,
              operation: () => jobProvider.uploadFiles(job.id!, [photo.path]),
              loadingMessage: 'Subiendo imagen...',
              successTitle: 'Imagen subida',
              successMessage: 'La imagen se subió exitosamente',
              errorTitle: 'Error al subir',
              getErrorMessage: () => jobProvider.errorMessage ?? 'No se pudo subir la imagen',
            );
            
            if (success && onRefresh != null) {
              onRefresh!();
            }
          }
        }
      } catch (e) {
        if (context.mounted) {
          await CustomAlerts.showErrorAlert(
            context,
            title: 'Error',
            message: 'Error al procesar las imágenes: ${e.toString()}',
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
      final success = await context.read<JobProvider>().backToPending(job.id!);
      
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
    final confirmed = await CustomAlerts.showConfirmAlert(
      context,
      title: 'Eliminar Tarea',
      message: '¿Estás seguro que deseas eliminar la tarea de ${job.clientName}?',
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    );

    if (confirmed && context.mounted) {
      final jobProvider = context.read<JobProvider>();
      
      final success = await CustomAlerts.executeWithLoading(
        context,
        operation: () => jobProvider.deleteJob(job.id!),
        loadingMessage: 'Eliminando tarea...',
        successTitle: 'Tarea eliminada',
        successMessage: 'La tarea fue eliminada exitosamente',
        errorTitle: 'Error al eliminar',
        getErrorMessage: () => jobProvider.errorMessage ?? 'No se pudo eliminar la tarea',
      );
      
      if (success && onRefresh != null) {
        onRefresh!();
      }
    }
  }

  void _handleEditJob(BuildContext context) async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => EditJobScreen(job: job),
      ),
    );

    // Si se editó la tarea, recargar la lista
    if (result == true && onRefresh != null) {
      onRefresh!();
    }
  }

  String _formatAddress(String address) {
    // Dividir por comas para poner cada parte en una línea diferente
    final parts = address.split(',');
    if (parts.length > 1) {
      // Retornar con saltos de línea explícitos
      return parts.map((p) => p.trim()).join('\n');
    }
    return address;
  }
}
