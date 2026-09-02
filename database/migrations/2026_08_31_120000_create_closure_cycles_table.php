<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('closure_cycles', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year');
            $table->tinyInteger('month');
            $table->string('label');
            $table->string('status')->default('OPEN');
            $table->timestamp('frozen_at')->nullable();
            $table->foreignUuid('frozen_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['year', 'month']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closure_cycles');
    }
};
