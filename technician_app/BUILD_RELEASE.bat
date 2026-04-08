@echo off
chcp 65001 >nul
:: =============================================================================
:: BUILD_RELEASE.bat - Launcher para el script de compilación
:: =============================================================================
:: Este script ejecuta BUILD_RELEASE.py que maneja toda la compilación
:: =============================================================================

color 0A
cls

:: Verificar que Python esté instalado
python --version >nul 2>&1
if errorlevel 1 (
    color 0C
    echo.
    echo ═══════════════════════════════════════════════════════════════════════
    echo   ❌ ERROR: Python no está instalado
    echo ═══════════════════════════════════════════════════════════════════════
    echo.
    echo   Este script requiere Python 3.x para funcionar.
    echo.
    echo   Opciones:
    echo     1. Instala Python desde: https://www.python.org/downloads/
    echo     2. Durante la instalación, marca "Add Python to PATH"
    echo.
    pause
    exit /b 1
)

:: Ejecutar el script Python
python BUILD_RELEASE.py

:: Mantener la ventana abierta si hay error
if errorlevel 1 (
    echo.
    pause
)

