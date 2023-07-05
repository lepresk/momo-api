<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Products;

use Lepresk\MomoApi\ApiProduct;
use Lepresk\MomoApi\Exceptions\ExceptionFactory;
use Lepresk\MomoApi\Exceptions\MomoException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SandboxApi extends ApiProduct
{

    /**
     * Create an API user in the sandbox target environment.
     *
     * ### Sample usage
     *
     * ```
     * $uuid = Utilities::guidv4();
     * $callbackHost = 'https://my-domain.com/callback';
     * $apiUser = $momo->sandbox()->createApiUser($uuid, $callbackHost);
     * echo "Api user created: $apiUser";
     * ```
     *
     * @param string $apiUser Format - UUID. Recource ID for the API user to be created. UUID version 4 is required.
     * @param string $callbackHost
     * @return string|null
     *
     * @throws MomoException
     * @throws TransportExceptionInterface
     * @see https://momodeveloper.mtn.com/docs/services/sandbox-provisioning-api/operations/post-v1_0-apiuser
     */
    public function createApiUser(string $apiUser, string $callbackHost): ?string
    {
        $response = $this->client->request('POST', "/v1_0/apiuser", [
            'json' => [
                'providerCallbackHost' => $callbackHost,
            ],
            'headers' => [
                'X-Reference-Id' => $apiUser,
                'Content-Type' => 'application/json',
                'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
            ]
        ]);

        if ($response->getStatusCode() === 201) {
            return $apiUser;
        }

        throw ExceptionFactory::create($response);
    }

    /**
     * Get API user information.
     *
     * ### Sample usage
     *
     * ```
     * $data = $momo->sandbox()->getApiUser($apiUser);
     * print_r($data);
     * ```
     *
     * @param string $apiUser Format - UUID. Recource ID for the API user to be created. UUID version 4 is required.
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws MomoException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getApiUser(string $apiUser): array
    {
        $response = $this->client->request('GET', "/v1_0/apiuser/$apiUser", [
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            return $response->toArray();
        }

        throw ExceptionFactory::create($response);
    }

    /**
     * Create an API key for an API user in the sandbox target environment.
     *
     * ### Sample usage
     *
     * ```
     * $apiKey = $momo->sandbox()->createApiKey($apiUser);
     * echo "Api token created : $apiKey\n";
     * ```
     *
     * @param string $apiUser Format - UUID. Recource ID for the API user to be created. UUID version 4 is required.
     * @return string apiKey
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws MomoException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function createApiKey(string $apiUser): string
    {
        $response = $this->client->request('POST', "/v1_0/apiuser/$apiUser/apikey", [
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
            ]
        ]);

        $data = $response->toArray(false);

        if ($response->getStatusCode() === 201) {
            return $data['apiKey'];
        }

        throw ExceptionFactory::create($response);
    }
}