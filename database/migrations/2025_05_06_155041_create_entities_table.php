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
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_type_id')->nullable()->constrained('entity_types')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('nick')->nullable();
            $table->boolean('approve')->default(false);
            $table->boolean('eon')->default(false);
            $table->boolean('cad')->default(false);
            $table->boolean('map')->default(false);
            $table->json('docs')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entities', function (Blueprint $table) {
            $table->dropForeign(['entity_type_id']);
        });
        Schema::dropIfExists('entities');
    }
};
