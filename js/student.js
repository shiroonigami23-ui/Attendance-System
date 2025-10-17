import { db } from './firebase-config.js';
import { doc, setDoc, collection, getDocs, orderBy, query } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
import { Utils } from './utils.js';

// A simple list of holidays for the year. Format: 'YYYY-MM-DD'
const holidays = {
    '2025-10-02': 'Gandhi Jayanti',
    '2025-10-21': 'Diwali',
    '2025-12-25': 'Christmas Day'
};

export class StudentDashboard {
    constructor(configManager) {
        this.configManager = configManager;
        this.currentUser = null;
        this.currentSection = 'A';
        this.attendanceHistory = {};
        this.calendarDisplayDate = new Date();
    }

    async init(userData) {
        this.currentUser = userData;
        this.currentSection = userData.section;
        this.calendarDisplayDate = new Date();

        this.renderHeader();
        this.updateCurrentAndNextClass();

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

    renderHeader() {
        if (!this.currentUser) return;
        document.getElementById('userName').textContent = this.currentUser.username;
        document.getElementById('userRoll').textContent = this.currentUser.rollNumber;
        document.getElementById('userSection').textContent = `Section ${this.currentUser.section}`;
        document.getElementById('userAvatar').textContent = this.currentUser.username.charAt(0).toUpperCase();
    }

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

    updateCurrentAndNextClass() {
        const currentClassEl = document.getElementById('currentClass');
        const nextClassEl = document.getElementById('nextClass');
        if (!currentClassEl || !nextClassEl) return;

        const now = new Date();
        const currentDay = now.toLocaleString('en-US', { weekday: 'long' });
        const timetable = this.configManager.getTimetable(this.currentUser.section);
        const daySchedule = timetable?.[currentDay];

        if (!daySchedule) {
            currentClassEl.innerHTML = '<h5>Current Class</h5><p>No classes scheduled today.</p>';
            nextClassEl.innerHTML = '<h5>Next Class</h5><p>Enjoy your day off!</p>';
            return;
        }

        let currentClass = null;
        let nextClass = null;
        const sortedSlots = Object.keys(daySchedule).sort();

        for (let i = 0; i < sortedSlots.length; i++) {
            const timeSlot = sortedSlots[i];
            const [startTimeStr, endTimeStr] = timeSlot.split('-');
            const [startHour, startMinute] = startTimeStr.split(':').map(Number);
            const [endHour, endMinute] = endTimeStr.split(':').map(Number);

            const classStart = new Date();
            classStart.setHours(startHour, startMinute, 0, 0);
            const classEnd = new Date();
            classEnd.setHours(endHour, endMinute, 0, 0);

            if (now >= classStart && now <= classEnd) {
                currentClass = { ...daySchedule[timeSlot], timeSlot };
                if (i + 1 < sortedSlots.length) {
                    const nextTimeSlot = sortedSlots[i+1];
                    nextClass = { ...daySchedule[nextTimeSlot], timeSlot: nextTimeSlot };
                }
                break;
            } else if (now < classStart) {
                if (!nextClass) {
                   nextClass = { ...daySchedule[timeSlot], timeSlot };
                }
            }
        }
        
        if (currentClass) {
            currentClassEl.innerHTML = `<h5>Current Class</h5><strong>${currentClass.subject}</strong><p><i class="fas fa-clock"></i> ${currentClass.timeSlot} | <i class="fas fa-map-marker-alt"></i> ${currentClass.room}</p>`;
        } else {
            currentClassEl.innerHTML = '<h5>Current Class</h5><p>No class right now.</p>';
        }

        if (nextClass) {
            nextClassEl.innerHTML = `<h5>Next Class</h5><strong>${nextClass.subject}</strong><p><i class="fas fa-clock"></i> ${nextClass.timeSlot} | <i class="fas fa-map-marker-alt"></i> ${nextClass.room}</p>`;
        } else {
            nextClassEl.innerHTML = '<h5>Next Class</h5><p>No more classes today.</p>';
        }
    }

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

    renderAttendanceLog() {
        const tableBody = document.getElementById('attendanceLogTable');
        if (Object.keys(this.attendanceHistory).length === 0) {
            tableBody.innerHTML = `<tr><td colspan="3" style="text-align:center;">No records found.</td></tr>`;
            return;
        }
        let html = '';
        for (const [date, record] of Object.entries(this.attendanceHistory)) {
            const statusClass = record.status === 'present' ? 'text-success' : 'text-danger';
            const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'long' });
            const formattedTime = record.timestamp ? new Date(record.timestamp.toDate()).toLocaleTimeString() : '-';
            html += `<tr>
                <td>${formattedDate}</td>
                <td><span class="${statusClass}" style="font-weight: bold;">${record.status.toUpperCase()}</span></td>
                <td>${formattedTime}</td>
            </tr>`;
        }
        tableBody.innerHTML = html;
    }

    renderSubjectAttendance() {
        const subjectStats = this.calculateSubjectAttendance();
        const dashboardTbody = document.getElementById('subjectAttendanceTableDashboard');
        let tableHTML = '';

        if (Object.keys(subjectStats).length === 0) {
            tableHTML = `<tr><td colspan="4">No data to calculate.</td></tr>`;
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
            const currentDate = new Date(year, month, day);
            const dayOfWeek = currentDate.getDay();
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            
            const attendanceRecord = this.attendanceHistory[dateStr];
            let dayClass = 'calendar-day';
            const today = new Date();
            
            if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) dayClass += ' today';
            if (dayOfWeek === 0 || dayOfWeek === 6) dayClass += ' weekend';
            if (holidays[dateStr]) dayClass += ' holiday';
            if (attendanceRecord?.status === 'present') dayClass += ' present';

            html += `<div class="${dayClass}" title="${holidays[dateStr] || dateStr}" onclick="showDayDetails('${dateStr}')">${day}</div>`;
        }
        calendar.innerHTML = html;
    }

    changeMonth(direction) {
        this.calendarDisplayDate.setMonth(this.calendarDisplayDate.getMonth() + direction);
        this.renderAttendanceCalendar();
    }

    calculateFuturePercentage() {
        const futureClassesInput = document.getElementById('futureClasses');
        const resultDiv = document.getElementById('futurePercentageResult');
        const futureClasses = parseInt(futureClassesInput.value, 10);

        if (isNaN(futureClasses) || futureClasses <= 0) {
            resultDiv.innerHTML = `<span class="text-danger">Enter a valid number.</span>`;
            return;
        }

        const history = Object.values(this.attendanceHistory);
        const currentPresent = history.filter(rec => rec.status === 'present').length;
        const currentTotal = history.length;

        const futurePresent = currentPresent + futureClasses;
        const futureTotal = currentTotal + futureClasses;
        const futurePercentage = futureTotal > 0 ? ((futurePresent / futureTotal) * 100).toFixed(1) : 0;

        resultDiv.innerHTML = `New percentage: <strong class="text-success">${futurePercentage}%</strong>`;
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
                if (classInfo.subject.toLowerCase().includes('study') || classInfo.type.toLowerCase() === 'break') continue;

                const [startTimeStr, endTimeStr] = timeSlot.split('-');
                const [endHour, endMinute] = endTimeStr.split(':').map(Number);

                const classEnd = new Date();
                classEnd.setHours(endHour, endMinute, 0, 0);

                const windowStart = new Date(classEnd.getTime() - 2.5 * 60000);
                const windowEnd = new Date(classEnd.getTime() + 2.5 * 60000);

                if (now >= windowStart && now <= windowEnd) {
                    isWindowOpen = true;
                    windowMessage.textContent = `Window for ${classInfo.subject} is OPEN until ${Utils.formatTime(windowEnd)}!`;
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
            Utils.showAlert("Can only mark attendance during the open window.", "warning");
            return;
        }

        const today = new Date().toISOString().split('T')[0];
        if (this.attendanceHistory[today]) {
            Utils.showAlert("Attendance already marked for today.", "info");
            return;
        }

        const attendanceRef = doc(db, "attendance", this.currentUser.rollNumber, "records", today);

        try {
            await setDoc(attendanceRef, { status: 'present', timestamp: new Date() });
            Utils.showAlert('Attendance marked!', 'success');
            await this.fetchAttendanceHistory();
            this.updateAttendanceStats();
            this.renderAttendanceCalendar();
            this.renderAttendanceLog();
            this.renderSubjectAttendance();
        } catch (error) {
            console.error("Error marking attendance: ", error);
            Utils.showAlert('Failed to mark attendance.', 'danger');
        }
    }

    showAttendanceForDay(dateStr) {
        const record = this.attendanceHistory[dateStr];
        const holiday = holidays[dateStr];
        const dayOfWeek = new Date(dateStr + 'T00:00:00').getDay();

        if (record) {
            const time = new Date(record.timestamp.toDate()).toLocaleTimeString();
            Utils.showAlert(`Status for ${dateStr}: PRESENT (Marked at ${time})`, 'success');
        } else if (holiday) {
            Utils.showAlert(`${holiday} - Holiday`, 'info');
        } else if (dayOfWeek === 0 || dayOfWeek === 6) {
            Utils.showAlert('Weekend - No classes scheduled', 'info');
        } else {
            Utils.showAlert(`Status for ${dateStr}: ABSENT (No record found)`, 'danger');
        }
    }

    switchSection(sectionId) {
        this.currentSection = sectionId;
        this.renderTimetable();
        document.querySelectorAll('.section-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`section${sectionId}`).classList.add('active');
    }
}
