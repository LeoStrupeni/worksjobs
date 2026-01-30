@echo off
echo ============================================
echo Habilitando Apache para acceso desde la red
echo ============================================
echo.

netsh advfirewall firewall add rule name="Apache HTTP Server" dir=in action=allow protocol=TCP localport=80
netsh advfirewall firewall add rule name="Apache HTTPS Server" dir=in action=allow protocol=TCP localport=443

echo.
echo ============================================
echo Reglas de firewall agregadas exitosamente
echo ============================================
echo.
echo Ahora reinicia Apache desde el panel de XAMPP
echo Luego prueba abrir desde tu celular:
echo http://192.168.1.4/panel
echo.
pause
