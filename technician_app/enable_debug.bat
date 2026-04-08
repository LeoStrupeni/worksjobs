@echo off
:: Toggle Debug Feature - Descomenta el bloque de debug

set FILE=lib\screens\home_screen.dart

:: Verificar si el debug está comentado
findstr /C:"// START_DEBUG_FEATURE" %FILE% >nul

if %errorlevel%==1 (
    echo Debug ya está activo
    exit /b 0
)

:: Descomentar líneas entre START y END
powershell -Command "$content = Get-Content '%FILE%'; $newContent = @(); foreach ($line in $content) { if ($line -match '^\s*//\s*(START_DEBUG_FEATURE|END_DEBUG_FEATURE)') { $newContent += $line -replace '^\s*//\s*', '        '; } elseif ($line -match '^\s*//\s*leading:' -or $line -match '^\s*//\s*onTap:' -or $line -match '^\s*//\s*child:' -or $line -match '^\s*//\s*\),' -or ($line -match '^\s*//\s*' -and $inBlock)) { $newContent += $line -replace '^\s*//\s*', '        '; } else { $newContent += $line; } if ($line -match 'START_DEBUG_FEATURE') { $inBlock = $true; } if ($line -match 'END_DEBUG_FEATURE') { $inBlock = $false; } }; $newContent | Set-Content '%FILE%'"

echo Debug activado exitosamente
