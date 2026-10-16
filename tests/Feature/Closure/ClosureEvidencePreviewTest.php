<?php

use App\Models\Closure\{ClosureIssue, ClosureIssueCategory, ClosureIssueSubcategory, ClosureWorkMessage};
use App\Models\{EvidenceFile, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

require_once __DIR__ . '/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

function closureEvidenceFor($owner, string $name = 'a.pdf', string $mime = 'application/pdf', string $content = '%PDF-1'): EvidenceFile
{
    Storage::disk('public')->put("evid/{$name}", $content);

    return $owner->EvidenceFiles()->create([
        'user_id' => User::factory()->create()->id, 'original_name' => $name, 'stored_name' => $name,
        'disk'    => 'public', 'path' => "evid/{$name}", 'mime' => $mime, 'extension' => pathinfo($name, PATHINFO_EXTENSION),
        'size'    => strlen($content), 'origin' => 'CLOSURE_WORK_MESSAGE',
    ]);
}

function closureMessage(array $f, string $visibility, ?int $issueId = null): ClosureWorkMessage
{
    return ClosureWorkMessage::query()->create([
        'note_id'     => $f['note']->id, 'closure_issue_id' => $issueId, 'author_id' => $f['internal']->id,
        'author_role' => 'INTERNAL', 'body' => 'x', 'visibility' => $visibility,
    ]);
}

it('authorizes evidence preview of work messages by access level and partner visibility', function () {
    Storage::fake('public');
    $f        = closureScreenFixture();
    $internal = closureEvidenceFor(closureMessage($f, ClosureWorkMessage::VISIBILITY_INTERNAL_ONLY), 'i.pdf');
    $shared   = closureEvidenceFor(closureMessage($f, ClosureWorkMessage::VISIBILITY_WITH_PARTNER), 's.pdf');

    $this->actingAs($f['outsider'])->get(route('files.evidence.preview', $shared))->assertForbidden();
    $this->actingAs($f['partnerUser'])->get(route('files.evidence.preview', $internal))->assertForbidden();
    $this->actingAs($f['partnerUser'])->get(route('files.evidence.preview', $shared))->assertOk();
    $this->actingAs($f['internal'])->get(route('files.evidence.preview', $internal))->assertOk();
    $this->actingAs($f['dispatcher'])->get(route('files.evidence.preview', $internal))->assertOk();
});

it('hides evidence of an issue addressed to another company from the partner', function () {
    Storage::fake('public');
    $f     = closureScreenFixture();
    $cat   = ClosureIssueCategory::query()->create(['name' => 'Cat ' . fake()->unique()->numerify('###')]);
    $sub   = ClosureIssueSubcategory::query()->create(['closure_issue_category_id' => $cat->id, 'name' => 'Sub ' . fake()->unique()->numerify('###'), 'requires_description' => false]);
    $issue = ClosureIssue::query()->create([
        'order_id'                     => $f['order']->id, 'closure_order_state_id' => $f['state']->id, 'closure_issue_category_id' => $cat->id,
        'closure_issue_subcategory_id' => $sub->id, 'destination_company_id' => $f['other']->id, 'opened_by' => $f['internal']->id,
        'status'                       => 'OPEN', 'cycle' => 1, 'blocking' => false, 'opened_at' => now(),
    ]);
    $evidence = closureEvidenceFor(closureMessage($f, ClosureWorkMessage::VISIBILITY_WITH_PARTNER, $issue->id));

    $this->actingAs($f['partnerUser'])->get(route('files.evidence.preview', $evidence))->assertForbidden();
});

it('serves only raster images and pdf inline, everything else as nosniff attachment', function () {
    Storage::fake('public');
    $f       = closureScreenFixture();
    $message = closureMessage($f, ClosureWorkMessage::VISIBILITY_WITH_PARTNER);

    $pdf = $this->actingAs($f['internal'])->get(route('files.evidence.preview', closureEvidenceFor($message, 'a.pdf')));
    expect($pdf->headers->get('Content-Disposition'))->toStartWith('inline');

    foreach ([['a.svg', 'image/svg+xml', '<svg onload="alert(1)"/>'], ['a.html', 'text/html', '<script>1</script>']] as [$n, $m, $c]) {
        $res = $this->actingAs($f['internal'])->get(route('files.evidence.preview', closureEvidenceFor($message, $n, $m, $c)));
        expect($res->headers->get('Content-Disposition'))->toStartWith('attachment')
            ->and($res->headers->get('X-Content-Type-Options'))->toBe('nosniff')
            ->and($res->headers->get('Content-Type'))->toBe('application/octet-stream');
    }
});

it('keeps previewing evidence of other owners as before', function () {
    Storage::fake('public');
    $f = closureScreenFixture();
    Storage::disk('public')->put('evid/n.pdf', '%PDF-1');
    $file = EvidenceFile::query()->forceCreate([
        'user_id' => $f['outsider']->id, 'original_name' => 'n.pdf', 'stored_name' => 'n.pdf', 'disk' => 'public',
        'path'    => 'evid/n.pdf', 'mime' => 'application/pdf', 'extension' => 'pdf', 'size' => 6, 'origin' => 'OTHER', 'evidenciable_type' => \App\Models\Note::class, 'evidenciable_id' => $f['note']->id,
    ]);

    $this->actingAs($f['outsider'])->get(route('files.evidence.preview', $file))->assertOk();
});
