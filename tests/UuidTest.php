<?php
declare(strict_types=1);

namespace Tests;

use Lepresk\MomoApi\Support\Uuid;

class UuidTest extends TestCase
{
    public function testValidGuidv4()
    {
        $guidv4 = Uuid::v4();
        $this->assertValidGuidV4($guidv4);
    }
}