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

    public function testToStringWithoutMessage()
    {
        $reason = new ErrorReason('NOT_ENOUGH_FUNDS', '');

        $this->assertEquals('[NOT_ENOUGH_FUNDS]', (string)$reason);
    }

    /**
     * @dataProvider getStatusFailureCodes
     */
    public function testGetStatusFailureCodeHasPredicate(string $code, string $predicate)
    {
        $reason = new ErrorReason($code, '');

        $this->assertSame($code, constant(ErrorReason::class . '::' . $code));
        $this->assertTrue($reason->$predicate(), "$predicate should match $code");
        $this->assertFalse((new ErrorReason('EXPIRED', ''))->$predicate());
    }

    public static function getStatusFailureCodes(): array
    {
        return [
            ['LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED', 'isLowBalanceOrPayeeLimitReachedOrNotAllowed'],
            ['COULD_NOT_PERFORM_TRANSACTION', 'isCouldNotPerformTransaction'],
            ['SENDER_ACCOUNT_NOT_ACTIVE', 'isSenderAccountNotActive'],
            ['PAYEE_LIMIT_REACHED', 'isPayeeLimitReached'],
            ['TRANSACTION_NOT_FOUND', 'isTransactionNotFound'],
            ['VALIDATION_ERROR', 'isValidationError'],
        ];
    }

    public function testIsPayerFundingFailure()
    {
        $this->assertTrue((new ErrorReason('NOT_ENOUGH_FUNDS', ''))->isPayerFundingFailure());
        $this->assertTrue((new ErrorReason('PAYER_LIMIT_REACHED', ''))->isPayerFundingFailure());
        $this->assertTrue((new ErrorReason('LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED', ''))->isPayerFundingFailure());

        $this->assertFalse((new ErrorReason('PAYER_NOT_FOUND', ''))->isPayerFundingFailure());
        $this->assertFalse((new ErrorReason('PAYEE_LIMIT_REACHED', ''))->isPayerFundingFailure());
        $this->assertFalse((new ErrorReason('EXPIRED', ''))->isPayerFundingFailure());
    }

    public function testErrorCodeConstants()
    {
        $this->assertEquals('PAYEE_NOT_FOUND', ErrorReason::PAYEE_NOT_FOUND);
        $this->assertEquals('NOT_ENOUGH_FUNDS', ErrorReason::NOT_ENOUGH_FUNDS);
        $this->assertEquals('PAYER_LIMIT_REACHED', ErrorReason::PAYER_LIMIT_REACHED);
    }
}
