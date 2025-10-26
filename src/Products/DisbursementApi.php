<?php

namespace Lepresk\MomoApi\Products;

use Lepresk\MomoApi\Abstracts\AbstractApiProduct;
use Lepresk\MomoApi\Concerns\InteractsWithHttp;
use Lepresk\MomoApi\Exceptions\ExceptionFactory;
use Lepresk\MomoApi\Exceptions\MomoException;
use Lepresk\MomoApi\Models\AccountBalance;
use Lepresk\MomoApi\Models\ApiToken;
use Lepresk\MomoApi\Models\PaymentRequest;
use Lepresk\MomoApi\Models\RefundRequest;
use Lepresk\MomoApi\Models\TransferRequest;
use Lepresk\MomoApi\Models\Transaction;
use Lepresk\MomoApi\Support\Uuid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DisbursementApi extends AbstractApiProduct
{
    use InteractsWithHttp;

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
     * ### Sample usage
     *
     * ```
     * $balance = $disbursement->getBalance();
     * echo $balance->getAvailableBalance(); // 1000000
     * echo $balance->getCurrency(); // XAF
     * ```
     *
     * @return AccountBalance
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws MomoException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getBalance(): AccountBalance
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
     * Deposit an amount from the owner's account to a payee account.
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
     * $paymentId = $momo->disbursement()->deposit($request);
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
    public function deposit(PaymentRequest $paymentRequest): string
    {
        $token = $this->getAccessToken();

        $xReferenceId = Uuid::v4();

        $headers = [
            'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
            'X-Reference-Id' => $xReferenceId,
            'X-Target-Environment' => $this->environment,
            'Authorization' => 'Bearer ' . $token->getAccessToken(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Add X-Callback-Url if configured
        if (!empty($this->config->getCallbackUri())) {
            $headers['X-Callback-Url'] = $this->config->getCallbackUri();
        }

        $response = $this->client->request('POST', '/disbursement/v1_0/deposit', [
            'json' => $paymentRequest->toArray(),
            'headers' => $headers
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
     * } catch (BadResourceException|InternalServerErrorException|ResourceNotFoundException $e) {
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

        $statusCode = $response->getStatusCode();
        if ($statusCode === 200 || $statusCode === 202) {
            return Transaction::parse($response->toArray());
        }

        throw ExceptionFactory::create($response);
    }

    /**
     * Transfer an amount from the owner's account to a payee account.
     *
     * ### Sample usage
     *
     * ```
     * $request = \Lepresk\MomoApi\Models\TransferRequest::make(
     *    '1000',
     *    '242068511358',
     *    'TRANSFER-001'
     * );
     * $transferId = $momo->disbursement()->transfer($request);
     * ```
     * @param TransferRequest $transferRequest
     * @return string transfer reference id
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws MomoException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function transfer(TransferRequest $transferRequest): string
    {
        $token = $this->getAccessToken();

        $xReferenceId = Uuid::v4();

        $headers = [
            'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
            'X-Reference-Id' => $xReferenceId,
            'X-Target-Environment' => $this->environment,
            'Authorization' => 'Bearer ' . $token->getAccessToken(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Add X-Callback-Url if configured
        if (!empty($this->config->getCallbackUri())) {
            $headers['X-Callback-Url'] = $this->config->getCallbackUri();
        }

        $response = $this->client->request('POST', '/disbursement/v1_0/transfer', [
            'json' => $transferRequest->toArray(),
            'headers' => $headers
        ]);

        $responseCode = $response->getStatusCode();
        if ($responseCode === 202) {
            return $xReferenceId;
        }

        throw ExceptionFactory::create($response);
    }

    /**
     * Get the status of a transfer. X-Reference-Id that was passed in the post is used as reference to the request.
     *
     * ### Sample usage
     *
     * ```
     * $transferId = "07a461a4-e721-462b-81c6-b9aa2f8abf06";
     * try {
     *      $result = $momo->disbursement()->getTransferStatus($transferId);
     *      if($result->isSuccessful()) {
     *          echo "Transfer successful";
     *          $result->getAmount(); // 1500
     *      }
     * } catch (BadResourceException|InternalServerErrorException|ResourceNotFoundException $e) {
     *      // Request failed, do something else
     * }
     * ```
     *
     * @param string $transferId UUID of transaction to get result. Reference id used when creating the transfer.
     * @return Transaction
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws MomoException
     */
    public function getTransferStatus(string $transferId): Transaction
    {
        $token = $this->getAccessToken();
        $response = $this->client->request('GET', '/disbursement/v1_0/transfer/' . $transferId, [
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
                'X-Target-Environment' => $this->environment,
                'Authorization' => 'Bearer ' . $token->getAccessToken(),
            ]
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode === 200 || $statusCode === 202) {
            return Transaction::parse($response->toArray());
        }

        throw ExceptionFactory::create($response);
    }

    /**
     * Refund an amount to the payer.
     *
     * ### Sample usage
     *
     * ```
     * $request = \Lepresk\MomoApi\Models\RefundRequest::make(
     *    '1000',
     *    '07a461a4-e721-462b-81c6-b9aa2f8abf06', // Original transaction ID
     *    'REFUND-001'
     * );
     * $refundId = $momo->disbursement()->refund($request);
     * ```
     * @param RefundRequest $refundRequest
     * @return string refund reference id
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws MomoException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function refund(RefundRequest $refundRequest): string
    {
        $token = $this->getAccessToken();

        $xReferenceId = Uuid::v4();

        $headers = [
            'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
            'X-Reference-Id' => $xReferenceId,
            'X-Target-Environment' => $this->environment,
            'Authorization' => 'Bearer ' . $token->getAccessToken(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Add X-Callback-Url if configured
        if (!empty($this->config->getCallbackUri())) {
            $headers['X-Callback-Url'] = $this->config->getCallbackUri();
        }

        $response = $this->client->request('POST', '/disbursement/v1_0/refund', [
            'json' => $refundRequest->toArray(),
            'headers' => $headers
        ]);

        $responseCode = $response->getStatusCode();
        if ($responseCode === 202) {
            return $xReferenceId;
        }

        throw ExceptionFactory::create($response);
    }

    /**
     * Get the status of a refund. X-Reference-Id that was passed in the post is used as reference to the request.
     *
     * ### Sample usage
     *
     * ```
     * $refundId = "07a461a4-e721-462b-81c6-b9aa2f8abf06";
     * try {
     *      $result = $momo->disbursement()->getRefundStatus($refundId);
     *      if($result->isSuccessful()) {
     *          echo "Refund successful";
     *          $result->getAmount(); // 1500
     *      }
     * } catch (BadResourceException|InternalServerErrorException|ResourceNotFoundException $e) {
     *      // Request failed, do something else
     * }
     * ```
     *
     * @param string $refundId UUID of transaction to get result. Reference id used when creating the refund.
     * @return Transaction
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws MomoException
     */
    public function getRefundStatus(string $refundId): Transaction
    {
        $token = $this->getAccessToken();
        $response = $this->client->request('GET', '/disbursement/v1_0/refund/' . $refundId, [
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
                'X-Target-Environment' => $this->environment,
                'Authorization' => 'Bearer ' . $token->getAccessToken(),
            ]
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode === 200 || $statusCode === 202) {
            return Transaction::parse($response->toArray());
        }

        throw ExceptionFactory::create($response);
    }
}