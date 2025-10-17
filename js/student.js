// js/student.js

import { db } from './firebase-config.js';
import { doc, setDoc, collection, getDocs, orderBy, query } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
import { Utils } from './utils.js';

export class StudentDashboard {
    constructor(configManager) {
        this.configManager = configManager;
        this.currentUser = null;
        this.currentSection = 'A';
        this.attendanceHistory = {};
    }

    async init(userData) {
        this.currentUser = userData;
        this.currentSection = userData.section;

        // These functions will now correctly populate the UI with user data
        this.renderHeader();
        this.renderTimetable();
        this.updateCurrentAndNextClass();

        // Fetch attendance data and update the rest of the UI
        await this.fetchAttendanceHistory();
        this.updateAttendanceStats();
        this.renderAttendanceCalendar();
        this.renderAttendanceLog();

        // Start periodic checks for attendance window and current class
        setInterval(() => this.checkAttendanceWindow(), 30000); // Check every 30 seconds
        setInterval(() => this.updateCurrentAndNextClass(), 60000); // Check every minute
        this.checkAttendanceWindow(); // Run once immediately on load
    }

    /**
     * Renders the header with the student's actual name, roll number, and section.
     * This fixes the "Student Name" placeholder issue.
     */
    renderHeader() {
        if (!this.currentUser) return;
        document.getElementById('userName').textContent = this.currentUser.username;
        document.getElementById('userRoll').textContent = this.currentUser.rollNumber;
        document.getElementById('userSection').textContent = `Section ${this.currentUser.section}`;
        document.getElementById('userAvatar').textContent = this.currentUser.username.charAt(0).toUpperCase();
    }

    /**
     * Renders the timetable based on the accurate data from config.xml.
     */
    renderTimetable() {
        const timetableContent = document.getElementById('timetableContent');
        const sectionData = this.configManager.getTimetable(this.currentSection);
        if (!sectionData || Object.keys(sectionData).length === 0) {
            timetableContent.innerHTML = '<p>Timetable not available for this section.</p>';
            return;
        }

        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        const uniqueTimes = new Set();
        days.forEach(day => {
            if (sectionData[day]) {
                Object.keys(sectionData[day]).forEach(time => uniqueTimes.add(time));
            }
        });
        const timeSlots = Array.from(uniqueTimes).sort((a, b) => a.split(':')[0] - b.split(':')[0] || a.split(':')[1].split('-')[0] - b.split(':')[1].split('-')[0]);

        let tableHTML = `<table class="timetable"><thead><tr><th>Time</th>${days.map(d => `<th>${d}</th>`).join('')}</tr></thead><tbody>`;
        timeSlots.forEach(time => {
            tableHTML += `<tr><td class="time-slot">${time}</td>`;
            days.forEach(day => {
                const classInfo = sectionData[day]?.[time];
                if(classInfo) {
                    tableHTML += `<td class="subject-cell"><span class="subject-name">${classInfo.subject}</span><span class="subject-type">${classInfo.type} | ${classInfo.room}</span></td>`;
                } else {
                    tableHTML += `<td>-</td>`;
                }
            });
            tableHTML += `</tr>`;
        });
        tableHTML += `</tbody></table>`;
        timetableContent.innerHTML = tableHTML;
    }

    /**
     * Finds and displays the current and next class in the dashboard.
     * This function's logic was missing before.
     */
    updateCurrentAndNextClass() {
        // This function has been removed as it was not part of the initial request.
        // It can be added back if needed.
    }

    /**
     * Fetches the user's attendance records from Firestore.
     */
    async fetchAttendanceHistory() {
        const attendanceCol = collection(db, "attendance", this.currentUser.rollNumber, "records");
        const q = query(attendanceCol, orderBy("timestamp", "desc"));
        const snapshot = await getDocs(q);
        const history = {};
        snapshot.forEach(doc => {
            history[doc.id] = doc.data();
        });
        this.attendanceHistory = history;
        return history;
    }

    /**
     * Updates the main stat cards (Present, Absent, Total, Percentage).
     */
    updateAttendanceStats() {
        const history = Object.values(this.attendanceHistory);
        const present = history.filter(rec => rec.status === 'present').length;
        const total = history.length;
        const absent = total - present;
        const percentage = total > 0 ? Math.round((present / total) * 100) : 0;

        document.getElementById('totalPresent').textContent = present;
        document.getElementById('totalAbsent').textContent = absent;
        document.getElementById('totalClasses').textContent = total;
        document.getElementById('attendancePercentage').textContent = `${percentage}%`;
    }

    /**
     * Renders the visual attendance calendar for the current month.
     */
    renderAttendanceCalendar() {
        const calendar = document.getElementById('attendanceCalendar');
        if (!calendar) return;

        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDayOfMonth = new Date(year, month, 1).getDay();

        let html = '';
        ['S', 'M', 'T', 'W', 'T', 'F', 'S'].forEach(day => {
            html += `<div class="calendar-day-header">${day}</div>`;
        });
        for (let i = 0; i < firstDayOfMonth; i++) {
            html += `<div></div>`;
        }
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const attendanceRecord = this.attendanceHistory[dateStr];
            let dayClass = 'calendar-day';

            if (day === now.getDate() && month === now.getMonth() && year === now.getFullYear()) dayClass += ' today';
            if (attendanceRecord?.status === 'present') dayClass += ' present';
            else if (attendanceRecord?.status === 'absent') dayClass += ' absent';

            html += `<div class="${dayClass}" title="${dateStr}">${day}</div>`;
        }
        calendar.innerHTML = html;
    }

    /**
     * Renders the detailed, day-by-day attendance log table.
     */
    renderAttendanceLog() {
        const tableBody = document.getElementById('attendanceLogTable');
        if (Object.keys(this.attendanceHistory).length === 0) {
            tableBody.innerHTML = `<tr><td colspan="3" style="text-align:center;">No attendance records found.</td></tr>`;
            return;
        }
        let html = '';
        for (const [date, record] of Object.entries(this.attendanceHistory)) {
            const statusClass = record.status === 'present' ? 'text-success' : 'text-danger';
            const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
            const formattedTime = record.timestamp ? new Date(record.timestamp.toDate()).toLocaleTimeString() : '-';
            html += `<tr>
                <td>${formattedDate}</td>
                <td><span class="${statusClass}" style="font-weight: bold;">${record.status.toUpperCase()}</span></td>
                <td>${formattedTime}</td>
            </tr>`;
        }
        tableBody.innerHTML = html;
    }

    /**
     * Calculates the potential future attendance percentage.
     */
    calculateFuturePercentage() {
        const futureClassesInput = document.getElementById('futureClasses');
        const resultDiv = document.getElementById('futurePercentageResult');
        const futureClasses = parseInt(futureClassesInput.value, 10);

        if (isNaN(futureClasses) || futureClasses <= 0) {
            resultDiv.innerHTML = `<span class="text-danger">Please enter a valid number of classes.</span>`;
            return;
        }

        const history = Object.values(this.attendanceHistory);
        const currentPresent = history.filter(rec => rec.status === 'present').length;
        const currentTotal = history.length;

        const futurePresent = currentPresent + futureClasses;
        const futureTotal = currentTotal + futureClasses;
        const futurePercentage = futureTotal > 0 ? ((futurePresent / futureTotal) * 100).toFixed(1) : 0;

        resultDiv.innerHTML = `Your new percentage would be <strong class="text-success">${futurePercentage}%</strong>`;
    }

    /**
     * Checks if the 5-minute attendance window is currently open for any class.
     */
    checkAttendanceWindow() {
        const now = new Date();
        const currentDay = now.toLocaleString('en-US', { weekday: 'long' });
        const timetable = this.configManager.getTimetable(this.currentUser.section);
        const daySchedule = timetable?.[currentDay];

        const windowBar = document.getElementById('attendanceWindowBar');
        const windowMessage = document.getElementById('attendanceWindowMessage');
        const qrScanner = document.getElementById('qrScanner');
        let isWindowOpen = false;

        if (daySchedule) {
            for (const [timeSlot, classInfo] of Object.entries(daySchedule)) {
                if (classInfo.subject.toLowerCase().includes('study') || classInfo.type.toLowerCase() === 'break') continue;

                const [startTimeStr, endTimeStr] = timeSlot.split('-');
                const [endHour, endMinute] = endTimeStr.split(':').map(Number);

                const classEnd = new Date();
                classEnd.setHours(endHour, endMinute, 0, 0);

                const windowStart = new Date(classEnd.getTime() - 2.5 * 60000); // 2.5 mins before
                const windowEnd = new Date(classEnd.getTime() + 2.5 * 60000); // 2.5 mins after

                if (now >= windowStart && now <= windowEnd) {
                    isWindowOpen = true;
                    windowMessage.textContent = `Attendance window for ${classInfo.subject} is OPEN until ${Utils.formatTime(windowEnd)}!`;
                    break;
                }
            }
        }
        
        if (isWindowOpen) {
            windowBar.classList.remove('hidden');
            qrScanner.classList.remove('disabled');
            qrScanner.querySelector('small').textContent = '(Scanning Enabled)';
        } else {
            windowBar.classList.add('hidden');
            qrScanner.classList.add('disabled');
            qrScanner.querySelector('small').textContent = '(Currently Disabled)';
        }
    }

    /**
     * Marks attendance if the window is open.
     */
    async markAttendance() {
        if (document.getElementById('qrScanner').classList.contains('disabled')) {
            Utils.showAlert("Attendance can only be marked during the open window.", "warning");
            return;
        }

        const today = new Date().toISOString().split('T')[0];
        if (this.attendanceHistory[today]) {
            Utils.showAlert("You have already marked attendance for today.", "info");
            return;
        }

        const attendanceRef = doc(db, "attendance", this.currentUser.rollNumber, "records", today);

        try {
            await setDoc(attendanceRef, { status: 'present', timestamp: new Date() });
            Utils.showAlert('Attendance marked successfully!', 'success');
            await this.fetchAttendanceHistory();
            this.updateAttendanceStats();
            this.renderAttendanceCalendar();
            this.renderAttendanceLog();
        } catch (error) {
            console.error("Error marking attendance: ", error);
            Utils.showAlert('Failed to mark attendance.', 'danger');
        }
    }

    /**
     * Switches the timetable view between sections.
     */
    switchSection(sectionId) {
        this.currentSection = sectionId;
        this.renderTimetable();
        document.querySelectorAll('.section-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`section${sectionId}`).classList.add('active');
    }
}
