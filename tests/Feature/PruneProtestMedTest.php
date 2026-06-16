<?php

namespace Tests\Feature;

use App\Models\MedProtest;
use App\Models\Protest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PruneProtestMedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_closes_stale_meda_with_job_and_deletes_stale_meda_without_job(): void
    {
        Carbon::setTestNow('2026-06-15 14:30:00');

        $protest = Protest::create([
            'nota' => '123456',
            'tipoNota' => 'ME',
        ]);

        $withJob = MedProtest::create([
            'protest_id' => $protest->id,
            'med_id' => 1,
            'statusSist' => 'MEDA',
        ]);

        $withoutJob = MedProtest::create([
            'protest_id' => $protest->id,
            'med_id' => 2,
            'statusSist' => 'MEDA',
        ]);

        $recent = MedProtest::create([
            'protest_id' => $protest->id,
            'med_id' => 3,
            'statusSist' => 'MEDA',
        ]);

        DB::table('med_protests')
            ->whereIn('id', [$withJob->id, $withoutJob->id])
            ->update(['updated_at' => now()->subHours(2)]);

        $user = User::factory()->create();

        DB::table('protest_jobs')->insert([
            'protest_id' => $protest->id,
            'med_protest_id' => $withJob->id,
            'created_by' => $user->id,
            'owner_id' => $user->id,
            'status' => 'opened',
            'priority' => 'normal',
            'need_evidence' => false,
            'is_advance' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('sicode:prune-protest-med', ['--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('med_protests', [
            'id' => $withJob->id,
            'statusSist' => 'MEDE',
            'dtFimMedida' => '2026-06-15',
        ]);
        $this->assertDatabaseMissing('med_protests', ['id' => $withoutJob->id]);
        $this->assertDatabaseHas('med_protests', [
            'id' => $recent->id,
            'statusSist' => 'MEDA',
            'dtFimMedida' => null,
        ]);
    }
}
