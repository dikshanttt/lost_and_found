# Lost & Found Hub (CivicFind): Beginner's Guide

This guide explains the project in the order that is easiest to learn: first the parts people see in the browser, then the PHP code that handles their actions, and finally the database that stores the information.

You do not need to understand every line before you can explain the project. Start with the big picture, follow one example such as making a report, then come back to the technical details.

## The project in one minute

CivicFind is a lost and found website. A visitor can browse reports and search for an item. A signed-in user can add, edit, or delete their own reports and submit a claim for an active found item. An administrator can review claims, manage reports, and manage accounts.

The browser displays HTML and CSS. JavaScript handles a few small interactions. PHP runs on the server, checks the request, reads or updates MySQL, and sends a page back to the browser. MySQL keeps accounts, item reports, claims, and categories after a page is closed.

There is no automatic item matching in this project. Search is based on the words and filters entered by the user. There is no automatic identity verification either: an administrator reads the claim details and makes a decision.

## How to read this guide

1. Read Part 1 first. It explains what appears on the screen.
2. Read Part 2 next. It explains what PHP does after someone clicks or submits a form.
3. Read Part 3 to understand where the records are stored.
4. Use the example flows and glossary to practice explaining the project aloud.

# Part 1: Front end — what the user sees

## 1. What is the front end?

The front end is the part of a website shown in a browser. In this project it is made from:

- HTML, which gives the page its structure: headings, links, forms, buttons, and tables.
- CSS, which controls its appearance: colors, spacing, layout, and how the page adjusts to smaller screens.
- JavaScript, which adds small interactions without changing the main PHP and database work.
- PHP-generated HTML, which fills the page with information from MySQL, such as item names and categories.

A page ending in .php can contain both PHP and HTML. PHP runs on the server first. The browser receives the resulting HTML; it does not receive the PHP source code.

### Reading PHP inside a page

~~~php
<h1><?= e($item['title']) ?></h1>
~~~

The short tag <?= ... ?> means “run this PHP and print the result here.” The variable $item contains data for one report. The ['title'] part selects its title value. The e() helper escapes special HTML characters so the title is shown as text.

A loop can repeat the same HTML for every database result:

~~~php
<?php foreach ($items as $item): ?>
    <p><?= e($item['title']) ?></p>
<?php endforeach; ?>
~~~

Read this as: “For each report in items, call the current report item and print its title.” If the database returned five reports, PHP outputs five paragraphs. The browser receives the paragraphs, not the PHP loop.

## 2. Where the front-end files are

- public/index.php is the home page.
- public/browse.php displays searchable item reports.
- public/item-detail.php displays one report and, when appropriate, a claim form.
- public/auth/ contains sign-in, registration, and sign-out pages.
- public/user/ contains report forms and the user's report and claim pages.
- public/admin/ contains the administrator pages.
- public/assets/css/style.css contains the site's styles and responsive rules.
- public/assets/js/main.js handles the mobile menu, account menu, dialogs, image preview, and flash message timing.
- includes/header.php and includes/footer.php are shared page parts, such as the navigation bar and footer.

The public folder is intended to be the web server's document root. That means visitors should reach the pages and assets inside public, while configuration and private helper files stay outside the web root.

## 3. Shared page layout

Most pages include the shared header and footer. The header shows the CivicFind name, navigation links, account controls, and any temporary success or error message. The footer shows common links at the bottom of the page.

Using shared header and footer files means that the navigation does not need to be copied into every page. If a shared link needs to change, it can be changed in one place.

The header also checks whether someone is signed in. Signed-in visitors see an account menu. Admin accounts also see a link to the admin dashboard. The menu is only navigation; the PHP admin pages still check the account's role before showing private data.

## 4. Home page: public/index.php

The home page has several sections:

1. A search box for words and a location.
2. A category list with the number of active reports in each category.
3. A small list of the newest active reports.
4. A short explanation of the basic steps: add a report, search reports, and submit a claim if a found item may be yours.

The PHP at the top asks MySQL for category counts and up to four recent active reports. The HTML later loops through those results and makes a card for each one. If the database returns no active reports, the page shows an empty-state message instead of an empty card list.

The home page search form uses GET. When someone searches, the browser puts values such as keyword and location in the URL and opens public/browse.php. GET is suitable for search because it reads information and does not change a database record.

## 5. Browse and search: public/browse.php

The browse page lets a visitor narrow reports using:

- A keyword, searched in the title, description, and location.
- Report type: lost or found.
- Category.
- Location text.
- How recently the report was added.

The filter values appear in the URL, for example:

~~~text
/browse.php?keyword=blue%20bag&type=found&page=1
~~~

The page shows up to nine results at a time. If there are more results, the page displays links for moving between result pages. Search and filters can be used without signing in.

The date filter is based on when the report was added to the site, using the created_at field. It is not a filter for the date the item was lost or found; that date is saved separately as item_date.

The search does not guess which item is a match. It simply returns records whose saved text and selected filter values meet the search conditions.

## 6. Item details: public/item-detail.php

A report card links to a details page using the report's database ID, for example:

~~~text
/item-detail.php?id=12
~~~

The page uses that ID to load the report, its category, and the name of the person who posted it. It displays the description, location, dates, report type, status, and photo when one exists.

The available buttons depend on the report and the visitor:

- The person who posted the report, or an admin, can edit or delete it.
- A signed-in person viewing someone else's active found item can open the claim form.
- A visitor who is not signed in is asked to sign in before claiming.
- A person cannot claim their own report.
- Lost reports show a link to add a found-item report.

The claim form opens in a dialog. JavaScript shows or hides that dialog, but PHP still receives and checks the form when it is submitted.

## 7. Reporting an item: public/user/report-item.php

A signed-in user can report either a lost item or a found item. The form asks for the report type, item name, category, date, location, description, and optionally a photo.

When the user submits the form, the browser sends a POST request. POST is used because this request creates a database record. PHP checks the required fields and photo, saves the report, and then redirects the user to its details page.

A report's type describes what happened: lost or found. Its status describes what is happening with the report: active, claimed, returned, or closed. These are separate ideas.

## 8. Account pages

- public/auth/register.php creates a regular user account.
- public/auth/login.php checks an email and password and starts a signed-in session.
- public/auth/logout.php signs the visitor out using a POST form.
- public/user/my-reports.php shows the signed-in user's own reports and claims.

A regular registration form cannot choose the admin role. It always creates a user account. The initial admin account is created through the setup process described in Part 2.

## 9. Responsive layout and JavaScript

The CSS includes rules for different screen widths. On a narrow screen, layouts can use fewer columns, stack content vertically, and show a menu button instead of the full navigation row. This is what responsive design means: the page adjusts to the device's screen.

The JavaScript in public/assets/js/main.js handles a few browser interactions:

- Opens and closes the small-screen navigation menu.
- Opens and closes the signed-in account menu.
- Opens and closes claim dialogs.
- Shows a preview after an image is selected.
- Hides temporary status messages after a short time.
- Updates the selected look of the lost/found choice on the report form.

JavaScript improves how the page feels, but it is not the authority for saving or protecting data. PHP checks important requests on the server, because a visitor can disable or bypass browser-side JavaScript.

# Part 2: Back end — what happens on the server

## 10. What is the back end?

The back end is the code and database work that happens on the server. In this project, PHP handles page requests, checks form values, decides whether a visitor has permission, and asks MySQL to read or change records.

A normal page request works like this:

1. The browser requests a URL.
2. The web server runs the matching PHP file.
3. PHP loads shared functions and configuration it needs.
4. The PHP file reads the request and may ask the database for information.
5. PHP combines the information with HTML.
6. The server sends the finished page to the browser.

When a form is submitted, the PHP file first checks and validates the submitted values. It then either displays an error or changes the database and redirects to another page.

## 11. Project folders and important files

~~~text
LostAndFound/
├── config/
│   ├── db.php                 Reads settings and opens the MySQL connection
│   └── setup.php              First-run setup, categories, and initial admin
├── includes/
│   ├── auth_check.php         Sessions and access checks
│   ├── functions.php          Shared helpers used by pages
│   ├── header.php             Shared navigation and top of page
│   ├── footer.php             Shared footer and JavaScript include
│   └── mailer.php             Email delivery through PHPMailer and SMTP
├── public/
│   ├── index.php              Home page
│   ├── browse.php             Search and filter reports
│   ├── item-detail.php        One report and claim form
│   ├── auth/                  Sign-in, registration, sign-out
│   ├── user/                  User reports, edits, deletion, claims
│   ├── admin/                 Admin dashboard and management pages
│   └── assets/                CSS, JavaScript, and uploaded photos
├── docs/                      Project guides and written documents
├── schema.sql                 Creates the database tables and categories
├── composer.json              Lists PHP libraries, including PHPMailer
├── .env.example               Example of local settings; copy to .env
└── README.md                  Project setup and usage notes
~~~

The folders have different jobs. config contains setup and database configuration. includes contains code shared by many pages. public contains the pages a browser opens. docs contains explanations and project documents.

## 12. Database and tables

The database is named lost_found_hub. schema.sql creates these main tables:

### users

Stores each account's name, email, password hash, role, status, and timestamps. Both regular users and admins are stored in this table. The role column distinguishes them: user or admin.

Passwords are not stored as readable text. password_hash stores a one-way password hash.

### categories

Stores choices such as Electronics, Documents, Keys, and Clothing. An item report refers to one category using category_id.

### items

Stores lost and found reports. Important columns include:

- user_id: the account that created the report.
- category_id: the report's category.
- type: lost or found.
- title, description, and location: what the user entered.
- item_date: the date the item was lost or found.
- image_path: optional path to the uploaded image.
- status: active, claimed, returned, or closed.
- created_at: when the report was created on the site.

### claims

Stores claim requests for found items. It records the item, claimant, claim message, extra proof details, status, and any admin review note. A claim's status is pending, approved, or rejected.

### auth_login_attempts

Stores short-lived, hashed identifiers for failed login attempts. The login page uses it to slow repeated password guessing. It does not store the user's plain password.

## 13. How the tables are connected

~~~text
users      1 ─── many items       One user can post many item reports.
users      1 ─── many claims      One user can submit many claims.
items      1 ─── many claims      A found item can receive claim requests.
categories 1 ─── many items       Many reports can use the same category.
users      1 ─── many reviews     An admin account can review many claims.
~~~

A foreign key is a database rule that connects one table to another. For example, items.user_id must point to an existing users.id. This prevents an item from being saved with an owner account that does not exist.

The project uses cascading deletion for some links. For example, deleting an item also deletes claims attached to that item. TRUNCATE is different from DELETE: MySQL can refuse to truncate a table that another table references, even when that other table has no rows. That is why this project's referenced tables should be cleared with DELETE in child-first order.

## 14. Connecting PHP to MySQL: config/db.php

The main functions in config/db.php are load_project_env(), env_value(), and get_db().

- load_project_env() reads the local .env file once, if it exists.
- env_value('DB_HOST') asks for one setting, such as the database host.
- get_db() creates and returns a PDO connection to MySQL.

PDO is PHP's database connection tool. It lets PHP send SQL and receive results. The connection is cached in a static variable, so get_db() reuses the same connection during one request instead of opening a new connection every time.

The connection uses utf8mb4 so text can include a wide range of characters. It reports database errors as exceptions and uses real prepared statements. Most queries that include user input use prepare() and execute() so the input is sent as data rather than being joined directly into SQL.

### A short database example, line by line

The report page follows this general pattern when saving a new report:

~~~php
$stmt = $db->prepare(
    'INSERT INTO items (user_id, category_id, type, title) VALUES (:uid, :category, :type, :title)'
);
$stmt->execute([
    'uid' => $user['id'],
    'category' => $categoryId,
    'type' => $type,
    'title' => $title,
]);
~~~

This is a shortened teaching example based on report-item.php. The real query also saves the description, location, date, and image path.

1. $db is the PDO connection to MySQL.
2. prepare() gives the database the shape of an INSERT query and marks values with placeholders such as :uid.
3. execute() supplies the real values for those placeholders.
4. The left side of each array entry names a placeholder. The right side supplies its PHP value.
5. MySQL saves one new row in the items table.

Keeping SQL and form values separate helps prevent a value typed by a visitor from being treated as SQL instructions.

## 15. Settings in .env

The .env file holds local settings such as database credentials and SMTP credentials. The example file is .env.example. A developer copies the example to .env and enters their own local values.

The .gitignore file excludes .env from Git so passwords and secret keys are not uploaded with the source code. The public/.htaccess file also denies direct web requests to .env files. Keep both protections in place and never paste real secrets into a public repository or class presentation.

The project uses settings such as DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, APP_KEY, ADMIN_EMAIL, ADMIN_PASSWORD, SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, MAIL_FROM_ADDRESS, and APP_BASE_URL.

The values in .env.example are examples. They are not working credentials. Use unique local values, especially for APP_KEY, ADMIN_PASSWORD, and SMTP_PASSWORD.

## 16. First-time setup and the initial admin

schema.sql creates the database tables and inserts the starter categories. Important: the current schema.sql contains DROP TABLE statements. Running it again on a database with real data will delete and recreate those tables. Do not rerun it on data you want to keep.

config/setup.php is a separate setup helper. It is designed to be run from the command line, not opened as a normal website page. It checks that the database is available, adds the starter categories if the category table is empty, creates an initial admin from valid ADMIN_EMAIL and ADMIN_PASSWORD settings, and prepares login-attempt support.

The initial admin is created with PHP's password_hash(). Regular registration always creates a user role. Admins and users share the users table; a separate admin table is not needed for this project.

## 17. Registration: public/auth/register.php

Registration follows this path:

1. The browser sends the form to public/auth/register.php with POST.
2. The page checks the CSRF token.
3. PHP trims the name and email and checks required values, email format, field lengths, password length, and password confirmation.
4. PHP checks whether the email is already in users.
5. password_hash() turns the submitted password into a one-way hash.
6. A prepared INSERT saves the name, email, hash, user role, and active status.
7. The app sends a welcome email if email settings are working.
8. It creates a signed-in session and redirects the new user to Browse.

The form asks for a password between 12 and 72 characters, and PHP checks the submitted length. The confirmation field is compared with the password but is not stored.

### Why are there two password functions?

- password_hash($password, PASSWORD_DEFAULT) is used when creating an account. It creates a one-way hash for storage.
- password_verify($enteredPassword, $savedHash) is used at login. It checks whether the entered password matches the saved hash.

The app does not decrypt the hash. Hashing is one-way: it is for checking a password, not recovering the original text.

## 18. Login and role-based destination

Login is in public/auth/login.php. PHP checks the email and submitted password against the saved account. It uses password_verify(), which compares the password to its saved hash without needing the original password.

After a valid login, login_user() regenerates the session ID and saves the account ID, name, email, and role in the session. The session is how later page requests remember that the same visitor has signed in.

Then the page checks the account role:

- role admin redirects to /admin/dashboard.php.
- role user redirects to /browse.php.

The login page also records failed attempts using hashed email and network address values. After five attempts for that pair in 15 minutes, it asks the visitor to wait. Old records are removed after a day. APP_KEY is used to create those hashes, so it should be changed from its example default.

## 19. Sessions, sign out, and protected pages

A session is a small server-side record linked to a browser cookie. The cookie identifies the session; account details are kept in PHP's session data.

includes/auth_check.php starts the session and defines shared functions:

- is_logged_in() checks whether the session has a user ID.
- current_user() returns the signed-in user's saved session details.
- is_admin() checks whether the session role is admin.
- login_user() regenerates the session ID and stores user details.
- logout_user() clears the session and removes its cookie.
- require_login() sends unsigned visitors to the sign-in page and checks that their account still exists and is active.
- require_admin() first requires a valid active login, then checks the admin role.

The protected user pages call require_login(). The admin pages call require_admin(). Hiding a link in the menu is not enough to protect a page; the server-side check is what denies access when someone types an admin URL directly.

For example, the first part of an admin page looks like this:

~~~php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();
~~~

require_once loads the shared login-check functions if they have not already been loaded. __DIR__ means “the folder containing this PHP file”; the relative path goes up to the project root and into includes. require_admin() then checks the current session and role. If the check fails, it redirects the visitor and stops running the admin page.

Sign out is submitted as a POST form with a CSRF token. The logout page checks that token, clears the session, and redirects to the home page.

## 20. Adding a report and uploading an image

The report form is public/user/report-item.php, but it requires a signed-in account. On submit, the server checks required text fields and the optional image.

The upload_item_image() helper in includes/functions.php checks that the upload succeeded, is no larger than 2 MB, and has a detected MIME type of JPEG, PNG, or WebP. It gives the uploaded file a generated name and stores it under public/assets/uploads/. The database stores the relative image path, not the image bytes.

The server does not trust a file's name or browser-provided content type as proof that it is an image. It checks the file contents with PHP's file information tool (finfo).

After a valid report is inserted into items, the page gets the new ID and redirects to that report's detail page. If the admin notification email is configured, it also sends an email about the new report.

## 21. Edit and delete reports

public/user/my-reports.php queries the current user's reports and claims. It uses the session user ID, so it only lists reports belonging to that account.

public/user/edit-item.php loads the requested item ID. It checks that the signed-in user owns the report or is an admin. On a valid POST request, it updates the editable fields in the items table. The report's lost/found type cannot be changed through that form.

public/user/delete-item.php accepts a POST request, checks the CSRF token, and checks that the current user owns the report or is an admin. It deletes the report from MySQL and removes its uploaded image file if one exists. Claims for that item are also removed by the database's foreign-key cascade.

## 22. Search, filters, and pages of results

public/browse.php reads filter values from the URL using PHP's $_GET array. It limits some text length and only accepts the known lost/found values for report type.

The page builds a list of SQL conditions only for filters the visitor selected. It prepares the query and binds the filter values before execution. This lets one page support many filter combinations without writing a separate SQL query for every combination.

The database first counts the matching reports. paginate() in includes/functions.php uses the total, page number, and nine-results-per-page setting to calculate how many pages there are and which records to fetch. SQL LIMIT and OFFSET retrieve only the current page's results.

Search fields:

- keyword checks title, description, and location.
- location checks the location text.
- type checks whether a report is lost or found.
- category checks category_id.
- date_range checks when the record was created, not item_date.

## 23. Claims and the admin review

A claim is a request from a signed-in user who believes an active found item belongs to them.

### Submitting a claim

1. The user opens the found item's detail page.
2. They enter a claim message and extra identifying details.
3. The browser sends a POST request to public/user/submit-claim.php.
4. PHP checks the CSRF token, that the item exists, that it is found and active, that the user is not the report owner, and that the user has not already claimed it.
5. PHP inserts a claims row with status pending.
6. The system emails the claimant and, if configured, the admin.
7. The user is redirected to the Claims tab in My Reports.

Submitting a claim does not immediately mark the report as claimed. It remains awaiting review until an admin makes a decision.

### Reviewing a claim

public/admin/claims.php requires an admin account. It displays claims, the claimant's message, their extra details, and an optional note from the admin.

When the admin approves a claim, PHP changes the claim status to approved, records who reviewed it and when, saves the admin note, and changes the item status to claimed. When the admin rejects a claim, it changes the claim status to rejected and saves review details; the code does not change the item's status in the reject branch. The claimant receives a review email if email delivery is configured.

The administrator is making a manual decision based on the information submitted. The app does not independently prove that a claimant owns an item.

## 24. Admin pages

Every page in public/admin/ starts with require_admin().

- dashboard.php shows counts and recent claims and reports.
- claims.php lets the admin view and approve or reject claims.
- items.php lists reports and lets the admin change item status, edit a report, or delete it.
- users.php lists accounts and lets the admin block/unblock an account or change its role. It does not offer those actions on the current admin's own row.

If an admin blocks a user, require_login() checks the account status on later protected requests and ends that user's session. This is why checking the database status matters even after login.

# Part 3: Shared building blocks

## 25. Useful functions in includes/functions.php

- e($text) escapes text for safe display in HTML. For example, it prevents a submitted title containing HTML tags from being treated as page markup.
- csrf_token() creates and prints a hidden form field containing a random session token.
- verify_csrf() checks that a POST form sent the expected token before a change is made.
- set_flash() saves a temporary message for the next page.
- get_flash() displays and then clears temporary messages.
- upload_item_image() checks and saves an image upload.
- type_badge() and status_badge() build small labels for reports and claims.
- get_categories() reads category rows from MySQL.
- paginate() calculates result page numbers and SQL offset.
- category_icon() picks an emoji for a known category.
- time_ago() turns a saved date and time into readable text such as “3 hours ago”.

The main purpose of shared helpers is to avoid repeating the same small piece of code in many pages.

## 26. Email notifications: includes/mailer.php

The project uses PHPMailer to send email through an SMTP server. SMTP is the service that accepts outgoing email from an application. The settings are read from .env.

send_email() checks that Composer's vendor/autoload.php exists, PHPMailer is available, and SMTP_HOST is configured. It then sets up the SMTP connection, sender, recipient, subject, HTML message, and plain-text alternative.

email_user() makes a basic HTML email and escapes the message before placing it into HTML. The app calls email functions after registration, a new report, a new claim, and an admin claim decision. If email is not configured or sending fails, the helper returns false and writes an error to the server log; it does not undo the saved report or claim.

The email setup needs working SMTP details. The example SMTP values are placeholders and will not send mail by themselves.

# Part 4: Safety and responsible explanation

## 27. Security features in simple terms

- Prepared statements: keep user-entered values separate from SQL instructions.
- Password hashing: store a one-way hash instead of the user's readable password.
- HTML escaping: display text as text instead of allowing it to become HTML or JavaScript.
- CSRF tokens: check that a form submission came from a page that was opened with this session.
- Session checks: remember a login while checking whether its account is still active.
- Role checks: require an admin role on admin pages.
- Upload checks: allow only supported image types up to the configured size and use a generated filename.
- .env and Git ignore rules: keep local credentials out of version control and direct browser requests.

These features lower common risks, but no short project should be described as completely secure. For a real public deployment, it also needs correct HTTPS and server configuration, protected secrets, backups, and ongoing maintenance.

## 28. A few project details to explain honestly

- The site searches saved report text and filters; it does not automatically match lost and found reports.
- Claims are reviewed by an admin; there is no automatic proof of ownership.
- Email notifications depend on working SMTP settings in .env.
- The first admin is created through the setup process, while public registration creates regular users.
- A report's status and type are different: found/lost is the type; active/claimed/returned/closed is the status.
- The home page displays recent active reports. The browse page is the place to search and filter reports.
- schema.sql is for initial database creation and contains DROP TABLE commands. Do not run it again if you need to keep existing database records.

# Part 5: Follow one complete example

## Example: A person reports a found backpack

1. The person signs in or creates an account.
2. They open the Report Found page.
3. The browser sends the form to public/user/report-item.php using POST.
4. PHP checks the form and its CSRF token, verifies the optional photo, and prepares an INSERT statement.
5. MySQL creates an items row with type found and status active.
6. PHP may send an email to the configured admin address.
7. PHP redirects the person to the new report's details page.
8. Another signed-in user who thinks the backpack is theirs can submit a claim.
9. MySQL saves a claims row with status pending.
10. An admin reviews the claim in the admin page.
11. If approved, the claim becomes approved and the item becomes claimed. If rejected, the claim becomes rejected.

This example shows the main parts working together: browser form, PHP validation, helper functions, MySQL, email, redirect, and role-protected admin review.

## Example: Trace a sign-in from start to finish

1. The visitor enters an email and password in the browser.
2. The form sends those values by POST to public/auth/login.php.
3. PHP checks the CSRF token and reads the fields.
4. PHP looks up the account by email using a prepared SQL statement.
5. password_verify() compares the entered password with password_hash in the database.
6. login_user() creates a fresh session ID and saves the user's ID, name, email, and role in the session.
7. If the role is admin, the browser is redirected to the admin dashboard. Otherwise, it is redirected to Browse.
8. On a later protected page request, require_login() checks the user still exists and is active. An admin page also checks the role.

# Part 6: Beginner glossary

- Browser: the program used to visit the site, such as Chrome or Firefox.
- Server: the computer or software that runs PHP and sends pages to the browser.
- Front end: the page structure, styling, and browser interactions.
- Back end: server-side code and database work.
- HTML: page structure.
- CSS: page appearance and layout.
- JavaScript: small actions handled in the browser.
- PHP: server-side programming language used by this project.
- MySQL: database software that stores the site's information.
- Table: a database collection of similar records, like users or items.
- Row: one record in a table.
- Column: one value stored for each record, such as email or status.
- Primary key: a unique ID for one row, such as users.id.
- Foreign key: a rule linking one table to a row in another table.
- Query: an instruction sent to the database, often written in SQL.
- Prepared statement: a query where SQL instructions and user values are sent separately.
- Session: server-side data that helps the app remember a signed-in visitor.
- Role: an account type used for permissions, such as user or admin.
- GET: a request commonly used to read pages or search; its values can appear in the URL.
- POST: a request commonly used to submit a form or make a change.
- Redirect: an instruction telling the browser to open another URL.
- SMTP: the protocol/service used here to send outgoing email.
- Responsive design: page layout that adjusts to different screen sizes.

# Part 7: Questions friends or a teacher may ask

### What does CivicFind do?

It lets people post lost and found item reports, search reports, and submit claims for found items. An admin reviews those claims.

### What is the difference between the front end and back end?

The front end is what a person sees and uses in the browser. The back end runs on the server and handles validation, permissions, email, and database work.

### What happens when a user submits a report?

The browser sends the form to PHP. PHP checks the fields and optional image, inserts a row in MySQL, and redirects to the new report.

### Why are passwords hashed?

So the database does not contain the original readable passwords. During login, PHP checks the entered password against the stored hash.

### Why do admins and normal users share a table?

They both have the same basic account information. The role column says whether an account is a user or an admin, so a second table is unnecessary here.

### How does the app keep a normal user out of the admin pages?

Each admin PHP page calls require_admin(). The function checks that the visitor is signed in and that their current role is admin.

### How does search work?

The browser sends the search and filter choices in the URL. PHP uses them to build a prepared database query and shows matching reports in pages of nine.

### Does the site automatically find matching lost and found reports?

No. People search the reports using words and filters. There is no automatic matching feature.

### What happens when someone submits a claim?

PHP checks the request and saves it as pending. An admin reviews it. Approval marks the claim approved and the item claimed; rejection marks the claim rejected.

### What does the database store?

Accounts, categories, lost and found reports, claims, and recent failed-login tracking information.

### What does responsive mean?

The layout changes to fit different screen widths, including phones and larger computer screens.

### What does PHPMailer do?

It connects the PHP project to an SMTP email service so the app can send welcome, report, and claim notifications.

# A short presentation outline

You can explain the project in this order:

1. “CivicFind is a website for posting and searching lost and found item reports.”
2. “The front end is made with PHP-generated HTML, CSS, and a little JavaScript.”
3. “PHP checks form submissions and account permissions on the server.”
4. “MySQL stores users, categories, item reports, and claims in related tables.”
5. “Users can search, report items, and make claims. Admins review claims and manage the site.”
6. “Passwords are hashed, forms use CSRF tokens, database inputs use prepared statements, and emails are sent through PHPMailer when SMTP is configured.”
7. “Search uses filters; the project does not automatically match items.”

Use your own words and be ready to open the matching page or file if someone asks where a feature is implemented. It is fine to say that you are still learning a section and explain what you have understood so far.

