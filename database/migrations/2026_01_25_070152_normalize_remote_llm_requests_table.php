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
        Schema::table('remote_llm_requests', function (Blueprint $table) {
            $table->foreignId('llm_model_id')->nullable()->after('game_session_id')->constrained()->nullOnDelete();
            $table->dropColumn(['provider', 'model_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remote_llm_requests', function (Blueprint $table) {
            $table->dropForeign(['llm_model_id']);
            $table->dropColumn('llm_model_id');
            $table->string('provider')->after('game_session_id');
            $table->string('model_name')->after('provider');
        });
    }
};
