class Address {
  final int id;
  final String? country;
  final String? state;
  final String? cp;
  final String? city;
  final String? addressStreet;
  final String? addressNro;
  final String? addressApartament;
  final String? addressDetail;

  Address({
    required this.id,
    this.country,
    this.state,
    this.cp,
    this.city,
    this.addressStreet,
    this.addressNro,
    this.addressApartament,
    this.addressDetail,
  });

  factory Address.fromJson(Map<String, dynamic> json) {
    return Address(
      id: json['id'] as int,
      country: json['country'] as String?,
      state: json['state'] as String?,
      cp: json['cp'] as String?,
      city: json['city'] as String?,
      addressStreet: json['address_street'] as String?,
      addressNro: json['address_nro'] as String?,
      addressApartament: json['address_apartament'] as String?,
      addressDetail: json['address_detail'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'country': country,
      'state': state,
      'cp': cp,
      'city': city,
      'address_street': addressStreet,
      'address_nro': addressNro,
      'address_apartament': addressApartament,
      'address_detail': addressDetail,
    };
  }

  String get fullAddress {
    final parts = <String>[];
    if (addressStreet != null) parts.add(addressStreet!);
    if (addressNro != null) parts.add(addressNro!);
    if (addressApartament != null) parts.add('Piso $addressApartament');
    if (city != null) parts.add(city!);
    if (addressDetail != null) parts.add('($addressDetail)');
    return parts.join(', ');
  }
}
