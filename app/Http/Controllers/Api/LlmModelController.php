<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LlmModelResource;
use App\Models\LlmModel;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LlmModelController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $models = LlmModel::where('is_active', true)
            ->with('provider')
            ->get();

        return LlmModelResource::collection($models);
    }
}
