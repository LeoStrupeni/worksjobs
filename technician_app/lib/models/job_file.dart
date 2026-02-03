class JobFile {
  final int id;
  final int jobId;
  final String name; // Nombre guardado en servidor
  final String? originalName; // Nombre original del archivo
  final String? originalExtension;
  final String? createdAt;
  final String? updatedAt;

  JobFile({
    required this.id,
    required this.jobId,
    required this.name,
    this.originalName,
    this.originalExtension,
    this.createdAt,
    this.updatedAt,
  });

  factory JobFile.fromJson(Map<String, dynamic> json) {
    return JobFile(
      id: json['id'] ?? 0,
      jobId: json['job_id'] ?? json['jobs_id'] ?? 0,
      name: json['name'] ?? json['file_name'] ?? '',
      originalName: json['original_name'] ?? json['file_name'],
      originalExtension: json['original_extension'] ?? json['file_type'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'job_id': jobId,
      'name': name,
      'original_name': originalName,
      'original_extension': originalExtension,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}
