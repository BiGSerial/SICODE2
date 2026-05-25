# Sitemap - Modulo Juridico

## 1. Estrutura global

1. Juridico
2. Juridico > Cockpit de Demandas
3. Juridico > Fila do Controlador
4. Juridico > Minha Fila (Ponta)
5. Juridico > Detalhe da Demanda
6. Juridico > Observabilidade
7. Juridico > Saude de Importacao
8. Juridico > Configuracoes de Alertas
9. Juridico > Relatorios e Exportacao

## 2. Mapa de navegacao por nivel

## 2.1 Nivel 1

- Cockpit de Demandas
- Fila do Controlador
- Minha Fila (Ponta)
- Observabilidade
- Relatorios
- Configuracoes

## 2.2 Nivel 2 - Cockpit de Demandas

- Lista Geral
- Filtros Salvos
- Acoes em Massa
- Busca Avancada

## 2.3 Nivel 2 - Fila do Controlador

- Novas Importadas
- Devolvidas pela Ponta
- Em Revisao
- Correcao Pendente
- Prontas para Fechamento Externo
- Fechadas Internamente sem Baixa Externa

## 2.4 Nivel 2 - Minha Fila (Ponta)

- Recebidas e Nao Abertas
- Em Andamento
- Aguardando Minha Resposta
- Correcao Solicitada
- Atrasadas

## 2.5 Nivel 2 - Observabilidade

- Visao Geral
- Por Origem
- Por Area
- Por Usuario
- Gargalos
- SLA e Prazos

## 2.6 Nivel 2 - Relatorios

- Exportacao de Demandas
- Exportacao de Gargalos
- Exportacao de Prazos
- Exportacao de Fechamentos Externos Pendentes

## 2.7 Nivel 2 - Configuracoes

- Regras de Alertas
- Parametros de SLA Interno
- Preferencias de Exibicao

## 3. Fluxo principal de navegacao

1. Usuario acessa Cockpit de Demandas.
2. Filtra por urgencia (vencidas, vence hoje, sem responsavel).
3. Abre Detalhe da Demanda.
4. Executa acao por perfil:
- Controlador: triagem, envio, revisao, fechamento.
- Ponta: recebimento, resposta, evidencia.
5. Retorna para fila contextual (controlador ou ponta).
6. Gestao consulta Observabilidade para acompanhamento.

## 4. Estrutura da tela Detalhe da Demanda

1. Cabecalho da demanda
- Processo
- Origem
- Prazo
- Status interno
- Status origem
- Responsavel atual

2. Aba Resumo
- Dados principais
- Risco e prioridade
- Pendencias de acao

3. Aba Tratativas
- Historico de assignments
- Estado atual de resposta
- Solicitar correcao / aprovar retorno

4. Aba Arquivos
- Lista de anexos por categoria
- Visibilidade
- Marcacao external_ready

5. Aba Comentarios
- Comentarios internos
- Comentarios vinculados ao assignment

6. Aba Timeline
- Eventos de ponta a ponta com data/hora e ator

7. Aba Snapshots da Origem
- Ultimas leituras da origem
- Variacoes de prazo e status

8. Aba Encerramento
- Encerramento interno
- Encerramento externo (protocolo obrigatorio)
- Reabertura

## 5. Rotas sugeridas

- /juridico/demandas
- /juridico/demandas/fila-controlador
- /juridico/demandas/minha-fila
- /juridico/demandas/{uuid}
- /juridico/observabilidade
- /juridico/observabilidade/origem
- /juridico/observabilidade/area
- /juridico/observabilidade/usuario
- /juridico/importacao/saude
- /juridico/configuracoes/alertas
- /juridico/relatorios

## 6. Navegacao por perfil

## 6.1 Controlador

Landing recomendada:

- /juridico/demandas/fila-controlador

Acessos criticos:

- detalhe da demanda
- observabilidade
- relatorios

## 6.2 Usuario da ponta

Landing recomendada:

- /juridico/demandas/minha-fila

Acessos criticos:

- detalhe da demanda
- anexos e resposta

## 6.3 Gestao

Landing recomendada:

- /juridico/observabilidade

Acessos criticos:

- relatorios
- saude de importacao

## 7. Estados vazios e excecoes no sitemap

- Sem demandas na fila
- Sem permissao para acao
- Demanda bloqueada por status
- Origem sem atualizacao recente
- Falha de importacao no ultimo batch

## 8. Regras de visibilidade na navegacao

- Itens de menu devem ser exibidos por permissao.
- Acoes de fechamento externo apenas para perfis autorizados.
- Conteudo sensivel de arquivos deve respeitar visibilidade de anexo.
- A rota de detalhe pode abrir em modo leitura para perfil sem acao.

## 9. Mapa de cores oficiais do projeto

Fonte oficial de tokens:

- resources/sass/_variables.scss

### 9.1 Core brand (EDP official color tokens)

- marine-blue: #212E3E
- slate-grey: #7C9599
- spruce-green: #143F47
- seaweed-green: #225E66
- cobalt-blue: #263CC8
- ice-blue: #0CD3F8
- violet-purple: #6D32FF
- electric-green: #28FF52
- black: #222222
- white: #FFFFFF

### 9.2 Paleta semantica oficial (EDP semantic palette)

- semantic-blue: #263CC8
- semantic-blue-soft: #A8B1E9
- semantic-green: #225E66
- semantic-green-soft: #91AFB3
- semantic-red: #E32C2C
- semantic-red-soft: #EDD5D3
- semantic-yellow: #F7D200
- semantic-yellow-soft: #FFF1BE

### 9.3 Mapa semantico Bootstrap usado no projeto

- primary: #263CC8
- secondary: #225E66
- success: #225E66
- info: #A8B1E9
- warning: #F7D200
- danger: #E32C2C
- light: #BECACC
- dark: #212E3E

### 9.4 Aplicacao recomendada no modulo juridico

- Acao principal (CTA): primary
- Destaques de navegacao secundaria: secondary
- Estados de sucesso/conclusao: success
- Informacao auxiliar/estado neutro de apoio: info
- Alertas de atencao (vence em breve): warning
- Estados criticos (vencido/bloqueio): danger
- Superficie de apoio e chips de baixo contraste: light
- Texto base e cabecalho de contexto: dark

### 9.5 Regra de consistencia para Design System

- Nao usar codigos hex hardcoded em componentes do modulo juridico quando houver token equivalente.
- Priorizar tokens semanticos (primary/success/warning/danger) para estados da interface.
- Usar tons brand/extended para variacoes de grafico, mantendo contraste minimo AA.
