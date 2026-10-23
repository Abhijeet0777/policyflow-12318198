<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class PremiumCalculator
{
    public function calculate(array $data): string
    {
        $rates = [
            'BASIC' => '12.00',
            'PLUS' => '20.00',
            'PREMIUM' => '30.00',
        ];

        $ageMultipliers = [
            'child' => 1.10,
            'adult' => 1.00,
            'senior' => 1.50,
            'elderly' => 2.00,
        ];

        $coverageMultipliers = [
            100000 => 1.00,
            300000 => 1.25,
            500000 => 1.50,
        ];

        $plan = strtoupper($data['plan_code']);
        $destination = strtoupper($data['destination']);
        $coverage = (int) $data['sum_insured'];

        if (!isset($rates[$plan])) {
            throw new InvalidArgumentException('Unsupported plan code.');
        }

        if (!in_array($destination, ['DOMESTIC', 'INTERNATIONAL'], true)) {
            throw new InvalidArgumentException('Unsupported destination.');
        }

        if (!isset($coverageMultipliers[$coverage])) {
            throw new InvalidArgumentException('Unsupported sum insured.');
        }

        $birthDate = Carbon::parse($data['date_of_birth']);
        $tripStart = Carbon::parse($data['trip_start'])->startOfDay();
        $tripEnd = Carbon::parse($data['trip_end'])->startOfDay();

        if ($tripEnd->lt($tripStart)) {
            throw new InvalidArgumentException(
                'Trip end must be on or after trip start.'
            );
        }

        $age = (int) $birthDate->diffInYears($tripStart);

        $ageMultiplier = match (true) {
            $age <= 17 => $ageMultipliers['child'],
            $age <= 60 => $ageMultipliers['adult'],
            $age <= 70 => $ageMultipliers['senior'],
            default => $ageMultipliers['elderly'],
        };

        $destinationMultiplier = $destination === 'DOMESTIC' ? 1.00 : 1.80;

        $travelDays = max(1, $tripStart->diffInDays($tripEnd) + 1);

        // Calculate using decimal arithmetic to avoid floating-point drift.
        $baseCents = (int) round((float) $rates[$plan] * 100);
        $raw = $baseCents
            * $travelDays
            * $ageMultiplier
            * $destinationMultiplier
            * $coverageMultipliers[$coverage];

        $roundedCents = (int) round($raw, 0, PHP_ROUND_HALF_UP);

        return number_format($roundedCents / 100, 2, '.', '');
    }
}
