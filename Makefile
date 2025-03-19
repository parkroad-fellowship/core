fmt:
	./vendor/bin/pint

test:
	php artisan test --parallel tests/Unit --env=testing

res:
	php artisan make:filament-resource --view --soft-deletes --generate

rel:
	php artisan make:filament-relation-manager --view --soft-deletes

rev:
	php artisan reverb:start --debug --port=9000

run:
	docker run --env-file=.env prf

daemon:
	docker run -d \
		--name newrelic-php-daemon \
		--network kamal \
		-p 31339:31339 \
		-e NRIA_LICENSE_KEY=547ecd8fba7795abfc826d8aea18e16eFFFFNRAL \
		newrelic/php-daemon:latest

infra:
	docker run -d \
		--name newrelic-infra \
		--network=host \
		--cap-add=SYS_PTRACE \
		--privileged \
		--pid=host \
		--cgroupns=host \
		-v "/:/host:ro" \
		-v "/var/run/docker.sock:/var/run/docker.sock" \
		-e NRIA_LICENSE_KEY=547ecd8fba7795abfc826d8aea18e16eFFFFNRAL \
		-e NRIA_DISPLAY_NAME=prf-core-vm \
		-e NRIA_VERBOSE=1 \
		-e TINI_SUBREAPER=1 \
		newrelic/infrastructure:latest