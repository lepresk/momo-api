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

    public static string $environment = self::ENVIRONMENT_SANDBOX;

    /**
     * Instance singleton
     *
     * @var MomoApi|null
     */
    private static ?MomoApi $instance = null;
    private static ?HttpClientInterface $client = null;

    private ?Config $collectionConfig = null;

    private ?Config $disbursementConfig = null;

    private function __construct()
    {
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
     * @return MomoApi
     */
    public static function create(): MomoApi
    {
        if (!self::$instance) {
            if (static::$client === null) {
                static::$client = HttpClient::create([
                    'base_uri' => static::getBaseUrl(),
                ]);
            }
            self::$instance = new self();
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

    public function setupCollection(Config $config): void
    {
        $this->collectionConfig = $config;
    }

    public function setupDisbursement(Config $config): void
    {
        $this->disbursementConfig = $config;
    }

    /**
     * Momo API Collection factory
     *
     * @return CollectionApi
     */
    public function collection(): CollectionApi
    {
        if ($this->collectionConfig === null) {
            throw new InvalidArgumentException("Collection must be setup with `MomoApi::setupCollection` before call `MomoApi::collection`");
        }

        return new CollectionApi(static::$client, self::$environment, $this->collectionConfig);
    }

    /**
     * Access to Disbursements product
     *
     * @return DisbursementApi
     */
    public function disbursement(): DisbursementApi
    {
        if ($this->disbursementConfig === null) {
            throw new InvalidArgumentException("Disbursement must be setup with `MomoApi::setupDisbursement` before call `MomoApi::disbursement`");
        }

        return new DisbursementApi(static::$client, self::$environment, $this->disbursementConfig);
    }

    /**
     * Access to the sandbox environment
     *
     * @param string $subscriptionKey
     * @return SandboxApi
     */
    public function sandbox(string $subscriptionKey): SandboxApi
    {
        if (self::$environment !== self::ENVIRONMENT_SANDBOX) {
            throw new InvalidArgumentException("Environment must be " . self::ENVIRONMENT_SANDBOX);
        }

        return new SandboxApi(static::$client, self::$environment, Config::sandbox($subscriptionKey));
    }
}