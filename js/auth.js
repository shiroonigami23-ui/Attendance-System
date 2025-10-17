// js/auth.js

import { db } from './firebase-config.js';
import { doc, getDoc, setDoc } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";
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

        const deviceData = await this.getDeviceRegistration();

        // If device is already registered to someone else, block login
        if (deviceData && deviceData.rollNumber !== rollNumber) {
            Utils.showAlert('This device is registered to another user.', 'danger');
            document.getElementById('deviceWarning').classList.remove('hidden');
            document.getElementById('authContainer').classList.add('hidden');
            return null;
        }

        const userData = {
            rollNumber,
            username: Utils.sanitizeInput(username),
            password, // In a real app, hash this!
            section,
            role: 'student'
        };

        // If device is not registered, or registered to the current user, proceed
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

        // Register the device to this user
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
        
        // If no session, check if device is registered
        const deviceData = await this.getDeviceRegistration();
        if (deviceData && deviceData.rollNumber) {
            // Device is registered, fetch user data and log them in automatically
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
