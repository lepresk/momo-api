<?php
declare(strict_types=1);

namespace Lepresk\MomoApi;

use Lepresk\MomoApi\Models\AirtelConfig;
use Lepresk\MomoApi\Products\AirtelCollectionApi;
use Lepresk\MomoApi\Products\AirtelDisbursementApi;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AirtelApi
{
    public const ENVIRONMENT_PRODUCTION = 'production';
    public const ENVIRONMENT_STAGING = 'staging';

    public const PRODUCTION_URL = 'https://openapi.airtel.cg';
    public const STAGING_URL = 'https://openapiuat.airtel.cg';

    private HttpClientInterface $client;

    private function __construct(string $mode, ?HttpClientInterface $client = null)
    {
        $baseUrl = $mode === self::ENVIRONMENT_PRODUCTION
            ? self::PRODUCTION_URL
            : self::STAGING_URL;

        $this->client = $client ?? HttpClient::create(['base_uri' => $baseUrl]);
    }

    public static function create(string $mode = self::ENVIRONMENT_STAGING, ?HttpClientInterface $client = null): self
    {
        return new self($mode, $client);
    }

    public function getCollection(AirtelConfig $config): AirtelCollectionApi
    {
        return new AirtelCollectionApi($this->client, $config);
    }

    public function getDisbursement(AirtelConfig $config): AirtelDisbursementApi
    {
        return new AirtelDisbursementApi($this->client, $config);
    }

    /**
     * Shorthand factory for Collection API.
     *
     * ### Sample usage
     *
     * ```php
     * $collection = AirtelApi::collection('staging', AirtelConfig::collection(
     *     clientId: 'YOUR_CLIENT_ID',
     *     clientSecret: 'YOUR_CLIENT_SECRET',
     * ));
     * $externalId = $collection->requestToPay('5000', '068511358', 'ORDER-001');
     * ```
     */
    public static function collection(string $mode, AirtelConfig $config): AirtelCollectionApi
    {
        return self::create($mode)->getCollection($config);
    }

    /**
     * Shorthand factory for Disbursement API.
     *
     * ### Sample usage
     *
     * ```php
     * $disbursement = AirtelApi::disbursement('staging', AirtelConfig::disbursement(
     *     clientId: 'YOUR_CLIENT_ID',
     *     clientSecret: 'YOUR_CLIENT_SECRET',
     *     encryptedPin: 'YOUR_ENCRYPTED_PIN',
     * ));
     * $externalId = $disbursement->transfer('10000', '068511358', 'PAY-001');
     * ```
     */
    public static function disbursement(string $mode, AirtelConfig $config): AirtelDisbursementApi
    {
        return self::create($mode)->getDisbursement($config);
    }
}
