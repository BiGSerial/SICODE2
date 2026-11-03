<?php

namespace App\Services\Production;

use App\Models\{Analise, Notetimeline, Prodtransfer, Production, Wpa};
use App\Models\SicodeSql\Production as SicodeSqlProduction;

/**
 * Ações de correção sobre uma Production (re-atribuir e remover o despacho), numa classe só para o Controle e para os
 * subcomponentes `production.actions.*` não terem cada um a sua cópia da regra.
 */
class ProductionActions
{
    /** `superadm` pode consultar delegações; lido uma vez por instância (a lista chama isto por linha). */
    private ?bool $isSuperadm = null;

    /** Mostra "Re-atribuir" para atividades já concluídas e não confirmadas (ou para o superadm). */
    public function canReattribute(Production $production): bool
    {
        if (!$production->confirmed && $production->completed) {
            return true;
        }

        return $this->isSuperadm ??= (bool) Auth()->User()?->superadm;
    }

    /** Só dá para remover o despacho de atividade em aberto, ainda Não atribuída ou Atribuída. */
    public function canRemove(Production $production): bool
    {
        return !$production->completed && $production->status <= 2;
    }

    public function reattribute(Production $production): bool
    {
        if (!$production->update(['status' => 2, 'completed' => false])) {
            return false;
        }

        Notetimeline::Create([
            'note_id'       => $production->note_id,
            'service_id'    => $production->service_id,
            'user_id'       => Auth()->User()->id,
            'info'          => 'A nota foi reatribuída.',
            'status'        => 26,
            'production_id' => $production->id,
        ]);

        return true;
    }

    public function remove(Production $production): bool
    {
        if (!$production->delete()) {
            return false;
        }

        // Verifica se existe alguma análise e remove
        if ($analise = Analise::Where('production_id', $production->id)->first()) {
            $analise->delete();
        }

        // Verifica se existe algum Wpa e solta o vínculo
        if ($wpa = Wpa::Where('production_id', $production->id)->first()) {
            $wpa->update(['production_id' => null]);
        }

        if ($transfer = Prodtransfer::Where('production_id', $production->id)->first()) {
            $transfer->delete();
        }

        Notetimeline::Create([
            'note_id'       => $production->note_id,
            'service_id'    => $production->service_id,
            'user_id'       => Auth()->User()->id,
            'info'          => 'Produção REMOVIDA',
            'status'        => 2,
            'production_id' => $production->id,
        ]);

        // Só executa caso o servidor esteja configurado produção.
        if (Env('APP_ENV') === 'production' && !Env('APP_QA')) {
            if ($sqlLog = SicodeSqlProduction::Where('production_id', $production->id)->first()) {
                $sqlLog->delete();
            }
        }

        return true;
    }
}
