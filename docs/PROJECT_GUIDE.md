# LostAndFound V1.5 — Beginner's Project Guide

This guide explains the project in reading order: **front end first**, then **backend**, then how a complete request travels through the application. It is written for someone learning PHP, HTML, CSS, JavaScript, and MySQL. You do not need to understand every function on the first read. Start with the bold ideas and follow the example flows.

---

## 1. What the project does

LostAndFound (the interface calls it CivicFind) lets people:

- create an account and sign in;
- post a report for an item they lost or found;
- search and filter reports;
- ask to claim a found item;
- manage their own reports and see their claims;
- let administrators review claims and manage users and item records.

It is a traditional PHP website. A browser requests a page, PHP runs on the server, PHP may read or write MySQL data, and then PHP sends HTML back to the browser. JavaScript adds small interactions in the browser. There is no separate JavaScript application or web framework.

---

## 2. The folders: where to look

```text
LostAndFound/
├── public/                 The only folder the web server should publish
│   ├── index.php           Home page
│   ├── browse.php          Search results and filters
│   ├── item-detail.php     One report and claim form
│   ├── assets/             CSS, JavaScript, images, uploaded photos
│   ├── auth/               Login, registration, logout pages
│   ├── user/               User report and claim pages/actions
│   └── admin/              Admin dashboard and management pages
├── includes/               Shared PHP helpers and page pieces
├── config/                 Database/environment config and setup script
├── schema.sql              MySQL database/table definition
├── docs/                   Project documents and this guide
├── tests/                  Manual system verification script
├── composer.json/lock      PHPMailer dependency declaration and lock
├── vendor/                 Installed Composer packages (generated)
├── .env.example            Safe sample settings to copy to .env
└── README.md               Setup and run instructions
```

**Why `public/` matters:** a web server should expose only `public/`. Database credentials, setup code, tests, and project documents stay outside the public web folder. This is also why the PHP file paths sometimes go up one or two folders with `../`.

When a browser visits `/assets/css/style.css`, that `/` means the web root, which is `public/`. It does not mean the top-level project folder on disk.

---

# Part A — Front end (what the visitor sees)

## 3. The three front-end building blocks

### HTML: page content and forms

HTML describes the page: headings, links, buttons, forms, tables, and item cards. For example, a search form in `public/browse.php` contains an input named `keyword` and uses `method="get"`.

- `name="keyword"` is the name PHP will use to read the submitted value.
- `method="get"` places search values in the URL, for example `browse.php?keyword=keys`.
- A link such as `href="/item-detail.php?id=12"` requests the item detail page for item 12.
- A form with `method="post"` sends its values in the request body. Posting is used for actions that change data, such as registering or submitting a claim.

PHP and HTML are mixed in the same page. The PHP code near the top often loads data first; the HTML below displays that data.

### CSS: appearance and responsive layout

`public/assets/css/style.css` controls colors, spacing, typography, buttons, cards, forms, tables, and layout. HTML elements use class names such as `item-card`, `btn`, or `navbar` so CSS can style them consistently.

Responsive rules are near the end of the stylesheet. For example:

```css
@media (max-width: 768px) {
    .items-grid, .items-grid-3 { grid-template-columns: 1fr; }
}
```

Read this as: “When the screen is 768 pixels wide or smaller, show these item grids as one column.” Other breakpoints adjust navigation, forms, tables, and footer layouts for smaller screens.

### JavaScript: small browser interactions

`public/assets/js/main.js` runs in the browser after the document loads. It handles the mobile navigation menu, account dropdown, pop-up modals, image previews, the lost/found selector, and disappearing flash messages. It does **not** save accounts or reports; PHP handles those server-side actions.

For example, the navigation code finds the button with ID `navToggle`, then adds a click listener. When the button is clicked, JavaScript toggles the CSS class `show`; CSS decides how an element with that class looks. For image previews, JavaScript reads a selected local file with `FileReader` and shows a temporary preview. The file is not uploaded until the user submits the form.

Modal code follows the same pattern: the HTML button has a `data-modal` value, JavaScript finds the element whose ID matches, and adds `show`. A CSS rule makes that modal visible. Closing the modal removes `show`.

---

## 4. Shared page frame

`includes/header.php` is included by most pages. It prints the HTML document start, navigation bar, sign-in links or signed-in user menu, flash messages, and the opening `<main>` element. `includes/footer.php` closes `<main>`, prints the footer, loads `main.js`, and closes the HTML document.

Because these are shared pieces, changing the navigation in `header.php` changes it on all pages that include the header.

The header uses PHP inside HTML:

```php
<?php if (is_logged_in()): ?>
    <!-- show the signed-in menu -->
<?php else: ?>
    <!-- show sign-in and register links -->
<?php endif; ?>
```

The `if` decides which HTML is sent. The visitor never sees the PHP itself; they receive the resulting HTML.

## 5. Search screen

`public/browse.php` is both the search page and the results page:

1. It reads filter values from `$_GET`, such as the keyword, location, type, category, date range, and page number.
2. It builds a database query using only filters the visitor supplied.
3. It asks MySQL for the total match count, then the matching page of items.
4. It prints the filters, item cards, and pagination links.

Changing a filter reloads the page with a new URL. This is a simple, useful approach for a PHP class project; it does not need a separate search API.

## 6. Other important screens

| Screen | What it shows/does |
|---|---|
| `public/index.php` | Home page and entry points into browsing/reporting |
| `public/item-detail.php` | Full item information; owners can edit/delete; eligible users can submit a claim |
| `public/auth/login.php` | Email/password sign-in form |
| `public/auth/register.php` | New account form |
| `public/user/report-item.php` | Form to create a lost/found report |
| `public/user/my-reports.php` | Signed-in user's reports and claims |
| `public/admin/dashboard.php` | Summary for administrators |
| `public/admin/claims.php` | Admin claim-review screen |
| `public/admin/items.php` and `users.php` | Admin item/user management |

---

# Part B — Backend (what the server does)

## 7. How a PHP request works

Suppose the browser requests `/user/report-item.php`:

1. The web server maps that URL to `public/user/report-item.php`.
2. PHP executes the file on the server.
3. `require_once` loads shared files such as the database connection and access-control helpers.
4. PHP checks the request and may read/write MySQL.
5. PHP either redirects the browser or prints a page.

`require_once __DIR__ . '/../../config/db.php';` means: start from the folder containing this PHP file, go up two folders, then open `config/db.php`. `__DIR__` is a PHP constant containing the current file's disk folder. `require_once` loads that file once, even if another included file also asks for it.

## 8. Database connection and settings

`config/db.php` reads database values through `env_value()` and creates one PDO connection in `get_db()`.

- **PDO** is PHP's built-in interface for connecting to databases.
- The `mysql:...` string tells PDO to use MySQL, which database to use, and the character encoding.
- `PDO::ERRMODE_EXCEPTION` makes database errors raise exceptions instead of silently failing.
- Prepared statements use placeholders like `:email`; the value is supplied separately. This helps prevent SQL injection.

`.env.example` shows the settings shape. Copy it to `.env`, put private values in `.env`, and do not commit `.env`. The project root stays outside `public/`, so the actual environment file is outside the web document root.

`composer.json` declares PHPMailer. Composer installs it into `vendor/`; `composer.lock` records the exact selected package version so another install can reproduce it.

## 9. The database tables

`schema.sql` describes the database. Main relationships:

```text
users 1 ─── many items
users 1 ─── many claims (as claimant)
items 1 ─── many claims
categories 1 ─── many items
```

- **users** stores a person's name, unique email, password hash, role (`user` or `admin`), and account status.
- **categories** stores options such as Electronics, Keys, and Clothing.
- **items** stores each lost/found report, its reporter (`user_id`), category, description, date, photo path, and status.
- **claims** stores a user's claim on a found item, the explanation/proof, and the admin review result.
- **auth_login_attempts** stores hashed email/IP identifiers and times for the sign-in rate limit.

An item row stores the reporter's numeric user ID, not a copy of all their details. The database foreign key connects that ID to `users.id`.

In the schema, `PRIMARY KEY` identifies a row uniquely. `AUTO_INCREMENT` asks MySQL to assign the next ID. `NOT NULL` means a value is required. `UNIQUE` prevents duplicates such as two accounts using the same email. `ENUM` limits a column to a known list of values. A `FOREIGN KEY` connects records in different tables and helps prevent invalid references. An `INDEX` is an extra lookup structure that can make common searches faster.

**Caution:** `schema.sql` drops and recreates the app tables. Import it only for a fresh database or after backing up data. For an existing installation, follow `README.md` and run the CLI setup script instead.

## 10. Forms, GET, POST, and redirects

- **GET** is normally for viewing/searching. Search filters appear in the URL and are read from `$_GET`.
- **POST** is for submitting a change. Form fields are read from `$_POST`.
- After a successful POST, a PHP page often sends a **redirect** with `header('Location: ...')` and then `exit`. This tells the browser to load another page and avoids resubmitting the form on refresh.

For example, report creation inserts a row, saves a success flash message, and redirects to the new item's detail page.

## 11. Authentication and roles

Authentication answers “Who is signed in?” Authorization answers “What may this signed-in person do?”

### Registration and sign-in

`public/auth/register.php` checks the submitted fields, hashes the password with `password_hash()`, and saves the account. It never stores the plain password.

`public/auth/login.php` looks up the email, checks the submitted password against the saved hash using `password_verify()`, checks that the account is active, and stores the user ID/name/email/role in the session. Admins go to the admin dashboard; regular users go to Browse.

### Sessions

A PHP **session** is server-side memory associated with the visitor's session cookie. The browser cookie identifies the session; the user ID and role are stored on the server in `$_SESSION`.

`includes/auth_check.php` starts the session, exposes helpers such as `is_logged_in()` and `is_admin()`, and implements `require_login()` / `require_admin()`. Protected pages call these before showing or changing private information. The access check is in the PHP page, not merely hidden from the menu.

### Passwords and form safety

- `password_hash()` turns a password into a one-way password hash.
- `password_verify()` checks a submitted password against that hash.
- `session_regenerate_id()` replaces the session ID when a user signs in.
- CSRF means a different website tricks a signed-in browser into submitting an unwanted action. `csrf_token()` puts a hard-to-guess hidden value in a form; `verify_csrf()` checks it on POST.
- `e()` HTML-escapes text before displaying it, reducing the chance that user-submitted text is interpreted as HTML/JavaScript.
- Login attempts are rate-limited by email/IP combination.

These security checks support the regular form flows; they are not extra screens the user needs to navigate.

## 12. Create, read, update, delete (CRUD)

CRUD is the common name for the four ways an application works with records:

| CRUD word | Meaning here | Example |
|---|---|---|
| Create | Add a record | `report-item.php` inserts an item |
| Read | Display records | `browse.php` selects and displays items |
| Update | Change a record | `edit-item.php` updates an owner's report |
| Delete | Remove a record | `delete-item.php` deletes an owner's report |

The admin pages also update statuses and review claims. SQL commands usually explain the action directly: `INSERT INTO` creates, `SELECT` reads, `UPDATE` changes, and `DELETE FROM` removes.

## 13. Claims: full server-side journey

1. A visitor opens a found item's detail page.
2. The page shows a claim form only if the item is active and the visitor is signed in, is not the reporter, and has not already claimed it.
3. The form POSTs to `public/user/submit-claim.php` with the item ID and claim text.
4. The server checks the CSRF token and repeats important rules (the item exists, is found and active, and belongs to someone else). Server checks matter because browser controls can be bypassed.
5. The server inserts a pending row in `claims` and sends notifications through PHPMailer.
6. An administrator reviews the claim in `public/admin/claims.php` and approves or rejects it.
7. PHP updates the claim, updates item status when approved, and emails the claimant about the decision.

The private proof description is for review and should not be posted publicly on item pages.

## 14. Email notifications

`includes/mailer.php` is a small shared wrapper around PHPMailer. It reads the SMTP server, port, encryption mode, username/password, and sender details from environment settings. The registration/report/claim pages call the mail helper when a notification is appropriate.

SMTP is the service used to hand an email to an email provider. PHPMailer formats and sends the message; it does not provide the email account itself. The project needs valid SMTP settings before real delivery can happen. If sending fails, the error is recorded in the PHP error log and the main database action is allowed to complete.

---

# Part C — Read the code with one example

## 15. Search, from input to results

Follow `public/browse.php`:

1. The HTML search input has `name="keyword"`.
2. The browser submits a GET URL such as `/browse.php?keyword=blue%20bag`.
3. PHP reads `$_GET['keyword']`, trims it, and limits its length.
4. PHP adds a `WHERE` condition only when a keyword was entered.
5. `$db->prepare($sql)` prepares the SQL. `$stmt->execute($params)` supplies the search term values.
6. `$stmt->fetchAll()` returns the matching rows as PHP arrays.
7. A `foreach` loop prints one item card per row. `e($item['title'])` escapes user-provided text before it becomes HTML.

In a PHP loop, `$item` is a variable holding the current row. `['title']` selects the title column from that row.

The important database lines have this shape:

```php
$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
```

Line by line:

1. `prepare($sql)` asks PDO/MySQL to prepare the SQL text with placeholders.
2. `execute($params)` supplies the values for those placeholders, such as the keyword and category.
3. `fetchAll()` takes all matching result rows and returns them as a PHP list of arrays.

The page then runs `foreach ($items as $item)` and prints one card for each row. A single-row lookup, such as looking up the account at login, uses `fetch()` instead.

## 16. Report a new item, from button to database

Follow `public/user/report-item.php`:

1. The browser requests the page. PHP includes database, helper, and session files.
2. `require_login()` sends a visitor to sign in if no active session exists.
3. PHP loads categories so the form can show a category dropdown.
4. When the visitor submits, `$_SERVER['REQUEST_METHOD'] === 'POST'` detects the form submission.
5. `verify_csrf()` checks that the form came from this site/session.
6. PHP reads and validates the form values.
7. If an image was selected, `upload_item_image()` checks the upload result, size, and detected image type, then moves it to `public/assets/uploads/`.
8. A prepared `INSERT INTO items` saves the report. `lastInsertId()` gives the new row's ID.
9. PHP sends an optional admin email, sets a flash message, and redirects to the new item's detail page.

The HTML `<form>` is the front-end portion. The POST checks, image move, SQL insert, email call, and redirect are the backend portion.

Here is the basic form-to-handler idea with non-essential markup left out:

```html
<form action="/user/report-item.php" method="post">
    <input name="title" required>
    <button type="submit">Publish report</button>
</form>
```

- `action` names the URL that handles the form.
- `method="post"` sends the form values in the request body.
- `name="title"` makes PHP receive this value as `$_POST['title']`.
- `required` gives the browser a basic empty-field check. PHP must still validate it because browser checks can be bypassed.

This project's actual form also includes `<?= csrf_token() ?>`, a hidden security value generated by PHP.

The PHP side uses a prepared insert similar to:

```php
$stmt = $db->prepare('INSERT INTO items (user_id, title) VALUES (:uid, :title)');
$stmt->execute(['uid' => $user['id'], 'title' => $title]);
$newId = (int)$db->lastInsertId();
header("Location: /item-detail.php?id=$newId");
exit;
```

Line by line: prepare the insert with placeholders; execute it with this user's ID and the submitted title; get the database's new row ID; tell the browser to open that item's page; stop the current request after the redirect.

## 17. PHP symbols you will see often

| Symbol/word | Beginner meaning |
|---|---|
| `$name` | A PHP variable |
| `$_GET`, `$_POST`, `$_SESSION` | Built-in arrays for URL values, form values, and session data |
| `->` | Call a method or access a property on an object, e.g. `$db->prepare()` |
| `=>` | Pair a key with a value in an array, e.g. `'email' => $email` |
| `??` | Use a fallback if the left value is missing, e.g. `$_GET['page'] ?? 1` |
| `===` | Compare both value and type |
| `foreach` | Repeat once for each item in a list |
| `function` | Define reusable code with a name |
| `return` | Give a result back to the code that called the function |
| `exit` | Stop running the current PHP request |
| `require_once` | Include another PHP file one time |
| `WHERE` | SQL condition for selecting/changing only matching rows |
| `:email` | A named SQL placeholder filled safely by a prepared statement |
| `<?= ... ?>` | Short PHP syntax for outputting a value into the HTML |
| `empty($value)` | Check whether a value is missing or blank |
| `trim($text)` | Remove spaces at the start/end of text |
| `filter_var(..., FILTER_VALIDATE_EMAIL)` | Check whether text looks like a valid email address |

---

# Part D — A good learning order

## 18. Suggested reading order

Read in this order and do not try to memorize everything at once:

1. `public/index.php` — recognize the HTML mixed with PHP.
2. `public/assets/css/style.css` — find the styles for a class used by a page.
3. `public/assets/js/main.js` — understand the mobile menu and modal interactions.
4. `includes/header.php` and `includes/footer.php` — see how pages share layout.
5. `public/browse.php` — follow a GET search into a database query and back into HTML.
6. `public/user/report-item.php` — follow a POST form into an INSERT and redirect.
7. `config/db.php` and `includes/functions.php` — learn the shared PDO connection and helpers.
8. `public/auth/register.php`, `public/auth/login.php`, then `includes/auth_check.php` — learn account creation and sessions.
9. `public/user/submit-claim.php`, then `public/admin/claims.php` — follow the claim workflow.
10. `includes/mailer.php` — see how notifications are sent through SMTP.
11. `schema.sql` — connect the PHP table/column names to the database design.

For each page, ask four questions: **What does the browser send? What does PHP check? Which table does it read/write? What does the browser see afterward?**

## 19. Simple presentation explanation

> “The front end is HTML, CSS, and a little JavaScript. The forms send search values with GET and changes with POST. PHP checks the values and the user's session, then uses PDO prepared statements to read or update MySQL. The database stores users, item reports, categories, and claims. After actions, PHP redirects to the next page and may show a flash message. PHPMailer sends email notices through SMTP when SMTP settings are configured.”

## 20. Setup reminders

See `README.md` for full setup steps. The important ideas are:

- The server's web root must be `public/`.
- `.env.example` is a sample; copy it to a private `.env` and fill in local settings.
- Composer installs PHPMailer from `composer.lock`.
- `config/setup.php` is run from the command line, not from a browser.
- Import `schema.sql` only when creating a fresh database. It drops and recreates the project tables.
- Email notices need real SMTP host/account settings; placeholder settings cannot deliver email.
