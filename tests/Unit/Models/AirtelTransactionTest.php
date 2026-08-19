<?php
declare(strict_types=1);

namespace Tests\Unit\Models;

use Lepresk\MomoApi\Models\AirtelTransaction;
use Tests\TestCase;

class AirtelTransactionTest extends TestCase
{
    public function testTiIsPendingLikeTip(): void
    {
        $transaction = AirtelTransaction::parse(['id' => 'ext-1', 'status' => 'TI']);

        $this->assertTrue($transaction->isPending());
        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isFailed());
    }

    public function testTipIsPending(): void
    {
        $this->assertTrue(AirtelTransaction::parse(['status' => 'TIP'])->isPending());
    }

    public function testTsIsSuccessful(): void
    {
        $transaction = AirtelTransaction::parse(['status' => 'TS']);

        $this->assertTrue($transaction->isSuccessful());
        $this->assertFalse($transaction->isPending());
    }

    public function testTfIsFailed(): void
    {
        $transaction = AirtelTransaction::parse(['status' => 'TF']);

        $this->assertTrue($transaction->isFailed());
        $this->assertFalse($transaction->isPending());
    }

    public function testUnknownStatusIsInNoState(): void
    {
        $transaction = AirtelTransaction::parse(['status' => 'XX']);

        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isPending());
        $this->assertFalse($transaction->isFailed());
        $this->assertSame('XX', $transaction->getStatus());
    }

    public function testExposesInProgressConstant(): void
    {
        $this->assertSame('TI', AirtelTransaction::STATUS_IN_PROGRESS);
    }

    public function testExposesReferenceId(): void
    {
        $transaction = AirtelTransaction::parse([
            'id' => 'ext-1',
            'reference_id' => 'ref-99',
            'airtel_money_id' => 'AM-1',
            'message' => 'Success',
            'status' => 'TS',
        ]);

        $this->assertSame('ext-1', $transaction->getId());
        $this->assertSame('ref-99', $transaction->getReferenceId());
        $this->assertSame('AM-1', $transaction->getAirtelMoneyId());
        $this->assertSame('Success', $transaction->getMessage());
    }

    public function testOptionalFieldsAreNullWhenAbsent(): void
    {
        $transaction = AirtelTransaction::parse(['id' => 'ext-1', 'status' => 'TS']);

        $this->assertNull($transaction->getReferenceId());
        $this->assertNull($transaction->getAirtelMoneyId());
        $this->assertNull($transaction->getMessage());
    }
}
