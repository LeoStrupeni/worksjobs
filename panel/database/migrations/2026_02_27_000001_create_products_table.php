<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // Campos de Colppy
            $table->integer('colppy_id')->nullable()->unique()->comment('ID del item en Colppy');
            $table->integer('colppy_empresa_id')->nullable()->comment('ID de empresa en Colppy');
            
            // Información básica del producto
            $table->string('codigo', 100)->nullable()->comment('Código del producto');
            $table->string('descripcion', 255)->nullable()->comment('Descripción del producto');
            $table->text('detalle')->nullable()->comment('Detalle adicional del producto');
            
            // Cuentas contables
            $table->string('cta_costo_ventas', 50)->nullable()->comment('Cuenta de costo de ventas');
            $table->string('cta_ingreso_ventas', 50)->nullable()->comment('Cuenta de ingreso de ventas');
            $table->string('cta_inventario', 50)->nullable()->comment('Cuenta de inventario');
            $table->string('desc_cta_inventario', 255)->nullable()->comment('Descripción cuenta inventario');
            $table->string('desc_cta_costo_ventas', 255)->nullable()->comment('Descripción cuenta costo ventas');
            $table->string('desc_cta_ingreso_ventas', 255)->nullable()->comment('Descripción cuenta ingreso ventas');
            
            // Stock y precios
            $table->decimal('stock_minimo', 10, 2)->nullable()->default(0)->comment('Stock mínimo');
            $table->decimal('disponibilidad', 10, 2)->nullable()->default(0)->comment('Stock disponible');
            $table->decimal('costo_calculado', 10, 2)->nullable()->default(0)->comment('Costo calculado');
            $table->decimal('ultimo_precio_compra', 10, 2)->nullable()->default(0)->comment('Último precio de compra');
            $table->decimal('precio_venta', 10, 2)->nullable()->comment('Precio de venta');
            
            // Información fiscal y unidades
            $table->string('iva', 10)->nullable()->comment('Porcentaje de IVA');
            $table->string('unidad_medida', 50)->nullable()->comment('Unidad de medida');
            
            // Tipo y clasificación
            $table->string('tipo_item', 1)->default('P')->comment('P=Producto, S=Servicio, K=Kit');
            $table->boolean('es_kit')->default(false)->comment('Si es un kit de productos');
            
            // Fechas
            $table->date('fecha_alta')->nullable()->comment('Fecha de alta en Colppy');
            $table->date('fecha_baja')->nullable()->comment('Fecha de baja en Colppy');
            
            // Otros
            $table->text('comentario_factura')->nullable()->comment('Comentario para factura');
            
            // Control de sincronización
            $table->boolean('is_from_colppy')->default(false)->comment('Si proviene de Colppy');
            $table->timestamp('colppy_updated_at')->nullable()->comment('Última actualización en Colppy');
            
            // Timestamps de Laravel
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('codigo');
            $table->index('descripcion');
            $table->index('tipo_item');
            $table->index('is_from_colppy');
            $table->index('colppy_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
