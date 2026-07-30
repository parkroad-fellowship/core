<?php

namespace App\Models\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

trait SetsProfilePhotoFromUrl
{
    public function setProfilePhotoFromUrl(string $url): void
    {
        $name = pathinfo($url)['basename'];
        $response = Http::get($url);

        if ($response->successful()) {
            file_put_contents($file = sys_get_temp_dir().'/'.Str::uuid()->toString(), $response);

            $this->updateProfilePhoto(new UploadedFile($file, $name));
        } else {
            Session::flash('flash.banner', 'Unable to retrieve image');
            Session::flash('flash.bannerStyle', 'danger');
        }
    }
}
