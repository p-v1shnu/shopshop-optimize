<?php

namespace App\Utils;

class BCELUtil
{
    public static function generateQr(array $arr): string
    {
        $rawQr = self::buildQr([
            '00' => $arr['00'],
            '01' => $arr['01'],
            '33' => self::buildQr([
                '00' => $arr['33']['00'],
                '01' => $arr['33']['01'],
                '02' => $arr['33']['02'],
                '03' => $arr['33']['03'],
            ]),
            '52' => $arr['52'],
            '53' => $arr['53'],
            '54' => $arr['54'],
            '58' => $arr['58'],
            '60' => $arr['60'],
            '62' => self::buildQr([
                '01' => $arr['62']['01'],
                '05' => $arr['62']['05'],
                '08' => $arr['62']['08'],
            ])
        ]);

        $fullQr = $rawQr . self::buildQr([
            63 => self::crc16($rawQr . "6304")
        ]); // 6304 is from system no need to change

        return $fullQr;
    }

    private static function buildQr(array $arr): string
    {
        $res = "";
        foreach ($arr as $key => $val) {
            if (!$val) continue;
            $res .= str_pad($key, 2, "0", STR_PAD_LEFT) .
                str_pad(strlen($val), 2, "0", STR_PAD_LEFT) .
                $val;
        }
        return $res;
    }

    private static function crc16(string $sStr, array $aParams = []): string
    {
        $aDefaults = [
            "polynome" => 0x1021,
            "init" => 0xFFFF,
            "xor_out" => 0,
        ];
        foreach ($aDefaults as $key => $val) {
            if (!isset($aParams[$key])) {
                $aParams[$key] = $val;
            }
        }
        $sStr .= "";
        $crc = $aParams['init'];
        $len = strlen($sStr);
        $i = 0;
        while ($len--) {
            $crc ^= ord($sStr[$i++]) << 8;
            $crc &= 0xffff;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? ($crc << 1) ^ $aParams['polynome'] :
                    $crc << 1;
                $crc &= 0xffff;
            }
        }
        $crc ^= $aParams['xor_out'];
        return str_pad(strtoupper(dechex($crc)), 4, "0", STR_PAD_LEFT);
    }
}
