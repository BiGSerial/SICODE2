<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_case_adverse_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->string('name');
            $table->string('document_type', 10);
            $table->text('document_encrypted');
            $table->char('document_hash', 64);
            $table->char('document_last4', 4);
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->unique(['legal_case_id', 'document_hash'], 'uq_legal_case_adverse_party_document');
            $table->index('document_hash');
            $table->index(['legal_case_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_case_adverse_parties');
    }
};
