<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Models;

/**
 * The `status` envelope Airtel returns alongside `data`.
 *
 * Airtel reports business failures — insufficient funds, invalid PIN, unknown
 * transaction — with HTTP 200 and `success: false` here, so it has to be
 * inspected rather than relying on the status code alone.
 */
class AirtelResponseStatus
{
    private bool $success;
    private ?string $message;
    private ?string $code;
    private ?string $resultCode;
    private ?string $responseCode;

    private function __construct(array $status)
    {
        $this->success = ($status['success'] ?? true) !== false;
        $this->message = self::optionalString($status, 'message');
        $this->code = self::optionalString($status, 'code');
        $this->resultCode = self::optionalString($status, 'result_code');
        $this->responseCode = self::optionalString($status, 'response_code');
    }

    /**
     * Returns null when the payload carries no `status` envelope.
     */
    public static function parse(array $body): ?self
    {
        if (!isset($body['status']) || !is_array($body['status'])) {
            return null;
        }

        return new self($body['status']);
    }

    private static function optionalString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Airtel's HTTP-shaped code, e.g. "500".
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Airtel's machine-readable outcome, e.g. "ESB000008".
     */
    public function getResultCode(): ?string
    {
        return $this->resultCode;
    }

    /**
     * Airtel's long-form response code, e.g. "DP00800001006".
     */
    public function getResponseCode(): ?string
    {
        return $this->responseCode;
    }
}
