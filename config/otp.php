<?php

return [
    'ltc_sms_url'          => env('LTC_SMS_URL'),
    'ltc_sms_api_key'      => env('LTC_SMS_API_KEY'),
    'ltc_sms_header'       => env('LTC_SMS_HEADER'),

    'telbiz_base_uri'      => env('TELBIZ_BASE_URI'),
    'telbiz_client_id'     => env('TELBIZ_CLIENT_ID'),
    'telbiz_client_secret' => env('TELBIZ_CLIENT_SECRET'),
    'telbiz_subject'       => env('TELBIZ_SUBJECT', ''),
];
