<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Models;

class ErrorReason
{
    public const PAYEE_NOT_FOUND = 'PAYEE_NOT_FOUND';
    public const PAYER_NOT_FOUND = 'PAYER_NOT_FOUND';
    public const NOT_ALLOWED = 'NOT_ALLOWED';
    public const NOT_ALLOWED_TARGET_ENVIRONMENT = 'NOT_ALLOWED_TARGET_ENVIRONMENT';
    public const INVALID_CALLBACK_URL_HOST = 'INVALID_CALLBACK_URL_HOST';
    public const INVALID_CURRENCY = 'INVALID_CURRENCY';
    public const SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';
    public const INTERNAL_PROCESSING_ERROR = 'INTERNAL_PROCESSING_ERROR';
    public const NOT_ENOUGH_FUNDS = 'NOT_ENOUGH_FUNDS';
    public const PAYER_LIMIT_REACHED = 'PAYER_LIMIT_REACHED';
    public const PAYEE_NOT_ALLOWED_TO_RECEIVE = 'PAYEE_NOT_ALLOWED_TO_RECEIVE';
    public const PAYMENT_NOT_APPROVED = 'PAYMENT_NOT_APPROVED';
    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const APPROVAL_REJECTED = 'APPROVAL_REJECTED';
    public const EXPIRED = 'EXPIRED';
    public const TRANSACTION_CANCELED = 'TRANSACTION_CANCELED';
    public const RESOURCE_ALREADY_EXIST = 'RESOURCE_ALREADY_EXIST';
    public const COULD_NOT_PERFORM_TRANSACTION = 'COULD_NOT_PERFORM_TRANSACTION';
    public const SENDER_ACCOUNT_NOT_ACTIVE = 'SENDER_ACCOUNT_NOT_ACTIVE';
    public const PAYEE_LIMIT_REACHED = 'PAYEE_LIMIT_REACHED';
    public const TRANSACTION_NOT_FOUND = 'TRANSACTION_NOT_FOUND';
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';
    /** MTN Congo's Get Status code for a payer with no balance, at a limit, or not allowed to pay */
    public const LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED = 'LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED';

    private string $code;
    private string $message;

    public function __construct(string $code, string $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    public static function fromArray(array $data): self
    {
        return new self($data['code'] ?? '', $data['message'] ?? '');
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function is(string $code): bool
    {
        return $this->code === $code;
    }

    public function isPayeeNotFound(): bool
    {
        return $this->is(self::PAYEE_NOT_FOUND);
    }

    public function isNotEnoughFunds(): bool
    {
        return $this->is(self::NOT_ENOUGH_FUNDS);
    }

    public function isPayerLimitReached(): bool
    {
        return $this->is(self::PAYER_LIMIT_REACHED);
    }

    public function isLowBalanceOrPayeeLimitReachedOrNotAllowed(): bool
    {
        return $this->is(self::LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED);
    }

    public function isCouldNotPerformTransaction(): bool
    {
        return $this->is(self::COULD_NOT_PERFORM_TRANSACTION);
    }

    public function isSenderAccountNotActive(): bool
    {
        return $this->is(self::SENDER_ACCOUNT_NOT_ACTIVE);
    }

    public function isPayeeLimitReached(): bool
    {
        return $this->is(self::PAYEE_LIMIT_REACHED);
    }

    public function isTransactionNotFound(): bool
    {
        return $this->is(self::TRANSACTION_NOT_FOUND);
    }

    public function isValidationError(): bool
    {
        return $this->is(self::VALIDATION_ERROR);
    }

    /**
     * The payer could not fund the payment: not enough balance or a limit reached.
     * MTN Congo reports this as LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED
     * rather than NOT_ENOUGH_FUNDS, so check this rather than isNotEnoughFunds()
     * when the market does not matter.
     */
    public function isPayerFundingFailure(): bool
    {
        return $this->isNotEnoughFunds()
            || $this->isPayerLimitReached()
            || $this->isLowBalanceOrPayeeLimitReachedOrNotAllowed();
    }

    public function __toString(): string
    {
        return $this->message === '' ? "[{$this->code}]" : "[{$this->code}] {$this->message}";
    }
}
