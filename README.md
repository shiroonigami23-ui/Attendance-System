# 📱 Advanced Campus Attendance System

![Status](https://img.shields.io/badge/Status-Live-success)
![Role](https://img.shields.io/badge/User_Roles-Student_&_Admin-blue)
![Security](https://img.shields.io/badge/Security-Device_Lock-red)

> **A smart, secure, and device-locked attendance management solution for educational institutions.**

**Attendance System** is a web-based platform designed to streamline the attendance process. It features a dual-interface system for Students and Admins, real-time analytics, and a unique **Device Lock Security** feature that prevents students from marking attendance on behalf of others by binding their account to a single device.

---

## 🔗 Live Demo

**Access the portal here:**
### [🏫 Launch Attendance System](https://shiroonigami23-ui.github.io/Attendance-System/)

---

## ✨ Key Features

### 🔐 Security & Integrity
- **Device Locking:** When a student logs in for the first time, their account is permanently bound to that specific device. Attempting to log in from another phone/laptop triggers a "Device Mismatch" error.
- **Admin Reset:** Only admins can clear device data to allow a student to switch devices.

### 🎓 Student Dashboard
- **Live Stats:** View Total Present, Absent, Total Classes, and Overall Percentage at a glance.
- **Mark Attendance:** Scan QR codes or tap-to-mark (enabled only during active windows).
- **Attendance Calculator:** A predictive tool ("If I attend the next X classes...") to help students plan their attendance goals.
- **Detailed History:**
  - **Subject-wise Summary:** Table view of attendance per subject.
  - **Calendar View:** Visual representation of attendance over the month.
  - **Daily Log:** Timestamped history of when attendance was marked.

### 🛡️ Admin Dashboard
- **Overview Panel:** Real-time counters for Total Students, Active Students, and System Status.
- **Student Management:** View registered students, their device IDs, last login times, and manage their access.
- **Manual Override:** Manually mark attendance (Present/Absent/Late) for any student.
- **Class Cancellation:** Cancel specific classes or declare a holiday for the entire day (notifies students).
- **Reports:** Generate and export CSV reports for:
  - Daily Attendance
  - Weekly Summaries
  - Monthly Defaulter Lists
- **System Settings:** Bulk logout all students or reset device registrations.

---

## 🎮 How to Use

### For Students
1. **Login/Register:** Enter your Roll Number and select your Section (A/B).
2. **Dashboard:** Check your current percentage.
3. **Mark Attendance:** Click the "Tap to Scan" button when the teacher opens the attendance window.
4. **Predict:** Use the calculator to see how many classes you need to attend to reach 75%.

### For Admins
1. **Login:** Use the Admin credentials.
2. **Manage:** Use the sidebar to navigate between Students, Attendance, and Reports.
3. **Handle Issues:** If a student loses their phone, go to "Registered Students" and reset their device ID.
4. **Export:** Go to "Reports" to download the monthly attendance sheet for the dean.

---

## 📸 Screenshots

| Student Dashboard | Admin Control Panel |
|:---:|:---:|
| *Stats, Calculator & History* | *Manage Students & Reports* |

*(Note: Screenshots can be added here)*

---

## 💻 Local Installation

To run this system locally:

1. **Clone the repository:**
   ```bash
   git clone [https://github.com/shiroonigami23-ui/Attendance-System.git](https://github.com/shiroonigami23-ui/Attendance-System.git)
   
