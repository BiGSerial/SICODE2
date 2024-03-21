<?php

namespace App\Console\Commands\Setup;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\select;

class Adminpass extends Command
{
    protected $adminEmail;

    protected $adminInfo;

    protected $adminName;

    protected $adminPassword;

    protected $adminRePassword;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:admin_pass';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset Admins Info';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $version = (object) json_decode(file_get_contents(base_path('appver.json')));

        // Detectar o sistema operacional
        $os = PHP_OS;

        // Exibir o sistema operacional
        $this->info('Sistema Operacional: ' . $os);

        // Limpar o terminal com base no sistema operacional
        if ($os === 'WINNT') {
            // Windows
            system('cls');

        } else {
            // macOS/Linux
            system('clear');
        }
        $art = "
        _____  _____   _____   ____   _____   ______
        / ____||_   _| / ____| / __ \ |  __ \ |  ____|
       | (___    | |  | |     | |  | || |  | || |__
        \___ \   | |  | |     | |  | || |  | ||  __|
        ____) | _| |_ | |____ | |__| || |__| || |____
       |_____/ |_____| \_____| \____/ |_____/ |______|TM
                 Ver: {$version->appver}
       _________________________________________________
    
            by: Will Oliveira
            A EDP CIP-ES Project Controller
       _________________________________________________
        ";

        $this->info($art);

        $this->info('Starting Admin process...');
        $this->line('');

        $this->adminInfo = User::Where('superadm', true)->first();

        if (!$this->adminInfo) {
            $this->comment('No has Admin user!');
            $this->info('Run: app:init');

            return;
        }
        // Etapa 2: Criação do usuário administrador

        $sel = 'xpto';

        while ($sel != 'x') {

            $this->comment('Step 1: Admins Info...');
            $this->comment('Admin Name:');
            $this->info($this->adminInfo->name);
            $this->comment('Admin Email:');
            $this->info($this->adminInfo->email);
            $this->line('');

            // $sel = $this->ask('(A).Change Name (E).Change Email (P).Change Password (X).Exit');
            $sel = select(
                label: 'Select one Option:',
                options: [
                    'A' => 'Change Name',
                    'E' => 'Change Email',
                    'P' => 'Change Password',
                    'X' => 'Exit',
                ],
                default: 'A'
            );

            if ($sel != 'x') {
                $this->selection($sel);
            }
        }

        $this->line('');

        // Etapa 3: Outras configurações iniciais...
        // Adicione aqui outras etapas iniciais, se necessário.

        $this->info('Setup completed successfully!');
    }

    private function selection($sel)
    {
        if ($sel == 'A') {
            $this->adminName = $this->ask('Enter New Admin Name:');

            if ($this->adminName) {
                $this->adminInfo->name = $this->adminName;

                if ($this->adminInfo->save()) {
                    $this->info('Name has changed with success.');
                    $this->line('');

                    return true;
                }
            }
        }

        if ($sel == 'E') {
            $this->adminEmail = $this->ask('Enter New Admin Email:');

            if ($this->adminName) {
                $this->adminInfo->email = $this->adminEmail;

                if ($this->adminInfo->save()) {
                    $this->info('Email has changed with success.');
                    $this->line('');

                    return true;
                }
            }
        }

        if ($sel == 'P') {
            $this->adminPassword   = $this->secret('Enter New Admin Password:');
            $this->adminRePassword = $this->secret('Re-Enter admin password:');

            if ($this->adminPassword === $this->adminPassword) {
                $this->adminInfo->password = Hash::make($this->adminPassword);

                if ($this->adminInfo->save()) {
                    $this->info('Password has changed with success.');
                    $this->line('');

                    return true;
                }
            } else {
                $this->line('');
                $this->comment("Passwords doesn't match!");

                return true;
            }
        }

    }
}
