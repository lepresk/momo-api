<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Products;

use Lepresk\MomoApi\Exceptions\ExceptionFactory;
use Lepresk\MomoApi\Models\AccountBalance;
use Lepresk\MomoApi\Models\AirtelConfig;
use Lepresk\MomoApi\Models\AirtelTransaction;
use Lepresk\MomoApi\Support\TokenCache;
use Lepresk\MomoApi\Support\Uuid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AirtelCollectionApi
{
    private AirtelConfig $config;
    private HttpClientInterface $client;
    private TokenCache $tokenCache;

    public function __construct(HttpClientInterface $client, AirtelConfig $config)
    {
        $this->client = $client;
        $this->config = $config;
        $this->tokenCache = new TokenCache();
    }

    /**
     * Obtain an OAuth2 access token. Cached for the token lifetime minus 60 seconds.
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getAccessToken(): string
    {
        $cached = $this->tokenCache->get();
        if ($cached !== null) {
            return $cached;
        }

        $response = $this->client->request('POST', '/auth/oauth2/token', [
            'json' => [
                'client_id' => $this->config->getClientId(),
                'client_secret' => $this->config->getClientSecret(),
                'grant_type' => 'client_credentials',
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw ExceptionFactory::create($response);
        }

        $data = $response->toArray(false);
        $token = (string) $data['access_token'];
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        $this->tokenCache->set($token, $expiresIn);
        return $token;
    }

    /**
     * Initiate a payment request. Returns the externalId to use for status checks.
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function requestToPay(string $amount, string $phone, string $reference): string
    {
        $token = $this->getAccessToken();
        $externalId = Uuid::v4();

        $response = $this->client->request('POST', '/merchant/v1/payments/', [
            'json' => [
                'reference' => $reference,
                'subscriber' => [
                    'country' => $this->config->getCountry(),
                    'currency' => $this->config->getCurrency(),
                    'msisdn' => $phone,
                ],
                'transaction' => [
                    'amount' => (float) $amount,
                    'country' => $this->config->getCountry(),
                    'currency' => $this->config->getCurrency(),
                    'id' => $externalId,
                ],
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'X-Country' => $this->config->getCountry(),
                'X-Currency' => $this->config->getCurrency(),
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
            ],
        ]);

        if ($response->getStatusCode() >= 400) {
            throw ExceptionFactory::create($response);
        }

        return $externalId;
    }

    /**
     * Get the status of a payment. Pass the externalId returned by requestToPay.
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getPaymentStatus(string $externalId): AirtelTransaction
    {
        $token = $this->getAccessToken();

        $response = $this->client->request(
            'GET',
            '/standard/v1/payments/' . rawurlencode($externalId),
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'X-Country' => $this->config->getCountry(),
                    'X-Currency' => $this->config->getCurrency(),
                    'Accept' => '*/*',
                ],
            ]
        );

        if ($response->getStatusCode() >= 400) {
            throw ExceptionFactory::create($response);
        }

        $data = $response->toArray(false);
        return AirtelTransaction::parse($data['data']['transaction'] ?? []);
    }

    /**
     * Get the account balance.
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getBalance(): AccountBalance
    {
        $token = $this->getAccessToken();

        $response = $this->client->request('GET', '/standard/v1/users/balance', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'X-Country' => $this->config->getCountry(),
                'X-Currency' => $this->config->getCurrency(),
                'Accept' => '*/*',
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw ExceptionFactory::create($response);
        }

        $data = $response->toArray(false);
        return AccountBalance::parse([
            'availableBalance' => (string) ($data['data']['balance'] ?? '0'),
            'currency' => (string) ($data['data']['currency'] ?? ''),
        ]);
    }
}
