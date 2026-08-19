<?php
declare(strict_types=1);

namespace Tests\Unit\Support;

use Lepresk\MomoApi\Support\Phone;
use Tests\TestCase;

class PhoneTest extends TestCase
{
    public function testStripsCongoBrazzavilleCountryCode(): void
    {
        $this->assertSame('068511358', Phone::clean('242068511358'));
    }

    public function testStripsFormattingBeforeLookingForTheCountryCode(): void
    {
        $this->assertSame('068511358', Phone::clean('+242 06 851 1358'));
        $this->assertSame('068511358', Phone::clean('(242)-06-851-1358'));
    }

    public function testLeavesANationalNumberUntouched(): void
    {
        $this->assertSame('068511358', Phone::clean('068511358'));
    }

    public function testHandlesTheMarketsItClaimsToCover(): void
    {
        $this->assertSame('712345678', Phone::clean('254712345678'));    // Kenya
        $this->assertSame('812345678', Phone::clean('243812345678'));    // RDC
        $this->assertSame('8012345678', Phone::clean('2348012345678'));  // Nigeria
    }

    public function testStripsOnlyTheFirstMatchingCountryCode(): void
    {
        $this->assertSame('243068511', Phone::clean('242243068511'));
    }

    public function testExposesTheCountryCodes(): void
    {
        $this->assertContains('242', Phone::AIRTEL_COUNTRY_CODES);
        $this->assertGreaterThanOrEqual(14, count(Phone::AIRTEL_COUNTRY_CODES));
    }
}
