<?php
declare(strict_types=1);

namespace Tests\Models;

use Lepresk\MomoApi\Models\TransferRequest;
use Tests\TestCase;

class TransferRequestTest extends TestCase
{
    public function testCreateTransferRequest()
    {
        $request = new TransferRequest(
            '1000',
            'XAF',
            'TRANSFER-001',
            '242068511358',
            'Salary payment',
            'Monthly salary'
        );

        $this->assertEquals('1000', $request->getAmount());
        $this->assertEquals('XAF', $request->getCurrency());
        $this->assertEquals('TRANSFER-001', $request->getExternalId());
        $this->assertEquals('242068511358', $request->getPayee());
        $this->assertEquals('Salary payment', $request->getPayerMessage());
        $this->assertEquals('Monthly salary', $request->getPayeeNote());
    }

    public function testMakeFactory()
    {
        $request = TransferRequest::make(
            '5000',
            '242068511358',
            'TRANSFER-002'
        );

        $this->assertEquals('5000', $request->getAmount());
        $this->assertEquals('XAF', $request->getCurrency());
        $this->assertEquals('TRANSFER-002', $request->getExternalId());
        $this->assertEquals('242068511358', $request->getPayee());
    }

    public function testToArray()
    {
        $request = new TransferRequest(
            '2500',
            'EUR',
            'TRANS-123',
            '33612345678',
            'Transfer message',
            'Transfer note'
        );

        $array = $request->toArray();

        $this->assertEquals('2500', $array['amount']);
        $this->assertEquals('EUR', $array['currency']);
        $this->assertEquals('TRANS-123', $array['externalId']);
        $this->assertEquals('MSISDN', $array['payee']['partyIdType']);
        $this->assertEquals('33612345678', $array['payee']['partyId']);
        $this->assertEquals('Transfer message', $array['payerMessage']);
        $this->assertEquals('Transfer note', $array['payeeNote']);
    }
}
