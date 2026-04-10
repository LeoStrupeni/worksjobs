class Client {
  final int id;
  final String name;
  final String? email;
  final String? phone;
  final String? cuit; // CUIT/CUIL del cliente

  Client({
    required this.id,
    required this.name,
    this.email,
    this.phone,
    this.cuit,
  });

  factory Client.fromJson(Map<String, dynamic> json) {
    // El backend puede devolver 'name' (de jobs) o 'first_name'+'last_name' (de búsqueda)
    String clientName;
    if (json['name'] != null) {
      clientName = json['name'] as String;
    } else {
      final firstName = json['first_name'] ?? '';
      final lastName = json['last_name'] ?? '';
      clientName = '$firstName $lastName'.trim();
    }
    
    // El backend puede devolver 'phone' o 'phone1'
    final clientPhone = json['phone'] ?? json['phone1'];
    
    return Client(
      id: json['id'] as int,
      name: clientName,
      email: json['email'] as String?,
      phone: clientPhone as String?,
      cuit: json['cuit'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'phone': phone,
      'cuit': cuit,
    };
  }
}
