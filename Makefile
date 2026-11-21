SHELL := /bin/bash
.DEFAULT_GOAL := help

MODE ?= blade
COMPOSE := docker compose -f compose.yaml -f compose.$(MODE).yaml
DEV := docker compose -f compose.yaml -f compose.$(MODE).yaml -f compose.dev.yaml
PROD := docker compose -f compose.yaml -f compose.$(MODE).yaml -f compose.prod.yaml

.PHONY: help init up dev prod down down-v restart ps logs build config \
        migrate seed fresh test test-unit lint pint stan composer artisan shell \
        api-docs e2e dev-image

help: ## Show available targets
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-14s\033[0m %s\n", $$1, $$2}'

init: ## Create env files, generate APP_KEY, build and start the stack
	@test -f .env || cp .env.example .env
	@test -f apps/core/.env || cp apps/core/.env.example apps/core/.env
	@if ! grep -q '^APP_KEY=base64:' apps/core/.env; then \
		key=$$(openssl rand -base64 32); \
		sed -i.bak "s|^APP_KEY=.*|APP_KEY=base64:$$key|" apps/core/.env; \
		rm -f apps/core/.env.bak; \
	fi
	$(COMPOSE) build
	$(COMPOSE) up -d
	@echo ""
	@echo "Stack is starting. Open http://localhost:8080 once healthy."
	@echo "Admin: http://localhost:8080/admin (seeded credentials are printed by the seeder)."

up: ## Start the stack (MODE=blade|next)
	$(COMPOSE) up -d --build

dev: ## Start with bind mounts and dev dependencies
	$(DEV) up -d --build

prod: ## Start with the production overlay
	$(PROD) up -d --build

down: ## Stop the stack
	$(COMPOSE) down

down-v: ## Stop the stack and delete all volumes
	$(COMPOSE) down -v

restart: ## Restart the stack
	$(COMPOSE) restart

ps: ## Show service status
	$(COMPOSE) ps

logs: ## Follow logs
	$(COMPOSE) logs -f --tail=100

build: ## Build images
	$(COMPOSE) build

config: ## Render the resolved compose configuration
	$(COMPOSE) config

migrate: ## Run migrations and seeders
	$(COMPOSE) run --rm migrate

seed: ## Re-run seeders
	$(COMPOSE) exec core php artisan db:seed --force

fresh: ## Reset the database and reseed
	$(COMPOSE) exec core php artisan migrate:fresh --seed --force

dev-image: ## Build the dev-target core image used by test/lint
	$(DEV) build core

test: dev-image ## Run static analysis and the Pest suite against Postgres/Redis
	$(DEV) run --rm --no-deps core ./vendor/bin/pint --test
	$(DEV) run --rm --no-deps core ./vendor/bin/phpstan analyse --memory-limit=512M
	$(DEV) run --rm --no-deps \
		-e APP_ENV=testing \
		-e DB_DATABASE=ecommerce_test \
		-e CACHE_STORE=redis \
		-e QUEUE_CONNECTION=sync \
		-e SESSION_DRIVER=array \
		core php artisan test

test-unit: dev-image ## Run only unit tests
	$(DEV) run --rm --no-deps \
		-e APP_ENV=testing \
		-e DB_DATABASE=ecommerce_test \
		-e CACHE_STORE=redis \
		-e QUEUE_CONNECTION=sync \
		-e SESSION_DRIVER=array \
		core php artisan test --testsuite=Unit

lint: dev-image ## Run Pint and PHPStan
	$(DEV) run --rm --no-deps core ./vendor/bin/pint --test
	$(DEV) run --rm --no-deps core ./vendor/bin/phpstan analyse --memory-limit=512M

pint: ## Auto-fix code style
	$(DEV) run --rm --no-deps core ./vendor/bin/pint

stan: ## Run PHPStan
	$(DEV) run --rm --no-deps core ./vendor/bin/phpstan analyse --memory-limit=512M

composer: ## Run Composer inside the dev image (ARGS="require ...")
	$(DEV) run --rm --no-deps core composer $(ARGS)

artisan: ## Run an Artisan command (ARGS="about")
	$(COMPOSE) exec core php artisan $(ARGS)

shell: ## Open a shell in the core container
	$(COMPOSE) exec core bash

api-docs: ## Export the OpenAPI spec to docs/api/openapi.json
	$(DEV) run --rm --no-deps core php artisan scramble:export --stdout > docs/api/openapi.json

e2e: ## Placeholder until Playwright lands in Phase 5
	@echo "E2E suite is introduced with the Next.js storefront (Phase 5)."
