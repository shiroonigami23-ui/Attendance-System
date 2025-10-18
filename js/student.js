import { db } from './firebase-config.js';
import { doc, setDoc, collection, getDocs, orderBy, query } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
import { Utils } from './utils.js';
// --- UPDATED: Import the new holidays list ---
import { holidays } from './holidays.js';


export class StudentDashboard {
    constructor(configManager) {
        this.configManager = configManager;
        this.currentUser = null;
        this.currentSection = 'A';
        this.attendanceHistory = {}; // This will now store nested subject data
        this.calendarDisplayDate = new Date();
    }

    async init(userData) {
        this.currentUser = userData;
        this.currentSection = userData.section;
        this.calendarDisplayDate = new Date();

        this.renderHeader();
        this.updateCurrentAndNextClass();

        await this.fetchAttendanceHistory(); // Updated function
        this.updateAttendanceStats();
        this.renderAttendanceLog();
        this.renderSubjectAttendance(); // Will now be more accurate
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
    
    async fetchAttendanceHistory() {
        const history = {};
        const recordsCol = collection(db, "attendance", this.currentUser.rollNumber, "records");
        const dateSnapshot = await getDocs(query(recordsCol));

        for (const dateDoc of dateSnapshot.docs) {
            const date = dateDoc.id;
            history[date] = { subjects: {} }; // Prepare a nested object for subjects
            const subjectsCol = collection(db, "attendance", this.currentUser.rollNumber, "records", date, "subjects");
            const subjectSnapshot = await getDocs(subjectsCol);
            
            if(subjectSnapshot.empty) {
                // Handle older, general attendance records if they exist
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

    // --- UPDATED STATS CALCULATION ---
    updateAttendanceStats() {
        let totalPresent = 0;
        let totalClasses = 0;

        Object.values(this.attendanceHistory).forEach(dailyRecord => {
            if(dailyRecord.status === 'present') totalPresent++;
            // Exclude cancelled from total
            if(dailyRecord.status && dailyRecord.status !== 'cancelled') totalClasses++;

            if (dailyRecord.subjects) {
                const subjects = Object.values(dailyRecord.subjects);
                totalPresent += subjects.filter(rec => rec.status === 'present' || rec.status === 'late').length;
                // Exclude cancelled classes from the total count
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

    // --- UPDATED LOG RENDERING ---
    renderAttendanceLog() {
        const tableBody = document.getElementById('attendanceLogTable');
        tableBody.innerHTML = '';
        let hasRecords = false;

        for (const [date, dailyRecord] of Object.entries(this.attendanceHistory)) {
             const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year:'numeric' });

            if(dailyRecord.subjects && Object.keys(dailyRecord.subjects).length > 0) {
                 hasRecords = true;
                for(const [subject, record] of Object.entries(dailyRecord.subjects)){
                    let statusClass = '';
                    let statusText = record.status.toUpperCase();

                    if (record.status === 'present' || record.status === 'late') {
                        statusClass = 'text-success';
                    } else if (record.status === 'absent') {
                        statusClass = 'text-danger';
                    } else { // Cancelled or other statuses
                        statusClass = 'text-muted';
                    }

                    const formattedTime = record.timestamp ? new Date(record.timestamp.toDate()).toLocaleTimeString() : '-';
                    tableBody.innerHTML += `<tr>
                        <td>${formattedDate}</td>
                        <td><span class="${statusClass}" style="font-weight: bold;">${statusText}</span> in ${subject}</td>
                        <td>${formattedTime}</td>
                    </tr>`;
                }
            } else if (dailyRecord.status) {
                // ... logic for old general records
            }
        }

        if (!hasRecords) {
            tableBody.innerHTML = `<tr><td colspan="3" style="text-align:center;">No records found.</td></tr>`;
        }
    }
    
    calculateSubjectAttendance() {
        const subjectStats = {};
        const subjects = this.configManager.getSubjects();

        // Initialize all subjects from config
        subjects.forEach(sub => {
            const subjectName = `${sub.code} - ${sub.name}`;
            subjectStats[subjectName] = { attended: 0, total: 0 };
        });

        // Go through the detailed history
        Object.values(this.attendanceHistory).forEach(dailyRecord => {
            if (dailyRecord.subjects) {
                for (const [subject, record] of Object.entries(dailyRecord.subjects)) {
                    // Make sure not to count cancelled classes in the total
                    if (subjectStats[subject] && record.status !== 'cancelled') {
                        subjectStats[subject].total++;
                        if (record.status === 'present' || record.status === 'late') {
                            subjectStats[subject].attended++;
                        }
                    }
                }
            }
        });
        
        // Calculate percentage for each
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
                 if (total === 0) continue; // Don't show subjects with no classes yet
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
        const firstDayOfMonth = new Date(year, month, 1).getDay();
        
        let html = '';
        ['S', 'M', 'T', 'W', 'T', 'F', 'S'].forEach(day => html += `<div class="calendar-day-header">${day}</div>`);
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
            
            // If any subject was attended that day, mark it as present on the calendar
            if (dailyRecord?.subjects && Object.values(dailyRecord.subjects).some(s => s.status === 'present' || s.status === 'late')) {
                dayClass += ' present';
            } else if (dailyRecord?.status === 'present') { // Support old format
                 dayClass += ' present';
            }

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
        
        let currentPresent = 0;
        let currentTotal = 0;
        Object.values(this.attendanceHistory).forEach(dailyRecord => {
            if(dailyRecord.status === 'present') currentPresent++;
            if(dailyRecord.status) currentTotal++;
            if (dailyRecord.subjects) {
                const subjects = Object.values(dailyRecord.subjects);
                currentPresent += subjects.filter(rec => rec.status === 'present' || rec.status === 'late').length;
                currentTotal += subjects.length;
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
        // This function would also need to know which class is being marked.
        // For QR code scanning, the class info should be embedded in the QR code.
        // We will simplify for now and assume it marks for the currently active class.
        Utils.showAlert("Marking attendance would require class info from the QR code.", "info");
    }

    showAttendanceForDay(dateStr) {
        const dailyRecord = this.attendanceHistory[dateStr];
        const holiday = holidays[dateStr];
        let message = ``;
        const formattedDate = new Date(dateStr + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });

        if (dailyRecord && dailyRecord.subjects && Object.keys(dailyRecord.subjects).length > 0) {
            message = `Attendance for ${formattedDate}:<br>`;
            Object.values(dailyRecord.subjects).forEach(rec => {
                let statusText = `<strong>${rec.status.toUpperCase()}</strong>`;
                if (rec.status === 'cancelled') {
                    statusText = `<em>${rec.status.toUpperCase()}</em>`;
                }
                message += `- ${rec.subject}: ${statusText}<br>`;
            });
        } else if (dailyRecord && dailyRecord.status) {
            message = `General attendance for ${formattedDate}: <strong>${dailyRecord.status.toUpperCase()}</strong>`;
        } else if (holiday) {
            message = `<strong>Holiday on ${formattedDate}:</strong> ${holiday}`;
        } else {
            message = `No attendance records for ${formattedDate}.`;
        }
        
        Utils.showAlert(message, 'info', 10000);
    }
}
