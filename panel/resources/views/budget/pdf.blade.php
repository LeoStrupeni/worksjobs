<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Presupuesto {{ $nroFactura }}</title>
    <style>
        @page {
            size: A4;
            margin: 25mm 20mm 15mm 20mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #000;
            line-height: 1.4;
        }
        
        /* Contenedor principal */
        .container {
            width: 98%;
            margin: 30px auto;
            position: relative;
            min-height: 900px;
        }
        
        /* Tablas de layout */
        .layout-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .layout-table td {
            vertical-align: top;
            padding: 8px;
        }
        
        .layout-table td.border {
            border: 1px solid #000;
        }
        
        /* Eliminar borde izquierdo de segunda columna */
        .layout-table td.border + td.border {
            border-left: none;
        }
        
        /* Logo */
        .logo-container {
            text-align: center;
            margin-bottom: 5px;
        }
        
        .logo-container img {
            max-width: 200px;
            max-height: 90px;
            height: auto;
        }
        
        .empresa-info {
            font-size: 9pt;
            line-height: 1.3;
        }
        
        /* Header presupuesto */
        .presupuesto-header {
            text-align: left;
            padding-left: 50px;
        }
        
        .presupuesto-header h1 {
            font-size: 15pt;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .presupuesto-header .numero {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .info-line {
            margin-bottom: 2px;
            font-size: 8pt;
        }
        
        /* Sección cliente */
        .cliente-header {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        /* Tabla de items */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
            font-size: 7.5pt;
        }
        
        .items-table th {
            background-color: #e0e0e0;
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-weight: bold;
        }
        
        .items-table td {
            border: none;
            padding: 3px 8px 3px 3px; 
        }
        
        .text-center {
            text-align: center!important;
        }

        .text-left {
            text-align: left!important;
        }
        
        .text-right {
            text-align: right!important;
        }
        
        /* Totales */
        .totales-container {
            width: 100%;
            border-collapse: collapse;
        }
        
        /* Totales al final de la página (pocos items) */
        .totales-bottom {
            position: absolute;
            bottom: 200px;
            left: 0;
            right: 0;
            width: 98%;
            margin: 0 auto;
        }
        
        /* Totales debajo de items (muchos items) */
        .totales-inline {
            margin-top: 20px;
        }
        
        .border-totales{
            border: 1px solid #000;
        }
    </style>
</head>
<body>
    <!-- Debug: Logo Base64 length: {{ strlen($logoBase64 ?? '') }} -->
    <div class="container">
        <!-- Header: Logo/Empresa y Presupuesto -->
        <table class="layout-table">
            <tr>
                <td class="border" style="width: 50%; border-bottom: none!important;">
                    <div class="logo-container">
                        @if(!empty($logoBase64))
                            <img src="{{ $logoBase64 }}" alt="Logo Strupeni">
                        @else
                            <strong style="font-size: 14pt; color: #333;">STRUPENI</strong>
                        @endif
                    </div>
                    <h4 style="text-align: center;">STRUPENI TECNOLOGIAS</h4>
                </td>
                <td class="border" style="width: 50%; border-bottom: none!important;">
                    <div class="presupuesto-header">
                        <h1>PRESUPUESTO</h1>
                        <div class="numero">Punto de Venta: {{ $nroFactura1 }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comp. Nro: {{ $nroFactura2 }}</div>
                        <div class="numero">Fecha de Emisión: {{ $fechaFactura }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="border" style="width: 50%; border-top: none!important;">
                    <div class="empresa-info"> 
                        <strong>Razon Social:</strong> {{ $empresaRazonSocial }}
                    </div>
                    <div class="empresa-info"> 
                        <strong>Domicilio Comercial:</strong> {{ $empresaDomicilio }}
                    </div>
                    <br>
                    <div class="empresa-info"> 
                        <strong>Condición Frente al IVA:</strong> {{ $empresaCondicionIVA }}
                    </div>
                </td>
                <td class="border" style="width: 50%; border-top: none!important;">
                    <div class="presupuesto-header">
                        <div class="empresa-info"> 
                            <strong>CUIT:</strong> {{ $empresaCUIT }}
                        </div>
                        <div class="empresa-info"> 
                            <strong>Ingresos Brutos:</strong> {{ $empresaIIBB }}
                        </div>
                        <div class="empresa-info"> 
                            <strong>Fecha de Inicio de Actividades:</strong> {{ $empresaFechaInicio }}
                        </div>
                    </div>
                    {{-- <div class="info-line">
                        <strong>Fecha:</strong> {{ $fechaFactura }}
                    </div>
                    <div class="info-line">
                        <strong>Fecha Pago:</strong> {{ $fechaPago }}
                    </div>
                    <div class="info-line">
                        <strong>Condición:</strong> {{ $condicionPago }}
                    </div> --}}
                </td>
            </tr>
        </table>
        
        <!-- Cliente -->
        <table class="layout-table">
            <tr>
                <td class="border"  style="border-right: none!important;">
                    <div class="info-line">
                        <strong>CUIT:</strong> {{ $clienteCUIT }}
                    </div>
                    <div class="info-line">
                        <strong>Condición Frente al IVA:</strong> {{ $clienteCondicionIVA }}
                    </div>
                    <div class="info-line">
                        <strong>Condición de Venta:</strong> {{ $condicionPago }}
                    </div>
                    
                </td>
                <td class="border" style="border-left: none!important;">
                    <div class="info-line">
                        <strong>Apellido y Nombre / Razón Social:</strong> {{ $clienteNombre }}
                    </div>
                    <div class="info-line">
                        <strong>Domicilio Comercial:</strong> {{ $clienteDomicilio }}
                    </div>
                    <div class="info-line">
                        <strong>Fecha Vencimiento:</strong> {{ $fechaVencimiento ?? '' }}
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Tabla de items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-left" style="width: 25%;">Producto / Servicio</th>
                    <th class="text-center" style="width: 12%;">Cantidad</th>
                    <th class="text-center" style="width: 12%;">U. Medida</th>
                    <th class="text-center" style="width: 12%;">Precio Unit.</th>
                    <th class="text-center" style="width: 12%;">% Descuento</th>
                    <th class="text-center" style="width: 12%;">Alícuota IVA</th>
                    <th class="text-center" style="width: 15%;">Subtotal sin IVA</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item['descripcion'] }}</td>
                    <td class="text-right">{{ number_format($item['cantidad'], 2, ',', '.') }}</td>
                    <td class="text-center">{{ $item['unidadMedida'] }}</td>
                    <td class="text-right">$ {{ number_format($item['precioUnitario'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item['descuento'], 2, ',', '.') }} %</td>
                    <td class="text-right">{{ number_format($item['iva'], 2, ',', '.') }} %</td>
                    <td class="text-right">$ {{ number_format($item['subtotal'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Totales -->
        <table class="totales-container border-totales {{ count($items) < 20 ? 'totales-bottom' : 'totales-inline' }}" style="width: 100%; border-collapse: collapse;">

            <tr>
                <td width="85%" style="padding: 8px 8px 2px 2px;font-size: 7.5pt;" class="text-right"><strong>Importe Neto Gravado: $</strong></td>
                <td width="15%" style="padding: 8px 8px 2px 2px;font-size: 7.5pt;" class="text-right"><strong>{{ number_format($netoGravado, 2, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td width="85%" style="padding: 2px 8px 2px 2px;font-size: 7.5pt;" class="text-right"><strong>Importe Exento / No Gravado: $</strong></td>
                <td width="15%" style="padding: 2px 8px 2px 2px;font-size: 7.5pt;" class="text-right"><strong>{{ number_format($netoNoGravado, 2, ',', '.') }}</strong></td>
            </tr>

            @if($iva21 > 0)
                <tr>
                    <td width="85%" style="padding: 2px 8px 2px 2px;font-size: 7.5pt;" class="text-right"><strong>IVA 21%: $</strong></td>
                    <td width="15%" style="padding: 2px 8px 2px 2px;font-size: 7.5pt;" class="text-right"><strong>{{ number_format($iva21, 2, ',', '.') }}</strong></td>
                </tr>
            @endif

            @if($iva105 > 0)
                <tr>
                    <td width="85%" style="padding: 2px 8px 2px 2px;font-size: 7.5pt;" class="text-right"><strong>IVA 10.5%: $</strong></td>
                    <td width="15%" style="padding: 2px 8px 2px 2px;font-size: 7.5pt;" class="text-right"><strong>{{ number_format($iva105, 2, ',', '.') }}</strong></td>
                </tr>
            @endif
            
            @if($iva27 > 0)
                <tr>
                    <td width="85%" style="padding: 2px 8px 2px 2px;font-size: 7.5pt;" class="text-right"><strong>IVA 27%: $</strong></td>
                    <td width="15%" style="padding: 2px 8px 2px 2px;font-size: 7.5pt;" class="text-right"><strong>{{ number_format($iva27, 2, ',', '.') }}</strong></td>
                </tr>
            @endif
            
            <tr>
                <td width="85%" style="padding: 2px 8px 8px 2px;font-size: 7.5pt;" class="text-right"><strong>Importe Total: $</strong></td>
                <td width="15%" style="padding: 2px 8px 8px 2px;font-size: 7.5pt;" class="text-right"><strong>{{ number_format($totalFactura, 2, ',', '.') }}</strong></td>
            </tr>

        </table>

    </div>
</body>
</html>
