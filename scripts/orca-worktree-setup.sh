#!/usr/bin/env bash
# Sobe a stack Docker efêmera deste worktree (código-fonte = diretório atual).
# Não toca no ambiente da develop (projeto compose "sicode2").
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
ENV_ORCA="$ROOT/.env.orca"
COMPOSE=(docker compose -f docker-compose.orca.yml --env-file "$ENV_ORCA")

used_ports() {
    { ss -ltnH 2>/dev/null | awk '{print $4}' | sed 's/.*://'
      docker ps --format '{{.Ports}}' | grep -oE ':[0-9]+->' | tr -d ':>-'; } | sort -u
}

if [ ! -f "$ENV_ORCA" ]; then
    slug="$(basename "$ROOT" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9]/-/g' | cut -c1-30)"
    hash="$(printf '%s' "$ROOT" | sha1sum | cut -c1-6)"
    used="$(used_ports)"
    port=8100
    while grep -qx "$port" <<<"$used"; do port=$((port + 1)); done
    cat > "$ENV_ORCA" <<EOT
COMPOSE_PROJECT_NAME=orca-${slug}-${hash}
ORCA_HTTP_PORT=${port}
DOCKER_UID=$(id -u)
DOCKER_GID=$(id -g)
EOT
    echo "Criado $ENV_ORCA (porta $port)"
fi

# shellcheck disable=SC1090
set -a; source "$ENV_ORCA"; set +a

# .env da aplicação: reaproveita credenciais de DB do .env de desenvolvimento, se existir.
if [ ! -f .env ]; then
    SRC="${ORCA_ENV_SOURCE:-$HOME/code/SICODE2/.env}"
    if [ -f "$SRC" ]; then cp "$SRC" .env; else cp .env.example .env; fi
fi
# APP_URL e ASSET_URL sempre com o endereço desta stack (senão os assets vêm de outra porta).
for var in APP_URL ASSET_URL; do
    if grep -q "^${var}=" .env; then
        sed -i "s#^${var}=.*#${var}=http://localhost:${ORCA_HTTP_PORT}#" .env
    else
        printf '\n%s=http://localhost:%s\n' "$var" "$ORCA_HTTP_PORT" >> .env
    fi
done

mkdir -p storage/framework/{views,cache,sessions} storage/logs bootstrap/cache

"${COMPOSE[@]}" up -d --build

COMPOSER_RUN=("${COMPOSE[@]}" exec -T -e COMPOSER_HOME=/tmp/composer app composer --no-interaction --ignore-platform-reqs)

if [ ! -d vendor ]; then
    # O lock ainda não declara PHP 8.5 (imagem atual), por isso ignora os requisitos de plataforma.
    "${COMPOSER_RUN[@]}" install --prefer-dist --no-scripts
fi

# config/octane.php é versionado, mas laravel/octane não está no composer.json/lock (no develop foi
# instalado à mão). Sem ele o package:discover quebra. Instala só no vendor e restaura os arquivos versionados.
if [ -f config/octane.php ] && [ ! -d vendor/laravel/octane ]; then
    cp composer.json "/tmp/orca-composer.json.$$"; cp composer.lock "/tmp/orca-composer.lock.$$"
    "${COMPOSER_RUN[@]}" require laravel/octane --no-scripts || true
    cp "/tmp/orca-composer.json.$$" composer.json; cp "/tmp/orca-composer.lock.$$" composer.lock
    rm -f "/tmp/orca-composer.json.$$" "/tmp/orca-composer.lock.$$"
fi
"${COMPOSER_RUN[@]}" dump-autoload --optimize >/dev/null 2>&1 || true
"${COMPOSE[@]}" exec -T app php artisan package:discover --ansi

grep -q '^APP_KEY=.\+' .env || "${COMPOSE[@]}" exec -T app php artisan key:generate --force
"${COMPOSE[@]}" exec -T app php artisan view:clear >/dev/null

# Build a cada setup: VITE_APP_URL vem do APP_URL e é embutido no bundle.
if [ "${ORCA_BUILD_ASSETS:-1}" = "1" ]; then
    "${COMPOSE[@]}" exec -T -e npm_config_cache=/tmp/npm-cache app sh -c \
        '[ -x node_modules/.bin/vite ] || npm ci --no-audit --no-fund; npm run build' \
        || echo "Aviso: build de assets falhou (segue sem Vite)."
fi
"${COMPOSE[@]}" exec -T app php artisan config:clear >/dev/null

echo
"${COMPOSE[@]}" ps
echo "Projeto: $COMPOSE_PROJECT_NAME"
echo "URL: http://localhost:${ORCA_HTTP_PORT}"
