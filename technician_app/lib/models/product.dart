class Product {
  final int id;
  final String codigo;
  final String descripcion;
  final bool isFromColppy;

  Product({
    required this.id,
    required this.codigo,
    required this.descripcion,
    required this.isFromColppy,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      codigo: json['codigo'] ?? '',
      descripcion: json['descripcion'] ?? '',
      isFromColppy: json['is_from_colppy'] == 1 || 
                    json['is_from_colppy'] == '1' || 
                    json['is_from_colppy'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'codigo': codigo,
      'descripcion': descripcion,
      'is_from_colppy': isFromColppy ? 1 : 0,
    };
  }

  String get displayName => '$codigo - $descripcion';
}

// Clase para productos seleccionados con cantidad y tipo
class SelectedProduct {
  final Product product;
  String unitType; // 'Unidad', 'Rollo', 'Metros'
  double quantity;
  final String uniqueId; // Para manejar duplicados

  SelectedProduct({
    required this.product,
    this.unitType = 'Unidad',
    this.quantity = 1.0,
    String? uniqueId,
  }) : uniqueId = uniqueId ?? DateTime.now().millisecondsSinceEpoch.toString();

  Map<String, dynamic> toJson() {
    return {
      'product_id': product.id,
      'unit_type': unitType,
      'quantity': quantity,
    };
  }

  // Constructor para cargar productos existentes de una tarea
  factory SelectedProduct.fromJobProduct(Map<String, dynamic> json) {
    return SelectedProduct(
      product: Product(
        id: json['product_id'] is String ? int.parse(json['product_id']) : json['product_id'],
        codigo: json['codigo'] ?? '',
        descripcion: json['descripcion'] ?? '',
        isFromColppy: false, // No es relevante en este contexto
      ),
      unitType: json['unit_type'] ?? 'Unidad',
      quantity: json['quantity'] is String 
          ? double.parse(json['quantity']) 
          : (json['quantity'] as num).toDouble(),
      uniqueId: json['unique_id']?.toString(),
    );
  }
}
