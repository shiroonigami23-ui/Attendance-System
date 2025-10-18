import { db } from './firebase-config.js';
import { doc, setDoc, collection, getDocs, orderBy, query } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
import { Utils } from './utils.js';
import { holidays } from './holidays.js';


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
        this.displayDayStatusMessage(); // Check for weekends/holidays

        await this.fetchAttendanceHistory();
        this.updateAttendanceStats();
        this.renderAttendanceLog();
        this.renderSubjectAttendance();
        this.renderAttendanceCalendar();

        setInterval(() => this.checkAttendanceWindow(), 30000);
        this.checkAttendanceWindow();
    }

    renderHeader() {
        if (!this.currentUser) return;
        document.getElementById('userName').textContent = this.currentUser.username;
        document.getElementById('userRoll').textContent = this.currentUser.rollNumber;
        document.getElementById('userSection').textContent = `Section ${this.currentUser.section}`;
        document.getElementById('userAvatar').textContent = this.currentUser.username.charAt(0).toUpperCase();
    }
    
    // --- NEW: Displays a banner for holidays or weekends ---
    displayDayStatusMessage() {
        const messageContainer = document.getElementById('dayStatusMessage');
        const messageSpan = messageContainer.querySelector('span');
        const now = new Date();
        const dayOfWeek = now.getDay(); // Sunday = 0, Saturday = 6
        const dateStr = now.toISOString().split('T')[0];

        const holiday = holidays[dateStr];

        if (holiday) {
            messageSpan.textContent = `Today is ${holiday}. Enjoy the day off!`;
            messageContainer.classList.remove('hidden');
            messageContainer.className = 'alert alert-info'; // Blue style for holidays
        } else if (dayOfWeek === 0 || dayOfWeek === 6) { // 0 for Sunday, 6 for Saturday
            messageSpan.textContent = `It's the weekend! No classes are scheduled today.`;
            messageContainer.classList.remove('hidden');
            messageContainer.className = 'alert alert-success'; // Green style for weekends
        } else {
            messageContainer.classList.add('hidden');
        }
    }

    async fetchAttendanceHistory() {
        const history = {};
        const recordsCol = collection(db, "attendance", this.currentUser.rollNumber, "records");
        const dateSnapshot = await getDocs(query(recordsCol));

        for (const dateDoc of dateSnapshot.docs) {
            const date = dateDoc.id;
            history[date] = { subjects: {} };
            const subjectsCol = collection(db, "attendance", this.currentUser.rollNumber, "records", date, "subjects");
            const subjectSnapshot = await getDocs(subjectsCol);
            
            if(subjectSnapshot.empty) {
                const dateData = dateDoc.data();
                 if(dateData.status){
                    history[date].status = dateData.status;
                    history[date].timestamp = dateData.timestamp;
                 }
            } else {
                subjectSnapshot.forEach(subjectDoc => {
                    history[date].subjects[subjectDoc.id] = subjectDoc.data();
                });
            }
        }
        this.attendanceHistory = history;
        return history;
    }

    updateAttendanceStats() {
        let totalPresent = 0;
        let totalClasses = 0;

        Object.values(this.attendanceHistory).forEach(dailyRecord => {
            if(dailyRecord.status === 'present') totalPresent++;
            if(dailyRecord.status && dailyRecord.status !== 'cancelled') totalClasses++;

            if (dailyRecord.subjects) {
                const subjects = Object.values(dailyRecord.subjects);
                totalPresent += subjects.filter(rec => rec.status === 'present' || rec.status === 'late').length;
                totalClasses += subjects.filter(rec => rec.status !== 'cancelled').length;
            }
        });
        
        const absent = totalClasses - totalPresent;
        const percentage = totalClasses > 0 ? Math.round((totalPresent / totalClasses) * 100) : 0;

        document.getElementById('totalPresent').textContent = totalPresent;
        document.getElementById('totalAbsent').textContent = absent;
        document.getElementById('totalClasses').textContent = totalClasses;
        document.getElementById('attendancePercentage').textContent = `${percentage}%`;
    }
    
    switchSection(sectionId) {
        this.currentSection = sectionId;
        document.querySelectorAll('.section-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`section${sectionId}`).classList.add('active');
        this.renderTimetable();
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
        const timeSlots = Array.from(uniqueTimes).sort((a, b) => {
            const timeA = parseInt(a.split(':')[0], 10) * 60 + parseInt(a.split(':')[1].split('-')[0], 10);
            const timeB = parseInt(b.split(':')[0], 10) * 60 + parseInt(b.split(':')[1].split('-')[0], 10);
            return timeA - timeB;
        });

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


    renderAttendanceLog() {
        const tableBody = document.getElementById('attendanceLogTable');
        tableBody.innerHTML = '';
        let hasRecords = false;

        const sortedDates = Object.keys(this.attendanceHistory).sort((a, b) => new Date(b) - new Date(a));

        for (const date of sortedDates) {
            const dailyRecord = this.attendanceHistory[date];
            const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year:'numeric' });

            if(dailyRecord.subjects && Object.keys(dailyRecord.subjects).length > 0) {
                hasRecords = true;
                for(const [subject, record] of Object.entries(dailyRecord.subjects)){
                    let statusClass = 'text-muted';
                    let statusText = record.status.toUpperCase();

                    if (record.status === 'present' || record.status === 'late') statusClass = 'text-success';
                    if (record.status === 'absent') statusClass = 'text-danger';

                    const formattedTime = record.timestamp ? new Date(record.timestamp.toDate()).toLocaleTimeString() : '-';
                    tableBody.innerHTML += `<tr>
                        <td>${formattedDate}</td>
                        <td><span class="${statusClass}" style="font-weight: bold;">${statusText}</span> in ${subject}</td>
                        <td>${formattedTime}</td>
                    </tr>`;
                }
            }
        }

        if (!hasRecords) {
            tableBody.innerHTML = `<tr><td colspan="3" style="text-align:center;">No attendance records found.</td></tr>`;
        }
    }
    
    calculateSubjectAttendance() {
        const subjectStats = {};
        const subjects = this.configManager.getSubjects();

        subjects.forEach(sub => {
            const subjectName = `${sub.code} - ${sub.name}`;
            subjectStats[subjectName] = { attended: 0, total: 0 };
        });

        Object.values(this.attendanceHistory).forEach(dailyRecord => {
            if (dailyRecord.subjects) {
                for (const [subject, record] of Object.entries(dailyRecord.subjects)) {
                    if (subjectStats[subject] && record.status !== 'cancelled') {
                        subjectStats[subject].total++;
                        if (record.status === 'present' || record.status === 'late') {
                            subjectStats[subject].attended++;
                        }
                    }
                }
            }
        });
        
        for (const subject in subjectStats) {
            const { attended, total } = subjectStats[subject];
            subjectStats[subject].percentage = total > 0 ? Math.round((attended / total) * 100) : 0;
        }

        return subjectStats;
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
                 if (total === 0) continue;
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
    
    renderAttendanceCalendar() {
        const calendar = document.getElementById('attendanceCalendar');
        const title = document.getElementById('calendarTitle');
        if (!calendar || !title) return;

        const date = this.calendarDisplayDate;
        const year = date.getFullYear();
        const month = date.getMonth();
        
        title.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });

        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDayOfMonth = (new Date(year, month, 1).getDay() + 6) % 7; 
        
        let html = '';
        ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(day => html += `<div class="calendar-day-header">${day}</div>`);
        for (let i = 0; i < firstDayOfMonth; i++) html += `<div></div>`;

        for (let day = 1; day <= daysInMonth; day++) {
            const currentDate = new Date(year, month, day);
            const dayOfWeek = currentDate.getDay();
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            
            const dailyRecord = this.attendanceHistory[dateStr];
            let dayClass = 'calendar-day';
            const today = new Date();
            
            if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) dayClass += ' today';
            if (dayOfWeek === 0 || dayOfWeek === 6) dayClass += ' weekend';
            if (holidays[dateStr]) dayClass += ' holiday';
            
            if (dailyRecord?.subjects && Object.values(dailyRecord.subjects).some(s => s.status === 'present' || s.status === 'late')) {
                dayClass += ' present';
            } else if (dailyRecord?.status === 'present') {
                 dayClass += ' present';
            }

            html += `<div class="${dayClass}" title="${holidays[dateStr] || dateStr}" onclick="window.app.studentDashboard.showAttendanceForDay('${dateStr}')">${day}</div>`;
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
        
        let currentPresent = 0;
        let currentTotal = 0;
        Object.values(this.attendanceHistory).forEach(dailyRecord => {
            if(dailyRecord.status === 'present') currentPresent++;
            if(dailyRecord.status) currentTotal++;
            if (dailyRecord.subjects) {
                const subjects = Object.values(dailyRecord.subjects);
                currentPresent += subjects.filter(rec => rec.status === 'present' || rec.status === 'late').length;
                currentTotal += subjects.filter(rec => rec.status !== 'cancelled').length;
            }
        });

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
        Utils.showAlert("Marking attendance would require class info from the QR code.", "info");
    }

    showAttendanceForDay(dateStr) {
        const dailyRecord = this.attendanceHistory[dateStr];
        const holiday = holidays[dateStr];
        const dayOfWeek = new Date(dateStr + 'T00:00:00').getDay();

        let message = '';
        if (dailyRecord) {
            if(dailyRecord.subjects && Object.keys(dailyRecord.subjects).length > 0) {
                 message = `Attendance for ${dateStr}:<br>`;
                 Object.values(dailyRecord.subjects).forEach(rec => {
                    let statusText = `<strong>${rec.status.toUpperCase()}</strong>`;
                    if (rec.status === 'cancelled') {
                        statusText = `<em>${rec.status.toUpperCase()}</em>`;
                    }
                    message += `- ${rec.subject}: ${statusText}<br>`;
                 });
            } else if (dailyRecord.status) {
                message = `General attendance for ${dateStr}: <strong>${dailyRecord.status.toUpperCase()}</strong>`;
            } else {
                 message = `No attendance records for ${dateStr}.`;
            }
        } else if (holiday) {
            message = `This day was a holiday: <strong>${holiday}</strong>.`;
        } else if (dayOfWeek === 0 || dayOfWeek === 6) {
            message = `This was a weekend. No classes were scheduled.`;
        } else {
            message = `No attendance records found for ${dateStr}.`;
        }
        
        Utils.showAlert(message, 'info', 8000);
    }
}
