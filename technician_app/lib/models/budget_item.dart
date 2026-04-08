class BudgetItem {
  final int? id;
  final int? budgetId;
  final int? productId;
  final String codigo;
  final String descripcion;
  final String? tipoItem; // 'P' = Producto, 'S' = Servicio
  final String unitType; // 'Unidad', 'Rollo', 'Metros'
  final double quantity;
  final double unitPrice;
  final double subtotal;

  BudgetItem({
    this.id,
    this.budgetId,
    this.productId,
    required this.codigo,
    required this.descripcion,
    this.tipoItem,
    required this.unitType,
    required this.quantity,
    required this.unitPrice,
    required this.subtotal,
  });

  factory BudgetItem.fromJson(Map<String, dynamic> json) {
    return BudgetItem(
      id: json['id'],
      budgetId: json['budget_id'],
      productId: json['product_id'],
      codigo: json['codigo'] ?? '',
      descripcion: json['descripcion'] ?? '',
      tipoItem: json['tipo_item'],
      unitType: json['unit_type'] ?? 'Unidad',
      quantity: _parseDouble(json['quantity']),
      unitPrice: _parseDouble(json['unit_price']),
      subtotal: _parseDouble(json['subtotal']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (budgetId != null) 'budget_id': budgetId,
      if (productId != null) 'product_id': productId,
      'codigo': codigo,
      'descripcion': descripcion,
      if (tipoItem != null) 'tipo_item': tipoItem,
      'unit_type': unitType,
      'quantity': quantity,
      'unit_price': unitPrice,
      'subtotal': subtotal,
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
  BudgetItem copyWith({
    int? id,
    int? budgetId,
    int? productId,
    String? codigo,
    String? descripcion,
    String? tipoItem,
    String? unitType,
    double? quantity,
    double? unitPrice,
    double? subtotal,
  }) {
    return BudgetItem(
      id: id ?? this.id,
      budgetId: budgetId ?? this.budgetId,
      productId: productId ?? this.productId,
      codigo: codigo ?? this.codigo,
      descripcion: descripcion ?? this.descripcion,
      tipoItem: tipoItem ?? this.tipoItem,
      unitType: unitType ?? this.unitType,
      quantity: quantity ?? this.quantity,
      unitPrice: unitPrice ?? this.unitPrice,
      subtotal: subtotal ?? this.subtotal,
    );
  }

  // Verificar si es un servicio
  bool get isService => tipoItem == 'S';

  // Verificar si es un producto
  bool get isProduct => tipoItem == 'P';

  @override
  String toString() {
    return 'BudgetItem{id: $id, codigo: $codigo, descripcion: $descripcion, '
        'tipo: $tipoItem, quantity: $quantity, unitPrice: $unitPrice, subtotal: $subtotal}';
  }
}
