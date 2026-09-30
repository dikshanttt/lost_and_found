# CivicFind – Lost and Found Hub

A PHP web application for posting, searching, and claiming lost & found items.

## Tech Stack

- PHP 8+
- MySQL
- HTML, CSS, JavaScript (vanilla)
- Composer / PHPMailer

## Project Structure

```
LostAndFound/
├── index.php              Home page
├── login.php              Sign in (demo: refreshes page)
├── register.php           Create account (demo: refreshes page)
├── logout.php             Sign out
├── browse.php             Search & filter items
├── item-detail.php        Item detail & claim page
│
├── config/
│   └── config.php         Environment loader
│
├── database/
│   ├── db.php             PDO database connection
│   └── schema.sql         MySQL schema
│
├── auth/
│   └── auth.php           Session & role-based access
│
├── admin/
│   ├── dashboard.php      Admin overview
│   ├── claims.php         Manage claims
│   ├── items.php          Manage items
│   └── users.php          Manage users
│
├── user/
│   ├── my-reports.php     User's own reports
│   ├── report-item.php    Submit a report
│   ├── edit-item.php      Edit a report
│   ├── delete-item.php    Delete a report
│   └── submit-claim.php   Submit a claim
│
├── includes/
│   ├── header.php         HTML head & opening tags
│   ├── footer.php         Footer & closing tags
│   ├── navbar.php         Navigation bar
│   └── functions.php      Shared helpers
│
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── images/
│
├── uploads/
│   ├── profiles/
│   └── documents/
│
├── vendor/                Composer packages
├── .env                   Environment config
├── .env.example           Sample env file
├── .gitignore
├── composer.json
└── composer.lock
```

## Setup

1. Install dependencies:
   ```bash
   composer install
   ```

2. Copy `.env.example` to `.env` and set your database credentials.

3. Import the schema:
   ```bash
   mysql -u root -p lost_found_hub < database/schema.sql
   ```

4. Start the PHP development server:
   ```bash
   php -S localhost:8000
   ```

5. Open `http://localhost:8000` in your browser.

## Notes

- Login and register forms refresh the page for demonstration purposes.
- The app reads database settings from `.env`.
- Role validation is enforced server-side before accessing protected pages.
