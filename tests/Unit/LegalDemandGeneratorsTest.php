<?php

namespace Tests\Unit;

use App\Services\Legal\LegalDemandHashGenerator;
use App\Services\Legal\LegalDemandKeyGenerator;
use Tests\TestCase;

class LegalDemandGeneratorsTest extends TestCase
{
    public function test_key_generator_is_stable_for_same_payload(): void
    {
        $generator = new LegalDemandKeyGenerator();

        $payload = [
            'source_type' => 'liminar',
            'source_external_id' => 'EXT-1',
            'process_number_normalized' => '50015528820268080038',
            'subject' => 'Modalidade X',
            'source_started_at' => '2026-05-01 10:00:00',
            'source_redirected_at' => '2026-05-02 10:00:00',
        ];

        $this->assertSame($generator->make($payload), $generator->make($payload));
    }

    public function test_key_generator_changes_when_relevant_field_changes(): void
    {
        $generator = new LegalDemandKeyGenerator();

        $a = $generator->make([
            'source_type' => 'liminar',
            'source_external_id' => 'EXT-1',
            'process_number_normalized' => '50015528820268080038',
            'subject' => 'Assunto A',
            'source_started_at' => '2026-05-01 10:00:00',
            'source_redirected_at' => '2026-05-02 10:00:00',
        ]);

        $b = $generator->make([
            'source_type' => 'liminar',
            'source_external_id' => 'EXT-1',
            'process_number_normalized' => '50015528820268080038',
            'subject' => 'Assunto B',
            'source_started_at' => '2026-05-01 10:00:00',
            'source_redirected_at' => '2026-05-02 10:00:00',
        ]);

        $this->assertNotSame($a, $b);
    }

    public function test_hash_generator_changes_when_due_date_changes(): void
    {
        $generator = new LegalDemandHashGenerator();

        $base = [
            'source_type' => 'sentence',
            'source_external_id' => 'EXT-2',
            'process_number_normalized' => '50015528820268080038',
            'external_status' => 'Em andamento',
            'legal_responsible_name' => 'Gestor',
            'law_firm_name' => 'Escritorio',
            'origin_area_name' => 'Origem',
            'target_area_name' => 'Destino',
            'target_person_name' => 'Pessoa',
            'subject' => 'Assunto',
            'description' => 'Descricao',
            'source_started_at' => '2026-05-01 10:00:00',
            'source_due_at' => '2026-05-10 10:00:00',
            'external_flow_status' => 'Fluxo A',
        ];

        $changed = $base;
        $changed['source_due_at'] = '2026-05-11 10:00:00';

        $this->assertNotSame($generator->make($base), $generator->make($changed));
    }
}
