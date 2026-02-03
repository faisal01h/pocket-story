<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeRequest;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\XenditService;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(protected XenditService $xenditService) {}

    /**
     * List available subscription plans and current user subscription.
     */
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get();
        
        $currentSubscription = auth()->user()->activeSubscription()
            ->with(['subscriptionPlan'])
            ->first();

        return response()->json([
            'plans' => $plans,
            'current_subscription' => $currentSubscription,
        ]);
    }

    /**
     * Create a new subscription for the user.
     */
    public function store(SubscribeRequest $request): JsonResponse
    {
        $user = auth()->user();

        // Check if user already has an active subscription
        if ($user->hasActiveSubscription()) {
            return response()->json([
                'message' => 'You already have an active subscription',
            ], 403);
        }

        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

        // Create pending subscription
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
        ]);

        // Create Xendit invoice
        $result = $this->xenditService->createInvoice($subscription);

        if (! $result['success']) {
            $subscription->delete();

            return response()->json([
                'message' => 'Failed to create payment invoice',
                'error' => $result['error'],
            ], 500);
        }

        // Update subscription with invoice details
        $subscription->update([
            'xendit_invoice_id' => $result['invoice_id'],
        ]);

        return response()->json([
            'message' => 'Subscription created successfully. Please proceed to payment.',
            'invoice_url' => $result['invoice_url'],
            'expires_at' => $result['expires_at'],
        ]);
    }
}
