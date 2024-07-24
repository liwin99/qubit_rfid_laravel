<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staging_worker_time_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reader_1_id');
            $table->string('reader_1_name');
            $table->unsignedBigInteger('reader_2_id')->nullable();
            $table->string('reader_2_name')->nullable();
            $table->string('epc');
            $table->unsignedBigInteger('project_id');
            $table->string('project_name');
            $table->dateTime('tag_read_datetime');
            $table->string('direction')->nullable();
            $table->date('period');
            $table->timestamps();

            $table->index(['tag_read_datetime', 'epc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staging_worker_time_logs');
    }
};
