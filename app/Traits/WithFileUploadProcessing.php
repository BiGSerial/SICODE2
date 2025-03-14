<?php

namespace App\Traits;

use App\Models\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

trait WithFileUploadProcessing
{
    public $files = [];
    public $tempFiles = [];
    public $uploadType = 'ADS'; // Default upload type, can be customized

    public function updatedFiles()
    {
        $this->validate([
            'files.*' => 'nullable|file|mimes:jpg,png,pdf,doc,docx,odt,xls,xlsx,xlsm,ods|max:10240',
        ]);

        $this->prepareFilesForSave();
    }

    public function prepareFilesForSave(): void
    {
        $tempFiles = [];
        foreach ($this->files as $file) {
            $tempFiles[] = [
                'file' => $file,
                'uploadType' => $this->uploadType,
                'note_id' => $this->note->id, // Make sure $note is accessible from the component
                'user_id' => Auth()->user()->id,
                'service_id' => null,
                'original_name' => $file->getClientOriginalName(),
                'suspicious' => false,
                'noexists' => false,
            ];
        }

        // Group, rename, and set newNames
        $this->tempFiles = $this->renameFiles($tempFiles);
    }

    private function generateFilename($file, $index, $count): string
    {
        $service_abrev = 'IPARC'; // You may need to customize this
        $newName = $file['uploadType'] . "_" . $service_abrev . "_" . $this->note->note . "_F" . str_pad($index + 1, 2, '0', STR_PAD_LEFT) . "-" . str_pad($count, 2, '0', STR_PAD_LEFT);
        $rev = File::where('file_name', 'like', $newName . "%")->count();
        return $newName . "_Rev" . $rev;
    }

    public function renameFiles(array $files): array
    {
        $groupedFiles = collect($files)->groupBy('uploadType');
        $tempFiles = [];

        foreach ($groupedFiles as $uploadType => $filesOfType) {
            $count = count($filesOfType);
            foreach ($filesOfType as $index => $file) {
                $newName = $this->generateFilename($file, $index, $count);
                $file['newName'] = $newName;
                $tempFiles[] = $file;
            }
        }

        // Flatten the grouped files back into a single array.
        return $tempFiles;
    }

    public function removeFile($index)
    {
        if (isset($this->tempFiles[$index])) {
            unset($this->tempFiles[$index]);
            $this->tempFiles = array_values($this->tempFiles); // Re-index array
            $this->prepareFilesForSave(); //Re process the files after remove one.
        }
    }

    public function cleanAllFiles()
    {
        foreach ($this->tempFiles as $file) {
            if (Storage::exists($file['file']->temporaryUrl())) {
                Storage::delete($file['file']->temporaryUrl());
            }
        }
        $this->tempFiles = [];
        $this->files = []; // Reset the file input
        $this->resetErrorBag(); // Clear validation errors
    }
}
