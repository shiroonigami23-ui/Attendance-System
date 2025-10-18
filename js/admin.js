// js/admin.js

import { db } from './firebase-config.js';
import { collection, getDocs, doc, setDoc, deleteDoc, updateDoc } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
import { Utils } from './utils.js';

export class AdminDashboard {
    constructor() {
        this.registeredStudents = [];
    }

    async init() {
        await this.loadRegisteredStudents();
    }

    // REPLACE the existing loadRegisteredStudents function
async loadRegisteredStudents() {
    console.log("Admin: Fetching registered devices...");
    const devicesSnap = await getDocs(collection(db, "devices"));
    const students = [];

    // Use Promise.all for more efficient data fetching
    const studentPromises = devicesSnap.docs.map(async (deviceDoc) => {
        const deviceData = deviceDoc.data();
        if (!deviceData.rollNumber) {
            console.warn(`Device ${deviceDoc.id} has no rollNumber, skipping.`);
            return null;
        }

        try {
            const userSnap = await getDoc(doc(db, "users", deviceData.rollNumber));
            if (userSnap.exists()) {
                return {
                    ...userSnap.data(),
                    deviceId: deviceDoc.id,
                    lastLogin: deviceData.lastLogin
                };
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
    // Filter out any null results from skipped or failed fetches
    this.registeredStudents = resolvedStudents.filter(student => student !== null);
    
    console.log(`Found ${this.registeredStudents.length} registered students.`);
    this.renderStudentTable();
    this.updateAdminStats();
}

// REPLACE the existing renderStudentTable function
renderStudentTable() {
    const tbody = document.getElementById('studentTableBody');
    tbody.innerHTML = ''; // Clear existing rows

    if (this.registeredStudents.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center;">No registered students found. Click Refresh to check again.</td></tr>`;
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
            <td>
                <button class="btn btn-sm btn-danger" onclick="window.app.adminDashboard.forceLogoutStudent('${student.deviceId}')">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </td>
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
        if (!rollNumber) {
            Utils.showAlert('Please enter a roll number.', 'warning');
            return;
        }

        const today = new Date().toISOString().split('T')[0];
        const attendanceRef = doc(db, "attendance", rollNumber, "records", today);
        await setDoc(attendanceRef, { status, timestamp: new Date(), markedBy: 'admin' });
        Utils.showAlert(`Attendance marked for ${rollNumber}!`, 'success');
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

    // --- ADDED THIS NEW FUNCTION ---
    async generateClassReport() {
        const reportType = document.getElementById('reportType').value;
        const reportDate = document.getElementById('reportDate').value;

        if (!reportDate) {
            Utils.showAlert('Please select a date for the report.', 'warning');
            return;
        }

        Utils.showAlert(`Generating ${reportType} report for ${reportDate}...`, 'info');
        console.log(`Generating report with type: ${reportType} and date: ${reportDate}`);

        // In a real application, you would add logic here to fetch data 
        // from Firestore and generate a downloadable report (e.g., a CSV file).
        // For now, this just shows an alert to confirm it's working.
    }
}
