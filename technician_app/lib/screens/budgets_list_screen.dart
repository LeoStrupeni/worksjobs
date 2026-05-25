import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/budget_provider.dart';
import '../providers/job_provider.dart';
import '../models/budget.dart';
import '../models/client.dart';
import 'budget_detail_screen.dart';
import 'create_budget_screen.dart';

class BudgetsListScreen extends StatefulWidget {
  const BudgetsListScreen({super.key});

  @override
  State<BudgetsListScreen> createState() => _BudgetsListScreenState();
}

class _BudgetsListScreenState extends State<BudgetsListScreen> {
  static const int _clientLimitShort = 10;
  static const int _clientLimitMedium = 20;
  static const int _clientLimitLong = 35;

  Client? _selectedClient;
  DateTimeRange? _selectedDateRange;
  final TextEditingController _clientController = TextEditingController();
  Timer? _clientDebounce;
  bool _isClientLoading = false;
  List<Client> _clientSuggestions = [];

  @override
  void initState() {
    super.initState();
    _clientController.addListener(_onClientTextChanged);
    // Cargar permisos y presupuestos al iniciar
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final budgetProvider = Provider.of<BudgetProvider>(context, listen: false);
      budgetProvider.loadUserPermissions();
      budgetProvider.fetchBudgets();
    });
  }

  @override
  void dispose() {
    _clientDebounce?.cancel();
    _clientController.removeListener(_onClientTextChanged);
    _clientController.dispose();
    super.dispose();
  }

  DateTime? _parseDate(String? value) {
    if (value == null || value.isEmpty) {
      return null;
    }

    try {
      return DateTime.parse(value);
    } catch (_) {
      return null;
    }
  }

  int _dynamicClientLimit(String query) {
    final len = query.trim().length;
    if (len <= 3) return _clientLimitShort;
    if (len <= 5) return _clientLimitMedium;
    return _clientLimitLong;
  }

  void _onClientTextChanged() {
    if (_selectedClient != null && _clientController.text == _selectedClient!.name) {
      return;
    }

    final query = _clientController.text.trim();
    _clientDebounce?.cancel();

    if (query.length < 2) {
      setState(() {
        _clientSuggestions = [];
        _isClientLoading = false;
      });
      return;
    }

    _clientDebounce = Timer(const Duration(milliseconds: 350), () async {
      setState(() => _isClientLoading = true);
      final results = await context
          .read<JobProvider>()
          .searchClients(query, limit: _dynamicClientLimit(query));

      if (!mounted) return;
      setState(() {
        _clientSuggestions = results;
        _isClientLoading = false;
      });
    });
  }

  Future<void> _selectClient(Client client) async {
    setState(() {
      _selectedClient = client;
      _clientController.text = client.name;
      _clientSuggestions = [];
    });
    await _applyFilters();
  }

  Future<void> _clearClientFilter() async {
    setState(() {
      _selectedClient = null;
      _clientController.clear();
      _clientSuggestions = [];
    });
    await _applyFilters();
  }

  String _formatFilterDate(DateTime date) =>
      DateFormat('dd/MM/yyyy').format(date);

  Future<void> _applyFilters() async {
    final budgetProvider = context.read<BudgetProvider>();
    await budgetProvider.applyFilters(
      clientId: _selectedClient?.id,
      dateFrom: _selectedDateRange?.start,
      dateTo: _selectedDateRange?.end,
    );
  }

  Future<void> _clearFilters() async {
    setState(() {
      _selectedClient = null;
      _selectedDateRange = null;
      _clientController.clear();
      _clientSuggestions = [];
    });

    await context.read<BudgetProvider>().clearFilters();
  }

  Future<void> _selectDateRange() async {
    final now = DateTime.now();
    final initialRange = _selectedDateRange ??
        DateTimeRange(
          start: DateTime(now.year, now.month, 1),
          end: now,
        );

    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020, 1, 1),
      lastDate: DateTime(2100, 12, 31),
      initialDateRange: initialRange,
      locale: const Locale('es'),
    );

    if (picked != null) {
      setState(() {
        _selectedDateRange = picked;
      });
      await _applyFilters();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Presupuestos'),
        elevation: 0,
        backgroundColor: const Color(0xFF00274E),  // ✅ Fondo azul oscuro
        foregroundColor: Colors.white,
      ),
      body: Consumer<BudgetProvider>(
        builder: (context, budgetProvider, child) {
          final providerDateFrom = _parseDate(budgetProvider.filterDateFrom);
          final providerDateTo = _parseDate(budgetProvider.filterDateTo);
          if (_selectedDateRange == null &&
              providerDateFrom != null &&
              providerDateTo != null) {
            _selectedDateRange = DateTimeRange(
              start: providerDateFrom,
              end: providerDateTo,
            );
          }

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
                    budgetProvider.hasActiveFilters
                        ? Icons.search_off
                        : Icons.description_outlined,
                    size: 80,
                    color: Colors.grey[400],
                  ),
                  const SizedBox(height: 16),
                  Text(
                    budgetProvider.hasActiveFilters
                        ? 'Sin resultados para los filtros aplicados'
                        : 'No hay presupuestos',
                    style: TextStyle(
                      fontSize: 18,
                      color: Colors.grey[600],
                    ),
                    textAlign: TextAlign.center,
                  ),
                  if (budgetProvider.hasActiveFilters) ...[
                    const SizedBox(height: 16),
                    ElevatedButton.icon(
                      onPressed: _clearFilters,
                      icon: const Icon(Icons.clear),
                      label: const Text('Limpiar filtros'),
                    ),
                  ] else ...[
                    const SizedBox(height: 8),
                    Text(
                      'Crea tu primer presupuesto',
                      style: TextStyle(
                        color: Colors.grey[500],
                      ),
                    ),
                  ],
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
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.fromLTRB(12, 10, 12, 8),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        TextField(
                          controller: _clientController,
                          decoration: InputDecoration(
                            labelText: 'Cliente',
                            hintText: 'Escribe al menos 2 letras',
                            prefixIcon: const Icon(Icons.person_search),
                            suffixIcon: _selectedClient != null || _clientController.text.isNotEmpty
                                ? IconButton(
                                    icon: const Icon(Icons.clear),
                                    onPressed: _clearClientFilter,
                                  )
                                : null,
                            filled: true,
                            fillColor: Colors.white,
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                            isDense: true,
                          ),
                        ),
                        if (_isClientLoading)
                          const Padding(
                            padding: EdgeInsets.only(top: 8),
                            child: LinearProgressIndicator(minHeight: 2),
                          ),
                        if (_clientSuggestions.isNotEmpty)
                          Container(
                            margin: const EdgeInsets.only(top: 6),
                            constraints: const BoxConstraints(maxHeight: 180),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              border: Border.all(color: Colors.grey.shade300),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: ListView.separated(
                              itemCount: _clientSuggestions.length,
                              separatorBuilder: (_, __) => const Divider(height: 1),
                              itemBuilder: (context, index) {
                                final client = _clientSuggestions[index];
                                return ListTile(
                                  dense: true,
                                  title: Text(client.name),
                                  subtitle: client.phone != null ? Text(client.phone!) : null,
                                  onTap: () => _selectClient(client),
                                );
                              },
                            ),
                          ),
                        if (_selectedClient != null)
                          Padding(
                            padding: const EdgeInsets.only(top: 8),
                            child: Wrap(
                              spacing: 8,
                              runSpacing: 8,
                              children: [
                                InputChip(
                                  avatar: const Icon(Icons.person, size: 18),
                                  label: Text(
                                    'Cliente: ${_selectedClient!.name}',
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  onDeleted: _clearClientFilter,
                                ),
                              ],
                            ),
                          ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            OutlinedButton.icon(
                              onPressed: _selectDateRange,
                              icon: const Icon(Icons.date_range),
                              label: Text(
                                _selectedDateRange != null
                                    ? '${_formatFilterDate(_selectedDateRange!.start)} - ${_formatFilterDate(_selectedDateRange!.end)}'
                                    : 'Rango de fechas',
                              ),
                            ),
                            if (budgetProvider.hasActiveFilters)
                              TextButton.icon(
                                onPressed: _clearFilters,
                                icon: const Icon(Icons.clear),
                                label: const Text('Limpiar'),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),

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
    
    // Parsear fecha de forma segura (puede venir en formato DD-MM-YYYY o YYYY-MM-DD)
    DateTime parseFecha(String fecha) {
      try {
        return DateTime.parse(fecha);
      } catch (e) {
        try {
          final parts = fecha.split('-');
          if (parts.length == 3) {
            return DateTime(
              int.parse(parts[2]), // year
              int.parse(parts[1]), // month
              int.parse(parts[0]), // day
            );
          }
        } catch (e2) {}
      }
      return DateTime.now();
    }

    return Card(
      margin: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
      elevation: 2,
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => BudgetDetailScreen(budgetId: budget.idFactura ?? ''),
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
                    dateFormatter.format(parseFecha(budget.fecha)),
                    style: TextStyle(fontSize: 12, color: Colors.grey[700]),
                  ),
                ],
              ),
              const SizedBox(height: 8),

              // Creado por (si está disponible)
              if (budget.createdByName != null)
                Row(
                  children: [
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
                ),
            ],
          ),
        ),
      ),
    );
  }
}
