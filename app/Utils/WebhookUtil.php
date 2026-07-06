<?php

namespace App\Utils;

use Exception;

class WebhookUtil
{
    public static function generateSignature(string $data): string
    {
        $privateKey = file_get_contents(storage_path('app/keypairs/' . config('custom.private_key_name')));
        $publicKey = file_get_contents(storage_path('app/keypairs/' . config('custom.public_key_name')));

        // ================================================================================

        $privateKeyId = openssl_pkey_get_private($privateKey);
        if (!$privateKeyId) {
            throw new Exception("Failed to load private key.");
        }

        // Sign the data
        $signature = null;
        openssl_sign($data, $signature, $privateKeyId, OPENSSL_ALGO_SHA256);

        // Encode the signature in base64
        $signatureBase64 = base64_encode($signature);

        // ================================================================================

        $publicKeyId = openssl_pkey_get_public($publicKey);
        if (!$publicKeyId) {
            throw new Exception("Failed to load public key.");
        }

        // Decode the Base64 signature
        $signature = base64_decode($signatureBase64);
        $verified = openssl_verify($data, $signature, $publicKeyId, OPENSSL_ALGO_SHA256);

        // Check verification result
        if ($verified === 1) {
            return $signatureBase64;
        } elseif ($verified === 0) {
            throw new Exception('Failed to verify signature: signature is incorrect');
        } else {
            throw new Exception('Failed to verify signature: ' . openssl_error_string());
        }
    }
}
