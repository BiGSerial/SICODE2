<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class () extends Migration {
    private const SERVICE = 'Encerramento';

    private const FOLDER = 'encerramento';

    /**
     * Provisiona o serviço técnico sem vínculos de contrato ou usuário.
     *
     * A busca aceita tanto o folder técnico quanto o nome exibido para ser
     * segura em bancos que já receberam esse provisionamento manualmente.
     */
    public function up(): void
    {
        $service = DB::table('services')
            ->where('folder', self::FOLDER)
            ->first()
            ?? DB::table('services')->where('service', self::SERVICE)->first();

        $attributes = [
            'service'      => self::SERVICE,
            'folder'       => self::FOLDER,
            'status'       => null,
            'project'      => true,
            'construction' => true,
            'icon'         => 'ri-checkbox-circle-line',
            'canReturn'    => false,
            'updated_at'   => now(),
        ];

        if ($service) {
            if (!$service->uuid) {
                $attributes['uuid'] = (string) Str::uuid();
            }

            DB::table('services')->where('id', $service->id)->update($attributes);

            return;
        }

        DB::table('services')->insert($attributes + [
            'uuid'       => (string) Str::uuid(),
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Cadastro de referência: não apagar um serviço que possa ter recebido vínculos depois do provisionamento.
    }
};
