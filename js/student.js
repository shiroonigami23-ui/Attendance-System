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
        this.renderHeader();
        this.renderTimetable();
        
        await this.fetchAttendanceHistory();
        this.updateAttendanceStats();
        this.renderAttendanceCalendar();
        this.renderAttendanceLog();
        
        // Start checking for attendance window every 30 seconds
        setInterval(() => this.checkAttendanceWindow(), 30000);
        this.checkAttendanceWindow(); // Run once immediately
    }

    renderHeader() {
        // ... (This function remains unchanged)
    }

    renderTimetable() {
        // ... (This function remains unchanged)
    }

    async fetchAttendanceHistory() {
        const attendanceCol = collection(db, "attendance", this.currentUser.rollNumber, "records");
        // Order by timestamp to get the most recent first
        const q = query(attendanceCol, orderBy("timestamp", "desc"));
        const snapshot = await getDocs(q);
        const history = {};
        snapshot.forEach(doc => {
            history[doc.id] = doc.data(); // Store full data object
        });
        this.attendanceHistory = history;
        return history;
    }

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

    renderAttendanceCalendar() {
        const calendar = document.getElementById('attendanceCalendar');
        if (!calendar) return;

        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDayOfMonth = new Date(year, month, 1).getDay();

        let html = '';
        // Day headers
        ['S', 'M', 'T', 'W', 'T', 'F', 'S'].forEach(day => {
            html += `<div class="calendar-day-header">${day}</div>`;
        });
        // Blank days for padding
        for (let i = 0; i < firstDayOfMonth; i++) {
            html += `<div></div>`;
        }
        // Month days
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const attendanceRecord = this.attendanceHistory[dateStr];
            let dayClass = 'calendar-day';

            if (day === now.getDate()) dayClass += ' today';
            if (attendanceRecord?.status === 'present') dayClass += ' present';
            else if (attendanceRecord?.status === 'absent') dayClass += ' absent';

            html += `<div class="${dayClass}" title="${dateStr}">${day}</div>`;
        }
        calendar.innerHTML = html;
    }

    renderAttendanceLog() {
        const tableBody = document.getElementById('attendanceLogTable');
        const history = this.attendanceHistory;
        if (Object.keys(history).length === 0) {
            tableBody.innerHTML = `<tr><td colspan="3">No attendance records found.</td></tr>`;
            return;
        }
        
        let html = '';
        for (const [date, record] of Object.entries(history)) {
            const statusClass = record.status === 'present' ? 'text-success' : 'text-danger';
            const formattedDate = new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
            const formattedTime = record.timestamp ? new Date(record.timestamp.toDate()).toLocaleTimeString() : '-';
            html += `<tr>
                <td>${formattedDate}</td>
                <td><span class="${statusClass}" style="font-weight: bold;">${record.status.toUpperCase()}</span></td>
                <td>${formattedTime}</td>
            </tr>`;
        }
        tableBody.innerHTML = html;
    }
    
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
                if (classInfo.subject.toLowerCase() === 'self study' || classInfo.type.toLowerCase() === 'break') continue;

                const [startTimeStr, endTimeStr] = timeSlot.split('-');
                const [endHour, endMinute] = endTimeStr.split(':').map(Number);

                const classEnd = new Date();
                classEnd.setHours(endHour, endMinute, 0, 0);

                const windowStart = new Date(classEnd.getTime() - 2.5 * 60000); // 2.5 mins before
                const windowEnd = new Date(classEnd.getTime() + 2.5 * 60000);   // 2.5 mins after

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
            await this.fetchAttendanceHistory(); // Refresh data
            this.updateAttendanceStats();
            this.renderAttendanceCalendar();
            this.renderAttendanceLog();
        } catch (error) {
            console.error("Error marking attendance: ", error);
            Utils.showAlert('Failed to mark attendance.', 'danger');
        }
    }
}
