<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Models;

class Transaction
{
    public const STATUS_SUCCESSFUL = 'SUCCESSFUL';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_FAILED = 'FAILED';

    private ?string $financialTransactionId;
    private ?string $externalId;
    private ?string $amount;
    private string $currency;
    private array $payer = [];
    private ?string $payerMessage;
    private ?string $payeeNote;
    private string $status;

    private ?ErrorReason $reason;

    /**
     * @param string|null $financialTransactionId
     * @param string|null $externalId
     * @param string|null $amount
     * @param string $currency
     * @param array $payer
     * @param string|null $payerMessage
     * @param string|null $payeeNote
     * @param string $status
     * @param ErrorReason|null $reason
     */
    public function __construct(?string $financialTransactionId, ?string $externalId, ?string $amount, string $currency, array $payer, ?string $payerMessage, ?string $payeeNote, string $status, ?ErrorReason $reason)
    {
        $this->financialTransactionId = $financialTransactionId;
        $this->externalId = $externalId;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->payer = $payer;
        $this->payerMessage = $payerMessage;
        $this->payeeNote = $payeeNote;
        $this->status = $status;
        $this->reason = $reason;
    }

    /**
     * Parse a transaction from an array, can be used to create a Transaction object
     * from an GET request
     *
     * @param array $array
     * @return Transaction
     */
    public static function parse(array $array): Transaction
    {
        $reason = null;
        if (isset($array['reason']) && is_array($array['reason'])) {
            $reason = ErrorReason::fromArray($array['reason']);
        }

        return new self(
            $array['financialTransactionId'] ?? null,
            $array['externalId'],
            $array['amount'],
            $array['currency'],
            $array['payer'] ?? $array['payee'] ?? [],
            $array['payerMessage'],
            $array['payeeNote'],
            $array['status'],
            $reason,
        );
    }

    /**
     * Checks if the transaction is successful
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->status === "SUCCESSFUL";
    }

    public function isPending(): bool
    {
        return $this->status === "PENDING";
    }

    public function isFailed(): bool
    {
        return $this->status === "FAILED";
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return ErrorReason|null
     */
    public function getReason(): ?ErrorReason
    {
        return $this->reason;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return string|null
     */
    public function getFinancialTransactionId(): ?string
    {
        return $this->financialTransactionId;
    }

    /**
     * @return string|null
     */
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    /**
     * @return string|null
     */
    public function getPayeeNote(): ?string
    {
        return $this->payeeNote;
    }

    /**
     * @return string|null
     */
    public function getPayer(): ?string
    {
        return $this->payer['partyId'] ?? null;
    }

    /**
     * Get payee (beneficiary) phone number
     * @return string|null
     */
    public function getPayee(): ?string
    {
        return $this->getPayer();
    }

    /**
     * @return string|null
     */
    public function getPayerMessage(): ?string
    {
        return $this->payerMessage;
    }
}