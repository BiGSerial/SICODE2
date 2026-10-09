<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Storage, URL};
use Tests\TestCase;

class ExportDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_download_is_limited_to_the_user_in_the_signed_link(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('exports/relatorio.xlsx', 'conteúdo de teste');
        $owner       = User::factory()->create();
        $anotherUser = User::factory()->create();
        $url         = URL::temporarySignedRoute('exports.download', now()->addMinute(), [
            'user' => $owner->id,
            'file' => 'relatorio.xlsx',
        ]);

        $this->actingAs($owner)->get($url)
            ->assertOk()
            ->assertDownload('relatorio.xlsx');

        $this->actingAs($anotherUser)->get($url)->assertForbidden();
    }
}
