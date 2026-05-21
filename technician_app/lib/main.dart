import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:provider/provider.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'flavor_config.dart';
import 'providers/auth_provider.dart';
import 'providers/job_provider.dart';
import 'providers/budget_provider.dart';
import 'providers/theme_provider.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() async {
  await runTechnicianApp(flavorConfigProd);
}

Future<void> runTechnicianApp(FlavorConfig flavorConfig) async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('es', null);
  runApp(TechnicianApp(flavorConfig: flavorConfig));
}

class TechnicianApp extends StatelessWidget {
  const TechnicianApp({super.key, required this.flavorConfig});

  final FlavorConfig flavorConfig;

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => ThemeProvider()..loadTheme()),
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => JobProvider()),
        ChangeNotifierProvider(create: (_) => BudgetProvider()),
      ],
      child: Consumer<ThemeProvider>(
        builder: (context, themeProvider, child) {
          return MaterialApp(
            title: 'Técnicos - Strupeni',
            debugShowCheckedModeBanner: false,
            localizationsDelegates: const [
              GlobalMaterialLocalizations.delegate,
              GlobalWidgetsLocalizations.delegate,
              GlobalCupertinoLocalizations.delegate,
            ],
            supportedLocales: const [
              Locale('es', 'ES'),
            ],
            // Tema dinámico desde CMS
            theme: themeProvider.getThemeData(),
            home: AuthWrapper(flavorConfig: flavorConfig),
          );
        },
      ),
    );
  }
}

class AuthWrapper extends StatefulWidget {
  const AuthWrapper({super.key, required this.flavorConfig});

  final FlavorConfig flavorConfig;

  @override
  State<AuthWrapper> createState() => _AuthWrapperState();
}

class _AuthWrapperState extends State<AuthWrapper> {
  Duration get _pendingUploadsRetryInterval =>
      Duration(seconds: widget.flavorConfig.pendingUploadsRetrySeconds);

  int get _pendingUploadsMaxBatch => widget.flavorConfig.pendingUploadsMaxBatch;

  Timer? _pendingUploadsTimer;

  @override
  void initState() {
    super.initState();
    // Verificar si hay sesión guardada
    context.read<AuthProvider>().initialize();
    _startPendingUploadsBackgroundRetry();
  }

  void _startPendingUploadsBackgroundRetry() {
    _pendingUploadsTimer?.cancel();
    _pendingUploadsTimer = Timer.periodic(_pendingUploadsRetryInterval, (_) {
      _processPendingUploadsTick();
    });

    _processPendingUploadsTick();
  }

  void _processPendingUploadsTick() {
    if (!mounted) {
      return;
    }

    final authProvider = context.read<AuthProvider>();
    if (!authProvider.isAuthenticated) {
      return;
    }

    final jobProvider = context.read<JobProvider>();
    unawaited(
      jobProvider.processPendingUploadsInBackground(
        maxBatch: _pendingUploadsMaxBatch,
      ),
    );
  }

  @override
  void dispose() {
    _pendingUploadsTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<AuthProvider>(
      builder: (context, authProvider, child) {
        if (authProvider.isLoading) {
          return const Scaffold(
            body: Center(
              child: CircularProgressIndicator(),
            ),
          );
        }

        if (authProvider.isAuthenticated) {
          return const HomeScreen();
        }

        return const LoginScreen();
      },
    );
  }
}
