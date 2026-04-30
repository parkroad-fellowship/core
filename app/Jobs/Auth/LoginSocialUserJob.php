<?php

namespace App\Jobs\Auth;

use App\Models\AppSetting;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;
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

        $excludeEmails = AppSetting::query()
            ->where('key', 'organization.excluded_emails')
            ->value('value');

        if(Arr::exists(json_decode($excludeEmails), $providerUser->email)) {
            throw new Exception('Access denied. This email is not allowed to log into the members app app.');
        }

        $orgDomain = config('prf.app.org_email_domain');
        if (Str::doesntContain($providerUser->email, "@{$orgDomain}")) {
            throw new Exception('Invalid email. Must be an organization email');
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

        if ($user->is_desk_email) {
            throw new Exception('Access denied. This is an administrative email and cannot be used to log into the mobile app.');
        }

        return $user;
    }
}
