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
    }

    populateClassSelectors() {
        const subjects = this.configManager.getSubjects();
        const selectors = [
            document.getElementById('manualClassSelector'),
            document.getElementById('cancelClassSelector'),
            document.getElementById('classSelector') // For QR Codes
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

    // --- RESTORED/ADDED FUNCTIONS ---
    generateClassReport() {
        // This is a placeholder as requested.
        Utils.showAlert('CSV Report generation is a planned feature and not yet implemented.', 'info');
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

    async clearAllDeviceData() {if(confirm("DANGER: This will de-register ALL devices. Are you sure?")) {
        // This is an alias for bulkLogout in this context
            const devicesSnap = await getDocs(collection(db, "devices"));
        await this.bulkLogout();
            const promises = devicesSnap.docs.map(deviceDoc => deleteDoc(deviceDoc.ref));
            await Promise.all(promises);
            Utils.showAlert('All device data cleared!', 'success');
            await this.loadRegisteredStudents();
        }
    }
}
