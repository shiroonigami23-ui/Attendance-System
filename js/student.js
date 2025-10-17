import { db } from './firebase-config.js';
import { doc, setDoc, collection, getDocs, orderBy, query } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
import { Utils } from './utils.js';

export class StudentDashboard {
    constructor(configManager) {
        this.configManager = configManager;
        this.currentUser = null;
        this.currentSection = 'A';
        this.attendanceHistory = {};
        this.calendarDisplayDate = new Date(); // State for calendar navigation
    }

    async init(userData) {
        this.currentUser = userData;
        this.currentSection = userData.section;
        this.calendarDisplayDate = new Date(); // Reset calendar to current month on login

        this.renderHeader();
        this.updateCurrentAndNextClass(); // Display current class info

        await this.fetchAttendanceHistory();
        this.updateAttendanceStats();
        this.renderAttendanceLog();
        this.renderSubjectAttendance();
        this.renderAttendanceCalendar();

        // Set up periodic checks
        setInterval(() => this.checkAttendanceWindow(), 30000);
        setInterval(() => this.updateCurrentAndNextClass(), 60000);
        this.checkAttendanceWindow();
    }

    /**
     * Renders the header with the student's actual name, roll number, and section.
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
     */
    updateCurrentAndNextClass() {
        // This function has been removed as per your request to have a cleaner student.js file
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
     * Simulates subject-wise attendance and renders it to the dashboard table.
     */
    renderSubjectAttendance() {
        const subjectStats = this.calculateSubjectAttendance();
        const dashboardTbody = document.getElementById('subjectAttendanceTableDashboard');
        let tableHTML = '';

        if (Object.keys(subjectStats).length === 0) {
            tableHTML = `<tr><td colspan="4">No attendance data to calculate.</td></tr>`;
        } else {
            for (const subject in subjectStats) {
                const { attended, total, percentage } = subjectStats[subject];
                const badgeClass = percentage >= 75 ? 'status-present' : percentage >= 60 ? 'status-late' : 'status-absent';
                tableHTML += `
                    <tr>
                        <td>${subject}</td>
                        <td class="text-success fw-bold">${attended}</td>
                        <td>${total}</td>
                        <td><span class="status-badge ${badgeClass}">${percentage}%</span></td>
                    </tr>
                `;
            }
        }
        dashboardTbody.innerHTML = tableHTML;
    }
    
    /**
     * Helper function to perform the subject attendance calculation.
     */
    calculateSubjectAttendance() {
        const timetable = this.configManager.getTimetable(this.currentUser.section);
        if (!timetable) return {};

        const subjectCounts = {};
        let totalPeriods = 0;
        Object.values(timetable).forEach(day => {
            Object.values(day).forEach(slot => {
                if (slot.type !== 'Break' && !slot.subject.toLowerCase().includes('study')) {
                    subjectCounts[slot.subject] = (subjectCounts[slot.subject] || 0) + 1;
                    totalPeriods++;
                }
            });
        });

        const history = Object.values(this.attendanceHistory);
        const totalAttendedDays = history.filter(rec => rec.status === 'present').length;
        const totalDaysTracked = history.length;

        const subjectStats = {};
        for (const subject in subjectCounts) {
            const proportion = subjectCounts[subject] / totalPeriods;
            const totalClassesForSubject = Math.round(proportion * totalDaysTracked);
            const attendedClassesForSubject = Math.round(proportion * totalAttendedDays);
            const percentage = totalClassesForSubject > 0 ? Math.round((attendedClassesForSubject / totalClassesForSubject) * 100) : 0;
            
            if (totalClassesForSubject > 0) {
                 subjectStats[subject] = {
                    attended: attendedClassesForSubject,
                    total: totalClassesForSubject,
                    percentage: percentage
                };
            }
        }
        return subjectStats;
    }
    
    /**
     * Renders the visual attendance calendar for the currently selected month.
     */
    renderAttendanceCalendar() {
        const calendar = document.getElementById('attendanceCalendar');
        const title = document.getElementById('calendarTitle');
        if (!calendar || !title) return;

        const date = this.calendarDisplayDate;
        const year = date.getFullYear();
        const month = date.getMonth();
        
        title.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });

        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDayOfMonth = new Date(year, month, 1).getDay();
        
        let html = '';
        ['S', 'M', 'T', 'W', 'T', 'F', 'S'].forEach(day => html += `<div class="calendar-day-header">${day}</div>`);
        for (let i = 0; i < firstDayOfMonth; i++) html += `<div></div>`;

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const attendanceRecord = this.attendanceHistory[dateStr];
            let dayClass = 'calendar-day';
            const today = new Date();

            if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) dayClass += ' today';
            if (attendanceRecord?.status === 'present') dayClass += ' present';

            html += `<div class="${dayClass}" title="${dateStr}">${day}</div>`;
        }
        calendar.innerHTML = html;
    }

    /**
     * Changes the calendar's month and re-renders it.
     */
    changeMonth(direction) {
        this.calendarDisplayDate.setMonth(this.calendarDisplayDate.getMonth() + direction);
        this.renderAttendanceCalendar();
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
            this.renderSubjectAttendance(); // Re-render subject stats after marking attendance
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
