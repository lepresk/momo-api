<?php
declare(strict_types=1);

namespace Tests\Unit\Models;

use Lepresk\MomoApi\Models\RefundRequest;
use Tests\TestCase;

class RefundRequestTest extends TestCase
{
    public function testCreateRefundRequest()
    {
        $originalTxId = '07a461a4-e721-462b-81c6-b9aa2f8abf06';

        $request = new RefundRequest(
            '1000',
            'XAF',
            'REFUND-001',
            $originalTxId,
            'Refund message',
            'Refund note'
        );

        $this->assertEquals('1000', $request->getAmount());
        $this->assertEquals('XAF', $request->getCurrency());
        $this->assertEquals('REFUND-001', $request->getExternalId());
        $this->assertEquals($originalTxId, $request->getReferenceIdToRefund());
        $this->assertEquals('Refund message', $request->getPayerMessage());
        $this->assertEquals('Refund note', $request->getPayeeNote());
    }

    public function testMakeFactory()
    {
        $originalTxId = '07a461a4-e721-462b-81c6-b9aa2f8abf06';

        $request = RefundRequest::make(
            '500',
            $originalTxId,
            'REFUND-002'
        );

        $this->assertEquals('500', $request->getAmount());
        $this->assertEquals('XAF', $request->getCurrency());
        $this->assertEquals('REFUND-002', $request->getExternalId());
        $this->assertEquals($originalTxId, $request->getReferenceIdToRefund());
    }

    public function testToArray()
    {
        $originalTxId = 'a1b2c3d4-e5f6-4a5b-9c8d-1e2f3a4b5c6d';

        $request = new RefundRequest(
            '2500',
            'EUR',
            'REF-123',
            $originalTxId,
            'Refund message',
            'Refund note'
        );

        $array = $request->toArray();

        $this->assertEquals('2500', $array['amount']);
        $this->assertEquals('EUR', $array['currency']);
        $this->assertEquals('REF-123', $array['externalId']);
        $this->assertEquals($originalTxId, $array['referenceIdToRefund']);
        $this->assertEquals('Refund message', $array['payerMessage']);
        $this->assertEquals('Refund note', $array['payeeNote']);
    }
}
