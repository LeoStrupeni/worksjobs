class Job {
  final int id;
  final int? clientId;
  final String? clientName;
  final String? clientFirstName;
  final String? clientLastName;
  final String? clientEmail;
  final String? clientPhone;
  final String? visitDatetime;
  final String? arrivalDatetime;
  final String? closedDatetime;
  final String? jobDescription;
  final String? closedJobObservation;
  final double? visitLatitud;
  final double? visitLongitud;
  final double? arrivalLatitud;
  final double? arrivalLongitud;
  final double? closedLatitud;
  final double? closedLongitud;
  final String? status;
  final String? colorStatus;
  final String? createdAt;
  final String? updatedAt;

  Job({
    required this.id,
    this.clientId,
    this.clientName,
    this.clientFirstName,
    this.clientLastName,
    this.clientEmail,
    this.clientPhone,
    this.visitDatetime,
    this.arrivalDatetime,
    this.closedDatetime,
    this.jobDescription,
    this.closedJobObservation,
    this.visitLatitud,
    this.visitLongitud,
    this.arrivalLatitud,
    this.arrivalLongitud,
    this.closedLatitud,
    this.closedLongitud,
    this.status,
    this.colorStatus,
    this.createdAt,
    this.updatedAt,
  });

  factory Job.fromJson(Map<String, dynamic> json) {
    try {
      return Job(
        id: json['id'] ?? 0,
        clientId: json['client_id'],
        clientName: json['client_name'],
        clientFirstName: json['client_first_name'],
        clientLastName: json['client_last_name'],
        clientEmail: json['client_email'],
        clientPhone: json['client_phone'],
        visitDatetime: json['visit_datetime'],
        arrivalDatetime: json['arrival_datetime'],
        closedDatetime: json['closed_datetime'],
        jobDescription: json['job_description'],
        closedJobObservation: json['closed_job_observation'],
        visitLatitud: json['visit_latitud'] != null 
          ? double.tryParse(json['visit_latitud'].toString()) 
          : null,
        visitLongitud: json['visit_longitud'] != null 
          ? double.tryParse(json['visit_longitud'].toString()) 
          : null,
        arrivalLatitud: json['arrival_latitud'] != null 
          ? double.tryParse(json['arrival_latitud'].toString()) 
          : null,
        arrivalLongitud: json['arrival_longitud'] != null 
          ? double.tryParse(json['arrival_longitud'].toString()) 
          : null,
        closedLatitud: json['closed_latitud'] != null 
          ? double.tryParse(json['closed_latitud'].toString()) 
          : null,
        closedLongitud: json['closed_longitud'] != null 
          ? double.tryParse(json['closed_longitud'].toString()) 
          : null,
        status: json['status'],
        colorStatus: json['color_status'],
        createdAt: json['created_at'],
        updatedAt: json['updated_at'],
      );
    } catch (e) {
      print('❌ Error parsing Job JSON: $e');
      print('📄 JSON data: $json');
      rethrow;
    }
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'client_id': clientId,
      'client_name': clientName,
      'client_first_name': clientFirstName,
      'client_last_name': clientLastName,
      'client_email': clientEmail,
      'client_phone': clientPhone,
      'visit_datetime': visitDatetime,
      'arrival_datetime': arrivalDatetime,
      'closed_datetime': closedDatetime,
      'job_description': jobDescription,
      'closed_job_observation': closedJobObservation,
      'visit_latitud': visitLatitud,
      'visit_longitud': visitLongitud,
      'arrival_latitud': arrivalLatitud,
      'arrival_longitud': arrivalLongitud,
      'closed_latitud': closedLatitud,
      'closed_longitud': closedLongitud,
      'status': status,
      'color_status': colorStatus,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }

  bool get isPending => status == 'Pendiente';
  bool get isInPlace => status == 'En Lugar';
  bool get isClosed => status == 'Cerrado';
}
