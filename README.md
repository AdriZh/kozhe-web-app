# KOZHE: Web Application 🥖

A web prototype for **KOZHE**, a food-waste startup I founded at 15. It connects cafes and restaurants with people who buy unsold food at a discount before it expires or the shift ends. KOZHE piloted with 20 venues in Almaty and ranked Top 10 in Central Asia at IT Fest 2024.

**Stack:** PHP · MySQL · HTML/CSS · JavaScript · UI designed in Figma

## Features
- **User registration and login** with `password_hash()` / `password_verify()` (bcrypt) and session-based authentication.
- **Prepared statements** for all user input, to prevent SQL injection.
- **CSRF tokens** on the auth forms (`index.php`).
- **Input validation:** required fields, matching passwords, minimum length, duplicate username/email check.
- **Product cards and a JavaScript carousel** for discounted food listings (`home.php`).
- **User search and sorting** with whitelisted sort columns.

## Pages
| File | Purpose |
|---|---|
| `home.php` | Main marketplace page: product cards, carousel, user search |
| `login.php` | Login and sign-up |
| `index.php` | Account page with CSRF-protected registration and login, plus logout |
| `schema.sql` | Database setup |

## Run locally
1. Install [XAMPP](https://www.apachefriends.org/) (Apache + MySQL).
2. Copy this folder into `htdocs/`.
3. Run `schema.sql` in phpMyAdmin.
4. Open `http://localhost/kozhe-web-app/home.php`.

## Product work behind it
Before coding, I ran customer research and wrote the system requirements, user flows, database model and test plan.
