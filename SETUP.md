# TaskFlow Backend — Setup & Connection Notes

## Stack
PHP (MySQLi + firebase/php-jwt) + MySQL/MariaDB, no framework/router —
plain folder-per-resource PHP scripts.

## 1. Place the project in XAMPP
Copy this whole folder into your XAMPP `htdocs` directory as:

    C:\xampp\htdocs\taskflow_backend

(If you use a different folder name, update the frontend's
`src/services/api.js` `baseURL` to match.)

## 2. Database
1. Start Apache + MySQL in the XAMPP control panel.
2. Open phpMyAdmin → Import → select `todo_system(2).sql`.
   This creates the `todo_system` database with all 5 tables
   (users, groups, tasks, logs, preferences) and some seed data.
3. `database.php` already points at `localhost` / `root` / no password /
   `todo_system` — XAMPP's defaults. Change these only if your local
   MySQL setup differs.

## 3. Composer dependency (firebase/php-jwt)
The `vendor/` folder is already included, so no `composer install` is
strictly required. If you ever delete `vendor/`, run:

    composer install

from this folder.

## 4. CORS
Every endpoint now starts with `require_once "cors.php";` (or
`../cors.php` for subfolders). This is what lets the React dev server
(`http://localhost:5173`) talk to PHP across origins, including the
preflight `OPTIONS` request the browser sends automatically whenever a
request carries an `Authorization` header.

**If you change the frontend's dev port**, update the allowed origin in
`cors.php` to match.

## 5. Quick endpoint map
| Feature        | Endpoint                                  | Auth required |
|-----------------|-------------------------------------------|---------------|
| Login            | POST `/Authentication/Login.php`         | no |
| Logout            | POST `/Authentication/Logout.php`         | no |
| Verify token       | GET `/Authentication/Verify.php`          | yes |
| Tasks CRUD          | `/Tasks/index.php` `create.php` `update.php` `delete.php` | yes |
| Users CRUD (admin)   | `/users/index.php` `create.php` `update.php` `delete.php` | yes |
| Groups CRUD (admin)  | `/Groups/index.php` `create.php` `update.php` `delete.php` | yes |
| Dashboard stats        | GET `/Dashboard.php`                  | yes |
| Preferences (theme etc) | GET/POST `/Preferences.php`           | yes |
| Activity logs (admin)   | GET `/logs.php`                       | yes |

All folder names are case-sensitive as written above (`Tasks`, `Groups`,
`users` — yes, `users` is lowercase, that's intentional/existing).

## Test with Thunder Client first
1. POST to `/Authentication/Login.php` with a seeded user's email/password
   (see `todo_system(2).sql` for seeded accounts, or use
   `hash_password.php` to generate a new bcrypt hash for a test user you
   insert via phpMyAdmin).
2. Copy the `token` from the response.
3. For every other request, add header: `Authorization: Bearer <token>`.
4. Try GET `/Tasks/index.php`, GET `/users/index.php`, etc.

If Thunder Client works but the browser doesn't, the issue is CORS —
double check the dev server port matches what's in `cors.php`.
