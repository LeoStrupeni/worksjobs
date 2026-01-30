class JobPermissions {
  final bool create;
  final bool read;
  final bool update;
  final bool delete;
  final List<String> allPermissions;
  final List<String> roles;

  JobPermissions({
    required this.create,
    required this.read,
    required this.update,
    required this.delete,
    required this.allPermissions,
    required this.roles,
  });

  factory JobPermissions.fromJson(Map<String, dynamic> json) {
    return JobPermissions(
      create: json['create'] ?? false,
      read: json['read'] ?? false,
      update: json['update'] ?? false,
      delete: json['delete'] ?? false,
      allPermissions: json['all_permissions'] != null
          ? List<String>.from(json['all_permissions'])
          : [],
      roles: json['roles'] != null ? List<String>.from(json['roles']) : [],
    );
  }

  bool get canMarkArrival => update;
  bool get canCloseJob => update;
  bool get canAddNote => update;
  bool get canViewFiles => read;
  bool get canUploadFiles => create || update;
}
