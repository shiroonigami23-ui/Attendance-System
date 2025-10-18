// js/admin.js

import { db } from './firebase-config.js';
import { collection, getDocs, doc, setDoc, deleteDoc, updateDoc } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
import { Utils } from './utils.js';

export class AdminDashboard {
    // --- UPDATED: Accept configManager in constructor ---
    constructor(configManager) {
        this.registeredStudents = [];
        this.configManager = configManager; // Store the config manager instance
    }

    async init() {
        this.populateClassSelectors(); // Populate dropdowns first
        await this.loadRegisteredStudents();
    }

    // --- NEW FUNCTION: To populate subject dropdowns dynamically ---
    populateClassSelectors() {
        const subjects = this.configManager.getSubjects();
        const qrSelector = document.getElementById('classSelector');
        const manualSelector = document.getElementById('manualClassSelector');

        // Clear existing placeholder options
        qrSelector.innerHTML = '';
        manualSelector.innerHTML = '';

        if (subjects.length === 0) {
            const defaultOption = '<option value="">No subjects found in config</option>';
            qrSelector.innerHTML = defaultOption;
            manualSelector.innerHTML = defaultOption;
            return;
        }

        subjects.forEach(subject => {
            // Create a display-friendly name like "CS501 - Theory of Computation"
            const subjectName = `${subject.code} - ${subject.name}`;
            const option = document.createElement('option');
            option.value = subjectName;
            option.textContent = subjectName;
            
            // Add the same option to both dropdowns
            qrSelector.appendChild(option.cloneNode(true));
            manualSelector.appendChild(option);
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
                } else {
                    console.warn(`User document not found for roll number: ${deviceData.rollNumber}`);
                    return null;
                }
            } catch (error) {
                console.error(`Error fetching user ${deviceData.rollNumber}:`, error);
                return null;
            }
        });

        const resolvedStudents = await Promise.all(studentPromises);
        this.registeredStudents = resolvedStudents.filter(student => student !== null);
        
        console.log(`Found ${this.registeredStudents.length} registered students.`);
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
            row.innerHTML = `
                <td>${student.rollNumber || 'N/A'}</td>
                <td>${student.username || 'N/A'}</td>
                <td>${student.section || 'N/A'}</td>
                <td>${student.deviceId.substring(0, 15)}...</td>
                <td>${lastLoginDate}</td>
                <td><span class="status-badge status-present">Active</span></td>
                <td><button class="btn btn-sm btn-danger" onclick="window.app.adminDashboard.forceLogoutStudent('${student.deviceId}')"><i class="fas fa-sign-out-alt"></i></button></td>
            `;
            tbody.appendChild(row);
        });
    }
    
    updateAdminStats() {
        document.getElementById('totalStudents').textContent = this.registeredStudents.length;
        document.getElementById('activeStudents').textContent = this.registeredStudents.length;
        document.getElementById('totalDevices').textContent = this.registeredStudents.length;
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

        if (!rollNumber) {
            Utils.showAlert('Please enter a roll number.', 'warning');
            return;
        }
        if (!className) {
            Utils.showAlert('Please select a class.', 'warning');
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

    async clearAllDeviceData() {
        if(confirm("DANGER: This will de-register ALL devices. Are you sure?")) {
            const devicesSnap = await getDocs(collection(db, "devices"));
            for (const deviceDoc of devicesSnap.docs) {
                await deleteDoc(deviceDoc.ref);
            }
            Utils.showAlert('All device data cleared!', 'success');
            await this.loadRegisteredStudents();
        }
    }

    async generateClassReport() {
        const reportType = document.getElementById('reportType').value;
        const reportDate = document.getElementById('reportDate').value;

        if (!reportDate) {
            Utils.showAlert('Please select a date for the report.', 'warning');
            return;
        }
        Utils.showAlert(`Generating ${reportType} report for ${reportDate}...`, 'info');
        console.log(`Generating report with type: ${reportType} and date: ${reportDate}`);
    }
}
