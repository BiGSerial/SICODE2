# Menu da Administração

Implementa o [padrão obrigatório de menu lateral](../PADRAO_MENU_LATERAL.md) para a Administração. Definição única: `App\Support\Menu\AdminNav`. O menu lateral (`admin/**/menu`, `config/*menu` e `admin/menu`, todos só uma chamada a `<x-nav.sidebar>`) e o dropdown ADMINISTRAÇÃO do topo (`AdminNav::topNodes()`) leem dela.

## Organização por processo

| Módulo | Grupo | Itens | Permissão |
|---|---|---|---|
| **Cadastros** (pessoas e empresas) | Usuários | Usuários · Hierarquia de usuários | admin |
| | Empresas | Empresas · Contratos | superadm |
| | Catálogos | Categorias | superadm |
| **Dados** (controle e auditoria) | Controle de dados | Notas D5 · Viabilidade · Notas · Informe de obra · Informe parcial | superadm |
| | Auditoria | Auditoria de notas | admin |
| **Configurações** (como o sistema se comporta) | Atividades e painéis | Configurar serviços · Wall de produção · Abrir o Wall (V2) (outra aba) | admin / superadm |
| | Informes e análises | Termo de aceite do informe · Avisos legais do painel do parceiro · Encerramento de análise | superadm |
| | Integrações | Destinatários das requisições ADS · Monitor de requisições ADS · Tokens de API | admin / superadm |
| **Sistema** (saúde e operação) | Monitoramento | Status do servidor · Monitor de produção · Histórico de execuções · Jobs · Scheduler · Saúde do SQL Server | admin / management / superadm |
| | Arquivos | Gerenciamento de arquivos | admin |

A permissão de cada item segue a da rota (`admin`, `management` ou `superadm`); quem não tem acesso não vê o item, e grupo ou módulo vazio some.

## O que mudou em relação aos menus antigos

- Antes havia 9 menus laterais independentes (um por pasta de tela) mais um dropdown do topo escrito à mão, com listas diferentes. Agora é uma única definição.
- Telas `config.*` e `admin.*` deixaram de ter menus separados: Configurações e Sistema reúnem o que estava em `config/menu`, `settings-menu` e `system-menu`.
- O menu do Wall deixou de ser exceção: "Abrir o Wall (V2)" é um item com `target` `_blank`.
- A tela de Categorias passou a ter o menu (o include estava comentado).

## Pendências

- `files.main` (Gerenciamento de arquivos) e `monitor.services` (Monitor de produção) usam layout próprio e ainda não incluem o menu lateral; o item aparece nos demais menus da Administração e no topo.
