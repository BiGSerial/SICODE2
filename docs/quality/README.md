# Módulo Qualidade

Domínio próprio que controla a **validação dos trabalhos do serviço Desenho** em duas ciclos, com hierarquia obrigatória **N2 → N1 → Desenhista**.

| Documento | Conteúdo |
|-----------|----------|
| [fluxo-funcional.md](fluxo-funcional.md) | Papéis, ciclos, estados, transições, rodadas, rejeições |
| [modelo-dominio.md](modelo-dominio.md) | Tabelas, enums, services, permissões, telas |
| [encerramento.md](encerramento.md) | Encerramento final e tratamento de encerramentos pendentes (legado) |
| [auditoria-implementacao.md](auditoria-implementacao.md) | Auditoria da entrega inicial (Codex) x especificação, com o que foi corrigido |

## Resumo

1. O **Pool** é uma *query de Notas* (tipo 1, critérios configuráveis, filtros por rubrica/região/município etc.); nada persiste. A **Gestão** despacha Notas a uma **empresa** → cria o `QualityProcess`.
2. **1º ciclo** (hoje Levantamento): o **N1** (cadastrado na Equipe da empresa) despacha ao **usuário**; a Production nasce nessa hora, na atividade configurada do 1º ciclo.
3. O usuário finaliza pelo **formulário dedicado** (informe de encerramento do Levantamento), os arquivos ficam associados à atividade e a rodada **cai na análise do N1**.
4. O N1 aprova (→ N2) ou rejeita (→ usuário, nova rodada). O N2 aprova ou **devolve ao N1** (nunca ao usuário); o N1 encaminha (pode trocar o usuário) ou **questiona o N2**.
5. **N2 aprova o 1º ciclo** ⇒ a atividade é encerrada (`completed_at` = data do envio ao N1) e a do **2º ciclo** (hoje Desenho) **abre automaticamente para o mesmo usuário e empresa**.
6. O 2º ciclo segue os mesmos critérios (formulário com ordens do orçamento). **N2 aprova ⇒ atividade encerrada → Qualidade concluída. Não volta mais.**
7. Toda devolução/rejeição exige **categoria** (e subcategoria quando houver). Motivos, filas e a aba Discussão N1 ↔ N2 mapeiam tudo.
8. Não há etapa de SAP: a aprovação final do N2 encerra a atividade e conclui a Qualidade na mesma transação.
9. Todo passo gera evento (`quality_events`) com usuário, papel, empresa, ciclo, rodada e estado anterior/posterior.
10. Nota com Qualidade aberta não aceita novo pedido nas atividades da Qualidade.

## Como validar em worktree

```bash
DC="docker compose -f docker-compose.orca.yml --env-file .env.orca"
$DC exec -T app php artisan migrate         # migrations não rodam sozinhas
$DC exec -T app ./vendor/bin/pest tests/Feature/QualityWorkflowFeatureTest.php tests/Feature/QualityScreensFeatureTest.php tests/Unit/QualityDomainContractTest.php
```

Telas: `/quality/dashboard`, `/quality/pool`, `/quality/queue?fila=minha-fila`, `/quality/history`, `/quality/processes/{id}`, `/quality/categories`, `/quality/settings`.

## Pendências conhecidas

- O status final do SAP não é necessário: não há etapa de SAP no encerramento (ver `encerramento.md`).
- As regras de validação das ordens de orçamento estão em `BudgetOrderRules`, espelhando as de `Forms\Analise` (que são métodos privados de um componente de 2.300 linhas). Consolidar em um único service é uma melhoria futura.
- N1/N2 são cadastrados em `quality_members` por empresa; quem é N1 em uma empresa e N2 em outra vê os cartões de N2 nas duas (papel por empresa só é aplicado às ações, não aos cartões).
- Concorrência é tratada com `lockForUpdate` + verificação de estado; o teste simula o segundo clique (ação sobre etapa já tratada), não threads paralelas reais.
