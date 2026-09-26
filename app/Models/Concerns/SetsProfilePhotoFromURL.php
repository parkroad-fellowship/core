<?php

namespace App\Models\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Throwable;

trait SetsProfilePhotoFromURL
{
    /**
     * Hosts social sign-in providers serve avatars from. Anything else is refused, so this can
     * never be used to make the server fetch internal or arbitrary URLs (SSRF).
     */
    private const AVATAR_HOSTS = ['googleusercontent.com', 'gravatar.com', 'githubusercontent.com', 'fbcdn.net'];

    private const MAX_AVATAR_BYTES = 5 * 1024 * 1024;

    public function setProfilePhotoFromUrl(string $url): void
    {
        if (!self::isAllowedAvatarURL($url)) {
            $this->flashProfilePhotoError();

            return;
        }

        try {
            $response = Http::timeout(10)->withOptions(['allow_redirects' => false])->get($url);
        } catch (Throwable) {
            $this->flashProfilePhotoError();

            return;
        }

        $isImage = str_starts_with(strtolower((string) $response->header('Content-Type')), 'image/');

        if (!$response->successful() || !$isImage || strlen($response->body()) > self::MAX_AVATAR_BYTES) {
            $this->flashProfilePhotoError();

            return;
        }

        file_put_contents($file = sys_get_temp_dir() . '/' . Str::uuid()->toString(), $response->body());

        $this->updateProfilePhoto(
            new UploadedFile($file, basename((string) parse_url($url, PHP_URL_PATH)) ?: 'avatar'),
        );
    }

    private static function isAllowedAvatarURL(string $url): bool
    {
        $parts = parse_url($url);

        if (
            ($parts['scheme'] ?? null) !== 'https'
            || !isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['port'])
        ) {
            return false;
        }

        $host = strtolower($parts['host']);

        foreach (self::AVATAR_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return true;
            }
        }

        return false;
    }

    private function flashProfilePhotoError(): void
    {
        Session::flash('flash.banner', 'Unable to retrieve image');
        Session::flash('flash.bannerStyle', 'danger');
    }
}
