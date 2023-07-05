<?php

namespace Lepresk\MomoApi\Products;

use Lepresk\MomoApi\ApiProduct;
use Lepresk\MomoApi\ApiToken;
use Lepresk\MomoApi\Config;
use Lepresk\MomoApi\Exceptions\ExceptionFactory;
use Lepresk\MomoApi\Exceptions\MomoException;
use Lepresk\MomoApi\Models\AccountBalance;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DisbursementApi extends ApiProduct
{

    /**
     * Create an access token which can then be used to authorize and authenticate
     * towards the other end-points of the API.
     *
     * ### Sample usage
     *
     * ```
     * $token = $momo->disbursement()->getAccessToken();
     * $accessToken = $token->getAccessToken();
     * $expireIn = $token->getExpiresIn();
     * ```
     *
     * @return ApiToken
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws MomoException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getAccessToken(): ApiToken
    {
        $response = $this->client->request('POST', "/disbursement/token/", [
            'auth_basic' => [$this->config->getApiUser(), $this->config->getApiKey()],
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
            ],
        ]);

        if ($response->getStatusCode() === 200) {
            return ApiToken::fromArray($response->toArray(false));
        }

        throw ExceptionFactory::create($response);
    }

    /**
     * Get the balance of own account.
     *
     * @return AccountBalance
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws MomoException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getAccountBalance()
    {
        $token = $this->getAccessToken();

        $response = $this->client->request('GET', '/disbursement/v1_0/account/balance', [
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
                'X-Target-Environment' => $this->environment,
                'Authorization' => 'Bearer ' . $token->getAccessToken(),
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            return AccountBalance::parse($response->toArray(false));
        }

        throw ExceptionFactory::create($response);
    }
}