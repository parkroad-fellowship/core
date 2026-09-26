<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirectUrl($this->callbackUrl($provider))->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        try {
            $providerUser = Socialite::driver($provider)->redirectUrl($this->callbackUrl($provider))->user();
        } catch (\Exception $e) {
            Log::warning('OAuth callback failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return redirect(config('socialstream.redirects.login-failed', '/login'));
        }

        if (in_array('generate-missing-emails', config('socialstream.features', [])) && !$providerUser->getEmail()) {
            $providerUser->email =
                $providerUser->getId() . '@' . $provider . '.' . parse_url(config('app.url'), PHP_URL_HOST);
        }

        $email = $providerUser->getEmail();
        $user = User::where('email', $email)->first();

        // Accounts are created by onboarding (members) or tenant provisioning (admins), never by signing in.
        if (!$user) {
            return redirect(config('socialstream.redirects.login-failed', '/login'));
        }

        $connectedAccount = ConnectedAccount::where('provider', strtolower($provider))
            ->where('provider_id', $providerUser->getId())
            ->first();

        if ($connectedAccount) {
            if (in_array('refresh-oauth-tokens', config('socialstream.features', []))) {
                $connectedAccount->forceFill([
                    'token' => $providerUser->token,
                    'secret' => $providerUser->tokenSecret ?? null,
                    'refresh_token' => $providerUser->refreshToken ?? null,
                    'expires_at' => property_exists($providerUser, 'expiresIn')
                        ? now()->addSeconds($providerUser->expiresIn)
                        : null,
                ])->save();
            }
        } elseif (
            in_array('auth-existing-unlinked-users', config('socialstream.features', []))
            && $this->providerVerifiedEmail($providerUser)
        ) {
            // Linking by email is only safe when the provider has verified the address.
            $this->createConnectedAccount($user, $provider, $providerUser);
        } else {
            return redirect(config('socialstream.redirects.login-failed', '/login'));
        }

        Auth::login($user, in_array('remember-session', config('socialstream.features', [])));

        return redirect(config('socialstream.redirects.login', config('socialstream.home', '/admin')));
    }

    private function providerVerifiedEmail(object $providerUser): bool
    {
        $raw = property_exists($providerUser, 'user') && is_array($providerUser->user) ? $providerUser->user : [];

        return filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function callbackUrl(string $provider): string
    {
        return request()->getSchemeAndHttpHost() . '/oauth/' . $provider . '/callback';
    }

    private function createConnectedAccount(User $user, string $provider, $providerUser): ConnectedAccount
    {
        return ConnectedAccount::forceCreate([
            'user_id' => $user->id,
            'provider' => strtolower($provider),
            'provider_id' => $providerUser->getId(),
            'name' => $providerUser->getName(),
            'nickname' => $providerUser->getNickname(),
            'email' => $providerUser->getEmail(),
            'avatar_path' => $providerUser->getAvatar(),
            'token' => $providerUser->token,
            'secret' => $providerUser->tokenSecret ?? null,
            'refresh_token' => $providerUser->refreshToken ?? null,
            'expires_at' => property_exists($providerUser, 'expiresIn')
                ? now()->addSeconds($providerUser->expiresIn)
                : null,
        ]);
    }
}
