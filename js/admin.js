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
        const manualSelector = document.getElementById('manualClassSelector');
        const cancelSelector = document.getElementById('cancelClassSelector');
        const classSelector = document.getElementById('classSelector');

        // Clear existing options
        manualSelector.innerHTML = '';
        cancelSelector.innerHTML = '';
        classSelector.innerHTML = '';

        if (subjects.length === 0) {
            const defaultOption = '<option value="">No subjects found</option>';
            manualSelector.innerHTML = defaultOption;
            cancelSelector.innerHTML = defaultOption;
            classSelector.innerHTML = defaultOption;
            return;
        }

        subjects.forEach(subject => {
            const subjectName = `${subject.code} - ${subject.name}`;
            const option = document.createElement('option');
            option.value = subjectName;
            option.textContent = subjectName;
            
            manualSelector.appendChild(option.cloneNode(true));
            cancelSelector.appendChild(option.cloneNode(true));
            classSelector.appendChild(option);
        });
    }
    
    async loadRegisteredStudents() {
        console.log("Admin: Fetching registered devices...");
        const devicesSnap = await getDocs(collection(db, "devices"));

        const studentPromises = devicesSnap.docs.map(async (deviceDoc) => {
            const deviceData = deviceDoc.data();
            if (!deviceData.rollNumber) {
                console.warn(`Device ${deviceDoc.id} has no rollNumber, skipping.`);
                return null;
            }
            try {
                const userSnap = await getDoc(doc(db, "users", deviceData.rollNumber));
                if (userSnap.exists()) {
                    return { ...userSnap.data(), deviceId: deviceDoc.id, lastLogin: deviceData.lastLogin };
                }
                return null;
            } catch (error) {
                console.error(`Error fetching user ${deviceData.rollNumber}:`, error);
                return null;
            }
        });

        const resolvedStudents = await Promise.all(studentPromises);
        this.registeredStudents = resolvedStudents.filter(student => student !== null);
        
        this.renderStudentTable();
        this.updateAdminStats();
    }

    renderStudentTable() {
        const tbody = document.getElementById('studentTableBody');
        tbody.innerHTML = ''; 

        if (this.registeredStudents.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center">No registered students found.</td></tr>`;
            return;
        }

        this.registeredStudents.forEach(student => {
            const row = document.createElement('tr');
            const lastLoginDate = student.lastLogin ? new Date(student.lastLogin).toLocaleString() : 'N/A';
            row.innerHTML = `
                <td>${student.rollNumber || 'N/A'}</td>
                <td>${student.username || 'N/A'}</td>
                <td>${student.section || 'N/A'}</td>
                <td>${student.deviceId ? student.deviceId.substring(0, 15) : 'N/A'}...</td>
                <td>${lastLoginDate}</td>
                <td><span class="status-badge status-present">Active</span></td>
                <td><button class="btn btn-sm btn-danger" onclick="window.app.adminDashboard.forceLogoutStudent('${student.deviceId}')"><i class="fas fa-sign-out-alt"></i></button></td>
            `;
            tbody.appendChild(row);
        });
    }
    
    updateAdminStats() {
        const totalStudents = this.registeredStudents.length;
        document.getElementById('totalStudents').textContent = totalStudents;
        document.getElementById('activeStudents').textContent = totalStudents;
        document.getElementById('totalDevices').textContent = totalStudents;
    }

    async forceLogoutStudent(deviceId) {
        if (confirm(`Are you sure you want to de-register this device?`)) {
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
            Utils.showAlert('Please provide all details.', 'warning');
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

        if (!confirm(`Are you sure you want to cancel "${className}" for ALL students on ${cancelDate}?`)) {
            return;
        }

        Utils.showAlert('Processing cancellation...', 'info');
        const usersSnap = await getDocs(collection(db, "users"));
        const promises = usersSnap.docs.map(userDoc => {
            const student = userDoc.data();
            if (student.role === 'student') {
                const attendanceRef = doc(db, "attendance", student.rollNumber, "records", cancelDate, "subjects", className);
                return setDoc(attendanceRef, {
                    status: 'cancelled',
                    subject: className,
                    timestamp: new Date(),
                    markedBy: 'admin'
                });
            }
        });

        await Promise.all(promises);
        Utils.showAlert(`Successfully cancelled "${className}" for all students on ${cancelDate}.`, 'success');
    }
    
    // --- MISSING FUNCTION ADDED ---
    async bulkLogout() {
        if(confirm("DANGER: This will de-register ALL devices and force every student to log in again. Are you sure?")) {
            Utils.showAlert('De-registering all devices...', 'info');
            const devicesSnap = await getDocs(collection(db, "devices"));
            const promises = devicesSnap.docs.map(d => deleteDoc(d.ref));
            await Promise.all(promises);
            Utils.showAlert('All devices have been de-registered.', 'success');
            await this.loadRegisteredStudents();
        }
    }

    async clearAllDeviceData() {
        if(confirm("DANGER: This will de-register ALL devices. Are you sure?")) {
            const devicesSnap = await getDocs(collection(db, "devices"));
            const promises = devicesSnap.docs.map(deviceDoc => deleteDoc(deviceDoc.ref));
            await Promise.all(promises);
            Utils.showAlert('All device data cleared!', 'success');
            await this.loadRegisteredStudents();
        }
    }
}
