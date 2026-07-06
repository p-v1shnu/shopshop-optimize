<?php

return [
    'db_enable_migration'       => env('DB_ENABLE_MIGRATION', false),
    'shop_campaign_code'        => env('SHOP_CAMPAIGN_CODE', ''),
    'system_env'                => env('SYSTEM_ENV'),
    'enable_search'             => env('ENABLE_SEARCH', false),

    'shop_discount_hal'         => env('SHOP_DISCOUNT_HAL'),
    'shop_free_shipping'        => env('SHOP_FREE_SHIPPING'),
    'payment_uuid_prefix'       => env('PAYMENT_UUID_PREFIX'),

    'bcel_qr_pubnub_subkey'     => env('BCEL_QR_PUBNUB_SUBKEY'),
    'bcel_qr_pubnub_userid'     => env('BCEL_QR_PUBNUB_USERID'),
    'bcel_qr_mc_id'             => env('BCEL_QR_MC_ID'),
    'bcel_qr_mc_code'           => env('BCEL_QR_MC_CODE'),
    'bcel_qr_mc_name'           => env('BCEL_QR_MC_NAME'),
    'bcel_qr_mcc'               => env('BCEL_QR_MCC'),

    'jdb_qr_api_url'            => env('JDB_QR_API_URL'),
    'jdb_qr_pubnub_subkey'      => env('JDB_QR_PUBNUB_SUBKEY'),
    'jdb_qr_pubnub_userid'      => env('JDB_QR_PUBNUB_USERID'),
    'jdb_qr_mc_id'              => env('JDB_QR_MC_ID'),
    'jdb_qr_mc_code'            => env('JDB_QR_MC_CODE'),
    'jdb_qr_mc_name'            => env('JDB_QR_MC_NAME'),
    'jdb_qr_partner_id'         => env('JDB_QR_PARTNER_ID'),
    'jdb_qr_client_id'          => env('JDB_QR_CLIENT_ID'),
    'jdb_qr_client_secret'      => env('JDB_QR_CLIENT_SECRET'),
    'jdb_qr_sign_key'           => env('JDB_QR_SIGN_KEY'),

    'hal_client_id'            => env('HAL_CLIENT_ID'),
    'hal_client_secret'        => env('HAL_CLIENT_SECRET'),
    'hal_username'             => env('HAL_USERNAME'),
    'hal_password'             => env('HAL_PASSWORD'),
    'hal_verify_secret'        => env('HAL_VERIFY_SECRET'),
    'hal_sign_secret'          => env('HAL_SIGN_SECRET'),
    'hal_webhook_url'          => env('HAL_WEBHOOK_URL'),

    'public_key_name'          => env('PUBLIC_KEY_NAME', 'public_key.pem'),
    'private_key_name'         => env('PRIVATE_KEY_NAME', 'private_key.pem'),

    'main_domain'              => 'shopshop.test',
    'google_map_api_key'       => env('GOOGLE_MAP_API_KEY'),

    'cloudflare_api_token'     => env('CLOUDFLARE_API_TOKEN'),
    'cloudflare_zone_id'       => env('CLOUDFLARE_ZONE_ID'),
    'flush_cache_secret'       => env('FLUSH_CACHE_SECRET'),
];
