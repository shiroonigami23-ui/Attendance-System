// js/app.js

import { ConfigManager, Utils } from './utils.js';
import { AuthManager } from './auth.js';
import { StudentDashboard } from './student.js';
import { AdminDashboard } from './admin.js';

class AttendanceSystem {
    constructor() {
        this.configManager = new ConfigManager();
        this.authManager = new AuthManager(this.configManager);
        this.studentDashboard = new StudentDashboard(this.configManager);
        this.adminDashboard = new AdminDashboard();
    }

    async init() {
        Utils.initializeTheme();
        this.updateTime();
        setInterval(() => this.updateTime(), 1000);

        await this.configManager.loadConfig();
        this.attachEventListeners();

        const user = await this.authManager.initialCheck();
        this.showUI(user);
    }

    showUI(user) {
        // Hide all containers first
        document.getElementById('authContainer').classList.add('hidden');
        document.getElementById('studentDashboard').classList.add('hidden');
        document.getElementById('adminDashboard').classList.add('hidden');
        document.getElementById('deviceWarning').classList.add('hidden');
        
        if (user) {
            if (user.role === 'admin') {
                document.getElementById('adminDashboard').classList.remove('hidden');
                this.adminDashboard.init();
            } else {
                document.getElementById('studentDashboard').classList.remove('hidden');
                this.studentDashboard.init(user);
            }
        } else {
            document.getElementById('authContainer').classList.remove('hidden');
        }
    }

    updateTime() {
        document.getElementById('currentTime').textContent = Utils.formatTime();
        document.getElementById('currentDate').textContent = Utils.formatDate();
    }

    attachEventListeners() {
        document.getElementById('studentAuthForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const rollNumber = document.getElementById('rollNumber').value;
            const section = document.getElementById('studentSection').value;
            const username = document.getElementById('studentUsername').value;
            const password = document.getElementById('studentPassword').value;
            const user = await this.authManager.loginStudent(rollNumber, username, password, section);
            if (user) this.showUI(user);
        });

        document.getElementById('adminAuthForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const username = document.getElementById('adminUsername').value;
            const password = document.getElementById('adminPassword').value;
            const user = this.authManager.loginAdmin(username, password);
            if (user) this.showUI(user);
        });
        
        document.getElementById('themeToggle').addEventListener('click', () => Utils.toggleTheme());
        document.getElementById('adminThemeToggle').addEventListener('click', () => Utils.toggleTheme());
    }
}

// Make app instance and its methods available globally for HTML onclicks
window.app = new AttendanceSystem();

// --- Global Functions for HTML ---
window.switchTab = (tabId) => {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
    document.getElementById(`${tabId}Tab`).classList.add('active');
    document.querySelector(`[onclick="switchTab('${tabId}')"]`).classList.add('active');
};

window.switchDashboardTab = (tabId) => {
    document.querySelectorAll('.tab-panel').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.nav-tab').forEach(el => el.classList.remove('active'));
    document.getElementById(`${tabId}Tab`).classList.add('active');
    document.querySelector(`[onclick="switchDashboardTab('${tabId}')"]`).classList.add('active');
};

window.switchAdminTab = (tabId) => {
    document.querySelectorAll('.tab-panel').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.nav-tab').forEach(el => el.classList.remove('active'));
    document.getElementById(`admin${tabId.charAt(0).toUpperCase() + tabId.slice(1)}Tab`).classList.add('active');
    document.querySelector(`[onclick="switchAdminTab('${tabId}')"]`).classList.add('active');
};

window.adminLogout = () => {
    window.app.authManager.logout();
    window.app.showUI(null);
};

window.scanQR = () => {
    // A real QR scanner would use the camera. We will simulate a successful scan.
    Utils.showAlert("Simulating QR scan...", "info", 2000);
    setTimeout(() => {
        window.app.studentDashboard.markAttendance();
    }, 1500);
};

window.switchSection = (sectionId) => window.app.studentDashboard.switchSection(sectionId);
window.markManualAttendance = () => window.app.adminDashboard.markManualAttendance();
window.clearDeviceData = () => window.app.adminDashboard.clearAllDeviceData();

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    window.app.init();
});
