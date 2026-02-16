import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/theme_provider.dart';

/// Screen de ejemplo que muestra cómo usar y recargar el tema CMS
class ThemeSettingsScreen extends StatelessWidget {
  const ThemeSettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Configuración de Tema'),
      ),
      body: Consumer<ThemeProvider>(
        builder: (context, themeProvider, _) {
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              // Información del tema actual
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Tema Actual',
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      const SizedBox(height: 12),
                      _buildInfoRow(
                        'Nombre:',
                        themeProvider.cmsTheme?.name ?? 'Tema por defecto',
                      ),
                      _buildInfoRow(
                        'Versión:',
                        themeProvider.cmsTheme?.version ?? 'N/A',
                      ),
                      _buildInfoRow(
                        'Estado:',
                        themeProvider.isLoading ? 'Cargando...' : 'Listo',
                      ),
                      if (themeProvider.error != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Text(
                            'Error: ${themeProvider.error}',
                            style: TextStyle(
                              color: Theme.of(context).colorScheme.error,
                              fontSize: 12,
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 16),

              // Botón para recargar tema
              ElevatedButton.icon(
                icon: themeProvider.isLoading
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.refresh),
                label: const Text('Actualizar Tema desde CMS'),
                onPressed: themeProvider.isLoading
                    ? null
                    : () async {
                        await themeProvider.reloadTheme();
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(
                                themeProvider.error == null
                                    ? '✅ Tema actualizado correctamente'
                                    : '❌ Error al actualizar tema',
                              ),
                              backgroundColor: themeProvider.error == null
                                  ? Colors.green
                                  : Colors.red,
                            ),
                          );
                        }
                      },
              ),

              const SizedBox(height: 24),

              // Demostración de colores
              Text(
                'Colores del Tema',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 12),

              if (themeProvider.cmsTheme != null)
                _buildColorPalette(context, themeProvider),

              const SizedBox(height: 24),

              // Ejemplo de componentes con el tema
              Text(
                'Ejemplos de Componentes',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 12),

              _buildComponentExamples(context),
            ],
          );
        },
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Text(
              label,
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
          Expanded(
            flex: 3,
            child: Text(value),
          ),
        ],
      ),
    );
  }

  Widget _buildColorPalette(BuildContext context, ThemeProvider provider) {
    final colors = provider.cmsTheme!.config.colors;

    final colorList = [
      ('Primary', colors.primary),
      ('Secondary', colors.secondary),
      ('Accent', colors.accent),
      ('Error', colors.error),
      ('Success', colors.success),
      ('Warning', colors.warning),
      ('Info', colors.info),
    ];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Wrap(
          spacing: 12,
          runSpacing: 12,
          children: colorList.map((item) {
            return _buildColorChip(item.$1, item.$2);
          }).toList(),
        ),
      ),
    );
  }

  Widget _buildColorChip(String name, Color color) {
    return Column(
      children: [
        Container(
          width: 60,
          height: 60,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: Colors.grey[300]!, width: 1),
          ),
        ),
        const SizedBox(height: 4),
        Text(
          name,
          style: const TextStyle(fontSize: 11),
        ),
      ],
    );
  }

  Widget _buildComponentExamples(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Botones
            ElevatedButton(
              onPressed: () {},
              child: const Text('Elevated Button'),
            ),
            const SizedBox(height: 8),
            
            OutlinedButton(
              onPressed: () {},
              child: const Text('Outlined Button'),
            ),
            const SizedBox(height: 8),
            
            TextButton(
              onPressed: () {},
              child: const Text('Text Button'),
            ),
            
            const Divider(height: 32),
            
            // Input
            TextField(
              decoration: InputDecoration(
                labelText: 'Campo de Texto',
                hintText: 'Escribe algo...',
                prefixIcon: const Icon(Icons.edit),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
            
            const SizedBox(height: 16),
            
            // Chips
            Wrap(
              spacing: 8,
              children: [
                Chip(
                  label: const Text('Chip 1'),
                  avatar: const Icon(Icons.check, size: 16),
                ),
                Chip(
                  label: const Text('Chip 2'),
                  backgroundColor: Theme.of(context).colorScheme.secondary,
                ),
                ActionChip(
                  label: const Text('Action Chip'),
                  onPressed: () {},
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
