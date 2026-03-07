<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Models;

/**
 * Represents an Airtel Money transaction.
 *
 * Status codes:
 * - TS  = Transaction Successful
 * - TF  = Transaction Failed
 * - TIP = Transaction In Progress (pending)
 */
class AirtelTransaction
{
    public const STATUS_SUCCESSFUL = 'TS';
    public const STATUS_FAILED = 'TF';
    public const STATUS_PENDING = 'TIP';

    private string $id;
    private string $status;
    private ?string $airtelMoneyId;
    private ?string $message;

    private function __construct(array $data)
    {
        $this->id = (string) ($data['id'] ?? '');
        $this->status = (string) ($data['status'] ?? '');
        $this->airtelMoneyId = isset($data['airtel_money_id']) ? (string) $data['airtel_money_id'] : null;
        $this->message = isset($data['message']) ? (string) $data['message'] : null;
    }

    public static function parse(array $data): self
    {
        return new self($data);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function getAirtelMoneyId(): ?string
    {
        return $this->airtelMoneyId;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
