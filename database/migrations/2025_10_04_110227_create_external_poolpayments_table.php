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

        Schema::create('external_poolpayments', function (Blueprint $table) {
            $table->id();

            // Relacionamento com a entidade/protocolo externo
            $table->foreignId('external_id')
                ->constrained('externals')
                ->onDelete('cascade');

            $table->foreignId('note_id')
                ->nullable()
                ->constrained('notes') 
                ->nullOnDelete();

            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuário que criou o registro');

            // Identificadores primários do Pool / Pedido
            $table->unsignedBigInteger('pool_id')->nullable()->comment('ID da Solicitação / PoolId');


            // Metadados principais
            $table->string('solicitacao_pagamento', 20)->nullable()->comment('Ex.: FI, MM, etc.');
            $table->timestamp('criacao_pedido')->nullable()->comment('Criação de Pedido (com hora)');
            $table->string('material_servico', 255)->nullable()->comment('Material ou Serviço');
            $table->string('status_pedido', 80)->nullable()->comment('Status do pedido');

            // Contrato / fornecedor / parceiro
            $table->string('local_prestacao_servico', 255)->nullable();
            $table->string('numero_contrato', 40)->nullable();
            $table->string('codigo_fornecedor', 20)->nullable();
            $table->string('fornecedor', 255)->nullable();
            $table->string('tipo_documento_fornecedor', 50)->nullable();
            $table->string('codigo_parceiro_sap', 50)->nullable();

            // Pessoas/Organização
            $table->string('gestor', 120)->nullable();
            $table->string('empresa_edp', 150)->nullable();
            $table->string('cnpj_empresa_grupo_edp', 25)->nullable();
            $table->string('centro_logistico', 80)->nullable();
            $table->string('responsavel_pool', 150)->nullable();

            // NF e dados contábeis
            $table->date('data_recebimento_nf')->nullable();
            $table->unsignedTinyInteger('mes')->nullable();
            $table->unsignedSmallInteger('ano')->nullable();
            $table->string('numero_nf', 50)->nullable();
            $table->string('classe_contabil', 50)->nullable();
            $table->string('rateio', 120)->nullable();
            $table->string('centro_ordem_diagrama', 120)->nullable();
            $table->string('operacao_diagrama', 80)->nullable();
            $table->date('data_emissao_nf')->nullable();

            // Pagamento
            $table->string('forma_pagamento', 80)->nullable();
            $table->boolean('baixa_adiantamento')->nullable();
            $table->date('data_vencimento')->nullable();
            $table->string('moeda', 10)->nullable()->comment('Ex.: BRL');
            $table->decimal('valor', 15, 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->string('solicitante', 150)->nullable();

            // Marcos de processo (transações SAP)
            $table->string('fi_fbv0', 50)->nullable();
            $table->string('pedido_me28', 50)->nullable();
            $table->string('medicao_ml85', 50)->nullable();
            $table->string('miro_numero', 50)->nullable();

            // Datas de workflow / aprovações
            $table->timestamp('data_envio_pedido_aprovacao')->nullable();
            $table->timestamp('data_aprovacao_pedido')->nullable();
            $table->timestamp('data_envio_medicao_aprovacao')->nullable();
            $table->timestamp('data_aprovacao_medicao')->nullable();
            $table->timestamp('data_envio_financeiro')->nullable();

            // Flags / links
            $table->boolean('aprovado')->nullable();
            $table->string('link_solicitacao', 2048)->nullable();



            // Auditoria
            $table->timestamps();

            // Índices
            $table->unique('pool_id'); // se houver chance de duplicidade por entidade, troque por unique(['external_id','pool_id'])
            $table->index('external_id');
            $table->index(['status_pedido', 'aprovado']);
            $table->index('codigo_fornecedor');
            $table->index('numero_contrato');
            $table->index('data_vencimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_poolpayments');
    }
};
