<?php
declare(strict_types=1);

namespace Tests\FixtureTests;

use Lepresk\MomoApi\Models\ApiToken;
use Lepresk\MomoApi\Models\AccountBalance;
use Lepresk\MomoApi\Models\ErrorReason;
use Lepresk\MomoApi\Models\Transaction;
use Tests\TestCase;

class DisbursementFixtureTest extends TestCase
{
    public function testParseTokenSuccess()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Disbursement/token_success.json');
        $data = json_decode($json, true);

        $token = ApiToken::fromArray($data);

        $this->assertInstanceOf(ApiToken::class, $token);
        $this->assertStringStartsWith('eyJ', $token->getAccessToken());
        $this->assertEquals('Bearer', $token->getTokenType());
        $this->assertEquals(3600, $token->getExpiresIn());
    }

    public function testParseTransferSuccessful()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Disbursement/transfer_successful.json');
        $data = json_decode($json, true);

        $transaction = Transaction::parse($data);

        $this->assertEquals('SUCCESSFUL', $transaction->getStatus());
        $this->assertEquals('100', $transaction->getAmount());
        $this->assertEquals('UGX', $transaction->getCurrency());
        $this->assertEquals('4609274685', $transaction->getPayee());
        $this->assertEquals('363440463', $transaction->getFinancialTransactionId());
        $this->assertTrue($transaction->isSuccessful());
        $this->assertNull($transaction->getReason());
    }

    public function testParseTransferFailedLimitReached()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Disbursement/transfer_failed_limit_reached.json');
        $data = json_decode($json, true);

        $transaction = Transaction::parse($data);

        $this->assertEquals('FAILED', $transaction->getStatus());
        $this->assertTrue($transaction->isFailed());
        $this->assertFalse($transaction->isSuccessful());

        $this->assertInstanceOf(ErrorReason::class, $transaction->getReason());
        $this->assertEquals('PAYER_LIMIT_REACHED', $transaction->getReason()->getCode());
        $this->assertTrue($transaction->getReason()->isPayerLimitReached());
    }

    public function testParseTransferFailedNotEnoughFunds()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Disbursement/transfer_failed_not_enough_funds.json');
        $data = json_decode($json, true);

        $transaction = Transaction::parse($data);

        $this->assertEquals('FAILED', $transaction->getStatus());
        $this->assertTrue($transaction->isFailed());

        $this->assertInstanceOf(ErrorReason::class, $transaction->getReason());
        $this->assertEquals('NOT_ENOUGH_FUNDS', $transaction->getReason()->getCode());
        $this->assertTrue($transaction->getReason()->isNotEnoughFunds());
        $this->assertFalse($transaction->getReason()->isPayerLimitReached());
    }

    public function testParseTransferPending()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Disbursement/transfer_pending.json');
        $data = json_decode($json, true);

        $transaction = Transaction::parse($data);

        $this->assertEquals('PENDING', $transaction->getStatus());
        $this->assertEquals('250', $transaction->getAmount());
        $this->assertEquals('XAF', $transaction->getCurrency());
        $this->assertEquals('242068511358', $transaction->getPayee());
        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isFailed());
        $this->assertNull($transaction->getReason());
    }

    public function testParseDepositSuccessful()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Disbursement/deposit_successful.json');
        $data = json_decode($json, true);

        $transaction = Transaction::parse($data);

        $this->assertEquals('SUCCESSFUL', $transaction->getStatus());
        $this->assertEquals('500', $transaction->getAmount());
        $this->assertEquals('EUR', $transaction->getCurrency());
        $this->assertEquals('46733123454', $transaction->getPayee());
        $this->assertTrue($transaction->isSuccessful());
    }

    public function testParseRefundSuccessful()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Disbursement/refund_successful.json');
        $data = json_decode($json, true);

        $transaction = Transaction::parse($data);

        $this->assertEquals('SUCCESSFUL', $transaction->getStatus());
        $this->assertEquals('100', $transaction->getAmount());
        $this->assertEquals('UGX', $transaction->getCurrency());
        $this->assertTrue($transaction->isSuccessful());
    }

    public function testParseRefundPending()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Disbursement/refund_pending.json');
        $data = json_decode($json, true);

        $transaction = Transaction::parse($data);

        $this->assertEquals('PENDING', $transaction->getStatus());
        $this->assertEquals('150', $transaction->getAmount());
        $this->assertFalse($transaction->isSuccessful());
    }

    public function testParseBalance()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Disbursement/balance.json');
        $data = json_decode($json, true);

        $balance = AccountBalance::parse($data);

        $this->assertEquals('1000000', $balance->getAvailableBalance());
        $this->assertEquals('XAF', $balance->getCurrency());
    }

    public function testParseErrorResourceNotFound()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Disbursement/error_resource_not_found.json');
        $data = json_decode($json, true);

        $error = ErrorReason::fromArray($data);

        $this->assertEquals('RESOURCE_NOT_FOUND', $error->getCode());
        $this->assertEquals('Requested resource was not found.', $error->getMessage());
    }
}
