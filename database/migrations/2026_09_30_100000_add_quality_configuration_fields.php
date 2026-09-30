<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('quality_rejection_categories')) {
            Schema::table('quality_rejection_categories', function (Blueprint $table) {
                if (!Schema::hasColumn('quality_rejection_categories', 'description')) {
                    $table->text('description')->nullable();
                }

                if (!Schema::hasColumn('quality_rejection_categories', 'applies_project')) {
                    $table->boolean('applies_project')->default(true);
                }

                if (!Schema::hasColumn('quality_rejection_categories', 'applies_budget')) {
                    $table->boolean('applies_budget')->default(true);
                }

                if (!Schema::hasColumn('quality_rejection_categories', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(0);
                }
            });
        }

        if (!Schema::hasTable('quality_settings')) {
            Schema::create('quality_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->json('value')->nullable();
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_settings');

        if (Schema::hasTable('quality_rejection_categories')) {
            Schema::table('quality_rejection_categories', function (Blueprint $table) {
                foreach (['description', 'applies_project', 'applies_budget', 'sort_order'] as $column) {
                    if (Schema::hasColumn('quality_rejection_categories', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
