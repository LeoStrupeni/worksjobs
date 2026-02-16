<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $page->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        .preview-container {
            background: white;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .preview-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin: -40px -40px 30px -40px;
            border-radius: 8px 8px 0 0;
        }
        .preview-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="preview-badge">
        <span class="badge bg-warning text-dark fs-5">
            <i class="fas fa-eye me-2"></i>MODO PREVIEW
        </span>
    </div>

    <div class="preview-container">
        <div class="preview-header">
            <h1 class="mb-1">{{ $page->title }}</h1>
            <small class="opacity-75">Identificador: {{ $page->key }}</small>
        </div>

        <div class="content">
            {!! $page->draft_content !!}
        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-between align-items-center">
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>
                Cerrar Preview
            </button>
            
            <form action="{{ route('cms.pages.publish', $page->id) }}" method="POST" onsubmit="return confirm('¿Publicar este contenido ahora?');">
                @csrf
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-check-circle me-2"></i>
                    Publicar Ahora
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
