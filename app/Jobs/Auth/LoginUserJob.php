<?php

namespace App\Jobs\Auth;

use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Auth;

class LoginUserJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $validated
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): User
    {
        try {
            $validated = $this->validated;

            if (! Auth::attempt($validated)) {
                throw new \Exception('Invalid credentials');
            }

            return User::query()
                ->where('email', $validated['email'])
                ->first();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
