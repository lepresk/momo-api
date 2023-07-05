<?php
declare(strict_types=1);

namespace Tests;

use Lepresk\MomoApi\Utilities;

class UtilitiesTest extends TestCase
{
    public function testValidGuidv4()
    {
        $guidv4 = Utilities::guidv4();
        $this->assertValidGuidV4($guidv4);
    }
}