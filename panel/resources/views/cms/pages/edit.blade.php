@extends('layout')

@section('link_by_page')
<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css" />
@endsection

@section('Content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card" style="border: none; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="card-header text-white d-flex justify-content-between align-items-center border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0;">
                    <h4 class="mb-0 fw-bold">
                        <i class="fas fa-edit me-2"></i>
                        Editar: {{ $page->title }}
                    </h4>
                    <div>
                        <a href="{{ route('cms.pages.versions', $page->id) }}" class="btn btn-info" style="border-radius: 10px; font-weight: 500;">
                            <i class="fas fa-history me-2"></i>Versiones
                        </a>
                        <a href="{{ route('cms.index') }}" class="btn btn-light" style="border-radius: 10px; font-weight: 500;">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
                <div class="card-body" style="background-color: #f8f9fa;">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; border-left: 4px solid #10b981;">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; border-left: 4px solid #ef4444;">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <!-- Info de la página -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <div class="card-body" style="background: white;">
                                    <p class="mb-2">
                                        <strong><i class="fas fa-key text-primary me-2"></i>Identificador:</strong> 
                                        <code style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px;">{{ $page->key }}</code>
                                    </p>
                            <p class="mb-1">
                                <strong>Estado:</strong>
                                @if($page->is_published)
                                    <span class="badge bg-success">Publicado</span>
                                @else
                                    <span class="badge bg-warning text-dark">Borrador</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            @if($page->published_at)
                                <p class="mb-1">
                                    <strong>Última publicación:</strong> {{ $page->published_at->format('d/m/Y H:i') }}
                                </p>
                            @endif
                            @if($page->user)
                                <p class="mb-1">
                                    <strong>Editado por:</strong> {{ $page->user->name }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <!-- Formulario de edición -->
                    <form action="{{ route('cms.pages.draft', $page->id) }}" method="POST" id="pageForm">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="title" class="form-label">
                                <strong>Título de la sección</strong>
                            </label>
                            <input 
                                type="text" 
                                class="form-control form-control-lg" 
                                id="title" 
                                name="title" 
                                value="{{ old('title', $page->title) }}" 
                                required
                            >
                        </div>

                        <!-- Campos SEO -->
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-search me-2"></i>
                                    Optimización SEO
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="meta_title" class="form-label">Meta Título</label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="meta_title" 
                                            name="meta_title" 
                                            value="{{ old('meta_title', $page->meta_title) }}"
                                            maxlength="60"
                                        >
                                        <small class="form-text text-muted">
                                            Recomendado: 50-60 caracteres
                                        </small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="og_image" class="form-label">Imagen Open Graph</label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="og_image" 
                                            name="og_image" 
                                            value="{{ old('og_image', $page->og_image) }}"
                                            placeholder="URL de la imagen para redes sociales"
                                        >
                                    </div>
                                    
                                    <div class="col-12 mb-2">
                                        <label for="meta_description" class="form-label">Meta Descripción</label>
                                        <textarea 
                                            class="form-control" 
                                            id="meta_description" 
                                            name="meta_description" 
                                            rows="2"
                                            maxlength="160"
                                        >{{ old('meta_description', $page->meta_description) }}</textarea>
                                        <small class="form-text text-muted">
                                            Recomendado: 150-160 caracteres
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <strong>Contenido (Editor WYSIWYG)</strong>
                            </label>
                            <div id="editor" style="min-height: 500px;">
                                {!! old('draft_content', $page->draft_content ?? $page->content) !!}
                            </div>
                            <textarea name="draft_content" id="draft_content" style="display: none;"></textarea>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="submit" class="btn btn-primary btn-lg" id="saveDraftBtn">
                                    <i class="fas fa-save me-2"></i>
                                    Guardar Borrador
                                </button>
                                
                                @if($page->draft_content)
                                    <a href="{{ route('cms.pages.preview', $page->id) }}" 
                                       class="btn btn-info btn-lg" 
                                       target="_blank">
                                        <i class="fas fa-eye me-2"></i>
                                        Ver Preview
                                    </a>
                                @endif
                            </div>

                            @if($page->draft_content)
                                <form action="{{ route('cms.pages.publish', $page->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas publicar este contenido? Se mostrará en el sitio web público.');">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Publicar Contenido
                                    </button>
                                </form>
                            @endif
                        </div>
                    </form>

                    <!-- Ayuda -->
                    <div class="alert alert-info mt-4">
                        <h6><i class="fas fa-info-circle me-2"></i>¿Cómo funciona?</h6>
                        <ul class="mb-0">
                            <li><strong>Guardar Borrador:</strong> Guarda los cambios sin publicarlos (solo tú los ves)</li>
                            <li><strong>Ver Preview:</strong> Visualiza cómo se verá el contenido antes de publicar</li>
                            <li><strong>Publicar:</strong> Hace visible el contenido en el sitio web público</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script_by_page')
<script type="importmap">
{
    "imports": {
        "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.js",
        "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.3.1/"
    }
}
</script>

<script type="module">
import {
    ClassicEditor,
    Essentials,
    Bold,
    Italic,
    Underline,
    Strikethrough,
    Font,
    Paragraph,
    Heading,
    Link,
    List,
    Image,
    ImageToolbar,
    ImageCaption,
    ImageStyle,
    ImageResize,
    ImageUpload,
    SimpleUploadAdapter,
    Table,
    TableToolbar,
    MediaEmbed,
    BlockQuote,
    Code,
    CodeBlock,
    HorizontalLine,
    Alignment,
    Indent,
    IndentBlock,
    RemoveFormat,
    SourceEditing
} from 'ckeditor5';

ClassicEditor
    .create(document.querySelector('#editor'), {
        plugins: [
            Essentials,
            Bold,
            Italic,
            Underline,
            Strikethrough,
            Font,
            Paragraph,
            Heading,
            Link,
            List,
            Image,
            ImageToolbar,
            ImageCaption,
            ImageStyle,
            ImageResize,
            ImageUpload,
            SimpleUploadAdapter,
            Table,
            TableToolbar,
            MediaEmbed,
            BlockQuote,
            Code,
            CodeBlock,
            HorizontalLine,
            Alignment,
            Indent,
            IndentBlock,
            RemoveFormat,
            SourceEditing
        ],
        toolbar: {
            items: [
                'undo', 'redo',
                '|',
                'heading',
                '|',
                'bold', 'italic', 'underline', 'strikethrough',
                '|',
                'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor',
                '|',
                'link', 'insertImage', 'insertTable', 'mediaEmbed', 'blockQuote', 'codeBlock',
                '|',
                'alignment',
                '|',
                'bulletedList', 'numberedList',
                '|',
                'outdent', 'indent',
                '|',
                'horizontalLine',
                '|',
                'removeFormat',
                '|',
                'sourceEditing'
            ],
            shouldNotGroupWhenFull: true
        },
        heading: {
            options: [
                { model: 'paragraph', title: 'Párrafo', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Título 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Título 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Título 3', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Título 4', class: 'ck-heading_heading4' }
            ]
        },
        image: {
            toolbar: [
                'imageTextAlternative', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side'
            ]
        },
        table: {
            contentToolbar: [
                'tableColumn', 'tableRow', 'mergeTableCells'
            ]
        },
        link: {
            decorators: {
                openInNewTab: {
                    mode: 'manual',
                    label: 'Abrir en nueva pestaña',
                    attributes: {
                        target: '_blank',
                        rel: 'noopener noreferrer'
                    }
                }
            }
        },
        // Configuración de upload de imágenes
        simpleUpload: {
            uploadUrl: '{{ route("cms.media.upload") }}',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }
    })
    .then(editor => {
        window.editor = editor;

        // Sincronizar contenido del editor con el textarea al enviar el formulario
        document.getElementById('pageForm').addEventListener('submit', function(e) {
            document.getElementById('draft_content').value = editor.getData();
        });

        // Auto-guardar cada 2 minutos
        setInterval(() => {
            document.getElementById('draft_content').value = editor.getData();
            console.log('Auto-guardado preparado (recuerda hacer clic en Guardar Borrador)');
        }, 120000);
    })
    .catch(error => {
        console.error('Error al cargar CKEditor:', error);
        alert('Error al cargar el editor. Por favor recarga la página.');
    });
</script>

@endsection
