#!/usr/bin/env bash
# deploy.sh — Pull & build automático na VPS
# Uso: ./deploy.sh [--seed]
set -euo pipefail

# ─── Configuração ─────────────────────────────────────────────────────────────
APP_DIR="/var/www/loja"
BACKEND_DIR="$APP_DIR/backend"
FRONTEND_DIR="$APP_DIR/frontend"
BRANCH="main"
SEED=false

for arg in "$@"; do
  case $arg in
    --seed) SEED=true ;;
    *) echo "Uso: $0 [--seed]"; exit 1 ;;
  esac
done

# ─── Helpers ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
log()  { echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"; }
ok()   { echo -e "${GREEN}[$(date '+%H:%M:%S')] ✓${NC} $1"; }
warn() { echo -e "${YELLOW}[$(date '+%H:%M:%S')] ⚠${NC} $1"; }
die()  { echo -e "${RED}[$(date '+%H:%M:%S')] ✗ ERRO:${NC} $1"; exit 1; }

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  Deploy — Store SaaS${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# ─── 1. Git pull ──────────────────────────────────────────────────────────────
log "Atualizando código (branch: $BRANCH)..."
cd "$APP_DIR"
git pull origin "$BRANCH" || die "git pull falhou"
ok "Código atualizado"

# ─── 2. Backend ───────────────────────────────────────────────────────────────
# O código PHP é montado via volume — não precisa rebuild a cada deploy.
# O docker compose up -d garante que os containers estejam rodando.
log "Garantindo containers backend..."
cd "$BACKEND_DIR"
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d \
  || die "docker compose (backend) falhou"

# Aguarda o PHP-FPM aceitar comandos
log "Aguardando container app..."
RETRIES=30
until docker compose exec -T app php -r "echo 'ok';" 2>/dev/null | grep -q ok; do
  RETRIES=$((RETRIES - 1))
  [ $RETRIES -le 0 ] && die "Container app não respondeu após 60 s"
  sleep 2
done
ok "Container app pronto"

# Dependências PHP em produção
log "Garantindo diretórios e permissões de storage..."
docker compose exec -u root -T app sh -c \
  "mkdir -p /var/www/vendor \
            /var/www/storage/logs \
            /var/www/storage/app/public \
            /var/www/storage/framework/views \
            /var/www/storage/framework/cache/data \
            /var/www/storage/framework/sessions \
            /var/www/bootstrap/cache \
   && chown -R appuser:appuser \
            /var/www/vendor \
            /var/www/storage \
            /var/www/bootstrap/cache \
   && chmod -R 775 /var/www/storage /var/www/bootstrap/cache"

log "Atualizando dependências PHP..."
docker compose exec -T app composer install \
  --no-dev --optimize-autoloader --no-interaction \
  || die "composer install falhou"
ok "Composer atualizado"

log "Criando/verificando storage symlink..."
docker compose exec -u root -T app php artisan storage:link --force 2>/dev/null \
  || warn "storage:link falhou (pode já existir)"

# Migrations
log "Executando migrations..."
docker compose exec -T app php artisan migrate --force \
  || die "php artisan migrate falhou"
ok "Migrations executadas"

# Caches do Laravel
log "Reconstruindo caches Laravel..."
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
ok "Caches reconstruídos"

# Horizon — sinaliza para reiniciar com novo código
log "Reiniciando Horizon..."
docker compose exec -T app php artisan horizon:terminate \
  || warn "horizon:terminate falhou (processo pode já estar parado — horizon reiniciará sozinho)"
ok "Horizon sinalizado"

# Seeders de dados de construção (somente com --seed)
if [ "$SEED" = true ]; then
  log "Executando seeders de construção..."
  docker compose exec -T app php artisan db:seed --class=ConstructionCategorySeeder --force
  docker compose exec -T app php artisan db:seed --class=ConstructionAttributeSeeder --force
  docker compose exec -T app php artisan db:seed --class=ConstructionGridSeeder --force
  ok "Seeders executados"
fi

# ─── 3. Frontend ──────────────────────────────────────────────────────────────
# O código Next.js é copiado na imagem durante o build (COPY . .) — rebuild obrigatório.
log "Verificando .env do frontend..."
[ -f "$FRONTEND_DIR/.env" ] \
  || die "frontend/.env não encontrado — crie o arquivo com NEXT_PUBLIC_API_URL"

log "Reconstruindo container frontend..."
cd "$FRONTEND_DIR"
# Força remoção do container (independente do estado: running, created, exited…)
docker rm -f store_admin 2>/dev/null || true
docker compose up -d --build \
  || die "docker compose (frontend) falhou"
ok "Container frontend reconstruído"

# ─── 4. Resultado ─────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}  Deploy concluído!$([ "$SEED" = true ] && echo " (seeders executados)")${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "Backend:"
cd "$BACKEND_DIR"
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps --format "table {{.Name}}\t{{.Status}}"
echo ""
echo "Frontend:"
cd "$FRONTEND_DIR"
docker compose ps --format "table {{.Name}}\t{{.Status}}"
