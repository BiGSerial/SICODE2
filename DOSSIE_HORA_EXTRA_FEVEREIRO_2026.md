# Dossiê de Justificativa de Horas Extras - Janeiro a Março/2026

**Colaborador:** Will Oliveira  
**Gestor(es):** Daniel Fonseca; Marcio Costa Longa  
**Projeto:** SICODE2  
**Período analisado:** 01/01/2026 a 03/03/2026  
**Fonte de evidência:** histórico de commits Git do repositório

## 1. Objetivo

Documentar e justificar a necessidade de horas extras realizadas no período de janeiro a março de 2026, com base nas entregas técnicas efetuadas no projeto SICODE2.

## 2. Resumo Executivo

No período analisado, houve atuação contínua em correções, evoluções e refatorações em módulos críticos (relatórios, ADS, protestos, pagamentos/cancelamentos, despachos e estrutura de menu/acessos), com múltiplas integrações em `develop`.

Indicadores do período:

- **108 commits** registrados no período.
- **26 integrações de branch (merge)** com entregas de feature/fix/refactor/chore.
- **804 arquivos alterados** no total.
- **47.743 inserções** e **9.910 deleções** de linhas.
- **10 commits fora do horário comercial** (após 18:00), evidenciando extensão de jornada para conclusão de entregas.

## 3. Entregas Técnicas (Integrações em `develop`)

1. **06/01/2026** - `fix/add-column-protest-report`  
   Ajustes em estrutura de relatório de protesto.

2. **06/01/2026** - `fix/add-column-entidadeexterna`  
   Correções estruturais envolvendo entidade externa.

3. **12/01/2026** - `fix/dashboard-protest-production`  
   Correções no dashboard de protesto em produção.

4. **15/01/2026** - `fix/protest-report-options`  
   Ajustes de opções/filtros de relatório de protesto.

5. **16/01/2026** - `chore/audit-controller-page`  
   Organização e ajustes de controladores/páginas de auditoria.

6. **19/01/2026** - `feature/upload-files-protests-med`  
   Implementação de upload de arquivos para fluxo de protestos.

7. **22/01/2026** - `feature/ads-request`  
   Evolução do fluxo de solicitações ADS.

8. **23/01/2026** - `feature/buscar-lista-d5`  
   Implementação de busca/listagem D5.

9. **26/01/2026** - `feature/list-d5-payment`  
   Evolução de listagem D5 para pagamentos.

10. **28/01/2026** - `fix/reports-export-file-problem`  
    Correção de problema na exportação de relatórios.

11. **29/01/2026** - `feature/finish-mass-condition`  
    Entrega de ajuste de finalização em condição de processamento em massa.

12. **02/02/2026** - `fix/reports-problem-export`  
    Correções no fluxo de exportação de relatórios.

13. **03/02/2026** - `fix/config-page`  
    Ajustes de configuração/página para estabilização funcional.

14. **04/02/2026** - `feature/cancel-notes-orders`  
    Implementação de funcionalidades para cancelamento de notas/pedidos.

15. **05/02/2026** - `fix/report-arquivo-nao-gerado`  
    Correção de falha crítica de geração de arquivo em relatório.

16. **09/02/2026** - `feature/dispatch-create-ads`  
    Evolução de criação de ADS no fluxo de despacho.

17. **10/02/2026** - `refactor/confirmation-prodution`  
    Refatoração de rotina de confirmação em produção.

18. **20/02/2026** - `fix/ads-tacit-automation`  
    Correções na automação de ADS tácita.

19. **24/02/2026** - `feature/implement-workreport-reports`  
    Implementação de relatórios vinculados a WorkReport.

20. **24/02/2026** - `feature/report-service-survey`  
    Implementação/evolução de relatórios de pesquisa de serviço.

21. **24/02/2026** - `refactor/hierarc-user-paralel`  
    Refatoração da hierarquia de usuários em fluxo paralelo.

22. **26/02/2026** - `fix/fix-date-list-ads-partner`  
    Correção de data/listagem de ADS para perfil parceiro.

23. **27/02/2026** - `feature/list-report-ads-situations`  
    Entrega de listagem de relatório de situações de ADS.

## 4. Evidências de Carga Técnica

Arquivos/módulos com maior recorrência de alterações no período:

- `routes/web.php` (22 alterações)
- `appver.json` (16 alterações)
- `resources/views/layouts/menu_itens.blade.php` (10 alterações)
- `app/Console/Commands/Ads/GenerateTacitAds.php` (10 alterações)
- `app/Http/Livewire/Responsible/AdsRequests.php` (7 alterações)
- `resources/views/livewire/responsible/ads-requests.blade.php` (7 alterações)
- `resources/views/livewire/partner/ads-requests.blade.php` (7 alterações)

Interpretação técnica: houve atuação simultânea em backend (serviços, comandos, controllers), rotas e frontend Livewire/Blade, com volume elevado e múltiplas frentes críticas em paralelo.

## 5. Distribuição das Atividades

Dias com maior intensidade de entregas (quantidade de commits):

- **24/02/2026:** 8 commits
- **06/01/2026:** 5 commits
- **19/01/2026:** 5 commits
- **26/01/2026:** 5 commits
- **27/01/2026:** 5 commits
- **09/02/2026:** 5 commits
- **25/02/2026:** 5 commits
- **26/02/2026:** 5 commits

Registros de atividade após 18:00 (fora do horário comercial):

- 29/01/2026 às 18:05
- 31/01/2026 às 18:02
- 02/02/2026 às 18:00 e 18:08
- 03/02/2026 às 19:04
- 19/02/2026 às 18:15 e 19:05
- 24/02/2026 às 18:23, 18:40 e 18:42

## 6. Justificativa Formal de Horas Extras

As horas extras no período de janeiro a março/2026 se justificam pela necessidade de:

- atender entregas simultâneas de **feature**, **fix**, **refactor** e **chore** em módulos críticos;
- corrigir incidentes com impacto direto em geração/exportação de relatórios e estabilidade de produção;
- manter continuidade operacional em fluxos de ADS, protestos, despacho e cancelamentos/pagamentos;
- concluir integrações dentro das janelas de entrega do time (`develop`), inclusive com atividade após horário comercial.

Com base nas evidências apresentadas (volume técnico, integrações de branch, alterações transversais e registros após 18:00), conclui-se que a extensão de jornada foi necessária para garantir estabilidade, cumprimento de prazo e continuidade do serviço.

## 7. Anexos Sugeridos

- Extrato de ponto/registro de jornada de janeiro, fevereiro e março/2026.
- Relatório de commits Git do período.
- Tickets/chamados vinculados às branches entregues.
- Evidências de validação/homologação das correções e features.

## 8. Aprovações

**Colaborador:** Will Oliveira  
Assinatura: ____________________________________________  
Data: ____/____/________

**Gestor:** Daniel Fonseca  
Assinatura: ____________________________________________  
Data: ____/____/________

**Gestor:** Marcio Costa Longa  
Assinatura: ____________________________________________  
Data: ____/____/________

---

**Observação importante:** este dossiê técnico foi montado com base no histórico Git. Para uso formal em RH/DP, recomenda-se anexar o espelho de ponto para vincular diretamente o volume de atividade aos tempos exatos de hora extra.
