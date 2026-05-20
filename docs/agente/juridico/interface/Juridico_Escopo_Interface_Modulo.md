# Escopo de Interface - Modulo Juridico

## 1. Objetivo do modulo na interface

Dar ao time juridico e operacional uma visao unica de demandas (liminares, sentencas/cumprimentos e subsidios), com foco em:

- nao perder prazo;
- nao perder demanda sem responsavel;
- garantir rastreabilidade completa da tratativa;
- diferenciar claramente o que esta com controlador vs com usuario da ponta;
- garantir fechamento interno e fechamento externo com comprovacao.

## 2. Perfis e responsabilidades na UX

### 2.1 Controlador

Responsavel por:

- triagem inicial;
- definir prioridade e risco;
- enviar para usuario/equipe;
- acompanhar recebimento e resposta;
- solicitar correcao;
- aprovar retorno;
- encerrar internamente;
- registrar encerramento externo;
- reabrir quando necessario.

### 2.2 Usuario da ponta (executor)

Responsavel por:

- receber demanda;
- marcar andamento;
- responder com texto e/ou evidencia;
- devolver com justificativa de impossibilidade;
- atender correcao solicitada pelo controlador.

### 2.3 Gestao/observabilidade

Responsavel por:

- monitorar backlog, SLA e gargalos;
- enxergar onde a demanda parou;
- acompanhar risco de prazo por area e por pessoa;
- auditar historico de eventos e anexos.

## 3. Fluxo operacional traduzido para interface

Fluxo principal da demanda:

1. Nova demanda importada.
2. Triagem do controlador.
3. Envio para ponta (usuario ou equipe).
4. Recebimento pela ponta.
5. Resposta da ponta.
6. Revisao do controlador.
7. Correcao (se necessario).
8. Pronta para encerramento externo.
9. Encerramento interno.
10. Encerramento externo (protocolo).
11. Reabertura (quando aplicavel).

Regra critica de comunicacao visual:

- "Ausente na origem" nao significa "encerrada".
- Esse estado deve aparecer como alerta de sincronizacao, sem baixar a demanda automaticamente.

## 4. Mapa de status para UI

Status internos que precisam de representacao visual consistente (badge + cor + descricao de acao esperada):

- new_imported
- triage
- waiting_controller_action
- sent_to_field
- field_received
- waiting_field_response
- returned_by_field
- under_controller_review
- returned_for_correction
- ready_to_close_external
- closed_internal
- closed_external
- reopened
- cancelled
- ignored

Estados complementares exibidos como sinalizadores (nao substituem status interno):

- vencida (prazo da origem estourado)
- vence hoje
- vence em ate 3 dias
- sem responsavel
- ausente na origem (missing)
- encerrada internamente sem fechamento externo

## 5. Escopo de telas do modulo

## 5.1 Tela 1 - Lista de Demandas (cockpit operacional)

Objetivo:

- priorizar rapidamente o que precisa de acao.

Colunas minimas:

- processo;
- origem (liminar/sentence/subsidy);
- assunto;
- prazo origem;
- status SICODE;
- status origem;
- responsavel atual;
- area destino;
- prioridade;
- risco;
- ultima atualizacao.

Acoes rapidas por linha:

- assumir/iniciar triagem;
- enviar para ponta;
- transferir responsavel;
- abrir detalhe;
- marcar prioridade/risco;
- encerrar interno (com permissao);
- registrar encerramento externo (com permissao).

Filtros obrigatorios:

- status interno;
- prazo (vencidas, hoje, proximos dias);
- sem responsavel;
- aguardando ponta;
- em revisao do controlador;
- pronta para encerramento externo;
- encerrada internamente sem externo;
- ausente na origem;
- reabertas;
- origem, area, usuario, empresa, escritorio.

## 5.2 Tela 2 - Detalhe da Demanda (visao 360)

Objetivo:

- executar o ciclo completo sem trocar de contexto.

Blocos obrigatorios:

- resumo executivo;
- dados do processo;
- dados da origem;
- prazo e risco;
- responsavel atual;
- tratativas/assignments;
- anexos e evidencias;
- comentarios;
- timeline de eventos;
- snapshots da origem;
- encerramento.

Acoes do controlador no detalhe:

- iniciar triagem;
- enviar para usuario/equipe com prazo interno e mensagem;
- aprovar retorno;
- solicitar correcao;
- transferir/reencaminhar;
- encerrar internamente;
- registrar encerramento externo com protocolo;
- reabrir.

Acoes da ponta no detalhe:

- confirmar recebimento;
- responder (texto/evidencia/justificativa);
- anexar novos documentos;
- responder correcao.

Validacao de UX critica:

- nao permitir resposta vazia;
- nao permitir envio sem destino;
- nao permitir fechamento externo sem protocolo;
- nao permitir acao bloqueada pelo status sem feedback claro.

## 5.3 Tela 3 - Fila do Controlador

Objetivo:

- mostrar somente o que depende do controlador agora.

Cards/filtros principais:

- novas importadas;
- devolvidas pela ponta;
- em revisao;
- devolvidas para correcao sem retorno;
- prontas para fechamento externo;
- fechamento interno sem baixa externa.

## 5.4 Tela 4 - Minha Fila (usuario da ponta)

Objetivo:

- reduzir tempo de resposta da ponta.

Secoes:

- recebidas e nao abertas;
- em andamento;
- aguardando resposta;
- com correcao solicitada;
- atrasadas.

Acoes:

- confirmar recebimento;
- responder;
- anexar evidencia;
- informar impossibilidade.

## 5.5 Tela 5 - Observabilidade e Gestao

Objetivo:

- monitoramento gerencial e prevencao de perda de prazo.

Painel visao geral (cards):

- total abertas;
- total vencidas;
- total vencem hoje;
- total vencem em 3 dias;
- total sem responsavel;
- total aguardando ponta;
- total devolvidas pela ponta;
- total em revisao do controlador;
- total prontas para fechamento externo;
- total encerradas internamente sem fechamento externo;
- total reabertas;
- total ausentes na origem.

Painel por recorte:

- por origem;
- por area destino;
- por usuario da ponta;
- por gargalo de etapa (tempos medios).

## 5.6 Tela 6 - Saude da Importacao

Objetivo:

- dar previsibilidade sobre sincronizacao com sistemas externos.

Dados por batch:

- inicio/fim;
- duracao;
- total, novos, atualizados, inalterados;
- ausentes, falhas;
- status e erro.

## 6. Componentes de interface obrigatorios

- barra de prioridade com foco em vencidas e sem responsavel;
- badges de status interno + chips de risco de prazo;
- stepper de ciclo da demanda;
- timeline auditavel de eventos;
- tabela de assignments (quem enviou, para quem, quando recebeu, quando respondeu);
- painel de anexos com categoria e visibilidade;
- area de fechamento externo com protocolo;
- historico de snapshots da origem com comparacao de alteracoes;
- alertas em destaque para "atividade sem baixa".

## 7. Regras de visibilidade de arquivos na UI

Visibilidades que devem guiar o que cada perfil enxerga:

- controller_only
- assigned_user_only
- internal_all
- legal_area
- external_ready

Categorias que devem aparecer como filtro e etiqueta:

- legal_document
- technical_evidence
- field_return
- controller_note
- external_protocol
- final_response
- other

Regras visuais:

- arquivo removido logicamente deve aparecer como removido no historico (sem sumir da trilha);
- destacar arquivo apto para envio externo;
- separar claramente anexos de evidencia e resposta final.

## 8. Alertas e automacoes de notificacao (UX)

Alertas obrigatorios:

- demanda nova critica;
- vencimento hoje;
- vencimento em 3 dias;
- vencida;
- ponta nao recebeu apos X horas;
- ponta recebeu e nao respondeu apos X dias;
- encerrada internamente sem encerramento externo apos X dias;
- reapareceu na origem;
- ausente na origem;
- prazo externo alterado.

Canais recomendados:

- centro de notificacoes do sistema;
- destaque em dashboard;
- lista de pendencias por perfil.

## 9. Escopo funcional por fase (para design e produto)

### Fase 1 - Operacao basica

- Lista de demandas;
- Detalhe da demanda;
- Triagem;
- Envio para ponta;
- Recebimento/resposta da ponta;
- Revisao e correcao;
- Encerramento interno;
- Timeline basica.

### Fase 2 - Fechamento robusto e documental

- Encerramento externo com protocolo;
- Painel de anexos com visibilidade;
- Comentarios internos;
- Fila do controlador e fila da ponta;
- Transferencia/reencaminhamento com historico.

### Fase 3 - Observabilidade gerencial

- Dashboard gerencial completo;
- Painel de gargalos;
- Saude de importacao;
- Alertas de prazo e baixa pendente;
- Indicadores de risco por area e usuario.

## 10. Criterios de aceite de interface (Definition of Done UX)

- Qualquer demanda e localizavel em ate 3 cliques.
- O usuario entende "de quem e a bola" sem abrir detalhes.
- Vencidas, sem responsavel e sem baixa externa ficam sempre evidentes.
- Toda acao relevante deixa rastro na timeline.
- O controlador consegue fechar ciclo completo sem depender de planilha externa.
- O executor consegue responder com evidencia em fluxo simples.
- A gestao enxerga gargalo por etapa, area e usuario.
- O modulo evita perda silenciosa de prazo e perda silenciosa de baixa.

## 11. Pontos de atencao para o time de design

- Priorizar legibilidade de estados e prazos acima de volume de informacao.
- Evitar interface "tabela pura": combinar tabela + cards + timeline + highlights.
- Tratar bloqueios de permissao/status com mensagens acionaveis (o que falta para prosseguir).
- Em mobile, priorizar fila por prioridade e acao rapida (receber, responder, aprovar, solicitar correcao).
- Incluir feedback explicito para "acao concluida" e "acao bloqueada".

## 12. Resumo executivo

O modulo juridico deve ser desenhado como um sistema de controle de ciclo e prazo, nao apenas consulta. A interface precisa deixar explicito, em cada momento, quatro perguntas:

1. O que e mais urgente agora?
2. Quem precisa agir agora?
3. O que falta para concluir a demanda?
4. O que esta sem baixa/sem dono/fora do prazo?

Se essas quatro respostas estiverem claras na lista, no detalhe e nos dashboards, o modulo reduz risco de perda de prazo e melhora governanca do processo juridico de ponta a ponta.
