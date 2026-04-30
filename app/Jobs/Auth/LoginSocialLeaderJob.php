<?php

namespace App\Jobs\Auth;

use App\Models\AppSetting;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

        $orgDomain = config('prf.app.org_email_domain', 'example.org');
        if (Str::doesntContain($providerUser->email, '@'.$orgDomain)) {
            throw new Exception('Invalid email. Must be an organization email');
        }

        $excludeEmails = AppSetting::query()
            ->where('key', 'organization.excluded_emails')
            ->value('value');

        if (! Arr::exists(json_decode($excludeEmails), $providerUser->email)) {
            throw new Exception('Access denied. This is a member email and cannot be used to log into this app.');
        }

        // Check if user exists
        return User::query()
            ->where('email', $providerUser->email)
            ->firstOrFail();
    }
}
