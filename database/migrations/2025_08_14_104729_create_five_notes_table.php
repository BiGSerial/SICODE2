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
        Schema::create('five_notes', function (Blueprint $table) {
            $table->id();
            $table->string('note_d5')->nullable();
            $table->foreignId('note_id')->constrained('notes')->onDelete('cascade');
            $table->string('loc_install')->nullable();
            $table->string('conjunto')->nullable();
            $table->string('pep')->nullable();
            $table->string('e_pep')->nullable();
            $table->string('codify')->nullable();
            $table->string('sintoms')->nullable();

            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->string('name')->nullable();
            $table->timestamp('dispatch_at')->nullable();

            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_supervisioned')->default(false);
            $table->timestamp('supervisioned_at')->nullable();
            $table->boolean('is_payed')->default(false);
            $table->timestamp('payed_at')->nullable();
            $table->boolean('is_archived')->default(false);

            $table->boolean('visible_partner')->default(false);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('five_notes');
    }
};
