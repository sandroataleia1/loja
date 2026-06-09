# Deploy na VPS — ERP SaaS Moda (backend Laravel + admin Next.js)

> Guia passo a passo para subir o **backend** (API Laravel, em `backend/`) e o
> **admin** (Next.js, em `apps/admin/`) numa VPS Linux (Ubuntu 22.04+).
> O **PDV** (Tauri) é desktop e **não** vai para a VPS.

Stack: PHP 8.4-FPM · PostgreSQL 16 · Redis 7 · Nginx · Horizon (filas) — tudo via
Docker Compose.

---

## ⚠️ Importante: o compose do repositório é de DESENVOLVIMENTO

O `backend/docker-compose.yml` atual é feito para dev:
- expõe **PostgreSQL (5432)** e **Redis (6379)** no host (risco se a VPS for pública);
- inclui **Mailpit** (SMTP de teste);
- `APP_ENV` cai para `local` por padrão.

Este guia cria um **override de produção** (`docker-compose.prod.yml`) que corrige
isso, sem alterar o arquivo de dev. Use sempre os dois arquivos juntos:
`docker compose -f docker-compose.yml -f docker-compose.prod.yml ...`

---

## 0. Pré-requisitos na VPS

- VPS Ubuntu 22.04/24.04, 2 vCPU / 4 GB RAM mínimo (recomendado para Postgres+Redis+PHP).
- Um **domínio** apontando para o IP da VPS (ex.: `api.sualoja.com.br` e `admin.sualoja.com.br`).
- Acesso `sudo`.

### 0.1 Atualizar e criar usuário de deploy
```bash
sudo apt update && sudo apt upgrade -y
sudo adduser deploy
sudo usermod -aG sudo deploy
# copie sua chave SSH para o usuário deploy e desabilite login por senha depois
```

### 0.2 Instalar Docker + Compose plugin
```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker deploy
# reabra a sessão SSH para o grupo docker valer
docker --version && docker compose version
```

### 0.3 Firewall (UFW)
```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```
> NÃO abra 5432/6379 publicamente. O override de produção já tira essas portas do host.

---

## 1. Clonar o repositório

```bash
sudo mkdir -p /opt/store && sudo chown deploy:deploy /opt/store
cd /opt/store
git clone <URL_DO_SEU_GITHUB> .
# se o repo for privado, use deploy key ou token:
#   git clone https://<token>@github.com/<org>/<repo>.git .
```

Estrutura relevante:
```
/opt/store
├── backend/        ← API Laravel (deploy via Docker)
├── apps/admin/     ← painel Next.js
└── docs/
```

---

## 2. Backend — configuração de produção

### 2.1 Override de produção (já incluído no repositório)
O arquivo **`backend/docker-compose.prod.yml`** já existe no repo. Ele: remove a
exposição pública de Postgres/Redis (`ports: !reset []`), desativa o Mailpit,
publica o Nginx só em `127.0.0.1:8000` (um proxy/SSL na frente publica em 443),
põe senha no Redis e adiciona um **scheduler** (cron do Laravel).

> Requer Docker Compose **v2.24+** (pelas tags `!reset`) — o `get.docker.com` do
> passo 0.2 já instala uma versão recente. Confira com `docker compose version`.

Nada a criar aqui — siga para o `.env` (2.2).

### 2.2 Criar o `.env` de produção
```bash
cd /opt/store/backend
cp .env.example .env
```
Edite `.env` com valores de produção (mínimos a trocar):
```dotenv
APP_NAME="Sua Loja"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.sualoja.com.br
APP_KEY=                       # gerado no passo 2.4

# Banco — use senha FORTE
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=store
DB_USERNAME=store
DB_PASSWORD=<senha-forte-do-banco>

# Redis — defina senha (o override exige)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=<senha-forte-do-redis>
REDIS_CLIENT=phpredis

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

LOG_LEVEL=warning

# Cookies/CORS para o admin (Sanctum)
SANCTUM_STATEFUL_DOMAINS=admin.sualoja.com.br
SESSION_DOMAIN=.sualoja.com.br

# E-mail real (ex.: SES, Mailgun, SMTP do provedor)
MAIL_MAILER=smtp
MAIL_HOST=<smtp-host>
MAIL_PORT=587
MAIL_USERNAME=<smtp-user>
MAIL_PASSWORD=<smtp-pass>
MAIL_FROM_ADDRESS="noreply@sualoja.com.br"
MAIL_FROM_NAME="Sua Loja"

# Fiscal (stub até integrar provedor real)
FISCAL_STUB_MODE=authorized
```
> O `APP_PORT`, `DB_PASSWORD`, `REDIS_PASSWORD` também são lidos pelo compose —
> manter no `.env` é suficiente (Compose lê o `.env` da pasta `backend/`).

### 2.3 Subir os containers (build + up)
```bash
cd /opt/store/backend
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
```

> Dica: crie um alias para encurtar:
> `alias dcp='docker compose -f docker-compose.yml -f docker-compose.prod.yml'`

### 2.4 Instalar dependências e inicializar a aplicação
Rode **dentro** do container `store_app`:
```bash
cd /opt/store/backend
dcp exec app composer install --no-dev --optimize-autoloader
dcp exec app php artisan key:generate --force
dcp exec app php artisan migrate --force
dcp exec app php artisan db:seed --class=Database\\Seeders\\RbacSeeder --force   # permissões/roles de sistema
dcp exec app php artisan storage:link
dcp exec app php artisan config:cache
dcp exec app php artisan route:cache
dcp exec app php artisan view:cache
```
> **Não** rode `db:seed` completo (DatabaseSeeder) em produção — ele cria um tenant
> demo. Rode só o `RbacSeeder` (permissões/roles), que é obrigatório.

### 2.5 Permissões de storage
```bash
dcp exec app chown -R www-data:www-data storage bootstrap/cache
```

### 2.6 Verificar saúde
```bash
dcp ps                      # todos "Up"/"healthy"
dcp exec app php artisan about
curl -I http://127.0.0.1:8000   # deve responder (200/302)
```

---

## 3. Proxy reverso + HTTPS (Caddy — mais simples)

O Nginx interno escuta só em `127.0.0.1:8000`. Coloque um proxy com TLS
automático na frente. **Caddy** é o caminho mais curto:

```bash
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update && sudo apt install -y caddy
```

`/etc/caddy/Caddyfile`:
```caddy
api.sualoja.com.br {
    reverse_proxy 127.0.0.1:8000
}

admin.sualoja.com.br {
    reverse_proxy 127.0.0.1:3000
}
```
```bash
sudo systemctl reload caddy
```
> Caddy obtém e renova o certificado Let's Encrypt automaticamente. (Alternativa:
> Nginx do host + certbot, se preferir.)

---

## 4. Admin (Next.js)

> **Estado do build (validado na Fase 13):** a imagem do admin foi buildada e
> testada (Next 15 sobe e responde). Correções aplicadas para destravar o build:
> criado o componente faltante `src/components/ui/table.tsx` e removidas
> definições de tipo duplicadas (`SaleStatus`/`PaymentMethod`) em
> `packages/shared-types`. O `next.config.ts` está com `ignoreBuildErrors`/
> `ignoreDuringBuilds` (dívida técnica: o type layer do frontend nunca foi
> type-checado — só `next dev`; ver pendências). Imports de módulo inexistente
> ainda falham o build (continuam sendo pegos).

O painel é uma app Next.js separada. Duas opções:

### Opção A — rodar com Node + PM2 na VPS
```bash
# instalar Node 22 + pnpm
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
sudo npm i -g pnpm pm2

cd /opt/store
pnpm install --frozen-lockfile
# configurar a URL da API (inclui o prefixo /api/v1 — confirmado em src/config/env.ts):
cat > apps/admin/.env.production <<'EOF'
NEXT_PUBLIC_API_URL=https://api.sualoja.com.br/api/v1
NEXT_PUBLIC_APP_NAME=Sua Loja Admin
EOF
pnpm --filter admin build
cd apps/admin
pm2 start "pnpm start" --name store-admin   # sobe na porta 3000
pm2 save && pm2 startup
```

### Opção B — containerizar o admin (já incluído no repositório) ✅ recomendado
Arquivos prontos: **`apps/admin/Dockerfile`** (multi-stage, contexto = raiz do
monorepo) e **`docker-compose.admin.yml`** (raiz). Publica em `127.0.0.1:3000`.

```bash
cd /opt/store
# NEXT_PUBLIC_* é embutido no BUILD → defina ANTES de buildar (num .env na raiz):
cat > .env <<'EOF'
NEXT_PUBLIC_API_URL=https://api.sualoja.com.br/api/v1
NEXT_PUBLIC_APP_NAME=Sua Loja Admin
EOF

docker compose -f docker-compose.admin.yml up -d --build
docker compose -f docker-compose.admin.yml ps   # store_admin "Up" em 127.0.0.1:3000
```
> Ao trocar a URL da API depois, rode novamente com `--build` (o valor é
> compilado no bundle). O Caddy (seção 3) já aponta `admin.sualoja.com.br` →
> `127.0.0.1:3000`, então serve tanto para a Opção A quanto para a B.

> Variáveis do admin (confirmadas em `apps/admin/src/config/env.ts`):
> `NEXT_PUBLIC_API_URL` (com `/api/v1`) e `NEXT_PUBLIC_APP_NAME`.

---

## 5. Pós-deploy / operação

### 5.1 Atualizar (deploy de nova versão)
```bash
cd /opt/store
git pull origin main
cd backend
dcp up -d --build
dcp exec app composer install --no-dev --optimize-autoloader
dcp exec app php artisan migrate --force
dcp exec app php artisan config:cache && dcp exec app php artisan route:cache && dcp exec app php artisan view:cache
dcp exec app php artisan horizon:terminate    # Horizon reinicia com código novo
# admin (Opção A - PM2):
cd /opt/store && pnpm install --frozen-lockfile && pnpm --filter @store/admin build && pm2 restart store-admin
# admin (Opção B - container):
cd /opt/store && docker compose -f docker-compose.admin.yml up -d --build
```

### 5.2 Filas (Horizon)
Já roda no container `store_horizon`. Painel em `…/horizon` (proteja por auth/policy).
```bash
dcp logs -f horizon
```

### 5.3 Agendador (scheduler)
O container `store_scheduler` (override) roda `schedule:run` a cada minuto —
cobre snapshots de analytics, condicionais vencidos e alertas de certificado.

### 5.4 Backup do banco (diário via cron do host)
```bash
# /etc/cron.d/store-backup
0 3 * * * deploy docker exec store_pgsql pg_dump -U store store | gzip > /opt/store/backups/store_$(date +\%F).sql.gz
```
Crie a pasta: `mkdir -p /opt/store/backups`. Considere enviar os dumps para
storage externo (S3) e testar a restauração periodicamente.

### 5.5 Logs
```bash
dcp logs -f app          # PHP-FPM/app
dcp logs -f nginx        # acesso/erros HTTP
dcp exec app tail -f storage/logs/laravel.log
```

---

## 6. Checklist de segurança (antes de abrir ao público)

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` gerado.
- [ ] `DB_PASSWORD` e `REDIS_PASSWORD` fortes; portas 5432/6379 **não** expostas (override aplicado).
- [ ] HTTPS ativo (Caddy/Let's Encrypt) nos dois domínios.
- [ ] `SANCTUM_STATEFUL_DOMAINS` e `SESSION_DOMAIN` corretos para o admin.
- [ ] `RbacSeeder` rodado; `DatabaseSeeder` (demo) **não** rodado em prod.
- [ ] Firewall só com 22/80/443; login SSH por chave (senha desabilitada).
- [ ] Backups agendados e restauração testada.
- [ ] Horizon e rotas internas (`/horizon`, `/telescope` se houver) protegidos.

---

## 7. Pendências conhecidas (ver `docs/audits/fase-13-pendencias.md`)

- **GD sem JPEG** no container: produção não processa imagem, mas se for gerar
  thumbnails no futuro, recompilar o GD com `libjpeg` no `docker/php/Dockerfile`.
- Decisão de produto `*.view_cost` (ocultar margem por papel) — opcional.
- Load test com volume antes de escala.

---

## Quer que eu suba para você?

Recomendo **não** compartilhar senhas de GitHub/VPS comigo (ficariam expostas no
histórico e um deploy de produção é difícil de reverter). O caminho seguro:

1. Você configura o acesso SSH e roda os comandos; **eu te acompanho em tempo
   real**, leio as saídas e corrijo erros.
2. Ou você cola aqui a saída de cada passo e eu indico o próximo.

Se preferir a Opção B do admin (containerizar) ou um `docker-compose.prod.yml`
pronto no repositório, eu gero os arquivos agora.
