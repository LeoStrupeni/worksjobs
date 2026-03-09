import 'package:flutter/material.dart';
import '../models/job.dart';
import '../models/job_file.dart';
import 'package:intl/intl.dart';

class PdfConfigScreen extends StatefulWidget {
  final Job job;
  final List<dynamic> notes;
  final List<JobFile> files;

  const PdfConfigScreen({
    super.key,
    required this.job,
    required this.notes,
    required this.files,
  });

  @override
  State<PdfConfigScreen> createState() => _PdfConfigScreenState();
}

class _PdfConfigScreenState extends State<PdfConfigScreen> {
  // Configuración general
  bool _includeDescription = true;
  bool _includeArrivalTime = true;
  bool _includeDepartureTime = true;
  bool _includeClosingComments = false;
  bool _includeProducts = true;
  bool _includeTechnicians = true;

  // Notas seleccionadas
  bool _includeNotes = true;
  Set<int> _selectedNoteIds = {};

  // Imágenes seleccionadas
  bool _includeImages = true;
  Set<int> _selectedImageIds = {};

  @override
  void initState() {
    super.initState();
    // Por defecto, seleccionar todas las notas e imágenes
    _selectedNoteIds = Set<int>.from(widget.notes.map((note) => note.id as int));
    _selectedImageIds = Set<int>.from(widget.files.map((file) => file.id));
    
    // Solo incluir comentarios de cierre si existen
    _includeClosingComments = widget.job.closedJobObservation != null && 
                               widget.job.closedJobObservation!.isNotEmpty;
  }

  void _toggleAllNotes(bool? value) {
    setState(() {
      if (value == true) {
        _selectedNoteIds = Set<int>.from(widget.notes.map((note) => note.id as int));
      } else {
        _selectedNoteIds.clear();
      }
    });
  }

  void _toggleAllImages(bool? value) {
    setState(() {
      if (value == true) {
        _selectedImageIds = Set<int>.from(widget.files.map((file) => file.id));
      } else {
        _selectedImageIds.clear();
      }
    });
  }

  Map<String, dynamic> _getPdfConfig() {
    return {
      'include_description': _includeDescription,
      'include_notes': _includeNotes,
      'note_ids': _selectedNoteIds.toList(),
      'include_arrival_time': _includeArrivalTime,
      'include_departure_time': _includeDepartureTime,
      'include_closing_comments': _includeClosingComments,
      'include_images': _includeImages,
      'image_ids': _selectedImageIds.toList(),
      'include_products': _includeProducts,
      'include_technicians': _includeTechnicians,
    };
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Configurar PDF',
          style: TextStyle(color: Colors.white),
        ),
        backgroundColor: const Color(0xFF00274E),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Información general
            _buildSectionCard(
              title: 'Información General',
              icon: Icons.info_outline,
              color: Colors.blue,
              children: [
                _buildSwitchTile(
                  title: 'Incluir descripción del trabajo',
                  value: _includeDescription,
                  onChanged: (value) {
                    setState(() => _includeDescription = value);
                  },
                ),
                if (widget.job.products != null && widget.job.products!.isNotEmpty)
                  _buildSwitchTile(
                    title: 'Incluir productos relacionados',
                    subtitle: '${widget.job.products!.length} producto(s)',
                    value: _includeProducts,
                    onChanged: (value) {
                      setState(() => _includeProducts = value);
                    },
                  ),
                if (widget.job.technicians != null && widget.job.technicians!.isNotEmpty)
                  _buildSwitchTile(
                    title: 'Incluir técnicos asignados',
                    subtitle: '${widget.job.technicians!.length} técnico(s)',
                    value: _includeTechnicians,
                    onChanged: (value) {
                      setState(() => _includeTechnicians = value);
                    },
                  ),
              ],
            ),

            const SizedBox(height: 16),

            // Registro de tiempos
            _buildSectionCard(
              title: 'Registro de Tiempos',
              icon: Icons.access_time,
              color: Colors.green,
              children: [
                _buildSwitchTile(
                  title: 'Fecha y hora de llegada',
                  subtitle: widget.job.arrivalDatetime != null
                      ? DateFormat('dd/MM/yyyy HH:mm').format(DateTime.parse(widget.job.arrivalDatetime!))
                      : 'No registrado',
                  value: _includeArrivalTime,
                  onChanged: (value) {
                    setState(() => _includeArrivalTime = value);
                  },
                ),
                _buildSwitchTile(
                  title: 'Fecha y hora de salida',
                  subtitle: widget.job.closedDatetime != null
                      ? DateFormat('dd/MM/yyyy HH:mm').format(DateTime.parse(widget.job.closedDatetime!))
                      : 'No registrado',
                  value: _includeDepartureTime,
                  onChanged: (value) {
                    setState(() => _includeDepartureTime = value);
                  },
                ),
              ],
            ),

            const SizedBox(height: 16),

            // Comentarios de cierre
            if (widget.job.closedJobObservation != null && 
                widget.job.closedJobObservation!.isNotEmpty)
              _buildSectionCard(
                title: 'Observaciones',
                icon: Icons.comment,
                color: Colors.orange,
                children: [
                  _buildSwitchTile(
                    title: 'Incluir comentarios de cierre',
                    value: _includeClosingComments,
                    onChanged: (value) {
                      setState(() => _includeClosingComments = value);
                    },
                  ),
                ],
              ),

            if (widget.job.closedJobObservation != null && 
                widget.job.closedJobObservation!.isNotEmpty)
              const SizedBox(height: 16),

            // Notas
            if (widget.notes.isNotEmpty)
              _buildSectionCard(
                title: 'Notas',
                icon: Icons.note,
                color: Colors.purple,
                children: [
                  _buildSwitchTile(
                    title: 'Incluir notas',
                    subtitle: '${widget.notes.length} nota(s) disponible(s)',
                    value: _includeNotes,
                    onChanged: (value) {
                      setState(() {
                        _includeNotes = value;
                        if (!value) {
                          _selectedNoteIds.clear();
                        } else {
                          _selectedNoteIds = Set<int>.from(
                            widget.notes.map((note) => note.id as int)
                          );
                        }
                      });
                    },
                  ),
                  if (_includeNotes) ...[
                    const Divider(),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Seleccionar notas:',
                            style: TextStyle(
                              fontWeight: FontWeight.w500,
                              color: Colors.grey[700],
                            ),
                          ),
                          TextButton(
                            onPressed: () {
                              _toggleAllNotes(
                                _selectedNoteIds.length != widget.notes.length
                              );
                            },
                            child: Text(
                              _selectedNoteIds.length == widget.notes.length
                                  ? 'Deseleccionar todas'
                                  : 'Seleccionar todas',
                            ),
                          ),
                        ],
                      ),
                    ),
                    ...widget.notes.map((note) {
                      final noteId = note.id as int;
                      final isSelected = _selectedNoteIds.contains(noteId);
                      final createdAt = note.created_at != null
                          ? DateFormat('dd/MM/yyyy HH:mm').format(
                              DateTime.parse(note.created_at)
                            )
                          : 'Sin fecha';

                      return CheckboxListTile(
                        dense: true,
                        title: Text(
                          note.note ?? '',
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontSize: 14),
                        ),
                        subtitle: Text(
                          createdAt,
                          style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                        ),
                        value: isSelected,
                        onChanged: (value) {
                          setState(() {
                            if (value == true) {
                              _selectedNoteIds.add(noteId);
                            } else {
                              _selectedNoteIds.remove(noteId);
                            }
                          });
                        },
                      );
                    }).toList(),
                  ],
                ],
              ),

            if (widget.notes.isNotEmpty)
              const SizedBox(height: 16),

            // Imágenes
            if (widget.files.isNotEmpty)
              _buildSectionCard(
                title: 'Imágenes',
                icon: Icons.image,
                color: Colors.red,
                children: [
                  _buildSwitchTile(
                    title: 'Incluir imágenes',
                    subtitle: '${widget.files.length} imagen(es) disponible(s)',
                    value: _includeImages,
                    onChanged: (value) {
                      setState(() {
                        _includeImages = value;
                        if (!value) {
                          _selectedImageIds.clear();
                        } else {
                          _selectedImageIds = Set<int>.from(
                            widget.files.map((file) => file.id)
                          );
                        }
                      });
                    },
                  ),
                  if (_includeImages) ...[
                    const Divider(),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Seleccionar imágenes:',
                            style: TextStyle(
                              fontWeight: FontWeight.w500,
                              color: Colors.grey[700],
                            ),
                          ),
                          TextButton(
                            onPressed: () {
                              _toggleAllImages(
                                _selectedImageIds.length != widget.files.length
                              );
                            },
                            child: Text(
                              _selectedImageIds.length == widget.files.length
                                  ? 'Deseleccionar todas'
                                  : 'Seleccionar todas',
                            ),
                          ),
                        ],
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.all(8.0),
                      child: GridView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 3,
                          crossAxisSpacing: 8,
                          mainAxisSpacing: 8,
                        ),
                        itemCount: widget.files.length,
                        itemBuilder: (context, index) {
                          final file = widget.files[index];
                          final isSelected = _selectedImageIds.contains(file.id);
                          final imageUrl = 'https://tecnicos.strupeni.com.ar/storage/${file.name}';

                          return GestureDetector(
                            onTap: () {
                              setState(() {
                                if (isSelected) {
                                  _selectedImageIds.remove(file.id);
                                } else {
                                  _selectedImageIds.add(file.id);
                                }
                              });
                            },
                            child: Stack(
                              children: [
                                Container(
                                  decoration: BoxDecoration(
                                    border: Border.all(
                                      color: isSelected
                                          ? const Color(0xFF00274E)
                                          : Colors.grey[300]!,
                                      width: isSelected ? 3 : 1,
                                    ),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(8),
                                    child: Image.network(
                                      imageUrl,
                                      fit: BoxFit.cover,
                                      width: double.infinity,
                                      height: double.infinity,
                                    ),
                                  ),
                                ),
                                if (isSelected)
                                  Positioned(
                                    top: 4,
                                    right: 4,
                                    child: Container(
                                      padding: const EdgeInsets.all(4),
                                      decoration: const BoxDecoration(
                                        color: Color(0xFF00274E),
                                        shape: BoxShape.circle,
                                      ),
                                      child: const Icon(
                                        Icons.check,
                                        color: Colors.white,
                                        size: 16,
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                          );
                        },
                      ),
                    ),
                  ],
                ],
              ),

            const SizedBox(height: 80), // Espacio para el botón flotante
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          Navigator.pop(context, _getPdfConfig());
        },
        backgroundColor: const Color(0xFF00274E),
        icon: const Icon(Icons.picture_as_pdf, color: Colors.white),
        label: const Text(
          'Generar PDF',
          style: TextStyle(color: Colors.white),
        ),
      ),
    );
  }

  Widget _buildSectionCard({
    required String title,
    required IconData icon,
    required Color color,
    required List<Widget> children,
  }) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(12),
                topRight: Radius.circular(12),
              ),
            ),
            child: Row(
              children: [
                Icon(icon, color: color, size: 24),
                const SizedBox(width: 12),
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ],
            ),
          ),
          ...children,
        ],
      ),
    );
  }

  Widget _buildSwitchTile({
    required String title,
    String? subtitle,
    required bool value,
    required Function(bool) onChanged,
  }) {
    return SwitchListTile(
      title: Text(
        title,
        style: const TextStyle(fontSize: 14),
      ),
      subtitle: subtitle != null
          ? Text(
              subtitle,
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            )
          : null,
      value: value,
      onChanged: onChanged,
      activeColor: const Color(0xFF00274E),
    );
  }
}
