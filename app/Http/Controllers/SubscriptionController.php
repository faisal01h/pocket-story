<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeRequest;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\XenditService;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function __construct(protected XenditService $xenditService) {}

    /**
     * Display available subscription plans.
     */
    public function index()
    {
        return Inertia::render('subscription/index', [
            'plans' => SubscriptionPlan::active()->orderBy('sort_order')->get(),
            'currentSubscription' => auth()->user()->activeSubscription()->with('subscriptionPlan')->first(),
        ]);
    }

    /**
     * Display the user's current subscription.
     */
    public function show()
    {
        $subscription = auth()->user()->activeSubscription()->with('subscriptionPlan')->first();

        return Inertia::render('subscription/show', [
            'subscription' => $subscription,
        ]);
    }

    /**
     * Subscribe to a plan.
     */
    public function subscribe(SubscribeRequest $request)
    {
        $user = auth()->user();

        // Check if user already has an active subscription
        if ($user->hasActiveSubscription()) {
            return back()->with('error', 'You already have an active subscription');
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

            return back()->with('error', 'Failed to create payment invoice: '.$result['error']);
        }

        // Update subscription with invoice details
        $subscription->update([
            'xendit_invoice_id' => $result['invoice_id'],
        ]);

        // Redirect to invoice URL
        return redirect($result['invoice_url']);
    }

    /**
     * Cancel the subscription.
     */
    public function cancel()
    {
        $subscription = auth()->user()->activeSubscription()->first();

        if (! $subscription) {
            return back()->with('error', 'No active subscription found');
        }

        $subscription->cancel();

        return redirect()->route('subscription.index')->with('success', 'Subscription cancelled successfully');
    }
}
