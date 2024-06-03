fmt:
	./vendor/bin/pint

test:
	php artisan test --parallel tests/Unit --env=testing

