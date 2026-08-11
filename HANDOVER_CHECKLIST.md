# ISP-BILLING-LITE
## System Handover Checklist

This document provides the steps required to deploy, run, maintain, and troubleshoot the ISP-BILLING-LITE system.

---

# 1. System Requirements

Operating System:

- Ubuntu 22.04 (WSL or Linux server)

Required Software:

- Nginx
- PHP
- MariaDB
- Composer
- Git

Recommended PHP Extensions:

- pdo
- pdo_mysql
- mbstring
- openssl
- fileinfo

---

# 2. Project Installation

Clone the project repository: git clone <repository-url>


Enter the project folder: cd ISP-BILLING-LITE


Install dependencies: composer install


---

# 3. Database Setup

Create the database: CREATE DATABASE isp_billing_lite_db;


Import the database schema: mysql -u root -p isp_billing_lite_db < database.sql


Update the database configuration file: config/database.php


Example configuration:
return [
'host' => '127.0.0.1',
'db' => 'isp_billing_lite_db',
'user' => 'root',
'pass' => '',
];




---

# 4. Web Server Configuration

Nginx must point to: /public


Example server block: server {
listen 80;
server_name isp-billing-lite.local;

root /path/to/ISP-BILLING-LITE/public;

index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/var/run/php/php-fpm.sock;
}
}


Restart Nginx: sudo service nginx restart


---

# 5. File Upload Permissions

Receipt uploads are stored in: public/uploads/receipts


Set permissions:
sudo chown -R www-data:www-data public/uploads
sudo chmod -R 775 public/uploads


---

# 6. Default System Access

Default admin account:
Email: admin@isp.com

Password: admin123








Administrators can create additional users through the **Users module**.

---

# 7. Running the System

Open the system in a browser: http://isp-billing-lite.local


Login using administrator credentials.

---

# 8. Database Backup

To backup the database: mysqldump -u root -p isp_billing_lite_db > backup.sql


This creates a backup file named: backup.sql


---

# 9. Database Restore

To restore a backup: mysql -u root -p isp_billing_lite_db < backup.sql


---

# 10. Email Configuration

Email settings are stored in: config/mail.php


Example configuration:
SMTP_HOST
SMTP_PORT
SMTP_USERNAME
SMTP_PASSWORD
SMTP_FROM


Email is used for:

- Payment confirmation
- Payment rejection
- Overdue reminders

---

# 11. System Maintenance

Recommended maintenance tasks:

- Verify payments daily
- Monitor outstanding invoices
- Backup the database regularly
- Review system logs

---

# 12. Troubleshooting

Login issues:

- Verify database connection
- Check user password

Upload issues:

- Verify upload folder permissions
- Check file size limits

Email issues:

- Verify SMTP configuration
- Check mail server connectivity

Database issues:

- Ensure MariaDB service is running

sudo service mysql status


---

# 13. Future Improvements

Possible enhancements:

- Automated billing scheduler
- Customer self-service portal
- Online payment gateway integration
- Advanced reporting dashboard
- API support

---

End of Handover Checklist



