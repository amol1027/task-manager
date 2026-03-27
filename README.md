# TaskFlow — Task Management App

A clean, minimal full-stack Task Manager built with **vanilla PHP**, **MySQL (PDO)**, and **Tailwind CSS via CDN**.

---

## Features

- **Auth**: Register, login, logout with bcrypt password hashing and session hardening
- **Tasks**: Create, read, update, delete (CRUD) your own tasks
- **Dashboard**: Filterable task grid with summary stat cards and overdue highlighting
- **Security**: CSRF tokens on all forms, PDO prepared statements, ownership checks
- **Profile**: Update your name and change your password

---

## Requirements

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL/MariaDB + PHP 8.x)
- A modern browser

---

## Setup Instructions

### 1. Place the project files

Ensure the project folder is at:
```
C:\xampp\htdocs\task manager\
```

### 2. Start XAMPP

Open the **XAMPP Control Panel** and start:
- **Apache**
- **MySQL**

### 3. Import the database

1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar to create a new database — OR just run the SQL below.
3. Click the **SQL** tab and paste the contents of `schema.sql`, then click **Go**.

The script will create the `task_manager` database, both tables, and a sample user + tasks.

**Sample login credentials:**
| Email | Password |
|---|---|
| `alice@example.com` | `Password@123` |

### 4. Configure the database (if needed)

Open `config/db.php` and adjust these constants if your MySQL credentials differ from the XAMPP defaults:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'task_manager');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default: empty password
```

### 5. Open the app

Visit: [http://localhost/task%20manager/](http://localhost/task%20manager/)

You'll be redirected to the login page automatically.

---

## File Structure

```
task manager/
├── config/
│   └── db.php              # PDO database connection
├── auth/
│   ├── register.php        # Registration + validation
│   ├── login.php           # Login + session management
│   └── logout.php          # Session destroy + redirect
├── pages/
│   ├── dashboard.php       # Task overview + filters + delete
│   ├── add_task.php        # Add new task
│   ├── edit_task.php       # Edit existing task
│   └── profile.php         # Update name + change password
├── includes/
│   ├── header.php          # Nav, session guard, flash messages
│   └── footer.php          # Page footer
├── index.php               # Entry point redirect
├── schema.sql              # Full DB schema + sample data
└── README.md               # This file
```

---

## Security Notes

| Measure | Implementation |
|---|---|
| SQL Injection | PDO prepared statements on every query |
| XSS | `htmlspecialchars()` on all output |
| CSRF | Token generated per session, verified on every POST |
| Session fixation | `session_regenerate_id(true)` on login |
| Password storage | `password_hash()` with bcrypt cost 12 |
| Auth guard | Every protected page checks `$_SESSION['user_id']` |
| Ownership | All task mutations verify `user_id = session user` |

---

## Troubleshooting

**Blank page or 500 error?**
- Enable PHP error display: in `php.ini` set `display_errors = On` and restart Apache.

**Database connection failed?**
- Verify MySQL is running in XAMPP.
- Check credentials in `config/db.php`.

**CSS not loading?**
- Tailwind is loaded from CDN — ensure you have an internet connection.

**URL not working?**
- The folder name contains a space (`task manager`). XAMPP handles this, but use `%20` in the URL: `http://localhost/task%20manager/`.
