Laravel Jetstream QR Code Login

Авторизация через QR-код между устройствами.

Проект позволяет войти в аккаунт на компьютере через подтверждение входа с телефона. Компьютер создаёт QR-код, телефон сканирует его, пользователь подтверждает вход, после чего компьютер автоматически получает авторизацию через WebSocket-событие.

Features
QR Code authentication
Login approval from another device
Real-time communication using Laravel Reverb
One-time QR sessions
QR expiration handling
Device information detection
Automatic cleanup of expired sessions
Automated tests
Code style checking
Static analysis
GitHub Actions CI
Stack
Backend
Laravel
Laravel Jetstream
Laravel Fortify
Laravel Reverb
MySQL
PHPUnit
Frontend
Vue 3
Inertia.js
Vite
Tailwind CSS
Development tools
Laravel Pint
Larastan (PHPStan)
GitHub Actions
Requirements

Before installation make sure you have:

PHP >= 8.4
Composer
Node.js >= 22
npm
MySQL
Installation
1. Clone repository
   git clone git@github.com:neforskiy/Laravel-Jetstream-Qr-Code-Login.git

cd Laravel-Jetstream-Qr-Code-Login
2. Install backend dependencies
   composer install
3. Install frontend dependencies
   npm install
4. Configure environment

Create .env:

cp .env.example .env

Generate application key:

php artisan key:generate
Database setup

Create MySQL database:

CREATE DATABASE laravel_qr_login;

Configure .env:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_qr_login
DB_USERNAME=root
DB_PASSWORD=

Run migrations:

php artisan migrate
Reverb configuration

Configure WebSocket server in .env:

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=qr-login
REVERB_APP_KEY=your_key
REVERB_APP_SECRET=your_secret

REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
Running the project

Start Laravel server:

php artisan serve

Start Vite:

npm run dev

Start Reverb:

php artisan reverb:start

Start scheduler:

php artisan schedule:work
QR Login Flow
1. Creating session

The computer requests:

POST /api/qr/session

Server creates:

login_sessions

with:

status = waiting
expires_at = now + 5 minutes

The response contains:

{
"uuid": "session-uuid",
"url": "/qr/session-uuid"
}
2. QR scanning

The phone opens:

/qr/{uuid}

The user sees login information:

IP address
Browser
Operating system
3. Approval

After confirmation:

waiting
|
v
approved

The server:

saves user id;
broadcasts LoginApproved event;
notifies the computer through Laravel Reverb.
4. Consuming session

The computer receives WebSocket event:

login.approved

Then calls:

POST /qr/consume/{uuid}

Server:

checks status;
checks expiration;
logs the user in;
regenerates session;
deletes QR session.

Final state:

approved
|
v
consumed
|
v
deleted
Security

Implemented protections:

QR sessions have expiration time.
UUID can only be used once.
Already processed sessions cannot be approved again.
Expired sessions cannot be consumed.
WebSocket event does not authenticate users directly.
Authentication happens only after server-side validation.
QR sessions are automatically deleted after expiration.
Session regeneration after login.
Testing

Run Laravel tests:

php artisan test

The project includes tests for:

QR session creation
QR approval
Expired QR protection
Duplicate approval protection
Session consuming
Session deletion
QR information endpoint
Invalid QR UUID handling
Code quality
Laravel Pint

Check code style:

./vendor/bin/pint --test

Fix formatting:

./vendor/bin/pint
Larastan

Run static analysis:

composer analyse
CI Pipeline

GitHub Actions automatically runs:

Push / Pull Request

        |
        v

Install dependencies

        |
        v

Laravel Pint

        |
        v

Larastan

        |
        v

PHPUnit Tests

        |
        v

Vite Build

If any step fails, the pipeline stops.

Project structure
app/
├── Events/
│    └── LoginApproved.php
│
├── Http/
│    └── Controllers/
│         └── LoginSessionController.php
│
└── Models/
└── LoginSession.php


resources/
└── js/
└── Pages/
└── Qr/


database/
└── migrations/
└── create_login_sessions_table.php


tests/
└── Feature/
└── LoginSessionTest.php
Future improvements

Possible future features:

Device management page
Ability to revoke trusted devices
Remembered devices
Rate limiting improvements
Docker environment
Automated deployment to VPS
Notifications about new logins
License

This project is open-source and available under the MIT License.
