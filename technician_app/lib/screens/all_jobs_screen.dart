import 'dart:async';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../models/client.dart';
import '../models/job_permissions.dart';
import '../providers/job_provider.dart';
import '../widgets/job_card.dart';
import 'job_detail_screen.dart';

class AllJobsScreen extends StatefulWidget {
  const AllJobsScreen({super.key});

  @override
  State<AllJobsScreen> createState() => _AllJobsScreenState();
}

class _AllJobsScreenState extends State<AllJobsScreen> {
  static const int _clientLimitShort = 10;
  static const int _clientLimitMedium = 20;
  static const int _clientLimitLong = 35;

  final TextEditingController _searchController = TextEditingController();
  final TextEditingController _clientController = TextEditingController();
  Timer? _searchDebounce;
  Timer? _clientDebounce;
  bool _isClientLoading = false;
  List<Client> _clientSuggestions = [];

  Client? _selectedClient;
  DateTimeRange? _selectedDateRange;
  String? _selectedStatus;

  static const Map<String, String> _statusOptions = {
    '': 'Todos',
    'pendiente': 'Pendiente',
    'en_lugar': 'En lugar',
    'cerrada': 'Cerrada',
    'archivada': 'Archivada',
  };

  @override
  void initState() {
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = context.read<JobProvider>();

      _searchController.text = provider.allJobsSearch;
      _selectedStatus = provider.allJobsStatus;

      if (provider.allJobsStartDate != null && provider.allJobsEndDate != null) {
        final start = DateTime.tryParse(provider.allJobsStartDate!);
        final end = DateTime.tryParse(provider.allJobsEndDate!);
        if (start != null && end != null) {
          _selectedDateRange = DateTimeRange(start: start, end: end);
        }
      }

      if (provider.allJobs.isEmpty) {
        provider.fetchAllJobs(page: 1);
      }
    });

    _searchController.addListener(_onSearchChanged);
    _clientController.addListener(_onClientTextChanged);
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _clientDebounce?.cancel();
    _searchController.removeListener(_onSearchChanged);
    _clientController.removeListener(_onClientTextChanged);
    _searchController.dispose();
    _clientController.dispose();
    super.dispose();
  }

  void _onSearchChanged() {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 450), () {
      _applyFilters();
    });
  }

  String _toApiDate(DateTime value) {
    return DateFormat('yyyy-MM-dd').format(value);
  }

  String _toUiDate(DateTime value) {
    return DateFormat('dd/MM/yyyy').format(value);
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

  Future<void> _pickDateRange() async {
    final now = DateTime.now();
    final initial = _selectedDateRange ??
        DateTimeRange(
          start: DateTime(now.year, now.month, 1),
          end: now,
        );

    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020, 1, 1),
      lastDate: DateTime(2100, 12, 31),
      initialDateRange: initial,
      locale: const Locale('es'),
    );

    if (picked != null) {
      setState(() {
        _selectedDateRange = picked;
      });
      await _applyFilters();
    }
  }

  Future<void> _applyFilters() async {
    await context.read<JobProvider>().applyAllJobsFilters(
          search: _searchController.text,
          clientId: _selectedClient?.id,
          startDate:
              _selectedDateRange != null ? _toApiDate(_selectedDateRange!.start) : null,
          endDate:
              _selectedDateRange != null ? _toApiDate(_selectedDateRange!.end) : null,
          status: _selectedStatus,
        );
  }

  Future<void> _clearFilters() async {
    _searchDebounce?.cancel();
    setState(() {
      _searchController.clear();
      _selectedClient = null;
      _clientController.clear();
      _clientSuggestions = [];
      _selectedDateRange = null;
      _selectedStatus = null;
    });

    await context.read<JobProvider>().clearAllJobsFilters();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<JobProvider>(
      builder: (context, jobProvider, child) {
        if (jobProvider.isLoading && jobProvider.allJobs.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }

        if (jobProvider.errorMessage != null && jobProvider.allJobs.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, size: 64, color: Colors.red),
                const SizedBox(height: 16),
                Text(
                  'Error: ${jobProvider.errorMessage}',
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: Colors.red),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () {
                    jobProvider.clearError();
                    jobProvider.fetchAllJobs(page: 1);
                  },
                  child: const Text('Reintentar'),
                ),
              ],
            ),
          );
        }

        final permissions = jobProvider.permissions ??
            JobPermissions(
              create: false,
              read: false,
              update: false,
              delete: false,
              allPermissions: const [],
              roles: const [],
            );

        return RefreshIndicator(
          onRefresh: () => jobProvider.fetchAllJobs(page: 1),
          child: Column(
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(12),
                color: Theme.of(context).primaryColor.withOpacity(0.08),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    TextField(
                      controller: _searchController,
                      decoration: InputDecoration(
                        hintText: 'Buscar por cliente, OT, presupuesto o descripción',
                        prefixIcon: const Icon(Icons.search),
                        suffixIcon: _searchController.text.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () {
                                  _searchController.clear();
                                  _applyFilters();
                                },
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
                    const SizedBox(height: 10),
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
                              subtitle:
                                  client.phone != null ? Text(client.phone!) : null,
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
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        OutlinedButton.icon(
                          onPressed: _pickDateRange,
                          icon: const Icon(Icons.date_range),
                          label: Text(
                            _selectedDateRange == null
                                ? 'Rango de fechas'
                                : '${_toUiDate(_selectedDateRange!.start)} - ${_toUiDate(_selectedDateRange!.end)}',
                          ),
                        ),
                        DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: _selectedStatus ?? '',
                            borderRadius: BorderRadius.circular(10),
                            items: _statusOptions.entries
                                .map(
                                  (entry) => DropdownMenuItem<String>(
                                    value: entry.key,
                                    child: Text(entry.value),
                                  ),
                                )
                                .toList(),
                            onChanged: (value) async {
                              setState(() {
                                _selectedStatus = (value == null || value.isEmpty)
                                    ? null
                                    : value;
                              });
                              await _applyFilters();
                            },
                          ),
                        ),
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
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      '${jobProvider.allJobsTotal} ${jobProvider.allJobsTotal == 1 ? 'tarea' : 'tareas'}',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Theme.of(context).primaryColor,
                      ),
                    ),
                    if (jobProvider.allJobsTotalPages > 1)
                      Row(
                        children: [
                          Text(
                            'Página ${jobProvider.allJobsPage}/${jobProvider.allJobsTotalPages}',
                            style: const TextStyle(fontSize: 12, color: Colors.grey),
                          ),
                          IconButton(
                            icon: const Icon(Icons.chevron_left),
                            onPressed: jobProvider.hasAllJobsPreviousPage
                                ? () => jobProvider.previousAllJobsPage()
                                : null,
                          ),
                          IconButton(
                            icon: const Icon(Icons.chevron_right),
                            onPressed: jobProvider.hasAllJobsNextPage
                                ? () => jobProvider.nextAllJobsPage()
                                : null,
                          ),
                        ],
                      ),
                  ],
                ),
              ),
              Expanded(
                child: jobProvider.allJobs.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.assignment_outlined,
                                size: 72, color: Colors.grey[400]),
                            const SizedBox(height: 12),
                            Text(
                              'No hay tareas con estos filtros',
                              style: TextStyle(
                                color: Colors.grey[600],
                                fontSize: 16,
                              ),
                            ),
                          ],
                        ),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.all(8),
                        itemCount: jobProvider.allJobs.length,
                        itemBuilder: (context, index) {
                          final job = jobProvider.allJobs[index];
                          return JobCard(
                            job: job,
                            permissions: permissions,
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) => JobDetailScreen(jobId: job.id!),
                                ),
                              );
                            },
                            onRefresh: () => jobProvider.fetchAllJobs(
                              page: jobProvider.allJobsPage,
                            ),
                          );
                        },
                      ),
              ),
            ],
          ),
        );
      },
    );
  }
}
