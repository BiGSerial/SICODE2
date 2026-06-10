<?php

namespace Tests\Unit;

use App\Models\ExternalPoolpayment;
use PHPUnit\Framework\TestCase;

class ExternalPoolpaymentTest extends TestCase
{
    public function test_it_normalizes_prefixed_pool_ids(): void
    {
        $payment = new ExternalPoolpayment();
        $payment->pool_id = ' hrc0008140 ';

        $this->assertSame('HRC0008140', $payment->pool_id);
        $this->assertMatchesRegularExpression(
            ExternalPoolpayment::POOL_ID_PATTERN,
            $payment->pool_id
        );
    }

    public function test_it_accepts_prefixes_other_than_hrc(): void
    {
        $payment = new ExternalPoolpayment();
        $payment->pool_id = 'abc0008140';

        $this->assertSame('ABC0008140', $payment->pool_id);
        $this->assertMatchesRegularExpression(
            ExternalPoolpayment::POOL_ID_PATTERN,
            $payment->pool_id
        );
    }

    public function test_it_keeps_legacy_numeric_pool_ids_compatible(): void
    {
        $payment = new ExternalPoolpayment();
        $payment->pool_id = 123456;

        $this->assertSame('123456', $payment->pool_id);
        $this->assertMatchesRegularExpression(
            ExternalPoolpayment::POOL_ID_PATTERN,
            $payment->pool_id
        );
    }

    public function test_it_rejects_unrecognized_pool_id_formats(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            ExternalPoolpayment::POOL_ID_PATTERN,
            'ABC-123'
        );
    }
}
