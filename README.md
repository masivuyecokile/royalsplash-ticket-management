# Royal Splash Ticketing System

Royal Splash Ticketing System is a custom PHP/MySQL digital ticketing platform built for managing event ticket sales, QR-coded tickets, PDF tickets, email delivery, ticket scanning, customer accounts, staff scanner accounts, and secure ticket transfers.

The system is designed for the Royal Splash event but can be extended for future events.

---

## Project Status

Current working features:

* Public event listing
* Public event detail page
* Ticket category selection
* Checkout flow
* Duplicate purchase warning
* PayFast payment integration
* PayFast ITN payment verification
* Automatic customer account creation after payment
* PDF ticket generation
* QR code generation
* Ticket email delivery
* Customer “My Tickets” dashboard
* QR scanner dashboard for staff
* Admin dashboard
* Event management
* Ticket category management
* Order records
* Ticket records
* Scanner user management
* Scan reports
* Ticket transfer by email
* Pending transfer status
* Cancel pending transfer
* Fresh QR token after transfer acceptance

---

## Tech Stack

* PHP 8+
* MySQL / MariaDB
* Composer
* PDO
* PHPMailer
* Dompdf
* Endroid QR Code
* vlucas/phpdotenv
* PayFast
* Bootstrap-style custom frontend
* SweetAlert2

---

## Recommended Server Setup

The app is designed to run directly from the project root.


The project structure should look like this:

```txt
royal-splash/
├── index.php
├── .htaccess
├── .env
├── app/
├── assets/
├── config/
├── storage/
├── uploads/
├── vendor/
├── composer.json
└── composer.lock
```

---

## Main Features

### 1. Public Event Page

Users can view available events and ticket types.

The event page supports:

* Event image
* Event date and time
* Venue details
* Ticket categories
* Ticket quantities
* Checkout summary
* Maximum ticket limit per order

---

### 2. Checkout

Users select tickets and proceed to checkout.

The checkout captures:

* Buyer full name
* Buyer email
* Buyer phone number
* Selected tickets
* Total amount

The system includes duplicate purchase protection.

If the same buyer email has already purchased paid tickets for the same event recently, the system shows:

```txt
You already purchased tickets for this event recently. Are you sure you want to buy more?
```

The buyer can still continue after confirming.

---

### 3. PayFast Payment

The system uses PayFast for payment processing.

Supported PayFast flow:

* Create order as pending
* Redirect buyer to PayFast
* Receive ITN callback
* Validate PayFast signature
* Validate merchant ID
* Validate payment amount
* Validate ITN with PayFast
* Mark order as paid
* Generate tickets
* Email tickets to buyer

---

### 4. Ticket Generation

After successful payment, the system automatically generates one ticket per purchased quantity.

Each ticket includes:

* Ticket number
* QR token
* Event name
* Ticket type
* Buyer name
* Buyer email
* QR code
* PDF file

---

### 5. Email Delivery

After ticket generation, the system sends the buyer an email with ticket PDF attachments.

SMTP is configured through `.env`.

---

### 6. Customer Accounts

When a buyer completes payment, the system checks whether a customer account exists for the buyer email.

If the account does not exist, the system creates one automatically and sends a password setup email.

Customers can log in and access:

* My Tickets
* Ticket QR page
* PDF downloads
* Ticket transfer options

---

### 7. Scanner Dashboard

Scanner users can log into the scanner dashboard and validate tickets at the gate.

Scanner behaviour:

* Valid unused ticket: marked as used
* Already used ticket: warning
* Cancelled ticket: blocked
* Invalid QR code: blocked

The system records:

* Scan time
* Scanner user
* Ticket status

---

### 8. Admin Dashboard

Admins can manage:

* Events
* Ticket categories
* Orders
* Tickets
* Scanner users
* Scan reports

---

### 9. Ticket Transfer

Customers can transfer a ticket to another person.

Transfer flow:

```txt
Customer A opens My Tickets
Customer A clicks Transfer Ticket
Customer A enters recipient name and email
Recipient receives email invitation
Recipient accepts transfer
System creates account if recipient does not exist
Ticket moves to recipient account
Ticket disappears from original owner
Old QR token is replaced with a new QR token
```

This protects the event because old downloaded PDFs or old QR codes become invalid after transfer acceptance.

---

### 10. Pending Transfer Management

Original ticket owner can see when a ticket has a pending transfer.

They can cancel the pending transfer before the recipient accepts.

---

## Installation

### 1. Clone Repository

```bash
git clone https://github.com/YOUR-USERNAME/royalsplash-ticket-management.git
cd royal-splash
```

---

### 2. Install Composer Dependencies

```bash
composer install
```

Required packages:

```bash
composer require vlucas/phpdotenv
composer require endroid/qr-code
composer require dompdf/dompdf
composer require phpmailer/phpmailer
```

---

### 3. Create `.env`

Copy `.env.example` to `.env`.

```bash
cp .env.example .env
```

Update the values for your environment.

---

### 4. Create Database

Create a MySQL database.

Example:

```sql
CREATE DATABASE royal_splash
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Import your SQL schema using phpMyAdmin or MySQL CLI.

Recommended schema file location:

```txt
database/schema.sql
```

---

### 5. Configure Apache

The project uses `.htaccess` URL rewriting.

Apache must have `mod_rewrite` enabled.

For staging under `/royal-splash/`, use:

```apache
RewriteBase /royal-splash/
```

For live root domain, use:

```apache
RewriteBase /
```

---

### 6. File Permissions

Make sure these folders are writable:

```txt
storage/
storage/tickets/
uploads/
```

Recommended permissions:

```bash
chmod -R 775 storage
chmod -R 775 uploads
```

---

## Environment Variables

Main `.env` values:

```env
APP_NAME="Royal Splash"
APP_URL=https://your-domain.co.za
APP_ENV=production

DB_HOST=localhost
DB_NAME=royal_splash
DB_USER=your_db_user
DB_PASS=your_db_password
DB_CHARSET=utf8mb4

PAYFAST_MODE=live

MAIL_HOST=mail.your-domain.co.za
MAIL_PORT=587
MAIL_USERNAME=tickets@your-domain.co.za
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tickets@your-domain.co.za
MAIL_FROM_NAME="Royal Splash"
MAIL_REPLY_TO=tickets@your-domain.co.za
```

---

## PayFast Configuration

### Sandbox Mode

Use sandbox while testing.

```env
PAYFAST_MODE=sandbox
```

### Live Mode

Before going live, update:

```env
PAYFAST_MODE=live
```

Make sure live merchant details are configured.

---

## Email Configuration

The system uses SMTP.

Recommended sender:

```txt
tickets@your-domain.co.za
```

Make sure the domain has:

* SPF
* DKIM
* DMARC

This improves email delivery and reduces spam risk.

---

## Admin User Setup

Generate a password hash:

```bash
php -r "echo password_hash('ChangeThisPassword123!', PASSWORD_DEFAULT) . PHP_EOL;"
```

Insert admin user:

```sql
INSERT INTO users (
    full_name,
    email,
    phone,
    password,
    role,
    status
) VALUES (
    'Admin User',
    'admin@example.com',
    '',
    'PASTE_HASH_HERE',
    'admin',
    'active'
);
```

Then log in using:

```txt
/admin
```

---

## User Roles

The system supports three roles:

```txt
admin
customer
scanner
```

### Admin

Can manage the system.

### Customer

Can buy tickets, view tickets, download PDFs, and transfer tickets.

### Scanner

Can scan and validate tickets at the gate.

---

## Important Security Notes


The `.env` file contains private credentials and must stay on the server only.




### PayFast

* Redirect to PayFast works
* Sandbox payment works
* ITN callback is received
* Order becomes paid
* PayFast logs are stored

### Tickets

* Tickets are generated
* PDFs are generated
* QR codes work
* Ticket email is sent
* Ticket appears under My Tickets

### Scanner

* Valid ticket scans successfully
* Used ticket shows warning
* Cancelled ticket is blocked
* Invalid QR code is blocked

### Ticket Transfer

* Transfer email is sent
* Recipient accepts transfer
* New account is created if needed
* Ticket moves to recipient
* Original owner loses ticket
* Old QR becomes invalid
* Pending transfer can be cancelled

### Admin

* Admin can view orders
* Admin can view tickets
* Admin can manage events
* Admin can manage ticket categories
* Admin can create scanner users
* Admin can view scan reports

---

## Go-Live Checklist

Before switching to production:

```txt
1. Set APP_ENV=production
2. Set APP_URL to the live domain
3. Set PAYFAST_MODE=live
4. Add PayFast live merchant credentials
5. Confirm PayFast notify URL is reachable
6. Confirm SMTP works
7. Confirm SPF, DKIM and DMARC
8. Remove test orders and tickets
9. Confirm admin password is strong
10. Confirm scanner users are created
11. Confirm event status is published/on_sale
12. Confirm ticket quantities are correct
13. Confirm backups are enabled
14. Confirm storage folders are writable
15. Confirm .env is not public
```

---

## Common Issues

### 1. Blank page

Check PHP error logs and confirm Composer dependencies are installed.

```bash
composer install
```

### 2. Routes not working

Make sure Apache rewrite is enabled and `.htaccess` has the correct `RewriteBase`.

### 3. Emails not sending

Check SMTP details in `.env`.

Try port 587 with TLS first.

If that fails, try:

```env
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

### 4. PayFast payment not updating order

Check:

* Notify URL
* PayFast logs table
* Merchant ID
* Passphrase
* Signature validation
* Server can receive POST callbacks

### 5. Tickets not appearing

Confirm order is marked as:

```txt
paid
```

Tickets are only generated after successful payment confirmation.



## License

Private project.

All rights reserved unless the owner chooses to release under an open-source license.

---

## Developed For

Royal Splash

---

## Developed By

Masivuye Cokile
Simple Perfect Solutions
