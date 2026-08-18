<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private const CONSTRUCTION_CODES = [
        'AL36',
        'CA03',
        'CC05',
        'CP08',
        'DE01',
        'DE12',
        'DE14',
        'EG02',
        'EG03',
        'EL06',
        'IR01',
        'MR09',
        'NB01',
        'NB12',
        'OU03',
        'OU08',
        'OU15',
        'OU16',
        'OU44',
        'OU47',
        'OU53',
        'OU80',
        'PL01',
        'RA01',
        'SA02',
        'SA07',
        'SA10',
        'SU01',
        'SU12',
        'SU14',
        'SU16',
        'SU27',
        'SU29',
    ];

    public function up(): void
    {
        Schema::create('protest_measure_code_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('classification', 30)->default('cip');
            $table->string('label')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['classification', 'active'], 'pmcc_classification_active_idx');
        });

        $now = now();
        DB::table('protest_measure_code_classifications')->insert(
            collect(self::CONSTRUCTION_CODES)->map(fn (string $code) => [
                'code' => $code,
                'classification' => 'construction',
                'label' => 'Construção',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('protest_measure_code_classifications');
    }
};
