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
        Schema::table('llm_limits', function (Blueprint $table) {
            // Add hourly period option
            $table->enum('period', ['hourly', 'daily', 'monthly', 'total'])->default('daily')->change();

            // Add period_hours for custom hour intervals (e.g., 6, 5)
            $table->unsignedInteger('period_hours')->nullable()->after('period');

            // Add resets_at to track when the limit window resets
            $table->timestamp('resets_at')->nullable()->after('period_hours');

            $table->index('resets_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('llm_limits', function (Blueprint $table) {
            $table->dropColumn(['period_hours', 'resets_at']);
            $table->dropIndex(['resets_at']);
            $table->enum('period', ['daily', 'monthly', 'total'])->default('daily')->change();
        });
    }
};
