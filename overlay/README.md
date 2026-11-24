# Courier API

A small Laravel REST API for managing **couriers** (master data). It has one module only and no UI.

- List with pagination, sorting, search and level filter
- Show, create, update and delete a courier
- Full input validation
- Feature tests for every endpoint and unit tests for the query scopes

## Requirements

- PHP 8.2+
- Composer
- MySQL 8 (the tests use in-memory SQLite, so they need the `pdo_sqlite` extension)

## Setup

```bash
git clone <repository-url> courier-api
cd courier-api
composer install
cp .env.example .env
php artisan key:generate
```

Set the database in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=courier_api
DB_USERNAME=root
DB_PASSWORD=
```

Create the database, then run:

```bash
php artisan migrate
php artisan db:seed --class=CourierSeeder   # optional, 50 fake couriers
php artisan serve
```

The API is now available at `http://127.0.0.1:8000/api/couriers`.
Send the header `Accept: application/json` with every request.

## Running the tests

```bash
php artisan test
```

The tests run on in-memory SQLite and do not touch your MySQL data.

## Database design

Table `couriers`:

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint, primary key | |
| `name` | string(255) | required, indexed because it is the default sort |
| `phone` | string(20) | required, unique. Digits with an optional leading `+`, 8 to 15 digits |
| `email` | string(255) | optional, unique |
| `level` | unsigned tinyint | 1 to 5, default 1, indexed because it is a filter |
| `vehicle_type` | string(20) | `bicycle`, `motorcycle`, `car`, `van` or `truck` (PHP enum `App\Enums\VehicleType`) |
| `vehicle_plate_number` | string(15) | optional |
| `address` | text | optional |
| `is_active` | boolean | default `true`, so a courier can be switched off without deleting them |
| `created_at` | timestamp | also the **registration date** used for sorting |
| `updated_at` | timestamp | |

Why these columns: a courier needs to be reachable (phone, email), assigned work by experience (level) and
vehicle (vehicle type and plate), and be switched on or off (is_active). Phone is unique because it is the
natural identifier a dispatcher uses.

## Endpoints

| Method | URL | Description |
| --- | --- | --- |
| GET | `/api/couriers` | List couriers (paginated) |
| POST | `/api/couriers` | Create a courier, returns `201` |
| GET | `/api/couriers/{id}` | Show all data of a courier |
| PUT / PATCH | `/api/couriers/{id}` | Update a courier. Every field is optional, only the sent fields change |
| DELETE | `/api/couriers/{id}` | Delete a courier, returns `204` |

Unknown ids return `404`. Invalid input returns `422` with the errors per field.

### GET /api/couriers

| Query parameter | Example | Description |
| --- | --- | --- |
| `page` | `?page=2` | Page number |
| `per_page` | `?per_page=25` | Items per page, 1 to 100, default 15 |
| `sort` | `?sort=registered_at` | `name` (default) or `registered_at` (date registered, overrides the default sort) |
| `direction` | `?direction=desc` | `asc` or `desc`. Default is `asc` for `name` and `desc` (newest first) for `registered_at` |
| `search` | `?search=budi+agung` | Every word must appear in the name, in any order, case-insensitive. `budi agung` finds `Budiono Hadi Agung` |
| `level` | `?level=2,3` | Only couriers with one of these levels (1 to 5), comma separated |

The parameters can be combined, for example `/api/couriers?search=budi&level=2,3&sort=registered_at&direction=asc`.
Pagination links keep the chosen parameters.

Example response:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Budiono Hadi Agung",
      "phone": "081234567890",
      "email": "budi@example.com",
      "level": 3,
      "vehicle_type": "motorcycle",
      "vehicle_plate_number": "L 1234 AB",
      "address": "Jl. Raya Surabaya No. 1",
      "is_active": true,
      "registered_at": "2026-11-24T10:15:00+00:00",
      "updated_at": "2026-11-24T10:15:00+00:00"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "per_page": 15, "total": 1, "last_page": 1 }
}
```

### POST /api/couriers and PUT /api/couriers/{id}

| Field | Rules |
| --- | --- |
| `name` | required, string, max 255 |
| `phone` | required, `+` optional then 8 to 15 digits, unique |
| `email` | optional, valid email, max 255, unique |
| `level` | required, integer from 1 to 5 |
| `vehicle_type` | required, one of `bicycle`, `motorcycle`, `car`, `van`, `truck` |
| `vehicle_plate_number` | optional, max 15 |
| `address` | optional, max 1000 |
| `is_active` | optional, boolean (default `true`) |

On update the unique checks ignore the courier that is being updated.

## Code structure

```
app/Enums/VehicleType.php                  allowed vehicle types
app/Models/Courier.php                     model with the search, levels and sorted scopes
app/Http/Controllers/CourierController.php thin controller
app/Http/Requests/*CourierRequest.php      validation (index query, store, update)
app/Http/Resources/CourierResource.php     JSON shape of a courier
database/migrations/...create_couriers_table.php
database/factories/CourierFactory.php
tests/Feature/Courier/                     index, show, store, update and destroy tests
tests/Unit/CourierScopeTest.php            tests for the model scopes
```

Design choices:

- Filtering, searching and sorting live in model scopes, so the controller stays short and each scope is tested on its own.
- Query parameters are validated in `IndexCourierRequest`, so a wrong `sort` or `level` returns `422` instead of being ignored silently.
- Search escapes `%` and `_` so they are matched literally, and uses `ESCAPE '!'`, which works on MySQL and SQLite.
- Sorting adds `id` as a tie-breaker so pages never repeat or skip rows.
- Delete removes the row for real (no soft deletes), as the brief asks for the data to be gone from the database.
