# Vending Machine API

This is a practice project to put **hexagonal architecture** into practice
together with **Domain-Driven Design (DDD)**, applied to a real case: a drink
vending machine with change.

The API is built with Symfony 8.1, PHP 8.5 and MySQL 8.4, fully orchestrated
with Docker Compose.

The codebase is organized in modules following a DDD architecture:
`Coin`, `Item` and `MachineStatus`.

## Table of Contents

- [Requirements](#requirements)
- [Getting started with Docker](#getting-started-with-docker)
- [Database](#database)
- [Products and domain rules](#products-and-domain-rules)
- [Endpoints](#endpoints)
- [Typical usage flow](#typical-usage-flow)
- [Tests](#tests)
- [Useful commands (Makefile)](#useful-commands-makefile)

## Requirements

- Docker and Docker Compose.

## Getting started with Docker

```bash
# 1. Clone the repository
git clone https://github.com/criis16/new_vending_machine.git
cd new_vending_machine

# 2. Build and start containers (php + mysql)
make up

# 3. Install dependencies inside the container
make composer install

# 4. Create the test database
make init-test-db

# 5. Create the application tables (dev and test)
make init-db-tables
```

The API is available at <http://localhost:8000>.

Steps 4 and 5 are only needed the first time; the schema and triggers are kept
between restarts thanks to the `mysql_data` volume.

## Database

The `Makefile` takes care of all the database infrastructure, but this is the
configuration the application uses by default:

- **Dev URL** (in `.env`):

  ```dotenv
  DATABASE_URL="mysql://symfony:symfony@mysql:3306/symfony?serverVersion=8.4.0"
  ```

  The `mysql` host is the service name inside the Compose network.
- **Port**: MySQL is published to the host on `127.0.0.1:3307` so you can
  connect from an external client (DBeaver, phpMyAdmin, etc.).
- **Credentials**: user `symfony`, password `symfony`, root `root`.
- **Databases**: `symfony` (dev) and `symfony_test` (tests).
- The schema and trigger scripts live in `app/etc/databases/`
  (`mutations.sql`, `coins.sql`, `items.sql`, `machine_status.sql`,
  `create_test_db.sql`).

## Products and domain rules

- **Valid coins** (values in euros): `0.05`, `0.10`, `0.25`, `1.00`.
  Any other value is rejected with `422`.
- **Products** (case-insensitive names):

  | Product | Price |
  |---------|-------|
  | `water` | 0.65 € |
  | `juice` | 1.00 € |
  | `soda`  | 1.50 € |

- The machine starts **empty**: you must restock coins and products with
  `POST /service` before being able to buy.
- Every change on `coins`, `items` or `machine_status` is recorded in the
  `mutations` audit table through triggers.

## Endpoints

| Method | Route          | Description                                    |
|--------|----------------|------------------------------------------------|
| POST   | `/insert_coin` | Inserts a coin and credits the balance         |
| GET    | `/return_coins`| Returns the current balance in available coins |
| POST   | `/service`     | Restocks coins and products                    |
| GET    | `/item`        | Buys a product and delivers the change         |

All JSON bodies of `POST` requests require
`Content-Type: application/json`. Domain and validation errors are returned
with `422` and a message in `"message"`.

### POST /insert_coin

Inserts a coin: it increments the stock of that denomination and credits its
value to the machine balance.

```bash
curl -X POST http://localhost:8000/insert_coin \
  -H "Content-Type: application/json" \
  -d '{"coin": 1.00}'
```

**Responses**

- `201 Created`

  ```json
  { "message": "Coins Inserted" }
  ```

- `422 Unprocessable Entity` — invalid coin (`0.05`, `0.10`, `0.25`,
  `1.00`) or a negative value.

### POST /service

Restocks the machine. Coins and products are given as a `value/quantity` and
`name/quantity` map.

```bash
curl -X POST http://localhost:8000/service \
  -H "Content-Type: application/json" \
  -d '{
        "coins": { "0.05": 20, "0.10": 20, "0.25": 20, "1.00": 10 },
        "items": { "water": 10, "juice": 10, "soda": 10 }
      }'
```

**Responses**

- `200 OK`

  ```json
  { "message": "Machine serviced" }
  ```

- `422` — invalid coin/product, or a negative quantity or value.

### GET /return_coins

Returns the machine balance in coins, empties the corresponding drawers and
resets the balance to zero.

```bash
curl http://localhost:8000/return_coins
```

**Responses**

- `200 OK` — the keys are the coin values in euros.

  ```json
  {
    "message": "Coins returned successfully",
    "coins": { "1.00": 1, "0.10": 2, "0.05": 1 }
  }
  ```

- `422` — empty balance or not enough coins to make the change.

### GET /item?name=...

Buys a product. It charges the price and delivers the change if the balance
exceeds the price. The balance is left at zero after the purchase.

```bash
curl --get http://localhost:8000/item \
  --data-urlencode "name=water"
```

**Responses**

- `200 OK`

  ```json
  {
    "message": "Item delivered",
    "item": "water",
    "change": { "0.25": 1, "0.10": 1 }
  }
  ```

- `422` — invalid product, out of stock, empty balance, balance not enough
  for the price, or not enough coins to make the change.
- `404` — the `name` parameter comes empty (default `MapQueryString` behavior
  in Symfony).

## Typical usage flow

1. **Restock the machine** with `POST /service`.
2. **Insert coins** with `POST /insert_coin` (e.g. `{"coin": 1.00}`).
3. **Buy** with `GET /item?name=water` → returns the product and the change.
4. If there is unused balance left, **get it back** with `GET /return_coins`.

## Tests

Database-backed tests create the schema automatically (via `SchemaTool`), but
the `symfony_test` database must exist. If you already ran `make init-test-db`
and `make init-db-tables`, it is ready.

The `test` environment variables are defined in `app/.env.test` and
`app/.env.test.local`: `KERNEL_CLASS='App\Kernel'`, `APP_SECRET` and the
`DATABASE_URL` (which points to `127.0.0.1:3307`, the port Docker publishes to
the host).

Run the suite inside the `php` container, using the Compose network URL (the
`.env.test` points to `127.0.0.1:3307`, which only exists on the host):

```bash
docker compose exec -e DATABASE_URL="mysql://symfony:symfony@mysql:3306/symfony?serverVersion=8.4.0" php php bin/phpunit
```

Alternative: run the tests from the host with `php bin/phpunit` (PHP >= 8.4 and
dependencies installed in `app/`), which does reach `127.0.0.1:3307`.

## Useful commands (Makefile)

| Command               | Description                                            |
|-----------------------|--------------------------------------------------------|
| `make up`             | Build and start `php` + `mysql`                        |
| `make down`           | Stop and remove containers                             |
| `make stop`           | Stop containers                                        |
| `make build`          | Rebuild images                                         |
| `make bash`           | Open a shell inside the `php` container                |
| `make composer <cmd>` | Run `composer` inside the container                    |
| `make console <cmd>`  | Run `symfony console` inside the container             |
| `make migrate`        | Apply Doctrine migrations                              |
| `make init-test-db`   | Create the `symfony_test` database                     |
| `make init-db-tables` | Create tables/triggers in `symfony` and `symfony_test` |
| `make test`           | Run PHPUnit inside the `php` container                 |
| `make status`         | Containers status                                      |
| `make logs`           | Follow container logs                                  |

Notes:

- `make migrate` only applies Doctrine migrations; a clean machine is set up
  with `make init-test-db` + `make init-db-tables`.
- `APP_SECRET` is empty in `.env` (enough for dev); define it in `.env.local`
  for other environments.