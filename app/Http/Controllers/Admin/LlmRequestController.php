<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class LlmRequestController extends Controller
{
    public function index()
    {
        $requests = \App\Models\RemoteLlmRequest::with('user')
            ->latest()
            ->paginate(15);

        return \Inertia\Inertia::render('Admin/LlmRequests/Index', [
            'requests' => $requests,
        ]);
    }

    public function show(\App\Models\RemoteLlmRequest $llmRequest)
    {
        $llmRequest->load(['user', 'gameSession.game']);

        return \Inertia\Inertia::render('Admin/LlmRequests/Show', [
            'request' => $llmRequest,
        ]);
    }
}
