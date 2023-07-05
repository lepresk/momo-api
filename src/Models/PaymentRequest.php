<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Models;

class PaymentRequest
{
    /**
     * @var float
     */
    private $amount;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $externalId;

    /**
     * Payer MSISDN (phone number in international format with country code and wihhout '+')
     *  e.g.`242068511358` Where `242` is a country code and `068511358` is a phone number
     * @var string
     */
    private $payer;

    /**
     * @var string
     */
    private $payerMessage;

    /**
     * @var string
     */
    private $payerNote;

    /**
     * @param float $amount
     * @param string $currency
     * @param string $externalId
     * @param string $payer
     * @param $payerMessage
     * @param $payerNote
     */
    public function __construct(float $amount, string $currency, string $externalId, string $payer, $payerMessage, $payerNote)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->externalId = $externalId;
        $this->payer = $payer;
        $this->payerMessage = $payerMessage;
        $this->payerNote = $payerNote;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getExternalId(): string
    {
        return $this->externalId;
    }

    /**
     * @return string
     */
    public function getPayer(): string
    {
        return $this->payer;
    }

    /**
     * @return string
     */
    public function getPayerMessage(): string
    {
        return $this->payerMessage;
    }


    /**
     * @return string
     */
    public function getPayerNote(): string
    {
        return $this->payerNote;
    }

    public function toArray(): array
    {
        return [
            "amount" => $this->amount,
            "currency" => $this->currency,
            "externalId" => $this->externalId,
            "payer" => [
                "partyIdType" => "MSISDN",
                "partyId" => $this->payer,
            ],
            "payerMessage" => $this->payerMessage,
            "payeeNote" => $this->payerNote,
        ];
    }
}