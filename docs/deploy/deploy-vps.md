# Deploy na VPS — Store SaaS

Guia passo a passo para subir o **backend** (Laravel) e o **admin** (Next.js)
em uma VPS Linux. O PDV (Tauri) é desktop e **não** vai para a VPS.

---

## Visão Geral

```
VPS
├── /var/www/loja/backend/   → API Laravel   → porta interna 8000
├── /var/www/loja/           → Admin Next.js → porta interna 3000
└── Caddy (proxy + HTTPS)    → expõe 443 para fora
```

Tudo sobe via **Docker Compose**. O Caddy cuida do TLS automaticamente.

---

## Pré-requisitos

| Item | Mínimo recomendado |
|------|-------------------|
| VPS | Ubuntu 22.04 ou 24.04 |
| CPU / RAM | 2 vCPU / 4 GB |
| Domínios | `api.sualoja.com.br` e `admin.sualoja.com.br` apontando para o IP da VPS |
| Acesso | `sudo` |

---

## Etapa 1 — Preparar o servidor

### 1.1 Atualizar o sistema
```bash
sudo apt update && sudo apt upgrade -y
```

### 1.2 Instalar Docker
```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
# Abra uma nova sessão SSH para o grupo docker valer
docker --version
docker compose version   # precisa ser v2.24+
```

### 1.3 Firewall
```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

> Não abra as portas 5432 (PostgreSQL) nem 6379 (Redis). O compose de produção
> já as mantém fechadas para o host.

---

## Etapa 2 — Clonar o repositório

```bash
sudo mkdir -p /var/www/loja
sudo chown $USER:$USER /var/www/loja
cd /var/www/loja
git clone https://github.com/sandroataleia1/loja.git .
```

Estrutura após o clone:
```
/var/www/loja/
├── backend/              ← API Laravel
├── apps/admin/           ← Painel Next.js
├── packages/             ← tipos e contratos compartilhados
├── docker-compose.admin.yml
└── docs/
```

---

## Etapa 3 — Configurar o Backend

### 3.1 Criar o arquivo .env
```bash
cd /var/www/loja/backend
cp .env.example .env
nano .env
```

Valores **obrigatórios** para alterar:

```dotenv
APP_NAME="Sua Loja"
APP_URL=https://api.sualoja.com.br
APP_FRONTEND_URL=https://admin.sualoja.com.br

# Banco de dados
DB_PASSWORD=troque_por_uma_senha_forte

# Redis
REDIS_PASSWORD=troque_por_uma_senha_forte

# E-mail real (substitua o mailpit)
MAIL_HOST=smtp.seuprovedor.com.br
MAIL_PORT=587
MAIL_USERNAME=seu@email.com.br
MAIL_PASSWORD=sua_senha_smtp
MAIL_FROM_ADDRESS="noreply@sualoja.com.br"

# Domínios do admin (sem https://)
SANCTUM_STATEFUL_DOMAINS=admin.sualoja.com.br
CORS_ALLOWED_ORIGINS=https://admin.sualoja.com.br
```

> Os demais valores (`APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`,
> `SESSION_ENCRYPT=true`) já vêm corretos no `.env.example`.

### 3.2 Subir os containers
```bash
cd /var/www/loja/backend
sudo docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

O primeiro `up --build` demora alguns minutos (baixa imagens e compila).
Quando terminar, confirme que está tudo rodando:

```bash
sudo docker ps
```

Você deve ver os containers:

| Nome | Função |
|------|--------|
| `store_app` | PHP 8.4-FPM (API Laravel) |
| `store_nginx` | Nginx (proxy interno :8000) |
| `store_pgsql` | PostgreSQL 16 |
| `store_redis` | Redis 7 |
| `store_horizon` | Filas (Laravel Horizon) |
| `store_scheduler` | Agendador (cron do Laravel) |
| `store_backup` | Backup diário do banco às 03h |

### 3.3 Inicializar a aplicação

Execute os comandos abaixo **um por vez** e aguarde cada um terminar:

```bash
# Instalar dependências PHP (sem dev)
sudo docker exec store_app composer install --no-dev --optimize-autoloader

# Gerar a chave de criptografia da aplicação
sudo docker exec store_app php artisan key:generate --force

# Criar as tabelas no banco
sudo docker exec store_app php artisan migrate --force

# Popular permissões e perfis de acesso (obrigatório)
sudo docker exec store_app php artisan db:seed --class="Database\Seeders\RbacSeeder" --force

# Criar link simbólico do storage
sudo docker exec store_app php artisan storage:link

# Otimizar (cache de config, rotas e views)
sudo docker exec store_app php artisan optimize
```

> **Importante:** não rode `db:seed` sem `--class`. O `DatabaseSeeder` completo
> cria um tenant de demonstração — use apenas o `RbacSeeder` em produção.

### 3.4 Verificar se a API responde
```bash
curl -I http://127.0.0.1:8080/up
# Esperado: HTTP/1.1 200 OK
```

---

## Etapa 4 — Configurar o Admin (Next.js)

### 4.1 Criar o arquivo .env do admin
```bash
cd /var/www/loja
cat > .env << 'EOF'
NEXT_PUBLIC_API_URL=https://api.sualoja.com.br/api/v1
NEXT_PUBLIC_APP_NAME=Sua Loja Admin
EOF
```

> `NEXT_PUBLIC_API_URL` é embutido no bundle durante o build. Se mudar o domínio
> da API depois, precisará fazer rebuild (`--build`).

### 4.2 Subir o container do admin
```bash
cd /var/www/loja
sudo docker compose -f docker-compose.admin.yml up -d --build
```

Verificar:
```bash
sudo docker ps | grep store_admin
curl -I http://127.0.0.1:3000
# Esperado: HTTP/1.1 200 OK
```

---

## Etapa 5 — Proxy Reverso com HTTPS (Caddy)

O Caddy instala certificados Let's Encrypt automaticamente.

### 5.1 Instalar o Caddy
```bash
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' \
  | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' \
  | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update && sudo apt install -y caddy
```

### 5.2 Configurar o Caddyfile
```bash
sudo nano /etc/caddy/Caddyfile
```

Conteúdo:
```caddy
api.sualoja.com.br {
    reverse_proxy 127.0.0.1:8080
}

admin.sualoja.com.br {
    reverse_proxy 127.0.0.1:3000
}
```

```bash
sudo systemctl reload caddy
```

Teste nos dois domínios:
```bash
curl -I https://api.sualoja.com.br/up
curl -I https://admin.sualoja.com.br
```

---

## Etapa 6 — Criar o Primeiro Usuário

Com tudo rodando, registre o primeiro tenant via API:

```bash
curl -s -X POST https://api.sualoja.com.br/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_name": "Minha Loja",
    "name": "Seu Nome",
    "email": "voce@sualoja.com.br",
    "password": "senha_segura",
    "password_confirmation": "senha_segura"
  }' | jq .
```

Guarde o `token` retornado — é com ele que você acessa o painel admin.

---

## Operação no Dia a Dia

### Atualizar para nova versão
```bash
cd /var/www/loja
git pull origin main

# Backend
cd backend
sudo docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
sudo docker exec store_app composer install --no-dev --optimize-autoloader
sudo docker exec store_app php artisan migrate --force
sudo docker exec store_app php artisan optimize
sudo docker exec store_app php artisan horizon:terminate  # reinicia com código novo

# Admin
cd /var/www/loja
sudo docker compose -f docker-compose.admin.yml up -d --build
```

### Ver logs
```bash
# App Laravel
sudo docker logs store_app -f

# Nginx (acessos e erros HTTP)
sudo docker logs store_nginx -f

# Filas (Horizon)
sudo docker logs store_horizon -f

# Log do Laravel dentro do container
sudo docker exec store_app tail -f storage/logs/laravel.log
```

### Reiniciar um container
```bash
sudo docker restart store_app
sudo docker restart store_horizon
```

### Backup manual do banco
```bash
sudo docker exec store_pgsql pg_dump -U store store | gzip \
  > /var/www/loja/backups/manual_$(date +%Y%m%d_%H%M%S).sql.gz
```

> O container `store_backup` já faz backup automático diariamente às 03h,
> mantendo os últimos 7 dias em `/var/www/loja/backups/`.

---

## Checklist Final (antes de abrir ao público)

- [ ] `APP_DEBUG=false` e `APP_KEY` gerado
- [ ] Senhas fortes em `DB_PASSWORD` e `REDIS_PASSWORD`
- [ ] HTTPS funcionando nos dois domínios
- [ ] `CORS_ALLOWED_ORIGINS` e `SANCTUM_STATEFUL_DOMAINS` corretos
- [ ] `RbacSeeder` rodado com sucesso
- [ ] Firewall com apenas portas 22, 80 e 443 abertas
- [ ] Backup funcionando (`sudo docker logs store_backup`)
- [ ] `curl https://api.sualoja.com.br/up` retorna 200
- [ ] Login no admin funciona

---

## Problemas Comuns

**`No such container: store_app`**
→ Os containers não subiram ainda. Rode a Etapa 3.2.

**`SQLSTATE: could not connect to server`**
→ O PostgreSQL ainda está iniciando. Aguarde 10–15 segundos e tente novamente.

**`cp: .env.example: Arquivo ou diretório inexistente`**
→ Certifique que está dentro de `/var/www/loja/backend/` (não na raiz do repo).

**Admin não carrega / CORS error**
→ Verifique se `CORS_ALLOWED_ORIGINS` no `.env` do backend bate com o domínio exato do admin (com `https://`).

**Porta 3000 não responde**
→ O build do admin pode ter falhado. Veja: `sudo docker logs store_admin`.
