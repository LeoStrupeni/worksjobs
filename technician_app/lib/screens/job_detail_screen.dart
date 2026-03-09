import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:image_picker/image_picker.dart';
import 'package:share_plus/share_plus.dart';
import 'package:gal/gal.dart';
import 'package:http/http.dart' as http;
import 'dart:typed_data';
import 'dart:convert';
import 'package:path_provider/path_provider.dart';
import 'dart:io';
import '../providers/job_provider.dart';
import '../providers/auth_provider.dart';
import '../models/job.dart';
import '../models/product.dart';
import '../models/technician.dart';
import '../services/auth_service.dart';
import '../utils/custom_alerts.dart';
import 'edit_job_screen.dart';
import 'pdf_config_screen.dart';

class JobDetailScreen extends StatefulWidget {
  final int jobId;

  const JobDetailScreen({super.key, required this.jobId});

  @override
  State<JobDetailScreen> createState() => _JobDetailScreenState();
}

class _JobDetailScreenState extends State<JobDetailScreen> {
  final _noteController = TextEditingController();
  List<Technician> _technicians = [];
  Set<int> _selectedImageIds = {}; // Para tracking de imágenes seleccionadas
  bool _isSelectionMode = false; // Modo selección múltiple

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
              title: const Text('Seleccionar de Galería (Múltiple)'),
              onTap: () async {
                Navigator.pop(context);
                final List<XFile> images = await picker.pickMultiImage(
                  maxWidth: 1920,
                  maxHeight: 1080,
                  imageQuality: 85,
                );
                if (images.isNotEmpty) {
                  await _uploadPhotos(images.map((e) => e.path).toList());
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
    final PageController pageController = PageController(initialPage: initialIndex);
    int currentPage = initialIndex;
    
    // Obtener usuario para verificar permisos
    final user = Provider.of<AuthProvider>(context, listen: false).user;
    
    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => Dialog(
          backgroundColor: Colors.black,
          insetPadding: EdgeInsets.zero,
          child: Stack(
            children: [
              PageView.builder(
                itemCount: files.length,
                controller: pageController,
                onPageChanged: (index) {
                  setState(() {
                    currentPage = index;
                  });
                },
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
              // Botones superiores
              Positioned(
                top: 16,
                left: 0,
                right: 0,
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Botones de acción (descarga y compartir)
                      Row(
                        children: [
                          IconButton(
                            icon: Icon(Icons.download, color: Colors.white, size: 28),
                            onPressed: () => _downloadImage(files[currentPage]),
                            tooltip: 'Descargar',
                          ),
                          // Botón compartir solo si tiene permiso
                          if (user?.canShare == true) ...[
                            SizedBox(width: 8),
                            IconButton(
                              icon: Icon(Icons.share, color: Colors.white, size: 28),
                              onPressed: () => _shareImage(files[currentPage]),
                              tooltip: 'Compartir',
                            ),
                          ],
                        ],
                      ),
                      // Botón de cerrar
                      IconButton(
                        icon: Icon(Icons.close, color: Colors.white, size: 32),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                ),
              ),
              // Indicador de página
              if (files.length > 1)
                Positioned(
                  bottom: 16,
                  left: 0,
                  right: 0,
                  child: Center(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      decoration: BoxDecoration(
                        color: Colors.black54,
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        '${currentPage + 1} / ${files.length}',
                        style: TextStyle(color: Colors.white, fontSize: 16),
                      ),
                    ),
                  ),
                ),
            ],
          ),
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

  Future<void> _uploadPhotos(List<String> filePaths) async {
    final jobProvider = context.read<JobProvider>();
    final count = filePaths.length;
    
    // Mostrar indicador de carga
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Row(
            children: [
              const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                ),
              ),
              const SizedBox(width: 16),
              Text('Subiendo $count foto${count != 1 ? 's' : ''}...'),
            ],
          ),
          duration: const Duration(seconds: 30),
        ),
      );
    }

    final success = await jobProvider.uploadFiles(widget.jobId, filePaths);
    
    if (mounted) {
      ScaffoldMessenger.of(context).hideCurrentSnackBar();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            success 
              ? '✅ $count foto${count != 1 ? 's' : ''} subida${count != 1 ? 's' : ''} exitosamente' 
              : '❌ Error al subir fotos',
          ),
          backgroundColor: success ? Colors.green : Colors.red,
        ),
      );
    }
  }

  Future<void> _deleteImage(dynamic file) async {
    final confirmed = await CustomAlerts.showConfirmAlert(
      context,
      title: 'Eliminar Imagen',
      message: '¿Estás seguro que deseas eliminar esta imagen?',
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    );

    if (!confirmed || !mounted) return;

    final success = await CustomAlerts.executeWithLoading(
      context,
      operation: () async {
        return await context.read<JobProvider>().deleteFile(widget.jobId, file.id);
      },
      loadingMessage: 'Eliminando imagen...',
      successTitle: 'Imagen eliminada',
      successMessage: 'La imagen se eliminó correctamente',
      errorTitle: 'Error al eliminar imagen',
      getErrorMessage: () => context.read<JobProvider>().errorMessage ?? 'No se pudo eliminar la imagen',
    );
  }

  Future<void> _downloadImage(dynamic file) async {
    try {
      final imageUrl = 'https://tecnicos.strupeni.com.ar/storage/${file.name}';
      
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
                Text('Descargando imagen...'),
              ],
            ),
            duration: Duration(seconds: 3),
          ),
        );
      }

      // Descargar la imagen
      final response = await http.get(Uri.parse(imageUrl));
      
      if (response.statusCode == 200) {
        // Guardar temporalmente
        final dir = await getTemporaryDirectory();
        final filePath = '${dir.path}/${file.originalName ?? file.name}';
        final tempFile = File(filePath);
        await tempFile.writeAsBytes(response.bodyBytes);
        
        // Guardar en la galería
        await Gal.putImage(filePath, album: 'Strupeni');
        
        // Limpiar archivo temporal
        await tempFile.delete();

        if (mounted) {
          ScaffoldMessenger.of(context).hideCurrentSnackBar();
          
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('✅ Imagen guardada en la galería'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        throw Exception('Error al descargar la imagen');
      }
    } catch (e) {
      print('Error al descargar imagen: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('❌ Error al descargar: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _shareImage(dynamic file) async {
    try {
      final imageUrl = 'https://tecnicos.strupeni.com.ar/storage/${file.name}';
      
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
                Text('Preparando imagen...'),
              ],
            ),
            duration: Duration(seconds: 3),
          ),
        );
      }

      // Descargar la imagen
      final response = await http.get(Uri.parse(imageUrl));
      
      if (response.statusCode == 200) {
        // Guardar temporalmente
        final tempDir = await getTemporaryDirectory();
        final fileName = file.originalName ?? 'imagen_${DateTime.now().millisecondsSinceEpoch}.jpg';
        final filePath = '${tempDir.path}/$fileName';
        final tempFile = File(filePath);
        await tempFile.writeAsBytes(response.bodyBytes);

        if (mounted) {
          ScaffoldMessenger.of(context).hideCurrentSnackBar();
        }

        // Compartir
        final result = await Share.shareXFiles(
          [XFile(filePath)],
          text: 'Imagen de tarea - Strupeni Electrónica',
        );

        if (result.status == ShareResultStatus.success && mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('✅ Imagen compartida exitosamente'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        throw Exception('Error al descargar la imagen');
      }
    } catch (e) {
      print('Error al compartir imagen: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('❌ Error al compartir: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _shareSelectedImages() async {
    final jobProvider = context.read<JobProvider>();
    final files = jobProvider.files;
    
    // Filtrar solo las imágenes seleccionadas
    final selectedFiles = files.where((file) => _selectedImageIds.contains(file.id)).toList();
    
    if (selectedFiles.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No hay imágenes seleccionadas'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    try {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                  ),
                ),
                const SizedBox(width: 16),
                Text('Preparando ${selectedFiles.length} imagen${selectedFiles.length > 1 ? 'es' : ''}...'),
              ],
            ),
            duration: const Duration(seconds: 10),
          ),
        );
      }

      // Descargar todas las imágenes seleccionadas
      final tempDir = await getTemporaryDirectory();
      final List<XFile> xFiles = [];

      for (var file in selectedFiles) {
        final imageUrl = 'https://tecnicos.strupeni.com.ar/storage/${file.name}';
        final response = await http.get(Uri.parse(imageUrl));
        
        if (response.statusCode == 200) {
          final fileName = file.originalName ?? 'imagen_${DateTime.now().millisecondsSinceEpoch}_${file.id}.jpg';
          final filePath = '${tempDir.path}/$fileName';
          final tempFile = File(filePath);
          await tempFile.writeAsBytes(response.bodyBytes);
          xFiles.add(XFile(filePath));
        }
      }

      if (mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
      }

      // Compartir
      if (xFiles.isNotEmpty) {
        final result = await Share.shareXFiles(
          xFiles,
          text: 'Imágenes de tarea - Strupeni Electrónica',
        );

        if (result.status == ShareResultStatus.success && mounted) {
          setState(() {
            _isSelectionMode = false;
            _selectedImageIds.clear();
          });
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✅ ${xFiles.length} imagen${xFiles.length > 1 ? 'es' : ''} compartida${xFiles.length > 1 ? 's' : ''} exitosamente'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        throw Exception('No se pudieron descargar las imágenes');
      }
    } catch (e) {
      print('Error al compartir imágenes: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('❌ Error al compartir: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
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
                final jobProvider = context.read<JobProvider>();
                
                try {
                  final success = await CustomAlerts.executeWithLoading(
                    context,
                    operation: () async {
                      return await jobProvider.updateJobTechnicians(
                        job.id!,
                        selectedIds,
                      );
                    },
                    loadingMessage: 'Actualizando técnicos...',
                    successTitle: 'Técnicos actualizados',
                    successMessage: 'Los técnicos se actualizaron correctamente',
                    errorTitle: 'Error',
                    getErrorMessage: () => jobProvider.errorMessage ?? 'No se pudo actualizar los técnicos',
                  );
                  
                  // Cerrar el modal DESPUÉS de que termine la operación
                  if (mounted && success) {
                    Navigator.pop(context);
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

  Future<void> _navigateToEditProducts() async {
    final job = context.read<JobProvider>().selectedJob;
    if (job == null) return;
    
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => _ProductsDialog(job: job),
    );
    
    // Si se guardaron cambios, recargar el detalle
    if (result == true && mounted) {
      context.read<JobProvider>().fetchJobDetail(widget.jobId);
    }
  }

  Future<void> _showCloseJobDialog() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cerrar Cita'),
        content: const Text('¿Está seguro que desea cerrar esta tarea?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.green,
            ),
            child: const Text('Sí, cerrar'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      final success = await context.read<JobProvider>().closeJob(widget.jobId);
      
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

  Future<void> _handleGeneratePDF() async {
    final jobProvider = context.read<JobProvider>();
    final job = jobProvider.selectedJob;
    final notes = jobProvider.notes;
    final files = jobProvider.files;

    if (job == null) return;

    // Navegar a la pantalla de configuración del PDF
    final config = await Navigator.push<Map<String, dynamic>>(
      context,
      MaterialPageRoute(
        builder: (context) => PdfConfigScreen(
          job: job,
          notes: notes,
          files: files,
        ),
      ),
    );

    // Si el usuario canceló o no configuró el PDF, salir
    if (config == null || !mounted) return;

    // Mostrar indicador de carga
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
            Text('Generando PDF...'),
          ],
        ),
        duration: Duration(seconds: 30),
      ),
    );

    try {
      // Generar el PDF en el servidor
      final result = await jobProvider.generateJobPDF(job.id!, config);

      if (mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();

        if (result['success'] == true) {
          // Decodificar el PDF de base64
          final pdfBytes = base64Decode(result['pdf']);
          
          // Guardar el archivo temporalmente
          final tempDir = await getTemporaryDirectory();
          final fileName = result['filename'] ?? 'Trabajo_${job.id}.pdf';
          final filePath = '${tempDir.path}/$fileName';
          final pdfFile = File(filePath);
          await pdfFile.writeAsBytes(pdfBytes);

          // Mostrar diálogo para compartir o guardar
          if (mounted) {
            showDialog(
              context: context,
              builder: (context) => AlertDialog(
                title: const Text('PDF Generado'),
                content: const Text('¿Qué deseas hacer con el PDF?'),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Cancelar'),
                  ),
                  TextButton(
                    onPressed: () async {
                      Navigator.pop(context);
                      // Guardar en descargas (esto depende de la plataforma)
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('PDF guardado en descargas'),
                          backgroundColor: Colors.green,
                        ),
                      );
                    },
                    child: const Text('Guardar'),
                  ),
                  ElevatedButton(
                    onPressed: () async {
                      Navigator.pop(context);
                      
                      // Compartir el PDF
                      final result = await Share.shareXFiles(
                        [XFile(filePath)],
                        text: 'Trabajo realizado - Tarea #${job.id}',
                      );

                      if (mounted && result.status == ShareResultStatus.success) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('✅ PDF compartido exitosamente'),
                            backgroundColor: Colors.green,
                          ),
                        );
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF00274E),
                    ),
                    child: const Text('Compartir'),
                  ),
                ],
              ),
            );
          }
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('❌ Error: ${result['message'] ?? 'No se pudo generar el PDF'}'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      print('Error al generar PDF: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('❌ Error: $e'),
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
                
                // Productos
                _buildProductsSection(job),
                
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
          final user = Provider.of<AuthProvider>(context, listen: false).user;
          
          // Si hay imágenes seleccionadas, mostrar botón de compartir
          if (_isSelectionMode && _selectedImageIds.isNotEmpty && user?.canShare == true) {
            return FloatingActionButton.extended(
              onPressed: _shareSelectedImages,
              icon: const Icon(Icons.share, color: Colors.white),
              label: Text(
                'Compartir (${_selectedImageIds.length})',
                style: const TextStyle(color: Colors.white),
              ),
              backgroundColor: const Color(0xFF00274E),
              foregroundColor: Colors.white,
            );
          }
          
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
    // Obtener usuario para verificar permiso de compartir
    final user = Provider.of<AuthProvider>(context, listen: false).user;
    final canShare = user?.canShare == true;
    
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
              Row(
                children: [
                  Text(
                    '${files.length}',
                    style: TextStyle(
                      fontSize: 16,
                      color: Colors.grey[600],
                    ),
                  ),
                  // Botón para activar/desactivar selección múltiple
                  if (canShare && files.isNotEmpty) ...[
                    const SizedBox(width: 8),
                    // Botón de compartir con menú
                    PopupMenuButton<String>(
                      icon: const Icon(
                        Icons.share,
                        color: Color(0xFF00274E),
                      ),
                      tooltip: 'Compartir imágenes',
                      onSelected: (value) {
                        if (value == 'all') {
                          setState(() {
                            _isSelectionMode = true;
                            _selectedImageIds.clear();
                            for (var file in files) {
                              _selectedImageIds.add(file.id);
                            }
                          });
                          // Auto compartir
                          Future.delayed(const Duration(milliseconds: 300), () {
                            _shareSelectedImages();
                          });
                        } else if (value == 'select') {
                          setState(() {
                            _isSelectionMode = !_isSelectionMode;
                            if (!_isSelectionMode) {
                              _selectedImageIds.clear();
                            }
                          });
                        }
                      },
                      itemBuilder: (context) => [
                        const PopupMenuItem(
                          value: 'all',
                          child: Row(
                            children: [
                              Icon(Icons.select_all, color: Color(0xFF00274E)),
                              SizedBox(width: 8),
                              Text('Compartir todas'),
                            ],
                          ),
                        ),
                        const PopupMenuItem(
                          value: 'select',
                          child: Row(
                            children: [
                              Icon(Icons.done_all, color: Color(0xFF00274E)),
                              SizedBox(width: 8),
                              Text('Seleccionar manualmente'),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ],
          ),
        ),
        // Banner de modo selección
        if (_isSelectionMode && files.isNotEmpty)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: const Color(0xFF00274E).withOpacity(0.1),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(Icons.info_outline, color: Color(0xFF00274E), size: 20),
                    const SizedBox(width: 8),
                    Text(
                      'Seleccionadas: ${_selectedImageIds.length}',
                      style: const TextStyle(
                        color: Color(0xFF00274E),
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
                Row(
                  children: [
                    TextButton(
                      onPressed: () {
                        setState(() {
                          _selectedImageIds.clear();
                          for (var file in files) {
                            _selectedImageIds.add(file.id);
                          }
                        });
                      },
                      child: const Text('Todas', style: TextStyle(color: Color(0xFF00274E))),
                    ),
                    TextButton(
                      onPressed: () {
                        setState(() {
                          _selectedImageIds.clear();
                        });
                      },
                      child: const Text('Ninguna', style: TextStyle(color: Color(0xFF00274E))),
                    ),
                    TextButton(
                      onPressed: () {
                        setState(() {
                          _isSelectionMode = false;
                          _selectedImageIds.clear();
                        });
                      },
                      child: const Text('Cancelar', style: TextStyle(color: Colors.red)),
                    ),
                  ],
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
                  final isSelected = _selectedImageIds.contains(file.id);
                  
                  return GestureDetector(
                    onTap: () {
                      if (_isSelectionMode) {
                        setState(() {
                          if (isSelected) {
                            _selectedImageIds.remove(file.id);
                          } else {
                            _selectedImageIds.add(file.id);
                          }
                        });
                      } else {
                        _showImageViewer(index, files);
                      }
                    },
                    child: Container(
                      margin: const EdgeInsets.only(right: 12),
                      width: 120,
                      decoration: BoxDecoration(
                        color: Colors.grey[200],
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: isSelected ? const Color(0xFF00274E) : Colors.grey[300]!,
                          width: isSelected ? 3 : 1,
                        ),
                      ),
                      child: Stack(
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: Image.network(
                              imageUrl,
                              fit: BoxFit.cover,
                              width: double.infinity,
                              height: double.infinity,
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
                          // Checkbox cuando está en modo selección
                          if (_isSelectionMode)
                            Positioned(
                              top: 4,
                              left: 4,
                              child: Container(
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                    color: const Color(0xFF00274E),
                                    width: 2,
                                  ),
                                ),
                                child: Icon(
                                  isSelected ? Icons.check_circle : Icons.circle_outlined,
                                  color: isSelected ? const Color(0xFF00274E) : Colors.grey,
                                  size: 24,
                                ),
                              ),
                            ),
                          // Botón de eliminar - solo si tiene permiso y no está en modo selección
                          if (!_isSelectionMode && (context.read<JobProvider>().permissions?.delete ?? false))
                            Positioned(
                              top: 4,
                              right: 4,
                              child: Container(
                                decoration: BoxDecoration(
                                  color: Colors.red.withOpacity(0.9),
                                  shape: BoxShape.circle,
                                ),
                                child: IconButton(
                                  icon: const Icon(Icons.delete_outline, size: 16),
                                  color: Colors.white,
                                  padding: EdgeInsets.zero,
                                  constraints: const BoxConstraints(
                                    minWidth: 28,
                                    minHeight: 28,
                                  ),
                                  onPressed: () => _deleteImage(file),
                                ),
                              ),
                            ),
                        ],
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

  Widget _buildProductsSection(Job job) {
    final hasProducts = job.products != null && job.products!.isNotEmpty;
    
    return Card(
      margin: const EdgeInsets.all(12),
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Row(
                  children: [
                    Icon(Icons.inventory_2, color: Color(0xFF00274E)),
                    SizedBox(width: 8),
                    Text(
                      'Productos',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
                // Botón para editar productos (solo si no está cerrada)
                if (!job.isClosed)
                  IconButton(
                    onPressed: () => _navigateToEditProducts(),
                    icon: const Icon(Icons.edit, size: 20),
                    color: const Color(0xFF00274E),
                    tooltip: 'Gestionar Productos',
                  ),
              ],
            ),
          ),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            color: Colors.grey[100],
            child: hasProducts
                ? Column(
                    children: job.products!.map((product) {
                      return Card(
                        margin: const EdgeInsets.only(bottom: 8),
                        child: ListTile(
                          leading: const CircleAvatar(
                            backgroundColor: Color(0xFF00274E),
                            child: Icon(
                              Icons.inventory_2,
                              size: 20,
                              color: Colors.white,
                            ),
                          ),
                          title: Text(
                            '${product['codigo'] ?? 'N/A'} - ${product['descripcion'] ?? 'N/A'}',
                            style: const TextStyle(fontWeight: FontWeight.w500),
                          ),
                          subtitle: Text(
                            'Cantidad: ${product['quantity'] ?? 'N/A'} ${product['unit_type'] ?? 'Unidad'}',
                            style: TextStyle(color: Colors.grey[600]),
                          ),
                        ),
                      );
                    }).toList(),
                  )
                : Center(
                    child: Column(
                      children: [
                        Icon(Icons.inventory_2_outlined, size: 48, color: Colors.grey[400]),
                        const SizedBox(height: 8),
                        Text(
                          'No hay productos asociados',
                          style: TextStyle(color: Colors.grey[600]),
                        ),
                        if (!job.isClosed) ...[
                          const SizedBox(height: 8),
                          TextButton.icon(
                            onPressed: _navigateToEditProducts,
                            icon: const Icon(Icons.add),
                            label: const Text('Agregar Productos'),
                          ),
                        ],
                      ],
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionButtons(Job job) {
    return Consumer<JobProvider>(
      builder: (context, jobProvider, child) {
        final permissions = jobProvider.permissions;
        
        // Si no hay permisos, no mostrar botones
        if (permissions == null) {
          return const SizedBox.shrink();
        }

        // Obtener usuario para verificar permisos de PDF
        final user = Provider.of<AuthProvider>(context, listen: false).user;

        // Si está cerrada, solo mostrar botón de generar PDF si tiene permiso
        if (job.isClosed) {
          // Solo mostrar botón de PDF si el usuario tiene permiso
          if (user?.canGeneratePDF != true) {
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
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _handleGeneratePDF,
                icon: const Icon(Icons.picture_as_pdf),
                label: const Text('Generar PDF'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF00274E),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  foregroundColor: Colors.white,
                ),
              ),
            ),
          );
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

// Modal de gestión de productos
class _ProductsDialog extends StatefulWidget {
  final Job job;

  const _ProductsDialog({required this.job});

  @override
  State<_ProductsDialog> createState() => _ProductsDialogState();
}

class _ProductsDialogState extends State<_ProductsDialog> {
  final _searchController = TextEditingController();
  final _quantityController = TextEditingController(text: '1');
  
  List<Product> _searchResults = [];
  List<Product> _initialProducts = [];
  List<SelectedProduct> _selectedProducts = [];
  
  Product? _selectedProduct;
  String _selectedUnitType = 'Unidad';
  bool _isSearching = false;
  bool _isLoading = true;
  
  // Guardar el job completo con address_id
  Job? _fullJob;

  @override
  void initState() {
    super.initState();
    _loadInitialData();
    _searchController.addListener(_onSearchChanged);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _quantityController.dispose();
    super.dispose();
  }

  Future<void> _loadInitialData() async {
    setState(() => _isLoading = true);
    
    try {
      print('🟢 MODAL DETALLE: Iniciando carga de productos...');
      print('🟢 MODAL DETALLE: widget.job.products = ${widget.job.products}');
      
      // Cargar productos iniciales desde AuthService
      final authService = AuthService();
      final productsData = await authService.getProducts();
      
      // Convertir Map a Product
      final products = productsData.map((p) => Product.fromJson(p)).toList();
      
      // Si no hay productos en el job, cargar el detalle completo (por si acaso)
      List<dynamic> currentProductsData;
      if (widget.job.products == null) {
        print('🟢 MODAL DETALLE: Productos null, cargando detalle completo...');
        final jobProvider = context.read<JobProvider>();
        await jobProvider.fetchJobDetail(widget.job.id!);
        final jobDetail = jobProvider.selectedJob;
        _fullJob = jobDetail; // Guardar el job completo con address_id
        currentProductsData = jobDetail?.products ?? [];
        print('🟢 MODAL DETALLE: Productos cargados desde detalle: ${currentProductsData.length}');
        print('🟢 MODAL DETALLE: addressId desde detalle: ${jobDetail?.addressId}');
      } else {
        currentProductsData = widget.job.products!;
        _fullJob = widget.job; // Usar el job original si ya tiene productos
      }
      
      print('🟢 MODAL DETALLE: currentProductsData length = ${currentProductsData.length}');
      print('🟢 MODAL DETALLE: currentProductsData = $currentProductsData');
      
      setState(() {
        _initialProducts = products;
        _searchResults = products;
        
        // Convertir los Map de productos del backend a SelectedProduct
        _selectedProducts = currentProductsData.map((pData) {
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
        
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error al cargar productos: $e')),
        );
      }
    }
  }

  void _onSearchChanged() {
    final query = _searchController.text.trim();
    
    if (query.isEmpty) {
      setState(() {
        _searchResults = _initialProducts;
        _isSearching = false;
      });
      return;
    }
    
    if (query.length < 2) {
      setState(() {
        _searchResults = [];
        _isSearching = false;
      });
      return;
    }
    
    _performSearch(query);
  }

  Future<void> _performSearch(String query) async {
    setState(() => _isSearching = true);
    
    try {
      final jobProvider = context.read<JobProvider>();
      final results = await jobProvider.searchProducts(query);
      
      if (mounted) {
        setState(() {
          _searchResults = results;
          _isSearching = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isSearching = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error en búsqueda: $e')),
        );
      }
    }
  }

  void _addProduct() {
    if (_selectedProduct == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Selecciona un producto')),
      );
      return;
    }
    
    final quantity = double.tryParse(_quantityController.text) ?? 1.0;
    if (quantity <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('La cantidad debe ser mayor a 0')),
      );
      return;
    }
    
    final newProduct = SelectedProduct(
      product: _selectedProduct!,
      unitType: _selectedUnitType,
      quantity: quantity,
      uniqueId: DateTime.now().millisecondsSinceEpoch.toString(),
    );
    
    setState(() {
      _selectedProducts.add(newProduct);
      _selectedProduct = null;
      _searchController.clear();
      _quantityController.text = '1';
      _selectedUnitType = 'Unidad';
      _searchResults = _initialProducts;
    });
  }

  void _removeProduct(int index) {
    setState(() {
      _selectedProducts.removeAt(index);
    });
  }

  Future<void> _saveProducts() async {
    // Usar el job completo que tiene todos los datos necesarios
    final jobToUse = _fullJob ?? widget.job;
    
    if (jobToUse.addressId == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Error: La tarea no tiene dirección asignada')),
        );
      }
      return;
    }
    
    // Parsear visitDatetime que viene como String
    DateTime visitDateTime;
    try {
      visitDateTime = DateTime.parse(jobToUse.visitDatetime!);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Error al procesar la fecha de visita')),
        );
      }
      return;
    }
    
    // Extraer IDs de técnicos
    final technicianIds = jobToUse.technicians?.map((t) => t['id'] as int).toList();
    
    final success = await CustomAlerts.executeWithLoading(
      context,
      operation: () async {
        return await context.read<JobProvider>().updateJob(
          jobId: jobToUse.id!,
          addressId: jobToUse.addressId!,
          visitDateTime: visitDateTime,
          description: jobToUse.jobDescription ?? '',
          latitude: jobToUse.visitLatitud,
          longitude: jobToUse.visitLongitud,
          technicianIds: technicianIds,
          products: _selectedProducts,
        );
      },
      loadingMessage: 'Guardando productos...',
      successTitle: 'Productos actualizados',
      successMessage: 'Los productos se actualizaron correctamente',
      errorTitle: 'Error al actualizar productos',
      getErrorMessage: () => context.read<JobProvider>().errorMessage ?? 'No se pudieron actualizar los productos',
    );
    
    if (success && mounted) {
      Navigator.pop(context, true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        constraints: const BoxConstraints(maxHeight: 700, maxWidth: 500),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF00274E),
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(16),
                  topRight: Radius.circular(16),
                ),
              ),
              child: Row(
                children: [
                  const Icon(Icons.inventory_2, color: Colors.white),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Gestionar Productos',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            
            if (_isLoading)
              const Expanded(
                child: Center(child: CircularProgressIndicator()),
              )
            else
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Búsqueda de productos
                      const Text(
                        'Buscar Producto',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      TextField(
                        controller: _searchController,
                        decoration: InputDecoration(
                          hintText: 'Buscar por código o descripción...',
                          prefixIcon: const Icon(Icons.search),
                          suffixIcon: _isSearching
                              ? const Padding(
                                  padding: EdgeInsets.all(12),
                                  child: SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  ),
                                )
                              : null,
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                      ),
                      
                      // Resultados de búsqueda
                      if (_searchResults.isNotEmpty && !_isSearching)
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
                                      '${_searchResults.length} producto${_searchResults.length != 1 ? 's' : ''} encontrado${_searchResults.length != 1 ? 's' : ''}',
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
                                  itemCount: _searchResults.length,
                                  separatorBuilder: (context, index) => Divider(height: 1, color: Colors.grey.shade300),
                                  itemBuilder: (context, index) {
                                    final product = _searchResults[index];
                                    final isSelected = _selectedProduct?.id == product.id;
                                    return InkWell(
                                      onTap: () {
                                        setState(() {
                                          _selectedProduct = product;
                                          _searchController.text = product.displayName;
                                          _searchResults = [];
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
                      
                      if (_selectedProduct != null) ...[
                        const SizedBox(height: 16),
                        const Text(
                          'Cantidad y Tipo de Unidad',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            Expanded(
                              flex: 2,
                              child: TextField(
                                controller: _quantityController,
                                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                decoration: const InputDecoration(
                                  labelText: 'Cantidad',
                                  border: OutlineInputBorder(),
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              flex: 3,
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
                                    setState(() => _selectedUnitType = value);
                                  }
                                },
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: _addProduct,
                            icon: const Icon(Icons.add),
                            label: const Text('Agregar Producto'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF00274E),
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 12),
                            ),
                          ),
                        ),
                      ],
                      
                      const SizedBox(height: 24),
                      
                      // Lista de productos agregados
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Productos Agregados',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          Text(
                            '${_selectedProducts.length} producto(s)',
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.grey.shade600,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      
                      if (_selectedProducts.isEmpty)
                        Container(
                          padding: const EdgeInsets.all(24),
                          decoration: BoxDecoration(
                            color: Colors.grey.shade100,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: Colors.grey.shade300),
                          ),
                          child: Center(
                            child: Column(
                              children: [
                                Icon(Icons.inventory_2_outlined, size: 48, color: Colors.grey.shade400),
                                const SizedBox(height: 8),
                                Text(
                                  'No hay productos agregados',
                                  style: TextStyle(color: Colors.grey.shade600),
                                ),
                              ],
                            ),
                          ),
                        )
                      else
                        ListView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: _selectedProducts.length,
                          itemBuilder: (context, index) {
                            final selectedProduct = _selectedProducts[index];
                            return Card(
                              margin: const EdgeInsets.only(bottom: 8),
                              child: ListTile(
                                leading: const CircleAvatar(
                                  backgroundColor: Color(0xFF00274E),
                                  child: Icon(Icons.inventory_2, color: Colors.white, size: 20),
                                ),
                                title: Text(
                                  selectedProduct.product.codigo,
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                                subtitle: Text(
                                  '${selectedProduct.product.descripcion}\n${selectedProduct.quantity} ${selectedProduct.unitType}',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                isThreeLine: true,
                                trailing: IconButton(
                                  icon: const Icon(Icons.delete, color: Colors.red),
                                  onPressed: () => _removeProduct(index),
                                ),
                              ),
                            );
                          },
                        ),
                    ],
                  ),
                ),
              ),
            
            // Footer con botones
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                border: Border(top: BorderSide(color: Colors.grey.shade300)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Cancelar'),
                  ),
                  const SizedBox(width: 12),
                  ElevatedButton(
                    onPressed: _saveProducts,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF00274E),
                      foregroundColor: Colors.white,
                    ),
                    child: const Text('Guardar Cambios'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
