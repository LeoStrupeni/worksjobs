import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/job_provider.dart';
import '../models/job_permissions.dart';
import '../widgets/job_card.dart';
import 'job_detail_screen.dart';

class TodayJobsScreen extends StatelessWidget {
  const TodayJobsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Consumer<JobProvider>(
        builder: (context, jobProvider, child) {
          // print('🖥️ TodayJobsScreen: isLoading=${jobProvider.isLoading}, todayJobs.length=${jobProvider.todayJobs.length}, errorMessage=${jobProvider.errorMessage}');
          
          if (jobProvider.isLoading && jobProvider.todayJobs.isEmpty) {
            // print('⏳ TodayJobsScreen: Mostrando loading...');
            return const Center(
              child: CircularProgressIndicator(),
            );
          }

          if (jobProvider.errorMessage != null) {
            print('❌ TodayJobsScreen: Mostrando error...');
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
                    'Error: ${jobProvider.errorMessage}',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Colors.red),
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () {
                      jobProvider.clearError();
                      jobProvider.fetchTodayJobs();
                    },
                    child: const Text('Reintentar'),
                  ),
                ],
              ),
            );
          }

          if (jobProvider.todayJobs.isEmpty) {
            // print('📭 TodayJobsScreen: Mostrando "No hay citas"...');
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.event_available,
                    size: 80,
                    color: Colors.grey[400],
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'No hay citas para hoy',
                    style: TextStyle(
                      fontSize: 18,
                      color: Colors.grey[600],
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    DateFormat('EEEE, d MMMM yyyy', 'es').format(DateTime.now()),
                    style: TextStyle(
                      color: Colors.grey[500],
                    ),
                  ),
                ],
              ),
            );
          }
          
          // print('✅ TodayJobsScreen: Mostrando ${jobProvider.todayJobs.length} citas');
          return RefreshIndicator(
            onRefresh: () async {
              await jobProvider.fetchTodayJobs();
            },
            child: Column(
              children: [
                // Header con fecha y contador
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  color: Theme.of(context).primaryColor.withOpacity(0.1),
                  child: Column(
                    children: [
                      Text(
                        DateFormat('EEEE, d MMMM yyyy', 'es').format(DateTime.now()),
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Theme.of(context).primaryColor,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${jobProvider.todayJobs.length} ${jobProvider.todayJobs.length == 1 ? 'cita' : 'citas'}',
                        style: const TextStyle(
                          fontSize: 14,
                          color: Colors.grey,
                        ),
                      ),
                    ],
                  ),
                ),
                
                // Lista de citas
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.all(8),
                    itemCount: jobProvider.todayJobs.length,
                    itemBuilder: (context, index) {
                      final job = jobProvider.todayJobs[index];
                      final permissions = jobProvider.permissions ?? JobPermissions(
                        create: false,
                        read: false,
                        update: false,
                        delete: false,
                        allPermissions: [],
                        roles: [],
                      );
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
                        onRefresh: () => jobProvider.fetchTodayJobs(),
                      );
                    },
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
