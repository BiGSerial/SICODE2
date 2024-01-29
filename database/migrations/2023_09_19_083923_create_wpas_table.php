<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wpas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('production_id')->nullable();
            $table->bigInteger('note_id')->nullable();
            $table->string('dd')->nullable();
            $table->string('sector')->nullable();
            $table->string('workcenter')->nullable();
            $table->string('stats')->nullable();
            $table->string('execstats')->nullable();
            $table->string('statuscomp')->nullable();
            $table->string('ststusexec')->nullable();
            $table->string('lat')->nullable();
            $table->string('long')->nullable();
            $table->timestamp('desired_at')->nullable();
            $table->timestamp('issue_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wpas');
    }
};
