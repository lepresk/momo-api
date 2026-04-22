<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Products;

use InvalidArgumentException;
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

class AirtelDisbursementApi
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
     * Transfer funds to a payee. Returns the externalId for status checks.
     *
     * @throws InvalidArgumentException if encryptedPin is not configured
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function transfer(string $amount, string $phone, string $reference): string
    {
        if (empty($this->config->getEncryptedPin())) {
            throw new InvalidArgumentException('encryptedPin is required for disbursement transfers');
        }

        $token = $this->getAccessToken();
        $externalId = Uuid::v4();

        $response = $this->client->request('POST', '/standard/v1/disbursements/', [
            'json' => [
                'payee' => ['msisdn' => $phone],
                'reference' => $reference,
                'pin' => $this->config->getEncryptedPin(),
                'transaction' => [
                    'amount' => (string) intval($amount),
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
     * Get the status of a transfer. Pass the externalId returned by transfer.
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getTransferStatus(string $externalId): AirtelTransaction
    {
        $token = $this->getAccessToken();

        $response = $this->client->request(
            'GET',
            '/standard/v1/disbursements/' . rawurlencode($externalId),
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

        if (!isset($data['data']['transaction'])) {
            throw new \RuntimeException(
                'Transaction not found in Airtel system for externalId: ' . $externalId
            );
        }

        return AirtelTransaction::parse($data['data']['transaction']);
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
