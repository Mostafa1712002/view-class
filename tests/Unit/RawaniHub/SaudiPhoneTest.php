<?php

namespace Tests\Unit\RawaniHub;

use App\Modules\RawaniHub\Support\SaudiPhone;
use PHPUnit\Framework\TestCase;

class SaudiPhoneTest extends TestCase
{
    public function test_every_saudi_mobile_spelling_has_one_canonical_form(): void
    {
        foreach (['0501234567', '501234567', '966501234567', '+966501234567', '00966501234567', '+966 050 123 4567', '050-123-4567'] as $raw) {
            $this->assertSame('966501234567', SaudiPhone::canonical($raw), $raw);
        }
        $this->assertSame('0112345678', SaudiPhone::canonical('011 234 5678')); // landline: digits only
        $this->assertSame('12025550100', SaudiPhone::canonical('+1 202 555 0100'));
        $this->assertNull(SaudiPhone::canonical(null));
        $this->assertNull(SaudiPhone::canonical(' - '));
    }

    public function test_spellings_round_trip_to_the_canonical(): void
    {
        foreach (SaudiPhone::spellings('966501234567') as $s) {
            $this->assertSame('966501234567', SaudiPhone::canonical($s), $s);
        }
        $this->assertSame(['12025550100', '+12025550100'], SaudiPhone::spellings('12025550100'));
    }
}
