import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import '../models/job.dart';
import '../models/job_permissions.dart';
import '../models/note.dart';
import '../models/product.dart';
import '../providers/job_provider.dart';
import '../services/auth_service.dart';
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
              
              // Tags de estado y productos
              Wrap(
                spacing: 8,
                runSpacing: 4,
                children: [
                  _buildStatusChip(),
                  if (job.products != null && job.products!.isNotEmpty)
                    _buildProductsChip(),
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
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton(
                  onPressed: () => _handleManageProducts(context),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF00274E),
                    side: const BorderSide(color: Color(0xFF00274E)),
                    padding: const EdgeInsets.symmetric(vertical: 8),
                  ),
                  child: const Icon(Icons.inventory_2, size: 20),
                ),
              ),
            ],
          ],
        ),
        
        // Menú dropdown para opciones avanzadas
        // Solo mostrar si hay al menos una opción disponible
        if (!job.isClosed && (
            (permissions.update && job.isInPlace) || // Volver a pendiente
            (permissions.update && !job.isInPlace) || // Editar
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
              if (permissions.update && !job.isInPlace && !job.isClosed)
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

  Widget _buildProductsChip() {
    final productCount = job.products?.length ?? 0;
    
    return Chip(
      avatar: const Icon(
        Icons.inventory_2,
        size: 14,
        color: Color(0xFF00274E),
      ),
      label: Text('$productCount producto${productCount != 1 ? 's' : ''}'),
      backgroundColor: const Color(0xFF00274E).withOpacity(0.1),
      labelStyle: const TextStyle(
        fontSize: 11,
        color: Color(0xFF00274E),
        fontWeight: FontWeight.w500,
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
            style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
            child: const Text('Sí, cerrar'),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      final success = await context.read<JobProvider>().closeJob(job.id!);
      
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
      
      if (success && onRefresh != null) {
        onRefresh!();
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

  Future<void> _handleManageProducts(BuildContext context) async {
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => _ProductsDialog(job: job),
    );
    
    if (result == true && onRefresh != null) {
      onRefresh!();
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

  void _handleViewNotes(BuildContext context) async {
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => _NotesDialog(job: job, permissions: permissions),
    );
    
    if (result == true && onRefresh != null) {
      onRefresh!();
    }
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
      print('🔵 MODAL GESTIONAR: Iniciando carga de productos...');
      print('🔵 MODAL GESTIONAR: widget.job.products = ${widget.job.products}');
      
      // Cargar productos iniciales desde AuthService
      final authService = AuthService();
      final productsData = await authService.getProducts();
      
      // Convertir Map a Product
      final products = productsData.map((p) => Product.fromJson(p)).toList();
      
      // Si no hay productos en el job, cargar el detalle completo
      List<dynamic> currentProductsData;
      if (widget.job.products == null) {
        print('🔵 MODAL GESTIONAR: Productos null, cargando detalle completo...');
        final jobProvider = context.read<JobProvider>();
        await jobProvider.fetchJobDetail(widget.job.id!);
        final jobDetail = jobProvider.selectedJob;
        _fullJob = jobDetail; // Guardar el job completo con address_id
        currentProductsData = jobDetail?.products ?? [];
        print('🔵 MODAL GESTIONAR: Productos cargados desde detalle: ${currentProductsData.length}');
        print('🔵 MODAL GESTIONAR: addressId desde detalle: ${jobDetail?.addressId}');
        print('🔵 MODAL GESTIONAR: clientId desde detalle: ${jobDetail?.clientId}');
      } else {
        currentProductsData = widget.job.products!;
        _fullJob = widget.job; // Usar el job original si ya tiene productos
      }
      
      print('🔵 MODAL GESTIONAR: currentProductsData length = ${currentProductsData.length}');
      print('🔵 MODAL GESTIONAR: currentProductsData = $currentProductsData');
      
      setState(() {
        _initialProducts = products;
        _searchResults = products;
        
        // Convertir los Map de productos del backend a SelectedProduct
        _selectedProducts = currentProductsData.map((pData) {
          print('🔵 MODAL GESTIONAR: Procesando producto: $pData');
          
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
        
        print('🔵 MODAL GESTIONAR: _selectedProducts.length después de cargar = ${_selectedProducts.length}');
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
    print('🔵 SAVE: Guardando ${_selectedProducts.length} productos...');
    
    // Usar el job completo que tiene todos los datos necesarios
    final jobToUse = _fullJob ?? widget.job;
    
    print('🔵 SAVE: jobToUse.id = ${jobToUse.id}');
    print('🔵 SAVE: jobToUse.addressId = ${jobToUse.addressId}');
    
    if (jobToUse.addressId == null) {
      print('❌ SAVE: addressId es NULL!');
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
      print('❌ SAVE: Error parseando fecha: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Error al procesar la fecha de visita')),
        );
      }
      return;
    }
    
    // Extraer IDs de técnicos
    final technicianIds = jobToUse.technicians?.map((t) => t['id'] as int).toList();
    print('🔵 SAVE: technicianIds = $technicianIds');
    print('🔵 SAVE: addressId = ${jobToUse.addressId}');
    print('🔵 SAVE: visitDateTime = $visitDateTime');
    print('🔵 SAVE: description = ${jobToUse.jobDescription}');
    
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

// Modal de gestión de notas
class _NotesDialog extends StatefulWidget {
  final Job job;
  final JobPermissions permissions;

  const _NotesDialog({required this.job, required this.permissions});

  @override
  State<_NotesDialog> createState() => _NotesDialogState();
}

class _NotesDialogState extends State<_NotesDialog> {
  final _noteController = TextEditingController();
  
  List<Note> _notes = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadNotes();
  }

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _loadNotes() async {
    setState(() => _isLoading = true);
    
    try {
      final jobProvider = context.read<JobProvider>();
      final result = await jobProvider.jobService.getNotes(widget.job.id!);
      
      if (mounted && result['success'] == true) {
        setState(() {
          _notes = result['notes'] as List<Note>;
          _isLoading = false;
        });
      } else {
        setState(() => _isLoading = false);
      }
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error al cargar notas: $e')),
        );
      }
    }
  }

  Future<void> _addNote() async {
    final note = _noteController.text.trim();
    
    if (note.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Escribe una nota')),
      );
      return;
    }
    
    final success = await CustomAlerts.executeWithLoading(
      context,
      operation: () async {
        return await context.read<JobProvider>().addNote(widget.job.id!, note);
      },
      loadingMessage: 'Agregando nota...',
      successTitle: 'Nota agregada',
      successMessage: 'La nota se agregó correctamente',
      errorTitle: 'Error al agregar nota',
      getErrorMessage: () => context.read<JobProvider>().errorMessage ?? 'No se pudo agregar la nota',
      showSuccessAlert: false, // No mostrar alert, solo refrescar
    );
    
    if (success) {
      _noteController.clear();
      await _loadNotes();
    }
  }

  Future<void> _deleteNote(Note note) async {
    final confirmed = await CustomAlerts.showConfirmAlert(
      context,
      title: 'Eliminar Nota',
      message: '¿Estás seguro que deseas eliminar esta nota?',
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    );

    if (!confirmed || !mounted) return;

    final success = await CustomAlerts.executeWithLoading(
      context,
      operation: () async {
        return await context.read<JobProvider>().deleteNote(widget.job.id!, note.id);
      },
      loadingMessage: 'Eliminando nota...',
      successTitle: 'Nota eliminada',
      successMessage: 'La nota se eliminó correctamente',
      errorTitle: 'Error al eliminar nota',
      getErrorMessage: () => context.read<JobProvider>().errorMessage ?? 'No se pudo eliminar la nota',
      showSuccessAlert: false,
    );
    
    if (success) {
      await _loadNotes();
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
                  const Icon(Icons.note, color: Colors.white),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Notas',
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
                      // Agregar nueva nota
                      if (widget.permissions.create)
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Agregar Nota',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 8),
                            TextField(
                              controller: _noteController,
                              maxLines: 3,
                              decoration: InputDecoration(
                                hintText: 'Escribe tu nota...',
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                              ),
                            ),
                            const SizedBox(height: 12),
                            SizedBox(
                              width: double.infinity,
                              child: ElevatedButton.icon(
                                onPressed: _addNote,
                                icon: const Icon(Icons.add),
                                label: const Text('Agregar Nota'),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF00274E),
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(vertical: 12),
                                ),
                              ),
                            ),
                            const SizedBox(height: 24),
                          ],
                        ),
                      
                      // Lista de notas
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Notas Existentes',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          Text(
                            '${_notes.length} nota(s)',
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.grey.shade600,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      
                      if (_notes.isEmpty)
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
                                Icon(Icons.note_outlined, size: 48, color: Colors.grey.shade400),
                                const SizedBox(height: 8),
                                Text(
                                  'No hay notas',
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
                          itemCount: _notes.length,
                          itemBuilder: (context, index) {
                            final note = _notes[index];
                            return Card(
                              margin: const EdgeInsets.only(bottom: 8),
                              color: Colors.blue.shade50,
                              child: ListTile(
                                leading: CircleAvatar(
                                  backgroundColor: Colors.blue.shade700,
                                  child: const Icon(Icons.note, color: Colors.white, size: 20),
                                ),
                                title: Text(
                                  note.note,
                                  style: const TextStyle(fontSize: 14),
                                ),
                                subtitle: Text(
                                  '${note.userName ?? 'Usuario'} - ${note.createdAt ?? ''}',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey.shade700,
                                  ),
                                ),
                                trailing: widget.permissions.delete
                                    ? IconButton(
                                        icon: const Icon(Icons.delete, color: Colors.red),
                                        onPressed: () => _deleteNote(note),
                                      )
                                    : null,
                              ),
                            );
                          },
                        ),
                    ],
                  ),
                ),
              ),
            
            // Footer con botón
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                border: Border(top: BorderSide(color: Colors.grey.shade300)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  ElevatedButton(
                    onPressed: () => Navigator.pop(context, true),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF00274E),
                      foregroundColor: Colors.white,
                    ),
                    child: const Text('Cerrar'),
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
