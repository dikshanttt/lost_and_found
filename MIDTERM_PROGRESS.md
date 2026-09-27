# LostAndFound — Midterm Progress Snapshot

This branch contains a deliberately limited, presentable slice of the project: the shared page layout, home screen, browse/search page, and item detail display. It is intended for reviewing the interface and the read/search path through PHP into MySQL.

## What is included

- Home and browse pages in `public/`
- Keyword, type, category, location, date, and pagination logic in `public/browse.php`
- Item detail display in `public/item-detail.php`
- Shared HTML frame and display helpers in `includes/`
- CSS and small browser interactions in `public/assets/`
- PDO database connection in `config/db.php`
- MySQL tables and relationships in `schema.sql`

This snapshot is not the complete application. Account forms, report editing/submission, claim processing, administrator pages, and email notifications are not part of this branch. Some navigation links point to those later project pages and will not work in this snapshot.

## Run the included slice

1. Configure a local MySQL database using `schema.sql` only if you are setting up a fresh database; it drops and recreates the project's tables.
2. Copy `.env.example` to `.env` and set the database connection values.
3. From the project root, run `php -S 127.0.0.1:8000 -t public`.
4. Open `http://127.0.0.1:8000` and use Browse to view/search item records in the database.

## Useful files to explain

- `public/index.php`: home page and data used by its sections.
- `public/browse.php`: reads search filters, builds a prepared database query, and renders result cards.
- `public/assets/css/style.css`: presentation and responsive breakpoints.
- `public/assets/js/main.js`: menu, modal, and image-preview interactions.
- `config/db.php`: creates the PDO database connection from environment values.
- `schema.sql`: defines the data tables and their relationships.
