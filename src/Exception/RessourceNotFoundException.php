<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Exception;

class RessourceNotFoundException extends MomoException
{
    public function __construct(?string $message, int $code = 404)
    {
        parent::__construct($message ?? 'Not found, reference id not found or closed in sandbox', $code);
    }
}