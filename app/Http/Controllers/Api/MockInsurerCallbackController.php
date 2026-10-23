<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MockInsurerCallbackController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider_reference' => ['required', 'string', 'max:255'],
            'event_id' => ['required', 'string', 'max:255'],
            'event' => ['required', 'in:ISSUED,FAILED'],
            'policy_number' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $policy = Policy::where('provider_reference', $data['provider_reference'])
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = $policy->status;

            if ($policy->status === 'ISSUED') {
                return ['policy' => $policy, 'duplicate' => true];
            }

            if (!in_array($policy->status, ['SUBMITTED', 'PENDING_CONFIRMATION'], true)) {
                return ['policy' => $policy, 'duplicate' => false, 'ignored' => true];
            }

            if ($data['event'] === 'ISSUED') {
                $policy->status = 'ISSUED';
                $policy->policy_number = $data['policy_number']
                    ?? 'POL-' . Str::upper(Str::random(12));
            } else {
                $policy->status = 'FAILED';
            }

            $policy->last_response = [
                'event_id' => $data['event_id'],
                'event' => $data['event'],
                'message' => $data['message'] ?? null,
            ];
            $policy->save();

            $policy->stateHistory()->create([
                'from_status' => $previousStatus,
                'to_status' => $policy->status,
                'reason' => 'Mock insurer callback: ' . $data['event'],
                'metadata' => [
                    'event_id' => $data['event_id'],
                    'provider_reference' => $data['provider_reference'],
                ],
            ]);

            return ['policy' => $policy, 'duplicate' => false];
        });

        return response()->json(['data' => $result['policy'], 'duplicate' => $result['duplicate']] + (isset($result['ignored']) ? ['ignored' => true] : []));
    }
}
