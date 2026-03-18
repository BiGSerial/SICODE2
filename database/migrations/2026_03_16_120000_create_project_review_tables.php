<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('project_review_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['name']);
        });

        Schema::create('project_review_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('project_review_categories')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['category_id', 'name']);
        });

        Schema::create('project_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategory_id')->constrained('project_review_subcategories')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['subcategory_id', 'name']);
        });

        Schema::create('project_review_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            $table->unsignedInteger('round_number')->default(1);

            $table->foreignUuid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();

            $table->boolean('proportionality_ok')->nullable();
            $table->decimal('proportionality_value', 14, 2)->nullable();

            $table->enum('decision', ['PENDING', 'APPROVED', 'APPROVED_WITH_REMARKS', 'REJECTED'])->default('PENDING');
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('analyst_note')->nullable();
            $table->text('designer_note')->nullable();

            $table->timestamps();

            $table->index(['production_id', 'round_number']);
            $table->index(['decision', 'submitted_at']);
            $table->unique(['production_id', 'round_number']);
        });

        Schema::create('project_review_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('project_review_cycles')->cascadeOnDelete();
            $table->string('order_number');
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->decimal('company_cost', 14, 2)->default(0);
            $table->decimal('client_cost', 14, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['cycle_id', 'order_number']);
        });

        Schema::create('project_review_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('project_review_cycles')->cascadeOnDelete();
            $table->foreignId('subcategory_id')->constrained('project_review_subcategories')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('project_review_items')->cascadeOnDelete();
            $table->enum('origin', ['LEVANTAMENTO', 'PROJETO', 'AMBOS']);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['cycle_id', 'subcategory_id', 'item_id'], 'project_review_findings_unique_item');
        });

        Schema::create('project_review_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            $table->foreignId('cycle_id')->nullable()->constrained('project_review_cycles')->nullOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('project_review_messages')->nullOnDelete();
            $table->text('message');
            $table->timestamps();

            $table->index(['production_id', 'created_at']);
            $table->index(['cycle_id', 'created_at']);
        });

        $categoryId = DB::table('project_review_categories')->insertGetId([
            'name' => 'Geral',
            'sort_order' => 1,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subcategoryId = DB::table('project_review_subcategories')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Documentação',
            'sort_order' => 1,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('project_review_items')->insert([
            [
                'subcategory_id' => $subcategoryId,
                'name' => 'Arquivo do projeto ausente/inválido',
                'sort_order' => 1,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'subcategory_id' => $subcategoryId,
                'name' => 'Valor divergente',
                'sort_order' => 2,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('project_review_messages');
        Schema::dropIfExists('project_review_findings');
        Schema::dropIfExists('project_review_orders');
        Schema::dropIfExists('project_review_cycles');
        Schema::dropIfExists('project_review_items');
        Schema::dropIfExists('project_review_subcategories');
        Schema::dropIfExists('project_review_categories');
    }
};
