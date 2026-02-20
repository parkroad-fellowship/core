<?php

namespace App\Helpers;

class RequestSigner
{
    public static function generateSignature(
        string $method,
        string $url,
        string $body,
        string $timestamp,
        string $appId,
        string $appSecret
    ): string {
        $method = strtoupper($method);
        $stringToSign = $method.'|'.$url.'|'.$body.'|'.$timestamp.'|'.$appId;

        return hash_hmac('sha256', $stringToSign, $appSecret);
    }

    public static function getRequiredHeaders(
        string $method,
        string $url,
        string $body,
        string $appId,
        string $appSecret
    ): array {
        $timestamp = (string) time();
        $signature = self::generateSignature($method, $url, $body, $timestamp, $appId, $appSecret);

        return [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
            'X-App-ID' => $appId,
        ];
    }
}
