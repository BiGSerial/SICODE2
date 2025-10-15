<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalPoolpayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'user_id',

        // Identificadores
        'pool_id',

        // Metadados principais
        'solicitacao_pagamento',
        'criacao_pedido',
        'material_servico',
        'status_pedido',

        // Contrato / fornecedor / parceiro
        'local_prestacao_servico',
        'numero_contrato',
        'codigo_fornecedor',
        'fornecedor',
        'tipo_documento_fornecedor',
        'codigo_parceiro_sap',

        // Pessoas/Organização
        'gestor',
        'empresa_edp',
        'cnpj_empresa_grupo_edp',
        'centro_logistico',
        'responsavel_pool',

        // NF e dados contábeis
        'data_recebimento_nf',
        'mes',
        'ano',
        'numero_nf',
        'classe_contabil',
        'rateio',
        'centro_ordem_diagrama',
        'operacao_diagrama',
        'data_emissao_nf',

        // Pagamento
        'forma_pagamento',
        'baixa_adiantamento',
        'data_vencimento',
        'moeda',
        'valor',
        'observacoes',
        'solicitante',

        // Marcos de processo
        'fi_fbv0',
        'pedido_me28',
        'medicao_ml85',
        'miro_numero',

        // Datas de workflow / aprovações
        'data_envio_pedido_aprovacao',
        'data_aprovacao_pedido',
        'data_envio_medicao_aprovacao',
        'data_aprovacao_medicao',
        'data_envio_financeiro',

        // Flags / links
        'aprovado',
        'link_solicitacao',
    ];

    protected $casts = [
        'external_id' => 'integer',
        'pool_id'     => 'integer',

        // Datas/hora (timestamp no schema)
        'criacao_pedido'               => 'datetime',
        'data_envio_pedido_aprovacao'  => 'datetime',
        'data_aprovacao_pedido'        => 'datetime',
        'data_envio_medicao_aprovacao' => 'datetime',
        'data_aprovacao_medicao'       => 'datetime',
        'data_envio_financeiro'        => 'datetime',

        // Datas (date no schema)
        'data_recebimento_nf' => 'date',
        'data_emissao_nf'     => 'date',
        'data_vencimento'     => 'date',

        // Numéricos
        'mes'   => 'integer',
        'ano'   => 'integer',
        'valor' => 'decimal:2',

        // Booleanos
        'baixa_adiantamento' => 'boolean',
        'aprovado'           => 'boolean',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!empty($model->pool_id) && empty($model->link_solicitacao)) {
                $model->link_solicitacao = 'https://portaldeservicos.edpbr.com.br/PoolLancamento/PoolDetail?PoolId=' . $model->pool_id;
            }
        });
    }

    public function external()
    {
        return $this->belongsTo(External::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
