<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RedeemCodeUsage;
use App\Models\RemoteLlmRequest;
use App\Models\User;
use App\Services\GoogleGenAIService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $users = $query->with('roles')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load([
            'roles',
            'gameSessions' => fn ($q) => $q->with('game')->latest()->take(10),
        ]);

        $redeems = RedeemCodeUsage::where('user_id', $user->id)
            ->with('redeemCode')
            ->latest()
            ->get();

        $llmRequests = RemoteLlmRequest::where('user_id', $user->id)
            ->with(['llmModel.provider', 'gameSession.game'])
            ->latest()
            ->take(20)
            ->get();

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'redeems' => $redeems,
            'llmRequests' => $llmRequests,
            'availableRoles' => Role::all(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user->syncRoles($validated['roles'] ?? []);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User roles updated successfully.');
    }

    public function summarize(User $user, GoogleGenAIService $aiService)
    {
        // Gather user data for analysis
        $sessions = $user->gameSessions()->with(['game', 'stateHistories'])->latest()->take(5)->get();
        $requests = RemoteLlmRequest::where('user_id', $user->id)->latest()->take(20)->get();

        $data = [
            'user' => [
                'name' => $user->name,
                'total_sessions' => $user->gameSessions()->count(),
            ],
            'recent_sessions' => $sessions->map(fn ($s) => [
                'game' => $s->game->title,
                'actions' => $s->stateHistories->where('role', 'user')->pluck('content')->take(10),
            ]),
            'llm_usage' => [
                'total_requests' => $requests->count(),
                'avg_input' => $requests->avg('input_token_count'),
                'avg_output' => $requests->avg('output_token_count'),
            ],
        ];

        try {
            $summary = $aiService->summarizeUserActivity(json_encode($data), 'gemini-2.5-pro', auth()->id());

            return redirect()
                ->route('admin.users.show', $user)
                ->with('ai_summary', $summary);
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.users.show', $user)
                ->withErrors(['ai_error' => 'Failed to generate summary: '.$e->getMessage()]);
        }
    }
}
