.PHONY: up down shell artisan test analyse format migrate fresh seed

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

shell:
	docker compose exec app bash

artisan:
	docker compose exec app php artisan $(cmd)

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

seed:
	docker compose exec app php artisan db:seed

test:
	docker compose exec app ./vendor/bin/pest

test-coverage:
	docker compose exec app ./vendor/bin/pest --coverage

analyse:
	docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=256M

format:
	docker compose exec app ./vendor/bin/pint

format-check:
	docker compose exec app ./vendor/bin/pint --test

tinker:
	docker compose exec app php artisan tinker

logs:
	docker compose logs -f app

queue:
	docker compose exec app php artisan queue:work --tries=3
