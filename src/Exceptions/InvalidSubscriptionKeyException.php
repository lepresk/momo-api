<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Exceptions;

class InvalidSubscriptionKeyException extends MomoException
{
    public function __construct(?string $message)
    {
        parent::__construct($message ?? 'Invalid subscription key', 401);
    }
}