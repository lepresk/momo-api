<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Models;

class RefundRequest
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
     * @var string
     */
    private string $payerMessage;

    /**
     * @var string
     */
    private string $payeeNote;

    /**
     * UUID of the original transaction to refund
     * @var string
     */
    private string $referenceIdToRefund;

    /**
     * @param string $amount
     * @param string $currency
     * @param string $externalId
     * @param string $referenceIdToRefund
     * @param string $payerMessage
     * @param string $payeeNote
     */
    public function __construct(
        string $amount,
        string $currency,
        string $externalId,
        string $referenceIdToRefund,
        string $payerMessage = '',
        string $payeeNote = ''
    ) {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->externalId = $externalId;
        $this->referenceIdToRefund = $referenceIdToRefund;
        $this->payerMessage = $payerMessage;
        $this->payeeNote = $payeeNote;
    }

    /**
     * Static factory with sensible defaults
     *
     * @param string $amount
     * @param string $referenceIdToRefund
     * @param string $externalId
     * @param string $currency
     * @param string $payerMessage
     * @param string $payeeNote
     * @return self
     */
    public static function make(
        string $amount,
        string $referenceIdToRefund,
        string $externalId,
        string $currency = 'XAF',
        string $payerMessage = '',
        string $payeeNote = ''
    ): self {
        return new self($amount, $currency, $externalId, $referenceIdToRefund, $payerMessage, $payeeNote);
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
    public function getReferenceIdToRefund(): string
    {
        return $this->referenceIdToRefund;
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
            "payerMessage" => $this->payerMessage,
            "payeeNote" => $this->payeeNote,
            "referenceIdToRefund" => $this->referenceIdToRefund,
        ];
    }
}
