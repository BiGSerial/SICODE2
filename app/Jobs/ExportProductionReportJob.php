<?php

namespace App\Jobs;

use App\Custom\Notestatus;
use App\Custom\RuleBuilder;
use App\Exports\Reports\ProductionFullExport;
use App\Models\Note;
use App\Models\Notify;
use App\Models\Production;
use App\Models\Service;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ExportProductionReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $services;
    protected $monthYear;
    protected $dt_init;
    protected $dt_end;
    public $user;

    public int $timeout = 1800; // 30 min para evitar estouro em selecoes amplas
    public $tries = 2;
    public $backoff = [30, 120];

    public function __construct($services, $monthYear, $dt_init, $dt_end, $user)
    {
        $this->onQueue('exports');
        $this->services = $services;
        $this->monthYear = $monthYear;
        $this->dt_init = $dt_init;
        $this->dt_end = $dt_end;
        $this->user = $user;
    }

    public function handle()
    {
        $filePath = null;
        $disk = Storage::disk('local');
        $reportData = [];
        $startDate = $this->monthYear ? Carbon::parse($this->monthYear)->startOfMonth()->format('Y-m-d 0:00:00') : null;
        $endDate = $this->monthYear ? Carbon::parse($this->monthYear)->endOfMonth()->format('Y-m-d 23:59:59') : null;
        $dtInit = $this->dt_init ? date('Y-m-d 0:00:00', strtotime($this->dt_init)) : null;
        $dtEnd = $this->dt_end ? date('Y-m-d 23:59:59', strtotime($this->dt_end)) : null;

        // dd($this->services, $startDate, $endDate, $dtInit, $dtEnd);


        try {
            foreach ($this->services as $service) {
                $toStatus = Service::where('uuid', $service)->first();
                if (!$toStatus || $toStatus->Status->isEmpty()) {
                    continue;
                }

                $noteNoWorks = Note::query();
                RuleBuilder::applyRules($noteNoWorks, $toStatus->Status);

                if ($startDate && $endDate) {
                    $noteNoWorks->whereBetween('dt_status', [$startDate, $endDate]);
                }

                if ($dtInit) {
                    $noteNoWorks->where('dt_status', '>=', $dtInit);
                }

                if ($dtEnd) {
                    $noteNoWorks->where('dt_status', '<=', $dtEnd);
                }

                $notes = $noteNoWorks->get();

                foreach ($notes as $note) {
                    $production = Production::where('note_id', $note->id)
                        ->where('service_id', $toStatus->uuid)
                        ->where('dt_note', $note->dt_status)
                        ->where('D5', false)
                        ->first();

                    $reportData[] = $this->formatReportData($note, $toStatus, $production);
                }

                $inProduction = Production::query()
                    ->where('service_id', $toStatus->uuid)
                    ->where('d5', false);

                if ($startDate && $endDate) {
                    $inProduction->whereBetween('dispatch_at', [$startDate, $endDate]);
                }

                if ($dtInit) {
                    $inProduction->where('dispatch_at', '>=', $dtInit);
                }

                if ($dtEnd) {
                    $inProduction->where('dispatch_at', '<=', $dtEnd);
                }

                $productions = $inProduction->get();
                foreach ($productions as $production) {
                    $reportData[] = $this->formatReportData($production->Note, $toStatus, $production);
                }
            }


            $fileName = 'exports/' . date('YmdHis') . '_producao.xlsx';
            $filePath = $fileName;

            $disk->makeDirectory('exports');
            $stored = Excel::store(new ProductionFullExport($reportData, 'SICODE'), $fileName, 'local');

            if (!$stored || !$disk->exists($fileName)) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            // Criar uma notificação para o usuário
            Notify::create([
                'user_id' => $this->user->id,  // Ou passe o ID do usuário para o Job
                'title' => 'Relatório de Produção Concluído',
                'info' => 'Seu relatório está pronto para download.',
                'link' => Storage::url($fileName),
                'status' => 4,
                'readed' => false,
            ]);

        } catch (\Throwable $th) {
            Log::error('ExportProductionReportJob falhou', [
                'user_id' => $this->user?->id,
                'services' => $this->services,
                'monthYear' => $this->monthYear,
                'dt_init' => $this->dt_init,
                'dt_end' => $this->dt_end,
                'attempt' => $this->attempts(),
                'error' => $th->getMessage(),
            ]);

            if ($filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $th;
        }
    }

    private function formatReportData($note, $toStatus, $production)
    {
        return [
            'note' => $note->note,
            'Rubrica' => $note->rubrica,
            'Municipio' => $note->lexp,
            'Serviço' => mb_strtoupper($toStatus->service),
            'Empreiteira' => $production && $production->Company ? mb_strtoupper($production->Company->name) : '',
            'DataStatus' => $production ? ($production->dt_note ? Carbon::parse($production->dt_note)->format('Y-m-d') : '') : ($note->dt_status ? Carbon::parse($note->dt_status)->format('Y-m-d') : ''),
            'HoraStatus' => $production ? ($production->dt_note ? Carbon::parse($production->dt_note)->format('H:i:s') : '') : ($note->dt_status ? Carbon::parse($note->dt_status)->format('H:i:s') : ''),
            'Despachado_Em_Data' => $production ? Carbon::parse($production->dispatch_at)->format('Y-m-d') : '',
            'Despachado_Em_Hora' => $production ? Carbon::parse($production->dispatch_at)->format('H:i:s') : '',
            'Finalizado_Em_Data' => $production ? Carbon::parse($production->completed_at)->format('Y-m-d') : '',
            'Finalizado_Em_Hora' => $production ? Carbon::parse($production->completed_at)->format('H:i:s') : '',
            'Tempo_reacao' => $production && $production->dispatch_at && $production->dt_note ? Carbon::Parse($production->dt_note)->diff(Carbon::parse($production->dispatch_at))->format('%d dias, %h horas e %i minutos') : '',
            'Tempo_Execucao' => $production && $production->dispatch_at && $production->completed_at ? Carbon::Parse($production->dispatch_at)->diff(Carbon::parse($production->completed_at))->format('%d dias, %h horas e %i minutos') : '',
            'Status' => $production ? Notestatus::status($production->status)->status : 'Na Pilha',
            'Fiscalizacao_por_fotos_parceira' => is_null($production?->supervision_by_partner_photos) ? '' : ($production->supervision_by_partner_photos ? 'SIM' : 'NÃO'),
        ];
    }

    public function failed(Exception $exception)
    {
        Notify::create([
            'user_id' => $this->user->id,
            'title' => 'Erro ao Gerar Relatório',
            'info' => 'Ocorreu um erro ao gerar o relatório.<br>'. $exception->getMessage(),
            'link' => '', // Sem link, pois o arquivo não foi gerado
            'status' => 5,
            'readed' => false,
        ]);
    }
}
