<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessExpiredPayment;
use App\Jobs\ProcessSuccessfulPayment;
use App\Services\XenditService;
use Illuminate\Http\Request;

class XenditWebhookController extends Controller
{
    public function __construct(protected XenditService $xenditService) {}

    /**
     * Handle Xendit webhook callbacks.
     */
    public function handle(Request $request)
    {
        // Verify webhook signature
        if (! $this->xenditService->verifyWebhookSignature($request)) {
            \Log::warning('Invalid Xendit webhook signature');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $webhookData = $request->all();
        $eventType = $webhookData['status'] ?? null;

        \Log::info('Xendit webhook received', ['event' => $eventType, 'invoice_id' => $webhookData['id'] ?? null]);

        // Dispatch appropriate job based on event type
        match ($eventType) {
            'PAID' => ProcessSuccessfulPayment::dispatch($webhookData),
            'EXPIRED' => ProcessExpiredPayment::dispatch($webhookData),
            'FAILED' => ProcessExpiredPayment::dispatch($webhookData),
            default => \Log::warning('Unhandled webhook event type: '.$eventType),
        };

        return response()->json(['success' => true]);
    }
}
