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

    public function __toString(): string
    {
        return "[{$this->code}] {$this->message}";
    }
}
