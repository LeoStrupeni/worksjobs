import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/budget_provider.dart';
import '../models/budget.dart';
import 'budget_detail_screen.dart';
import 'create_budget_screen.dart';

class BudgetsListScreen extends StatefulWidget {
  const BudgetsListScreen({super.key});

  @override
  State<BudgetsListScreen> createState() => _BudgetsListScreenState();
}

class _BudgetsListScreenState extends State<BudgetsListScreen> {
  @override
  void initState() {
    super.initState();
    // Cargar permisos y presupuestos al iniciar
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final budgetProvider = Provider.of<BudgetProvider>(context, listen: false);
      budgetProvider.loadUserPermissions();
      budgetProvider.fetchBudgets();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Presupuestos'),
        elevation: 0,
      ),
      body: Consumer<BudgetProvider>(
        builder: (context, budgetProvider, child) {
          if (budgetProvider.isLoading && budgetProvider.budgets.isEmpty) {
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
                      budgetProvider.fetchBudgets();
                    },
                    child: const Text('Reintentar'),
                  ),
                ],
              ),
            );
          }

          if (budgetProvider.budgets.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.description_outlined,
                    size: 80,
                    color: Colors.grey[400],
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'No hay presupuestos',
                    style: TextStyle(
                      fontSize: 18,
                      color: Colors.grey[600],
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Crea tu primer presupuesto',
                    style: TextStyle(
                      color: Colors.grey[500],
                    ),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () async {
              await budgetProvider.fetchBudgets();
            },
            child: Column(
              children: [
                // Header con contador
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  color: Theme.of(context).primaryColor.withOpacity(0.1),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '${budgetProvider.totalBudgets} ${budgetProvider.totalBudgets == 1 ? 'presupuesto' : 'presupuestos'}',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Theme.of(context).primaryColor,
                            ),
                          ),
                          if (budgetProvider.totalPages > 1)
                            Text(
                              'Página ${budgetProvider.currentPage} de ${budgetProvider.totalPages}',
                              style: const TextStyle(
                                fontSize: 12,
                                color: Colors.grey,
                              ),
                            ),
                        ],
                      ),
                      if (budgetProvider.totalPages > 1)
                        Row(
                          children: [
                            IconButton(
                              icon: const Icon(Icons.chevron_left),
                              onPressed: budgetProvider.hasPreviousPage
                                  ? () => budgetProvider.previousPage()
                                  : null,
                            ),
                            IconButton(
                              icon: const Icon(Icons.chevron_right),
                              onPressed: budgetProvider.hasNextPage
                                  ? () => budgetProvider.nextPage()
                                  : null,
                            ),
                          ],
                        ),
                    ],
                  ),
                ),

                // Lista de presupuestos
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.all(8),
                    itemCount: budgetProvider.budgets.length,
                    itemBuilder: (context, index) {
                      final budget = budgetProvider.budgets[index];
                      return _BudgetCard(budget: budget);
                    },
                  ),
                ),
              ],
            ),
          );
        },
      ),
      floatingActionButton: Consumer<BudgetProvider>(
        builder: (context, budgetProvider, child) {
          // Solo mostrar FAB si tiene permiso para crear presupuestos
          if (!budgetProvider.canCreateBudgets) {
            return const SizedBox.shrink();
          }
          
          return FloatingActionButton.extended(
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const CreateBudgetScreen(),
                ),
              );
            },
            label: const Text('Nuevo Presupuesto'),
            icon: const Icon(Icons.add),
          );
        },
      ),
    );
  }
}

class _BudgetCard extends StatelessWidget {
  final Budget budget;

  const _BudgetCard({required this.budget});

  @override
  Widget build(BuildContext context) {
    final formatter = NumberFormat.currency(symbol: '\$', decimalDigits: 2);
    final dateFormatter = DateFormat('dd/MM/yyyy');

    return Card(
      margin: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
      elevation: 2,
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => BudgetDetailScreen(budgetId: budget.id!),
            ),
          );
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Número de presupuesto
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    budget.nroFactura,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.blue,
                    ),
                  ),
                  Chip(
                    label: Text(
                      formatter.format(budget.total),
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                    backgroundColor: Colors.green[50],
                    labelStyle: TextStyle(color: Colors.green[700]),
                  ),
                ],
              ),
              const SizedBox(height: 8),

              // Cliente
              Row(
                children: [
                  Icon(Icons.person, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      budget.clientName ?? 'Sin cliente',
                      style: const TextStyle(fontSize: 14),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),

              // Fecha
              Row(
                children: [
                  Icon(Icons.calendar_today, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Text(
                    dateFormatter.format(DateTime.parse(budget.fecha)),
                    style: TextStyle(fontSize: 12, color: Colors.grey[700]),
                  ),
                ],
              ),
              const SizedBox(height: 8),

              // Items count
              Row(
                children: [
                  Icon(Icons.shopping_cart, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Text(
                    '${budget.itemCount} ${budget.itemCount == 1 ? 'item' : 'items'}',
                    style: TextStyle(fontSize: 12, color: Colors.grey[700]),
                  ),
                  const SizedBox(width: 16),
                  if (budget.createdByName != null) ...[
                    Icon(Icons.account_circle, size: 16, color: Colors.grey[600]),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        budget.createdByName!,
                        style: TextStyle(fontSize: 12, color: Colors.grey[700]),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
