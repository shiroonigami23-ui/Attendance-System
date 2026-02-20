# AWS Deployment Guide (EC2 + Apache + MySQL/RDS)

This is the fastest production path for this PHP project.

## 1. Launch infrastructure

1. Create an EC2 Ubuntu instance (`t3.large` or above for load tests).
2. Open Security Group ports:
   - `22` (SSH) from your IP
   - `80` (HTTP) from `0.0.0.0/0`
   - `443` (HTTPS) from `0.0.0.0/0` (when SSL enabled)
3. Use either:
   - Local MySQL on EC2, or
   - RDS MySQL (recommended).

## 2. Deploy app on EC2

```bash
git clone https://github.com/shiroonigami23-ui/Attendance-System.git
cd Attendance-System
chmod +x aws/setup.sh
./aws/setup.sh
```

## 3. Configure environment

Edit:

```bash
sudo nano /etc/attendance-system/attendance.env
```

Required values:

- `BASE_URL`
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `SALT_DEVICE` (long random secret)
- SMTP values if email is needed

Then restart Apache:

```bash
sudo systemctl restart apache2
```

## 4. Database setup

1. Create DB and user.
2. Import your SQL schema/data.
3. Verify app DB connectivity:

```bash
cd /var/www/html/Attendance_System
sudo -u www-data /usr/bin/php tests/validate_env.php
```

## 5. Production hardening checklist

1. Set `APP_ENV=production`, `APP_DEBUG=0`.
2. Use HTTPS (ALB + ACM certificate or Certbot).
3. Restrict DB Security Group to EC2 SG only.
4. Disable public directory indexing (already handled in `aws/apache_config.conf`).
5. Keep `/tests` local-only (already restricted in vhost).
6. Rotate credentials and salts before go-live.

## 6. Performance for 10k attendance bursts

Recommended baseline:

- EC2: `t3.large`+ (or `c6i.large` for better CPU consistency)
- Apache worker tuning and PHP OPcache enabled
- MySQL/RDS with `max_connections` tuned above peak concurrent workers

Temporary local tuning example:

```sql
SET GLOBAL max_connections = 800;
```

For production/RDS, set this in your DB parameter group (persistent).

Run stress test from app host:

```bash
cd /var/www/html/Attendance_System
php tests/load_test_10k.php --requests=10000 --concurrency=200
```

If you see intermittent 500s under heavy concurrency, increase DB connection limits and use a larger DB instance/class.

## 7. Smoke test after deploy

```bash
cd /var/www/html/Attendance_System
powershell -ExecutionPolicy Bypass -File tests/run_full_verification.ps1 -BaseUrl http://localhost/Attendance_System -Requests 500 -Concurrency 100
```

If all pass, cut traffic to the server URL/domain.
