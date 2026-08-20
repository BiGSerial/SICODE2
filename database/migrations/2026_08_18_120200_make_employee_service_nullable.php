<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE employees ALTER COLUMN service_id DROP NOT NULL'),
            'sqlsrv' => DB::statement('ALTER TABLE employees ALTER COLUMN service_id CHAR(36) NULL'),
            'sqlite' => null,
            default => DB::statement('ALTER TABLE employees MODIFY service_id CHAR(36) NULL'),
        };
    }

    public function down(): void
    {
        $this->restoreNullableEmployeesForRequiredService();

        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE employees ALTER COLUMN service_id SET NOT NULL'),
            'sqlsrv' => DB::statement('ALTER TABLE employees ALTER COLUMN service_id CHAR(36) NOT NULL'),
            'sqlite' => null,
            default => DB::statement('ALTER TABLE employees MODIFY service_id CHAR(36) NOT NULL'),
        };
    }

    private function restoreNullableEmployeesForRequiredService(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $employees = DB::table('employees')
            ->whereNull('service_id')
            ->select('id', 'contract_id')
            ->orderBy('id')
            ->get();

        foreach ($employees as $employee) {
            $serviceUuid = DB::table('service_contract_rules')
                ->join('services', 'services.id', '=', 'service_contract_rules.service_id')
                ->where('service_contract_rules.contract_id', $employee->contract_id)
                ->orderBy('services.service')
                ->value('services.uuid');

            if (!$serviceUuid) {
                $serviceId = DB::table('services')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'service' => 'Atividade legado rollback',
                    'status' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $serviceUuid = DB::table('services')->where('id', $serviceId)->value('uuid');

                DB::table('service_contract_rules')->insert([
                    'service_id' => $serviceId,
                    'contract_id' => $employee->contract_id,
                    'posts' => false,
                    'qtd' => 0,
                    'days' => 0,
                    'dispatch' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('employees')
                ->where('id', $employee->id)
                ->update(['service_id' => $serviceUuid]);
        }
    }
};
