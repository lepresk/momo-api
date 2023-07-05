<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Exceptions;

class BadRessourceExeption extends MomoException
{

    public function __construct()
    {
        parent::__construct("Bad request, e.g. an incorrectly formatted reference id was provided.");
    }
}