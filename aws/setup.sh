#!/bin/bash

# Update package list
sudo apt-get update

# Install Apache and PHP
sudo apt-get install -y apache2 php libapache2-mod-php php-mysql php-gd php-mbstring php-xml php-curl

# Enable Apache modules
sudo a2enmod rewrite

# Create project directory
sudo mkdir -p /var/www/html/Attendance_System
sudo chown -R $USER:$USER /var/www/html/Attendance_System

# Copy application files (Run this from the repo root)
# cp -r * /var/www/html/Attendance_System/

# Set permissions for uploads
sudo mkdir -p /var/www/html/Attendance_System/assets/uploads
sudo chown -R www-data:www-data /var/www/html/Attendance_System/assets/uploads
sudo chmod -R 775 /var/www/html/Attendance_System/assets/uploads

# Configure Apache (Copy the config file)
# sudo cp aws/apache_config.conf /etc/apache2/sites-available/attendance.conf
# sudo a2ensite attendance.conf
# sudo systemctl reload apache2

echo "Setup complete. Please configure your database and environment variables."
