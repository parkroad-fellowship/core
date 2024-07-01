fmt:
	./vendor/bin/pint

test:
	php artisan test --parallel tests/Unit --env=testing

res:
	php artisan make:filament-resource --view --soft-deletes --generate

rel:
	php artisan make:filament-relation-manager --view --soft-deletes

rev:
	php artisan reverb:start --debug
