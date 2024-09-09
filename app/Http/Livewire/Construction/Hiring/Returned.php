<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Returned extends Component
{
    use WithFileUploads;

    public $action;

    protected $listeners = [
        'update_list' => '$refresh'
    ];

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {


                return Storage::download($file->path, $file->file_name);

            }
        }
    }

    public function getListsProperty()
    {
        // return Note::whereRelation('Viabilities', function ($q) {
        //     $q->where('engineer', true)
        //         ->where(function ($q) {
        //             $q->where('rejected', true)
        //             ->orwhere('approved', true);
        //         })->where('hired', false);
        // })
        //     ->with(['Viabilities' => function ($query) {
        //         $query->where('engineer', true)
        //         ->where(function ($q) {
        //             $q->where('rejected', true)
        //             ->orwhere('approved', true);
        //         })->where('hired', false)
        //         ->with('Company', 'User', 'Form', 'Comments.User', 'Reclaims.production');
        //     }, 'Files'])->paginate(50);

        return Note::whereRelation('viabilities', function ($q) {
            $q->whereHas('reclaims');
        })->paginate(50);
    }


    public function render()
    {
        return view('livewire.construction.hiring.returned', [
            'lists' => $this->lists
        ]);
    }
}
