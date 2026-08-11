# ISP-BILLING-LITE
## Administrator Guide

This guide explains the responsibilities and features available to **system administrators**.

Administrators manage the entire ISP-BILLING-LITE system including users, customers, billing, payments, and reports.

---

# 1. Administrator Role

Administrators have full access to the system.

Administrator permissions include:

- Manage users
- Manage customers
- Manage plans
- Manage subscriptions
- Generate invoices
- Verify or reject payments
- Access reports
- Configure system modules

Roles available in the system:

- ROLE_ADMIN
- ROLE_STAFF
- ROLE_CUSTOMER

Only **ROLE_ADMIN** users can manage system users.

---

# 2. User Management

Administrators can manage system accounts.

To manage users:

1. Navigate to **Users**.
2. View the list of existing users.

Administrator actions:

- Create new users
- Edit user details
- Change user passwords
- Assign roles
- Delete users

Important rules:

- Administrators **cannot delete their own logged-in account**.
- Passwords are securely stored using hashing.

---

# 3. Customer Management

Administrators maintain ISP customer records.

Customer actions include:

- Create customer records
- Update customer information
- Remove inactive customers

Customer information typically includes:

- Full name
- Email
- Contact details
- Address

Maintaining accurate customer information helps ensure correct billing.

---

# 4. Plan Management

Plans represent the internet service packages offered by the ISP.

Administrators can:

- Create plans
- Modify plan details
- Remove outdated plans

Plan information includes:

- Plan name
- Internet speed
- Monthly price

Example:

| Plan | Speed | Price |
|------|------|------|
| Fiber 50 | 50 Mbps | ₱1500 |
| Fiber 100 | 100 Mbps | ₱2500 |

---

# 5. Subscription Management

Subscriptions connect customers to their selected internet plans.

Administrators can:

- Assign plans to customers
- Update subscriptions
- Remove subscriptions

Subscriptions determine which plan a customer is billed for.

---

# 6. Invoice Management

Invoices represent billing records issued to customers.

Administrators can:

- Generate invoices manually
- View invoice details
- Download invoice PDF
- Delete invoices if necessary

Invoice information includes:

- Customer
- Plan
- Amount due
- Due date
- Status

Invoice statuses:

- ISSUED
- PAID
- OVERDUE

---

# 7. Payment Processing

Payments are submitted for invoices.

Administrators record and manage payments.

Payment process:

1. Customer submits payment.
2. Receipt may be uploaded.
3. Payment status is initially **PENDING**.

Administrator actions:

- Verify payment
- Reject payment

When payment is verified:

- Payment status becomes **VERIFIED**
- Invoice status becomes **PAID**
- Customer receives email confirmation.

When payment is rejected:

- Payment status becomes **REJECTED**
- Invoice status returns to **ISSUED**
- Customer receives rejection notification.

---

# 8. Receipt Upload

Payments may include receipt uploads.

Supported formats:

- JPG
- JPEG
- PNG
- WEBP

Receipts are stored in:



This allows administrators to review payment evidence.

---

# 9. Reports and Monitoring

Administrators can access system reports.

Available reports:

### Outstanding Accounts
Displays unpaid invoices.

### Revenue Summary
Displays:

- Total verified revenue
- Daily revenue
- Monthly revenue

Reports help administrators track financial performance.

---

# 10. Email Notifications

The system automatically sends emails for important events.

Emails include:

- Payment confirmation
- Payment rejection
- Overdue reminders

These notifications help maintain communication with customers.

---

# 11. System Maintenance

Administrators should regularly perform:

- Database backups
- System monitoring
- Verification of payments
- Customer record updates

Regular maintenance ensures the system remains accurate and secure.

---

End of Administrator Guide.
