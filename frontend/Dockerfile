# Admin (Next.js) — imagem de produção.
# Contexto de build = frontend/ (pasta onde este Dockerfile está).
# Uso: cd frontend && docker compose up -d --build

FROM node:22-alpine AS base
RUN corepack enable && corepack prepare pnpm@latest --activate
WORKDIR /app
ENV NEXT_TELEMETRY_DISABLED=1

# ── Builder ──────────────────────────────────────────────────────────────────
FROM base AS builder

# 1) Manifesto primeiro (cache de layer)
COPY package.json pnpm-lock.yaml .npmrc pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile --ignore-scripts

# 2) Código
COPY . .

# 3) Variáveis públicas embutidas no bundle
ARG NEXT_PUBLIC_API_URL
ARG NEXT_PUBLIC_APP_NAME="Store Admin"
ENV NEXT_PUBLIC_API_URL=$NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_APP_NAME=$NEXT_PUBLIC_APP_NAME

RUN pnpm build

# ── Runner ────────────────────────────────────────────────────────────────────
FROM base AS runner
ENV NODE_ENV=production
COPY --from=builder /app ./
EXPOSE 3000
CMD ["pnpm", "start"]
