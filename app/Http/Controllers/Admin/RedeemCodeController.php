<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LlmProvider;
use App\Models\RedeemCode;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RedeemCodeController extends Controller
{
    public function index()
    {
        $codes = RedeemCode::with('llmModel.provider')->withCount('usages')->latest()->paginate(20);

        return Inertia::render('Admin/RedeemCodes/Index', [
            'codes' => $codes,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/RedeemCodes/Create', [
            'providers' => LlmProvider::with('models')->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:redeem_codes,code',
            'llm_model_id' => 'nullable|exists:llm_models,id',
            'max_tokens' => 'required|integer|min:1',
            'period' => 'required|in:daily,monthly,total',
            'usage_limit' => 'required|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'required|boolean',
        ]);

        RedeemCode::create($validated);

        return redirect()->route('admin.redeem-codes.index')->with('success', 'Redeem code created successfully.');
    }

    public function edit(RedeemCode $redeemCode)
    {
        $redeemCode->load('llmModel.provider');

        return Inertia::render('Admin/RedeemCodes/Edit', [
            'redeemCode' => $redeemCode,
            'providers' => LlmProvider::with('models')->where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, RedeemCode $redeemCode)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:redeem_codes,code,'.$redeemCode->id,
            'llm_model_id' => 'nullable|exists:llm_models,id',
            'max_tokens' => 'required|integer|min:1',
            'period' => 'required|in:daily,monthly,total',
            'usage_limit' => 'required|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'required|boolean',
        ]);

        $redeemCode->update($validated);

        return redirect()->route('admin.redeem-codes.index')->with('success', 'Redeem code updated successfully.');
    }

    public function destroy(RedeemCode $redeemCode)
    {
        $redeemCode->delete();

        return redirect()->route('admin.redeem-codes.index')->with('success', 'Redeem code deleted successfully.');
    }
}
