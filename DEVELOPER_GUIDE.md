# ISP-BILLING-LITE
## Developer Guide

This guide explains the technical structure of the ISP-BILLING-LITE system for developers.

It includes the system architecture, folder structure, database structure, and setup instructions.

---

# 1. System Overview

ISP-BILLING-LITE is a lightweight billing system for Internet Service Providers.

Main features:

- Customer management
- Internet plan management
- Subscription management
- Invoice generation
- Payment recording
- Receipt upload
- Payment verification
- Email notifications
- Revenue and outstanding reports
- Role-based access control

---

# 2. Technology Stack

Environment:

- Windows 11
- WSL Ubuntu 22.04

Server:

- Nginx

Language:

- PHP (Custom MVC structure)

Database:

- MariaDB

Libraries:

- DomPDF (Invoice PDF generation)
- PHPMailer (Email sending)

---

# 3. Project Folder Structure

ISP-BILLING-LITE
│
├── app
│ ├── Controllers
│ │ ├── AuthController.php
│ │ ├── CustomerController.php
│ │ ├── DashboardController.php
│ │ ├── InvoiceController.php
│ │ ├── PaymentController.php
│ │ ├── PlanController.php
│ │ ├── SubscriptionController.php
│ │ └── UserController.php
│ │
│ └── Services
│ └── MailService.php
│
├── config
│ ├── database.php
│ └── mail.php
│
├── core
│ ├── Database.php
│ ├── Router.php
│ ├── View.php
│ └── helpers.php
│
├── public
│ ├── index.php
│ ├── uploads
│ │ └── receipts
│
├── routes
│ └── web.php
│
├── views
│ ├── layouts
│ ├── dashboard
│ ├── customers
│ ├── plans
│ ├── subscriptions
│ ├── invoices
│ ├── payments
│ ├── reports
│ └── users
│
└── vendor


---

# 4. Architecture

The system uses a **custom MVC-style architecture**.

### Router

Located in: core/Router.php


Handles incoming HTTP requests and maps them to controller methods.

Routes are defined in: routes/web.php


---

### Controllers

Located in: app/Controllers


Controllers process requests, interact with the database, and render views.

Example controllers:

- AuthController
- CustomerController
- PlanController
- SubscriptionController
- InvoiceController
- PaymentController
- UserController

---

### Views

Located in:views/


Views handle the frontend display.

Examples:

- dashboard
- customers
- plans
- invoices
- payments
- reports

The main layout template is: views/layouts/main.php








---

# 5. Database

Database name: isp_billing_lite_db


Main tables:

| Table | Purpose |
|------|------|
| users | system accounts |
| customers | ISP subscribers |
| plans | internet service plans |
| subscriptions | customer-plan assignments |
| invoices | billing records |
| payments | payment records |
| notifications | email notification logs |

---

# 6. Authentication System

Authentication uses:

- PHP sessions
- role-based access

Roles:

ROLE_ADMIN
ROLE_STAFF
ROLE_CUSTOMER


Passwords are stored using: password_hash()


Legacy SHA256 passwords are automatically upgraded during login.

---

# 7. Payment Workflow

Payment process:

1. Payment is recorded.
2. Receipt can be uploaded.
3. Payment status becomes **PENDING**.
4. Administrator verifies payment.

If verified:

- payment status = VERIFIED
- invoice status = PAID

If rejected:

- payment status = REJECTED
- invoice status = ISSUED

---

# 8. File Upload System

Receipt uploads are stored in: public/uploads/receipts


Allowed file types:

- JPG
- JPEG
- PNG
- WEBP

Uploaded files are renamed with timestamps for uniqueness.

---

# 9. Email Notifications

Emails are handled using: PHPMailer


Mail service located at: app/Services/MailService.php


Email types include:

- payment confirmation
- payment rejection
- overdue reminders

Notification logs are stored in the `notifications` table.

---

# 10. Running the Project

Local development server: http://isp-billing-lite.local


Ensure the following services are running: nginx
php-fpm
mariadb


---

# 11. Dependencies

Install dependencies using Composer: composer install


Vendor libraries include:

- dompdf
- phpmailer

---

# 12. Security Notes

Security practices implemented:

- password hashing
- input validation
- file upload validation
- role-based access control
- session-based authentication

---

# 13. Future Improvements

Possible future enhancements:

- automated billing scheduler
- customer portal
- payment gateway integration
- analytics dashboard
- API support

---

End of Developer Guide.




