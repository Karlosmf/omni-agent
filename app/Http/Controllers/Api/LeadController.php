<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Jobs\ProcessAiResponse;
use App\Services\AiConciergeService;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request, AiConciergeService $aiService): JsonResponse
    {
        // 1. Create Lead synchronously
        $lead = $aiService->createLead(
            $request->validated('customer_name'),
            $request->validated('customer_phone'),
            $request->validated('raw_message'),
            $request->validated('source') ?? 'api'
        );

        // 2. Dispatch AI processing asynchronously
        ProcessAiResponse::dispatch($lead);

        return response()->json([
            'message' => 'Lead received. AI processing started.',
            'data' => $lead,
        ], 201);
    }
}
