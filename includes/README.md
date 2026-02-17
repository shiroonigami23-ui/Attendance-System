# Includes folder

Shared **PHP includes** used across the app: configuration, database connection, auth, session handling, and navbar.

---

## Structure

```
includes/
├── README.md       ← You are here
├── Config.php      # DB config, BASE_URL, SALT_DEVICE, PDO connection
├── Auth.php        # checkAuth(), getCurrentUser() (device fingerprint)
├── SessionManager.php  # Session helpers
├── navbar.php      # Shared navbar HTML/fragment
```

---

## Files (with links)

| File | Purpose |
|------|---------|
| [Config.php](Config.php) | Defines `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `BASE_URL`, `SALT_DEVICE` (from env or placeholders). Creates `$pdo`. Used by almost every PHP page. |
| [Auth.php](Auth.php) | `checkAuth()` — ensures user is logged in and device fingerprint matches. `getCurrentUser()` — returns current user + student profile if applicable. |
| [SessionManager.php](SessionManager.php) | Session-related helpers (e.g. flash messages, CSRF if used). |
| [navbar.php](navbar.php) | Reusable navbar markup; included in dashboard and other role pages. |

---

## Usage

- **Config:** Include first: `require_once 'includes/Config.php';`
- **Auth:** After Config, on protected pages: `require_once 'includes/Auth.php'; checkAuth();`
- **Navbar:** `require_once 'includes/navbar.php';` where the layout expects it.

---

## Security

- Do **not** commit real credentials. Use environment variables; [Config.php](Config.php) reads `DB_*`, `BASE_URL`, `SALT_DEVICE` from env with placeholder fallbacks.

---

## Back to project

→ [Main README](../README.md)
