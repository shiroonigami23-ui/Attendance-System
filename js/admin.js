// js/admin.js

import { db } from './firebase-config.js';
import { collection, getDocs, doc, setDoc, deleteDoc, updateDoc } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
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
        const cancelSelector = document.getElementById('cancelClassSelector'); // New selector for cancellation

        manualSelector.innerHTML = '';
        cancelSelector.innerHTML = '';

        if (subjects.length === 0) {
            const defaultOption = '<option value="">No subjects found</option>';
            manualSelector.innerHTML = defaultOption;
            cancelSelector.innerHTML = defaultOption;
            return;
        }

        subjects.forEach(subject => {
            const subjectName = `${subject.code} - ${subject.name}`;
            const option = document.createElement('option');
            option.value = subjectName;
            option.textContent = subjectName;
            
            manualSelector.appendChild(option.cloneNode(true));
            cancelSelector.appendChild(option);
        });
    }

    async loadRegisteredStudents() {
        // ... same as before
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
        // ... same as before
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
        // ... same as before
        document.getElementById('activeStudents').textContent = this.registeredStudents.length;
        document.getElementById('totalDevices').textContent = this.registeredStudents.length;
    }

    async forceLogoutStudent(deviceId) {
        // ... same as before
            await deleteDoc(doc(db, "devices", deviceId));
            Utils.showAlert('Device de-registered successfully!', 'success');
            await this.loadRegisteredStudents();
        }
    }

    async markManualAttendance() {
        // ... same as before
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

    // --- NEW FUNCTION for CANCELLING a CLASS ---
    async cancelClass() {
        const cancelDate = document.getElementById('cancelDate').value;
        const className = document.getElementById('cancelClassSelector').value;

        if (!cancelDate || !className) {
            Utils.showAlert('Please select both a date and a class to cancel.', 'warning');
            return;
        }

        if (!confirm(`Are you sure you want to cancel the class "${className}" for ALL students on ${cancelDate}? This cannot be undone.`)) {
            return;
        }

        Utils.showAlert('Processing cancellation for all students... This may take a moment.', 'info');

        // We need a list of all students to mark the class as cancelled for them.
        // We'll fetch all users from the 'users' collection.
        const usersSnap = await getDocs(collection(db, "users"));
        
        const cancellationPromises = [];

        usersSnap.forEach(userDoc => {
            const student = userDoc.data();
            if (student.role === 'student') {
                const attendanceRef = doc(db, "attendance", student.rollNumber, "records", cancelDate, "subjects", className);
                const promise = setDoc(attendanceRef, {
                    status: 'cancelled',
                    subject: className,
                    timestamp: new Date(),
                    markedBy: 'admin'
                });
                cancellationPromises.push(promise);
            }
        });

        try {
            await Promise.all(cancellationPromises);
            Utils.showAlert(`Successfully cancelled "${className}" for all students on ${cancelDate}.`, 'success');
        } catch (error) {
            console.error("Error cancelling class: ", error);
            Utils.showAlert('An error occurred during cancellation. Please check the console.', 'danger');
        }
    }

    async clearAllDeviceData() {
        // ... same as before
