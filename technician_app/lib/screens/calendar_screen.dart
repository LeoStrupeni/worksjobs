import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:table_calendar/table_calendar.dart';
import 'package:intl/intl.dart';
import '../providers/job_provider.dart';
import '../models/job.dart';
import '../models/job_permissions.dart';
import 'job_detail_screen.dart';
import '../widgets/job_card.dart';

class CalendarScreen extends StatefulWidget {
  const CalendarScreen({super.key});

  @override
  State<CalendarScreen> createState() => _CalendarScreenState();
}

class _CalendarScreenState extends State<CalendarScreen> {
  CalendarFormat _calendarFormat = CalendarFormat.month;
  DateTime _focusedDay = DateTime.now();
  DateTime? _selectedDay;

  @override
  void initState() {
    super.initState();
    _selectedDay = _focusedDay;
    _loadJobsForMonth();
  }

  void _loadJobsForMonth() {
    final firstDay = DateTime(_focusedDay.year, _focusedDay.month, 1);
    final lastDay = DateTime(_focusedDay.year, _focusedDay.month + 1, 0);
    
    final startDate = DateFormat('yyyy-MM-dd').format(firstDay);
    final endDate = DateFormat('yyyy-MM-dd').format(lastDay);
    
    context.read<JobProvider>().fetchJobsByDateRange(startDate, endDate);
  }

  List<Job> _getJobsForDay(DateTime day, List<Job> calendarJobs) {
    return calendarJobs.where((job) {
      if (job.visitDatetime == null) return false;
      try {
        final jobDate = DateTime.parse(job.visitDatetime!);
        return isSameDay(jobDate, day);
      } catch (e) {
        return false;
      }
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<JobProvider>(
      builder: (context, jobProvider, child) {
        final selectedDayJobs = _selectedDay != null 
            ? _getJobsForDay(_selectedDay!, jobProvider.calendarJobs) 
            : <Job>[];

        return Column(
          children: [
            // Calendario
            TableCalendar(
              firstDay: DateTime.utc(2020, 1, 1),
              lastDay: DateTime.utc(2030, 12, 31),
              focusedDay: _focusedDay,
              calendarFormat: _calendarFormat,
              locale: 'es',
              availableCalendarFormats: const {
                CalendarFormat.month: 'Mes',
                CalendarFormat.twoWeeks: '2 Semanas',
                CalendarFormat.week: 'Semana',
              },
              selectedDayPredicate: (day) => isSameDay(_selectedDay, day),
              onDaySelected: (selectedDay, focusedDay) {
                setState(() {
                  _selectedDay = selectedDay;
                  _focusedDay = focusedDay;
                });
              },
              onFormatChanged: (format) {
                setState(() {
                  _calendarFormat = format;
                });
              },
              onPageChanged: (focusedDay) {
                _focusedDay = focusedDay;
                _loadJobsForMonth();
              },
              eventLoader: (day) => _getJobsForDay(day, jobProvider.calendarJobs),
              calendarStyle: CalendarStyle(
                todayDecoration: BoxDecoration(
                  color: Theme.of(context).primaryColor.withOpacity(0.3),
                  shape: BoxShape.circle,
                ),
                selectedDecoration: BoxDecoration(
                  color: Theme.of(context).primaryColor,
                  shape: BoxShape.circle,
                ),
                markerDecoration: BoxDecoration(
                  color: Colors.orange,
                  shape: BoxShape.circle,
                ),
              ),
              headerStyle: HeaderStyle(
                formatButtonVisible: true,
                titleCentered: true,
                formatButtonShowsNext: false,
                formatButtonDecoration: BoxDecoration(
                  color: Theme.of(context).primaryColor,
                  borderRadius: BorderRadius.circular(8),
                ),
                formatButtonTextStyle: const TextStyle(
                  color: Colors.white,
                ),
              ),
            ),

            const Divider(height: 1),

            // Lista de citas del día seleccionado
            Expanded(
              child: jobProvider.isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : selectedDayJobs.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.event_note,
                                size: 64,
                                color: Colors.grey[400],
                              ),
                              const SizedBox(height: 16),
                              Text(
                                'No hay citas para este día',
                                style: TextStyle(
                                  fontSize: 16,
                                  color: Colors.grey[600],
                                ),
                              ),
                              const SizedBox(height: 8),
                              if (_selectedDay != null)
                                Text(
                                  DateFormat('EEEE, d MMMM yyyy', 'es')
                                      .format(_selectedDay!),
                                  style: TextStyle(
                                    color: Colors.grey[500],
                                  ),
                                ),
                            ],
                          ),
                        )
                      : Column(
                          children: [
                            // Header con fecha seleccionada
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(12),
                              color: Theme.of(context).primaryColor.withOpacity(0.1),
                              child: Column(
                                children: [
                                  if (_selectedDay != null)
                                    Text(
                                      DateFormat('EEEE, d MMMM yyyy', 'es')
                                          .format(_selectedDay!),
                                      style: TextStyle(
                                        fontSize: 14,
                                        fontWeight: FontWeight.bold,
                                        color: Theme.of(context).primaryColor,
                                      ),
                                    ),
                                  const SizedBox(height: 4),
                                  Text(
                                    '${selectedDayJobs.length} ${selectedDayJobs.length == 1 ? 'cita' : 'citas'}',
                                    style: const TextStyle(
                                      fontSize: 12,
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
                                itemCount: selectedDayJobs.length,
                                itemBuilder: (context, index) {
                                  final job = selectedDayJobs[index];
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
                                          builder: (context) =>
                                              JobDetailScreen(jobId: job.id!),
                                        ),
                                      );
                                    },
                                  );
                                },
                              ),
                            ),
                          ],
                        ),
            ),
          ],
        );
      },
    );
  }
}
