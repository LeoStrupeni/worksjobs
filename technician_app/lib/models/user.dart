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
}
