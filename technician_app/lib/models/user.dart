class User {
  final int id;
  final String name;
  final String email;
  final String? imagen;
  final List<String> roles;
  final List<String> permissions;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.imagen,
    required this.roles,
    required this.permissions,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      imagen: json['imagen'],
      roles: json['roles'] != null 
        ? List<String>.from(json['roles']) 
        : [],
      permissions: json['permissions'] != null 
        ? List<String>.from(json['permissions']) 
        : [],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'imagen': imagen,
      'roles': roles,
      'permissions': permissions,
    };
  }

  // Helper methods para verificar permisos específicos
  bool get canShare => permissions.contains('create share');
  bool get canGeneratePDF => permissions.contains('create pdf');
  
  // Permisos de presupuestos
  bool get canCreateBudgets => permissions.contains('create budgets');
  bool get canReadBudgets => permissions.contains('read budgets');
  bool get canCreateClients => permissions.contains('create clients');
  
  // Permisos de tareas (jobs)
  bool get canCreateJobs => permissions.contains('create jobs');
  bool get canReadJobs => permissions.contains('read jobs');
  bool get canUpdateJobs => permissions.contains('update jobs');
  bool get canDeleteJobs => permissions.contains('delete jobs');
}
