<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUploadMetadataToJobsFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs_files', function (Blueprint $table) {
            $table->string('checksum', 64)->nullable()->after('original_extension');
            $table->timestamp('captured_at')->nullable()->after('checksum');
            $table->decimal('captured_latitude', 10, 7)->nullable()->after('captured_at');
            $table->decimal('captured_longitude', 10, 7)->nullable()->after('captured_latitude');
            $table->timestamp('uploaded_at')->nullable()->after('captured_longitude');
            $table->string('upload_source', 50)->nullable()->after('uploaded_at');
            $table->string('app_queue_id', 80)->nullable()->after('upload_source');

            $table->index(['job_id', 'checksum']);
            $table->index('uploaded_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs_files', function (Blueprint $table) {
            $table->dropIndex(['job_id', 'checksum']);
            $table->dropIndex(['uploaded_at']);

            $table->dropColumn([
                'checksum',
                'captured_at',
                'captured_latitude',
                'captured_longitude',
                'uploaded_at',
                'upload_source',
                'app_queue_id',
            ]);
        });
    }
}
