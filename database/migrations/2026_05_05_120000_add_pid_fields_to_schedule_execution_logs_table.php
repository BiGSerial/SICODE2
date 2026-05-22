<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_execution_logs', function (Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable()->after('exit_code')->index();
            $table->text('process_command')->nullable()->after('process_id');
            $table->dateTime('stopped_at')->nullable()->after('process_command');
            $table->string('stop_signal', 20)->nullable()->after('stopped_at');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_execution_logs', function (Blueprint $table) {
            $table->dropColumn([
                'process_id',
                'process_command',
                'stopped_at',
                'stop_signal',
            ]);
        });
    }
};
