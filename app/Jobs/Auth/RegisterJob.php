<?php

namespace App\Jobs\Auth;

use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Hash;

class RegisterJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): User
    {
        $data = $this->data;

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('member');

        return $user;
    }
}
