<?php
declare(strict_types=1);

namespace Tests\Unit\Products;

use Lepresk\MomoApi\Models\PaymentRequest;
use Lepresk\MomoApi\Models\RefundRequest;
use Lepresk\MomoApi\Models\TransferRequest;
use Lepresk\MomoApi\MomoApi;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\TestCase;

/**
 * The sandbox accepts EUR only: https://momodeveloper.mtn.com/api-documentation/testing
 */
class SandboxCurrencyTest extends TestCase
{
    /**
     * Run $submit against $environment and return the currency it sent.
     */
    private function sentCurrency(string $environment, string $product, callable $submit): string
    {
        $sent = null;
        $expectedRequests = [
            function (): MockResponse {
                return new MockResponse(json_encode([
                    'access_token' => 'testToken',
                    'expires_in' => 3600,
                    'token_type' => 'Bearer'
                ]), ['http_code' => 200]);
            },
            function ($method, $url, $options) use (&$sent): MockResponse {
                $sent = json_decode($options['body'], true)['currency'];
                return new MockResponse('', ['http_code' => 202]);
            },
        ];

        MomoApi::useClient($this->provideClient($expectedRequests));
        $api = MomoApi::$product([
            'environment' => $environment,
            'subscription_key' => 'testSubKey',
            'api_user' => 'apiUser',
            'api_key' => 'apiKey',
            'callback_url' => ''
        ]);
        $submit($api);

        return $sent;
    }

    public static function submissions(): array
    {
        return [
            'requestToPay' => ['collection', function ($api) {
                $api->requestToPay(PaymentRequest::make('100', '242068511358', 'ORDER-1'));
            }],
            'quickPay' => ['collection', function ($api) {
                $api->quickPay('100', '242068511358', 'ORDER-1');
            }],
            'deposit' => ['disbursement', function ($api) {
                $api->deposit(PaymentRequest::make('100', '242068511358', 'ORDER-1'));
            }],
            'transfer' => ['disbursement', function ($api) {
                $api->transfer(TransferRequest::make('100', '242068511358', 'PAY-1'));
            }],
            'refund' => ['disbursement', function ($api) {
                $api->refund(RefundRequest::make('100', 'origin-ref', 'REFUND-1'));
            }],
        ];
    }

    /**
     * @dataProvider submissions
     */
    public function testSendsEurInSandboxWhateverTheRequestCurrency(string $product, callable $submit)
    {
        $this->assertSame('EUR', $this->sentCurrency(MomoApi::ENVIRONMENT_SANDBOX, $product, $submit));
    }

    /**
     * @dataProvider submissions
     */
    public function testKeepsTheRequestCurrencyOutsideSandbox(string $product, callable $submit)
    {
        $this->assertSame('XAF', $this->sentCurrency(MomoApi::ENVIRONMENT_MTN_CONGO, $product, $submit));
    }

    public function testDoesNotChangeTheRequestObject()
    {
        $request = PaymentRequest::make('100', '242068511358', 'ORDER-1');

        $this->sentCurrency(MomoApi::ENVIRONMENT_SANDBOX, 'collection', function ($api) use ($request) {
            $api->requestToPay($request);
        });

        $this->assertSame('XAF', $request->getCurrency());
    }
}
