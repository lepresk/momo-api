<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Exceptions;

class BadRequestExeption extends MomoException
{

    public function __construct()
    {
        parent::__construct("Bad request, e.g. invalid data was sent in the request.");
    }
}