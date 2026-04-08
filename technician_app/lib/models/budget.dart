import 'budget_item.dart';

class Budget {
  final int? id;
  final String? idFactura; // ID de Colppy
  final String nroFactura; // Ej: "0002-00000044"
  final int? clientId;
  final String? clientName;
  final String? clientCuit;
  final String fecha; // Fecha del presupuesto
  final double total;
  final String? observaciones;
  final int? createdBy;
  final String? createdByName;
  final String? createdAt;
  final String? updatedAt;
  final List<BudgetItem> items;

  Budget({
    this.id,
    this.idFactura,
    required this.nroFactura,
    this.clientId,
    this.clientName,
    this.clientCuit,
    required this.fecha,
    required this.total,
    this.observaciones,
    this.createdBy,
    this.createdByName,
    this.createdAt,
    this.updatedAt,
    this.items = const [],
  });

  factory Budget.fromJson(Map<String, dynamic> json) {
    // Parsear items si existen
    List<BudgetItem> itemsList = [];
    if (json['items'] != null && json['items'] is List) {
      itemsList = (json['items'] as List)
          .map((item) => BudgetItem.fromJson(item))
          .toList();
    }

    return Budget(
      id: json['id'],
      idFactura: json['id_factura'],
      nroFactura: json['nro_factura'] ?? '',
      clientId: json['client_id'],
      clientName: json['client_name'],
      clientCuit: json['client_cuit'],
      fecha: json['fecha'] ?? '',
      total: _parseDouble(json['total']),
      observaciones: json['observaciones'],
      createdBy: json['created_by'],
      createdByName: json['created_by_name'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
      items: itemsList,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (idFactura != null) 'id_factura': idFactura,
      'nro_factura': nroFactura,
      if (clientId != null) 'client_id': clientId,
      if (clientName != null) 'client_name': clientName,
      if (clientCuit != null) 'client_cuit': clientCuit,
      'fecha': fecha,
      'total': total,
      if (observaciones != null) 'observaciones': observaciones,
      if (createdBy != null) 'created_by': createdBy,
      'items': items.map((item) => item.toJson()).toList(),
    };
  }

  // Helper para parsear doubles de manera segura
  static double _parseDouble(dynamic value) {
    if (value == null) return 0.0;
    if (value is double) return value;
    if (value is int) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }

  // Crear copia con modificaciones
  Budget copyWith({
    int? id,
    String? idFactura,
    String? nroFactura,
    int? clientId,
    String? clientName,
    String? clientCuit,
    String? fecha,
    double? total,
    String? observaciones,
    int? createdBy,
    String? createdByName,
    String? createdAt,
    String? updatedAt,
    List<BudgetItem>? items,
  }) {
    return Budget(
      id: id ?? this.id,
      idFactura: idFactura ?? this.idFactura,
      nroFactura: nroFactura ?? this.nroFactura,
      clientId: clientId ?? this.clientId,
      clientName: clientName ?? this.clientName,
      clientCuit: clientCuit ?? this.clientCuit,
      fecha: fecha ?? this.fecha,
      total: total ?? this.total,
      observaciones: observaciones ?? this.observaciones,
      createdBy: createdBy ?? this.createdBy,
      createdByName: createdByName ?? this.createdByName,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      items: items ?? this.items,
    );
  }

  // Calcular total sumando items
  double calculateTotal() {
    return items.fold(0.0, (sum, item) => sum + item.subtotal);
  }

  // Verificar si tiene items
  bool get hasItems => items.isNotEmpty;

  // Cantidad de items
  int get itemCount => items.length;

  @override
  String toString() {
    return 'Budget{id: $id, nroFactura: $nroFactura, clientName: $clientName, '
        'fecha: $fecha, total: $total, itemCount: ${items.length}}';
  }
}
