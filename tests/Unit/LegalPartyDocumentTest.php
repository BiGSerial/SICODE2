<?php

namespace Tests\Unit;

use App\Support\Legal\LegalPartyDocument;
use Tests\TestCase;

class LegalPartyDocumentTest extends TestCase
{
    public function testValidatesCpfCheckDigits(): void
    {
        $this->assertTrue(LegalPartyDocument::validate('529.982.247-25'));
        $this->assertFalse(LegalPartyDocument::validate('529.982.247-24'));
        $this->assertFalse(LegalPartyDocument::validate('111.111.111-11'));
    }

    public function testValidatesCnpjCheckDigits(): void
    {
        $this->assertTrue(LegalPartyDocument::validate('04.252.011/0001-10'));
        $this->assertFalse(LegalPartyDocument::validate('04.252.011/0001-11'));
        $this->assertFalse(LegalPartyDocument::validate('00.000.000/0000-00'));
    }

    public function testFormatsAndMasksDocuments(): void
    {
        $this->assertSame('529.982.247-25', LegalPartyDocument::format('52998224725'));
        $this->assertSame('04.252.011/0001-10', LegalPartyDocument::format('04252011000110'));
        $this->assertSame('***.***.***-25', LegalPartyDocument::mask('52998224725'));
        $this->assertSame('**.***.***/****-10', LegalPartyDocument::mask('04252011000110'));
    }
}
