<?php

namespace Tests\Unit;

use App\Services\Legal\LegalSourceNormalizer;
use Tests\TestCase;

class LegalSourceNormalizerTest extends TestCase
{
    public function test_normalize_process_number_keeps_consistency(): void
    {
        $normalizer = new LegalSourceNormalizer();

        $plain = $normalizer->normalizeProcessNumber('50015528820268080038');
        $formatted = $normalizer->normalizeProcessNumber('5001552-88.2026.8.08.0038');

        $this->assertSame('50015528820268080038', $plain);
        $this->assertSame($plain, $formatted);
    }

    public function test_normalize_process_number_handles_null_and_spaces(): void
    {
        $normalizer = new LegalSourceNormalizer();

        $this->assertNull($normalizer->normalizeProcessNumber(null));
        $this->assertSame(
            '50015528820268080038',
            $normalizer->normalizeProcessNumber('  5001552-88.2026.8.08.0038  ')
        );
    }

    public function test_parse_external_date_supported_formats_and_null(): void
    {
        $normalizer = new LegalSourceNormalizer();

        $d1 = $normalizer->parseExternalDate('08/04/2026 03:00:00');
        $d2 = $normalizer->parseExternalDate('2026-04-15 10:55:07.405');
        $d3 = $normalizer->parseExternalDate('08/05/2026 00:00:00');
        $d4 = $normalizer->parseExternalDate('NULL');
        $d5 = $normalizer->parseExternalDate('data-invalida');

        $this->assertNotNull($d1);
        $this->assertNotNull($d2);
        $this->assertNotNull($d3);
        $this->assertNull($d4);
        $this->assertNull($d5);
    }
}
