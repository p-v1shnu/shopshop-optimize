<?php

namespace App\Utils;

use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JDBUtil
{
    public static function JDBQRSendRequest(string $url, array $body, ?string $token = null): array {
        $client = new \Hidehalo\Nanoid\Client();
        $randomId = $client->formattedId('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', 11);

        $requestBody = [
            'requestId' => CarbonImmutable::now()->format('YmdHms') . $randomId,
            ...$body,
        ];

        $bodyString = json_encode($requestBody);
        $hash = hash_hmac('SHA256', $bodyString, config('custom.jdb_qr_sign_key'));

        Log::info('JDB QR Request', [
            'hash' => $hash,
            'body' => $requestBody,
        ]);

        $headers = [
            'Content-Type' => 'application/json',
            'SignedHash'   => $hash,
        ];

        if ($token !== null) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $response = Http::withHeaders($headers)->post(config('custom.jdb_qr_api_url') . $url, $requestBody);
        $responseBody = $response->json();

        Log::info('JDB QR Response', $responseBody);

        return $responseBody;
    }

    /**
     * @throws Exception
     */
    public static function JDBQRGetAccessToken(): string
    {
        $cacheKey = 'jdb.qr.access_token.mcid_' . config('custom.jdb_qr_mc_id');
        $token = Cache::get($cacheKey);

        if ($token) {
            return $token;
        }

        // If token not exist then request from api
        $data = self::JDBQRSendRequest('/autenticate', [
            'partnerId'   => config('custom.jdb_qr_partner_id'),
            'clientId'    => config('custom.jdb_qr_client_id'),
            'clientScret' => config('custom.jdb_qr_client_secret'),
        ]);

        if ($data['success'] === true) {
            $expiresIn = $data['data']['expiresIn'];
            $accessToken = $data['data']['accessToken'];
            Cache::put($cacheKey, $accessToken, $expiresIn - 100);

            return $accessToken;
        } else {
            throw new Exception('JDB QR No access token');
        }
    }

    /**
     * @throws Exception
     */
    public static function JDBQRGenerateQr(string $billNumber, int $amount): array
    {
        return self::JDBQRSendRequest('/generateQr', [
            'partnerId'     => config('custom.jdb_qr_partner_id'),
            'mechantId'     => config('custom.jdb_qr_mc_id'),
            'txnAmount'     => $amount,
            'billNumber'    => $billNumber,
            'terminalId'    => 'Mineral',
            'terminalLabel' => 'Mineral',
            'mobileNo'      => '2012345678',
        ], self::JDBQRGetAccessToken());
    }

    /**
     * @throws Exception
     */
    public static function JDBQRCheckTransaction(string $billNumber): array
    {
        return self::JDBQRSendRequest('/checkTransaction', [
            'billNumber' => $billNumber,
        ], self::JDBQRGetAccessToken());
    }
}
