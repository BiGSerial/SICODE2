# Padronização Consulta D5 x Relatório Notas D5

## Objetivo da reunião
Alinhar por que `reports/consulta_d5` e `reports/five-notes-report` retornavam conjuntos diferentes de registros e definir a padronização para garantir a mesma base, filtros e resultado.

## Cenário anterior (antes da padronização)

### Rota `reports/consulta_d5`
- View: `resources/views/reports/consulta_d5.blade.php`
- Livewire: `engineers.waiting-five-notes`
- Classe: `app/Http/Livewire/Engineers/WaitingFiveNotes.php`

Base e regras:
- Consulta em `five_notes`, porém com regra de elegibilidade operacional:
  - `visible_partner = true`
  - ou `note_d5` vazio + evento em `timeline_events` (`d5_created_from_supervision`)
- Filtros operacionais por etapa (`fornecedor`, `fiscalização`, `pagamento`, `finalizado`)
- Filtros avançados por componente (`company`, `type`, `city`, `rubrica`, `desired_between`)
- Busca textual e busca múltipla (D5/nota)

### Rota `reports/five-notes-report`
- View: `resources/views/reports/five-notes-report.blade.php`
- Livewire: `reports.five-note-report`
- Classe: `app/Http/Livewire/Reports/FiveNoteReport.php`
- Service: `app/Services/Reports/FiveNoteReportService.php`

Base e regras:
- Consulta em `five_notes` sem a regra de elegibilidade operacional da tela de engenharia
- Filtros analíticos:
  - período de despacho (`dispatch_from`, `dispatch_to`)
  - período de conclusão (`completed_from`, `completed_to`)
  - empresa parceira (`company_id`)
  - busca textual ampla

## Causa da divergência
A divergência não era de banco/conexão, mas de **regra de seleção e filtros**:
- A `consulta_d5` aplicava regras de fila/etapa de operação.
- O `five-note-report` aplicava filtros analíticos de período/empresa/busca.

Resultado: conjuntos diferentes para a mesma percepção de "consulta D5".

## Decisão de padronização
Manter a `consulta_d5` no componente operacional original, preservando o estilo e as informações da tela:
- Componente da consulta: `engineers.waiting-five-notes`
- Status e responsável no front e no export
- Mesmas regras operacionais na tela e no export
- Filtro de período com escolha da coluna: despacho, conclusão ou ambos

## Alteração aplicada
- Arquivo alterado: `resources/views/reports/consulta_d5.blade.php`
- Alteração: `@livewire('engineers.waiting-five-notes')` mantido/restaurado para a rota `consulta_d5`.
- Filtro adicional na consulta: `period_column` (`dispatch`, `completed`, `both`).
- Regra de período replicada no export (`ExportWaitingFiveNotesJob`) para evitar divergência tela x arquivo.

## Impacto esperado
- A consulta mantém o comportamento e visual operacionais esperados.
- Exportação passa a respeitar o mesmo recorte temporal configurado na tela.
- Reduz divergência entre registros vistos no front e registros exportados.

## Checklist de validação pós-deploy
1. Acessar `reports/consulta_d5` e `reports/five-notes-report`.
2. Aplicar o mesmo intervalo de despacho.
3. Aplicar o mesmo intervalo de conclusão.
4. Aplicar a mesma empresa.
5. Aplicar a mesma busca textual.
6. Confirmar:
   - total de registros igual;
   - mesmas linhas retornadas;
   - paginação consistente.

## Observação
O relatório analítico (`reports.five-note-report`) continua separado para análises gerenciais.  
Já a rota `consulta_d5` permanece como consulta operacional, com regra de filtros e export alinhados entre si.
