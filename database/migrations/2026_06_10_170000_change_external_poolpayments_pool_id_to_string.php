<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('external_poolpayments', function (Blueprint $table) {
            $table->string('pool_id', 30)
                ->nullable()
                ->comment('ID numérico legado ou código alfanumérico da solicitação')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('external_poolpayments', function (Blueprint $table) {
            $table->unsignedBigInteger('pool_id')
                ->nullable()
                ->comment('ID da Solicitação / PoolId')
                ->change();
        });
    }
};
