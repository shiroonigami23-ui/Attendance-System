# Advanced Campus Attendance System (ACAS)

A PHP/MySQL web application for campus attendance with role-based access (Students, Teachers, Master Admin), device fingerprinting, live QR/TOTP sessions, and leave management.

---

## Table of contents

- [Features](#features)
- [Project structure](#project-structure)
- [Requirements](#requirements)
- [Setup](#setup)
- [Configuration](#configuration)
- [Folder overview](#folder-overview)
- [Legacy frontend](#legacy-frontend)

---

## Features

| Role | Description |
|------|-------------|
| **Student** | Login, dashboard, mark attendance via QR/TOTP, timetable, leave apply, reports |
| **Teacher (SEMI_ADMIN)** | Live sessions (start/close), swap requests, leave apply, manual override, red zone, reports |
| **Master Admin** | Full control: grace console, semester promotion, substitute management, cancel classes, subject/timetable config |

- **Security:** Device fingerprint binding, Argon2id password hashing, session management  
- **Live attendance:** 10-second TOTP tokens; students scan QR or enter token  
- **Leave system:** Student and teacher leave applications with uploads  

---

## Project structure

```
Attendance_System/
├── README.md                 ← You are here
├── .gitignore
├── .htaccess
│
├── index.php                 # Landing (guest)
├── login.php                 # Login page
├── login_handler.php         # Login processing
├── logout.php                # Global logout
├── register.php              # Registration form
├── register_handler.php      # Registration processing
├── dashboard.php             # Role-based redirect after login
├── profile.php               # User profile & password change
│
├── about.php | academics.php | admission.php | contact.php
├── feature.php | faculty.php | help.php | privacy.php | research.php | terms.php
├── check_swap_tables.php      # Swap tables check
├── sql_check.php             # DB structure diagnostic (uses [includes/Config.php](includes/Config.php))
│
├── admin/                    # Master admin area → [admin/README.md](admin/README.md)
├── api/                      # JSON APIs → [api/README.md](api/README.md)
├── assets/                   # Static files & uploads → [assets/README.md](assets/README.md)
├── includes/                 # Config, auth, navbar → [includes/README.md](includes/README.md)
├── student/                  # Student portal → [student/README.md](student/README.md)
├── teacher/                  # Teacher portal → [teacher/README.md](teacher/README.md)
└── _backup_old/              # Legacy Firebase SPA → [_backup_old/README.md](_backup_old/README.md)
```

---

## Requirements

- **PHP** 7.4+ (with PDO MySQL, session, GD for uploads)
- **MySQL** 5.7+ / MariaDB
- **Web server:** Apache (XAMPP/WAMP) or nginx with PHP-FPM

---

## Setup

1. **Clone the repo**
   ```bash
   git clone https://github.com/shiroonigami23-ui/Attendance-System.git
   cd Attendance-System
   ```

2. **Database**
   - Create a MySQL database (e.g. `attendance_system`).
   - Import schema/migrations if present in the repo or your docs.
   - Set DB credentials via [Configuration](#configuration).

3. **Document root**
   - Point your web server (or XAMPP `htdocs`) to the project root so that `index.php`, `login.php`, etc. are served at e.g. `http://localhost/Attendance_System/`.

4. **Configuration**
   - Copy or set environment variables (see [Configuration](#configuration)).
   - Ensure `includes/Config.php` is not overwritten by deployments if you use env-based config.

5. **Uploads**
   - Ensure `assets/uploads/` (and subfolders like `assets/uploads/leaves`, `assets/uploads/teacher_leaves`) exist and are writable by the web server.

---

## Configuration

Credentials are **not** committed. Use environment variables or a local override.

| Variable | Description | Example |
|----------|-------------|---------|
| `DB_HOST` | MySQL host | `localhost` |
| `DB_NAME` | Database name | `attendance_system` |
| `DB_USER` | MySQL user | your user |
| `DB_PASS` | MySQL password | your password |
| `BASE_URL` | App base URL | `http://localhost/Attendance_System/` |
| `SALT_DEVICE` | Device fingerprint salt | random string |
| `DEFAULT_STUDENT_PASSWORD` | Default password for new students | (optional) |
| `DEFAULT_TEACHER_PASSWORD` | Default password for new teachers | (optional) |

- **Config file:** [includes/Config.php](includes/Config.php) reads these and falls back to placeholders so the app runs after you set env (or edit placeholders for local dev).

---

## Folder overview

| Folder | Purpose | README |
|--------|----------|--------|
| [admin/](admin/) | Master admin: grace, promotion, substitutes, cancel classes | [admin/README.md](admin/README.md) |
| [api/](api/) | REST-like endpoints (e.g. mark attendance, subjects) | [api/README.md](api/README.md) |
| [assets/](assets/) | CSS, JS, icons, uploads (profile pics, leave attachments) | [assets/README.md](assets/README.md) |
| [includes/](includes/) | Config, Auth, navbar, SessionManager | [includes/README.md](includes/README.md) |
| [student/](student/) | Student login, dashboard, scanner, leave, report, timetable | [student/README.md](student/README.md) |
| [teacher/](teacher/) | Teacher dashboard, live session, swap, leave, reports | [teacher/README.md](teacher/README.md) |
| [_backup_old/](_backup_old/) | Legacy Firebase-based SPA (optional) | [_backup_old/README.md](_backup_old/README.md) |

---

## Legacy frontend

The [_backup_old/](_backup_old/) directory contains an older Firebase-based single-page app (student/admin). It is optional. The main application is the PHP stack described above. See [_backup_old/README.md](_backup_old/README.md) for its structure and usage.

---

## Quick links to key files

- [includes/Config.php](includes/Config.php) — Database and app config  
- [includes/Auth.php](includes/Auth.php) — Auth and device fingerprint checks  
- [login.php](login.php) — Login page  
- [login_handler.php](login_handler.php) — Login processing  
- [dashboard.php](dashboard.php) — Post-login role redirect  
- [api/mark_attendance.php](api/mark_attendance.php) — Mark attendance API  
- [.gitignore](.gitignore) — Ignored files (logs, uploads, .env)

---

## Verification and Load Testing

Run complete local verification:

```powershell
powershell -ExecutionPolicy Bypass -File tests\run_full_verification.ps1 -BaseUrl http://localhost/Attendance_System -Requests 500 -Concurrency 100
```

Run full 10k attendance stress test:

```powershell
C:\xampp\php\php.exe tests\load_test_10k.php --requests=10000 --concurrency=200
```

Notes:
- `tests/mock_mark_attendance.php` is local-only for stress tests.
- Load tests can seed temporary students automatically and clean up after run.

---

## AWS Deployment

Detailed guide: [aws/README.md](aws/README.md)

Quick setup:

```bash
chmod +x aws/setup.sh
./aws/setup.sh
```
