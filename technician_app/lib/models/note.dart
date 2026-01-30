class Note {
  final int id;
  final int jobsId;
  final String note;
  final int? userId;
  final String? userName;
  final String? userEmail;
  final String? createdAt;
  final String? updatedAt;

  Note({
    required this.id,
    required this.jobsId,
    required this.note,
    this.userId,
    this.userName,
    this.userEmail,
    this.createdAt,
    this.updatedAt,
  });

  factory Note.fromJson(Map<String, dynamic> json) {
    return Note(
      id: json['id'] ?? 0,
      jobsId: json['jobs_id'] ?? 0,
      note: json['note'] ?? '',
      userId: json['user_id'],
      userName: json['user']?['name'],
      userEmail: json['user']?['email'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'jobs_id': jobsId,
      'note': note,
      'user_id': userId,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}
