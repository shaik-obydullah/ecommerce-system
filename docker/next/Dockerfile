# syntax=docker/dockerfile:1.7

# ---------------------------------------------------------------------------
# deps: install Node dependencies in a cacheable layer.
# ---------------------------------------------------------------------------
FROM node:22-alpine AS deps

WORKDIR /app

COPY apps/storefront-next/package.json ./
RUN npm install --no-audit --no-fund

# ---------------------------------------------------------------------------
# builder: compile the Next.js standalone server.
# ---------------------------------------------------------------------------
FROM node:22-alpine AS builder

WORKDIR /app

ENV NEXT_TELEMETRY_DISABLED=1

COPY --from=deps /app/node_modules ./node_modules
COPY apps/storefront-next/ ./

RUN npm run build

# ---------------------------------------------------------------------------
# prod: minimal standalone runtime.
# ---------------------------------------------------------------------------
FROM node:22-alpine AS prod

WORKDIR /app

ENV NODE_ENV=production \
    NEXT_TELEMETRY_DISABLED=1 \
    PORT=3000

COPY --from=builder /app/public ./public
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static

EXPOSE 3000

CMD ["node", "server.js"]
