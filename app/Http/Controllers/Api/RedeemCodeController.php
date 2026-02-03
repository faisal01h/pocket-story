<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RedeemCodeUsageResource;
use App\Models\RedeemCodeUsage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RedeemCodeController extends Controller
{
    /**
     * Get the redeem history for the authenticated user.
     */
    public function history(Request $request): AnonymousResourceCollection
    {
        $history = RedeemCodeUsage::with(['redeemCode.llmModel'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate();

        return RedeemCodeUsageResource::collection($history);
    }
}
