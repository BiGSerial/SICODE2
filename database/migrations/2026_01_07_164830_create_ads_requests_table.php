<?php

use App\Enum\AdsRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ads_requests', function (Blueprint $table) {
            $table->id();

            // Quem solicitou no SICODE
            $table->foreignUuid('requested_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            // Nota (registro interno) + número de nota (pra facilitar auditoria/integração)
            $table->foreignId('note_id')
                ->constrained('notes')
                ->cascadeOnDelete();

            // (Opcional, mas útil) o número da nota/OV que o usuário digitou/solicitou
            // Se no seu sistema a nota é sempre a note_id (número), pode remover.


            // Lote para round-robin/fairness
            $table->uuid('batch_id')->index();

            // Flags
            $table->boolean('partner')->default(false);     // sua flag partner
            $table->boolean('completed')->default(false);   // pode derivar do status, mas bom pra BI

            // Status ENUM
            $table->enum('status', AdsRequestStatus::values())
                ->default(AdsRequestStatus::QUEUED->value)
                ->index();

            // Retry/erros
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->text('last_error')->nullable();

            // Cancelamento e versionamento por nota
            $table->unsignedInteger('version')->default(1); // versão crescente por note_id
            $table->timestamp('canceled_at')->nullable()->index();
            $table->foreignId('superseded_by_id')->nullable()
                ->constrained('ads_requests')
                ->nullOnDelete();

            // Execução/entrega
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index(); // quando finalizou (DONE/FAILED/CANCELED)
            $table->timestamp('delivered_at')->nullable()->index(); // quando enviou email/doc, se aplicar

            // Rastreamento de sync com SQL Server (opcional, mas ajuda MUITO)
            $table->unsignedBigInteger('sqlserver_id')->nullable()->unique();

            $table->timestamps();

            // Índices úteis
            $table->index(['note_id', 'status']);
            $table->index(['note_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads_requests');
    }
};
