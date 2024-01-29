<?php

namespace App\Console\Commands\Update;

use App\Models\Manualnote;
use App\Models\Note;
use App\Models\Production;
use App\Models\User;
use Illuminate\Console\Command;

class ConfirmManual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:confirm-manual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Waiting List to Count Production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("CHECKING WAITING LIST WITH BASE ... ");

        $waiting_list = Manualnote::where('cancel', true)->where('confirmed', false)->update([
            'confirmed' => true,
            'finish_at' => date('Y-m-d H:i:s')
        ]);

        if ($waiting_list) {
            $this->info("CANCEL NOTES FROM WAITING LIST.... DONE.");
        } else {
            $this->comment("CANCEL NOTES FROM WAITING LIST.... NO NOTES TO CANCEL.");
        }

        $waiting_list = Manualnote::where('completed', true)->where('cancel', false)->where('confirmed', false)->get();

        foreach ($waiting_list as $list) {
            $note = Note::where('note', trim($list->note))->first();

            if($note) {
                try {
                    $prod = Production::Create([
                        'note_id' => $note->id,
                        'service_id' => $list->service_id,
                        'user_id' => $list->user_id,
                        'company_id' => (User::with('Employee')->find($list->user_id))->Employee->Contract->company_id,
                        'dispatch_by' => $list->user_id,
                        'att_by' => $list->user_id,
                        'dt_note' => $note->dt_status,
                        'status_note' => $list->status,
                        'dispatch_at' => $list->created_at,
                        'att_at' => $list->created_at,
                        'completed_at' => $list->finish_at,
                        'completed' => true,
                        'status' => 5,
                        'manual' => true,
                    ]);

                    $list->update([
                        'confirmed' => true
                    ]);

                    $this->info($list->note . " NOTE CONFIRMED...");

                } catch (\Throwable $th) {
                    $this->comment($list->note . " NOTE ERROR...");
                }
            }
        }
    }
}
