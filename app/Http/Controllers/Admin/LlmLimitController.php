<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LlmLimit;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LlmLimitController extends Controller
{
    public function index()
    {
        $limits = LlmLimit::with('user')->latest()->paginate(10);
        return Inertia::render('Admin/LlmLimits/Index', [
            'limits' => $limits
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/LlmLimits/Create', [
            'users' => User::all(['id', 'name', 'email'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'model_name' => 'nullable|string',
            'period' => 'required|in:daily,monthly,total',
            'max_tokens' => 'required|integer|min:1',
            'is_active' => 'required|boolean',
        ]);

        LlmLimit::create($validated);

        return redirect()->route('admin.llm-limits.index')->with('success', 'Limit created successfully.');
    }

    public function edit(LlmLimit $llmLimit)
    {
        return Inertia::render('Admin/LlmLimits/Edit', [
            'limit' => $llmLimit,
            'users' => User::all(['id', 'name', 'email'])
        ]);
    }

    public function update(Request $request, LlmLimit $llmLimit)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'model_name' => 'nullable|string',
            'period' => 'required|in:daily,monthly,total',
            'max_tokens' => 'required|integer|min:1',
            'is_active' => 'required|boolean',
        ]);

        $llmLimit->update($validated);

        return redirect()->route('admin.llm-limits.index')->with('success', 'Limit updated successfully.');
    }

    public function destroy(LlmLimit $llmLimit)
    {
        $llmLimit->delete();
        return redirect()->route('admin.llm-limits.index')->with('success', 'Limit deleted successfully.');
    }
}
