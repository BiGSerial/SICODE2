<?php

namespace App\Http\Controllers;

use App\Models\Legal\LegalDemandFile;
use App\Services\Legal\{LegalDemandFileService, LegalDemandUploadService};
use Illuminate\Http\Request;

class LegalController extends Controller
{
    private function ensureSubdemandsFeatureEnabled(): void
    {
        abort_unless(config('features.legal_subdemands', true), 404);
    }

    public function queue()
    {
        return view('legal.controller.queue');
    }

    public function triage()
    {
        return view('legal.controller.triage');
    }

    public function demandDetail(string $uuid)
    {
        return view('legal.controller.detail', compact('uuid'));
    }

    public function file(LegalDemandFile $file, Request $request, LegalDemandFileService $files, LegalDemandUploadService $storage)
    {
        abort_if($file->removed_at !== null, 404);

        if (auth()->check()) {
            abort_unless($files->canView($file, $request->user()), 403);
        } else {
            abort_unless($request->hasValidSignature(), 403);
            abort_unless(in_array($file->visibility, ['shared', 'external_ready'], true), 403);
        }

        abort_unless($storage->exists($file), 404);

        $name = $file->downloadName();

        if ($request->boolean('download')) {
            return $storage->download($file, $name);
        }

        return response($storage->content($file), 200, [
            'Content-Type'        => $file->mime_type ?: $storage->mimeType($file) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($name) . '"',
        ]);
    }

    public function subdemandMonitor()
    {
        $this->ensureSubdemandsFeatureEnabled();

        return view('legal.controller.subdemand-monitor');
    }

    public function subdemandDetail(string $uuid)
    {
        $this->ensureSubdemandsFeatureEnabled();

        return view('legal.controller.subdemand-detail', compact('uuid'));
    }

    public function fieldQueue()
    {
        return view('legal.field.assignments');
    }

    public function fieldResponse(string $uuid)
    {
        return view('legal.field.response', compact('uuid'));
    }

    public function fieldResponseExternal(int $assignment_id)
    {
        $assignment = \App\Models\Legal\LegalDemandAssignment::findOrFail($assignment_id);
        $uuid       = $assignment->uuid;

        return view('legal.field.response_external', compact('uuid'));
    }

    public function externalExpired()
    {
        return view('legal.field.response_external_expired');
    }

    public function subdemandResponseExternal(string $token)
    {
        $this->ensureSubdemandsFeatureEnabled();

        return view('legal.field.subdemand_external_response', compact('token'));
    }

    public function dashboard()
    {
        return view('legal.management.dashboard');
    }

    public function cases()
    {
        return view('legal.management.cases');
    }

    public function reports()
    {
        return view('legal.management.reports');
    }
}
