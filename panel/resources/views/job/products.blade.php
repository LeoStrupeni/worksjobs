<div class="modal fade" id="addproducts" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title">
                    <i class="fas fa-box me-2"></i>Agregar Productos a la Tarea
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="formaddproducts" method="POST" action="">
                @csrf
                @method('PUT')
                <input type="hidden" name="action_type" value="products_only">
                
                <!-- Spinner de carga -->
                <div id="modal-body-addproducts-roller" class="modal-body">
                    <div style="display:block;" class="text-center">
                        <br><br>
                        <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                        <br><br><br>
                    </div>
                </div>
                
                <div id="modal-body-addproducts" class="modal-body p-4 d-none">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Tarea:</strong> <span id="addproducts_task_name"></span>
                    </div>
                    
                    <!-- Card Productos -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title d-flex align-items-center mb-3">
                                <span class="rounded-circle bg-primary bg-opacity-10 p-2 me-2">
                                    <i class="fas fa-box text-primary me-2"></i>
                                </span>
                                Seleccionar Productos
                            </h6>
                            
                            <div class="row">
                                <div class="col-md-5">
                                    <label for="product_id_add" class="form-label fw-semibold">
                                        Producto
                                        <span id="spinner_product_add" class="d-none">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        </span>
                                    </label>
                                    <select class="form-control selectpicker searchvar" 
                                        id="product_id_add" 
                                        data-live-search="true" 
                                        data-size="4" 
                                        data-dropup-auto="false"
                                        data-none-selected-text="Seleccione un producto" 
                                        data-none-results-text="No hay resultados coincidentes">
                                        <option></option>
                                        @foreach (Session::get('products') as $p)
                                            <option value="{{$p->id}}">{{$p->codigo}} - {{$p->descripcion}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="unit_type_add" class="form-label fw-semibold">Tipo de Unidad</label>
                                    <select class="form-control" id="unit_type_add">
                                        <option value="Unidad">Unidad</option>
                                        <option value="Rollo">Rollo</option>
                                        <option value="Metros">Metros</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="quantity_add" class="form-label fw-semibold">Cantidad</label>
                                    <input type="number" class="form-control" id="quantity_add" min="0.01" step="0.01" value="1.00">
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary w-100" onclick="addProductToJob('add')">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Lista de productos agregados -->
                            <div id="products_list_add">
                                <!-- Los productos se agregarán aquí dinámicamente -->
                            </div>
                            
                            <!-- Productos ocultos para enviar en el formulario -->
                            <div id="products_hidden_add"></div>
                        </div>
                    </div>
                </div>

                <div id="modal-foot-addproducts" class="modal-footer border-0 d-none">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar Productos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
