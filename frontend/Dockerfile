# Admin (Next.js) — imagem de produção.
# IMPORTANTE: o contexto de build é a RAIZ do monorepo (pnpm workspace), não
# frontend/. Use o docker-compose.admin.yml na raiz:
#   docker compose -f docker-compose.admin.yml up -d --build
#
# NEXT_PUBLIC_* é embutido em BUILD time → passado via build args.

FROM node:22-alpine AS base
RUN corepack enable && corepack prepare pnpm@latest --activate
WORKDIR /app
ENV NEXT_TELEMETRY_DISABLED=1

# ─────────────────────────────────────────────────────
# Builder: instala deps do workspace e compila o admin
# ─────────────────────────────────────────────────────
FROM base AS builder

# 1) Manifestos primeiro (cache de layer). Todos os membros do workspace
#    precisam existir para o --frozen-lockfile validar o lockfile.
COPY pnpm-workspace.yaml package.json pnpm-lock.yaml .npmrc ./
COPY frontend/package.json                        frontend/package.json
COPY frontend/packages/contracts/package.json     frontend/packages/contracts/package.json
COPY frontend/packages/shared-types/package.json  frontend/packages/shared-types/package.json
COPY pdv/package.json                             pdv/package.json

RUN pnpm install --frozen-lockfile --filter @store/admin...

# 2) Código (admin + packages que ele transpila)
COPY frontend/packages/contracts    frontend/packages/contracts
COPY frontend/packages/shared-types frontend/packages/shared-types
COPY frontend                       frontend

# 3) Variáveis públicas embutidas no bundle
ARG NEXT_PUBLIC_API_URL
ARG NEXT_PUBLIC_APP_NAME="Store Admin"
ENV NEXT_PUBLIC_API_URL=$NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_APP_NAME=$NEXT_PUBLIC_APP_NAME

RUN pnpm --filter @store/admin build

# ─────────────────────────────────────────────────────
# Runner: roda `next start`
# ─────────────────────────────────────────────────────
FROM base AS runner
ENV NODE_ENV=production
# Copia o workspace já instalado/compilado (preserva symlinks do pnpm)
COPY --from=builder /app ./
WORKDIR /app/frontend
EXPOSE 3000
CMD ["pnpm", "start"]
