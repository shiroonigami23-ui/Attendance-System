# Teacher area

This folder contains the **Teacher** (SEMI_ADMIN) portal. Users with role `SEMI_ADMIN` are redirected here after login (see [../dashboard.php](../dashboard.php)).

---

## Structure

```
teacher/
├── README.md             ← You are here
├── dashboard.php         # Teacher home
├── logout.php            # Teacher logout
├── timetable.php         # View teaching timetable
├── live_session.php      # Start live session → generates TOTP / QR
├── close_session.php     # End live session
├── manual_override.php   # Manually mark students present/absent/late
├── red_zone.php          # Red zone / defaulters view
├── leave_apply.php       # Teacher leave application
├── teacher_report.php    # Teaching / attendance reports
├── swap_dashboard.php    # Swap overview
├── swap_request_form.php # Create swap request
├── swap_requests.php     # View / manage swap requests
```

---

## Files (with links)

| File | Purpose |
|------|---------|
| [dashboard.php](dashboard.php) | Teacher dashboard; links to live session, timetable, swap, leave, reports |
| [logout.php](logout.php) | Destroys session; redirects to [../login.php](../login.php) |
| [timetable.php](timetable.php) | Displays teacher’s teaching timetable |
| [live_session.php](live_session.php) | Start a live attendance session; generates 10-second TOTP token and QR for students (used by [../student/scanner.php](../student/scanner.php)) |
| [close_session.php](close_session.php) | Ends the current live session |
| [manual_override.php](manual_override.php) | Manually mark attendance (present/absent/late) for students |
| [red_zone.php](red_zone.php) | View students in “red zone” (low attendance) |
| [leave_apply.php](leave_apply.php) | Teacher leave application (uploads to [../assets/uploads/](../assets/README.md#uploads)) |
| [teacher_report.php](teacher_report.php) | Reports for classes taught |
| [swap_dashboard.php](swap_dashboard.php) | Overview of slot swaps |
| [swap_request_form.php](swap_request_form.php) | Form to request a slot swap |
| [swap_requests.php](swap_requests.php) | List and manage swap requests |

---

## Live attendance flow

1. Teacher opens [live_session.php](live_session.php) and starts a session for a slot.
2. A TOTP token (and QR) is shown; students use [../student/scanner.php](../student/scanner.php) to scan or enter the token.
3. [../api/mark_attendance.php](../api/mark_attendance.php) validates the token and records attendance.
4. Teacher closes the session via [close_session.php](close_session.php).

---

## Dependencies

- [../includes/Config.php](../includes/Config.php), [../includes/Auth.php](../includes/Auth.php)
- [../admin/get_subjects.php](../admin/get_subjects.php) or [../api/get_subjects_with_metadata.php](../api/get_subjects_with_metadata.php) for subject lists where needed

---

## Back to project

→ [Main README](../README.md)
