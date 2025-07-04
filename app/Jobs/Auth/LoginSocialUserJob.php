<?php

namespace App\Jobs\Auth;

use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class LoginSocialUserJob
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
            default => throw new \Exception('Invalid provider'),
        };
    }

    private function loginGoogleMember(string $accessToken): User
    {
        $providerUser = Socialite::driver('google')->userFromToken($accessToken);

        if (! $providerUser) {
            throw new \Exception('Invalid access token');
        }

        if (! $providerUser->email) {
            throw new \Exception('Email not provided by provider');
        }

        if (Str::doesntContain($providerUser->email, '@parkroadfellowship.org')) {
            throw new \Exception('Invalid email. Must be a Parkroad Fellowship email');
        }

        // Check if email is in exclusion list
        $excludedEmails = config('prf.app.excluded_emails', []);
        if (in_array($providerUser->email, $excludedEmails)) {
            throw new \Exception('Access denied. This is an administrative email and cannot be used to log into the mobile app.');
        }

        // Check if user exists
        $user = User::query()
            ->where('email', $providerUser->email)
            ->first();

        if (! $user) {
            $user = User::updateOrCreate([
                'email' => $providerUser->email,
            ], [
                'name' => $providerUser->name,
                'email' => $providerUser->email,
                'password' => bcrypt($providerUser->id),
            ]);

            // Verify User
            $user->markEmailAsVerified();

            $user->assignRole('member');

            return $user;
        }

        return $user;
    }
}
