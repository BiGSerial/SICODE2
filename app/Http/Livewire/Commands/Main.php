<?php

namespace App\Http\Livewire\Commands;

use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class Main extends Component
{
    public $output = [];

    public $command;

    public function executeCommand()
    {
        // // Execute o comando Artisan e capture a saída
        // $command = 'php artisan seu_comando_aqui';
        $this->output = Artisan::call($this->command);
        // dd(Artisan::output());
        $this->output = explode("\n", Artisan::output());

    }

    public function render()
    {
        return view('livewire.commands.main');
    }
}
