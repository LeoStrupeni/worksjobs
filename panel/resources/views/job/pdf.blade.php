<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trabajo Realizado - {{ $job->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 20px;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #00274E;
        }
        
        .header-logo {
            display: table-cell;
            width: 200px;
            vertical-align: middle;
            padding-right: 20px;
        }
        
        .header-logo img {
            max-width: 180px;
            max-height: 80px;
            display: block;
        }
        
        .header-content {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        
        .header-content h1 {
            color: #00274E;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header-content p {
            color: #666;
            font-size: 14px;
        }
        
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background-color: #00274E;
            color: white;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 10px;
            width: 30%;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-left: none;
        }
        
        .description-box {
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f9f9f9;
            margin-top: 5px;
            white-space: pre-wrap;
        }
        
        .note-item {
            border-left: 3px solid #00274E;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
        }
        
        .note-date {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .note-content {
            font-size: 11px;
            white-space: pre-wrap;
        }
        
        .images-grid {
            width: 100%;
            margin-top: 10px;
            font-size: 0;
        }
        
        .image-item {
            display: inline-block;
            width: 30%;
            margin-right: 3%;
            margin-bottom: 15px;
            text-align: center;
            vertical-align: top;
            page-break-inside: avoid;
        }
        
        .image-item img {
            width: 100%;
            max-height: 180px;
            height: auto;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .image-caption {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .products-table th,
        .products-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .products-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .technicians-list {
            margin-top: 5px;
        }
        
        .technician-badge {
            display: inline-block;
            background-color: #00274E;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 11px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        @page {
            margin: 20mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <img src="{{ asset('assets/media/Logo.png') }}" alt="Strupeni Electrónica">
        </div>
        <div class="header-content">
            <h1>Orden de Trabajo Nro: {{ $job->id }}</h1>
            {{-- <p style="font-size: 12px; margin-top: 5px;">Generado el {{ date('d/m/Y H:i') }}</p>--}}
        </div>
    </div>

    {{-- Información del Cliente --}}
    <div class="section">
        <div class="section-title">INFORMACIÓN DEL CLIENTE</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Cliente:</div>
                <div class="info-value">{{ $job->client->first_name }} {{ $job->client->last_name ?? '' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Domicilio:</div>
                <div class="info-value">
                    @if($job->clientAddress)
                        {{ $job->clientAddress->address_detail ?? '' }} 
                        {{ $job->clientAddress->address_street }} 
                        {{ $job->clientAddress->address_nro ?? '' }} 
                        {{ $job->clientAddress->city ?? '' }}
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Visita:</div>
                <div class="info-value">{{ $job->visit_datetime ? \Carbon\Carbon::parse($job->visit_datetime)->format('d/m/Y H:i') : 'No asignada' }}</div>
            </div>
        </div>
    </div>

    {{-- Descripción del Trabajo --}}
    @if($includeDescription)
    <div class="section">
        <div class="section-title">DESCRIPCIÓN DEL TRABAJO</div>
        <div class="description-box">{{ $job->job_description }}</div>
    </div>
    @endif

    {{-- Técnicos Asignados --}}
    @if($includeTechnicians && $job->technicians && count($job->technicians) > 0)
    <div class="section">
        <div class="section-title">TÉCNICOS ASIGNADOS</div>
        <div class="technicians-list">
            @foreach($job->technicians as $technician)
                <span class="technician-badge">{{ $technician->name }}</span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Productos Relacionados --}}
    @if($includeProducts && $job->products && count($job->products) > 0)
    <div class="section">
        <div class="section-title">PRODUCTOS RELACIONADOS</div>
        <table class="products-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Tipo de Unidad</th>
                    <th style="text-align: right;">Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($job->products as $product)
                <tr>
                    <td>{{ $product->codigo }}</td>
                    <td>{{ $product->descripcion }}</td>
                    <td>{{ $product->unit_type ?? 'Unidad' }}</td>
                    <td style="text-align: right;">{{ number_format($product->quantity ?? 1, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Notas --}}
    @if($includeNotes && count($notes) > 0)
    <div class="section">
        <div class="section-title">NOTAS</div>
        @foreach($notes as $note)
        <div class="note-item">
            <div class="note-date">{{ \Carbon\Carbon::parse($note->created_at)->format('d/m/Y H:i') }}</div>
            <div class="note-content">{{ $note->note }}</div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Registro de Tiempos --}}
    @if($includeArrivalTime || $includeDepartureTime)
    <div class="section">
        <div class="section-title">REGISTRO DE TIEMPOS</div>
        <div class="info-grid">
            @if($includeArrivalTime)
            <div class="info-row">
                <div class="info-label">Fecha y Hora de Arribo:</div>
                <div class="info-value">{{ $job->arrival_datetime ? \Carbon\Carbon::parse($job->arrival_datetime)->format('d/m/Y H:i') : 'No registrado' }}</div>
            </div>
            @endif
            @if($includeDepartureTime)
            <div class="info-row">
                <div class="info-label">Fecha y Hora de Cierre:</div>
                <div class="info-value">{{ $job->closed_datetime ? \Carbon\Carbon::parse($job->closed_datetime)->format('d/m/Y H:i') : 'No registrado' }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Imágenes --}}
    @if($includeImages && count($images) > 0)
    <div class="section">
        <div class="section-title">IMÁGENES</div>
        <div class="images-grid">
            @foreach($images as $image)
                <div class="image-item">
                    <img src="{{ storage_path('app/public/' . $image->name) }}" alt="Imagen de trabajo">
                    <div class="image-caption">{{ $image->original_name ?? 'Imagen ' . $loop->iteration }}</div>
                </div>
                @if($loop->iteration % 3 == 0 && !$loop->last)
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Este documento es una representación del trabajo realizado en la fecha indicada.</p>
    </div>
</body>
</html>
