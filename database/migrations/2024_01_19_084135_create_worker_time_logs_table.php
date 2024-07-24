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
        Schema::create('worker_time_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reader_1_id')->nullable();
            $table->string('reader_1_name')->nullable();
            $table->unsignedBigInteger('reader_2_id')->nullable();
            $table->string('reader_2_name')->nullable();
            $table->string('epc');
            $table->unsignedBigInteger('project_id');
            $table->string('project_name');
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            $table->date('period');
            $table->dateTime('last_tag_read');
            $table->dateTime('last_synced_to_tms')->nullable();
            $table->timestamps();

            $table->index(['last_tag_read', 'epc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_time_logs');
    }
};
