<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->dateTime('visit_datetime');
            $table->decimal('visit_latitud',10,8)->nullable();
            $table->decimal('visit_longitud',10,8)->nullable();
            $table->enum('visit_coords_status', ['0', '1'])->nullable()->comment('0-No Definido, 1-ok');
            $table->longText('job_description');

            $table->dateTime('arrival_datetime')->nullable();
            $table->decimal('arrival_latitud',10,8)->nullable();
            $table->decimal('arrival_longitud',10,8)->nullable();
            $table->enum('arrival_coords_status', ['0', '1'])->nullable()->comment('0-No Definido, 1-ok');

            $table->dateTime('closed_datetime')->nullable();
            $table->decimal('closed_latitud',10,8)->nullable();
            $table->decimal('closed_longitud',10,8)->nullable();
            $table->enum('closed_coords_status', ['0', '1'])->nullable()->comment('0-No Definido, 1-ok');

            $table->longText('closed_job_observation')->nullable();

            $table->longText('visit_json_coords')->nullable();
            $table->longText('arrival_json_coords')->nullable();
            $table->longText('closed_json_coords')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
}
