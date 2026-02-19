class ColppyCliente {
  final String idCliente;
  final String razonSocial;
  final String? nombreFantasia;
  final String? cuit;
  final bool activo;
  final Map<String, dynamic> datosAdicionales;

  ColppyCliente({
    required this.idCliente,
    required this.razonSocial,
    this.nombreFantasia,
    this.cuit,
    this.activo = true,
    this.datosAdicionales = const {},
  });

  /// Factory para crear desde JSON de Colppy
  factory ColppyCliente.fromJson(Map<String, dynamic> json) {
    return ColppyCliente(
      idCliente: json['idCliente']?.toString() ?? '',
      razonSocial: json['RazonSocial']?.toString() ?? '',
      nombreFantasia: json['NombreFantasia']?.toString(),
      cuit: json['CUIT']?.toString(),
      activo: (json['Activo'] ?? '1') == '1',
      datosAdicionales: json,
    );
  }

  /// Convertir a JSON
  Map<String, dynamic> toJson() {
    return {
      'idCliente': idCliente,
      'RazonSocial': razonSocial,
      'NombreFantasia': nombreFantasia,
      'CUIT': cuit,
      'Activo': activo ? '1' : '0',
      ...datosAdicionales,
    };
  }

  /// Obtener nombre a mostrar (Nombre fantasía o Razón Social)
  String get nombreMostrar => nombreFantasia ?? razonSocial;

  @override
  String toString() =>
      'ColppyCliente(id: $idCliente, nombre: $nombreMostrar, activo: $activo)';
}

/// Respuesta de lista de clientes
class ColppyClientesResponse {
  final List<ColppyCliente> clientes;
  final int total;
  final int start;
  final int limit;

  ColppyClientesResponse({
    required this.clientes,
    this.total = 0,
    this.start = 0,
    this.limit = 100,
  });

  /// Factory para crear desde respuesta de API
  factory ColppyClientesResponse.fromJson(dynamic json) {
    List<ColppyCliente> clientes = [];

    if (json is List) {
      clientes = json
          .map((item) => ColppyCliente.fromJson(item as Map<String, dynamic>))
          .toList();
    } else if (json is Map<String, dynamic>) {
      if (json['clientes'] is List) {
        clientes = (json['clientes'] as List)
            .map(
                (item) => ColppyCliente.fromJson(item as Map<String, dynamic>))
            .toList();
      }
    }

    return ColppyClientesResponse(
      clientes: clientes,
      total: json is Map ? json['total'] ?? json.length : 0,
      start: json is Map ? json['start'] ?? 0 : 0,
      limit: json is Map ? json['limit'] ?? 100 : 100,
    );
  }

  /// Está vacío?
  bool get isEmpty => clientes.isEmpty;

  /// Hay más resultados?
  bool get hasMasResultados => (start + clientes.length) < total;

  @override
  String toString() =>
      'ColppyClientesResponse(total: $total, clientes: ${clientes.length})';
}
