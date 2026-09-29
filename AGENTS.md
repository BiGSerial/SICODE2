# AGENTS.md

Instruções para agentes que não leem o `CLAUDE.md` (Codex, OpenCode, Cursor, Gemini CLI). O guia completo do projeto está em `CLAUDE.md`.

## Comandos e arquitetura
Veja `CLAUDE.md` (stack Laravel 10 + Livewire 2, testes com Pest, formatação com `./vendor/bin/pint --dirty`).

## Testar em worktree do Orca (Docker efêmero)
O container `sicode2-app`/`sicode2-nginx` (porta 8000) monta o checkout da **develop** (`/home/will/code/SICODE2`), não o seu worktree. Para validar código de um worktree, suba a stack própria dele — nunca use `:8000` nem `docker exec sicode2-app` para isso.
```bash
scripts/orca-worktree-setup.sh      # sobe a stack, imprime a URL (localhost:81xx); 1ª vez leva alguns minutos
scripts/orca-worktree-teardown.sh   # derruba só este worktree (--purge apaga o .env.orca)
```
- Comandos dentro da stack: `DC="docker compose -f docker-compose.orca.yml --env-file .env.orca"`, depois `$DC exec -T app php artisan ...` e `$DC exec -T -e HOME=/tmp app php artisan tinker` (o `HOME=/tmp` é necessário).
- Browser do Orca: `orca-ide tab create --url http://localhost:<porta>`. O login de `localhost` é compartilhado entre portas.
- O setup grava `APP_URL`/`ASSET_URL` com a porta da stack e roda `npm run build` a cada execução (`ORCA_BUILD_ASSETS=0` pula). O banco é o MariaDB compartilhado do host: migrations não rodam sozinhas.
- Ao terminar a validação, rode o teardown.
