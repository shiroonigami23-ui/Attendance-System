// js/admin.js

import { db } from './firebase-config.js';
import { collection, getDocs, doc, setDoc, deleteDoc } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
import { Utils } from './utils.js';

export class AdminDashboard {
    constructor(configManager) {
        this.registeredStudents = [];
        this.configManager = configManager;
    }

    async init() {
        this.populateClassSelectors();
        await this.loadRegisteredStudents();
        this.setupReportListeners();
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
                html = `<label for="reportDateInput">Date</label><input type="date" id="reportDateInput" class="form-control" value="${todayStr}">`;
                break;
            case 'weekly':
                const week = Math.ceil((((today - new Date(today.getFullYear(), 0, 1)) / 86400000) + new Date(today.getFullYear(), 0, 1).getDay() + 1) / 7);
                const weekStr = `${today.getFullYear()}-W${String(week).padStart(2, '0')}`;
                html = `<label for="reportWeekInput">Week</label><input type="week" id="reportWeekInput" class="form-control" value="${weekStr}">`;
                break;
            case 'monthly':
                const monthStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
                html = `<label for="reportMonthInput">Month</label><input type="month" id="reportMonthInput" class="form-control" value="${monthStr}">`;
                break;
        }
        dateInputContainer.innerHTML = html;
    }


    populateClassSelectors() {
        const subjects = this.configManager.getSubjects();
        const selectors = [
            document.getElementById('manualClassSelector'),
            document.getElementById('cancelClassSelector'),
            document.getElementById('classSelector')
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
        // --- NEW: Populate the datalist after loading students ---
        this.populateRollNumberDatalist();
    }

    // --- NEW: Populates the searchable datalist for manual attendance ---
    populateRollNumberDatalist() {
        const datalist = document.getElementById('studentRollNumbers');
        if (!datalist) return;

        datalist.innerHTML = ''; // Clear existing options
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
                <td>${student.rollNumber || 'N/A'}</td>
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
        const status = document.getElementById('attendanceStatus').value;
        const className = document.getElementById('manualClassSelector').value;

        if (!rollNumber || !className) {
            Utils.showAlert('Please provide a roll number and select a class.', 'warning');
            return;
        }

        const today = new Date().toISOString().split('T')[0];
        const attendanceRef = doc(db, "attendance", rollNumber, "records", today, "subjects", className);
        
        await setDoc(attendanceRef, { 
            status, 
            subject: className,
            timestamp: new Date(), 
            markedBy: 'admin' 
        });

        Utils.showAlert(`Attendance marked for ${rollNumber} in ${className}!`, 'success');
        document.getElementById('manualRollNumber').value = '';
    }

    async cancelClass() {
        const cancelDate = document.getElementById('cancelDate').value;
        const className = document.getElementById('cancelClassSelector').value;

        if (!cancelDate || !className) {
            Utils.showAlert('Please select both a date and a class to cancel.', 'warning');
            return;
        }

        if (!confirm(`Are you sure you want to cancel "${className}" for ALL students on ${cancelDate}?`)) return;

        Utils.showAlert('Processing cancellation for all registered students...', 'info');
        
        const cancellationPromises = this.registeredStudents.map(student => {
            const attendanceRef = doc(db, "attendance", student.rollNumber, "records", cancelDate, "subjects", className);
            return setDoc(attendanceRef, {
                status: 'cancelled',
                subject: className,
                timestamp: new Date(),
                markedBy: 'admin'
            });
        });

        await Promise.all(cancellationPromises);
        Utils.showAlert(`Successfully cancelled "${className}" for all students on ${cancelDate}.`, 'success');
    }

    async generateClassReport() {
        const reportType = document.getElementById('reportType').value;
        const reportDisplay = document.getElementById('reportDisplay');
        reportDisplay.innerHTML = `<p class="text-center p-3">Generating report, please wait...</p>`;

        if (this.registeredStudents.length === 0) {
            await this.loadRegisteredStudents();
        }
        
        switch (reportType) {
            case 'daily':
                await this.generateDailyReport();
                break;
            case 'weekly':
                await this.generateWeeklyReport();
                break;
            case 'monthly':
                await this.generateMonthlyReport();
                break;
        }
    }

    async generateDailyReport() {
        const dateInput = document.getElementById('reportDateInput');
        if (!dateInput || !dateInput.value) {
            Utils.showAlert('Please select a date.', 'warning');
            return;
        }
        const dateStr = dateInput.value;
        const formattedDate = new Date(dateStr + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });

        let records = [];
        for (const student of this.registeredStudents) {
            const subjectsCol = collection(db, "attendance", student.rollNumber, "records", dateStr, "subjects");
            const subjectSnapshot = await getDocs(subjectsCol);
            if (!subjectSnapshot.empty) {
                subjectSnapshot.forEach(doc => {
                    records.push({
                        rollNumber: student.rollNumber,
                        username: student.username,
                        section: student.section,
                        ...doc.data()
                    });
                });
            }
        }

        const headers = ['Roll No', 'Name', 'Section', 'Subject', 'Status', 'Time Marked'];
        const rows = records.map(rec => [
            rec.rollNumber,
            rec.username,
            rec.section,
            rec.subject,
            `<span class="status-badge status-${rec.status}">${rec.status}</span>`,
            rec.timestamp ? new Date(rec.timestamp.toDate()).toLocaleTimeString() : 'N/A'
        ]);

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
                        if (data.status === 'present' || data.status === 'late') {
                            studentStats[student.rollNumber].present++;
                        }
                    }
                });
            }
        }
        
        const headers = ['Roll No', 'Name', 'Section', 'Classes Attended', 'Total Classes', 'Percentage'];
        const rows = Object.entries(studentStats).map(([rollNumber, stats]) => {
            if (stats.total === 0) return null;
            const percentage = Math.round((stats.present / stats.total) * 100);
            const badgeClass = percentage >= 75 ? 'status-present' : percentage >= 60 ? 'status-late' : 'status-absent';
            return [rollNumber, stats.username, stats.section, stats.present, stats.total, `<span class="status-badge ${badgeClass}">${percentage}%</span>`];
        }).filter(row => row !== null);

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
                        if (data.status === 'present' || data.status === 'late') {
                            studentStats[student.rollNumber].present++;
                        }
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
