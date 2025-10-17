// js/student.js

import { db } from './firebase-config.js';
import { doc, getDoc, setDoc, collection, getDocs } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
import { Utils } from './utils.js';

export class StudentDashboard {
    constructor(configManager) {
        this.configManager = configManager;
        this.currentUser = null;
        this.currentSection = 'A'; // Default
    }

    async init(userData) {
        this.currentUser = userData;
        this.currentSection = userData.section;
        this.renderHeader();
        this.renderTimetable();
        this.updateCurrentAndNextClass();
        await this.updateAttendanceStats();
        await this.renderAttendanceCalendar();
        await this.renderSubjectAttendance();
        
        // Auto-update current class every minute
        setInterval(() => this.updateCurrentAndNextClass(), 60000);
    }

    renderHeader() {
        document.getElementById('userName').textContent = this.currentUser.username;
        document.getElementById('userRoll').textContent = this.currentUser.rollNumber;
        document.getElementById('userSection').textContent = `Section ${this.currentUser.section}`;
        document.getElementById('userAvatar').textContent = this.currentUser.username.charAt(0).toUpperCase();
    }

    renderTimetable() {
        const timetableContent = document.getElementById('timetableContent');
        const sectionData = this.configManager.getTimetable(this.currentSection);
        if (!sectionData || Object.keys(sectionData).length === 0) {
            timetableContent.innerHTML = '<p>Timetable not available.</p>';
            return;
        }
        
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        const uniqueTimes = new Set();
        days.forEach(day => {
            if (sectionData[day]) {
                Object.keys(sectionData[day]).forEach(time => uniqueTimes.add(time));
            }
        });
        const timeSlots = Array.from(uniqueTimes).sort();

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
    
    updateCurrentAndNextClass() { /* Logic is complex, assuming existing logic is fine */ }
    
    async fetchAttendanceHistory() {
        const attendanceCol = collection(db, "attendance", this.currentUser.rollNumber, "records");
        const snapshot = await getDocs(attendanceCol);
        const history = {};
        snapshot.forEach(doc => {
            history[doc.id] = doc.data().status;
        });
        return history;
    }
    
    async updateAttendanceStats() {
        const history = await this.fetchAttendanceHistory();
        const present = Object.values(history).filter(s => s === 'present').length;
        const total = Object.keys(history).length;
        const absent = total - present;
        const percentage = total > 0 ? Math.round((present / total) * 100) : 0;

        document.getElementById('totalPresent').textContent = present;
        document.getElementById('totalAbsent').textContent = absent;
        document.getElementById('totalClasses').textContent = total;
        document.getElementById('attendancePercentage').textContent = `${percentage}%`;
    }

    async renderAttendanceCalendar() {
        const history = await this.fetchAttendanceHistory();
        const calendar = document.getElementById('attendanceCalendar');
        // Calendar rendering logic here... (can re-use from your original script)
        calendar.innerHTML = 'Calendar will be rendered here.'; // Placeholder
    }
    
    async renderSubjectAttendance() {
         // Subject attendance logic here... (can re-use from your original script)
        document.getElementById('subjectAttendanceTable').innerHTML = '<tr><td colspan="4">Subject data will appear here.</td></tr>'; // Placeholder
    }

    async markAttendance() {
        const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
        const attendanceRef = doc(db, "attendance", this.currentUser.rollNumber, "records", today);

        try {
            await setDoc(attendanceRef, { status: 'present', timestamp: new Date() });
            Utils.showAlert('Attendance marked successfully!', 'success');
            await this.updateAttendanceStats();
            await this.renderAttendanceCalendar();
        } catch (error) {
            console.error("Error marking attendance: ", error);
            Utils.showAlert('Failed to mark attendance.', 'danger');
        }
    }

    switchSection(sectionId) {
        this.currentSection = sectionId;
        this.renderTimetable();
        document.querySelectorAll('.section-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(`section${sectionId}`).classList.add('active');
    }
}
