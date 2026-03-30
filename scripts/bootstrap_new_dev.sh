#!/usr/bin/env bash

set -euo pipefail

PROJECT_NAME="SICODE2"
DEFAULT_REPO_URL="git@github.com:BiGSerial/SICODE2.git"
DEFAULT_BASE_DIR="${HOME}/dev"
DEFAULT_PROJECT_DIR="${DEFAULT_BASE_DIR}/${PROJECT_NAME}"
COMPOSE_FILE_NAME="docker-compose.new-dev.yml"
NGINX_CONF_DIR_REL="docker/dev/nginx"
PHP_DOCKERFILE_DIR_REL="docker/dev/php"
LOCAL_BIN_DIR="${HOME}/.local/bin"
CONFIG_DIR="${HOME}/.config/develop-sicode2"
CONFIG_FILE="${CONFIG_DIR}/config.env"
GIT_ALIASES_FILE="${HOME}/.config/sicode/git-aliases.ini"
GIT_MINI_FLOW_SCRIPT="${HOME}/.local/bin/git-mini-flow"
DEPLOY_SICODE2_SCRIPT="${HOME}/.local/bin/deploy-sicode2"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { printf "%b\n" "${BLUE}[INFO]${NC} $*"; }
success() { printf "%b\n" "${GREEN}[OK]${NC} $*"; }
warn() { printf "%b\n" "${YELLOW}[WARN]${NC} $*"; }
error() { printf "%b\n" "${RED}[ERRO]${NC} $*"; }

show_progress() {
    local current="$1"
    local total="$2"
    local label="$3"
    local width=30
    local percent=$(( current * 100 / total ))
    local filled=$(( current * width / total ))
    local empty=$(( width - filled ))
    local i

    printf "\r["
    for ((i=0; i<filled; i++)); do printf "#"; done
    for ((i=0; i<empty; i++)); do printf "-"; done
    printf "] %3d%% - %s" "$percent" "$label"

    if [[ "$current" -eq "$total" ]]; then
        printf "\n"
    fi
}

require_sudo() {
    if ! command -v sudo >/dev/null 2>&1; then
        error "sudo nao encontrado. Instale sudo ou rode como root."
        exit 1
    fi
}

run_with_progress() {
    local step="$1"
    local index="$2"
    local total="$3"

    show_progress "$index" "$total" "$step"
}

detect_pkg_manager() {
    for mgr in apt dnf yum pacman zypper; do
        if command -v "$mgr" >/dev/null 2>&1; then
            echo "$mgr"
            return 0
        fi
    done
    return 1
}

install_git() {
    if command -v git >/dev/null 2>&1; then
        success "Git ja instalado: $(git --version)"
        return
    fi

    log "Git nao encontrado. Instalando..."
    require_sudo

    local mgr
    if ! mgr="$(detect_pkg_manager)"; then
        error "Nao foi possivel identificar o gerenciador de pacotes."
        exit 1
    fi

    case "$mgr" in
        apt)
            sudo apt update
            sudo apt install -y git
            ;;
        dnf)
            sudo dnf install -y git
            ;;
        yum)
            sudo yum install -y git
            ;;
        pacman)
            sudo pacman -Sy --noconfirm git
            ;;
        zypper)
            sudo zypper --non-interactive install git
            ;;
    esac

    success "Git instalado com sucesso."
}

install_docker() {
    if ! command -v curl >/dev/null 2>&1; then
        log "curl nao encontrado. Instalando..."
        require_sudo
        local mgr
        if ! mgr="$(detect_pkg_manager)"; then
            error "Nao foi possivel identificar o gerenciador de pacotes para instalar curl."
            exit 1
        fi
        case "$mgr" in
            apt)
                sudo apt update
                sudo apt install -y curl
                ;;
            dnf)
                sudo dnf install -y curl
                ;;
            yum)
                sudo yum install -y curl
                ;;
            pacman)
                sudo pacman -Sy --noconfirm curl
                ;;
            zypper)
                sudo zypper --non-interactive install curl
                ;;
        esac
    fi

    if command -v docker >/dev/null 2>&1; then
        success "Docker ja instalado: $(docker --version)"
    else
        log "Docker nao encontrado. Instalando via script oficial..."
        require_sudo
        curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
        sudo sh /tmp/get-docker.sh
        rm -f /tmp/get-docker.sh
        success "Docker instalado com sucesso."
    fi

    if ! groups "$USER" | grep -q '\bdocker\b'; then
        log "Adicionando usuario '$USER' ao grupo docker..."
        sudo usermod -aG docker "$USER"
        warn "Execute 'newgrp docker' ou faca logout/login para aplicar grupo docker."
    fi

    if docker compose version >/dev/null 2>&1; then
        success "Docker Compose plugin encontrado."
        return
    fi

    warn "Docker Compose plugin nao encontrado. Tentando instalar..."
    require_sudo

    local mgr
    if ! mgr="$(detect_pkg_manager)"; then
        error "Nao foi possivel identificar o gerenciador de pacotes para instalar docker-compose-plugin."
        exit 1
    fi

    case "$mgr" in
        apt)
            sudo apt update
            sudo apt install -y docker-compose-plugin
            ;;
        dnf)
            sudo dnf install -y docker-compose-plugin || true
            ;;
        yum)
            sudo yum install -y docker-compose-plugin || true
            ;;
        pacman)
            sudo pacman -Sy --noconfirm docker-compose
            ;;
        zypper)
            sudo zypper --non-interactive install docker-compose
            ;;
    esac

    if docker compose version >/dev/null 2>&1; then
        success "Docker Compose disponivel."
    else
        warn "Docker Compose nao foi validado automaticamente. Verifique manualmente."
    fi
}

ensure_ssh_key() {
    local key_path="${HOME}/.ssh/id_ed25519"
    mkdir -p "${HOME}/.ssh"
    chmod 700 "${HOME}/.ssh"

    if [[ -f "$key_path" ]]; then
        success "Chave ed25519 ja existe em $key_path"
    else
        log "Criando nova chave ed25519 para acesso ao GitHub..."
        ssh-keygen -t ed25519 -C "${USER}@$(hostname)-sicode2" -f "$key_path"
        success "Chave criada com sucesso."
    fi

    if ! ssh-keyscan -t rsa github.com >> "${HOME}/.ssh/known_hosts" 2>/dev/null; then
        warn "Nao foi possivel atualizar known_hosts automaticamente."
    fi

    chmod 600 "${HOME}/.ssh/known_hosts" 2>/dev/null || true

    log "Adicione a chave publica abaixo em GitHub > Settings > SSH and GPG keys:"
    echo ""
    cat "${key_path}.pub"
    echo ""
    read -r -p "Pressione ENTER apos adicionar a chave no GitHub para continuar..." _
}

clone_project_if_needed() {
    mkdir -p "$DEFAULT_BASE_DIR"

    if [[ -d "$DEFAULT_PROJECT_DIR/.git" ]]; then
        success "Repositorio ja existe em: $DEFAULT_PROJECT_DIR"
        return
    fi

    log "Clonando repositorio privado SICODE2..."
    git clone "$DEFAULT_REPO_URL" "$DEFAULT_PROJECT_DIR"
    success "Repositorio clonado em $DEFAULT_PROJECT_DIR"
}

write_nginx_conf() {
    local project_dir="$1"
    mkdir -p "${project_dir}/${NGINX_CONF_DIR_REL}"

    cat > "${project_dir}/${NGINX_CONF_DIR_REL}/default.conf" <<'EOF'
server {
    listen 80;
    server_name localhost;

    root /var/www/html/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_pass app:9000;
    }

    location ~ /\.ht {
        deny all;
    }

    client_max_body_size 50M;
}
EOF

    success "Nginx config criada em ${NGINX_CONF_DIR_REL}/default.conf"
}

write_php_dockerfile() {
    local project_dir="$1"
    mkdir -p "${project_dir}/${PHP_DOCKERFILE_DIR_REL}"

    cat > "${project_dir}/${PHP_DOCKERFILE_DIR_REL}/Dockerfile" <<'EOF'
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libpq-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]
EOF

    success "Dockerfile PHP criado em ${PHP_DOCKERFILE_DIR_REL}/Dockerfile"
}

write_compose_file() {
    local project_dir="$1"

    cat > "${project_dir}/${COMPOSE_FILE_NAME}" <<'EOF'
services:
  app:
    build:
      context: .
      dockerfile: docker/dev/php/Dockerfile
    container_name: sicode2_app
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    depends_on:
      - db
    networks:
      - sicode2

  web:
    image: nginx:1.27-alpine
    container_name: sicode2_web
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
      - ./docker/dev/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - app
    networks:
      - sicode2

  db:
    image: mariadb:11
    container_name: sicode2_db
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: sicode
      MYSQL_USER: sicode
      MYSQL_PASSWORD: sicode
    ports:
      - "3307:3306"
    volumes:
      - sicode2_db_data:/var/lib/mysql
    networks:
      - sicode2

networks:
  sicode2:
    driver: bridge

volumes:
  sicode2_db_data:
EOF

    success "Arquivo ${COMPOSE_FILE_NAME} criado."
}

create_docker_assets() {
    local project_dir="$1"
    write_nginx_conf "$project_dir"
    write_php_dockerfile "$project_dir"
    write_compose_file "$project_dir"
}

start_containers() {
    local project_dir="$1"

    if [[ ! -f "${project_dir}/${COMPOSE_FILE_NAME}" ]]; then
        error "Compose file nao encontrado em ${project_dir}/${COMPOSE_FILE_NAME}"
        exit 1
    fi

    log "Subindo containers..."
    (
        cd "$project_dir"
        docker compose -f "$COMPOSE_FILE_NAME" up -d --build
    )
    success "Containers iniciados (web, app, db)."
}

configure_git_aliases() {
    mkdir -p "$(dirname "$GIT_ALIASES_FILE")"
    mkdir -p "$LOCAL_BIN_DIR"

    cat > "$GIT_MINI_FLOW_SCRIPT" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail

cmd="${1:-help}"
arg1="${2:-}"

ensure_repo() {
  git rev-parse --is-inside-work-tree >/dev/null 2>&1 || {
    echo "Execute dentro de um repositorio git."
    exit 1
  }
}

current_branch() {
  git branch --show-current
}

default_base_branch() {
  if git show-ref --verify --quiet refs/heads/develop; then
    echo "develop"
    return
  fi
  if git show-ref --verify --quiet refs/heads/main; then
    echo "main"
    return
  fi
  if git show-ref --verify --quiet refs/heads/master; then
    echo "master"
    return
  fi
  echo "main"
}

case "$cmd" in
  start)
    ensure_repo
    if [[ -z "$arg1" ]]; then
      echo "Uso: git start <nome-da-branch>"
      exit 1
    fi
    git checkout -b "$arg1"
    ;;
  publish)
    ensure_repo
    b="$(current_branch)"
    git push -u origin "$b"
    ;;
  finish)
    ensure_repo
    b="$(current_branch)"
    msg="${arg1:-chore: finish $b}"
    git add -A
    git commit -m "$msg" || true
    git push -u origin "$b"
    ;;
  abort)
    ensure_repo
    b="$(current_branch)"
    base="$(default_base_branch)"
    if [[ "$b" == "$base" ]]; then
      echo "Nao e permitido abortar a branch base ($base)."
      exit 1
    fi
    git checkout "$base"
    git branch -D "$b"
    ;;
  abort-remote)
    ensure_repo
    b="${arg1:-$(current_branch)}"
    git push origin --delete "$b"
    ;;
  release)
    ensure_repo
    base="$(default_base_branch)"
    git checkout "$base"
    git pull --rebase
    ;;
  cleanup)
    ensure_repo
    git fetch --all --prune
    git branch --merged | egrep -v '(^\*|main|master|develop)' | xargs -r git branch -d
    ;;
  pause)
    ensure_repo
    git add -A
    git stash push -m "pause: $(date +%Y-%m-%d_%H-%M-%S)"
    ;;
  resume)
    ensure_repo
    git stash pop
    ;;
  update|sync)
    ensure_repo
    git fetch --all --prune
    git pull --rebase
    ;;
  help|*)
    cat <<HELP
git-mini-flow comandos:
  start <branch>     Cria e troca para nova branch
  publish            Publica branch atual no origin
  finish [msg]       Commita (se houver mudancas) e publica branch
  abort              Remove branch atual local e volta para base
  abort-remote [b]   Remove branch remota (atual ou informada)
  release            Atualiza branch base (develop/main/master)
  cleanup            Remove branches locais ja mergeadas
  pause              Stash de tudo com timestamp
  resume             Aplica ultimo stash
  update|sync        Fetch + pull --rebase
HELP
    ;;
esac
EOF

    chmod +x "$GIT_MINI_FLOW_SCRIPT"

    cat > "$GIT_ALIASES_FILE" <<'EOF'
[alias]
    s = "status -sb"
    co = "checkout"
    co-new = "checkout -b"
    cm = "commit -m"
    start = "!git-mini-flow start"
    publish = "!git-mini-flow publish"
    finish = "!git-mini-flow finish"
    abort = "!git-mini-flow abort"
    abort-remote = "!git-mini-flow abort-remote"
    release = "!git-mini-flow release"
    cleanup = "!git-mini-flow cleanup"
    helper = "!git-mini-flow help"
    done = "finish"
    pause = "!git-mini-flow pause"
    resume = "!git-mini-flow resume"
    sync = "!git-mini-flow update"
    wip = "!git add -A && git commit -m"
    cq = "!git add -A && git commit -m quick_save"
EOF

    git config --global include.path "$GIT_ALIASES_FILE"
    success "Fluxo git personalizado configurado (aliases + script git-mini-flow)."
}

configure_deploy_sicode2() {
    mkdir -p "$LOCAL_BIN_DIR" "$CONFIG_DIR"

    cat > "$CONFIG_FILE" <<EOF
WINSCP_EXE=/mnt/c/Users/Compet/AppData/Local/Programs/WinSCP/WinSCP.com
SFTP_USER=deploy_sicode
HOST=edpbr1204.edp.pt
REMOTE_QA_DIR=/qa
REMOTE_PROD_DIR=/prod
EOF

    cat > "${DEPLOY_SICODE2_SCRIPT}" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail

CONFIG_FILE="${HOME}/.config/develop-sicode2/config.env"

if [[ ! -f "$CONFIG_FILE" ]]; then
  echo "Config nao encontrada: $CONFIG_FILE"
  exit 1
fi

# shellcheck source=/dev/null
source "$CONFIG_FILE"

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; BLUE='\033[0;34m'; NC='\033[0m'

usage() {
  echo -e "${YELLOW}Uso: $0 <qa|prod> [fast|full] [database]${NC}"
  echo -e "Exemplos:"
  echo -e "  $0 qa fast           (Sincroniza: app, resources, routes + appver.json)"
  echo -e "  $0 qa fast database  (Sincroniza: app, resources, routes, database + appver.json)"
  echo -e "  $0 prod full         (Sincroniza TUDO, exceto .env e pastas pesadas)"
  exit 1
}

[[ "${1:-}" != "qa" && "${1:-}" != "prod" ]] && usage

TARGET="$1"
PROFILE="${2:-fast}"
SYNC_DATABASE=false
for arg in "$@"; do
  [[ "$arg" == "database" ]] && SYNC_DATABASE=true
done

if [[ ! -x "$WINSCP_EXE" ]]; then
  echo -e "${RED}WinSCP.com nao encontrado em:${NC} $WINSCP_EXE"
  echo -e "${YELLOW}Ajuste em:${NC} $CONFIG_FILE"
  exit 1
fi

if ! command -v wslpath >/dev/null 2>&1; then
  echo -e "${RED}wslpath nao encontrado. O script de deploy exige WSL + WinSCP no Windows.${NC}"
  exit 1
fi

REMOTE_DIR=$([[ "$TARGET" == "qa" ]] && echo "$REMOTE_QA_DIR" || echo "$REMOTE_PROD_DIR")
PROJECT_ROOT_WSL=$(git rev-parse --show-toplevel 2>/dev/null || pwd)
PROJECT_ROOT_WIN=$(wslpath -w "$PROJECT_ROOT_WSL")
EXCLUDES="| .git/; .env*; node_modules/; vendor/; storage/; tests/; .idea/; .vscode/; .deploy*"

echo -e "${BLUE}==========================================================${NC}"
echo -e "DEPLOY SICODE2: ${GREEN}${TARGET}${NC} | Perfil: ${YELLOW}${PROFILE}${NC}"
[[ "$SYNC_DATABASE" == "true" ]] && echo -e "Extra: ${YELLOW}database incluido${NC}"
echo -e "${BLUE}==========================================================${NC}"

TMP_SCRIPT=$(mktemp /tmp/winscp_script_XXXX.txt)
cat <<WINSCP > "$TMP_SCRIPT"
option batch continue
option confirm off
option transfer binary
open sftp://${SFTP_USER}@${HOST} -hostkey="*"
cd "${REMOTE_DIR}"
lcd "${PROJECT_ROOT_WIN}"
echo Enviando appver.json...
put -nopreservetime -nopermissions "appver.json"
WINSCP

if [[ "$PROFILE" == "full" ]]; then
  echo "echo Sincronizando TUDO (Modo Full)..." >> "$TMP_SCRIPT"
  echo "synchronize remote -nopreservetime -nopermissions -filemask=\"$EXCLUDES\" . ." >> "$TMP_SCRIPT"
else
  FOLDERS=("app" "resources" "routes")
  [[ "$SYNC_DATABASE" == "true" ]] && FOLDERS+=("database")
  for dir in "${FOLDERS[@]}"; do
    if [[ -d "$PROJECT_ROOT_WSL/$dir" ]]; then
      echo "echo Sincronizando pasta: $dir" >> "$TMP_SCRIPT"
      echo "synchronize remote -nopreservetime -nopermissions -filemask=\"$EXCLUDES\" \"$dir\" \"$dir\"" >> "$TMP_SCRIPT"
    fi
  done
fi

echo "exit" >> "$TMP_SCRIPT"
TMP_SCRIPT_WIN=$(wslpath -w "$TMP_SCRIPT")

echo -e "${YELLOW}Iniciando WinSCP...${NC}"
"$WINSCP_EXE" /script="$TMP_SCRIPT_WIN"
rm -f "$TMP_SCRIPT"
echo -e "${GREEN}Deploy finalizado.${NC}"
EOF

    chmod +x "${DEPLOY_SICODE2_SCRIPT}"

    if [[ ":$PATH:" != *":${LOCAL_BIN_DIR}:"* ]]; then
        warn "Adicione ${LOCAL_BIN_DIR} ao PATH para usar deploy-sicode2 globalmente."
        warn "Exemplo: echo 'export PATH=\"${LOCAL_BIN_DIR}:$PATH\"' >> ~/.bashrc"
    fi

    success "Script deploy-sicode2 criado em ${DEPLOY_SICODE2_SCRIPT}"
    warn "Antes de usar, ajuste os dados em ${CONFIG_FILE} (WinSCP, host, usuario e paths remotos)."
}

configure_deploy_sicode2_interactive() {
    mkdir -p "$CONFIG_DIR"

    [[ -f "$CONFIG_FILE" ]] && source "$CONFIG_FILE"

    local winscp_exe="${WINSCP_EXE:-/mnt/c/Users/Compet/AppData/Local/Programs/WinSCP/WinSCP.com}"
    local sftp_user="${SFTP_USER:-deploy_sicode}"
    local host="${HOST:-edpbr1204.edp.pt}"
    local qa_dir="${REMOTE_QA_DIR:-/qa}"
    local prod_dir="${REMOTE_PROD_DIR:-/prod}"

    read -r -p "WinSCP.com [$winscp_exe]: " input; winscp_exe="${input:-$winscp_exe}"
    read -r -p "Usuario SFTP [$sftp_user]: " input; sftp_user="${input:-$sftp_user}"
    read -r -p "Host SFTP [$host]: " input; host="${input:-$host}"
    read -r -p "Diretorio remoto QA [$qa_dir]: " input; qa_dir="${input:-$qa_dir}"
    read -r -p "Diretorio remoto PROD [$prod_dir]: " input; prod_dir="${input:-$prod_dir}"

    cat > "$CONFIG_FILE" <<EOF
WINSCP_EXE=${winscp_exe}
SFTP_USER=${sftp_user}
HOST=${host}
REMOTE_QA_DIR=${qa_dir}
REMOTE_PROD_DIR=${prod_dir}
EOF

    success "Configuracao de deploy salva em ${CONFIG_FILE}"
}

prompt_winscp() {
    warn "Instale o WinSCP na maquina Windows caso va usar sincronizacao SFTP no fluxo do time."
    warn "Download oficial: https://winscp.net"
}

run_base_setup() {
    local steps=3
    local i=0

    ((i++)); run_with_progress "Verificando/instalando Git" "$i" "$steps"; install_git
    ((i++)); run_with_progress "Verificando/instalando Docker" "$i" "$steps"; install_docker
    ((i++)); run_with_progress "Configurando aliases git personalizados" "$i" "$steps"; configure_git_aliases

    success "Setup base finalizado (sem SSH Git e sem WinSCP)."
    warn "Quando tiver credenciais/configuracoes prontas, execute as opcoes 4, 5, 9 e 10."
}

run_post_config_setup() {
    local steps=4
    local i=0

    ((i++)); run_with_progress "Configurando chave SSH ed25519" "$i" "$steps"; ensure_ssh_key
    ((i++)); run_with_progress "Clonando repositorio SICODE2" "$i" "$steps"; clone_project_if_needed
    ((i++)); run_with_progress "Criando script deploy-sicode2" "$i" "$steps"; configure_deploy_sicode2
    ((i++)); run_with_progress "Configurando dados de deploy (WinSCP/SFTP)" "$i" "$steps"; configure_deploy_sicode2_interactive

    success "Setup complementar finalizado."
}

print_menu() {
    cat <<'EOF'

======= Onboarding SICODE2 (Linux) =======
1) Setup base (sem SSH Git e sem WinSCP) - recomendado
2) Instalar/verificar Git
3) Instalar/verificar Docker
4) Configurar chave SSH ed25519 (quando tiver acesso Git)
5) Clonar repositorio privado SICODE2 (quando tiver acesso Git)
6) Gerar arquivos Docker (Nginx + PHP 8.4 + MariaDB)
7) Subir containers
8) Configurar aliases Git (git start etc.)
9) Criar/atualizar script deploy-sicode2 (SFTP via WinSCP)
10) Configurar dados do deploy-sicode2 (WinSCP/SFTP)
11) Lembrete para instalar WinSCP
12) Setup complementar (SSH Git + clone + deploy)
0) Sair
EOF
}

main() {
    while true; do
        print_menu
        read -r -p "Escolha uma opcao: " option

        case "$option" in
            1) run_base_setup ;;
            2) install_git ;;
            3) install_docker ;;
            4) ensure_ssh_key ;;
            5) clone_project_if_needed ;;
            6) create_docker_assets "$DEFAULT_PROJECT_DIR" ;;
            7) start_containers "$DEFAULT_PROJECT_DIR" ;;
            8) configure_git_aliases ;;
            9) configure_deploy_sicode2 ;;
            10) configure_deploy_sicode2_interactive ;;
            11) prompt_winscp ;;
            12) run_post_config_setup ;;
            0)
                log "Saindo."
                exit 0
                ;;
            *)
                warn "Opcao invalida."
                ;;
        esac
    done
}

main "$@"
