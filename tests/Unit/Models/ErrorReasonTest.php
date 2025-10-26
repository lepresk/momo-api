<?php
declare(strict_types=1);

namespace Tests\Unit\Models;

use Lepresk\MomoApi\Models\ErrorReason;
use Tests\TestCase;

class ErrorReasonTest extends TestCase
{
    public function testCreateErrorReason()
    {
        $reason = new ErrorReason('NOT_ENOUGH_FUNDS', 'Insufficient balance');

        $this->assertEquals('NOT_ENOUGH_FUNDS', $reason->getCode());
        $this->assertEquals('Insufficient balance', $reason->getMessage());
    }

    public function testFromArray()
    {
        $data = [
            'code' => 'PAYER_LIMIT_REACHED',
            'message' => 'Transaction limit exceeded'
        ];

        $reason = ErrorReason::fromArray($data);

        $this->assertEquals('PAYER_LIMIT_REACHED', $reason->getCode());
        $this->assertEquals('Transaction limit exceeded', $reason->getMessage());
    }

    public function testIsMethod()
    {
        $reason = new ErrorReason('NOT_ENOUGH_FUNDS', 'Insufficient balance');

        $this->assertTrue($reason->is('NOT_ENOUGH_FUNDS'));
        $this->assertFalse($reason->is('PAYER_LIMIT_REACHED'));
    }

    public function testIsNotEnoughFunds()
    {
        $reason = new ErrorReason('NOT_ENOUGH_FUNDS', 'Insufficient balance');

        $this->assertTrue($reason->isNotEnoughFunds());
        $this->assertFalse($reason->isPayerLimitReached());
    }

    public function testIsPayerLimitReached()
    {
        $reason = new ErrorReason('PAYER_LIMIT_REACHED', 'Limit exceeded');

        $this->assertTrue($reason->isPayerLimitReached());
        $this->assertFalse($reason->isNotEnoughFunds());
    }

    public function testIsPayeeNotFound()
    {
        $reason = new ErrorReason('PAYEE_NOT_FOUND', 'Payee not found');

        $this->assertTrue($reason->isPayeeNotFound());
    }

    public function testToString()
    {
        $reason = new ErrorReason('NOT_ENOUGH_FUNDS', 'Insufficient balance');

        $this->assertEquals('[NOT_ENOUGH_FUNDS] Insufficient balance', (string)$reason);
    }

    public function testErrorCodeConstants()
    {
        $this->assertEquals('PAYEE_NOT_FOUND', ErrorReason::PAYEE_NOT_FOUND);
        $this->assertEquals('NOT_ENOUGH_FUNDS', ErrorReason::NOT_ENOUGH_FUNDS);
        $this->assertEquals('PAYER_LIMIT_REACHED', ErrorReason::PAYER_LIMIT_REACHED);
    }
}
