<?php

namespace App\Console\Commands\Setup;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Hash, Schema};

use function Laravel\Prompts\{password, text};

class init extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Init App on First Time';

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

        $this->info('Starting setup process...');
        $this->line('');

        // Etapa 1: Criação do banco de dados (substitua pelos detalhes do seu banco de dados)
        // Etapa 1: Verificar se as migrações já foram aplicadas
        if (!Schema::hasTable('users')) {
            $this->comment('Step 1: Applying migrations...');
            $this->call('migrate');
            $this->line('');
        } else {

            $this->comment('Step 1: Migrations have already been applied.');
        }
        $this->line('');
        // Verificar se o setup já foi concluído
        // if (isset(User::first()->name)) {
        //     $this->info('Setup has already been completed.');
        //     return;
        // }
        // Etapa 2: Criação do usuário administrador
        $this->comment('Step 2: Creating the admin user...');
        $adminEmail = text(
            label: 'Enter admin email:',
            required: 'A Email Has Required!',
        );
        $adminName = text(
            label: 'Enter admin name:',
            default: 'Sicode Sys',
            required: 'A Email Has Required!',
        );

        $adminPassword = password(
            label: 'Enter admin password:',
            required: 'The password is required.'
        );
        $adminRePassword = password('Re-Enter admin password:');

        $error = 0;
        while ($adminPassword !== $adminRePassword) {

            if ($os === 'WINNT') {
                // Windows
                system('cls');

            } else {
                // macOS/Linux
                system('clear');
            }

            $this->info($art);
            $this->line('');

            $error++;
            $this->line('');
            $this->comment("Passwords doesn't match, try again...({$error}/3)");
            $adminPassword = password(
                label: 'Enter admin password:',
                required: 'The password is required.'
            );
            $adminRePassword = password('Re-Enter admin password:');

            if ($error >= 3) {
                $this->info('Exceded Password Attempt, run app:init again.');

                return;
            }
        }

        $this->createAdminUser($adminEmail, $adminName, $adminPassword);
        $this->line('');

        // Etapa 3: Outras configurações iniciais...
        // Adicione aqui outras etapas iniciais, se necessário.

        $this->info('Setup completed successfully!');
    }

    private function createAdminUser($email, $name, $password)
    {
        if (!(User::where('superadm', true)->first())) {
            return User::create([
                'email'    => $email,
                'name'     => $name,
                'password' => Hash::make($password),
                'superadm' => true,
            ]);
        } else {
            $this->comment('An Administrator already exists, run: app:admin_pass to change the password.');

            return true;
        }
    }
}
