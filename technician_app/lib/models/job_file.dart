class JobFile {
  final int id;
  final int jobsId;
  final String? fileName;
  final String? filePath;
  final String? fileType;
  final String? createdAt;
  final String? updatedAt;

  JobFile({
    required this.id,
    required this.jobsId,
    this.fileName,
    this.filePath,
    this.fileType,
    this.createdAt,
    this.updatedAt,
  });

  factory JobFile.fromJson(Map<String, dynamic> json) {
    return JobFile(
      id: json['id'] ?? 0,
      jobsId: json['jobs_id'] ?? 0,
      fileName: json['file_name'],
      filePath: json['file_path'],
      fileType: json['file_type'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'jobs_id': jobsId,
      'file_name': fileName,
      'file_path': filePath,
      'file_type': fileType,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}
