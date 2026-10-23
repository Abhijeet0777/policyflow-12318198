<?php

namespace Tests\Feature;

use App\Models\Policy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function policyPayload(): array
    {
        return [
            'client_request_id' => 'TEST-1001',
            'agent_id' => 'AGENT-TEST',
            'customer_name' => 'Test Customer',
            'date_of_birth' => '1998-04-10',
            'destination' => 'DOMESTIC',
            'trip_start' => '2026-12-01',
            'trip_end' => '2026-12-02',
            'plan_code' => 'BASIC',
            'sum_insured' => 100000,
        ];
    }

    public function test_duplicate_policy_intake_returns_existing_policy(): void
    {
        $first = $this->postJson('/api/policy-requests', $this->policyPayload());

        $first->assertCreated()
            ->assertJsonPath('duplicate', false);

        $second = $this->postJson('/api/policy-requests', $this->policyPayload());

        $second->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertDatabaseCount('policy_requests', 1);
    }

    public function test_duplicate_issuance_callback_does_not_issue_policy_twice(): void
    {
        $policy = Policy::create([
            ...$this->policyPayload(),
            'premium' => '24.00',
            'status' => 'SUBMITTED',
            'provider_reference' => 'MOCK-TEST-1001',
            'attempt_count' => 1,
        ]);

        $callback = [
            'provider_reference' => 'MOCK-TEST-1001',
            'event_id' => 'EVENT-TEST-1001',
            'event' => 'ISSUED',
            'policy_number' => 'POL-TEST-1001',
            'message' => 'Test policy issued',
        ];

        $this->postJson('/api/mock-insurer/callback', $callback)
            ->assertOk()
            ->assertJsonPath('duplicate', false)
            ->assertJsonPath('data.status', 'ISSUED');

        $this->postJson('/api/mock-insurer/callback', $callback)
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('data.policy_number', 'POL-TEST-1001');

        $this->assertDatabaseHas('policy_requests', [
            'id' => $policy->id,
            'status' => 'ISSUED',
            'policy_number' => 'POL-TEST-1001',
        ]);

        $this->assertDatabaseCount('policy_state_histories', 1);
    }
}
