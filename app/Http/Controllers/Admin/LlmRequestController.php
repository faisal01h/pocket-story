<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RemoteLlmRequest;
use Inertia\Inertia;
use Inertia\Response;

class LlmRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        \Illuminate\Support\Facades\Gate::authorize('viewAny', RemoteLlmRequest::class);

        $requests = RemoteLlmRequest::with(['user', 'llmModel.provider'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/LlmRequests/Index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(RemoteLlmRequest $llmRequest): Response
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $llmRequest);

        $llmRequest->load(['user', 'gameSession.game', 'llmModel.provider']);

        return Inertia::render('Admin/LlmRequests/Show', [
            'request' => $llmRequest,
        ]);
    }
}
