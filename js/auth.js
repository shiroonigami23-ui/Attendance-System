// js/auth.js

import { db } from './firebase-config.js';
import { doc, getDoc, setDoc, collection, query, where, getDocs } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
import { Utils } from './utils.js';

export class AuthManager {
    constructor(configManager) {
        this.configManager = configManager;
        this.deviceId = Utils.generateDeviceFingerprint();
        this.currentUser = JSON.parse(sessionStorage.getItem('currentUser'));
    }

    async getDeviceRegistration() {
        const deviceRef = doc(db, "devices", this.deviceId);
        const deviceSnap = await getDoc(deviceRef);
        return deviceSnap.exists() ? deviceSnap.data() : null;
    }

    async loginStudent(rollNumber, username, password, section) {
        if (!Utils.isValidRollNumber(rollNumber, this.configManager)) {
            Utils.showAlert('Invalid roll number format.', 'danger');
            return null;
        }

        // --- NEW & IMPROVED TWO-WAY DEVICE LOCK LOGIC ---

        // 1. Check if THIS device is registered to any student.
        const thisDeviceRegistration = await this.getDeviceRegistration();

        // 2. Check if the STUDENT trying to log in is registered on ANY other device.
        const studentDeviceQuery = query(collection(db, "devices"), where("rollNumber", "==", rollNumber));
        const studentDeviceSnap = await getDocs(studentDeviceQuery);
        let studentRegisteredDeviceId = null;
        if (!studentDeviceSnap.empty) {
            // Student's account is already tied to a device. Get that device's ID.
            studentRegisteredDeviceId = studentDeviceSnap.docs[0].id;
        }

        // --- APPLY STRICT SECURITY CHECKS ---

        // SCENARIO 1: This device is already taken by a DIFFERENT student. BLOCK.
        if (thisDeviceRegistration && thisDeviceRegistration.rollNumber !== rollNumber) {
            Utils.showAlert('This device is registered to another user.', 'danger');
            document.getElementById('deviceWarning').classList.remove('hidden');
            document.getElementById('authContainer').classList.add('hidden');
            return null;
        }

        // SCENARIO 2: This student's account is already locked to a DIFFERENT device. BLOCK.
        if (studentRegisteredDeviceId && studentRegisteredDeviceId !== this.deviceId) {
            Utils.showAlert('Your account is locked to another device. Please use your original registered device or contact an admin.', 'danger');
            document.getElementById('deviceWarning').classList.remove('hidden');
            document.getElementById('authContainer').classList.add('hidden');
            return null;
        }
        
        // --- If all checks pass, proceed with login ---
        const userData = {
            rollNumber,
            username: Utils.sanitizeInput(username),
            password, // In a real app, this should be hashed!
            section,
            role: 'student'
        };

        const userRef = doc(db, "users", rollNumber);
        const userSnap = await getDoc(userRef);

        if (userSnap.exists()) {
            // User exists, "log them in"
            const existingData = userSnap.data();
            if (existingData.password !== password) {
                 Utils.showAlert('Incorrect password.', 'danger');
                 return null;
            }
        } else {
            // New user, register them
            await setDoc(userRef, userData);
        }

        // Register or update the device to this user. This is now safe.
        const deviceRef = doc(db, "devices", this.deviceId);
        await setDoc(deviceRef, {
            rollNumber,
            lastLogin: new Date().toISOString()
        });

        this.currentUser = userData;
        sessionStorage.setItem('currentUser', JSON.stringify(userData));
        Utils.showAlert(`Welcome, ${username}!`, 'success');
        return userData;
    }

    loginAdmin(username, password) {
        const adminCreds = this.configManager.getAdminCredentials();
        if (username === adminCreds.username && password === adminCreds.password) {
            const adminData = { username, role: 'admin' };
            this.currentUser = adminData;
            sessionStorage.setItem('currentUser', JSON.stringify(adminData));
            Utils.showAlert('Admin login successful!', 'success');
            return adminData;
        }
        Utils.showAlert('Invalid admin credentials.', 'danger');
        return null;
    }

    logout() {
        this.currentUser = null;
        sessionStorage.removeItem('currentUser');
        Utils.showAlert('You have been logged out.', 'info');
    }

    getCurrentUser() {
        return this.currentUser;
    }

    async initialCheck() {
        if (this.currentUser) {
            return this.currentUser; // User is already in session
        }
        
        const deviceData = await this.getDeviceRegistration();
        if (deviceData && deviceData.rollNumber) {
            const userRef = doc(db, "users", deviceData.rollNumber);
            const userSnap = await getDoc(userRef);
            if (userSnap.exists()) {
                this.currentUser = userSnap.data();
                sessionStorage.setItem('currentUser', JSON.stringify(this.currentUser));
                return this.currentUser;
            }
        }
        return null;
    }
}
