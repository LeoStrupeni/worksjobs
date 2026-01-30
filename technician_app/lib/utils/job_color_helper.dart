import 'package:flutter/material.dart';

class JobColorHelper {
  static Color getStatusColor(String colorStatus) {
    switch (colorStatus.toLowerCase()) {
      case 'red':
        return Colors.red;
      case 'orange':
        return Colors.orange;
      case 'blue':
        return Colors.blue;
      case 'green':
        return Colors.green;
      case 'black':
        return Colors.black87;
      default:
        return Colors.grey;
    }
  }

  static Color getStatusBackgroundColor(String colorStatus) {
    switch (colorStatus.toLowerCase()) {
      case 'red':
        return Colors.red.shade50;
      case 'orange':
        return Colors.orange.shade50;
      case 'blue':
        return Colors.blue.shade50;
      case 'green':
        return Colors.green.shade50;
      case 'black':
        return Colors.grey.shade200;
      default:
        return Colors.grey.shade100;
    }
  }

  static IconData getStatusIcon(String status) {
    switch (status) {
      case 'Cerrado':
        return Icons.check_circle;
      case 'En Lugar':
        return Icons.location_on;
      case 'Pendiente':
        return Icons.pending;
      default:
        return Icons.info;
    }
  }
}
