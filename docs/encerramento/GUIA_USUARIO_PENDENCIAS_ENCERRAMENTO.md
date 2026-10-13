# Guia do usuário — Pendências de Encerramento

## O que é uma pendência

Uma pendência registra de modo estruturado por que uma Ordem de Encerramento não pode avançar. Ela não altera o status SAP, nem o status das operações SAP. A atividade operacional fica identificada como **Aguardando retorno**, com a causa preservada na linha do tempo.

## Abertura e classificação

O responsável seleciona uma categoria e uma subcategoria compatível, informa a empresa parceira que deve agir e descreve a solicitação quando a subcategoria exigir detalhamento.

Categorias e subcategorias podem deixar de ser usadas no futuro, mas nunca desaparecem das pendências antigas. Por isso os filtros e relatórios futuros continuarão a explicar a causa original.

## Fluxo de trabalho

1. A pendência é aberta e, quando bloqueadora, pausa a tratativa no ponto atual.
2. O responsável solicita o retorno à empresa parceira.
3. A parceira responde à mesma pendência.
4. O responsável valida a resposta, devolve para correção ou resolve.
5. Ao resolver a última pendência bloqueadora, a atividade retorna ao ponto operacional anterior.

Uma devolução não cria outra pendência: o mesmo registro recebe um novo ciclo. Assim, o histórico mostra quantas vezes houve correção e quem atuou em cada etapa.

## Rastreabilidade e segurança

Cada mudança guarda data/hora, ator, origem (interna ou parceira), estado anterior, estado seguinte e mensagem informada. A parceira só poderá responder pendências destinadas à sua própria empresa; ela não poderá transferir posse, alterar o SAP ou modificar operações.

## Caixa da parceira

No portal parceiro, abra **Encerramento > Pendências**. A lista contém exclusivamente pendências destinadas à empresa do usuário. Use a busca por Nota/Ordem, situação e categoria para encontrar a demanda.

Quando a situação for **Aguardando resposta** ou **Devolvida para correção**, selecione **Responder**, descreva o retorno e envie. A resposta fica registrada no histórico; a parceira não tem controles para alterar posse interna, SAP ou operações.

## Limite temporário desta entrega

A caixa e a resposta já estão implementadas, mas aguardam validação em banco no ambiente isolado. O envio de anexos/evidências ainda não está disponível: o mecanismo de arquivos atual é específico de cancelamentos e precisa ser adaptado com trilha própria.
