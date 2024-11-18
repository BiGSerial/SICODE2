<?php

namespace App\Http\Livewire\Config\System;

use App\Services\LogService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class JobsServices extends Component
{
    public $logs;
    public $jobs; // Jobs pendentes e em execução
    public $failedJobs; // Jobs que falharam

    public function mount(LogService $logService)
    {
        // $this->logs = $logService->getLogs();
        $this->jobs = DB::table('jobs')->get(); // Jobs pendentes ou em execução
        $this->failedJobs = DB::table('failed_jobs')->get(); // Jobs que falharam
    }

    public function restartJob($id)
    {
        $job = DB::table('failed_jobs')->find($id);
        if ($job) {
            DB::table('failed_jobs')->where('id', $id)->delete();
            DB::table('jobs')->insert([
                'queue' => $job->queue,
                'payload' => $job->payload,
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp
            ]);

            session()->flash('message', 'Job reiniciado com sucesso!');
        } else {
            session()->flash('error', 'Job não encontrado!');
        }
    }

    public function render()
    {
        return view('livewire.config.system.jobs-services');
    }
}
