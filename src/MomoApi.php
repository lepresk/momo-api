<?php
declare(strict_types=1);

namespace Lepresk\MomoApi;

use InvalidArgumentException;
use Lepresk\MomoApi\Collection\Config;
use Lepresk\MomoApi\Collection\MomoCollection;
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
    public static string $environment = self::ENVIRONMENT_SANDBOX;
    /**
     * Instance singleton
     *
     * @var MomoApi|null
     */
    private static ?MomoApi $instance = null;
    private static ?HttpClientInterface $client = null;
    private ?Config $collectionConfig = null;
    /**
     * @var string
     */
    private string $subscriptionKey;

    /**
     * @param string $subscriptionKey
     */
    private function __construct(string $subscriptionKey)
    {
        $this->subscriptionKey = $subscriptionKey;
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
     * @param string $subscriptionKey
     * @return MomoApi
     */
    public static function create(string $subscriptionKey): MomoApi
    {
        if (!self::$instance) {
            if (static::$client === null) {
                static::$client = HttpClient::create([
                    'base_uri' => static::getBaseUrl(),
                ]);
            }
            self::$instance = new self($subscriptionKey);
        }
        return self::$instance;
    }

    public static function getBaseUrl(): string
    {
        if (self::$environment === MomoApi::ENVIRONMENT_SANDBOX) {
            return self::SANDBOX_URL;
        }
        return self::PRODUCTION_URL;
    }

    /**
     * @param string $environment
     */
    public static function setEnvironment(string $environment): void
    {
        self::$environment = $environment;
    }

    /**
     * @param Config $config
     * @return void
     */
    public function setupCollection(Config $config): void
    {
        $this->collectionConfig = $config;
    }

    /**
     * Momo API Collection factory
     */
    public function collection(): MomoCollection
    {
        if ($this->collectionConfig === null) {
            throw new InvalidArgumentException("Collection must be setup with `MomoApi::setupCollection` before call `MomoApi::collection`");
        }

        return new MomoCollection(static::$client, $this->subscriptionKey, $this->collectionConfig,  self::$environment);
    }

    /**
     * Access to the sandbox environment
     *
     * @return Sandbox
     */
    public function sandbox(): Sandbox
    {
        if (self::$environment !== self::ENVIRONMENT_SANDBOX) {
            throw new InvalidArgumentException("Environment must be " . self::ENVIRONMENT_SANDBOX);
        }

        return new Sandbox(static::$client, $this->subscriptionKey);
    }
}