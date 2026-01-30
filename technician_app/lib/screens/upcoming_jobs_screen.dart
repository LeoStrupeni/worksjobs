import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/job_provider.dart';
import '../models/job_permissions.dart';
import '../widgets/job_card.dart';
import 'job_detail_screen.dart';

class UpcomingJobsScreen extends StatelessWidget {
  const UpcomingJobsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<JobProvider>(
      builder: (context, jobProvider, child) {
        if (jobProvider.isLoading && jobProvider.upcomingJobs.isEmpty) {
          return const Center(
            child: CircularProgressIndicator(),
          );
        }

        if (jobProvider.errorMessage != null) {
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
                    jobProvider.fetchUpcomingJobs();
                  },
                  child: const Text('Reintentar'),
                ),
              ],
            ),
          );
        }

        if (jobProvider.upcomingJobs.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.event_busy,
                  size: 80,
                  color: Colors.grey[400],
                ),
                const SizedBox(height: 16),
                Text(
                  'No hay próximas citas',
                  style: TextStyle(
                    fontSize: 18,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: () async {
            await jobProvider.fetchUpcomingJobs();
          },
          child: Column(
            children: [
              // Header con contador
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                color: Theme.of(context).primaryColor.withOpacity(0.1),
                child: Text(
                  '${jobProvider.upcomingJobs.length} ${jobProvider.upcomingJobs.length == 1 ? 'cita programada' : 'citas programadas'}',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).primaryColor,
                  ),
                ),
              ),
              
              // Lista de citas
              Expanded(
                child: ListView.builder(
                  padding: const EdgeInsets.all(8),
                  itemCount: jobProvider.upcomingJobs.length,
                  itemBuilder: (context, index) {
                    final job = jobProvider.upcomingJobs[index];
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
                            builder: (context) => JobDetailScreen(jobId: job.id),
                          ),
                        );
                      },
                      onRefresh: () => jobProvider.fetchUpcomingJobs(),
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
