# Componentes do Design System - Prioridade do Modulo Juridico

## 1. Escala de prioridade

- P0: bloqueia operacao sem o componente.
- P1: alto impacto em produtividade e clareza.
- P2: melhora de eficiencia e consistencia.
- P3: evolucao visual e refinamento.

## 2. Lista priorizada

## 2.1 P0 - Criticos para go-live

1. DataTable Operacional
- Uso: cockpit, fila do controlador, minha fila.
- Requisitos: ordenacao, filtros, colunas fixas, paginacao, acoes por linha, selecao em massa.

2. Status Badge
- Uso: status interno da demanda e status da origem.
- Requisitos: mapa fixo de cores e labels por status.

3. Deadline Chip
- Uso: vencida, vence hoje, vence em 3 dias, em prazo.
- Requisitos: semaforo de risco e tooltip com data/hora.

4. Action Bar Contextual
- Uso: acoes do controlador e da ponta no detalhe.
- Requisitos: habilitar/desabilitar por status e permissao.

5. Formulario de Tratativa
- Uso: enviar para ponta, responder, solicitar correcao.
- Requisitos: validacoes obrigatorias e mensagens de erro acionaveis.

6. Upload Manager com Metadados
- Uso: anexos por categoria/visibilidade.
- Requisitos: categoria, visibilidade, flags de evidencia/final_response/external_ready.

7. Timeline Auditavel
- Uso: historico de eventos.
- Requisitos: data/hora, ator, evento, transicao de status, metadata chave.

8. Notification Banner
- Uso: alertas criticos (prazo, sem responsavel, sem baixa externa).
- Requisitos: severidade, persistencia, call-to-action.

## 2.2 P1 - Alta prioridade

1. Filter Drawer Avancado
- Uso: filtros combinados na lista.
- Requisitos: salvar filtros, limpar rapido, presets por perfil.

2. KPI Card
- Uso: visao geral e observabilidade.
- Requisitos: valor, variacao, estado de risco, link para detalhar.

3. Assignment History Table
- Uso: bloco de tratativas.
- Requisitos: enviado por, destino, sent_at, received_at, answered_at, SLA local.

4. Empty State Padronizado
- Uso: filas vazias e telas sem dados.
- Requisitos: mensagem clara + acao recomendada.

5. Permission Guard Wrapper
- Uso: esconder/desabilitar elementos por permissao.
- Requisitos: fallback visual e explicacao de bloqueio.

6. Confirm Modal com Contexto
- Uso: encerramento, reabertura, cancelamento de assignment.
- Requisitos: resumo do impacto + confirmacao explicita.

## 2.3 P2 - Media prioridade

1. Snapshot Diff Viewer
- Uso: comparar alteracoes entre snapshots da origem.
- Requisitos: antes/depois por campo relevante.

2. Bulk Action Toolbar
- Uso: alteracao de prioridade, atribuicao e exportacao em lote.
- Requisitos: validacao de elegibilidade por status.

3. Saved Views Manager
- Uso: favoritos de filtro e layout por perfil.
- Requisitos: criar, editar, compartilhar (opcional).

4. Side Panel de Detalhe Rapido
- Uso: consulta sem sair da lista.
- Requisitos: preview de resumo e proximas acoes.

## 2.4 P3 - Refinamento

1. Heatmap de Gargalos
- Uso: observabilidade por etapa e area.

2. Onboarding contextual
- Uso: dicas para novos usuarios por perfil.

3. Macro de resposta
- Uso: acelerar retorno de ponta e controlador.

## 3. Tokens e fundamentos visuais

## 3.1 Cores de status (sugestao)

- new_imported: azul
- triage: azul-escuro
- sent_to_field/field_received/waiting_field_response: laranja
- returned_by_field/under_controller_review/returned_for_correction: amarelo
- ready_to_close_external: ciano
- closed_internal: cinza
- closed_external: verde
- reopened: vermelho
- cancelled/ignored: grafite

## 3.2 Tipografia

- Titulo de pagina
- Titulo de bloco
- Label de metrica
- Texto de detalhe
- Texto tecnico (ids, protocolos)

## 3.3 Espacamento e densidade

- Densidade compacta para lista operacional.
- Densidade confortavel para detalhe e formularios.

## 4. Regras de acessibilidade

- Contraste minimo WCAG AA.
- Navegacao por teclado nas tabelas e acoes.
- Feedback de erro em texto claro (nao so cor).
- Indicacao de estado desabilitado com motivo.

## 5. Ordem de implementacao sugerida

1. Pacote P0 completo.
2. P1 focado em filtros, KPIs e governanca de permissao.
3. P2 para ganho de produtividade operacional.
4. P3 para maturidade e experiencia avancada.
