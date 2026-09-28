# LostAndFound V1.5

Page-based PHP and MySQL project. V1.5 includes account authentication, searchable item listings, responsive pages, and email notifications through PHPMailer and SMTP.

## Local setup

1. For a fresh database, import `schema.sql`. It creates the `lost_found_hub` database and tables; it drops existing app tables, so never import it over data you need. For an existing installation, keep its data and proceed to the setup script, which adds the V1.5 authentication support table and claim uniqueness rule where possible.
2. Copy `.env.example` to `.env` and set the database connection, a unique `APP_KEY`, initial administrator email/password, and SMTP details. Use a password of at least 12 characters for the initial administrator.
3. Install the PHP dependency with `composer install`.
4. Run `php config/setup.php` from the project directory. The setup script is intentionally CLI-only and creates the first admin using the environment values.
5. Remove `ADMIN_PASSWORD` from `.env` after the admin account is created. Keep `.env` private and never commit it.
6. Set the web server document root to the project's `public/` directory. For PHP's built-in server, run `php -S 127.0.0.1:8000 -t public` from the project root. The private configuration and source folders are intentionally outside the document root.

`SMTP_ENCRYPTION` accepts `tls`, `ssl`, or `none`; use the value and port supplied by your SMTP provider. `MAIL_FROM_ADDRESS` must be an address authorized by that provider. `ADMIN_NOTIFICATION_EMAIL` receives new report and claim notices. Email delivery failures are recorded in the PHP error log and do not interrupt submissions.

The `public/.htaccess` files disable directory listings and prevent uploaded PHP scripts from being served on Apache. For Nginx or another server, configure equivalent upload restrictions. Keep the project root outside the public document root; only `public/` should be web accessible.
