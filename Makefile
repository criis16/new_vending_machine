.PHONY: up down build bash composer migrate status logs stop test

up:
	docker compose up -d --build

down:
	docker compose down

stop:
	docker compose stop

build:
	docker compose build

bash:
	docker compose exec php sh

# Ejecutar comandos de Composer dentro del contenedor (ej: make composer install)
composer:
	docker compose exec php composer $(filter-out $@,$(MAKECMDGOALS))

# Ejecutar comandos de Symfony console (ej: make console make:migration)
console:
	docker compose exec php symfony console $(filter-out $@,$(MAKECMDGOALS))

migrate:
	docker compose exec php symfony console doctrine:migrations:migrate

test:
	docker compose exec php php bin/phpunit

status:
	docker compose ps

logs:
	docker compose logs -f

%:
	@: