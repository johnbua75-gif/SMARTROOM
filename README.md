<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## SmartRoom Backend (Supabase Postgres)

This project is configured to use Supabase Postgres through Laravel's `pgsql` driver.

### 1. Environment setup

Update `.env` values:

- `DB_CONNECTION=pgsql`
- `DB_HOST=db.<project-ref>.supabase.co`
- `DB_PORT=5432`
- `DB_DATABASE=postgres`
- `DB_USERNAME=postgres`
- `DB_PASSWORD=<your-supabase-db-password>`
- `DB_SCHEMA=public`
- `DB_SSLMODE=require`

Optional Supabase integration values (prepared for future Supabase Auth support):

- `SUPABASE_URL`
- `SUPABASE_ANON_KEY`
- `SUPABASE_SERVICE_ROLE_KEY`
- `SUPABASE_JWT_SECRET`

### 2. Run migrations

```bash
php artisan migrate
```

### 3. API endpoints

Versioned API base path: `/api/v1`

- `GET|POST /classrooms`
- `GET|PUT|PATCH|DELETE /classrooms/{classroom}`
- `GET|POST /schedules`
- `GET|PUT|PATCH|DELETE /schedules/{schedule}`
- `GET|POST /access-cards`
- `GET|PUT|PATCH|DELETE /access-cards/{access_card}`
- `GET /access-logs`

### 4. Auth strategy

- Current implementation uses standard Laravel auth model and sessions.
- Database is Supabase Postgres only.
- `users.supabase_user_id` is included so Supabase Auth can be linked later without changing core schema.

### 5. Temporary password onboarding (admin-created users)

When an admin creates a user, SmartRoom now:

- generates a secure random temporary password (`8-12` characters)
- hashes the password before save
- sets `must_change_password = true`
- sends the temporary password to the user's email using Laravel Mail

On first login, the user is redirected to `/password/change` and must set a new password before accessing protected pages.

### 6. Example admin user creation request

`POST /admin/users`

Form fields:

- `first_name` (required)
- `last_name` (required)
- `email` (required, unique)
- `role` (required: `admin|super_admin|faculty|staff|student`)
- `department` (optional)

Example payload:

```http
POST /admin/users
Content-Type: application/x-www-form-urlencoded

first_name=Jane&last_name=Doe&email=jane.doe@psu.edu.ph&role=faculty&department=CITE
```

### 7. Gmail SMTP mail configuration

Set the following in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=yourgmail@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=yourgmail@gmail.com
MAIL_FROM_NAME="SmartRoom"
```

Notes:

- Use a Google App Password (not your normal Gmail password).
- Ensure 2-Step Verification is enabled on the Gmail account.

## 8. ESP32/Arduino smart lock integration

The system includes REST API endpoints for ESP32/Arduino smart lock devices to log access attempts and retrieve access card information.

### Running the server for ESP32 connectivity

**Important:** When running the development server for ESP32 devices to connect, you must bind to `0.0.0.0` instead of the default `127.0.0.1`:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

This allows ESP32 devices on the same network to reach the API using your machine's IP address (e.g., `http://192.168.1.100:8000/api/v1/...`).

### ESP32 API endpoints

- **GET `/api/v1/access-cards?rfid_uid=XX:XX:XX:XX&classroom_id=1`** — Look up RFID card by UID
  - Query params: `rfid_uid` (required), `classroom_id` (required)
  - Returns: Access card details if found; empty result if not found

- **POST `/api/v1/access-logs`** — Log an access attempt (RFID or fingerprint)
  - Required fields: `classroom_id`, `direction`, `result`, `accessed_at`
  - Optional fields: `user_id`, `access_card_id`, `reason`, `metadata`
  - `access_card_id` is nullable (for denied scans of unregistered cards)
  - `metadata` can store method info: `{"method": "RFID"}` or `{"method": "fingerprint"}`

### ESP32 authentication

All API requests must include a Bearer token in the Authorization header:

```
Authorization: Bearer your-sanctum-token
```

Generate a token for an ESP32 device by creating a user account with appropriate permissions.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
