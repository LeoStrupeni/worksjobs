#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
BUILD_FAST.py - Script de Compilación Automática Flutter (Solo Versión Usuario)
==============================================================================

Genera la versión de la app Strupeni Técnicos omitiendo la versión debug.

Uso: python BUILD_FAST.py
"""

import os
import re
import sys
import subprocess
import shutil
import argparse
from datetime import datetime

# Colores ANSI para terminal
class Colors:
    GREEN = '\033[92m'
    YELLOW = '\033[93m'
    RED = '\033[91m'
    BLUE = '\033[94m'
    CYAN = '\033[96m'
    BOLD = '\033[1m'
    END = '\033[0m'

FLAVOR_TARGETS = {
    'dev': 'lib/main_dev.dart',
    'qa': 'lib/main_qa.dart',
    'prod': 'lib/main_prod.dart',
}

def print_header(text):
    print(f"\n{Colors.BOLD}{Colors.CYAN}{'='*75}")
    print(f"  {text}")
    print(f"{'='*75}{Colors.END}\n")

def print_step(step, total, text):
    print(f"{Colors.BOLD}[{step}/{total}] {Colors.BLUE}{text}{Colors.END}")

def print_success(text):
    print(f"{Colors.GREEN}✅ {text}{Colors.END}")

def print_error(text):
    print(f"{Colors.RED}❌ {text}{Colors.END}")

def print_warning(text):
    print(f"{Colors.YELLOW}⚠️  {text}{Colors.END}")

def build_arg_parser():
    parser = argparse.ArgumentParser(
        description='Compilación y ejecución automatizada para Strupeni Técnicos',
    )
    parser.add_argument(
        '--mode',
        choices=['release', 'run-dev', 'run-qa', 'run-prod', 'build-prod'],
        default='release',
        help='release (default) mantiene el flujo interactivo de 2 APKs',
    )
    return parser

def read_current_version():
    """Lee la versión actual del pubspec.yaml"""
    with open('pubspec.yaml', 'r', encoding='utf-8') as f:
        content = f.read()
    
    match = re.search(r'^version:\s*(\d+)\.(\d+)\.(\d+)\+(\d+)', content, re.MULTILINE)
    if not match:
        raise Exception("No se pudo leer la versión del pubspec.yaml")
    
    major, minor, patch, build = match.groups()
    return {
        'major': int(major),
        'minor': int(minor),
        'patch': int(patch),
        'build': int(build),
        'full': f"{major}.{minor}.{patch}+{build}"
    }

def update_pubspec_version(version, build):
    """Actualiza la versión en pubspec.yaml"""
    with open('pubspec.yaml', 'r', encoding='utf-8') as f:
        content = f.read()
    
    new_content = re.sub(
        r'^version:.*$',
        f'version: {version}+{build}',
        content,
        flags=re.MULTILINE
    )
    
    with open('pubspec.yaml', 'w', encoding='utf-8') as f:
        f.write(new_content)

def toggle_debug_feature(enable=True):
    """Comenta o descomenta el bloque de debug in home_screen.dart"""
    filepath = 'lib/screens/home_screen.dart'
    
    with open(filepath, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    new_lines = []
    in_debug_block = False
    
    for line in lines:
        if 'START_DEBUG_FEATURE' in line:
            in_debug_block = True
            new_lines.append(line)  # Mantener markers como están
        elif 'END_DEBUG_FEATURE' in line:
            new_lines.append(line)  # Mantener markers como están
            in_debug_block = False
        elif in_debug_block:
            if enable:
                # Descomentar: quitar '// ' del inicio (después de espacios)
                stripped = line.lstrip()
                if stripped.startswith('// '):
                    indent = len(line) - len(stripped)
                    new_lines.append(' ' * indent + stripped[3:])
                elif stripped.startswith('//'):
                    indent = len(line) - len(stripped)
                    new_lines.append(' ' * indent + stripped[2:])
                else:
                    new_lines.append(line)
            else:
                # Comentar: agregar '// ' al inicio (preservando indentación)
                stripped = line.lstrip()
                if stripped and not stripped.startswith('//'):
                    indent = len(line) - len(stripped)
                    new_lines.append(' ' * indent + '// ' + stripped)
                else:
                    new_lines.append(line)
        else:
            new_lines.append(line)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.writelines(new_lines)

def run_command(cmd, error_message):
    result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    if result.returncode != 0:
        print_error(error_message)
        print(result.stderr)
        return False

    return True

def run_flutter_build(flavor='prod'):
    """Ejecuta flutter build apk por flavor"""
    target = FLAVOR_TARGETS[flavor]
    print(f"{Colors.YELLOW}    Compilando APK ({flavor})...{Colors.END}")
    cmd = f'flutter build apk --flavor {flavor} -t {target} --release'
    return run_command(cmd, 'Falló la compilación')

def archive_prod_build():
    """Copia el APK prod generado a nombres finales legibles."""
    current = read_current_version()
    version = f"{current['major']}.{current['minor']}.{current['patch']}"
    src_apk = 'build/app/outputs/flutter-apk/app-prod-release.apk'

    if not os.path.exists(src_apk):
        print_error(f"No se encontró el APK generado: {src_apk}")
        return False

    filename = f'strupeni-tecnicos-v{version}.apk'
    dst_apk_build = f'build/app/outputs/flutter-apk/{filename}'

    os.makedirs('VersionApk', exist_ok=True)
    dst_apk_version = f'VersionApk/{filename}'

    shutil.copy2(src_apk, dst_apk_build)
    shutil.copy2(src_apk, dst_apk_version)

    print_success('APK prod archivado correctamente')
    print(f"       📍 build/app/outputs/flutter-apk/{filename}")
    print(f"       📍 VersionApk/{filename}\n")
    return True

def run_flutter_app(flavor):
    """Ejecuta flutter run por flavor"""
    target = FLAVOR_TARGETS[flavor]
    print_header(f"RUN {flavor.upper()} - Strupeni Técnicos")
    print(f"Ejecutando: flutter run --flavor {flavor} -t {target}\n")
    cmd = f'flutter run --flavor {flavor} -t {target}'
    return run_command(cmd, f'Falló la ejecución para flavor {flavor}')

def run_non_release_mode(mode):
    if mode == 'run-dev':
        return run_flutter_app('dev')
    if mode == 'run-qa':
        return run_flutter_app('qa')
    if mode == 'run-prod':
        return run_flutter_app('prod')
    if mode == 'build-prod':
        print_header('BUILD PROD - Strupeni Técnicos')
        if not run_flutter_build('prod'):
            return False
        return archive_prod_build()
    return False

def main():
    args = build_arg_parser().parse_args()

    # Verificar que estamos en el directorio correcto
    if not os.path.exists('pubspec.yaml'):
        print_error("No se encontró pubspec.yaml")
        print("Ejecuta este script desde la carpeta technician_app/")
        sys.exit(1)
    
    # Verificar que Flutter está instalado
    try:
        subprocess.run('flutter --version', shell=True, capture_output=True, check=True)
    except:
        print_error("Flutter no está instalado o no está en el PATH")
        sys.exit(1)

    if args.mode != 'release':
        ok = run_non_release_mode(args.mode)
        if not ok:
            sys.exit(1)
        return
    
    print_header("BUILD FAST - Strupeni Técnicos App")
    print("Compilación Automática con Versionado\n")
    
    # PASO 1: Leer versión actual
    print_step(1, 7, "📖 Leyendo versión actual...")
    current = read_current_version()
    print(f"\n    Versión actual: {current['major']}.{current['minor']}.{current['patch']}")
    print(f"    Build number:   {current['build']}")
    print(f"    MAJOR: {current['major']}, MINOR: {current['minor']}, PATCH: {current['patch']}\n")
    
    # PASO 2: Solicitar nueva versión
    print_step(2, 7, "✍️  Ingresa la nueva versión")
    print("\n    Versión Usuario (MINOR debe ser PAR)")
    print("    Formato: MAJOR.MINOR.PATCH")
    print(f"    Ejemplo: 1.2.0  (MINOR=2, es par ✓)\n")
    
    while True:
        try:
            new_version = input("    Nueva versión: ").strip()
            
            # Validar formato
            match = re.match(r'^(\d+)\.(\d+)\.(\d+)$', new_version)
            if not match:
                print_error("Formato inválido. Usa: MAJOR.MINOR.PATCH\n")
                continue
            
            major, minor, patch = map(int, match.groups())
            
            # Validar que MINOR sea par
            if minor % 2 != 0:
                print_error(f"El MINOR debe ser PAR para versión de usuario")
                print(f"       Ingresaste: {new_version} (MINOR={minor})")
                print(f"       Ejemplos válidos: 1.0.0, 1.2.1, 2.4.3\n")
                continue
            
            break
        except KeyboardInterrupt:
            print("\n\n❌ Compilación cancelada")
            sys.exit(0)
    
    # Calcular versión debug
    debug_minor = minor + 1
    debug_version = f"{major}.{debug_minor}.{patch}"
    
    # Incrementar build number UNA SOLA VEZ (mismo para ambas versiones)
    new_build = current['build'] + 1
    
    print_success("Versión validada correctamente")
    print(f"\n    📦 Versiones a generar:")
    print(f"       • Usuario: {new_version}+{new_build} (sin debug)")
    print(f"       • Debug:   {debug_version}+{new_build} (con debug)\n")
    
    # PASO 3: Confirmación
    confirm = input("    ¿Continuar con la compilación? (S/N): ").strip().upper()
    if confirm != 'S':
        print("\n❌ Compilación cancelada")
        sys.exit(0)
    
    print_header("INICIANDO COMPILACIÓN")
    
    # Crear carpetas necesarias
    os.makedirs('build_backup', exist_ok=True)
    
    # Backup de archivos
    shutil.copy2('pubspec.yaml', 'build_backup/pubspec.yaml.bak')
    shutil.copy2('lib/screens/home_screen.dart', 'build_backup/home_screen.dart.bak')
    
    try:
        # PASO 4: Compilar versión USUARIO
        print_step(3, 7, f"🔨 Compilando versión USUARIO {new_version}+{new_build}...")
        print()
        
        # Actualizar versión
        update_pubspec_version(new_version, new_build)
        print_success(f"Versión actualizada a {new_version}+{new_build}")
        
        # Desactivar debug
        toggle_debug_feature(enable=False)
        print_success("Debug desactivado")
        
        # Limpiar y compilar
        print("    🧹 Limpiando caché Flutter...")
        subprocess.run('flutter clean', shell=True, capture_output=True)
        
        print("    📦 Obteniendo dependencias...")
        subprocess.run('flutter pub get', shell=True, capture_output=True)
        
        if not run_flutter_build():
            raise Exception("Falló la compilación de versión usuario")
        
        # Copiar APK a ubicaciones finales
        src_apk = 'build/app/outputs/flutter-apk/app-prod-release.apk'
        filename_usuario = f'strupeni-tecnicos-v{new_version}.apk'
        dst_apk_build = f'build/app/outputs/flutter-apk/{filename_usuario}'
        
        # Crear carpeta VersionApk si no existe
        os.makedirs('VersionApk', exist_ok=True)
        dst_apk_version = f'VersionApk/{filename_usuario}'
        
        if os.path.exists(src_apk):
            # Copiar a build/ y a VersionApk/
            shutil.copy2(src_apk, dst_apk_build)
            shutil.copy2(src_apk, dst_apk_version)
            print_success(f"APK Usuario generado y guardado")
            print(f"       📍 build/app/outputs/flutter-apk/{filename_usuario}")
            print(f"       📍 VersionApk/{filename_usuario}\n")
        
        # Restaurar home_screen.dart
        shutil.copy2('build_backup/home_screen.dart.bak', 'lib/screens/home_screen.dart')
        
        # PASO 5: Compilar versión DEBUG (OMITIDO EN MODO RÁPIDO)
        print_step(4, 7, f"🔨 Compilando versión DEBUG {debug_version}+{new_build}...")
        print()
        print_warning("Compilación de versión DEBUG omitida en este script rápido.")
        print()
        
        # PASO 6: Dejar en versión debug
        print_step(5, 7, "🔄 Configurando para desarrollo...")
        print()
        
        # Actualizar versión a debug en pubspec para desarrollo diario
        update_pubspec_version(debug_version, new_build)
        toggle_debug_feature(enable=True)
        
        print_success(f"pubspec.yaml en versión debug: {debug_version}+{new_build}")
        print_success("home_screen.dart con debug activado\n")
        
        # PASO 7: Generar resumen
        print_step(6, 7, "📋 Generando resumen...")
        
        summary_file = f'VersionApk/BUILD_SUMMARY_{new_version}.txt'
        with open(summary_file, 'w', encoding='utf-8') as f:
            f.write("═" * 75 + "\n")
            f.write("  RESUMEN DE COMPILACIÓN (RÁPIDO) - Strupeni Técnicos App\n")
            f.write("═" * 75 + "\n\n")
            f.write(f"Fecha: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n\n")
            f.write("VERSIONES GENERADAS:\n\n")
            f.write(f"  1. VERSIÓN USUARIO (Producción):\n")
            f.write(f"     • Versión: {new_version}+{new_build}\n")
            f.write(f"     • Archivo: strupeni-tecnicos-v{new_version}.apk\n")
            f.write(f"     • Debug: DESACTIVADO\n")
            f.write(f"     • Destinatarios: Técnicos finales\n\n")
            f.write(f"  2. VERSIÓN DEBUG (Desarrollo):\n")
            f.write(f"     • Versión: {debug_version}+{new_build}\n")
            f.write(f"     • Archivo: (OMITIDO EN MODO RÁPIDO)\n")
            f.write(f"     • Debug: ACTIVADO (gesto secreto 5 taps en logo)\n")
            f.write(f"     • Destinatarios: Desarrolladores / Testing\n\n")
            f.write(f"VERSIÓN ANTERIOR: {current['full']}\n\n")
            f.write("═" * 75 + "\n")
        
        print_success(f"Resumen guardado: {os.path.basename(summary_file)}\n")
        
        # PASO 8: Finalización
        print_step(7, 7, "✅ COMPILACIÓN COMPLETADA")
        print_header("🎉 COMPILACIÓN EXITOSA 🎉")
        
        print("📦 Archivos generados:\n")
        print(f"    1️⃣  strupeni-tecnicos-v{new_version}.apk")
        print("        └─ Para distribución a técnicos (SIN debug)\n")
        print("    2️⃣  [Versión Debug Omitida]\n")
        print(f"📍 Ubicaciones:")
        print(f"    • build/app/outputs/flutter-apk/ (última compilación)")
        print(f"    • VersionApk/ (historial de todas las versiones)\n")
        print(f"📝 pubspec.yaml configurado con versión DEBUG para desarrollo")
        print(f"    Versión actual: {debug_version}+{new_build}\n")
        print("═" * 75 + "\n")
        
    except Exception as e:
        print_error(f"Error durante la compilación: {e}")
        print("\n🔄 Restaurando archivos...")
        shutil.copy2('build_backup/pubspec.yaml.bak', 'pubspec.yaml')
        shutil.copy2('build_backup/home_screen.dart.bak', 'lib/screens/home_screen.dart')
        sys.exit(1)

if __name__ == '__main__':
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n❌ Compilación cancelada por el usuario")
        sys.exit(0)