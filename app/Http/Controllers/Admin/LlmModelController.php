<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LlmModel;
use App\Models\LlmProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LlmModelController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/LlmModels/Index', [
            'models' => LlmModel::with('provider')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/LlmModels/Create', [
            'providers' => LlmProvider::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'llm_provider_id' => 'required|exists:llm_providers,id',
            'name' => 'required|string|max:255',
            'identifier' => 'required|string|max:255',
            'endpoint_url' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        LlmModel::create($validated);

        return redirect()->route('admin.llm-models.index')
            ->with('success', 'LLM Model created successfully.');
    }

    public function edit(LlmModel $llmModel): Response
    {
        return Inertia::render('Admin/LlmModels/Edit', [
            'model' => $llmModel,
            'providers' => LlmProvider::all(),
        ]);
    }

    public function update(Request $request, LlmModel $llmModel)
    {
        $validated = $request->validate([
            'llm_provider_id' => 'required|exists:llm_providers,id',
            'name' => 'required|string|max:255',
            'identifier' => 'required|string|max:255',
            'endpoint_url' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $llmModel->update($validated);

        return redirect()->route('admin.llm-models.index')
            ->with('success', 'LLM Model updated successfully.');
    }

    public function destroy(LlmModel $llmModel)
    {
        $llmModel->delete();

        return redirect()->route('admin.llm-models.index')
            ->with('success', 'LLM Model deleted successfully.');
    }
}
