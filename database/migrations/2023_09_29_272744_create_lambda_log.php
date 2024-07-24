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
        Schema::create('lambda_logs', function (Blueprint $table) {
            $table->id();
            $table->json('payload');
            $table->json('error_message');
            $table->dateTime('created_at')->useCurrent();

            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lambda_logs');
    }
};
