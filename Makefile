.PHONY: up down build bash composer migrate status logs stop test init-test-db init-db-tables

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

init-test-db:
		docker compose exec -T mysql mysql -u root -proot < app/etc/databases/create_test_db.sql

init-db-tables:
		docker compose exec -T mysql sh -c 'exec mysql -u root -proot symfony' < app/etc/databases/mutations.sql
		docker compose exec -T mysql sh -c 'exec mysql -u root -proot symfony' < app/etc/databases/coins.sql
		docker compose exec -T mysql sh -c 'exec mysql -u root -proot symfony' < app/etc/databases/items.sql
		docker compose exec -T mysql sh -c 'exec mysql -u root -proot symfony' < app/etc/databases/machine_status.sql

		docker compose exec -T mysql sh -c 'exec mysql -u root -proot symfony_test' < app/etc/databases/mutations.sql
		docker compose exec -T mysql sh -c 'exec mysql -u root -proot symfony_test' < app/etc/databases/coins.sql
		docker compose exec -T mysql sh -c 'exec mysql -u root -proot symfony_test' < app/etc/databases/items.sql
		docker compose exec -T mysql sh -c 'exec mysql -u root -proot symfony_test' < app/etc/databases/machine_status.sql

%:
	@: