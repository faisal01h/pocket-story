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
        Schema::create('remote_llm_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('model_name');
            $table->longText('input_token')->nullable(); // Actual text content
            $table->longText('output_token')->nullable(); // Actual response content
            $table->integer('input_token_count');
            $table->integer('output_token_count');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remote_llm_requests');
    }
};
