<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LlmProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class LlmProviderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/LlmProviders/Index', [
            'providers' => LlmProvider::withCount('models')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/LlmProviders/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_url' => 'nullable|url|max:255',
            'api_key' => 'nullable|string|max:255',
            'secret_key' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        LlmProvider::create($validated);

        return redirect()->route('admin.llm-providers.index')
            ->with('success', 'LLM Provider created successfully.');
    }

    public function edit(LlmProvider $llmProvider): Response
    {
        return Inertia::render('Admin/LlmProviders/Edit', [
            'provider' => $llmProvider,
        ]);
    }

    public function update(Request $request, LlmProvider $llmProvider)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_url' => 'nullable|url|max:255',
            'api_key' => 'nullable|string|max:255',
            'secret_key' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $llmProvider->update($validated);

        return redirect()->route('admin.llm-providers.index')
            ->with('success', 'LLM Provider updated successfully.');
    }

    public function destroy(LlmProvider $llmProvider)
    {
        $llmProvider->delete();

        return redirect()->route('admin.llm-providers.index')
            ->with('success', 'LLM Provider deleted successfully.');
    }
}
