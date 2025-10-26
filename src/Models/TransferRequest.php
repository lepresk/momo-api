<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Models;

class TransferRequest
{
    /**
     * @var string
     */
    private string $amount;

    /**
     * @var string
     */
    private string $currency;

    /**
     * @var string
     */
    private string $externalId;

    /**
     * Payee MSISDN (phone number in international format with country code and without '+')
     *  e.g.`242068511358` Where `242` is a country code and `068511358` is a phone number
     * @var string
     */
    private string $payee;

    /**
     * @var string
     */
    private string $payerMessage;

    /**
     * @var string
     */
    private string $payeeNote;

    /**
     * @param string $amount
     * @param string $currency
     * @param string $externalId
     * @param string $payee
     * @param string $payerMessage
     * @param string $payeeNote
     */
    public function __construct(
        string $amount,
        string $currency,
        string $externalId,
        string $payee,
        string $payerMessage,
        string $payeeNote
    ) {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->externalId = $externalId;
        $this->payee = $payee;
        $this->payerMessage = $payerMessage;
        $this->payeeNote = $payeeNote;
    }

    /**
     * Static factory with sensible defaults
     *
     * @param string $amount
     * @param string $payee
     * @param string $externalId
     * @param string $currency
     * @param string $payerMessage
     * @param string $payeeNote
     * @return self
     */
    public static function make(
        string $amount,
        string $payee,
        string $externalId,
        string $currency = 'XAF',
        string $payerMessage = '',
        string $payeeNote = ''
    ): self {
        return new self($amount, $currency, $externalId, $payee, $payerMessage, $payeeNote);
    }

    /**
     * @return string
     */
    public function getAmount(): string
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
    public function getPayee(): string
    {
        return $this->payee;
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
    public function getPayeeNote(): string
    {
        return $this->payeeNote;
    }

    public function toArray(): array
    {
        return [
            "amount" => $this->amount,
            "currency" => $this->currency,
            "externalId" => $this->externalId,
            "payee" => [
                "partyIdType" => "MSISDN",
                "partyId" => $this->payee,
            ],
            "payerMessage" => $this->payerMessage,
            "payeeNote" => $this->payeeNote,
        ];
    }
}
