<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Histhiring extends Component
{
    use WithFileUploads;

    protected $listeners = [
        'update_list' => '$refresh'
    ];

    public function downloadFile($id)
    {


        if ($file = File::find($id)) {

            // dd($file->file_name);

            if (Storage::disk('local')->exists($file->path)) {


                return Storage::download($file->path, $file->file_name);

            }
        }
    }

    public function getListsProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            $q->where('user_id', auth()->user()->id)
                ->where('hired', true);
        })
            ->with(['Viabilities' => function ($query) {
                $query->where('hired', true)
                ->with('Company', 'User', 'Form', 'Comments.User');
            }, 'Files'])->paginate(50);
    }



    public function render()
    {
        return view('livewire.construction.hiring.histhiring', [
            'lists' => $this->lists
        ]);
    }
}
