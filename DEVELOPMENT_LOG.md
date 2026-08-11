# Development Log

# ISP-BILLING-LITE
## Development Log

Project Name: ISP-BILLING-LITE  
Database: isp_billing_lite_db  
Environment: Windows 11 + WSL Ubuntu 22.04  
Web Server: Nginx  
Language: PHP  
Database Engine: MariaDB  

---

# Week 1 — Foundation & Environment Setup

Completed Tasks:

- Installed WSL Ubuntu 22.04
- Installed Nginx web server
- Installed PHP
- Installed MariaDB
- Installed Composer and Git
- Created ISP-BILLING-LITE project folder
- Initialized project structure

Project Architecture:

- Custom PHP MVC structure
- Routing system
- Controllers and Views
- Database connection module
- Session authentication

Authentication System:

- Login page
- Login logic
- Logout
- Session-based authentication
- Role-based access control

Roles:

- ROLE_ADMIN
- ROLE_STAFF
- ROLE_CUSTOMER

UI Foundation:

- Shared layout
- Sidebar navigation
- Dashboard page
- Mobile responsive layout

---

# Week 2 — Billing Core

Customer Management:

- Create customer
- Edit customer
- Delete customer
- Customer listing

Plan Management:

- Create internet plans
- Edit plans
- Delete plans
- Plan listing

Subscription Management:

- Assign plan to customer
- Edit subscription
- Delete subscription

Invoice System:

- Manual invoice generation
- Invoice list page
- Invoice view page
- Invoice deletion
- Invoice PDF generation

Payment Module:

- Record payment
- Upload GCash receipt
- Payment listing
- Payment status management

---

# Week 3 — Payment Verification & Policies

Payment Verification Workflow:

- Verify payment
- Reject payment

Payment Status:

- PENDING
- VERIFIED
- REJECTED

Invoice Status:

- ISSUED
- PAID
- OVERDUE

Payment Processing:

- Verified payment automatically marks invoice as PAID
- Rejected payment returns invoice to ISSUED

Reports:

- Outstanding accounts report
- Revenue summary report

Email Notifications:

- Payment confirmation email
- Payment rejection email
- Overdue reminder email

Notifications System:

- Notification logs stored in database
- Status tracking (PENDING / SENT / FAILED)

---

# Week 4 — Hardening, Testing & Documentation

User Management Module:

- Create users
- Edit users
- Delete users
- Role assignment

Security Improvements:

- Password hashing using password_hash()
- Automatic upgrade from legacy SHA256 passwords

UI Improvements:

- Premium login page
- Sidebar redesign
- Dashboard improvements

Billing Automation:

- Monthly invoice generation
- Overdue invoice tagging

File Upload System:

- GCash receipt upload
- Receipt file storage in `/public/uploads/receipts`

Bug Fixes:

- Login compatibility fix for old and new password hashes
- Receipt upload folder permission fix
- Upload validation improvements

Quality Assurance:

Full system QA completed:

- Customer module
- Plan module
- Subscription module
- Invoice system
- Payment verification flow
- Receipt upload
- Reports
- Role-based access control

---

# Current System Status

Project Completion: **~99%**

Modules Completed:

- Authentication
- Customer Management
- Plan Management
- Subscription Management
- Invoice System
- Payment System
- Email Notifications
- Reports
- User Management
- Dashboard Metrics

System ready for deployment and handover.

