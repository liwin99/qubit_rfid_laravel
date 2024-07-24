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
        Schema::create('rfid_tag_reads', function (Blueprint $table) {
            $table->id(); // Auto-incremental primary key
            $table->string('reader_name');
            $table->string('ip_address')->nullable();
            $table->string('epc');
            $table->string('bank_data')->nullable();
            $table->unsignedInteger('antenna')->nullable();
            $table->unsignedInteger('read_count');
            $table->unsignedInteger('protocol')->nullable();
            $table->integer('rssi')->nullable();
            $table->dateTime('tag_read_datetime');
            $table->bigInteger('first_seen_timestamp');
            $table->bigInteger('last_seen_timestamp');
            $table->string('unique_hash', 64)->unique();
            $table->dateTime('created_at')->useCurrent();

            $table->index(['tag_read_datetime']);
            $table->index(['epc']);
            $table->index(['reader_name']);
            $table->index(['reader_name', 'epc', 'tag_read_datetime']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_tag_reads');
    }
};
