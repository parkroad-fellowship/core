fmt:
	./vendor/bin/pint

stan:
	./vendor/bin/phpstan analyse --memory-limit=2G --fix

test:
	php artisan test --parallel tests/Unit --env=testing

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