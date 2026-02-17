# API folder

This folder holds **server-side endpoints** used by the frontend (e.g. marking attendance, fetching subjects). They return JSON and are called via POST/GET from student/teacher pages.

---

## Structure

```
api/
├── README.md                      ← You are here
├── mark_attendance.php            # Mark present/absent for a session
├── get_subjects_with_metadata.php # Subjects list with metadata
```

---

## Files (with links)

| File | Purpose |
|------|---------|
| [mark_attendance.php](mark_attendance.php) | Accepts `session_id`, `token` (TOTP), `student_id`, `status`; validates token and records attendance. Used by [../student/scanner.php](../student/scanner.php). |
| [get_subjects_with_metadata.php](get_subjects_with_metadata.php) | Returns subjects (and related metadata) for dropdowns or reports. Used by admin/teacher/student pages as needed. |

---

## Usage notes

- **mark_attendance.php:** Expects JSON or form body with `session_id`, `token`, and student/slot info. Validates the 10-second TOTP token against the live session before saving.
- **get_subjects_with_metadata.php:** Typically called via AJAX/fetch; returns JSON list of subjects.

---

## Dependencies

- Both use [../includes/Config.php](../includes/Config.php) for DB; session/auth may be required depending on endpoint.

---

## Back to project

→ [Main README](../README.md)
