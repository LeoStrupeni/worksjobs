import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:share_plus/share_plus.dart';
import 'dart:io';
import 'dart:typed_data';
import 'package:path_provider/path_provider.dart';
import '../providers/budget_provider.dart';
import '../models/budget.dart';
import '../models/budget_item.dart';
import 'associate_tasks_to_budget_screen.dart';

class BudgetDetailScreen extends StatefulWidget {
  final int budgetId;

  const BudgetDetailScreen({
    super.key,
    required this.budgetId,
  });

  @override
  State<BudgetDetailScreen> createState() => _BudgetDetailScreenState();
}

class _BudgetDetailScreenState extends State<BudgetDetailScreen> {
  @override
  void initState() {
    super.initState();
    // Cargar detalle al iniciar
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<BudgetProvider>(context, listen: false)
          .fetchBudgetDetail(widget.budgetId);
    });
  }

  /// Compartir PDF del presupuesto
  Future<void> _shareBudgetPdf(BuildContext context, Budget budget) async {
    // Mostrar loading dialog
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
                  Text('Generando PDF...'),
                ],
              ),
            ),
          ),
        );
      },
    );

    try {
      final budgetProvider = Provider.of<BudgetProvider>(context, listen: false);
      
      // Descargar el PDF
      final result = await budgetProvider.downloadBudgetPdf(
        int.parse(budget.idFactura ?? '0'),
      );

      // Cerrar loading dialog
      if (mounted) Navigator.of(context).pop();

      if (result['success'] == true) {
        final Uint8List pdfBytes = result['pdf_bytes'];
        
        // Guardar temporalmente el archivo
        final tempDir = await getTemporaryDirectory();
        final filename = 'presupuesto_${budget.nroFactura}.pdf';
        final file = File('${tempDir.path}/$filename');
        await file.writeAsBytes(pdfBytes);

        // Compartir el archivo
        await Share.shareXFiles(
          [XFile(file.path)],
          text: 'Presupuesto ${budget.nroFactura}',
        );
      } else {
        // Mostrar error
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(result['message'] ?? 'Error al generar PDF'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      // Cerrar loading dialog si está abierto
      if (mounted) Navigator.of(context).pop();
      
      // Mostrar error
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
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
        title: const Text('Detalle del Presupuesto'),
        elevation: 0,
      ),
      body: Consumer<BudgetProvider>(
        builder: (context, budgetProvider, child) {
          if (budgetProvider.isLoading) {
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
                    onPressed: () {
                      budgetProvider.clearError();
                      budgetProvider.fetchBudgetDetail(widget.budgetId);
                    },
                    child: const Text('Reintentar'),
                  ),
                ],
              ),
            );
          }

          final budget = budgetProvider.currentBudget;

          if (budget == null) {
            return const Center(
              child: Text('No se encontró el presupuesto'),
            );
          }

          return _BudgetDetailContent(budget: budget);
        },
      ),
      bottomNavigationBar: Consumer<BudgetProvider>(
        builder: (context, budgetProvider, child) {
          final budget = budgetProvider.currentBudget;

          if (budget == null) return const SizedBox.shrink();

          return Container(
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
            child: SafeArea(
              child: Row(
                children: [
                  // Botón Compartir PDF
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () => _shareBudgetPdf(context, budget),
                      icon: const Icon(Icons.picture_as_pdf, size: 20),
                      label: const Text('Compartir PDF'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.orange,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  // Botón Asociar Tareas
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () async {
                        final result = await Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => AssociateTasksToBudgetScreen(
                              budget: budget,
                            ),
                          ),
                        );

                        // Si se asociaron tareas, recargar el presupuesto
                        if (result == true && mounted) {
                          Provider.of<BudgetProvider>(context, listen: false)
                              .fetchBudgetDetail(widget.budgetId);
                        }
                      },
                      icon: const Icon(Icons.link, size: 20),
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
          );
        },
      ),
    );
  }
}

class _BudgetDetailContent extends StatelessWidget {
  final Budget budget;

  const _BudgetDetailContent({required this.budget});

  @override
  Widget build(BuildContext context) {
    final formatter = NumberFormat.currency(symbol: '\$', decimalDigits: 2);
    final dateFormatter = DateFormat('dd/MM/yyyy');

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header Card
          Card(
            elevation: 4,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Número presupuesto
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Presupuesto',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey[600],
                              ),
                            ),
                            Text(
                              budget.nroFactura,
                              style: const TextStyle(
                                fontSize: 22,
                                fontWeight: FontWeight.bold,
                                color: Colors.blue,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Icon(
                        Icons.description,
                        size: 40,
                        color: Colors.blue,
                      ),
                    ],
                  ),
                  const Divider(height: 24),

                  // Cliente
                  _InfoRow(
                    icon: Icons.person,
                    label: 'Cliente',
                    value: budget.clientName ?? 'Sin cliente',
                  ),
                  const SizedBox(height: 8),

                  // CUIT
                  if (budget.clientCuit != null)
                    _InfoRow(
                      icon: Icons.badge,
                      label: 'CUIT',
                      value: budget.clientCuit!,
                    ),
                  if (budget.clientCuit != null) const SizedBox(height: 8),

                  // Fecha
                  _InfoRow(
                    icon: Icons.calendar_today,
                    label: 'Fecha',
                    value: dateFormatter.format(DateTime.parse(budget.fecha)),
                  ),
                  const SizedBox(height: 8),

                  // Creado por
                  if (budget.createdByName != null)
                    _InfoRow(
                      icon: Icons.account_circle,
                      label: 'Creado por',
                      value: budget.createdByName!,
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Observaciones
          if (budget.observaciones != null && budget.observaciones!.isNotEmpty)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.notes, size: 20, color: Colors.blue),
                        const SizedBox(width: 8),
                        Text(
                          'Observaciones',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey[800],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      budget.observaciones!,
                      style: const TextStyle(fontSize: 14),
                    ),
                  ],
                ),
              ),
            ),
          const SizedBox(height: 16),

          // Items
          Text(
            'Items (${budget.items.length})',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.grey[800],
            ),
          ),
          const SizedBox(height: 8),

          // Lista de items
          ...budget.items.map((item) => _ItemCard(item: item)).toList(),

          const SizedBox(height: 16),

          // Total
          Card(
            color: Colors.green[50],
            elevation: 4,
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'TOTAL',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    formatter.format(budget.total),
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.green[700],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 18, color: Colors.grey[600]),
        const SizedBox(width: 8),
        Text(
          '$label: ',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: Colors.grey[700],
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(fontSize: 14),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}

class _ItemCard extends StatelessWidget {
  final BudgetItem item;

  const _ItemCard({required this.item});

  @override
  Widget build(BuildContext context) {
    final formatter = NumberFormat.currency(symbol: '\$', decimalDigits: 2);

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Código y tipo
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Text(
                      item.codigo,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: Colors.blue,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 6,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: item.isService ? Colors.orange[100] : Colors.blue[100],
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        item.isService ? 'Servicio' : 'Producto',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: item.isService ? Colors.orange[800] : Colors.blue[800],
                        ),
                      ),
                    ),
                  ],
                ),
                Text(
                  formatter.format(item.subtotal),
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Colors.green,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),

            // Descripción
            Text(
              item.descripcion,
              style: const TextStyle(fontSize: 13),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 8),

            // Cantidad, unidad y precio unitario
            Row(
              children: [
                Text(
                  '${item.quantity} ${item.unitType}',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[700],
                    fontWeight: FontWeight.w500,
                  ),
                ),
                Text(
                  ' × ${formatter.format(item.unitPrice)}',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
