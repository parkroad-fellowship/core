fmt:
	./vendor/bin/pint

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

ssh:
	ssh -i ~/.ssh/id_prfops azureuser@4.221.155.241

nlp:
	cd .. && cd nlp/nlp && make dev