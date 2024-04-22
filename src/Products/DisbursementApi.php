<?php

namespace Lepresk\MomoApi\Products;

use Lepresk\MomoApi\ApiProduct;
use Lepresk\MomoApi\ApiToken;
use Lepresk\MomoApi\Exceptions\ExceptionFactory;
use Lepresk\MomoApi\Exceptions\MomoException;
use Lepresk\MomoApi\Models\AccountBalance;
use Lepresk\MomoApi\Models\PaymentRequest;
use Lepresk\MomoApi\Models\Transaction;
use Lepresk\MomoApi\Utilities;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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
                'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
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
    public function getAccountBalance(): AccountBalance
    {
        $token = $this->getAccessToken();

        $response = $this->client->request('GET', '/disbursement/v1_0/account/balance', [
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
                'X-Target-Environment' => $this->environment,
                'Authorization' => 'Bearer ' . $token->getAccessToken(),
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            return AccountBalance::parse($response->toArray(false));
        }

        throw ExceptionFactory::create($response);
    }

    /**
     * Deposit an amount from the owner’s account to a payee account.
     *
     * ### Sample usage
     *
     * ```
     * $request = new \Lepresk\MomoApi\Models\PaymentRequest(
     *    5000,
     *   'XAF',
     *   'ORDER-10',
     *   '46733123454',
     *   'Payment message',
     *   'A note',
     * );
     * $paymentId = $momo->disbursement()->requestToPay($request);
     * ```
     * @param PaymentRequest $paymentRequest
     * @return string payment reference id
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws MomoException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getDepositV1(PaymentRequest $paymentRequest): string
    {
        $token = $this->getAccessToken();

        $xReferenceId = Utilities::guidv4();

        $response = $this->client->request('POST', '/disbursement/v1_0/account/deposit', [
            'json' => $paymentRequest->toArray(),
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
                'X-Reference-Id' => $xReferenceId,
                'X-Target-Environment' => $this->environment,
                'Authorization' => 'Bearer ' . $token->getAccessToken(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);

        $responseCode = $response->getStatusCode();
        if ($responseCode === 202) {
            return $xReferenceId;
        }

        throw ExceptionFactory::create($response);
    }

    /**
     * Get the status of a deposit. X-Reference-Id that was passed in the post is used as reference to the request.
     *
     * ### Sample usage
     *
     * ```
     * $depositId = "07a461a4-e721-462b-81c6-b9aa2f8abf06";
     * try {
     *      $result = $momo->disbursement()->checkPayment($paymentId);
     *      if($result->isSuccessful()) {
     *          echo "Payment successful";
     *          $result->getAmount(); // 1500
     *          $result->getPayer(); // 46733123454
     *      }
     * } catch (BadRessourceExeption|InternalServerErrorException|RessourceNotFoundException $e) {
     *      // Request failed, do something else
     * }
     * ```
     *
     * @param string $depositId UUID of transaction to get result. Reference id used when creating the request to pay.
     * @return Transaction
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws MomoException
     */
    public function getDepositStatus(string $depositId): Transaction
    {
        $token = $this->getAccessToken();
        $response = $this->client->request('GET', '/disbursement/v1_0/deposit/' . $depositId, [
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
                'X-Target-Environment' => $this->environment,
                'Authorization' => 'Bearer ' . $token->getAccessToken(),
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            return Transaction::parse($response->toArray());
        }

        throw ExceptionFactory::create($response);
    }
}