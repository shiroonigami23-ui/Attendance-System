# Student area

This folder contains the **Student** portal. Users with role `STUDENT` are redirected here after login (see [../dashboard.php](../dashboard.php)).

---

## Structure

```
student/
├── README.md           ← You are here
├── login.php           # Student-specific login page (optional entry)
├── login_handler.php   # Process student login
├── logout.php          # Student logout
├── dashboard.php       # Student home: stats, quick actions
├── scanner.php         # QR/TOTP scanner + manual token entry
├── timetable.php       # View timetable
├── leave_apply.php     # Apply for leave (with upload)
├── report.php          # Attendance report / history
```

---

## Files (with links)

| File | Purpose |
|------|---------|
| [dashboard.php](dashboard.php) | Student dashboard: attendance summary, percentage, links to scanner, timetable, leave, report |
| [login.php](login.php) | Student login form (email, password, device fingerprint) |
| [login_handler.php](login_handler.php) | Validates credentials, checks device lock, sets session; redirects to [dashboard.php](dashboard.php) |
| [logout.php](logout.php) | Destroys session; redirects to [../login.php](../login.php) |
| [scanner.php](scanner.php) | Scan QR from teacher’s live session or enter 16-digit TOTP token; calls [../api/mark_attendance.php](../api/mark_attendance.php) |
| [timetable.php](timetable.php) | Displays student’s weekly timetable |
| [leave_apply.php](leave_apply.php) | Form to apply for leave; uploads stored under [../assets/uploads/](../assets/README.md#uploads) |
| [report.php](report.php) | Subject-wise and date-wise attendance report |

---

## Flow

1. Student logs in via [../login.php](../login.php) or [login.php](login.php).
2. [../login_handler.php](../login_handler.php) or [login_handler.php](login_handler.php) validates and enforces device binding.
3. Redirect to [dashboard.php](dashboard.php).
4. To mark attendance: open [scanner.php](scanner.php), scan QR or enter token from teacher’s live session.

---

## Dependencies

- [../includes/Config.php](../includes/Config.php), [../includes/Auth.php](../includes/Auth.php)
- [../api/mark_attendance.php](../api/mark_attendance.php) for saving attendance from scanner

---

## Back to project

→ [Main README](../README.md)
