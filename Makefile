.PHONY: up down restart build logs ps shell db-create db-migrate db-fixtures console cache-clear test test-unit test-integration test-functional test-performance coverage lint cs-fix analyze install update setup prod-deploy help

# Default target
help:
	@echo "Snowflake Symfony Application Makefile"
	@echo "-----------------------------------------------------------------------------------"
	@echo "up              - Start all containers"
	@echo "down            - Stop all containers"
	@echo "restart         - Restart all containers"
	@echo "build           - Build or rebuild containers"
	@echo "logs            - View container logs"
	@echo "ps              - List running containers"
	@echo "shell           - Open shell in PHP container"
	@echo "db-create       - Create database"
	@echo "db-migrate      - Run database migrations"
	@echo "db-fixtures     - Load database fixtures"
	@echo "console         - Run Symfony console (e.g. make console c=cache:clear)"
	@echo "cache-clear     - Clear Symfony cache"
	@echo ""
	@echo "Testing commands:"
	@echo "test            - Run tests (e.g. make test c=Unit)"
	@echo "test-unit       - Run unit tests"
	@echo "test-integration - Run integration tests"
	@echo "test-functional - Run functional tests"
	@echo "test-performance - Run performance tests"
	@echo "coverage        - Generate test coverage report"
	@echo ""
	@echo "Code quality:"
	@echo "lint            - Run PHP linter"
	@echo "cs-fix          - Fix code style issues"
	@echo "analyze         - Run static analysis (PHPStan)"
	@echo ""
	@echo "Project setup:"
	@echo "install         - Install dependencies"
	@echo "update          - Update dependencies"
	@echo "setup           - Initial project setup"
	@echo ""
	@echo "Production:"
	@echo "prod-deploy     - Prepare for production deployment"
	@echo "snowflake-info  - Generate and analyze Snowflake IDs"
	@echo "-----------------------------------------------------------------------------------"

# Docker commands
up:
	docker-compose --env-file .env.docker up -d

down:
	docker-compose down

restart:
	docker-compose restart

build:
	docker-compose --env-file .env.docker build

logs:
	docker-compose logs -f

ps:
	docker-compose ps

shell:
	docker-compose exec php sh

# Symfony commands
db-create:
	docker-compose exec php bin/console doctrine:database:create --if-not-exists

db-migrate:
	docker-compose exec php bin/console doctrine:migrations:migrate --no-interaction

db-fixtures:
	docker-compose exec php bin/console doctrine:fixtures:load --no-interaction

console:
	docker-compose exec php bin/console $(c)

cache-clear:
	docker-compose exec php bin/console cache:clear

# Testing commands
test:
	docker-compose exec php bin/phpunit --testsuite=$(c)

test-unit:
	docker-compose exec php bin/phpunit --testsuite=Unit

test-integration:
	docker-compose exec php bin/phpunit --testsuite=Integration

test-functional:
	docker-compose exec php bin/phpunit --testsuite=Functional

test-performance:
	docker-compose exec php bin/phpunit --testsuite=Performance

coverage:
	docker-compose exec php bin/phpunit --coverage-html=var/coverage
	@echo "Coverage report generated in var/coverage/index.html"

# Code quality
lint:
	@echo "Running PHP Syntax Check..."
	docker-compose exec php find src tests -name "*.php" -type f -print0 | xargs -0 -n1 php -l

cs-fix:
	docker-compose exec php vendor/bin/php-cs-fixer fix --verbose

analyze:
	docker-compose exec php vendor/bin/phpstan analyse src tests

# Project setup
install:
	docker-compose exec php composer install

update:
	docker-compose exec php composer update

setup: build up install db-create db-migrate

# Production
prod-deploy:
	docker-compose exec php composer install --no-dev --optimize-autoloader
	docker-compose exec php bin/console cache:clear --env=prod
	docker-compose exec php bin/console cache:warmup --env=prod
	docker-compose exec php bin/console assets:install --env=prod
	@echo "Application ready for production deployment"

# Snowflake utilities
snowflake-info:
	docker-compose exec php bin/console app:snowflake:info $(ARGS)

# Shorthand for common commands
.PHONY: t cc sf

t: test-unit
cc: cache-clear
sf: snowflake-info