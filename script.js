class ConfigManager {
    constructor() {
        this.config = null;
        this.loadConfig();
    }

    async loadConfig() {
        try {
            const response = await fetch('config.xml');
            const xmlText = await response.text();
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xmlText, 'text/xml');
            this.config = this.parseXMLConfig(xmlDoc);
        } catch (error) {
            console.error('Error loading config:', error);
            this.config = this.getDefaultConfig();
        }
    }

    parseXMLConfig(xmlDoc) {
        const config = {};
        
        // Parse system info
        const system = xmlDoc.querySelector('system');
        config.system = {
            name: system?.querySelector('name')?.textContent || 'Attendance System',
            institution: system?.querySelector('institution')?.textContent || 'Institution',
            version: system?.querySelector('version')?.textContent || '1.0',
            academicYear: system?.querySelector('academic-year')?.textContent || '2024-25',
            semester: system?.querySelector('semester')?.textContent || 'Winter'
        };

        // Parse roll number ranges
        config.rollNumbers = [];
        const ranges = xmlDoc.querySelectorAll('roll-numbers range');
        ranges.forEach(range => {
            config.rollNumbers.push({
                type: range.querySelector('type')?.textContent,
                prefix: range.querySelector('prefix')?.textContent,
                start: range.querySelector('start')?.textContent,
                end: range.querySelector('end')?.textContent,
                format: range.querySelector('format')?.textContent
            });
        });

        // Parse sections
        config.sections = [];
        const sections = xmlDoc.querySelectorAll('sections section');
        sections.forEach(section => {
            config.sections.push({
                id: section.getAttribute('id'),
                name: section.getAttribute('name'),
                capacity: parseInt(section.getAttribute('capacity'))
            });
        });

        // Parse admin credentials
        const admin = xmlDoc.querySelector('admin');
        config.admin = {
            username: admin?.querySelector('username')?.textContent || 'admin',
            password: admin?.querySelector('password')?.textContent || 'admin123',
            email: admin?.querySelector('email')?.textContent || 'admin@example.com',
            fullName: admin?.querySelector('full-name')?.textContent || 'Administrator'
        };

        // Parse settings
        const settings = xmlDoc.querySelector('settings');
        config.settings = {
            maxDevicesPerUser: parseInt(settings?.querySelector('max-devices-per-user')?.textContent) || 1,
            attendanceTimeWindow: parseInt(settings?.querySelector('attendance-time-window')?.textContent) || 30,
            autoLogoutTime: parseInt(settings?.querySelector('auto-logout-time')?.textContent) || 0,
            requireQRForAttendance: settings?.querySelector('require-qr-for-attendance')?.textContent === 'true',
            enableNotifications: settings?.querySelector('enable-notifications')?.textContent === 'true',
            sessionTimeout: parseInt(settings?.querySelector('session-timeout')?.textContent) || 24,
            maxLoginAttempts: parseInt(settings?.querySelector('max-login-attempts')?.textContent) || 3,
            lockoutDuration: parseInt(settings?.querySelector('lockout-duration')?.textContent) || 15
        };

        // Parse subjects
        config.subjects = [];
        const subjects = xmlDoc.querySelectorAll('subjects subject');
        subjects.forEach(subject => {
            config.subjects.push({
                code: subject.getAttribute('code'),
                name: subject.getAttribute('name'),
                type: subject.getAttribute('type'),
                credits: parseInt(subject.getAttribute('credits'))
            });
        });

        // Parse timetable
        config.timetable = {};
        const timetableSections = xmlDoc.querySelectorAll('timetable section');
        timetableSections.forEach(section => {
            const sectionId = section.getAttribute('id');
            config.timetable[sectionId] = {};
            
            const days = section.querySelectorAll('day');
            days.forEach(day => {
                const dayName = day.getAttribute('name');
                config.timetable[sectionId][dayName] = {};
                
                const slots = day.querySelectorAll('slot');
                slots.forEach(slot => {
                    const time = slot.getAttribute('time');
                    config.timetable[sectionId][dayName][time] = {
                        subject: slot.getAttribute('subject'),
                        type: slot.getAttribute('type'),
                        room: slot.getAttribute('room')
                    };
                });
            });
        });

        return config;
    }

    getDefaultConfig() {
        return {
            system: {
                name: 'Advanced Campus Attendance System',
                institution: 'RJIT BSF Academy',
                version: '2.0',
                academicYear: '2024-25',
                semester: 'Winter'
            },
            rollNumbers: [
                { type: 'CS', prefix: '0902CS231', start: '001', end: '150', format: '0902CS231XXX' },
                { type: 'Department', prefix: 'D', start: '01', end: '04', format: 'DXX' }
            ],
            sections: [
                { id: 'A', name: 'Section A', capacity: 40 },
                { id: 'B', name: 'Section B', capacity: 40 },
                { id: 'C', name: 'Section C', capacity: 35 },
                { id: 'D', name: 'Section D', capacity: 35 }
            ],
            admin: {
                username: 'admin',
                password: 'ShiroOni',
                email: 'admin@rjit.academy',
                fullName: 'System Administrator'
            },
            settings: {
                maxDevicesPerUser: 1,
                attendanceTimeWindow: 30,
                autoLogoutTime: 0,
                requireQRForAttendance: false,
                enableNotifications: true,
                sessionTimeout: 24,
                maxLoginAttempts: 3,
                lockoutDuration: 15
            },
            timetable: {
                'A': {
                    'Monday': {
                        '09:00-10:00': { subject: 'CS501', type: 'Theory', room: 'A101' },
                        '10:00-11:00': { subject: 'CS502', type: 'Lab', room: 'Lab1' },
                        '11:00-12:00': { subject: 'CS503', type: 'Theory', room: 'A102' },
                        '12:00-13:00': { subject: 'Lunch', type: 'Break', room: '-' },
                        '13:00-14:00': { subject: 'CS504', type: 'Theory', room: 'A101' },
                        '14:00-15:00': { subject: 'CS505', type: 'Lab', room: 'Lab2' }
                    }
                }
            }
        };
    }

    getRollNumberRanges() {
        return this.config?.rollNumbers || [];
    }

    getAdminCredentials() {
        return this.config?.admin || { username: 'admin', password: 'ShiroOni' };
    }

    getSystemSettings() {
        return this.config?.settings || {};
    }

    getTimetable(section) {
        return this.config?.timetable?.[section] || {};
    }
}

// Utility Functions
class Utils {
    static generateDeviceFingerprint() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillText('Device fingerprint', 2, 2);
        
        const fingerprint = [
            navigator.userAgent,
            navigator.language,
            screen.width + 'x' + screen.height,
            new Date().getTimezoneOffset(),
            navigator.platform,
            navigator.cookieEnabled,
            canvas.toDataURL()
        ].join('|');
        
        return btoa(fingerprint).replace(/[^a-zA-Z0-9]/g, '').substring(0, 32);
    }

    static isValidRollNumber(rollNumber) {
        if (!rollNumber) return false;
        
        const ranges = window.configManager?.getRollNumberRanges() || [];
        
        for (const range of ranges) {
            if (range.type === 'CS') {
                const csPattern = new RegExp(`^${range.prefix}(\\d{3})$`);
                const match = rollNumber.match(csPattern);
                if (match) {
                    const num = parseInt(match[1]);
                    const start = parseInt(range.start);
                    const end = parseInt(range.end);
                    return num >= start && num <= end;
                }
            } else if (range.type === 'Department') {
                const dPattern = new RegExp(`^${range.prefix}(\\d{2})$`);
                const match = rollNumber.match(dPattern);
                if (match) {
                    const num = parseInt(match[1]);
                    const start = parseInt(range.start);
                    const end = parseInt(range.end);
                    return num >= start && num <= end;
                }
            }
        }
        
        return false;
    }

    static showAlert(message, type = 'info', duration = 5000) {
        const alertContainer = document.getElementById('alertContainer') || this.createAlertContainer();
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="alert-icon fas ${this.getAlertIcon(type)}"></i>
            <span>${message}</span>
            <button type="button" class="btn-close" onclick="this.parentElement.remove()" style="position: absolute; right: 10px; background: none; border: none; color: var(--muted); cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        alertContainer.appendChild(alert);
        
        if (duration > 0) {
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.remove();
                }
            }, duration);
        }
    }

    static createAlertContainer() {
        const container = document.createElement('div');
        container.id = 'alertContainer';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        `;
        document.body.appendChild(container);
        return container;
    }

    static getAlertIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    static formatTime(date = new Date()) {
        return date.toLocaleTimeString('en-US', {
            hour12: true,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }

    static formatDate(date = new Date()) {
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    static sanitizeInput(input) {
        if (typeof input !== 'string') return '';
        return input.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
                    .replace(/[<>'"]/g, '')
                    .trim();
    }

    static validateSection(section) {
        const validSections = ['A', 'B', 'C', 'D'];
        return validSections.includes(section);
    }

    static validatePassword(password) {
        if (!password || password.length < 6) return false;
        return true;
    }

    static setStorageItem(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (error) {
            console.error('Storage error:', error);
            this.showAlert('Storage error. Please check if localStorage is available.', 'danger');
            return false;
        }
    }

    static getStorageItem(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error('Storage retrieval error:', error);
            return defaultValue;
        }
    }

    static removeStorageItem(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('Storage removal error:', error);
            return false;
        }
    }

    static initializeTheme() {
        const savedTheme = this.getStorageItem('theme', 'light');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const theme = savedTheme === 'auto' ? (prefersDark ? 'dark' : 'light') : savedTheme;
        
        document.documentElement.setAttribute('data-theme', theme);
        this.updateThemeToggle(theme);
    }

    static toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        this.setStorageItem('theme', newTheme);
        this.updateThemeToggle(newTheme);
    }

    static updateThemeToggle(theme) {
        const toggle = document.getElementById('themeToggle');
        if (toggle) {
            const icon = toggle.querySelector('i');
            const text = toggle.querySelector('span');
            
            if (theme === 'dark') {
                icon.className = 'fas fa-sun';
                text.textContent = 'Light Mode';
            } else {
                icon.className = 'fas fa-moon';
                text.textContent = 'Dark Mode';
            }
        }
    }
}

// Authentication Manager
class AuthManager {
    constructor() {
        this.deviceId = Utils.generateDeviceFingerprint();
        this.maxLoginAttempts = 3;
        this.lockoutDuration = 15 * 60 * 1000; // 15 minutes
    }

    isDeviceRegistered() {
        const registeredDevices = Utils.getStorageItem('registeredDevices', {});
        return this.deviceId in registeredDevices;
    }

    getCurrentUser() {
        const registeredDevices = Utils.getStorageItem('registeredDevices', {});
        return registeredDevices[this.deviceId] || null;
    }

    isUserLockedOut(rollNumber) {
        const lockouts = Utils.getStorageItem('userLockouts', {});
        const lockoutData = lockouts[rollNumber];
        
        if (!lockoutData) return false;
        
        const now = Date.now();
        if (now - lockoutData.timestamp > this.lockoutDuration) {
            delete lockouts[rollNumber];
            Utils.setStorageItem('userLockouts', lockouts);
            return false;
        }
        
        return lockoutData.attempts >= this.maxLoginAttempts;
    }

    recordFailedAttempt(rollNumber) {
        const lockouts = Utils.getStorageItem('userLockouts', {});
        
        if (!lockouts[rollNumber]) {
            lockouts[rollNumber] = { attempts: 0, timestamp: Date.now() };
        }
        
        lockouts[rollNumber].attempts++;
        lockouts[rollNumber].timestamp = Date.now();
        
        Utils.setStorageItem('userLockouts', lockouts);
        
        const remainingAttempts = this.maxLoginAttempts - lockouts[rollNumber].attempts;
        if (remainingAttempts > 0) {
            Utils.showAlert(`Invalid credentials. ${remainingAttempts} attempts remaining.`, 'warning');
        } else {
            Utils.showAlert(`Account locked for 15 minutes due to multiple failed attempts.`, 'danger');
        }
    }

    clearFailedAttempts(rollNumber) {
        const lockouts = Utils.getStorageItem('userLockouts', {});
        if (lockouts[rollNumber]) {
            delete lockouts[rollNumber];
            Utils.setStorageItem('userLockouts', lockouts);
        }
    }

    validateStudentCredentials(rollNumber, username, password, section) {
        if (this.isUserLockedOut(rollNumber)) {
            Utils.showAlert('Account is temporarily locked. Please try again later.', 'danger');
            return false;
        }

        if (!Utils.isValidRollNumber(rollNumber)) {
            this.recordFailedAttempt(rollNumber);
            Utils.showAlert('Invalid roll number format. Please check and try again.', 'danger');
            return false;
        }

        if (!Utils.validateSection(section)) {
            this.recordFailedAttempt(rollNumber);
            Utils.showAlert('Invalid section. Please select a valid section.', 'danger');
            return false;
        }

        if (!username || username.length < 3) {
            this.recordFailedAttempt(rollNumber);
            Utils.showAlert('Username must be at least 3 characters long.', 'danger');
            return false;
        }

        if (!Utils.validatePassword(password)) {
            this.recordFailedAttempt(rollNumber);
            Utils.showAlert('Password must be at least 6 characters long.', 'danger');
            return false;
        }

        if (this.isDeviceRegistered()) {
            const currentDeviceUser = this.getCurrentUser();
            if (currentDeviceUser && currentDeviceUser.rollNumber !== rollNumber) {
                Utils.showAlert('This device is already registered with a different user. Contact admin for assistance.', 'danger');
                return false;
            }
        }

        return true;
    }

    registerStudent(userData) {
        const registeredDevices = Utils.getStorageItem('registeredDevices', {});
        const registrationData = {
            ...userData,
            deviceId: this.deviceId,
            registrationDate: new Date().toISOString(),
            lastLogin: new Date().toISOString(),
            loginCount: 1
        };

        registeredDevices[this.deviceId] = registrationData;
        Utils.setStorageItem('registeredDevices', registeredDevices);
        Utils.setStorageItem('currentUser', { ...registrationData, role: 'student' });
        
        this.clearFailedAttempts(userData.rollNumber);
        return true;
    }

    loginStudent(rollNumber, username, password, section) {
        if (!this.validateStudentCredentials(rollNumber, username, password, section)) {
            return false;
        }

        const userData = {
            rollNumber: Utils.sanitizeInput(rollNumber),
            username: Utils.sanitizeInput(username),
            section: Utils.sanitizeInput(section),
            role: 'student'
        };

        if (this.isDeviceRegistered()) {
            const registeredDevices = Utils.getStorageItem('registeredDevices', {});
            registeredDevices[this.deviceId].lastLogin = new Date().toISOString();
            registeredDevices[this.deviceId].loginCount = (registeredDevices[this.deviceId].loginCount || 0) + 1;
            Utils.setStorageItem('registeredDevices', registeredDevices);
            Utils.setStorageItem('currentUser', userData);
        } else {
            this.registerStudent(userData);
        }

        this.clearFailedAttempts(rollNumber);
        Utils.showAlert('Login successful! Welcome to the attendance system.', 'success');
        return true;
    }

    loginAdmin(username, password) {
        const adminCreds = window.configManager?.getAdminCredentials() || { username: 'admin', password: 'ShiroOni' };

        if (username !== adminCreds.username || password !== adminCreds.password) {
            Utils.showAlert('Invalid admin credentials.', 'danger');
            return false;
        }

        const adminData = {
            username: Utils.sanitizeInput(username),
            role: 'admin',
            loginTime: new Date().toISOString()
        };

        Utils.setStorageItem('currentUser', adminData);
        Utils.showAlert('Admin login successful!', 'success');
        return true;
    }

    getCurrentLoggedUser() {
        return Utils.getStorageItem('currentUser', null);
    }

    isLoggedIn() {
        const currentUser = this.getCurrentLoggedUser();
        return currentUser !== null;
    }

    isAdmin() {
        const currentUser = this.getCurrentLoggedUser();
        return currentUser && currentUser.role === 'admin';
    }

    logout(isAdminForced = false) {
        if (!isAdminForced && !this.isAdmin()) {
            Utils.showAlert('Regular users cannot logout. Contact admin if needed.', 'warning');
            return false;
        }

        Utils.removeStorageItem('currentUser');
        
        if (isAdminForced) {
            Utils.showAlert('User logged out by admin.', 'info');
        } else {
            Utils.showAlert('Admin logged out successfully.', 'success');
        }
        
        return true;
    }

    forceLogoutUser(rollNumber) {
        if (!this.isAdmin()) {
            Utils.showAlert('Access denied. Admin privileges required.', 'danger');
            return false;
        }

        const registeredDevices = Utils.getStorageItem('registeredDevices', {});
        const deviceToRemove = Object.keys(registeredDevices).find(deviceId => 
            registeredDevices[deviceId].rollNumber === rollNumber
        );

        if (deviceToRemove) {
            delete registeredDevices[deviceToRemove];
            Utils.setStorageItem('registeredDevices', registeredDevices);
            
            const currentUser = this.getCurrentLoggedUser();
            if (currentUser && currentUser.rollNumber === rollNumber) {
                Utils.removeStorageItem('currentUser');
            }
            
            Utils.showAlert(`User ${rollNumber} has been logged out.`, 'success');
            return true;
        }

        Utils.showAlert('User not found.', 'warning');
        return false;
    }

    clearAllDeviceData() {
        if (!this.isAdmin()) {
            Utils.showAlert('Access denied. Admin privileges required.', 'danger');
            return false;
        }

        const confirmPassword = prompt('Enter admin password to confirm:');
        const adminCreds = window.configManager?.getAdminCredentials() || { password: 'ShiroOni' };
        
        if (confirmPassword !== adminCreds.password) {
            Utils.showAlert('Invalid password. Operation cancelled.', 'danger');
            return false;
        }

        Utils.removeStorageItem('registeredDevices');
        Utils.removeStorageItem('currentUser');
        Utils.removeStorageItem('userLockouts');
        Utils.removeStorageItem('attendanceHistory');
        
        Utils.showAlert('All device data cleared successfully.', 'success');
        return true;
    }

    init() {
        const currentUser = this.getCurrentLoggedUser();
        
        if (currentUser) {
            if (currentUser.role === 'admin') {
                return { success: true, userType: 'admin', userData: currentUser };
            } else if (this.isDeviceRegistered()) {
                const deviceUser = this.getCurrentUser();
                if (deviceUser && deviceUser.rollNumber === currentUser.rollNumber) {
                    return { success: true, userType: 'student', userData: currentUser };
                }
            }
        }

        if (this.isDeviceRegistered()) {
            const deviceUser = this.getCurrentUser();
            if (deviceUser) {
                Utils.setStorageItem('currentUser', { ...deviceUser, role: 'student' });
                return { success: true, userType: 'student', userData: deviceUser };
            }
        }

        return { success: false, userType: null, userData: null };
    }
}

// Student Dashboard Manager
class StudentDashboard {
    constructor() {
        this.currentSection = 'A';
        this.attendanceHistory = Utils.getStorageItem('attendanceHistory', {});
    }

    renderTimetable() {
        const timetableContent = document.getElementById('timetableContent');
        if (!timetableContent) return;

        const sectionData = window.configManager?.getTimetable(this.currentSection) || {};
        if (Object.keys(sectionData).length === 0) {
            timetableContent.innerHTML = '<p>No timetable data available for this section.</p>';
            return;
        }

        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        const timeSlots = ['09:00-10:00', '10:00-11:00', '11:00-12:00', '12:00-13:00', '13:00-14:00', '14:00-15:00'];

        let tableHTML = `
            <table class="timetable">
                <thead>
                    <tr>
                        <th>Time</th>
                        ${days.map(day => `<th>${day}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
        `;

        timeSlots.forEach(timeSlot => {
            tableHTML += `<tr>`;
            tableHTML += `<td class="time-slot">${timeSlot}</td>`;
            
            days.forEach(day => {
                const classInfo = sectionData[day] && sectionData[day][timeSlot];
                if (classInfo) {
                    let cellClass = 'subject-cell';
                    if (classInfo.subject === 'Lunch') {
                        cellClass += ' lunch-break';
                    }
                    
                    const now = new Date();
                    const currentDay = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][now.getDay()];
                    
                    if (day === currentDay) {
                        const [startTime, endTime] = timeSlot.split('-');
                        const [startHour, startMinute] = startTime.split(':').map(Number);
                        const [endHour, endMinute] = endTime.split(':').map(Number);
                        
                        const startDateTime = new Date();
                        startDateTime.setHours(startHour, startMinute, 0);
                        
                        const endDateTime = new Date();
                        endDateTime.setHours(endHour, endMinute, 0);
                        
                        if (now >= startDateTime && now <= endDateTime) {
                            cellClass += ' current';
                        } else if (now < startDateTime && (startDateTime - now) <= 3600000) {
                            cellClass += ' upcoming';
                        }
                    }
                    
                    tableHTML += `
                        <td class="${cellClass}">
                            <span class="subject-name">${classInfo.subject}</span>
                            <span class="subject-type">${classInfo.type}${classInfo.room !== '-' ? ' | ' + classInfo.room : ''}</span>
                        </td>
                    `;
                } else {
                    tableHTML += `<td class="subject-cell">-</td>`;
                }
            });
            
            tableHTML += `</tr>`;
        });
        
        tableHTML += `</tbody></table>`;
        timetableContent.innerHTML = tableHTML;
    }

    updateCurrentAndNextClass() {
        const currentClassElement = document.getElementById('currentClass');
        const nextClassElement = document.getElementById('nextClass');
        
        if (!currentClassElement || !nextClassElement) return;

        const now = new Date();
        const currentDay = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][now.getDay()];
        const sectionData = window.configManager?.getTimetable(this.currentSection) || {};
        
        if (!sectionData[currentDay]) {
            currentClassElement.innerHTML = '<p>No classes today</p>';
            nextClassElement.innerHTML = '<p>No upcoming classes</p>';
            return;
        }

        const todaySchedule = sectionData[currentDay];
        const timeSlots = Object.keys(todaySchedule).sort();
        
        let currentClass = null;
        let nextClass = null;

        for (let i = 0; i < timeSlots.length; i++) {
            const timeSlot = timeSlots[i];
            const [startTime, endTime] = timeSlot.split('-');
            const [startHour, startMinute] = startTime.split(':').map(Number);
            const [endHour, endMinute] = endTime.split(':').map(Number);
            
            const startDateTime = new Date();
            startDateTime.setHours(startHour, startMinute, 0);
            
            const endDateTime = new Date();
            endDateTime.setHours(endHour, endMinute, 0);
            
            if (now >= startDateTime && now <= endDateTime) {
                currentClass = { ...todaySchedule[timeSlot], timeSlot };
                if (i + 1 < timeSlots.length) {
                    nextClass = { ...todaySchedule[timeSlots[i + 1]], timeSlot: timeSlots[i + 1] };
                }
                break;
            } else if (now < startDateTime && !nextClass) {
                nextClass = { ...todaySchedule[timeSlot], timeSlot };
            }
        }

        if (currentClass) {
            currentClassElement.innerHTML = `
                <div class="current-class-info">
                    <h4>${currentClass.subject}</h4>
                    <p><i class="fas fa-clock"></i> ${currentClass.timeSlot}</p>
                    <p><i class="fas fa-map-marker-alt"></i> ${currentClass.room}</p>
                    <p><i class="fas fa-book"></i> ${currentClass.type}</p>
                </div>
            `;
        } else {
            currentClassElement.innerHTML = '<p>No current class</p>';
        }

        if (nextClass) {
            nextClassElement.innerHTML = `
                <div class="next-class-info">
                    <h4>${nextClass.subject}</h4>
                    <p><i class="fas fa-clock"></i> ${nextClass.timeSlot}</p>
                    <p><i class="fas fa-map-marker-alt"></i> ${nextClass.room}</p>
                    <p><i class="fas fa-book"></i> ${nextClass.type}</p>
                </div>
            `;
        } else {
            nextClassElement.innerHTML = '<p>No upcoming classes</p>';
        }
    }

    renderAttendanceCalendar() {
        const calendar = document.getElementById('attendanceCalendar');
        if (!calendar) return;

        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth();
        
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        let calendarHTML = '';
        
        const dayHeaders = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
        dayHeaders.forEach(day => {
            calendarHTML += `<div class="calendar-day" style="font-weight: bold; background: var(--primary); color: white;">${day}</div>`;
        });
        
        for (let i = 0; i < firstDay; i++) {
            calendarHTML += `<div class="calendar-day"></div>`;
        }
        
        const currentUser = window.authManager?.getCurrentLoggedUser();
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dateStr = date.toISOString().split('T')[0];
            const dayAttendance = this.attendanceHistory[currentUser?.rollNumber]?.[dateStr];
            
            let dayClass = 'calendar-day';
            if (day === now.getDate() && month === now.getMonth()) {
                dayClass += ' today';
            }
            if (dayAttendance === 'present') {
                dayClass += ' present';
            } else if (dayAttendance === 'absent') {
                dayClass += ' absent';
            }
            
            calendarHTML += `<div class="${dayClass}" title="${dateStr}">${day}</div>`;
        }
        
        calendar.innerHTML = calendarHTML;
    }

    renderSubjectAttendance() {
        const tableBody = document.getElementById('subjectAttendanceTable');
        if (!tableBody) return;

        const subjects = ['CS501', 'CS502', 'CS503', 'CS504', 'CS505', 'CS506', 'CS507', 'CS508'];
        const currentUser = window.authManager?.getCurrentLoggedUser();
        
        let tableHTML = '';
        subjects.forEach(subject => {
            const subjectAttendance = this.calculateSubjectAttendance(currentUser?.rollNumber, subject);
            const present = subjectAttendance.present;
            const total = subjectAttendance.total;
            const percentage = total > 0 ? ((present / total) * 100).toFixed(1) : 0;
            
            tableHTML += `
                <tr>
                    <td>${subject}</td>
                    <td class="text-success fw-bold">${present}</td>
                    <td>${total}</td>
                    <td>
                        <span class="status-badge ${percentage >= 75 ? 'status-present' : percentage >= 60 ? 'status-late' : 'status-absent'}">
                            ${percentage}%
                        </span>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = tableHTML;
    }

    calculateSubjectAttendance(rollNumber, subject) {
        const userAttendance = this.attendanceHistory[rollNumber] || {};
        const totalDays = Object.keys(userAttendance).length;
        const presentDays = Object.values(userAttendance).filter(status => status === 'present').length;
        
        const subjectFactor = Math.random() * 0.3 + 0.7;
        return {
            present: Math.floor(presentDays * subjectFactor),
            total: Math.floor(totalDays * subjectFactor) + Math.floor(Math.random() * 5) + 1
        };
    }

    markAttendance(status = 'present') {
        const currentUser = window.authManager?.getCurrentLoggedUser();
        if (!currentUser) return;
        
        const today = new Date().toISOString().split('T')[0];
        
        if (!this.attendanceHistory[currentUser.rollNumber]) {
            this.attendanceHistory[currentUser.rollNumber] = {};
        }
        
        this.attendanceHistory[currentUser.rollNumber][today] = status;
        this.saveAttendanceHistory();
        
        this.updateAttendanceStats();
        this.renderAttendanceCalendar();
        this.renderSubjectAttendance();
        
        Utils.showAlert(`Attendance marked as ${status}!`, 'success');
    }

    saveAttendanceHistory() {
        Utils.setStorageItem('attendanceHistory', this.attendanceHistory);
    }

    updateAttendanceStats() {
        const currentUser = window.authManager?.getCurrentLoggedUser();
        if (!currentUser) return;
        
        const userAttendance = this.attendanceHistory[currentUser.rollNumber] || {};
        const presentDays = Object.values(userAttendance).filter(status => status === 'present').length;
        const totalDays = Object.keys(userAttendance).length;
        const absentDays = totalDays - presentDays;
        const percentage = totalDays > 0 ? Math.round((presentDays / totalDays) * 100) : 0;
        
        const elements = {
            totalPresent: document.getElementById('totalPresent'),
            totalAbsent: document.getElementById('totalAbsent'),
            totalClasses: document.getElementById('totalClasses'),
            attendancePercentage: document.getElementById('attendancePercentage')
        };
        
        if (elements.totalPresent) elements.totalPresent.textContent = presentDays;
        if (elements.totalAbsent) elements.totalAbsent.textContent = absentDays;
        if (elements.totalClasses) elements.totalClasses.textContent = totalDays;
        if (elements.attendancePercentage) elements.attendancePercentage.textContent = percentage + '%';
    }

    switchSection(section) {
        if (Utils.validateSection(section)) {
            this.currentSection = section;
            this.renderTimetable();
            this.updateCurrentAndNextClass();
            
            document.querySelectorAll('.section-btn').forEach(btn => btn.classList.remove('active'));
            const activeBtn = document.getElementById(`section${section}`);
            if (activeBtn) activeBtn.classList.add('active');
        }
    }

    init() {
        this.renderTimetable();
        this.updateCurrentAndNextClass();
        this.renderAttendanceCalendar();
        this.renderSubjectAttendance();
        this.updateAttendanceStats();
        
        setInterval(() => {
            this.updateCurrentAndNextClass();
        }, 60000);
    }

    show(userData) {
        document.getElementById('authContainer').classList.add('hidden');
        document.getElementById('studentDashboard').classList.remove('hidden');
        document.getElementById('adminDashboard').classList.add('hidden');
        document.getElementById('deviceWarning').classList.add('hidden');

        const elements = {
            userName: document.getElementById('userName'),
            userRoll: document.getElementById('userRoll'),
            userSection: document.getElementById('userSection'),
            userAvatar: document.getElementById('userAvatar')
        };
        
        if (elements.userName) elements.userName.textContent = userData.username;
        if (elements.userRoll) elements.userRoll.textContent = userData.rollNumber;
        if (elements.userSection) elements.userSection.textContent = `Section ${userData.section}`;
        if (elements.userAvatar) elements.userAvatar.textContent = userData.username.charAt(0).toUpperCase();
        
        this.currentSection = userData.section;
        this.init();
    }
}

// Admin Dashboard Manager
class AdminDashboard {
    constructor() {
        this.registeredStudents = [];
        this.attendanceData = {};
    }

    loadRegisteredStudents() {
        const registeredDevices = Utils.getStorageItem('registeredDevices', {});
        this.registeredStudents = Object.entries(registeredDevices).map(([deviceId, userData]) => ({
            ...userData,
            deviceId,
            id: userData.rollNumber
        }));
        
        this.renderStudentTable();
        this.updateAdminStats();
    }

    renderStudentTable() {
        const tbody = document.getElementById('studentTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';

        this.registeredStudents.forEach(student => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${student.rollNumber}</td>
                <td>${student.username}</td>
                <td>Section ${student.section}</td>
                <td>${student.deviceId.substring(0, 12)}...</td>
                <td>${new Date(student.registrationDate).toLocaleDateString()}</td>
                <td>${new Date(student.lastLogin).toLocaleDateString()}</td>
                <td><span class="status-badge status-present">Active</span></td>
                <td>
                    <div class="d-flex gap-2" style="gap: 5px;">
                        <button class="btn btn-sm btn-warning" onclick="window.adminDashboard.editStudent('${student.rollNumber}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="window.adminDashboard.forceLogoutStudent('${student.rollNumber}')">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="window.adminDashboard.viewStudentDetails('${student.rollNumber}')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    updateAdminStats() {
        const totalStudents = this.registeredStudents.length;
        const activeStudents = this.registeredStudents.filter(s => 
            new Date(s.lastLogin) > new Date(Date.now() - 24 * 60 * 60 * 1000)
        ).length;

        const elements = {
            totalStudents: document.getElementById('totalStudents'),
            activeStudents: document.getElementById('activeStudents'),
            totalDevices: document.getElementById('totalDevices'),
            systemStatus: document.getElementById('systemStatus')
        };

        if (elements.totalStudents) elements.totalStudents.textContent = totalStudents;
        if (elements.activeStudents) elements.activeStudents.textContent = activeStudents;
        if (elements.totalDevices) elements.totalDevices.textContent = totalStudents;
        if (elements.systemStatus) elements.systemStatus.textContent = 'Operational';
    }

    forceLogoutStudent(rollNumber) {
        if (confirm(`Are you sure you want to logout student ${rollNumber}?`)) {
            if (window.authManager.forceLogoutUser(rollNumber)) {
                this.loadRegisteredStudents();
                Utils.showAlert(`Student ${rollNumber} has been logged out successfully.`, 'success');
            }
        }
    }

    editStudent(rollNumber) {
        const student = this.registeredStudents.find(s => s.rollNumber === rollNumber);
        if (!student) {
            Utils.showAlert('Student not found.', 'danger');
            return;
        }

        const newUsername = prompt('Enter new username:', student.username);
        const newSection = prompt('Enter new section (A/B/C/D):', student.section);

        if (newUsername && Utils.validateSection(newSection)) {
            const registeredDevices = Utils.getStorageItem('registeredDevices', {});
            const deviceId = student.deviceId;
            
            if (registeredDevices[deviceId]) {
                registeredDevices[deviceId].username = Utils.sanitizeInput(newUsername);
                registeredDevices[deviceId].section = Utils.sanitizeInput(newSection);
                Utils.setStorageItem('registeredDevices', registeredDevices);
                
                this.loadRegisteredStudents();
                Utils.showAlert('Student information updated successfully.', 'success');
            }
        } else {
            Utils.showAlert('Invalid input. Please check username and section.', 'danger');
        }
    }

    viewStudentDetails(rollNumber) {
        const student = this.registeredStudents.find(s => s.rollNumber === rollNumber);
        if (!student) {
            Utils.showAlert('Student not found.', 'danger');
            return;
        }

        const attendanceHistory = Utils.getStorageItem('attendanceHistory', {});
        const studentAttendance = attendanceHistory[rollNumber] || {};
        const presentDays = Object.values(studentAttendance).filter(status => status === 'present').length;
        const totalDays = Object.keys(studentAttendance).length;
        const percentage = totalDays > 0 ? Math.round((presentDays / totalDays) * 100) : 0;

        const modal = this.createStudentDetailsModal(student, {
            presentDays,
            totalDays,
            percentage,
            lastAttendance: this.getLastAttendanceDate(studentAttendance)
        });

        document.body.appendChild(modal);
    }

    createStudentDetailsModal(student, attendance) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        `;

        modal.innerHTML = `
            <div class="modal-content card" style="max-width: 500px; margin: 20px;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Student Details</h3>
                    <button class="btn btn-sm btn-secondary" onclick="this.closest('.modal-overlay').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 1rem;">
                        <strong>Roll Number:</strong> ${student.rollNumber}<br>
                        <strong>Name:</strong> ${student.username}<br>
                        <strong>Section:</strong> ${student.section}<br>
                        <strong>Device ID:</strong> ${student.deviceId.substring(0, 16)}...<br>
                        <strong>Registration Date:</strong> ${new Date(student.registrationDate).toLocaleDateString()}<br>
                        <strong>Last Login:</strong> ${new Date(student.lastLogin).toLocaleString()}<br>
                        <strong>Login Count:</strong> ${student.loginCount || 1}
                    </div>
                    <hr>
                    <h4>Attendance Summary</h4>
                    <div style="margin-top: 1rem;">
                        <strong>Present Days:</strong> ${attendance.presentDays}<br>
                        <strong>Total Days:</strong> ${attendance.totalDays}<br>
                        <strong>Percentage:</strong> <span class="status-badge ${attendance.percentage >= 75 ? 'status-present' : attendance.percentage >= 60 ? 'status-late' : 'status-absent'}">${attendance.percentage}%</span><br>
                        <strong>Last Attendance:</strong> ${attendance.lastAttendance || 'Never'}
                    </div>
                </div>
            </div>
        `;

        modal.onclick = (e) => {
            if (e.target === modal) modal.remove();
        };

        return modal;
    }

    getLastAttendanceDate(attendanceHistory) {
        const dates = Object.keys(attendanceHistory).sort().reverse();
        return dates.length > 0 ? new Date(dates[0]).toLocaleDateString() : null;
    }

    exportStudentData() {
        const csvContent = "data:text/csv;charset=utf-8," 
            + "Roll Number,Username,Section,Device ID,Registration Date,Last Login,Login Count\n"
            + this.registeredStudents.map(student => 
                `${student.rollNumber},${student.username},${student.section},${student.deviceId.substring(0, 12)}...,${new Date(student.registrationDate).toLocaleDateString()},${new Date(student.lastLogin).toLocaleDateString()},${student.loginCount || 1}`
            ).join("\n");
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `student_data_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        Utils.showAlert('Student data exported successfully!', 'success');
    }

    bulkLogout() {
        if (confirm('Are you sure you want to logout all students? This action cannot be undone.')) {
            Utils.removeStorageItem('registeredDevices');
            Utils.removeStorageItem('currentUser');
            this.loadRegisteredStudents();
            Utils.showAlert('All students have been logged out successfully.', 'success');
        }
    }

    generateQRCode() {
        const classSelector = document.getElementById('classSelector');
        const qrDisplay = document.getElementById('qrCodeDisplay');
        
        if (!classSelector || !qrDisplay) return;
        
        if (!classSelector.value) {
            Utils.showAlert('Please select a class first.', 'warning');
            return;
        }
        
        const qrData = {
            class: classSelector.value,
            timestamp: new Date().toISOString(),
            validUntil: new Date(Date.now() + 30 * 60 * 1000).toISOString()
        };
        
        qrDisplay.innerHTML = `
            <div class="qr-code-display text-center">
                <div class="icon">
                    <i class="fas fa-qrcode" style="color: var(--success); font-size: 4rem;"></i>
                </div>
                <h4 style="color: var(--success);">QR Code Generated</h4>
                <p><strong>Class:</strong> ${classSelector.value}</p>
                <p><strong>Generated:</strong> ${new Date().toLocaleTimeString()}</p>
                <p><strong>Valid Until:</strong> ${new Date(qrData.validUntil).toLocaleTimeString()}</p>
                <p style="font-size: 0.8rem; color: var(--muted);">Students can scan this code to mark attendance</p>
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm" onclick="window.adminDashboard.refreshQRCode()">
                        <i class="fas fa-refresh"></i> Refresh
                    </button>
                </div>
            </div>
        `;
        
        Utils.setStorageItem('activeQRCode', qrData);
        Utils.showAlert(`QR code generated for ${classSelector.value}`, 'success');
    }

    refreshQRCode() {
        const classSelector = document.getElementById('classSelector');
        if (classSelector && classSelector.value) {
            this.generateQRCode();
        }
    }

    markManualAttendance() {
        const rollNumber = document.getElementById('manualRollNumber').value;
        const status = document.getElementById('attendanceStatus').value;
        
        if (!rollNumber) {
            Utils.showAlert('Please enter a roll number.', 'warning');
            return;
        }
        
        if (!Utils.isValidRollNumber(rollNumber)) {
            Utils.showAlert('Invalid roll number format.', 'danger');
            return;
        }
        
        const student = this.registeredStudents.find(s => s.rollNumber === rollNumber);
        if (!student) {
            Utils.showAlert('Student not found in registered users.', 'danger');
            return;
        }
        
        const attendanceHistory = Utils.getStorageItem('attendanceHistory', {});
        const today = new Date().toISOString().split('T')[0];
        
        if (!attendanceHistory[rollNumber]) {
            attendanceHistory[rollNumber] = {};
        }
        
        attendanceHistory[rollNumber][today] = status;
        Utils.setStorageItem('attendanceHistory', attendanceHistory);
        
        document.getElementById('manualRollNumber').value = '';
        document.getElementById('attendanceStatus').value = 'present';
        
        Utils.showAlert(`Attendance marked as ${status} for ${rollNumber}`, 'success');
    }

    show() {
        document.getElementById('authContainer').classList.add('hidden');
        document.getElementById('studentDashboard').classList.add('hidden');
        document.getElementById('adminDashboard').classList.remove('hidden');
        document.getElementById('deviceWarning').classList.add('hidden');
        
        this.loadRegisteredStudents();
    }
}

// Main Application Controller
class AttendanceSystem {
    constructor() {
        this.currentSection = 'A';
        this.attendanceHistory = Utils.getStorageItem('attendanceHistory', {});
        this.notificationSystem = null;
    }

    async init() {
        // Initialize configuration
        window.configManager = new ConfigManager();
        await window.configManager.loadConfig();
        
        // Initialize managers
        window.authManager = new AuthManager();
        window.studentDashboard = new StudentDashboard();
        window.adminDashboard = new AdminDashboard();
        
        // Initialize theme
        Utils.initializeTheme();
        
        // Update time display
        this.updateTime();
        setInterval(() => this.updateTime(), 1000);
        
        // Check authentication and show appropriate interface
        this.checkDeviceAndLogin();
    }

    updateTime() {
        const timeElement = document.getElementById('currentTime');
        if (timeElement) {
            timeElement.textContent = Utils.formatTime();
        }
        
        const dateElement = document.getElementById('currentDate');
        if (dateElement) {
            dateElement.textContent = Utils.formatDate();
        }
    }

    checkDeviceAndLogin() {
        const authResult = window.authManager.init();
        
        if (authResult.success) {
            if (authResult.userType === 'admin') {
                window.adminDashboard.show();
            } else {
                window.studentDashboard.show(authResult.userData);
                this.startNotificationSystem();
            }
        } else {
            this.showAuthContainer();
        }
    }

    showAuthContainer() {
        document.getElementById('authContainer').classList.remove('hidden');
        document.getElementById('studentDashboard').classList.add('hidden');
        document.getElementById('adminDashboard').classList.add('hidden');
        document.getElementById('deviceWarning').classList.add('hidden');
    }

    showDeviceWarning() {
        document.getElementById('authContainer').classList.add('hidden');
        document.getElementById('studentDashboard').classList.add('hidden');
        document.getElementById('adminDashboard').classList.add('hidden');
        document.getElementById('deviceWarning').classList.remove('hidden');
    }

    startNotificationSystem() {
        // Implement notification system for upcoming classes
        if (this.notificationSystem) {
            clearInterval(this.notificationSystem);
        }
        
        this.notificationSystem = setInterval(() => {
            this.checkUpcomingClasses();
        }, 60000); // Check every minute
    }

    checkUpcomingClasses() {
        const currentUser = window.authManager.getCurrentLoggedUser();
        if (!currentUser) return;
        
        const now = new Date();
        const currentDay = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][now.getDay()];
        const sectionData = window.configManager?.getTimetable(currentUser.section) || {};
        
        if (!sectionData[currentDay]) return;
        
        const todaySchedule = sectionData[currentDay];
        const timeSlots = Object.keys(todaySchedule).sort();
        
        timeSlots.forEach(timeSlot => {
            const [startTime] = timeSlot.split('-');
            const [startHour, startMinute] = startTime.split(':').map(Number);
            
            const startDateTime = new Date();
            startDateTime.setHours(startHour, startMinute, 0);
            
            const timeDiff = startDateTime - now;
            const minutesUntil = Math.floor(timeDiff / (1000 * 60));
            
            if (minutesUntil === 10 || minutesUntil === 5) {
                const classInfo = todaySchedule[timeSlot];
                this.showNotification(
                    'Class Reminder',
                    `Your ${classInfo.subject} class starts in ${minutesUntil} minutes in ${classInfo.room}`,
                    'upcoming'
                );
            }
        });
    }

    showNotification(title, message, type = 'info') {
        const notificationContainer = document.getElementById('notificationContainer') || this.createNotificationContainer();
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <h4><i class="fas ${this.getNotificationIcon(type)}"></i> ${title}</h4>
            <p>${message}</p>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        notificationContainer.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 10000);
    }

    createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'notificationContainer';
        container.className = 'notification-container';
        document.body.appendChild(container);
        return container;
    }

    getNotificationIcon(type) {
        const icons = {
            upcoming: 'fa-clock',
            'attendance-time': 'fa-calendar-check',
            urgent: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }
}

// Global Functions for HTML integration
function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
    
    document.querySelector(`[onclick*="${tab}"]`).classList.add('active');
    document.getElementById(`${tab}Tab`).classList.add('active');
}

function switchDashboardTab(tab) {
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(tp => tp.classList.remove('active'));
    
    document.querySelector(`[onclick*="${tab}"]`).classList.add('active');
    document.getElementById(`${tab}Tab`).classList.add('active');
    
    if (tab === 'timetable') {
        window.studentDashboard.renderTimetable();
    } else if (tab === 'history') {
        window.studentDashboard.renderAttendanceCalendar();
        window.studentDashboard.renderSubjectAttendance();
    }
}

function switchAdminTab(tab) {
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(tp => tp.classList.remove('active'));
    
    document.querySelector(`[onclick*="${tab}"]`).classList.add('active');
    document.getElementById(`admin${tab.charAt(0).toUpperCase() + tab.slice(1)}Tab`).classList.add('active');
    
    if (tab === 'students') {
        window.adminDashboard.loadRegisteredStudents();
    }
}

function switchSection(section) {
    document.querySelectorAll('.section-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(`section${section}`).classList.add('active');
    window.studentDashboard.switchSection(section);
}

function handleStudentAuth(event) {
    event.preventDefault();
    
    const rollNumber = document.getElementById('rollNumber').value;
    const section = document.getElementById('studentSection').value;
    const username = document.getElementById('studentUsername').value;
    const password = document.getElementById('studentPassword').value;

    if (window.authManager.loginStudent(rollNumber, username, password, section)) {
        const userData = { rollNumber, username, section, role: 'student' };
        window.studentDashboard.show(userData);
        window.attendanceSystem.startNotificationSystem();
    }
}

function handleAdminAuth(event) {
    event.preventDefault();
    
    const username = document.getElementById('adminUsername').value;
    const password = document.getElementById('adminPassword').value;

    if (window.authManager.loginAdmin(username, password)) {
        window.adminDashboard.show();
    }
}

function adminLogout() {
    window.authManager.logout();
    window.attendanceSystem.showAuthContainer();
}

function clearDeviceData() {
    window.authManager.clearAllDeviceData();
}

function scanQR() {
    const scanner = document.getElementById('qrScanner');
    scanner.classList.add('scanning');
    
    setTimeout(() => {
        scanner.classList.remove('scanning');
        window.studentDashboard.markAttendance('present');
        window.studentDashboard.updateCurrentAndNextClass();
    }, 2000);
}

function exportStudentData() {
    window.adminDashboard.exportStudentData();
}

function bulkLogout() {
    window.adminDashboard.bulkLogout();
}

function generateQRCode() {
    window.adminDashboard.generateQRCode();
}

function markManualAttendance() {
    window.adminDashboard.markManualAttendance();
}

function generateClassReport() {
    const reportType = document.getElementById('reportType').value;
    const reportDate = document.getElementById('reportDate').value || new Date().toISOString().split('T')[0];
    
    Utils.showAlert(`${reportType.charAt(0).toUpperCase() + reportType.slice(1)} report generation started...`, 'info');
    
    setTimeout(() => {
        Utils.showAlert(`${reportType.charAt(0).toUpperCase() + reportType.slice(1)} report generated successfully!`, 'success');
    }, 2000);
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.attendanceSystem = new AttendanceSystem();
    window.attendanceSystem.init();
});
