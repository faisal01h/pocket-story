<?php

namespace App\Http\Controllers;

use App\Models\LlmLimit;
use App\Models\RedeemCode;
use App\Models\RedeemCodeUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RedeemCodeController extends Controller
{
    public function redeem(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|exists:redeem_codes,code',
        ]);

        $user = $request->user();
        $code = RedeemCode::where('code', $validated['code'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->withCount('usages')
            ->firstOrFail();

        if ($code->usages_count >= $code->usage_limit) {
            return back()->withErrors(['code' => 'This code has reached its usage limit.']);
        }

        if (RedeemCodeUsage::where('redeem_code_id', $code->id)->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['code' => 'You have already redeemed this code.']);
        }

        DB::transaction(function () use ($user, $code) {
            // Record usage
            RedeemCodeUsage::create([
                'redeem_code_id' => $code->id,
                'user_id' => $user->id,
            ]);

            // Create/Update user limit
            // For simplicity, we create a new limit record for the user.
            // LlmService will aggregate all active limits.
            LlmLimit::create([
                'user_id' => $user->id,
                'llm_model_id' => $code->llm_model_id,
                'period' => $code->period,
                'max_tokens' => $code->max_tokens,
                'is_active' => true,
            ]);
        });

        return back()->with('success', 'Code redeemed successfully! Your token limit has been increased.');
    }
}
