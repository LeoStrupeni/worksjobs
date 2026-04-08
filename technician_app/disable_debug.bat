@echo off
:: Toggle Debug Feature - Comenta o descomenta el bloque de debug

set FILE=lib\screens\home_screen.dart

:: Verificar si el debug está comentado
findstr /C:"// START_DEBUG_FEATURE" %FILE% >nul

if %errorlevel%==0 (
    echo Debug ya está comentado
    exit /b 0
)

:: Comentar líneas entre START y END
powershell -Command "$content = Get-Content '%FILE%'; $inBlock = $false; $newContent = @(); foreach ($line in $content) { if ($line -match 'START_DEBUG_FEATURE') { $inBlock = $true; $newContent += '        // ' + $line.TrimStart(); } elseif ($line -match 'END_DEBUG_FEATURE') { $inBlock = $false; $newContent += '        // ' + $line.TrimStart(); } elseif ($inBlock) { $newContent += '        // ' + $line.TrimStart(); } else { $newContent += $line; } }; $newContent | Set-Content '%FILE%'"

echo Debug comentado exitosamente
