<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Support;

use Exception;
use InvalidArgumentException;

class Uuid
{
    /**
     * Generate random GUIDv4
     *
     * @return string
     * @throws Exception
     */
    public static function v4(): string
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Throw unless $value is a UUID, before anything is sent. Any version is
     * accepted: callers may derive a deterministic v5 UUID from a business key.
     *
     * @throws InvalidArgumentException
     */
    public static function assert(string $value, string $name): void
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            throw new InvalidArgumentException(sprintf('%s must be a UUID, got "%s"', $name, $value));
        }
    }
}
