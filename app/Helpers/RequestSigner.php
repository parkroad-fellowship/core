<?php

namespace App\Helpers;

class RequestSigner
{
    public static function generateSignature(
        string $method,
        string $url,
        string $timestamp,
        string $appId,
        string $appSecret
    ): string {
        $method = strtoupper($method);
        $stringToSign = $method.'|'.$url.'|'.$timestamp.'|'.$appId;

        return hash_hmac('sha256', $stringToSign, $appSecret);
    }

    public static function getRequiredHeaders(
        string $method,
        string $url,
        string $appId,
        string $appSecret
    ): array {
        $timestamp = (string) (time() * 1000);
        $signature = self::generateSignature($method, $url, $timestamp, $appId, $appSecret);

        return [
            'X-PRF-Signature' => $signature,
            'X-PRF-Timestamp' => $timestamp,
            'X-PRF-App-ID' => $appId,
        ];
    }
}
