<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Exceptions;

class InternalServerErrorException extends MomoException
{
    public function __construct(?string $message)
    {
        $message = $message ?? "Internal Error. Note that if the retrieved request to pay has failed, it will not cause this status to be returned. This status is only returned if the GET request itself fails.";
        parent::__construct($message);
    }
}