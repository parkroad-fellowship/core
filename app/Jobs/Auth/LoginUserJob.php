<?php

namespace App\Jobs\Auth;

use App\Models\User;
use Exception;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class LoginUserJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $validated
     */
    public function __construct(
        public array $validated,
    ) {}

    /**
     * Checks credentials without starting a session; the caller issues a Sanctum token.
     */
    public function handle(): User
    {
        $credentials = [
            'email' => strtolower(Arr::string($this->validated, 'email')),
            'password' => Arr::string($this->validated, 'password'),
        ];

        if (!Auth::once($credentials)) {
            throw new Exception('Invalid credentials');
        }

        $user = Auth::user();

        if (!$user instanceof User) {
            throw new Exception('Invalid credentials');
        }

        return $user;
    }
}
