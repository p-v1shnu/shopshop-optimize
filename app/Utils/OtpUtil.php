<?php

namespace App\Utils;

use Carbon\CarbonImmutable;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

class OtpUtil
{
    /**
     * @param string $msisdn
     * @param string $message
     * @return array
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws NotFoundExceptionInterface
     */
    public static function sendSMS(string $msisdn, string $message): array
    {
        return self::sendViaTelbiz($msisdn, $message);
    }

    /**
     * @param string $msisdn
     * @param string $message
     * @return array
     * @throws ConnectionException
     */
    public static function sendViaLTC(string $msisdn, string $message): array
    {
        $msisdn = '856' . $msisdn;

        $requestBody = [
            'transaction_id' => 'LTC' . CarbonImmutable::now()->format('YmdHis') . Str::random(8),
            'header'         => config('otp.ltc_sms_header'),
            'phoneNumber'    => $msisdn,
            'message'        => $message,
        ];
        Log::info('LTC SMS Request', $requestBody);

        $headers = [
            'Application' => 'application/json',
            'Apikey'      => config('otp.ltc_sms_api_key'),
        ];
        $response = Http::withHeaders($headers)->post(config('otp.ltc_sms_url'), $requestBody);
        $responseData = json_decode(json_encode($response->json()), true);
        Log::info('LTC SMS Response', $responseData);

        return [
            'provider'     => 'ltc',
            'id'           => $responseData['transaction_id'] ?? null,
            'responseData' => $responseData,
        ];
    }

    /**
     * @param string $msisdn
     * @param string $message
     * @return array
     * @throws GuzzleException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function sendViaTelbiz(string $msisdn, string $message): array
    {
        // ================================================================================
        // Get access token
        // ================================================================================

        $tokenCacheKey = 'telbiz_token_client_id_' . config('otp.telbiz_client_id');
        $token = null;

        // Get token from cache
        if (cache()->has($tokenCacheKey)) {
            $token = cache()->get($tokenCacheKey);

            Log::info('Telbiz - token from cache', [
                'token' => $token,
            ]);

            // Check if token is expired
            $parts = explode('.', $token);

            if (count($parts) != 3) {
                throw new Exception('Telbiz - Invalid JWT token');
            }

            // Decode the payload (middle part)
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            // Get the expiration time
            $exp = $payload['exp'];

            if (!$exp) {
                throw new Exception('Telbiz - Invalid JWT expiration time');
            }

            // Check if the token is expired
            if (CarbonImmutable::now()->timestamp >= $exp) {
                Log::info('Telbiz - token from cache expired');
                cache()->forget($tokenCacheKey);
                $token = null;
            } else {
                Log::info('Telbiz - token from cache not expired');
            }

        }

        // If token is not in cache or token in cache expired, get it from API
        if (!$token) {
            Log::info('Telbiz - token is not in cache or token in cache expired, get it from API');

            $config = [
                'base_uri' => config('otp.telbiz_base_uri') . 'connect/token',
                'verify'   => false,
                'timeout'  => 6000,
                'headers'  => [
                    'Accept'       => 'application/json',
                    'Content-type' => 'application/json'
                ],
                'json' => [
                    'ClientID'  => config('otp.telbiz_client_id'),
                    'Secret'    => config('otp.telbiz_client_secret'),
                    'GrantType' => 'client_credentials',
                    'Scope'     => 'Telbiz_API_SCOPE profile openid'
                ],
                'http_errors' => false,
            ];

            $logConfig = $config;
            $logConfig['json']['ClientID'] = '...';
            $logConfig['json']['Secret'] = '...';
            Log::info('Telbiz - Get token request', $logConfig);

            $client = new Client($config);
            $result = $client->request('POST');
            $responseData = json_decode($result->getBody()->getContents(), true);

            Log::info('Telbiz - Get token response', $responseData);

            if ($responseData['success'] === true) {
                $token = $responseData['accessToken'];

                Log::info('Telbiz token from API', [
                    'token' => $token,
                ]);

                // Cache token for 30 minutes
                cache()->put($tokenCacheKey, $token, 60 * 30);
            }
        }

        if (!$token) {
            throw new Exception('Telbiz - Get token error');
        }

        // ================================================================================
        // Send SMS
        // ================================================================================

        $config = [
            'base_uri' => config('otp.telbiz_base_uri') . 'smsservice/newtransaction?subject=' . config('otp.telbiz_subject'),
            'verify'   => false,
            'timeout'  => 6000,
            'headers'  => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
                'Content-type'  => 'application/json'
            ],
            'json' => [
                'Title'   => 'OTP',
                'Phone'   => $msisdn,
                'Message' => $message
            ],
            'http_errors' => false,
        ];

        Log::info('Telbiz - Send SMS request', $config);

        $client = new Client($config);
        $result = $client->request('POST');

        // If unauthorized clear token cache
        if ($result->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
            cache()->forget($tokenCacheKey);
            throw new Exception('Telbiz response Unauthorized');
        }

        $responseData = json_decode($result->getBody()->getContents(), true);

        Log::info('Telbiz - Send SMS response', $responseData);

        return [
            'provider'     => 'telbiz',
            'id'           => $responseData['key']['rangeKey'] ?? null,
            'responseData' => $responseData,
        ];
    }
}
