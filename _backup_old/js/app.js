import { ConfigManager, Utils } from './utils.js';
import { AuthManager } from './auth.js';
import { StudentDashboard } from './student.js';
import { AdminDashboard } from './admin.js';

class AttendanceSystem {
    constructor() {
        this.configManager = new ConfigManager();
        this.authManager = new AuthManager(this.configManager);
        this.studentDashboard = new StudentDashboard(this.configManager);
        this.adminDashboard = new AdminDashboard(this.configManager);
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
        const timeEl = document.getElementById('currentTime');
        const dateEl = document.getElementById('currentDate');
        if(timeEl) timeEl.textContent = Utils.formatTime();
        if(dateEl) dateEl.textContent = Utils.formatDate();
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

window.app = new AttendanceSystem();

// --- GLOBAL FUNCTIONS ---

// Auth Tabs
window.switchTab = (tabId) => {
    document.querySelectorAll('#authContainer .tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('#authContainer .tab').forEach(el => el.classList.remove('active'));
    document.getElementById(`${tabId}Tab`).classList.add('active');
    document.querySelector(`button[onclick="switchTab('${tabId}')"]`).classList.add('active');
};

// Student Dashboard Tabs
window.switchDashboardTab = (tabId) => {
    document.querySelectorAll('#studentDashboard .tab-panel').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('#studentDashboard .nav-tab').forEach(el => el.classList.remove('active'));
    document.getElementById(`${tabId}Tab`).classList.add('active');
    document.querySelector(`button[onclick="switchDashboardTab('${tabId}')"]`).classList.add('active');
    if (tabId === 'timetable') {
        window.app.studentDashboard.renderTimetable();
    }
};

// Admin Dashboard Tabs
window.switchAdminTab = (tabId) => {
    document.querySelectorAll('#adminDashboard .tab-panel').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('#adminDashboard .nav-tab').forEach(el => el.classList.remove('active'));
    document.getElementById(`admin${tabId.charAt(0).toUpperCase() + tabId.slice(1)}Tab`).classList.add('active');
    document.querySelector(`button[onclick="switchAdminTab('${tabId}')"]`).classList.add('active');
};

// Student Functions
window.changeCalendarMonth = (direction) => window.app.studentDashboard.changeMonth(direction);
window.showDayDetails = (dateStr) => window.app.studentDashboard.showAttendanceForDay(dateStr);
window.switchSection = (sectionId) => window.app.studentDashboard.switchSection(sectionId);
window.scanQR = () => window.app.studentDashboard.markAttendance(); 
window.refreshStudentData = () => window.app.studentDashboard.refreshData();

// Admin Functions
window.adminLogout = () => {
    window.app.authManager.logout();
    window.app.showUI(null);
};
window.refreshAdminData = () => window.app.adminDashboard.loadRegisteredStudents();
window.exportStudentData = () => window.app.adminDashboard.exportStudentData();
window.markManualAttendance = () => window.app.adminDashboard.markManualAttendance();
window.cancelClass = () => window.app.adminDashboard.cancelClass();
// --- NEW: Hook for the cancel entire day button ---
window.cancelEntireDay = () => window.app.adminDashboard.cancelEntireDay();
window.generateClassReport = () => window.app.adminDashboard.generateClassReport();
window.exportReport = () => window.app.adminDashboard.exportCurrentReport();
window.bulkLogout = () => window.app.adminDashboard.bulkLogout();
window.clearDeviceData = () => window.app.adminDashboard.clearAllDeviceData();
window.viewStudentDetails = (rollNumber) => window.app.adminDashboard.showStudentAttendanceModal(rollNumber);
window.closeStudentModal = () => document.getElementById('studentDetailModal').classList.add('hidden');


// --- START THE APP ---
document.addEventListener('DOMContentLoaded', () => {
    window.app.init();
});
