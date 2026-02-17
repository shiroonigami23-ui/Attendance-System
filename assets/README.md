# Assets folder

Static assets and **user uploads** (profile photos, leave attachments). Public files under `assets/` are served by the web server; uploads are written by the app.

---

## Structure

```
assets/
├── README.md        ← You are here
├── css/             # Stylesheets (if any)
├── icons/           # Icon images (if any)
├── js/              # Front-end scripts (if any)
└── uploads/         # User-uploaded files (see below)
    ├── .gitkeep     # Keeps folder in git; contents ignored
    ├── leaves/      # Student leave attachments
    └── teacher_leaves/  # Teacher leave attachments
```

---

## Uploads

| Path | Purpose |
|------|---------|
| [uploads/](uploads/) | Root for uploads; profile photos and other files may be stored here |
| [uploads/leaves/](uploads/leaves/) | Student leave application attachments |
| [uploads/teacher_leaves/](uploads/teacher_leaves/) | Teacher leave application attachments |

- Uploaded files are **not** committed (see [../.gitignore](../.gitignore): `assets/uploads/*` is ignored; only [uploads/.gitkeep](uploads/.gitkeep) is tracked so the directory exists after clone.
- Ensure these directories exist and are **writable** by the web server (e.g. `chmod 755` or equivalent).

---

## Usage in app

- Registration and profile: profile photo path saved in DB; file stored under `assets/uploads/`.
- [../student/leave_apply.php](../student/leave_apply.php) and [../teacher/leave_apply.php](../teacher/leave_apply.php) upload to the appropriate `uploads/` subfolder.

---

## Back to project

→ [Main README](../README.md)
