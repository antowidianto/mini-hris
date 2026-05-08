# Mini HRIS Backend

Laravel REST API for the Mini HRIS portfolio project.

## Setup

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

## Checks

```bash
php artisan test
vendor/bin/pint --test
```

See the root `README.md` for the full project overview, default accounts, and API endpoint summary.

