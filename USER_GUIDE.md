# ISP-BILLING-LITE
## User Guide

This guide explains how to use the ISP-BILLING-LITE Billing Management System.

The system allows administrators and staff to manage customers, internet plans, subscriptions, invoices, and payments.

---

# 1. Login

1. Open the system in a browser:

http://isp-billing-lite.local


2. Enter your:

- Email
- Password

3. Click **Login**.

If credentials are correct, you will be redirected to the **Dashboard**.

---

# 2. Dashboard

The dashboard provides an overview of the billing system.

Information shown:

- Total customers
- Active subscriptions
- Outstanding invoices
- Total verified revenue
- Monthly payment chart
- Recent payments

Use the **left sidebar** to navigate to other modules.

---

# 3. Customers

Customers represent ISP subscribers.

To create a customer:

1. Go to **Customers**
2. Click **Add Customer**
3. Fill in customer details
4. Click **Save**

Customer fields may include:

- Full Name
- Email
- Phone
- Address

You can also:

- Edit customer information
- Delete customers

---

# 4. Plans

Plans represent internet service packages.

To create a plan:

1. Go to **Plans**
2. Click **Add Plan**
3. Enter:

- Plan Name
- Speed
- Price

4. Save the plan.

Plans can also be edited or deleted.

Example:

| Plan | Speed | Price |
|-----|------|------|
| Fiber 50 | 50 Mbps | ₱1500 |
| Fiber 100 | 100 Mbps | ₱2500 |

---

# 5. Subscriptions

Subscriptions connect **customers to plans**.

To assign a plan:

1. Go to **Subscriptions**
2. Click **Add Subscription**
3. Select:

- Customer
- Plan
- Start Date

4. Save.

Subscriptions allow the system to generate invoices.

---

# 6. Invoices

Invoices represent billing records for customers.

To create an invoice:

1. Go to **Invoices**
2. Click **Generate Invoice**
3. Select the customer
4. Enter invoice details
5. Save

Invoices contain:

- Invoice number
- Customer name
- Plan details
- Amount due
- Due date
- Status

Invoice statuses:

- ISSUED
- PAID
- OVERDUE

Invoices can also be downloaded as **PDF files**.

---

# 7. Payments

Payments record customer payments.

To record a payment:

1. Go to **Payments**
2. Click **Record Payment**
3. Select:

- Invoice
- Amount
- Payment date
- Payment method

4. Upload receipt (optional)

Supported receipt formats:

- JPG
- JPEG
- PNG
- WEBP

5. Click **Save Payment**

Payment statuses:

- PENDING
- VERIFIED
- REJECTED

---

# 8. Payment Verification

Administrators verify payments.

In the **Payments** page:

- Click **Verify** to confirm payment.
- Click **Reject** if the payment is invalid.

When verified:

- Payment status becomes **VERIFIED**
- Invoice status becomes **PAID**
- Customer receives email confirmation.

When rejected:

- Payment status becomes **REJECTED**
- Invoice returns to **ISSUED**.

---

# 9. Reports

Reports provide financial insights.

Available reports:

### Outstanding Accounts
Shows unpaid invoices.

### Revenue Summary
Shows:

- Total revenue
- Today's revenue
- Monthly revenue

---

# 10. Users

Administrators can manage system users.

Actions include:

- Create users
- Edit users
- Delete users
- Assign roles

Roles:

- ROLE_ADMIN
- ROLE_STAFF
- ROLE_CUSTOMER

---

# 11. Logout

To logout:

1. Click **Logout** in the sidebar.
2. You will be returned to the login page.

---

# System Tips

- Always verify payments before marking invoices as paid.
- Upload receipts for better payment tracking.
- Keep customer information updated.
- Regularly review outstanding invoices.

---

End of User Guide.
