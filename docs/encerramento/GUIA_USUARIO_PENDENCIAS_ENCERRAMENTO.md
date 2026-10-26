# Guia do usuário — Pendências de Encerramento

## O que é uma pendência

Registra, de forma estruturada, por que uma Ordem não pode avançar e o que a parceira precisa fazer. Não altera o SAP nem as operações. Quando bloqueia a Ordem, ela fica em **Aguardando Parceira** e volta ao ponto anterior ao resolver a última pendência bloqueadora.

## Abrir (equipe interna)

Na **Gestão da Obra**, em **Enviar à parceira**, escolha categoria e subcategoria, empresa parceira, se é bloqueante, as Ordens envolvidas e descreva. Categorias: Material/MIGO pendente, Documentação/As-built, Medição, Depósito, Apontamento de horas/operações, Dados da obra e Outros. Categorias inativadas continuam visíveis nas pendências antigas.

## Fluxo

1. A equipe envia a pendência à parceira.
2. A parceira responde.
3. A equipe **valida**, **devolve** para correção (mesmo registro, novo ciclo) ou **resolve**.

Cada passo guarda data, autor, origem e mensagem, e aparece no chat da obra.

## Portal da parceira

- **Menu:** o item **Encerramento** ganha uma **bolinha vermelha** quando há pendência esperando você, e o link **Pendências** mostra a **quantidade** (aguardando resposta + devolvidas para correção).
- **Encerramento > Pendências:** lista só as pendências da sua empresa, com o tempo de espera de cada uma. Filtre por Nota/Ordem, situação e categoria. **Exportar Excel** gera a lista com os filtros da tela.
- **Ver pedido e responder:** abre a pendência por inteiro. Você lê o que o Encerramento pediu (descrição, **lista de exigências**, se a Ordem fica travada), o **motivo da devolução** quando ela volta para correção, o histórico com data de cada etapa e o tempo entre elas, e os arquivos já trocados.
- **Seu retorno:** responda **cada exigência** (Atendido, Não se aplica ou Não consigo atender; nos dois últimos a justificativa é obrigatória), escreva a mensagem e anexe as **evidências** (PDF, imagens, Excel, Word, CSV, TXT ou ZIP, até 10 MB cada). Depois de enviado, o retorno fica visível em modo leitura. **Exportar Excel** baixa o pedido, suas respostas e o histórico.
- **Encerramento > Meu histórico:** em quais obras e pendências você já respondeu, o que respondeu, quando, quantos anexos e o que aconteceu depois (aceita, devolvida, em validação ou aguardando). Filtros por busca, situação e período, e exportação em Excel.
- **Obra:** abra a obra para ver o chat. A parceira só vê mensagens marcadas como **Com parceira** e só de obras ligadas à sua empresa. Ela pode escrever no chat, mas não altera posse, status interno, SAP ou operações.
- **Quem observa (superadm):** vê as telas da parceira como **uma empresa por vez** (seletor no topo), em modo somente leitura, sem controles internos e sem responder em nome dela.

## Caixa do Encerramento em Medição e Fiscalização

Quem trabalha em **Pagamento (Medição)** ou **Fiscalização** vê no menu da atividade o grupo **ENCERRAMENTO** com dois atalhos e seus selos: **EM ACOMPANHAMENTO** (selo azul, quantas Ordens estão nos status do serviço) e **DESPACHADAS PARA RESOLVER** (selo vermelho, pendências esperando o serviço; some quando não há). Cada atalho abre a aba correspondente da caixa, que tem duas abas:

- **Em acompanhamento** (só visão): **todas** as Ordens do Encerramento (despachadas ou não, as mesmas da lista do Despacho) que estão, agora, em um status do usuário (SAP) que a gestão ligou a esse serviço. Não há nada a responder — quando o status muda, a Ordem sai da caixa sozinha. Cada cartão no topo é um status (clique para filtrar); a tabela mostra valor da obra, status SAP, desde quando a Ordem está no status (só para as já despachadas; "desde o 1º registro" indica que o início real pode ser anterior), se ela está despachada e com quem, e há filtros de status, responsável, empresa do informe, busca, tipo, busca em massa e exportação para Excel. A mudança de status pode levar até 5 minutos para refletir.
- **Despachadas para o serviço** (ação): pendências que o Encerramento enviou e esperam esse serviço.

### Como a gestão liga um status a um serviço
**Encerramento › Gestão do ciclo › Mapa de status por serviço** (ou o botão "Configurar status desta caixa" dentro da caixa). Em cada serviço, marque os status que pertencem a ele e clique em **Salvar**. Um status só pode pertencer a **um** serviço: se já estiver em outro, aparece desabilitado com o nome do dono — libere-o lá primeiro. O número ao lado de cada status é quantas Ordens do Encerramento estão nele agora.

## Avisos

A parceira recebe sino e toast quando uma pendência é enviada ou devolvida e quando chega mensagem "Com parceira". A equipe é avisada quando a parceira responde ou escreve. No histórico da pendência, o Encerramento vê a resposta **por exigência** (atendido / não se aplica / não consigo, com a justificativa) e a quantidade de anexos.
