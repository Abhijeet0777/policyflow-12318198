<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PolicySubmissionController extends Controller
{
    public function submit(int $id): JsonResponse
    {
        $policy = DB::transaction(function () use ($id) {
            $policy = Policy::whereKey($id)->lockForUpdate()->firstOrFail();

            if (in_array($policy->status, [
                'SUBMITTED', 'PENDING_CONFIRMATION', 'ISSUED'
            ], true)) {
                return $policy;
            }

            if (!in_array($policy->status, [
                'RECEIVED', 'VALIDATED', 'FAILED'
            ], true)) {
                return $policy;
            }

            $previousStatus = $policy->status;
            $policy->attempt_count++;

            $scenario = request()->input('scenario', 'SUCCESS');

            if ($scenario === 'TEMPORARY_FAILURE') {
                $policy->status = 'FAILED';
                $policy->last_response = [
                    'scenario' => $scenario,
                    'message' => 'Temporary mock insurer failure. Retry is allowed.',
                ];
            } else {
                $policy->status = 'SUBMITTED';
                $policy->provider_reference = $policy->provider_reference
                    ?? 'MOCK-' . strtoupper(bin2hex(random_bytes(6)));
                $policy->last_response = [
                    'scenario' => 'SUCCESS',
                    'message' => 'Mock insurer accepted the submission.',
                ];
            }

            $policy->save();

            $policy->stateHistory()->create([
                'from_status' => $previousStatus,
                'to_status' => $policy->status,
                'reason' => 'Mock insurer submission: ' . $scenario,
                'metadata' => [
                    'provider_reference' => $policy->provider_reference,
                    'attempt' => $policy->attempt_count,
                    'scenario' => $scenario,
                ],
            ]);

            return $policy;
        });

        return response()->json(['data' => $policy]);
    }
}
