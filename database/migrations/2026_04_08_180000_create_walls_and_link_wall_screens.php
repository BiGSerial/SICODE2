<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('walls', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('wall_screens', function (Blueprint $table) {
            $table->foreignId('wall_id')->nullable()->after('id')->constrained('walls')->cascadeOnDelete();
            $table->string('screen_type')->default('production_services')->after('name');
            $table->unsignedInteger('service_rotation_seconds')->nullable()->after('duration_seconds');
            $table->json('screen_config')->nullable()->after('service_rotation_seconds');
            $table->index(['wall_id', 'enabled', 'display_order']);
        });

        $defaultWallId = DB::table('walls')->insertGetId([
            'name' => 'Wall 1',
            'enabled' => true,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('wall_screens')
            ->whereNull('wall_id')
            ->update([
                'wall_id' => $defaultWallId,
                'updated_at' => now(),
            ]);

    }

    public function down(): void
    {
        Schema::table('wall_screens', function (Blueprint $table) {
            $table->dropIndex(['wall_id', 'enabled', 'display_order']);
            $table->dropConstrainedForeignId('wall_id');
            $table->dropColumn(['screen_type', 'service_rotation_seconds', 'screen_config']);
        });

        Schema::dropIfExists('walls');
    }
};
