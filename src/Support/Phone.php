<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Support;

class Phone
{
    /**
     * Country codes of the Airtel Africa markets. Airtel's API expects a
     * national MSISDN, so a number carrying one of these must have it removed
     * before the request is sent.
     */
    public const AIRTEL_COUNTRY_CODES = [
        '256', // Uganda
        '254', // Kenya
        '255', // Tanzania
        '260', // Zambia
        '250', // Rwanda
        '234', // Nigeria
        '241', // Gabon
        '227', // Niger
        '242', // Congo-Brazzaville
        '243', // DR Congo
        '235', // Chad
        '261', // Madagascar
        '265', // Malawi
        '248', // Seychelles
    ];

    /**
     * Strip formatting and, if present, a leading Airtel-market country code.
     *
     * A number that is already national is returned unchanged, so this is safe
     * to apply to any input. Only the first matching code is removed.
     */
    public static function clean(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        foreach (self::AIRTEL_COUNTRY_CODES as $code) {
            if (str_starts_with($digits, $code)) {
                return substr($digits, strlen($code));
            }
        }

        return $digits;
    }
}
