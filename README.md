# Cheryne's

Cheryne's is a PHP 8.2 restaurant web app for menu browsing, ordering, reservations, payments, WhatsApp ordering, click-to-call CTAs, local SEO, and admin management.

Generated pieces:

- Vanilla PHP app with PSR-4 autoloading under `App\`.
- PostgreSQL schema with constraints, indexes, triggers, logs table, and seed data.
- Menu, cart, checkout, reservations, auth, admin dashboard, admin CRUD, and status management.
- Stripe Checkout integration with a local demo fallback and an M-Pesa Daraja STK Push integration.
- JSON-LD Restaurant schema, sitemap route, robots file, and Nyali, Mombasa SEO copy.
- Docker, PHPUnit stubs, `.env.example`, Apache `.htaccess`, and deployment notes.

## Requirements

- PHP 8.2+
- Composer
- PostgreSQL 14+
- Apache or Nginx configured to serve only `public/`

## Local setup

1. Install dependencies:

   ```bash
   composer install
   ```

2. Create the database:

   ```bash
   createdb cherynes
   psql -d cherynes -f schema.sql
   ```

   `schema.sql` already includes seed data. `seed.sql` is provided separately if you want to reseed sample data later.

3. Create `.env`:

   ```bash
   cp .env.example .env
   ```

4. Set database credentials in `.env`:

   ```env
   DB_DSN=pgsql:host=localhost;port=5432;dbname=cherynes
   DB_USER=your_user
   DB_PASS=your_password
   SESSION_SECURE=false
   APP_URL=http://localhost:3000
   ```

5. Run locally using the built-in router so all routes map to `public/index.php`:

    ```bash
    php -S localhost:3000 -t public router.php
    ```

    Or use Composer:

    ```bash
    composer serve
    ```

6. Open `http://localhost:3000`.

## Admin login

Development admin:

- Email: `admin@cherynes.com`
- Password:`Your admin password`

Change this immediately in production. The seed hash is for local development only.

## Docker setup

```bash
docker compose up --build
docker compose exec db psql -U cherynes -d cherynes -f /var/www/html/schema.sql
```

Then open `http://localhost:3000`.

## Payments

Stripe:

- Add `STRIPE_SECRET` and `STRIPE_PUBLISHABLE` to `.env`.
- Use Stripe webhooks before production to confirm payments server-side.
- This app redirects to a demo success URL when Stripe credentials or `stripe/stripe-php` are missing.

M-Pesa:

- Add Daraja sandbox credentials: `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET`, `MPESA_SHORTCODE`, `MPESA_PASSKEY`, and `MPESA_CALLBACK_URL`.
- Set `MPESA_SANDBOX=true` and use `https://sandbox.safaricom.co.ke` for `MPESA_BASE_URL` while testing. For production, set `MPESA_SANDBOX=false` and use `https://api.safaricom.co.ke`.
- `MPESA_CALLBACK_URL` must be a public HTTPS URL ending in `/webhooks/mpesa`; Safaricom cannot reach `localhost`. A tunnel such as ngrok can expose a local development server.
- The app requests a Daraja OAuth token, sends the STK prompt, stores the returned `CheckoutRequestID`, and confirms the order only when Safaricom posts a successful callback.
- The Docker image installs PHP cURL, which is required for Daraja API requests.

Do not store card numbers. Use Stripe Checkout tokens/sessions only.

## Local SEO

The homepage includes the required meta title:

`Cheryne's — Authentic local foods in Nyali, Mombasa`

It also includes Restaurant JSON-LD with:

- Name: `Cheryne's`
- Phone: `0795 879797`
- Area served: `Nyali, Mombasa`
- Cuisine: `Local`, `Kenyan`
- Menu URL: `/menu`

SEO routes/files:

- `/sitemap.xml`
- `/robots.txt`
- `/menu`
- `/contact`

Google Business Profile:

- Claim the profile for Cheryne's.
- Add the phone number `0795 879797`.
- Add the website menu link: `/menu`.
- Upload real food, interior, exterior, and team photos.
- Add service area and location details for Nyali, Mombasa.
- Keep hours, holiday hours, and popular dishes updated.
- Ask customers for reviews and reply to reviews professionally.

## Security checklist

- Serve only the `public/` directory as the web root.
- Enable HTTPS and set `SESSION_SECURE=true`.
- Change the seeded admin password.
- Rotate all secrets before production.
- Use a least-privilege PostgreSQL user.
- Keep Composer dependencies updated with `composer update` after testing.
- Disable debug output: `APP_DEBUG=false`.
- Set safe file permissions. Uploaded menu images should be writable by the web user, but PHP files should not be writable.
- Disable dangerous PHP functions in production where appropriate: `exec`, `shell_exec`, `passthru`, `proc_open`, `popen`.
- Configure server log rotation for `logs/app.log`.
- Back up PostgreSQL with scheduled `pg_dump` jobs and test restores.

## Privacy

- Store only the personal data needed for orders and reservations.
- Do not store payment card data.
- Use Stripe-hosted checkout and payment references.
- Provide a contact path for data deletion requests.
- Delete or anonymize old order/reservation data when it is no longer needed.

## Tests

Install dev dependencies and run:

```bash
composer test
```

The CSRF and password tests run without a database. The order integration test is skipped unless DB environment variables are configured.

## Nginx note

Use `public/` as the root and route missing files to `index.php`:

```nginx
root /path/to/cherynes/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

## Next deployment steps

- Replace placeholder food images with real Cheryne's photos.
- Confiigure Stripe webhooks and Daraja callbacks.
- Add production email/SMS alerts for failed payments and reservation changes.
- Review CSP after final domains are known.
# user
