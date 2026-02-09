<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adsforms', function (Blueprint $table) {
            $table->boolean('tacit')->default(false)->after('partial');
            $table->timestamp('tacit_due_at')->nullable()->after('tacit');
            $table->timestamp('tacit_delivered_at')->nullable()->after('tacit_due_at');
        });

        Schema::table('work_reports', function (Blueprint $table) {
            $table->boolean('acceptance_accepted')->default(false)->after('informed_at');
            $table->timestamp('acceptance_at')->nullable()->after('acceptance_accepted');
            $table->string('acceptance_name')->nullable()->after('acceptance_at');
            $table->json('acceptance_meta')->nullable()->after('acceptance_name');
        });
    }

    public function down(): void
    {
        Schema::table('adsforms', function (Blueprint $table) {
            $table->dropColumn(['tacit', 'tacit_due_at', 'tacit_delivered_at']);
        });

        Schema::table('work_reports', function (Blueprint $table) {
            $table->dropColumn(['acceptance_accepted', 'acceptance_at', 'acceptance_name', 'acceptance_meta']);
        });
    }
};
