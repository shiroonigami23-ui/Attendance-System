// js/admin.js

import { db } from './firebase-config.js';
import { collection, getDocs, doc, setDoc, getDoc, updateDoc, deleteDoc, arrayUnion } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
import { Utils } from './utils.js';
import { holidays } from './holidays.js';

export class AdminDashboard {
    constructor(configManager) {
        this.registeredStudents = [];
        this.configManager = configManager;
        this.currentReportData = null; 
        this.classStartDate = this.getNextMonday();
    }

    getNextMonday() {
        const date = new Date();
        const today = date.getDay();
        const offset = today === 0 ? 1 : (8 - today) % 7;
        const nextMonday = new Date(date);
        nextMonday.setDate(date.getDate() + offset);
        nextMonday.setHours(0, 0, 0, 0); 
        console.log(`Official class start date set to: ${nextMonday.toDateString()}`);
        return nextMonday;
    }

    async init() {
        this.populateClassSelectors();
        await this.loadRegisteredStudents();
        this.setupReportListeners();
        this.setManualAttendanceDate();
        this.setCancelDate();
    }

    setManualAttendanceDate() {
        const dateInput = document.getElementById('manualAttendanceDate');
        if (dateInput) {
            const today = new Date();
            dateInput.max = today.toISOString().split('T')[0];
            dateInput.value = today.toISOString().split('T')[0];
        }
    }

    // --- NEW: Set min date for cancellation date picker ---
    setCancelDate() {
        const dateInput = document.getElementById('cancelDate');
        if (dateInput) {
            const today = new Date();
            dateInput.min = today.toISOString().split('T')[0];
            dateInput.value = today.toISOString().split('T')[0];
        }
    }
    
    setupReportListeners() {
        const reportTypeSelect = document.getElementById('reportType');
        if (reportTypeSelect) {
            reportTypeSelect.addEventListener('change', this.updateReportInput.bind(this));
        }
        this.updateReportInput();
    }

    updateReportInput() {
        const reportType = document.getElementById('reportType').value;
        const dateInputContainer = document.getElementById('reportDateContainer');
        if (!dateInputContainer) return;

        let html = '';
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];

        switch (reportType) {
            case 'daily':
                html = `<label for="reportDateInput">Date</label><input type="date" id="reportDateInput" class="form-control" value="${todayStr}" max="${todayStr}">`;
                break;
            case 'weekly':
                const week = Math.ceil((((today - new Date(today.getFullYear(), 0, 1)) / 86400000) + new Date(today.getFullYear(), 0, 1).getDay() + 1) / 7);
                const weekStr = `${today.getFullYear()}-W${String(week).padStart(2, '0')}`;
                html = `<label for="reportWeekInput">Week</label><input type="week" id="reportWeekInput" class="form-control" value="${weekStr}" max="${weekStr}">`;
                break;
            case 'monthly':
                const monthStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
                html = `<label for="reportMonthInput">Month</label><input type="month" id="reportMonthInput" class="form-control" value="${monthStr}" max="${monthStr}">`;
                break;
        }
        dateInputContainer.innerHTML = html;
    }


    populateClassSelectors() {
        const subjects = this.configManager.getSubjects();
        const selectors = [
            document.getElementById('manualClassSelector'),
            document.getElementById('cancelClassSelector')
        ];

        selectors.forEach(selector => {
            if (selector) selector.innerHTML = '';
        });

        if (subjects.length === 0) {
            const defaultOption = '<option value="">No subjects found</option>';
            selectors.forEach(selector => {
                if (selector) selector.innerHTML = defaultOption;
            });
            return;
        }

        subjects.forEach(subject => {
            const subjectName = `${subject.code} - ${subject.name}`;
            const option = document.createElement('option');
            option.value = subjectName;
            option.textContent = subjectName;
            
            selectors.forEach(selector => {
                if (selector) selector.appendChild(option.cloneNode(true));
            });
        });
    }

    async loadRegisteredStudents() {
        console.log("Admin: Fetching registered students...");
        const usersSnap = await getDocs(collection(db, "users"));
        const devicesSnap = await getDocs(collection(db, "devices"));
        
        const deviceMap = new Map();
        devicesSnap.forEach(doc => {
            deviceMap.set(doc.data().rollNumber, { deviceId: doc.id, lastLogin: doc.data().lastLogin });
        });

        this.registeredStudents = usersSnap.docs
            .map(doc => {
                const student = doc.data();
                if (student.role !== 'student') return null;
                const deviceInfo = deviceMap.get(student.rollNumber);
                return {
                    ...student,
                    deviceId: deviceInfo?.deviceId,
                    lastLogin: deviceInfo?.lastLogin
                };
            })
            .filter(student => student !== null);

        this.renderStudentTable();
        this.updateAdminStats();
        this.populateRollNumberDatalist();
    }
    
    populateRollNumberDatalist() {
        const datalist = document.getElementById('studentRollNumbers');
        if (!datalist) return;

        datalist.innerHTML = ''; 
        if (this.registeredStudents.length > 0) {
            this.registeredStudents.forEach(student => {
                const option = document.createElement('option');
                option.value = student.rollNumber;
                option.textContent = `${student.username} (${student.section})`;
                datalist.appendChild(option);
            });
        }
    }


    renderStudentTable() {
        const tbody = document.getElementById('studentTableBody');
        tbody.innerHTML = ''; 

        if (this.registeredStudents.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center;">No registered students found.</td></tr>`;
            return;
        }

        this.registeredStudents.forEach(student => {
            const row = document.createElement('tr');
            const lastLoginDate = student.lastLogin ? new Date(student.lastLogin).toLocaleString() : 'N/A';
            const deviceIdText = student.deviceId ? `${student.deviceId.substring(0, 15)}...` : 'Not Registered';
            const actionButton = student.deviceId ? `<button class="btn btn-sm btn-danger" onclick="window.app.adminDashboard.forceLogoutStudent('${student.deviceId}')"><i class="fas fa-sign-out-alt"></i></button>` : '';

            row.innerHTML = `
                <td><a href="#" class="roll-number-link" onclick="viewStudentDetails('${student.rollNumber}')">${student.rollNumber || 'N/A'}</a></td>
                <td>${student.username || 'N/A'}</td>
                <td>${student.section || 'N/A'}</td>
                <td>${deviceIdText}</td>
                <td>${lastLoginDate}</td>
                <td><span class="status-badge status-present">Active</span></td>
                <td>${actionButton}</td>
            `;
            tbody.appendChild(row);
        });
    }

    async showStudentAttendanceModal(rollNumber) {
        const modal = document.getElementById('studentDetailModal');
        const modalContent = document.getElementById('modalContent');
        const student = this.registeredStudents.find(s => s.rollNumber === rollNumber);
    
        if (!student) {
            Utils.showAlert('Could not find student details.', 'danger');
            return;
        }
    
        modal.classList.remove('hidden');
        modalContent.innerHTML = `<div id="modalLoader"><i class="fas fa-spinner fa-spin"></i><p>Calculating accurate attendance...</p></div>`;
    
        try {
            const semesterStartDate = this.classStartDate;
            const today = new Date();
    
            const attendanceMap = new Map();
            const recordsCol = collection(db, "attendance", rollNumber, "records");
            const dateSnapshot = await getDocs(recordsCol);
    
            for (const dateDoc of dateSnapshot.docs) {
                const subjectsMap = new Map();
                const subjectsCol = collection(db, "attendance", rollNumber, "records", dateDoc.id, "subjects");
                const subjectSnapshot = await getDocs(subjectsCol);
                subjectSnapshot.forEach(subjectDoc => {
                    subjectsMap.set(subjectDoc.id, subjectDoc.data());
                });
                attendanceMap.set(dateDoc.id, subjectsMap);
            }
    
            let totalScheduledClasses = 0;
            let totalAttendedClasses = 0;
            const sectionTimetable = this.configManager.getTimetable(student.section);
    
            if (today >= semesterStartDate) {
                for (let d = new Date(semesterStartDate); d <= today; d.setDate(d.getDate() + 1)) {
                    const dateStr = d.toISOString().split('T')[0];
                    const dayOfWeek = d.getDay();
    
                    if (dayOfWeek === 0 || dayOfWeek === 6 || holidays[dateStr]) continue;
    
                    const dayName = d.toLocaleString('en-US', { weekday: 'long' });
                    const daySchedule = sectionTimetable ? sectionTimetable[dayName] : null;
    
                    if (daySchedule) {
                        const dailyAttendance = attendanceMap.get(dateStr);
                        for (const slot of Object.values(daySchedule)) {
                            if (slot.type.toLowerCase() === 'break' || slot.subject.toLowerCase().includes('study')) continue;
                            
                            totalScheduledClasses++;
                            const record = dailyAttendance ? dailyAttendance.get(slot.subject) : null;
    
                            if (record && (record.status === 'present' || record.status === 'late')) {
                                totalAttendedClasses++;
                            }
                        }
                    }
                }
            }
    
            const percentage = totalScheduledClasses > 0 ? Math.round((totalAttendedClasses / totalScheduledClasses) * 100) : 0;
            const badgeClass = percentage >= 75 ? 'text-success' : percentage >= 60 ? 'text-warning' : 'text-danger';
    
            modalContent.innerHTML = `
                <h3>${student.username}</h3>
                <p>${student.rollNumber} | Section ${student.section}</p>
                <div id="modalPercentage" class="${badgeClass}" style="background: none; border: none; padding: 0;">${percentage}%</div>
                <div class="text-center">
                    <p><strong>${totalAttendedClasses}</strong> classes attended out of <strong>${totalScheduledClasses}</strong> total scheduled classes.</p>
                </div>
            `;
        } catch (error) {
            console.error("Error calculating student attendance:", error);
            modalContent.innerHTML = `<p class="text-danger">Could not calculate attendance. Please try again.</p>`;
        }
    }
    
    updateAdminStats() {
        document.getElementById('totalStudents').textContent = this.registeredStudents.length;
        document.getElementById('activeStudents').textContent = this.registeredStudents.filter(s => s.deviceId).length;
        document.getElementById('totalDevices').textContent = this.registeredStudents.filter(s => s.deviceId).length;
    }

    async forceLogoutStudent(deviceId) {
        if (confirm(`Are you sure you want to de-register this device? The student will need to log in again.`)) {
            await deleteDoc(doc(db, "devices", deviceId));
            Utils.showAlert('Device de-registered successfully!', 'success');
            await this.loadRegisteredStudents();
        }
    }

    async markManualAttendance() {
        const rollNumber = document.getElementById('manualRollNumber').value;
        const newStatus = document.getElementById('attendanceStatus').value;
        
        const fullClassNameFromSelector = document.getElementById('manualClassSelector').value;
        const dateInput = document.getElementById('manualAttendanceDate');
        const dateStr = dateInput.value;

        // --- VALIDATION 1: Check against future date (tomorrow or later) ---
        const todayStr = new Date().toISOString().split('T')[0];
        if (dateStr > todayStr) { 
            Utils.showAlert("You cannot mark attendance for a future date (wait for class to happen).", 'danger');
            return;
        }

        if (!rollNumber || !fullClassNameFromSelector || !dateStr) {
            Utils.showAlert('Please select a date, roll number, and class.', 'warning');
            return;
        }

        const subjectCode = fullClassNameFromSelector.split(' - ')[0]; 
        
        const student = this.registeredStudents.find(s => s.rollNumber === rollNumber);
        if (!student) {
            Utils.showAlert(`Student with roll number ${rollNumber} not found.`, 'danger');
            return;
        }
        
        const dayOfWeek = new Date(dateStr + 'T00:00:00').toLocaleString('en-US', { weekday: 'long' });
        const timetable = this.configManager.getTimetable(student.section);
        const daySchedule = timetable ? timetable[dayOfWeek] : null;

        if (!daySchedule) {
            Utils.showAlert(`No classes are scheduled on ${dayOfWeek} for Section ${student.section}.`, 'warning');
            return;
        }
        
        const classCode = fullClassNameFromSelector.split(' - ')[0].replace(/\s/g, '');
        
        // Find all slots for this subject today
        const subjectSlots = Object.entries(daySchedule)
            .filter(([timeSlot, slotInfo]) => slotInfo.subject.replace(/\s/g, '').includes(classCode));
        
        if (subjectSlots.length === 0) {
            Utils.showAlert(`The class "${fullClassNameFromSelector}" is not scheduled on ${dayOfWeek} for Section ${student.section}.`, 'warning');
            return;
        }

        // --- VALIDATION 2: Check against current time (Only for TODAY) ---
        if (dateStr === todayStr) {
            
            // 1. Find the LATEST end time among all slots for this class today.
            const latestEndTimeStr = subjectSlots
                .map(([timeSlot, slotInfo]) => timeSlot.split('-')[1])
                .sort() // Simple string sort works for HH:MM format
                .pop();
            
            if (latestEndTimeStr) {
                const [endHour, endMinute] = latestEndTimeStr.split(':').map(Number);
                
                // 2. Create a Date object for the class's scheduled end time today.
                const classEndDateTime = new Date();
                classEndDateTime.setHours(endHour, endMinute, 0, 0);

                // 3. Check if the class is still in the future.
                if (new Date() < classEndDateTime) {
                    Utils.showAlert(`Cannot mark attendance. The last scheduled slot for this class ends at ${latestEndTimeStr}.`, 'danger');
                    return;
                }
            }
        }
        
        // All validation passed. Proceed with DB operations.
        const attendanceRef = doc(db, "attendance", rollNumber, "records", dateStr, "subjects", subjectCode);
        const parentRecordRef = doc(db, "attendance", rollNumber, "records", dateStr); 
        
        try {
            const docSnap = await getDoc(attendanceRef);
            if (docSnap.exists()) {
                const existingData = docSnap.data();
                if (existingData.status === newStatus) {
                    Utils.showAlert(`Student is already marked as '${newStatus}'. No change needed.`, 'info');
                    return;
                }
                
                if (confirm(`A record by '${existingData.markedBy}' already exists with status '${existingData.status}'. Change to '${newStatus}'?`)) {
                    await updateDoc(attendanceRef, { 
                        status: newStatus, 
                        markedBy: 'admin', 
                        timestamp: new Date(),
                        subject: fullClassNameFromSelector 
                    });
                    
                    await setDoc(parentRecordRef, { updated: new Date() }, { merge: true });

                    Utils.showAlert('Attendance record updated successfully!', 'success');
                }
            } else {
                await setDoc(attendanceRef, { 
                    status: newStatus, 
                    subject: fullClassNameFromSelector,
                    markedBy: 'admin', 
                    timestamp: new Date() 
                });
                
                await setDoc(parentRecordRef, { created: new Date() }, { merge: true });

                Utils.showAlert(`Attendance marked successfully for ${rollNumber}!`, 'success');
            }
             document.getElementById('manualRollNumber').value = '';
        } catch (error) {
            console.error("Error marking attendance:", error);
            Utils.showAlert('Failed to save attendance. Check console for details.', 'danger');
        }
    }
                    
    

    async cancelClass() {
        const dateStr = document.getElementById('cancelDate').value; 
        const fullClassName = document.getElementById('cancelClassSelector').value; // e.g., "CS503 - Data Analytics"
        const sectionId = document.getElementById('cancelSectionSelector').value;

        if (!dateStr || !fullClassName) {
            Utils.showAlert('Please select a date and a class to cancel.', 'warning');
            return;
        }

        const subjectCode = fullClassName.split(' - ')[0]; // Key for DB
        
        const date = new Date(dateStr + 'T00:00:00');
        const dayOfWeek = date.getDay();
        if (dayOfWeek === 0 || dayOfWeek === 6 || holidays[dateStr]) {
            Utils.showAlert(`Cannot cancel a class on a weekend or a holiday (${holidays[dateStr] || 'Weekend'}).`, 'warning');
            return;
        }

        const dayName = date.toLocaleString('en-US', { weekday: 'long' });
        const sectionsToCheck = sectionId === 'All' ? ['A', 'B'] : [sectionId];
        let isClassScheduled = false;

        for (const sec of sectionsToCheck) {
            const timetable = this.configManager.getTimetable(sec);
            const daySchedule = timetable ? timetable[dayName] : null;
            // Check if any slot for this section contains the subject code/name
            if (daySchedule && Object.values(daySchedule).some(slot => slot.subject.includes(subjectCode))) {
                isClassScheduled = true;
                break;
            }
        }

        if (!isClassScheduled) {
            Utils.showAlert(`The class "${fullClassName}" is not scheduled for Section(s) ${sectionsToCheck.join(', ')} on ${dayName}.`, 'danger');
            return;
        }

        if (!confirm(`Are you sure you want to cancel "${fullClassName}" for Section(s) ${sectionsToCheck.join(', ')} on ${dateStr}?`)) return;

        const studentsToNotify = this.registeredStudents.filter(s => sectionsToCheck.includes(s.section));
        if (studentsToNotify.length === 0) {
            Utils.showAlert('No registered students found in the selected section(s).', 'info');
            return;
        }

        Utils.showAlert('Processing cancellation...', 'info');
        
        const promises = studentsToNotify.map(student => {
            // Find the full class name as it appears in the student's timetable for the 'subject' field
            const studentTimetable = this.configManager.getTimetable(student.section)?.[dayName];
            const actualTimetableSubject = Object.values(studentTimetable || {}).find(slot => slot.subject.includes(subjectCode))?.subject || fullClassName;
            
            
            const ref = doc(db, "attendance", student.rollNumber, "records", dateStr, "subjects", subjectCode); 
            return setDoc(ref, { status: 'cancelled', subject: actualTimetableSubject, markedBy: 'admin', timestamp: new Date() });
        });
        await Promise.all(promises);

        const noticeRef = doc(db, "cancellations", dateStr);
        await setDoc(noticeRef, {
            cancelledClasses: arrayUnion({ className: fullClassName, timestamp: new Date(), sections: sectionsToCheck })
        }, { merge: true });

        Utils.showAlert(`Successfully cancelled "${fullClassName}" for the selected sections.`, 'success');
    }


    async cancelEntireDay() {
        const dateStr = document.getElementById('cancelDate').value;
        const sectionId = document.getElementById('cancelSectionSelector').value;

        if (!dateStr) {
            Utils.showAlert('Please select a date to cancel.', 'warning');
            return;
        }

        const date = new Date(dateStr + 'T00:00:00');
        const dayOfWeek = date.getDay();
        if (dayOfWeek === 0 || dayOfWeek === 6 || holidays[dateStr]) {
            Utils.showAlert(`Cannot cancel an entire day on a weekend or a holiday.`, 'warning');
            return;
        }

        const dayName = date.toLocaleString('en-US', { weekday: 'long' });
        const sectionsToCancel = sectionId === 'All' ? ['A', 'B'] : [sectionId];
        let classesToCancelBySection = {};
        let totalClassesToCancel = 0;

        for (const sec of sectionsToCancel) {
            const timetable = this.configManager.getTimetable(sec);
            const daySchedule = timetable ? timetable[dayName] : null;
            if (daySchedule) {
                const dailyClasses = Object.values(daySchedule)
                    .filter(slot => slot.type.toLowerCase() !== 'break' && !slot.subject.toLowerCase().includes('study'))
                    .map(slot => slot.subject);
                if (dailyClasses.length > 0) {
                    classesToCancelBySection[sec] = [...new Set(dailyClasses)];
                    totalClassesToCancel += classesToCancelBySection[sec].length;
                }
            }
        }
  if (totalClassesToCancel === 0) {
            Utils.showAlert(`No classes are scheduled for Section(s) ${sectionsToCancel.join(', ')} on ${dayName}.`, 'info');
            return;
        }

        if (!confirm(`This will cancel ${totalClassesToCancel} class(es) for Section(s) ${sectionsToCancel.join(', ')} on ${dateStr}. Are you sure?`)) return;

        const studentsToNotify = this.registeredStudents.filter(s => sectionsToCancel.includes(s.section));
        if (studentsToNotify.length === 0) {
            Utils.showAlert('No students found for the selected section(s).', 'info');
            return;
        }

        Utils.showAlert('Processing full-day cancellation...', 'info');
        const promises = [];
        const cancellationNotices = [];

        for (const student of studentsToNotify) {
            const studentClasses = classesToCancelBySection[student.section];
            if (studentClasses) {
                for (const className of studentClasses) {
                    const ref = doc(db, "attendance", student.rollNumber, "records", dateStr, "subjects", className);
                    promises.push(setDoc(ref, { status: 'cancelled', subject: className, markedBy: 'admin', timestamp: new Date() }));
                }
            }
        }
        
        for (const [sec, classes] of Object.entries(classesToCancelBySection)) {
            classes.forEach(className => {
                cancellationNotices.push({ className, timestamp: new Date(), sections: [sec] });
            });
        }

        await Promise.all(promises);

        const noticeRef = doc(db, "cancellations", dateStr);
        await setDoc(noticeRef, {
            cancelledClasses: arrayUnion(...cancellationNotices)
        }, { merge: true });

        Utils.showAlert(`Successfully cancelled all classes for ${dateStr}.`, 'success');
    }

    async synchronizeAttendanceForDate(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        if (date < this.classStartDate) {
            console.log(`Skipping DB write for ${dateStr} (before official start date).`);
            return;
        }

        const reportDisplay = document.getElementById('reportDisplay');
        if (reportDisplay) reportDisplay.innerHTML = `<p class="text-center p-3"><i class="fas fa-sync-alt fa-spin"></i> Synchronizing attendance records for ${dateStr}...</p>`;

        const dayOfWeek = date.getDay();
        if (dayOfWeek === 0 || dayOfWeek === 6 || holidays[dateStr]) return;

        const dayName = date.toLocaleString('en-US', { weekday: 'long' });

        for (const sectionId of ['A', 'B']) {
            const sectionTimetable = this.configManager.getTimetable(sectionId);
            const daySchedule = sectionTimetable ? sectionTimetable[dayName] : null;
            if (!daySchedule) continue;

            const studentsInSection = this.registeredStudents.filter(s => s.section === sectionId);
            if (studentsInSection.length === 0) continue;

            const uniqueClasses = [...new Set(Object.values(daySchedule)
                .filter(slot => slot.type.toLowerCase() !== 'break' && !slot.subject.toLowerCase().includes('study'))
                .map(slot => slot.subject))];

            for (const className of uniqueClasses) {
                for (const student of studentsInSection) {
                    const attendanceRef = doc(db, "attendance", student.rollNumber, "records", dateStr, "subjects", className);
                    const docSnap = await getDoc(attendanceRef);
                    if (!docSnap.exists()) {
                        await setDoc(attendanceRef, { status: 'absent', subject: className, markedBy: 'system', timestamp: new Date() });
                        const parentRecordRef = doc(db, "attendance", student.rollNumber, "records", dateStr);
                        await setDoc(parentRecordRef, { synced: new Date() }, { merge: true });
                    }
                    }
                }
            }
        }



    async generateClassReport() {
        const reportType = document.getElementById('reportType').value;
        const reportDisplay = document.getElementById('reportDisplay');
        reportDisplay.innerHTML = `<p class="text-center p-3">Generating report, please wait...</p>`;
        this.currentReportData = null;

        if (this.registeredStudents.length === 0) await this.loadRegisteredStudents();
        
        switch (reportType) {
            case 'daily': await this.generateDailyReport(); break;
            case 'weekly': await this.generateWeeklyReport(); break;
            case 'monthly': await this.generateMonthlyReport(); break;
        }
    }

    async generateDailyReport() {
        const dateInput = document.getElementById('reportDateInput');
        if (!dateInput || !dateInput.value) {
            Utils.showAlert('Please select a date.', 'warning');
            return;
        }
        const dateStr = dateInput.value;
        
        await this.synchronizeAttendanceForDate(dateStr);

        const formattedDate = new Date(dateStr + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
        let records = [];
        for (const student of this.registeredStudents) {
            const subjectsCol = collection(db, "attendance", student.rollNumber, "records", dateStr, "subjects");
            const subjectSnapshot = await getDocs(subjectsCol);
            if (!subjectSnapshot.empty) {
                subjectSnapshot.forEach(doc => {
                    records.push({ rollNumber: student.rollNumber, username: student.username, section: student.section, ...doc.data() });
                });
            }
        }

        const headers = ['Roll No', 'Name', 'Section', 'Subject', 'Status', 'Marked By', 'Time'];
        const rows = records.map(rec => [
            rec.rollNumber, rec.username, rec.section, rec.subject,
            `<span class="status-badge status-${rec.status}">${rec.status}</span>`,
            rec.markedBy || 'N/A',
            rec.timestamp ? new Date(rec.timestamp.toDate()).toLocaleTimeString() : 'N/A'
        ]);
        
        this.currentReportData = {
            title: `Daily_Report_${dateStr}`,
            headers: headers,
            rows: records.map(rec => [rec.rollNumber, rec.username, rec.section, rec.subject, rec.status, rec.markedBy, rec.timestamp ? new Date(rec.timestamp.toDate()).toLocaleString() : 'N/A'])
        };

        this.renderReport(`Daily Report for ${formattedDate}`, headers, rows);
    }
    
    async generateWeeklyReport() {
        const weekInput = document.getElementById('reportWeekInput');
        if (!weekInput || !weekInput.value) {
            Utils.showAlert('Please select a week.', 'warning');
            return;
        }
        const [year, week] = weekInput.value.split('-W');
        
        const d = new Date(`Jan 01, ${year} 01:00:00`);
        const w = d.getTime() + 604800000 * (week -1);
        const weekStart = new Date(w);
        const dates = Array.from({length: 7}, (_, i) => {
            const targetDate = new Date(weekStart);
            targetDate.setDate(targetDate.getDate() + i);
            return targetDate.toISOString().split('T')[0];
        });

        for (const dateStr of dates) await this.synchronizeAttendanceForDate(dateStr);

        const studentStats = {};
        this.registeredStudents.forEach(s => {
            studentStats[s.rollNumber] = { username: s.username, section: s.section, present: 0, total: 0 };
        });

        for (const dateStr of dates) {
            for (const student of this.registeredStudents) {
                const subjectsCol = collection(db, "attendance", student.rollNumber, "records", dateStr, "subjects");
                const subjectSnapshot = await getDocs(subjectsCol);
                subjectSnapshot.forEach(doc => {
                    const data = doc.data();
                    if (data.status !== 'cancelled') {
                        studentStats[student.rollNumber].total++;
                        if (data.status === 'present' || data.status === 'late') studentStats[student.rollNumber].present++;
                    }
                });
            }
        }
        
        const headers = ['Roll No', 'Name', 'Section', 'Attended', 'Total', 'Percentage'];
        const rows = Object.entries(studentStats).map(([rollNumber, stats]) => {
            if (stats.total === 0) return null;
            const percentage = Math.round((stats.present / stats.total) * 100);
            const badgeClass = percentage >= 75 ? 'status-present' : percentage >= 60 ? 'status-late' : 'status-absent';
            return [rollNumber, stats.username, stats.section, stats.present, stats.total, `<span class="status-badge ${badgeClass}">${percentage}%</span>`];
        }).filter(Boolean);

        this.currentReportData = {
            title: `Weekly_Report_${year}_W${week}`,
            headers: headers,
            rows: Object.entries(studentStats).map(([rollNumber, stats]) => {
                if (stats.total === 0) return null;
                const percentage = Math.round((stats.present / stats.total) * 100);
                return [rollNumber, stats.username, stats.section, stats.present, stats.total, `${percentage}%`];
            }).filter(Boolean)
        };

        this.renderReport(`Weekly Report for Week ${week}, ${year}`, headers, rows);
    }

    async generateMonthlyReport() {
        const monthInput = document.getElementById('reportMonthInput');
        if (!monthInput || !monthInput.value) {
            Utils.showAlert('Please select a month.', 'warning');
            return;
        }
        const [year, month] = monthInput.value.split('-');
        
        const startDate = new Date(year, month - 1, 1);
        const endDate = new Date(year, month, 0);

        const dates = [];
        for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
            dates.push(new Date(d).toISOString().split('T')[0]);
        }

        for (const dateStr of dates) await this.synchronizeAttendanceForDate(dateStr);

        const studentStats = {};
        this.registeredStudents.forEach(s => {
            studentStats[s.rollNumber] = { username: s.username, section: s.section, present: 0, total: 0 };
        });

        for (const dateStr of dates) {
            for (const student of this.registeredStudents) {
                const subjectsCol = collection(db, "attendance", student.rollNumber, "records", dateStr, "subjects");
                const subjectSnapshot = await getDocs(subjectsCol);
                subjectSnapshot.forEach(doc => {
                    const data = doc.data();
                    if (data.status !== 'cancelled') {
                        studentStats[student.rollNumber].total++;
                        if (data.status === 'present' || data.status === 'late') studentStats[student.rollNumber].present++;
                    }
                });
            }
        }

        const headers = ['Roll No', 'Name', 'Section', 'Attendance Percentage'];
        const defaulterThreshold = 75;
        const rows = Object.entries(studentStats)
            .map(([rollNumber, stats]) => {
                if (stats.total === 0) return null;
                const percentage = Math.round((stats.present / stats.total) * 100);
                return { rollNumber, stats, percentage };
            })
            .filter(item => item && item.percentage < defaulterThreshold)
            .map(item => [item.rollNumber, item.stats.username, item.stats.section, `<span class="status-badge status-absent">${item.percentage}%</span>`]);

        const monthName = new Date(year, month - 1).toLocaleString('default', { month: 'long' });
        
         this.currentReportData = {
            title: `Defaulter_Report_${monthName}_${year}`,
            headers: headers,
            rows: Object.entries(studentStats)
                .map(([rollNumber, stats]) => {
                    if (stats.total === 0) return null;
                    const percentage = Math.round((stats.present / stats.total) * 100);
                    return { rollNumber, stats, percentage };
                })
                .filter(item => item && item.percentage < defaulterThreshold)
                .map(item => [item.rollNumber, item.stats.username, item.stats.section, `${item.percentage}%`])
        };
        
        this.renderReport(`Monthly Defaulter List (< ${defaulterThreshold}%) for ${monthName} ${year}`, headers, rows);
    }

    renderReport(title, headers, rows) {
        const reportDisplay = document.getElementById('reportDisplay');
        if (!reportDisplay) return;

        if (rows.length === 0) {
            reportDisplay.innerHTML = `
                <h4 class="card-title mt-4">${title}</h4>
                <p class="text-center text-muted mt-3 p-3">No data found for the selected period.</p>
            `;
            return;
        }

        let tableHTML = `
            <h4 class="card-title mt-4">${title}</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>
                    </thead>
                    <tbody>
                        ${rows.map(row => `<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`).join('')}
                    </tbody>
                </table>
            </div>
        `;
        reportDisplay.innerHTML = tableHTML;
    }

    exportCurrentReport() {
        if (!this.currentReportData || !this.currentReportData.rows || this.currentReportData.rows.length === 0) {
            Utils.showAlert('No report generated to export. Please generate a report first.', 'warning');
            return;
        }

        const { title, headers, rows } = this.currentReportData;

        const sanitizeRow = row => row.map(cell => {
            const cellStr = String(cell).replace(/<[^>]*>?/gm, '').replace(/"/g, '""');
            return `"${cellStr}"`;
        });
        
        const csvContent = "data:text/csv;charset=utf-8," 
            + headers.join(',') + '\n' 
            + rows.map(sanitizeRow).map(e => e.join(',')).join('\n');

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `${title}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        Utils.showAlert('Report exported successfully!', 'success');
    }

     exportStudentData() {
        if (this.registeredStudents.length === 0) {
            Utils.showAlert('No student data to export.', 'warning');
            return;
        }

        const headers = ['RollNumber', 'Username', 'Section', 'DeviceID', 'LastLogin'];
        
        const studentSet = new Set(this.registeredStudents.map(s => JSON.stringify(s)));
        const uniqueStudents = Array.from(studentSet).map(s => JSON.parse(s));

        const rows = uniqueStudents.map(student => [
            student.rollNumber,
            `"${student.username.replace(/"/g, '""')}"`,
            student.section,
            student.deviceId || 'N/A',
            student.lastLogin ? new Date(student.lastLogin).toLocaleString() : 'N/A'
        ]);

        const csvContent = "data:text/csv;charset=utf-8," 
            + headers.join(',') + '\n' 
            + rows.map(e => e.join(',')).join('\n');

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `student_data_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);

        link.click();
        document.body.removeChild(link);
        Utils.showAlert('Student data exported!', 'success');
    }


    async bulkLogout() {
        if (confirm("DANGER: This will de-register ALL devices, forcing every student to log in again. Are you sure?")) {
            Utils.showAlert('De-registering all devices... This may take a moment.', 'info');
            const devicesSnap = await getDocs(collection(db, "devices"));
            const promises = devicesSnap.docs.map(d => deleteDoc(d.ref));
            await Promise.all(promises);
            Utils.showAlert('All devices have been successfully de-registered.', 'success');
            await this.loadRegisteredStudents();
        }
    }

    async clearAllDeviceData() {
      if(confirm("DANGER: This will de-register ALL devices. Are you sure?")) {
        await this.bulkLogout();
      }
    }
}
