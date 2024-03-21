<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\{confirm, info, text};

class SicodeUpVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:up_version 
                            {--regular : Perform basic update} 
                            {--implements : Implement middle digit update} 
                            {--app : Update initial digit for new application}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update version in JSON file  
        {--regular : Perform basic update} 
        {--implements : Implement middle digit update} 
        {--app : Update initial digit for new application}';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $versionFile = base_path('appver.json');
        $versionData = json_decode(File::get($versionFile), true);

        // $description = $this->ask('Enter description:');

        $description = text(
            label: 'Enter a Description:',
            hint: 'Describe principals Modifies this NewVersion.'
        );

        info('>> ' . $description);
        $confirm = confirm(
            label: 'Confirm Informations?:',
            default: false,
            yes: 'I accept',
            no: 'I decline',
            hint: 'Describe principals Modifies this NewVersion.'
        );

        if (!$confirm) {
            info('Exiting! Thanks For Use! Bye!');

            return;
        }

        $currentVersion = $versionData['appver'];

        if ($this->option('app')) {
            $newVersion = $this->updateVersion($currentVersion, 0);
        } elseif ($this->option('implements')) {
            $newVersion = $this->updateVersion($currentVersion, 1);
        } else {
            $newVersion = $this->updateVersion($currentVersion, 2);
        }

        $newHistoryEntry = [
            'version'     => $newVersion,
            'description' => $description,
            'date'        => Carbon::now()->toDateString(),
        ];

        array_unshift($versionData['historic'], $newHistoryEntry);
        $versionData['appver'] = $newVersion;

        File::put($versionFile, json_encode($versionData, JSON_PRETTY_PRINT));

        $this->info("Version updated successfully to $newVersion");
    }

    private function updateVersion($currentVersion, $position)
    {
        $versionParts = explode('.', $currentVersion);

        if ($position === 1) {
            $versionParts[1] = (int) $versionParts[1] + 1;
            $versionParts[2] = 0;
        } elseif ($position === 0) {
            $versionParts[0] = (int) $versionParts[0] + 1;
            $versionParts[1] = 0;
            $versionParts[2] = 0;
        } else {
            $versionParts[2] = (int) $versionParts[2] + 1;
        }

        return implode('.', $versionParts);
    }
}
