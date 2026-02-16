@extends('layout')

@section('Content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card" style="border: none; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="card-header text-white d-flex justify-content-between align-items-center border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0;">
                    <h4 class="mb-0 fw-bold">
                        <i class="fas fa-cog me-2"></i>Configuración de API
                    </h4>
                    <a href="{{ route('cms.index') }}" class="btn btn-light" style="border-radius: 10px; font-weight: 500;">
                        <i class="fas fa-arrow-left me-2"></i>Volver al CMS
                    </a>
                </div>
                <div class="card-body" style="background-color: #f8f9fa;">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px; border-left: 4px solid #10b981;">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 12px; border-left: 4px solid #ef4444;">
                            <i class="fas fa-exclamation-circle me-2"></i>Por favor revisa los errores:
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <form action="{{ route('cms.api-config.update') }}" method="POST">
                        @csrf
                        
                        <div class="card mb-4" style="border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                            <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-key me-2"></i>Configuración de Acceso a API</h5>
                            </div>
                            <div class="card-body" style="background: white;">
                                <!-- URL de Login API -->
                                <div class="form-group mb-4">
                                    <label class="form-label font-weight-bold">
                                        <i class="fas fa-link me-2 text-info"></i>URL API Login
                                        <small class="text-muted">(Endpoint de autenticación)</small>
                                    </label>
                                    <input type="url" name="url_api_login" class="form-control @error('url_api_login') is-invalid @enderror" 
                                        value="{{ old('url_api_login', $configArray['url_api_login']) }}" 
                                        placeholder="https://api.example.com/login" style="border-radius: 10px;">
                                    @error('url_api_login')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Usuario Dev API -->
                                <div class="form-group mb-4">
                                    <label class="form-label font-weight-bold">
                                        <i class="fas fa-user-tie me-2 text-info"></i>Usuario Dev API
                                        <small class="text-muted">(Usuario de desarrollo/staging)</small>
                                    </label>
                                    <input type="text" name="user_dev_api" class="form-control @error('user_dev_api') is-invalid @enderror" 
                                        value="{{ old('user_dev_api', $configArray['user_dev_api']) }}" 
                                        placeholder="usuario_desarrollo" style="border-radius: 10px;">
                                    @error('user_dev_api')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Password Dev API -->
                                <div class="form-group mb-4">
                                    <label class="form-label font-weight-bold">
                                        <i class="fas fa-lock me-2 text-info"></i>Contraseña Dev API
                                        <small class="text-muted">(Contraseña de desarrollo/staging)</small>
                                    </label>
                                    <input type="password" name="pass_dev_api" class="form-control @error('pass_dev_api') is-invalid @enderror" 
                                        value="{{ old('pass_dev_api', $configArray['pass_dev_api']) }}" 
                                        placeholder="●●●●●●●●" style="border-radius: 10px;">
                                    @error('pass_dev_api')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4" style="border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                            <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-shield-alt me-2"></i>Configuración de Producción</h5>
                            </div>
                            <div class="card-body" style="background: white;">
                                <!-- Usuario API Producción -->
                                <div class="form-group mb-4">
                                    <label class="form-label font-weight-bold">
                                        <i class="fas fa-user me-2 text-success"></i>Usuario API
                                        <small class="text-muted">(Usuario de producción)</small>
                                    </label>
                                    <input type="text" name="user_api" class="form-control @error('user_api') is-invalid @enderror" 
                                        value="{{ old('user_api', $configArray['user_api']) }}" 
                                        placeholder="usuario_produccion" style="border-radius: 10px;">
                                    @error('user_api')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Password API Producción -->
                                <div class="form-group mb-4">
                                    <label class="form-label font-weight-bold">
                                        <i class="fas fa-lock me-2 text-success"></i>Contraseña API
                                        <small class="text-muted">(Contraseña de producción)</small>
                                    </label>
                                    <input type="password" name="pass_api" class="form-control @error('pass_api') is-invalid @enderror" 
                                        value="{{ old('pass_api', $configArray['pass_api']) }}" 
                                        placeholder="●●●●●●●●" style="border-radius: 10px;">
                                    @error('pass_api')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- ID Empresa API -->
                                <div class="form-group mb-4">
                                    <label class="form-label font-weight-bold">
                                        <i class="fas fa-building me-2 text-success"></i>ID Empresa API
                                        <small class="text-muted">(Identificador único de la empresa en el sistema)</small>
                                    </label>
                                    <input type="text" name="id_empresa_api" class="form-control @error('id_empresa_api') is-invalid @enderror" 
                                        value="{{ old('id_empresa_api', $configArray['id_empresa_api']) }}" 
                                        placeholder="12345" style="border-radius: 10px;">
                                    @error('id_empresa_api')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4" style="border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                            <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-map me-2"></i>Configuración Adicional</h5>
                            </div>
                            <div class="card-body" style="background: white;">
                                <!-- Google API Key -->
                                <div class="form-group mb-4">
                                    <label class="form-label font-weight-bold">
                                        <i class="fab fa-google me-2 text-danger"></i>Google API Key
                                        <small class="text-muted">(Clave de API de Google)</small>
                                    </label>
                                    <input type="text" name="google_api_key" class="form-control @error('google_api_key') is-invalid @enderror" 
                                        value="{{ old('google_api_key', $configArray['google_api_key']) }}" 
                                        placeholder="AIzaSyD..." style="border-radius: 10px;">
                                    @error('google_api_key')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="card" style="border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                            <div class="card-body" style="background: white;">
                                <div class="d-flex gap-2 justify-content-between">
                                    <div>
                                        <a href="{{ route('cms.index') }}" class="btn btn-secondary" style="border-radius: 10px; font-weight: 500;">
                                            <i class="fas fa-times me-2"></i>Cancelar
                                        </a>
                                    </div>
                                    <button type="submit" class="btn btn-success" style="border-radius: 10px; font-weight: 500; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
                                        <i class="fas fa-save me-2"></i>Guardar Configuración
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="alert alert-info mt-4" style="border-radius: 12px; border-left: 4px solid #3b82f6;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nota de Seguridad:</strong> Las contraseñas se guardan en la base de datos. Asegúrate de usar conexiones HTTPS seguras y considera implementar encriptación para los datos sensibles.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-group label {
        margin-bottom: 0.75rem;
    }
    
    .form-control {
        border: 1.5px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .form-control.is-invalid {
        border-color: #ef4444;
    }
    
    .form-control.is-invalid:focus {
        border-color: #ef4444;
        box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.25);
    }
    
    .invalid-feedback {
        display: block;
    }
</style>
@endsection
