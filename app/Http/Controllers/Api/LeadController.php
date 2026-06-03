<?php

namespace App\Http\Controllers\Api;

use App\Actions\Leads\CaptureLeadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Jobs\ProcessAiResponse;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request, CaptureLeadAction $captureLeadAction): JsonResponse
    {
        // 1. Create Lead synchronously
        $lead = $captureLeadAction->execute([
            'customer_name' => $request->validated('customer_name'),
            'customer_phone' => $request->validated('customer_phone'),
            'raw_message' => $request->validated('raw_message'),
            'source' => $request->validated('source') ?? 'api',
        ]);

        // 2. Dispatch AI processing asynchronously
        ProcessAiResponse::dispatch($lead);

        return response()->json([
            'message' => 'Lead received. AI processing started.',
            'data' => $lead,
        ], 201);
    }
}
