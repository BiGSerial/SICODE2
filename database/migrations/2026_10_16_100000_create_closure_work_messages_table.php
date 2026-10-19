<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('closure_work_messages')) {
            return;
        }

        Schema::create('closure_work_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->restrictOnDelete();
            $table->foreignId('closure_issue_id')->nullable()->constrained('closure_issues')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('author_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('author_role', 20);
            $table->text('body');
            $table->string('visibility', 20)->default('INTERNAL_ONLY');
            $table->timestamps();

            $table->index(['note_id', 'created_at'], 'closure_work_message_note_created_index');
            $table->index(['closure_issue_id', 'created_at'], 'closure_work_message_issue_created_index');
            $table->index(['note_id', 'visibility'], 'closure_work_message_note_visibility_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closure_work_messages');
    }
};
