<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('game_sessions')
            ->whereNotNull('state_history')
            ->orderBy('id')
            ->chunkById(100, function ($sessions) {
                foreach ($sessions as $session) {
                    $history = json_decode($session->state_history, true);

                    if (is_array($history)) {
                        foreach ($history as $entry) {
                            \Illuminate\Support\Facades\DB::table('game_session_state_histories')->insert([
                                'game_session_id' => $session->id,
                                'role' => $entry['role'] ?? 'unknown',
                                'content' => $entry['content'] ?? '',
                                'created_at' => $session->created_at,
                                'updated_at' => $session->updated_at,
                            ]);
                        }
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To reverse, we'd need to re-aggregate the records back into JSON.
        // This is complex and might lose ordering if not careful.
        // For now, we'll implement a best-effort reversal.
        \Illuminate\Support\Facades\DB::table('game_sessions')->orderBy('id')->chunkById(100, function ($sessions) {
            foreach ($sessions as $session) {
                $history = \Illuminate\Support\Facades\DB::table('game_session_state_histories')
                    ->where('game_session_id', $session->id)
                    ->orderBy('id')
                    ->get(['role', 'content'])
                    ->map(fn ($item) => [
                        'role' => $item->role,
                        'content' => $item->content,
                    ])
                    ->toArray();

                if (! empty($history)) {
                    \Illuminate\Support\Facades\DB::table('game_sessions')
                        ->where('id', $session->id)
                        ->update(['state_history' => json_encode($history)]);
                }
            }
        });
    }
};
