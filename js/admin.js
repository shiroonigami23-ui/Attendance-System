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

    async loadRegisteredStudents() {
        const devicesSnap = await getDocs(collection(db, "devices"));
        const students = [];
        for (const deviceDoc of devicesSnap.docs) {
            const deviceData = deviceDoc.data();
            const userSnap = await getDoc(doc(db, "users", deviceData.rollNumber));
            if (userSnap.exists()) {
                students.push({
                    ...userSnap.data(),
                    deviceId: deviceDoc.id,
                    lastLogin: deviceData.lastLogin
                });
            }
        }
        this.registeredStudents = students;
        this.renderStudentTable();
        this.updateAdminStats();
    }

    renderStudentTable() {
        const tbody = document.getElementById('studentTableBody');
        tbody.innerHTML = '';
        this.registeredStudents.forEach(student => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${student.rollNumber}</td>
                <td>${student.username}</td>
                <td>${student.section}</td>
                <td>${student.deviceId.substring(0, 15)}...</td>
                <td>${new Date(student.lastLogin).toLocaleString()}</td>
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
}
