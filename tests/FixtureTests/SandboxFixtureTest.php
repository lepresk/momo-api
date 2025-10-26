<?php
declare(strict_types=1);

namespace Tests\FixtureTests;

use Tests\TestCase;

class SandboxFixtureTest extends TestCase
{
    public function testParseApiUserKey()
    {
        $json = file_get_contents(__DIR__ . '/../Fixtures/Sandbox/apiuser_key.json');
        $data = json_decode($json, true);

        $this->assertArrayHasKey('apiKey', $data);
        $this->assertIsString($data['apiKey']);
        $this->assertEquals(32, strlen($data['apiKey']));
    }
}
