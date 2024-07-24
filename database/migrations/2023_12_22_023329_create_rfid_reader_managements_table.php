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
        Schema::create('rfid_reader_managements', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('location_1_id');
            $table->unsignedBigInteger('location_2_id');
            $table->unsignedBigInteger('location_3_id')->nullable();
            $table->unsignedBigInteger('location_4_id')->nullable();
            $table->boolean('used_for_attendance')->default(true);
            $table->timestamps();

            $table->index(['name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_reader_management');
    }
};
