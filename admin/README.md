# Admin (Master) area

This folder contains the **Master Admin** panel. Only users with role `MASTER` are redirected here after login (see [../dashboard.php](../dashboard.php)).

---

## Structure

```
admin/
├── README.md                 ← You are here
├── dashboard.php             # Admin home / overview
├── logout.php                # Admin logout
├── get_actual_semester.php   # Returns current semester (API helper)
├── get_subjects.php          # Returns subjects list (API helper)
├── grace_console.php         # Grace marks / condonation management
├── semester_promotion.php     # Semester promotion workflow
├── substitute_management.php # Substitute teacher assignment
├── cancel_classes.php        # Cancel classes / declare holidays
```

---

## Files (with links)

| File | Purpose |
|------|---------|
| [dashboard.php](dashboard.php) | Main admin dashboard; entry point after login |
| [logout.php](logout.php) | Destroys session and redirects to main [../login.php](../login.php) |
| [get_actual_semester.php](get_actual_semester.php) | Returns current semester (used by other pages) |
| [get_subjects.php](get_subjects.php) | Returns list of subjects (used by dropdowns/APIs) |
| [grace_console.php](grace_console.php) | Grace / condonation rules and application |
| [semester_promotion.php](semester_promotion.php) | Run semester promotion (e.g. advance all students) |
| [substitute_management.php](substitute_management.php) | Manage substitute teachers for slots |
| [cancel_classes.php](cancel_classes.php) | Cancel specific classes or declare day holiday |

---

## Dependencies

- All admin pages typically require [../includes/Config.php](../includes/Config.php) and [../includes/Auth.php](../includes/Auth.php).
- Access is restricted to `$_SESSION['role'] === 'MASTER'` (enforced in each file or via a shared check).

---

## Back to project

→ [Main README](../README.md)
