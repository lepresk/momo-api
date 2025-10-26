<?php
declare(strict_types=1);

namespace Lepresk\MomoApi;

use InvalidArgumentException;
use Lepresk\MomoApi\Products\CollectionApi;
use Lepresk\MomoApi\Products\DisbursementApi;
use Lepresk\MomoApi\Products\SandboxApi;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MomoApi
{
    public const ENVIRONMENT_MTN_CONGO = 'mtncongo';
    public const ENVIRONMENT_MTN_UGANDA = 'mtnuganda';
    public const ENVIRONMENT_MTN_GHANA = 'mtnghana';
    public const ENVIRONMENT_IVORY_COAST = 'mtnivorycoast';
    public const ENVIRONMENT_ZAMBIA = 'mtnzambia';
    public const ENVIRONMENT_CAMEROON = 'mtncameroon';
    public const ENVIRONMENT_BENIN = 'mtnbenin';
    public const ENVIRONMENT_SWAZILAND = 'mtnswaziland';
    public const ENVIRONMENT_GUINEACONAKRY = 'mtnguineaconakry';
    public const ENVIRONMENT_SOUTHAFRICA = 'mtnsouthafrica';
    public const ENVIRONMENT_LIBERIA = 'mtnliberia';
    public const ENVIRONMENT_SANDBOX = 'sandbox';


    public const SANDBOX_URL = 'https://sandbox.momodeveloper.mtn.com';
    public const PRODUCTION_URL = 'https://proxy.momoapi.mtn.com';

    public string $environment;

    private static ?HttpClientInterface $client = null;

    private function __construct(string $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @param HttpClientInterface $client
     * @return void
     */
    public static function useClient(HttpClientInterface $client)
    {
        self::$client = $client;
    }

    /**
     * @return HttpClientInterface|null
     */
    public static function getClient(): ?HttpClientInterface
    {
        return self::$client;
    }

    /**
     * Class factory
     *
     * @param string $environment
     * @return MomoApi
     */
    public static function create(string $environment): MomoApi
    {
        if (self::$client === null) {
            self::$client = HttpClient::create([
                'base_uri' => self::getBaseUrl($environment),
            ]);
        }
        return new self($environment);
    }

    /**
     * Fluent factory for Collection API
     *
     * @param array $config Configuration array with keys: environment, subscription_key, api_user, api_key, callback_url
     * @return CollectionApi
     */
    public static function collection(array $config): CollectionApi
    {
        $environment = $config['environment'] ?? self::ENVIRONMENT_SANDBOX;
        $subscriptionKey = $config['subscription_key'] ?? throw new InvalidArgumentException('subscription_key is required');
        $apiUser = $config['api_user'] ?? throw new InvalidArgumentException('api_user is required');
        $apiKey = $config['api_key'] ?? throw new InvalidArgumentException('api_key is required');
        $callbackUrl = $config['callback_url'] ?? '';

        if (self::$client === null) {
            self::$client = HttpClient::create([
                'base_uri' => self::getBaseUrl($environment),
            ]);
        }

        $configObject = Config::collection($subscriptionKey, $apiUser, $apiKey, $callbackUrl);
        return new CollectionApi(self::$client, $environment, $configObject);
    }

    /**
     * Fluent factory for Disbursement API
     *
     * @param array $config Configuration array with keys: environment, subscription_key, api_user, api_key, callback_url
     * @return DisbursementApi
     */
    public static function disbursement(array $config): DisbursementApi
    {
        $environment = $config['environment'] ?? self::ENVIRONMENT_SANDBOX;
        $subscriptionKey = $config['subscription_key'] ?? throw new InvalidArgumentException('subscription_key is required');
        $apiUser = $config['api_user'] ?? throw new InvalidArgumentException('api_user is required');
        $apiKey = $config['api_key'] ?? throw new InvalidArgumentException('api_key is required');
        $callbackUrl = $config['callback_url'] ?? '';

        if (self::$client === null) {
            self::$client = HttpClient::create([
                'base_uri' => self::getBaseUrl($environment),
            ]);
        }

        $configObject = Config::disbursement($subscriptionKey, $apiUser, $apiKey, $callbackUrl);
        return new DisbursementApi(self::$client, $environment, $configObject);
    }

    public static function getBaseUrl($environment): string
    {
        if ($environment === MomoApi::ENVIRONMENT_SANDBOX) {
            return self::SANDBOX_URL;
        }
        return self::PRODUCTION_URL;
    }

    /**
     * Access to the sandbox environment
     *
     * @param string $subscriptionKey
     * @return SandboxApi
     */
    public function sandbox(string $subscriptionKey): SandboxApi
    {
        if ($this->environment !== self::ENVIRONMENT_SANDBOX) {
            throw new InvalidArgumentException("Environment must be " . self::ENVIRONMENT_SANDBOX);
        }

        return new SandboxApi(self::$client, $this->environment, Config::sandbox($subscriptionKey));
    }
}