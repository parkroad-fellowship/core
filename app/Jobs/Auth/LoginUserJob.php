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

    public function __construct(
        public array $validated,
    ) {}

    public function handle(): User
    {
        try {
            $validated = $this->validated;

            if (!Auth::attempt(Arr::only($validated, ['email', 'password']))) {
                throw new Exception('Invalid credentials');
            }

            return User::query()->where('email', strtolower($validated['email']))->first();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
