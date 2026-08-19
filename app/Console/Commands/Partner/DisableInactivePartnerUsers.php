<?php

namespace App\Console\Commands\Partner;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;

class DisableInactivePartnerUsers extends Command
{
    protected $signature = 'partner-users:disable-inactive {--dry-run : Apenas mostra quantos usuários seriam desativados}';

    protected $description = 'Soft delete partner users according to company inactivity policy.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        Company::query()
            ->whereNotNull('partner_user_inactivity_days')
            ->where('partner_user_inactivity_days', '>', 0)
            ->orderBy('name')
            ->each(function (Company $company) use ($dryRun, &$total) {
                $cutoff = now()->subDays((int) $company->partner_user_inactivity_days);
                $companyIds = Company::query()
                    ->where('id', $company->id)
                    ->orWhere('parent_id', $company->id)
                    ->pluck('id');

                $query = User::query()
                    ->where('onlyparner', true)
                    ->whereIn('company_id', $companyIds)
                    ->whereNull('deleted_at')
                    ->where(function ($query) use ($cutoff) {
                        $query->where('last_seen_at', '<=', $cutoff)
                            ->orWhere(function ($query) use ($cutoff) {
                                $query->whereNull('last_seen_at')
                                    ->where('last_login_at', '<=', $cutoff);
                            })
                            ->orWhere(function ($query) use ($cutoff) {
                                $query->whereNull('last_seen_at')
                                    ->whereNull('last_login_at')
                                    ->where('created_at', '<=', $cutoff);
                            });
                    });

                $count = (clone $query)->count();
                $total += $count;

                if ($dryRun || $count === 0) {
                    $this->line("{$company->name}: {$count} usuário(s) elegível(is).");

                    return;
                }

                $query->chunkById(100, function ($users) {
                    $users->each->delete();
                });

                $this->info("{$company->name}: {$count} usuário(s) desativado(s).");
            });

        $this->info("Total: {$total} usuário(s).");

        return Command::SUCCESS;
    }
}
