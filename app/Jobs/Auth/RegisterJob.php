<?php

namespace App\Jobs\Auth;

use App\Actions\Tenant\AddTenantMemberAction;
use App\Enums\PRFRole;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    /**
     * People already registered with another organisation join this one by proving they own
     * the account (same password); nobody can attach someone else's account to a tenant.
     */
    public function handle(): User
    {
        $email = strtolower((string) $this->data['email']);
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            if (!Hash::check((string) $this->data['password'], (string) $user->password)) {
                throw ValidationException::withMessages([
                    'email' => 'This email is already registered. Sign in with your existing password to join.',
                ]);
            }
        } else {
            $user = User::create([
                'name' => $this->data['name'],
                'email' => $email,
                'password' => Hash::make((string) $this->data['password']),
            ]);
        }

        if (!$user->hasRole(PRFRole::MEMBER)) {
            $user->assignRole(PRFRole::MEMBER);
        }

        if (tenancy()->initialized) {
            app(AddTenantMemberAction::class)->handle(tenancy()->tenant, $user, 'member');
        }

        return $user;
    }
}
