# Padrão de Estilização de Exportações (SICODE)

## Objetivo
Padronizar aparência e auditoria dos relatórios exportados (Excel), mantendo identidade visual do sistema.

## Padrão visual obrigatório
1. Usar `StyledArraySheetExport` em todas as abas.
2. Linha 1 com título `SICODE - <NOME DA ABA>`.
3. Cabeçalho com fundo escuro, texto branco e negrito.
4. Zebra rows na área de dados.
5. Bordas suaves em toda a tabela.
6. Congelar painel em `A3`.
7. Ativar autofiltro no cabeçalho.
8. Ajustar alinhamento/formatação por tipo de coluna:
   - Valores/custos: numérico com 2 casas.
   - Percentuais: numérico com 2 casas.
   - Datas: formato data/hora.
   - Comentários: quebra de linha e coluna larga.

## Auditoria obrigatória no documento
Toda exportação deve incluir aba `Controle Exportacao` com no mínimo:
- Usuário solicitante
- Email
- Data/Hora da solicitação
- Tipo de exportação

## Regra de implementação
Ao criar ou alterar exportação:
1. Verificar como estava antes (cores/layout) para não regredir.
2. Aplicar este padrão como baseline.
3. Garantir aba de auditoria preenchida pelo Job.
