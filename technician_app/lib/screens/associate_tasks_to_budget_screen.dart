import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/budget.dart';
import '../providers/budget_provider.dart';

/// Pantalla para asociar tareas existentes a un presupuesto
class AssociateTasksToBudgetScreen extends StatefulWidget {
  final Budget budget;

  const AssociateTasksToBudgetScreen({
    Key? key,
    required this.budget,
  }) : super(key: key);

  @override
  State<AssociateTasksToBudgetScreen> createState() =>
      _AssociateTasksToBudgetScreenState();
}

class _AssociateTasksToBudgetScreenState
    extends State<AssociateTasksToBudgetScreen> {
  final TextEditingController _searchController = TextEditingController();
  final Set<int> _selectedJobIds = {};
  bool _isAssociating = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadAvailableJobs();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _loadAvailableJobs() {
    final budgetProvider = Provider.of<BudgetProvider>(context, listen: false);
    budgetProvider.fetchAvailableJobs(search: _searchController.text);
  }

  void _onSearchChanged() {
    // Debounce para evitar demasiadas llamadas
    Future.delayed(const Duration(milliseconds: 500), () {
      if (mounted && _searchController.text.isNotEmpty) {
        _loadAvailableJobs();
      }
    });
  }

  Future<void> _confirmAssociation() async {
    if (_selectedJobIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Debe seleccionar al menos una tarea'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    setState(() => _isAssociating = true);

    final budgetProvider = Provider.of<BudgetProvider>(context, listen: false);

    final result = await budgetProvider.associateJobsToBudget(
      budgetId: int.parse(widget.budget.idFactura ?? '0'),
      budgetNumber: widget.budget.nroFactura,
      jobIds: _selectedJobIds.toList(),
    );

    setState(() => _isAssociating = false);

    if (mounted) {
      if (result['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Tareas asociadas correctamente'),
            backgroundColor: Colors.green,
          ),
        );

        // Volver a la pantalla anterior
        Navigator.of(context).pop(true);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Error al asociar tareas'),
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
        title: const Text('Asociar Tareas'),
        elevation: 0,
      ),
      body: Column(
        children: [
          // Banner informativo
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            color: Colors.blue[50],
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Presupuesto: ${widget.budget.nroFactura}',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Cliente: ${widget.budget.clientName}',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey[700],
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Seleccione las tareas que desea asociar a este presupuesto',
                  style: TextStyle(
                    fontSize: 13,
                    color: Colors.grey[600],
                    fontStyle: FontStyle.italic,
                  ),
                ),
              ],
            ),
          ),

          // Barra de búsqueda
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Buscar por ID o descripción...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          _searchController.clear();
                          _loadAvailableJobs();
                        },
                      )
                    : null,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              onChanged: (value) => _onSearchChanged(),
            ),
          ),

          // Lista de tareas
          Expanded(
            child: Consumer<BudgetProvider>(
              builder: (context, budgetProvider, child) {
                if (budgetProvider.isLoadingJobs) {
                  return const Center(
                    child: CircularProgressIndicator(),
                  );
                }

                if (budgetProvider.errorMessage != null) {
                  return Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(
                          Icons.error_outline,
                          size: 64,
                          color: Colors.red,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Error: ${budgetProvider.errorMessage}',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: Colors.red),
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton(
                          onPressed: _loadAvailableJobs,
                          child: const Text('Reintentar'),
                        ),
                      ],
                    ),
                  );
                }

                final jobs = budgetProvider.availableJobs;

                if (jobs.isEmpty) {
                  return Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.work_off_outlined,
                          size: 64,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'No hay tareas disponibles',
                          style: TextStyle(
                            fontSize: 16,
                            color: Colors.grey[600],
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Las tareas disponibles son aquellas\nsin presupuesto asignado y no cerradas',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 13,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  );
                }

                return ListView.builder(
                  itemCount: jobs.length,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemBuilder: (context, index) {
                    final job = jobs[index];
                    final jobId = job['id'] as int;
                    final isSelected = _selectedJobIds.contains(jobId);

                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: CheckboxListTile(
                        value: isSelected,
                        onChanged: (bool? value) {
                          setState(() {
                            if (value == true) {
                              _selectedJobIds.add(jobId);
                            } else {
                              _selectedJobIds.remove(jobId);
                            }
                          });
                        },
                        title: Text(
                          'Tarea #${job['id']}',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(height: 4),
                            Text(
                              job['job_description'] ?? 'Sin descripción',
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                Icon(
                                  Icons.person_outline,
                                  size: 14,
                                  color: Colors.grey[600],
                                ),
                                const SizedBox(width: 4),
                                Expanded(
                                  child: Text(
                                    job['client_name'] ?? 'Sin cliente',
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.grey[600],
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                            if (job['technician_names'] != null) ...[
                              const SizedBox(height: 2),
                              Row(
                                children: [
                                  Icon(
                                    Icons.engineering_outlined,
                                    size: 14,
                                    color: Colors.grey[600],
                                  ),
                                  const SizedBox(width: 4),
                                  Expanded(
                                    child: Text(
                                      job['technician_names'],
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Colors.grey[600],
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                            const SizedBox(height: 4),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 4,
                              ),
                              decoration: BoxDecoration(
                                color: _getStatusColor(job['status']),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                job['status'] ?? 'Sin estado',
                                style: const TextStyle(
                                  fontSize: 11,
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ],
                        ),
                        selected: isSelected,
                        activeColor: Theme.of(context).primaryColor,
                      ),
                    );
                  },
                );
              },
            ),
          ),

          // Barra inferior con contador y botón
          if (!_isAssociating)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.grey.withOpacity(0.3),
                    spreadRadius: 1,
                    blurRadius: 5,
                  ),
                ],
              ),
              child: Row(
                children: [
                  // Contador de seleccionadas
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      color: _selectedJobIds.isEmpty
                          ? Colors.grey[300]
                          : Theme.of(context).primaryColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          Icons.check_circle,
                          size: 18,
                          color: _selectedJobIds.isEmpty
                              ? Colors.grey[600]
                              : Theme.of(context).primaryColor,
                        ),
                        const SizedBox(width: 6),
                        Text(
                          '${_selectedJobIds.length} seleccionada(s)',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: _selectedJobIds.isEmpty
                                ? Colors.grey[600]
                                : Theme.of(context).primaryColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),

                  // Botón confirmar
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed:
                          _selectedJobIds.isEmpty ? null : _confirmAssociation,
                      icon: const Icon(Icons.link),
                      label: const Text('Asociar Tareas'),
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),

          // Indicador de carga durante asociación
          if (_isAssociating)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.grey.withOpacity(0.3),
                    spreadRadius: 1,
                    blurRadius: 5,
                  ),
                ],
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: const [
                  SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                  SizedBox(width: 12),
                  Text(
                    'Asociando tareas...',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Color _getStatusColor(String? status) {
    switch (status?.toLowerCase()) {
      case 'pendiente':
        return Colors.orange;
      case 'en proceso':
        return Colors.blue;
      case 'arribado':
        return Colors.purple;
      case 'completado':
        return Colors.green;
      default:
        return Colors.grey;
    }
  }
}
