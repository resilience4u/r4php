SHELL := /bin/bash

.PHONY: build shell deps run-basic run-guzzle test help lock
.PHONY: up down

up:
	@docker compose up -d httpbin

down:
	@docker compose down

build:
	@docker compose build --no-cache

shell:
	@docker compose run --rm app bash

deps:
	@docker compose run --rm composer install --prefer-dist --no-interaction

lock:
	@docker compose run --rm composer update --no-interaction

run-basic:
	@docker compose run --rm app php examples/basic.php

run-guzzle:
	@docker compose run --rm app php examples/guzzle.php

test:
	@docker compose run --rm app ./vendor/bin/phpunit --colors=always

help:
	@echo "Targets:"
	@echo "  build       - Build docker image"
	@echo "  deps        - composer install (in container)"
	@echo "  lock        - composer update (refresh lock, in container)"
	@echo "  run-basic   - Run examples/basic.php"
	@echo "  run-guzzle  - Run examples/guzzle.php"
	@echo "  test        - Run PHPUnit tests"
	@echo "  shell       - Open bash in app container"
