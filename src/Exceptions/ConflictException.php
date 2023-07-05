<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Exceptions;

class ConflictException extends MomoException
{
    public function __construct(?string $message)
    {
        parent::__construct($message ?? "Conflict, duplicated reference id", 409);
    }
}