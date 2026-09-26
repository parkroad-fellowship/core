fmt:
	vendor/bin/mago fmt

fmt-check:
	vendor/bin/mago fmt --check

stan:
	./vendor/bin/phpstan analyse --memory-limit=2G

stan-fix:
	./vendor/bin/phpstan analyse --memory-limit=2G --fix

# Dockerized test runner mirroring .github/workflows/test-code.yml (Postgres 16 + RLS).
# Usage:
#   make test                                        full suite
#   make test-file FILE=tests/Feature/Api/DepartmentTest.php
#   make test-filter FILTER="creates a department"
TEST_COMPOSE = docker compose -f docker-compose.test.yml
TEST_PREPARE = [ -f .env ] || (cp .env.example .env && php artisan key:generate); \
	php artisan migrate:fresh --force >/dev/null; \
	php artisan tenants:rls --force >/dev/null;

test: test-up
	$(TEST_COMPOSE) run --rm app sh -c "$(TEST_PREPARE) vendor/bin/pest --compact"

test-file: test-up
	$(TEST_COMPOSE) run --rm app sh -c "$(TEST_PREPARE) vendor/bin/pest --compact $(FILE)"

test-filter: test-up
	$(TEST_COMPOSE) run --rm app sh -c "$(TEST_PREPARE) vendor/bin/pest --compact --filter='$(FILTER)'"

test-up:
	$(TEST_COMPOSE) up -d --wait postgres

test-build:
	$(TEST_COMPOSE) up -d --build --wait postgres
	$(TEST_COMPOSE) build app

.PHONY: fmt fmt-check stan stan-fix test test-file test-filter test-up test-build

res:
	php artisan make:filament-resource --view --soft-deletes --generate

rel:
	php artisan make:filament-relation-manager --view --soft-deletes

rev:
	php artisan reverb:start --debug --port=9090

run:
	docker run --env-file=.env prf


build:
	docker build --pull --rm -f 'Dockerfile'  --platform linux/amd64,linux/arm64 -t 'prf:latest' '.' 

nlp:
	cd .. && cd nlp/nlp && make dev

tenant:
	php artisan tenants:create "Parkroad Fellowship" prf --domain=prf.test --admin-email=admin@example.org --org-email-domain=example.org