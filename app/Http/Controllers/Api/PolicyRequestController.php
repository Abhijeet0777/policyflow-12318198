<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Services\PremiumCalculator;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PolicyRequestController extends Controller
{
    public function store(
        Request $request,
        PremiumCalculator $calculator
    ): JsonResponse {
        $data = $request->validate([
            'client_request_id' => ['required', 'string', 'max:100'],
            'agent_id' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'destination' => [
                'required',
                Rule::in(['DOMESTIC', 'INTERNATIONAL']),
            ],
            'trip_start' => ['required', 'date'],
            'trip_end' => ['required', 'date', 'after_or_equal:trip_start'],
            'plan_code' => ['required', Rule::in(['BASIC', 'PLUS', 'PREMIUM'])],
            'sum_insured' => ['required', 'integer', Rule::in([100000, 300000, 500000])],
        ]);

        $key = [
            'agent_id' => $data['agent_id'],
            'client_request_id' => $data['client_request_id'],
        ];

        $existing = Policy::where($key)->first();

        if ($existing) {
            return response()->json([
                'data' => $existing,
                'duplicate' => true,
            ], 200);
        }

        $premium = $calculator->calculate($data);

        try {
            $policy = DB::transaction(function () use ($data, $key, $premium) {
               $policy = Policy::create(array_merge($data, [
    'premium' => $premium,
    'status' => 'RECEIVED',
    'attempt_count' => 0,
]));

$policy->stateHistory()->create([
    'from_status' => null,
    'to_status' => 'RECEIVED',
    'reason' => 'Policy request created',
    'metadata' => ['source' => 'api'],
]);

return $policy;
            });
        } catch (QueryException $exception) {
            // A concurrent request may have inserted the same idempotency key.
            $existing = Policy::where($key)->first();

            if ($existing) {
                return response()->json([
                    'data' => $existing,
                    'duplicate' => true,
                ], 200);
            }

            throw $exception;
        }

        return response()->json([
            'data' => $policy,
            'duplicate' => false,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => [
                'sometimes',
                Rule::in([
                    'RECEIVED',
                    'VALIDATED',
                    'SUBMITTED',
                    'PENDING_CONFIRMATION',
                    'ISSUED',
                    'FAILED',
                    'NEEDS_RECONCILIATION',
                ]),
            ],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Policy::query()->latest();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return response()->json(
            $query->paginate($filters['per_page'] ?? 15)
        );
    }

   public function show(int $id): JsonResponse
{
    $policy = Policy::with('stateHistory')->findOrFail($id);

    return response()->json([
        'data' => $policy,
        'audit_trail' => $policy->stateHistory,
    ]);
}
}
