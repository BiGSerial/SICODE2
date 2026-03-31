# Onboarding Linux - SICODE2

Script de setup automatizado para novos devs:

- Verifica/instala Git.
- Verifica/instala Docker + Compose.
- Configura aliases Git (incluindo `git start` e fluxo `git-mini-flow`).
- Gera ambiente Docker com containers separados:
  - `web` (Nginx)
  - `app` (Laravel em PHP 8.4 FPM)
  - `db` (MariaDB)
- Gera script `deploy-sicode2` para deploy SFTP via WinSCP (WSL + Windows).

## Como executar

No repositorio:

```bash
chmod +x scripts/bootstrap_new_dev.sh
./scripts/bootstrap_new_dev.sh
```

## Fluxo recomendado

1. Execute a opcao `1` (setup base), que nao depende de chave Git nem WinSCP.
2. Quando tiver chave SSH do GitHub configurada, execute as opcoes `4` e `5`.
3. Quando WinSCP estiver instalado/configurado no Windows, execute as opcoes `9` e `10`.

## Menu resumido

- `1`: setup base sem SSH Git e sem WinSCP.
- `4` e `5`: etapas que dependem de acesso ao repo privado.
- `9`: cria `deploy-sicode2` em `~/.local/bin/deploy-sicode2`.
- `10`: configura `~/.config/develop-sicode2/config.env` (WinSCP/SFTP).
- `12`: roda setup complementar (SSH + clone + deploy).

## Deploy por SFTP

Depois de configurar as opcoes `9` e `10`, rode dentro do projeto:

```bash
deploy-sicode2 qa fast
deploy-sicode2 qa fast database
deploy-sicode2 prod full
```

## Artefatos gerados

- `~/dev/SICODE2`
- `~/dev/SICODE2/docker-compose.new-dev.yml`
- `~/dev/SICODE2/docker/dev/nginx/default.conf`
- `~/dev/SICODE2/docker/dev/php/Dockerfile`
- `~/.config/sicode/git-aliases.ini`
- `~/.local/bin/git-mini-flow`
- `~/.local/bin/deploy-sicode2`
- `~/.config/develop-sicode2/config.env`

## URL local

Após subir containers: `http://localhost:8080`
