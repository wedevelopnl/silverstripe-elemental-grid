COMPOSE := docker compose -f .docker/compose.yml

.PHONY: up down destroy build test test-unit test-integration test-js test-e2e test-e2e-ui coverage coverage-unit coverage-integration coverage-js mutate mutate-js analyse qa qa-js

## Start services (build if needed)
up:
	$(COMPOSE) up -d --build

## Stop services
down:
	$(COMPOSE) down

## Stop services and remove volumes
destroy:
	$(COMPOSE) down -v

## Build images without starting
build:
	$(COMPOSE) build

## Ensure services are running
ensure-up:
	@$(COMPOSE) exec app true 2>/dev/null || $(MAKE) up

## Run all tests (PHP unit + integration + JS)
test: ensure-up test-unit test-integration test-js

## Run unit tests (no database or framework)
test-unit: ensure-up
	$(COMPOSE) exec app vendor/bin/phpunit --testsuite unit

## Run integration tests (full SilverStripe environment)
test-integration: ensure-up
	$(COMPOSE) exec app vendor/bin/phpunit --testsuite integration

## Run JavaScript tests (Vitest)
test-js:
	npm run test

## Run all tests with merged coverage (HTML + Clover XML)
coverage: ensure-up
	$(COMPOSE) exec app vendor/bin/phpunit \
		--coverage-html coverage/unit/html \
		--coverage-clover coverage/unit/clover.xml

## Run unit tests with coverage (individual report)
coverage-unit: ensure-up
	$(COMPOSE) exec app vendor/bin/phpunit --testsuite unit \
		--coverage-html coverage/unit/html \
		--coverage-clover coverage/unit/clover.xml

## Run integration tests with coverage (individual report)
coverage-integration: ensure-up
	$(COMPOSE) exec app vendor/bin/phpunit --testsuite integration \
		--coverage-html coverage/integration/html \
		--coverage-clover coverage/integration/clover.xml

## Run JavaScript tests with coverage
coverage-js:
	npm run coverage

## Run PHP mutation testing (Infection)
mutate: ensure-up
	$(COMPOSE) exec app vendor/bin/infection --threads=4

## Run JavaScript mutation testing (Stryker)
mutate-js:
	npm run mutate

## Run PHPStan static analysis
analyse: ensure-up
	$(COMPOSE) exec app vendor/bin/phpstan analyse -c phpstan.neon.dist --memory-limit=512M

## Run full QA suite (PHPStan + PHP tests + JS QA)
qa: analyse test-unit test-integration qa-js

## Run E2E tests (Playwright, requires running Docker services)
test-e2e: ensure-up
	npx playwright test

## Run E2E tests with interactive UI
test-e2e-ui: ensure-up
	npx playwright test --ui

## Run JavaScript QA (lint + typecheck + test)
qa-js:
  # Not setup yet
	# npm run qa
