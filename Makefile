fmt:
	vendor/bin/mago fmt

fmt-check:
	vendor/bin/mago fmt --check

stan:
	./vendor/bin/phpstan analyse --memory-limit=2G --fix

# Dockerized test runner mirroring .github/workflows/test-code.yml
test: test-build
	docker compose -f docker-compose.test.yml run --rm app sh -c "\
		[ -f .env ] || (cp .env.example .env && php artisan key:generate); \
		php artisan migrate:fresh --force; \
		php artisan tenants:rls --force; \
		php -d memory_limit=512M artisan test tests/Unit --env=testing"

test-build:
	docker compose -f docker-compose.test.yml up -d --build postgres
	docker compose -f docker-compose.test.yml build app

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

pub:
	# 1. Get the latest from the public world
	git fetch public
	# 2. Create/Reset the local branch to match the public main EXACTLY
	git checkout -B public-deploy public/main
	# 3. Overwrite the files with your private main's state
	git checkout main -- .
	# 4. Commit and push
	git add .
	git commit -m "Automated sync from private repo"
	git push public public-deploy:main
	# 5. Back to main
	git checkout main

tenant:
	./artisan tenant:create "Parkroad Fellowship" prf --domain=prf.test --admin-email=admin@prf.prf.test