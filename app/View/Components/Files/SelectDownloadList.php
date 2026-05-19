<?php

namespace App\View\Components\Files;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SelectDownloadList extends Component
{
    public function __construct(public $files, public bool $latestOnly = false)
    {
        if ($latestOnly && $files) {
            $this->files = $this->filterLatestRevisions($files);
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.files.select-download-list');
    }

    private function filterLatestRevisions($files)
    {
        $groups = [];

        foreach ($files as $file) {
            $serviceKey = $file->service_id ?? 'null';

            if (preg_match('/^(.*)_Rev[-_]?(\d+)$/i', $file->file_name, $m)) {
                $base = $m[1];
                $rev  = (int) $m[2];
            } else {
                $base = $file->file_name;
                $rev  = -1;
            }

            $key = $serviceKey . '::' . $base;

            if (!isset($groups[$key]) || $rev > $groups[$key]['rev']) {
                $groups[$key] = ['file' => $file, 'rev' => $rev];
            }
        }

        $latestIds = array_map(fn ($g) => $g['file']->id, array_values($groups));

        return $files->whereIn('id', $latestIds);
    }
}
