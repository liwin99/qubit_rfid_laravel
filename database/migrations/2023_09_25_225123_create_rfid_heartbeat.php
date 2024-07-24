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
        Schema::create('rfid_heartbeats', function (Blueprint $table) {
            $table->id(); // Auto-incremental primary key
            $table->string('reader_name');
            $table->string('ip_address')->nullable();
            $table->dateTime('heartbeat_datetime');
            $table->unsignedInteger('heartbeat_sequence_number');
            $table->dateTime('created_at')->useCurrent();

            $table->index(['reader_name']);
            $table->index(['heartbeat_datetime']);
            $table->index(['reader_name', 'heartbeat_datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_heartbeats');
    }
};
