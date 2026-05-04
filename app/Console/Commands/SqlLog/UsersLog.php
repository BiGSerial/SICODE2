<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\SicodeSql\UserSqlLog;
use App\Models\User;
use Illuminate\Console\Command;

class UsersLog extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:users-log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Users Log';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userCount = User::count();
        $progressBar = $this->createProgressBar($userCount);
        $progressBar->setFormat(' <bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s% %message%');

        $progressBar->start();

        User::withTrashed()->chunk(50, function ($chunk) use ($progressBar) {
            foreach ($chunk as $user) {
                UserSqlLog::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'name' => $user->name,
                        'Registration' => $user->Registration,
                        'email' => $user->email,
                        'company' => isset($user->Employee->Contract->Company) ? $user->Employee->Contract->Company->name : null,
                        'superadm' => $user->superadm ? true : false,
                        'admin' => $user->admin ? true : false,
                        'management' => $user->management ? true : false,
                        'operator' => $user->operator ? true : false,
                        'user' => $user->user ? true : false,
                        'contract' => $user->contract ? true : false,
                        'responsible' => $user->responsible ? true : false,
                        'engineer' => $user->engineer ? true : false,
                        'onlyparner' => $user->onlyparner ? true : false,
                        'deleted' => $user->deleted ? true : false,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                        'deleted_at' => $user->deleted_at,
                    ]
                );
            }
            $progressBar->advance(count($chunk));
        });

        $progressBar->finish();
    }
}
