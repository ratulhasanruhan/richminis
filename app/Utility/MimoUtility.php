<?php

namespace App\Utility;

/**
 * Legacy "Mimo" SMS gateway helper. The older token-based MIMO HTTP API is no longer
 * present in this codebase; OTP sending is routed through the same MIM SMS HTTP API
 * used by {@see \App\Services\OTP\Mimsms}, using MIM_* environment variables.
 */
class MimoUtility
{
    public static function getToken(): string
    {
        return 'mim-http-v1';
    }

    public static function sendMessage(string $text, string $to, string $token): void
    {
        $to = trim($to);
        if ($to === '') {
            throw new \InvalidArgumentException('Phone number is required for SMS.');
        }
        if (str_starts_with($to, '+')) {
            $to = substr($to, 1);
        }

        $url = 'https://api.mimsms.com/api/SmsSending/SMS';
        $data = [
            'UserName' => env('MIM_USER_NAME'),
            'Apikey' => env('MIM_API_KEY'),
            'MobileNumber' => $to,
            'CampaignId' => 'null',
            'SenderName' => env('MIM_SENDER_ID'),
            'TransactionType' => 'T',
            'Message' => $text,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'accept:application/json',
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException('SMS request failed: ' . $err);
        }
        if ($response === false || $response === '') {
            throw new \RuntimeException('SMS gateway returned an empty response.');
        }

        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            if (array_key_exists('Success', $decoded) && $decoded['Success'] === false) {
                throw new \RuntimeException($decoded['Message'] ?? 'SMS gateway rejected the request.');
            }
            if (array_key_exists('IsSuccess', $decoded) && $decoded['IsSuccess'] === false) {
                throw new \RuntimeException($decoded['Message'] ?? 'SMS gateway rejected the request.');
            }
        }
    }

    public static function logout(string $token): void
    {
        // No-op: current gateway uses API key per request.
    }
}
