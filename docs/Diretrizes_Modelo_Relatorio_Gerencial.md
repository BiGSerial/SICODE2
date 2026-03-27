# Diretrizes - Modelo de Relatorio Gerencial Semanal

## Objetivo
Padronizar o relatorio gerencial semanal em linguagem simples para publico nao tecnico, mantendo comparabilidade entre semanas.

## Janela de apuracao
- Sempre informar explicitamente o periodo fechado da semana anterior.
- Formato recomendado: `dd/mm/aaaa a dd/mm/aaaa`.

## Estrutura obrigatoria
Usar exatamente 4 blocos:

1. **Resumo executivo**
- Quantidade de commits no periodo (incluindo merges em `develop`).
- Quantidade de arquivos alterados.
- Total de insercoes e delecoes.
- Frentes principais da semana (texto curto e objetivo).

2. **Entregas principais**
- Agrupar por frente funcional (ex.: Analise de Projetos, Admin/Usuarios, Fluxos operacionais, Navegacao).
- Em cada frente, listar os principais itens entregues em bullets curtos.
- Priorizar o que muda para a operacao/gestao, evitando detalhamento tecnico excessivo.

3. **Impacto esperado**
- Explicar efeitos práticos para operacao e gestao.
- Foco em: rastreabilidade, governanca, produtividade, confiabilidade e visibilidade gerencial.

4. **Proximos passos (semana atual)**
- Lista objetiva de acoes de validacao/homologacao/estabilizacao.
- Itens acionaveis e verificaveis.

## Padrao de redacao
- Iniciar com: `Prezados, Segue o resumo gerencial das entregas da semana passada (...).`
- Linguagem executiva, simples e direta.
- Evitar jargao tecnico quando nao for essencial.
- Manter bullets curtos e foco em resultado.

## Template oficial
```text
Prezados,
Segue o resumo gerencial das entregas da semana passada (dd/mm/aaaa a dd/mm/aaaa).

1. Resumo executivo
- X commits no periodo (incluindo merges em develop).
- X arquivos alterados.
- X insercoes e X delecoes.
- Foco principal: [frentes principais da semana].

2. Entregas principais
- [Frente 1]:
  - [entrega objetiva]
  - [entrega objetiva]
- [Frente 2]:
  - [entrega objetiva]
  - [entrega objetiva]

3. Impacto esperado
- [impacto 1]
- [impacto 2]
- [impacto 3]

4. Proximos passos (semana atual)
- [acao 1]
- [acao 2]
- [acao 3]
```
