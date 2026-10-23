<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Policy extends Model
{
    protected $table = 'policy_requests';

    protected $fillable = [
        'client_request_id',
        'agent_id',
        'customer_name',
        'date_of_birth',
        'destination',
        'trip_start',
        'trip_end',
        'plan_code',
        'sum_insured',
        'premium',
        'status',
        'provider_reference',
        'policy_number',
        'last_response',
        'attempt_count',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'trip_start' => 'date',
            'trip_end' => 'date',
            'premium' => 'decimal:2',
            'last_response' => 'array',
        ];
    }

    public function stateHistory(): HasMany
    {
        return $this->hasMany(PolicyStateHistory::class);
    }
}
