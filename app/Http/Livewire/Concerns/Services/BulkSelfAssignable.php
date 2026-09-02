<?php

namespace App\Http\Livewire\Concerns\Services;

use App\Models\Note;

/**
 * Padrão de "auto atribuição" para telas de Serviços (Services/*\/Main.php) — extraído do que
 * já funcionava em produção em Services/Analises/Main.php (go_att_mass/add_to_accompany_mass/
 * checkAllSelect/setSelectAllFiltered), agora reutilizável por qualquer atividade do mesmo tipo.
 *
 * Ao contrário do Despacho (que abre um modal para escolher empresa/usuário), nas telas de
 * Serviço o colaborador atribui a atividade para si mesmo — só um sweetalert2 de confirmação,
 * sem modal. Este trait cobre tanto a atribuição individual (`to_accompany`/`add_to_accompany`,
 * já existente em cada Main.php) quanto em massa (seleção de várias linhas, inclusive entre
 * páginas — a seleção "todos" opera sobre o resultado filtrado inteiro, não só a página visível).
 *
 * O componente que usa este trait precisa implementar:
 * - baseQuery() — já é convenção existente nesses Main.php (usada pela paginação).
 * - assignNoteToSelf(Note $note): array — executa a atribuição de fato, retornando
 *   ['ok' => bool, 'note' => string, 'reason' => ?string].
 * - adicionar 'confirm_accompany_mass' => 'add_to_accompany_mass' em $listeners.
 */
trait BulkSelfAssignable
{
    public array $selected = [];

    public bool $selectAll = false;

    /**
     * Seleciona/deseleciona TODOS os itens do resultado filtrado atual (não só a página visível).
     */
    public function setSelectAllFiltered(): void
    {
        $ids = $this->baseQuery()->pluck('id')->toArray();

        if ($this->selectAll) {
            foreach ($ids as $id) {
                if (!in_array($id, $this->selected)) {
                    $this->selected[] = $id;
                }
            }
        } else {
            $this->selected = array_values(array_diff($this->selected, $ids));
        }
    }

    /**
     * Usado inline no Blade (@checked) para refletir se todos os itens filtrados já estão selecionados.
     */
    public function checkAllSelect(): bool
    {
        $ids = $this->baseQuery()->pluck('id')->toArray();

        if (!count($ids)) {
            $this->selectAll = false;

            return false;
        }

        $this->selectAll = empty(array_diff($ids, $this->selected));

        return $this->selectAll;
    }

    /**
     * Abre o sweetalert2 de confirmação para atribuir em massa as Notas/OVs selecionadas.
     */
    public function go_att_mass(): void
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota selecionada',
                'timer'    => 2500,
            ]);

            return;
        }

        $count = count($this->selected);

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Atribuir em massa',
            'msg'   => "Você deseja atribuir <strong>{$count}</strong> Nota(s)/OV(s) para você?</br></br>
                <div class='card card-light'>
                  <div class='card-body'>
                    <p>As Notas/OVs elegíveis estarão disponíveis em acompanhamento como sua tarefa e nenhum outro usuário poderá atribuí-las para si.</p>
                  </div>
                </div>",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Atribua!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_accompany_mass',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum serviço foi atribuído.',
        ]);
    }

    /**
     * Executa a atribuição em massa após a confirmação do sweetalert2, e mostra um resumo único.
     */
    public function add_to_accompany_mass(): void
    {
        $notes = Note::whereIn('id', $this->selected)->get();

        $success = 0;
        $errors  = [];

        foreach ($notes as $note) {
            $result = $this->assignNoteToSelf($note);

            if ($result['ok']) {
                $success++;
            } else {
                $errors[] = "{$result['note']}: {$result['reason']}";
            }
        }

        $this->selected  = [];
        $this->selectAll = false;

        if (count($errors)) {
            $msg = implode('<br>', array_slice($errors, 0, 10));

            if (count($errors) > 10) {
                $msg .= '<br>...';
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => $success ? 'warning' : 'error',
                'title'    => "Atribuição concluída ({$success} sucesso, " . count($errors) . ' falha)',
                'html'     => $msg,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => "Atribuição em massa concluída ({$success})",
            'timer'    => 2500,
        ]);
    }
}
