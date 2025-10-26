<?php
declare(strict_types=1);

namespace Tests\FixtureTests;

use Lepresk\MomoApi\ApiToken;
use Lepresk\MomoApi\Models\AccountBalance;
use Lepresk\MomoApi\Models\ErrorReason;
use Lepresk\MomoApi\Models\Transaction;
use Tests\TestCase;

class CollectionFixtureTest extends TestCase
{
    public function testParseTokenSuccess()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Collection/token_success.json');
        $data = json_decode($json, true);

        $token = ApiToken::fromArray($data);

        $this->assertInstanceOf(ApiToken::class, $token);
        $this->assertStringStartsWith('eyJ', $token->getAccessToken());
        $this->assertEquals('Bearer', $token->getTokenType());
        $this->assertEquals(3600, $token->getExpiresIn());
    }

    public function testParsePaymentPending()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Collection/payment_pending.json');
        $data = json_decode($json, true);

        $transaction = Transaction::parse($data);

        $this->assertEquals('PENDING', $transaction->getStatus());
        $this->assertEquals('1000', $transaction->getAmount());
        $this->assertEquals('EUR', $transaction->getCurrency());
        $this->assertEquals('46733123454', $transaction->getPayer());
        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isFailed());
        $this->assertNull($transaction->getReason());
    }

    public function testParsePaymentSuccessful()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Collection/payment_successful.json');
        $data = json_decode($json, true);

        $transaction = Transaction::parse($data);

        $this->assertEquals('SUCCESSFUL', $transaction->getStatus());
        $this->assertEquals('2500', $transaction->getAmount());
        $this->assertEquals('EUR', $transaction->getCurrency());
        $this->assertEquals('46733123453', $transaction->getPayer());
        $this->assertEquals('987654321', $transaction->getFinancialTransactionId());
        $this->assertTrue($transaction->isSuccessful());
        $this->assertNull($transaction->getReason());
    }

    public function testParsePaymentFailedNotEnoughFunds()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Collection/payment_failed_not_enough_funds.json');
        $data = json_decode($json, true);

        $transaction = Transaction::parse($data);

        $this->assertEquals('FAILED', $transaction->getStatus());
        $this->assertEquals('5000', $transaction->getAmount());
        $this->assertEquals('XAF', $transaction->getCurrency());
        $this->assertTrue($transaction->isFailed());
        $this->assertFalse($transaction->isSuccessful());

        $this->assertInstanceOf(ErrorReason::class, $transaction->getReason());
        $this->assertEquals('NOT_ENOUGH_FUNDS', $transaction->getReason()->getCode());
        $this->assertTrue($transaction->getReason()->isNotEnoughFunds());
    }

    public function testParsePaymentFailedPayerLimit()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Collection/payment_failed_payer_limit.json');
        $data = json_decode($json, true);

        $transaction = Transaction::parse($data);

        $this->assertEquals('FAILED', $transaction->getStatus());
        $this->assertTrue($transaction->isFailed());

        $this->assertInstanceOf(ErrorReason::class, $transaction->getReason());
        $this->assertEquals('PAYER_LIMIT_REACHED', $transaction->getReason()->getCode());
        $this->assertTrue($transaction->getReason()->isPayerLimitReached());
        $this->assertFalse($transaction->getReason()->isNotEnoughFunds());
    }

    public function testParseBalance()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Collection/balance.json');
        $data = json_decode($json, true);

        $balance = AccountBalance::parse($data);

        $this->assertEquals('50000', $balance->getAvailableBalance());
        $this->assertEquals('EUR', $balance->getCurrency());
    }

    public function testParseErrorResourceNotFound()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Collection/error_resource_not_found.json');
        $data = json_decode($json, true);

        $error = ErrorReason::fromArray($data);

        $this->assertEquals('RESOURCE_NOT_FOUND', $error->getCode());
        $this->assertEquals('Requested resource was not found.', $error->getMessage());
    }
}
