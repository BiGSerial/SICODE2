# Hierarquia de usuários (Administração → Hierarquia)

Tela: `/admin/user/hierarchy` (`Admin\Hierarchy\Org`). Substitui o antigo `Board`/`Manager`.

## Camadas
- **Dados** — `App\Services\Hierarchy\HierarchyTreeService`: `nodes()` (árvore plana), `badges()` (coberturas e observações vigentes), `detail()` (painel), `impact()` (o que muda ao mover, sem gravar), `move()` e `coverageOverview()`. A gravação da árvore continua em `App\Services\HierarchyService::moveSubtree` (mantém `user_closure` e auditoria).
- **Livewire** — `Org` é fino: seleção (`?user=`), `prepareMove` → modal de confirmação → `confirmMove`, delegações e observações (criar, encerrar, remover). Não carrega a árvore.
- **Navegador** — `resources/js/hierarchy/org.js` (`hierOrg()`, Alpine) busca `admin.user.hierarchy.nodes` e monta tudo localmente: expandir/recolher, busca por nome/e-mail, filtro por empresa e "Com cobertura", marcar vários, arrastar. Os mapas pesados ficam fora do estado reativo do Alpine.
- **Estilo** — `resources/sass/hierarchy/_org.scss` (só `var(--ops-*)` e tons).

## Regras
- Mover leva a equipe junto. Alvo inválido: a própria pessoa, alguém da equipe dela, ou o gestor atual.
- Arrastar nunca grava: abre a confirmação com "hoje / depois" de cada pessoa. Solte na faixa "sem gestor" para tirar o gestor.
- Quem tem equipe aparece na árvore; raízes sem equipe ficam no grupo "Sem gestor e sem equipe".
- Selos: âmbar "Coberto por", azul "Cobre", cinza observações vigentes e tamanho da equipe (diretos / total).
- Delegação e observação seguem vigentes até `valid_to`; "Encerrar" grava `valid_to = agora - 1s`, "Remover" apaga.

## Conferência
Testes: `tests/Feature/HierarchyOrgTest.php`. Navegador: arrastar, marcar vários, buscar, cobrir, encerrar.
