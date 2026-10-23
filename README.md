# Trippers Shield — Policy Request API

A Laravel REST API for receiving travel insurance policy requests, calculating premiums, and tracking policy request records.

## Technology Stack

- PHP 8.5
- Laravel 11+
- MySQL
- Laravel Sanctum
- PHPUnit

## Current Features

- Create policy requests through a REST API.
- Calculate premiums based on plan, travel duration, age, destination, and sum insured.
- Prevent duplicate requests using agent ID and client request ID.
- Retrieve a policy request by ID.
- List policy requests with pagination and optional status filtering.
- Validate incoming request data.

## Premium Calculation

| Plan | Base Rate per Day |
|---|---:|
| BASIC | ₹12 |
| PLUS | ₹20 |
| PREMIUM | ₹30 |

Additional multipliers:

- Age 0–17: 1.10
- Age 18–60: 1.00
- Age 61–70: 1.50
- Age 71+: 2.00
- Domestic destination: 1.00
- International destination: 1.80
- Sum insured ₹100,000: 1.00
- Sum insured ₹300,000: 1.25
- Sum insured ₹500,000: 1.50

Travel duration includes both the start and end dates. The calculated premium is rounded to two decimal places.

## API Endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/policy-requests` | Create a policy request |
| GET | `/api/policy-requests` | List requests with pagination |
| GET | `/api/policy-requests/{id}` | Retrieve a request by ID |

## Local Setup

1. Install PHP, Composer, and MySQL.
2. Clone this repository.
3. Install dependencies:

   ```bash
   composer install
   npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
