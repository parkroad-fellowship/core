<?php

namespace App\Jobs\Auth;

use App\Models\AppSetting;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Bus\Dispatchable;
use Laravel\Socialite\Facades\Socialite;

class LoginSocialLeaderJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): User
    {
        $data = $this->data;

        return match ($data['provider']) {
            'google' => $this->loginGoogleMember($data['access_token']),
            default => throw new Exception('Invalid provider'),
        };
    }

    private function loginGoogleMember(string $accessToken): User
    {
        $providerUser = Socialite::driver('google')->userFromToken($accessToken);

        if (! $providerUser) {
            throw new Exception('Invalid access token');
        }

        if (! $providerUser->email) {
            throw new Exception('Email not provided by provider');
        }

        $user = User::query()
            ->where('email', $providerUser->email)
            ->first();

        if (! $user) {
            throw new Exception('Access denied. Your email is not registered.');
        }

        $executiveRoles = AppSetting::get('general.executive_committee_roles', []);

        if (! $user->hasAnyRole($executiveRoles)) {
            throw new Exception('Access denied. You do not have an executive committee role.');
        }

        return $user;
    }
}
