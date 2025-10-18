// js/utils.js

export class ConfigManager {
    constructor() {
        this.config = null;
    }


    async loadConfig() {
        try {
            const response = await fetch('config.xml');
            if (!response.ok) {
                // This will now throw a user-visible error
                throw new Error(`Could not find or load config.xml. Please ensure it is in the root project folder.`);
            }
            const xmlText = await response.text();
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xmlText, 'text/xml');
            
            // Check if parsing failed
            if (xmlDoc.getElementsByTagName('parsererror').length) {
                throw new Error('Error parsing config.xml. Check the file for syntax errors.');
            }
            
            this.config = this.parseXMLConfig(xmlDoc);
        } catch (error) {
            console.error('CRITICAL ERROR:', error);
            // Show a visible alert to the user
            Utils.showAlert(error.message, 'danger', 0); // duration 0 means it stays until closed
            this.config = {}; // Fallback to empty config
        }
    }


    parseXMLConfig(xmlDoc) {
        const config = {};
        // System Info
        const system = xmlDoc.querySelector('system');
        config.system = {
            name: system?.querySelector('name')?.textContent || 'Attendance System',
        };
        // Roll Numbers
        config.rollNumbers = Array.from(xmlDoc.querySelectorAll('roll-numbers range')).map(range => ({
            type: range.querySelector('type')?.textContent,
            prefix: range.querySelector('prefix')?.textContent,
            start: range.querySelector('start')?.textContent,
            end: range.querySelector('end')?.textContent,
        }));
        // Admin Credentials
        const admin = xmlDoc.querySelector('admin');
        config.admin = {
            username: admin?.querySelector('username')?.textContent || 'admin',
            password: admin?.querySelector('password')?.textContent || 'admin123',
        };
        // --- NEW: Parse Subjects ---
        config.subjects = Array.from(xmlDoc.querySelectorAll('subjects subject')).map(subject => ({
            code: subject.getAttribute('code'),
            name: subject.getAttribute('name'),
        }));
        // Timetable
        config.timetable = {};
        xmlDoc.querySelectorAll('timetable section').forEach(section => {
            const sectionId = section.getAttribute('id');
            config.timetable[sectionId] = {};
            section.querySelectorAll('day').forEach(day => {
                const dayName = day.getAttribute('name');
                config.timetable[sectionId][dayName] = {};
                day.querySelectorAll('slot').forEach(slot => {
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

    getRollNumberRanges() { return this.config?.rollNumbers || []; }
    getAdminCredentials() { return this.config?.admin || {}; }
    getTimetable(section) { return this.config?.timetable?.[section] || {}; }
    // --- NEW: Getter for Subjects ---
    getSubjects() { return this.config?.subjects || []; }
}

export const Utils = {
    generateDeviceFingerprint() {
        const fingerprint = [
            navigator.userAgent,
            navigator.language,
            screen.width + 'x' + screen.height,
            new Date().getTimezoneOffset(),
            navigator.platform,
        ].join('|');
        // Simple hash function
        let hash = 0;
        for (let i = 0; i < fingerprint.length; i++) {
            const char = fingerprint.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }
        return `device_${Math.abs(hash)}`;
    },

    isValidRollNumber(rollNumber, configManager) {
        if (!rollNumber) return false;
        const ranges = configManager.getRollNumberRanges();
        for (const range of ranges) {
            if (rollNumber.startsWith(range.prefix)) {
                const numPart = rollNumber.substring(range.prefix.length);
                const num = parseInt(numPart, 10);
                const start = parseInt(range.start, 10);
                const end = parseInt(range.end, 10);
                if (!isNaN(num) && num >= start && num <= end) {
                    return true;
                }
            }
        }
        return false;
    },

    showAlert(message, type = 'info', duration = 5000) {
        const alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) return;

        const alert = document.createElement('div');
        const icons = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `<i class="alert-icon fas ${icons[type]}"></i><span>${message}</span>`;
        alertContainer.appendChild(alert);

        setTimeout(() => alert.remove(), duration);
    },

    formatTime: (date = new Date()) => date.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit' }),
    formatDate: (date = new Date()) => date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }),
    
    sanitizeInput(input) {
        const div = document.createElement('div');
        div.textContent = input;
        return div.innerHTML;
    },

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        this.updateThemeToggle(newTheme);
    },

    updateThemeToggle(theme) {
        document.querySelectorAll('.theme-toggle').forEach(toggle => {
            const icon = toggle.querySelector('i');
            const text = toggle.querySelector('span');
            if (theme === 'dark') {
                icon.className = 'fas fa-sun';
                if(text) text.textContent = 'Light Mode';
            } else {
                icon.className = 'fas fa-moon';
                if(text) text.textContent = 'Dark Mode';
            }
        });
    },

    initializeTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        this.updateThemeToggle(savedTheme);
    }
};
