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
        Schema::create('rfid_reader_pairings', function (Blueprint $table) {
            $table->unsignedBigInteger('pair_id');
            $table->unsignedBigInteger('reader_id');
            $table->unsignedBigInteger('reader_position');

            $table->index(['pair_id']);
            $table->index(['reader_id']);
            $table->index(['reader_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_reader_pairings');
    }
};
