import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../providers/job_provider.dart';
import '../utils/debug_logger.dart';
import 'today_jobs_screen.dart';
import 'upcoming_jobs_screen.dart';
import 'calendar_screen.dart';
import 'all_jobs_screen.dart';
import 'create_job_screen.dart';
import 'budgets_list_screen.dart';
import 'debug_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _selectedIndex = 0;
  
  // Para el gesto de debug
  int _debugTapCount = 0;
  DateTime? _lastDebugTap;

  List<Widget> _buildScreens({required bool canReadJobs}) {
    final screens = <Widget>[
      const TodayJobsScreen(),
      const UpcomingJobsScreen(),
      const CalendarScreen(),
    ];

    if (canReadJobs) {
      screens.add(const AllJobsScreen());
    }

    return screens;
  }

  List<BottomNavigationBarItem> _buildBottomItems({required bool canReadJobs}) {
    final items = <BottomNavigationBarItem>[
      const BottomNavigationBarItem(
        icon: Icon(Icons.today),
        label: 'Hoy',
      ),
      const BottomNavigationBarItem(
        icon: Icon(Icons.upcoming),
        label: 'Próximas',
      ),
      const BottomNavigationBarItem(
        icon: Icon(Icons.calendar_month),
        label: 'Calendario',
      ),
    ];

    if (canReadJobs) {
      items.add(
        const BottomNavigationBarItem(
          icon: Icon(Icons.list_alt),
          label: 'Todas',
        ),
      );
    }

    return items;
  }

  @override
  void initState() {
    super.initState();
    // Inicializar el logger
    DebugLogger.instance.initialize();
    
    // Cargar citas del día al iniciar
    Future.microtask(() {
      context.read<JobProvider>().fetchTodayJobs();
      context.read<JobProvider>().fetchUpcomingJobs();
    });
  }

  void _onItemTapped(int index) {
    setState(() {
      _selectedIndex = index;
    });
  }

  Future<void> _navigateToCreateJob() async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => const CreateJobScreen(),
      ),
    );

    // Si se creó una tarea, recargar según la pestaña activa
    if (result == true && mounted) {
      final jobProvider = context.read<JobProvider>();
      final canReadJobs = context.read<AuthProvider>().user?.canReadJobs ?? false;
      final screens = _buildScreens(canReadJobs: canReadJobs);
      final currentIndex = _selectedIndex >= screens.length ? 0 : _selectedIndex;
      if (currentIndex == 0) {
        jobProvider.fetchTodayJobs();
      } else if (currentIndex == 1) {
        jobProvider.fetchUpcomingJobs();
      } else if (canReadJobs && currentIndex == 3) {
        jobProvider.fetchAllJobs(page: 1);
      }
    }
  }

  Future<void> _handleLogout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cerrar Sesión'),
        content: const Text('¿Estás seguro que deseas cerrar sesión?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text(
              'Cerrar Sesión',
              style: TextStyle(color: Colors.red),
            ),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      await context.read<AuthProvider>().logout();
    }
  }

  /// Gesto secreto de debug: tap 5 veces en 3 segundos en el logo
  void _handleDebugTap() {
    final now = DateTime.now();
    
    // Si pasaron más de 3 segundos desde el último tap, resetear contador
    if (_lastDebugTap != null && now.difference(_lastDebugTap!).inSeconds > 3) {
      _debugTapCount = 0;
    }
    
    _debugTapCount++;
    _lastDebugTap = now;
    
    // Feedback visual
    ScaffoldMessenger.of(context).clearSnackBars();
    
    if (_debugTapCount >= 5) {
      // ¡Abrir debug screen!
      DebugLogger.instance.info('🛠️ Debug screen abierta por gesto secreto');
      
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => const DebugScreen(),
        ),
      );
      
      _debugTapCount = 0; // Resetear
    } else {
      // Mostrar progreso
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Debug: Tap $_debugTapCount/5'),
          duration: const Duration(milliseconds: 500),
          backgroundColor: Colors.deepPurple,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = context.watch<AuthProvider>();
    final canReadJobs = authProvider.user?.canReadJobs ?? false;
    final screens = _buildScreens(canReadJobs: canReadJobs);
    final bottomItems = _buildBottomItems(canReadJobs: canReadJobs);
    final currentIndex = _selectedIndex >= screens.length ? 0 : _selectedIndex;

    return GestureDetector(
      // Resetear timer de inactividad con cualquier interacción
      onTap: () => authProvider.resetInactivityTimer(),
      onPanDown: (_) => authProvider.resetInactivityTimer(),
      child: Scaffold(
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
          // Logo con gesto secreto de debug (5 taps para abrir pantalla de logs)
          // START_DEBUG_FEATURE
          leading: GestureDetector(
            onTap: _handleDebugTap,
            child: const Padding(
              padding: EdgeInsets.all(8.0),
              child: Icon(
                Icons.electrical_services,
                color: Colors.white,
                size: 32,
              ),
            ),
          ),
          // END_DEBUG_FEATURE
          title: Text(
            _getTitleForIndex(currentIndex, canReadJobs: canReadJobs),
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
            ),
          ),
          iconTheme: const IconThemeData(color: Colors.white),
          systemOverlayStyle: SystemUiOverlayStyle.light,
          actions: [
            // Botón crear tarea - Solo si tiene permiso
            if (authProvider.user?.permissions.contains('create jobs') ?? false)
              IconButton(
                icon: const Icon(Icons.add_circle_outline, color: Colors.white),
                tooltip: 'Nueva Tarea',
                onPressed: _navigateToCreateJob,
              ),
            IconButton(
              icon: const Icon(Icons.refresh, color: Colors.white),
              onPressed: () {
                final jobProvider = context.read<JobProvider>();
                if (currentIndex == 0) {
                  jobProvider.fetchTodayJobs();
                } else if (currentIndex == 1) {
                  jobProvider.fetchUpcomingJobs();
                } else if (currentIndex == 2) {
                  final now = DateTime.now();
                  final start = DateTime(now.year, now.month, 1);
                  final end = DateTime(now.year, now.month + 1, 0);
                  jobProvider.fetchJobsByDateRange(
                    '${start.year.toString().padLeft(4, '0')}-${start.month.toString().padLeft(2, '0')}-${start.day.toString().padLeft(2, '0')}',
                    '${end.year.toString().padLeft(4, '0')}-${end.month.toString().padLeft(2, '0')}-${end.day.toString().padLeft(2, '0')}',
                  );
                } else if (canReadJobs && currentIndex == 3) {
                  jobProvider.fetchAllJobs(page: 1);
                }
              },
            ),
            PopupMenuButton<String>(
              onSelected: (value) {
                if (value == 'logout') {
                  _handleLogout();
                } else if (value == 'budgets') {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => const BudgetsListScreen(),
                    ),
                  );
                }
              },
              itemBuilder: (context) => [
                PopupMenuItem(
                  value: 'profile',
                  child: Row(
                    children: [
                      const Icon(Icons.person_outline),
                      const SizedBox(width: 8),
                      Text(authProvider.user?.name ?? 'Usuario'),
                    ],
                  ),
                ),
                // Opción de Presupuestos - Solo si tiene permiso
                if (authProvider.user?.canReadBudgets ?? false)
                  const PopupMenuItem(
                    value: 'budgets',
                    child: Row(
                      children: [
                        Icon(Icons.description_outlined),
                        SizedBox(width: 8),
                        Text('Presupuestos'),
                      ],
                    ),
                  ),
                const PopupMenuDivider(),
                const PopupMenuItem(
                  value: 'logout',
                  child: Row(
                    children: [
                      Icon(Icons.logout, color: Colors.red),
                      SizedBox(width: 8),
                      Text('Cerrar Sesión', style: TextStyle(color: Colors.red)),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
        body: screens[currentIndex],
        bottomNavigationBar: BottomNavigationBar(
          currentIndex: currentIndex,
          onTap: _onItemTapped,
          selectedItemColor: const Color(0xFF00274E),
          unselectedItemColor: Colors.grey,
          items: bottomItems,
        ),
      ),
    );
  }

  String _getTitleForIndex(int index, {required bool canReadJobs}) {
    switch (index) {
      case 0:
        return 'Citas de Hoy o Abiertas';
      case 1:
        return 'Próximas Citas';
      case 2:
        return 'Calendario';
      case 3:
        return canReadJobs ? 'Todas las Tareas' : 'Técnicos';
      default:
        return 'Técnicos';
    }
  }
}
