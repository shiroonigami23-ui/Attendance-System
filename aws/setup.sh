#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/html/Attendance_System"
SITE_CONF="/etc/apache2/sites-available/attendance.conf"
ENV_FILE="/etc/attendance-system/attendance.env"

echo "[1/8] Updating packages..."
sudo apt-get update -y

echo "[2/8] Installing Apache + PHP runtime..."
sudo apt-get install -y \
  apache2 \
  mysql-client \
  unzip \
  curl \
  git \
  rsync \
  php \
  libapache2-mod-php \
  php-mysql \
  php-gd \
  php-mbstring \
  php-xml \
  php-curl \
  php-zip \
  php-intl \
  php-cli \
  php-opcache

echo "[3/8] Enabling Apache modules..."
sudo a2enmod rewrite headers expires ssl

echo "[4/8] Preparing app directory..."
sudo mkdir -p "$APP_DIR"
sudo rsync -a --delete ./ "$APP_DIR"/ --exclude ".git" --exclude "assets/uploads"

echo "[5/8] Setting writable directories..."
sudo mkdir -p "$APP_DIR/assets/uploads"
sudo chown -R www-data:www-data "$APP_DIR/assets/uploads"
sudo chmod -R 775 "$APP_DIR/assets/uploads"

echo "[6/8] Installing Apache vhost..."
sudo cp aws/apache_config.conf "$SITE_CONF"
sudo a2dissite 000-default.conf || true
sudo a2ensite attendance.conf

echo "[7/8] Creating env template..."
sudo mkdir -p /etc/attendance-system
if [[ ! -f "$ENV_FILE" ]]; then
  sudo tee "$ENV_FILE" >/dev/null <<'EOF'
# Attendance System runtime variables
export APP_ENV=production
export APP_DEBUG=0
export APP_TIMEZONE=Asia/Kolkata
export BASE_URL=https://your-domain.example/

export DB_HOST=127.0.0.1
export DB_NAME=attendance_system
export DB_USER=attendance_user
export DB_PASS=change-me

export SALT_DEVICE=change-this-to-a-long-random-secret

export WIFI_FENCING_ENABLED=1
export GEO_FENCING_ENABLED=1
export CAMPUS_LAT=26.15843
export CAMPUS_LNG=78.49089
export ALLOWED_RADIUS=200

export SMTP_HOST=email-smtp.us-east-1.amazonaws.com
export SMTP_USER=
export SMTP_PASS=
export SMTP_PORT=587
export SMTP_FROM=noreply@example.com
EOF
fi

if ! grep -q "attendance.env" /etc/apache2/envvars; then
  echo "source $ENV_FILE" | sudo tee -a /etc/apache2/envvars >/dev/null
fi

echo "[8/8] Restarting Apache..."
sudo apachectl configtest
sudo systemctl restart apache2
sudo systemctl enable apache2

echo "Done."
echo "Next:"
echo "  1) Edit $ENV_FILE with your real DB/SMTP/BASE_URL values."
echo "  2) Run: sudo systemctl restart apache2"
echo "  3) Open: http://<server-ip>/Attendance_System/"
