<?php

if (file_exists(__DIR__ . '/../Services/ActivityLogger.php')) {
    require_once __DIR__ . '/../Services/ActivityLogger.php';
}

if (file_exists(__DIR__ . '/../Services/MailService.php')) {
    require_once __DIR__ . '/../Services/MailService.php';
}

if (file_exists(__DIR__ . '/../Services/PaymentMethodService.php')) {
    require_once __DIR__ . '/../Services/PaymentMethodService.php';
}

if (file_exists(__DIR__ . '/../Services/EmailAlertService.php')) {
    require_once __DIR__ . '/../Services/EmailAlertService.php';
}

if (file_exists(__DIR__ . '/../Services/BillingCycleService.php')) {
    require_once __DIR__ . '/../Services/BillingCycleService.php';
}

class PaymentController
{
    private function db()
    {
        $config = require __DIR__ . '/../../config/database.php';

        $host = $config['host'] ?? '127.0.0.1';
        $db   = $config['db'] ?? ($config['name'] ?? 'isp_billing_lite_db');
        $user = $config['user'] ?? 'root';
        $pass = $config['pass'] ?? '';

        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function redirectTo(string $path): void
    {
        redirect($path);
    }

    private function requireLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
    }

    private function isCustomer(): bool
    {
        return (($_SESSION['user']['role'] ?? '') === 'ROLE_CUSTOMER');
    }

    private function normalizePaymentMethod(string $method): string
    {
        $method = strtoupper(trim($method));

        if (str_starts_with($method, 'BANK')) {
            return 'BANK TRANSFER';
        }

        if (str_starts_with($method, 'GCASH')) {
            return 'GCASH';
        }

        if (in_array($method, ['GCASH', 'BANK TRANSFER', 'CASH'], true)) {
            return $method;
        }

        return 'GCASH';
    }

    private function getLoggedInCustomerId(): int
    {
        return (int)($_SESSION['user']['customer_id'] ?? 0);
    }

    private function setPaymentError(string $message): void
    {
        $_SESSION['payment_error'] = $message;
    }

    private function getPaymentError(): ?string
    {
        $message = $_SESSION['payment_error'] ?? null;
        unset($_SESSION['payment_error']);
        return $message;
    }

    private function getInvoiceRow(PDO $db, int $invoiceId): ?array
    {
        $stmt = $db->prepare("
            SELECT
                invoices.id,
                invoices.customer_id,
                invoices.amount,
                invoices.status,
                invoices.due_date,
                customers.full_name AS customer_name
            FROM invoices
            LEFT JOIN customers ON invoices.customer_id = customers.id
            WHERE invoices.id = ?
            LIMIT 1
        ");
        $stmt->execute([$invoiceId]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function index()
    {
        $this->requireLogin();

        if ($this->isCustomer()) {
            $this->redirectTo('/payments/create');
        }

        $db = $this->db();

        $page = (int)($_GET['page'] ?? 1);
        $search = trim((string)($_GET['search'] ?? ''));
        $statusFilter = strtoupper(trim((string)($_GET['status'] ?? '')));
        $sortBy = trim((string)($_GET['sort_by'] ?? 'id'));
        $sortDir = strtoupper(trim((string)($_GET['sort_dir'] ?? 'DESC')));
        $perPage = 20;

        if ($page < 1) {
            $page = 1;
        }

        $allowedStatus = ['', 'PENDING', 'VERIFIED', 'REJECTED'];
        if (!in_array($statusFilter, $allowedStatus, true)) {
            $statusFilter = '';
        }

        $allowedSort = [
            'id' => 'payments.id',
            'invoice_number' => 'invoices.id',
            'customer_name' => 'customers.full_name',
            'amount' => 'payments.amount',
            'payment_date' => 'payments.payment_date',
            'method' => 'payments.method',
            'status' => 'payments.status',
        ];

        if (!isset($allowedSort[$sortBy])) {
            $sortBy = 'id';
        }

        if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
            $sortDir = 'DESC';
        }

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(
                CAST(payments.id AS CHAR) LIKE :search
                OR CAST(invoices.id AS CHAR) LIKE :search
                OR customers.full_name LIKE :search
                OR CAST(payments.amount AS CHAR) LIKE :search
                OR payments.payment_date LIKE :search
                OR payments.method LIKE :search
                OR payments.status LIKE :search
            )";
            $params[':search'] = '%' . $search . '%';
        }

        if ($statusFilter !== '') {
            $where[] = "payments.status = :status";
            $params[':status'] = $statusFilter;
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        $countSql = "
            SELECT COUNT(*) AS total_rows
            FROM payments
            LEFT JOIN invoices ON payments.invoice_id = invoices.id
            LEFT JOIN customers ON invoices.customer_id = customers.id
            {$whereSql}
        ";
        $countStmt = $db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $countRow = $countStmt->fetch();
        $totalRows = (int)($countRow['total_rows'] ?? 0);
        $totalPages = max(1, (int)ceil($totalRows / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $orderBySql = $allowedSort[$sortBy] . ' ' . $sortDir;

        $sql = "
            SELECT
                payments.*,
                invoices.id AS invoice_number,
                customers.full_name AS customer_name
            FROM payments
            LEFT JOIN invoices ON payments.invoice_id = invoices.id
            LEFT JOIN customers ON invoices.customer_id = customers.id
            {$whereSql}
            ORDER BY {$orderBySql}
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $payments = $stmt->fetchAll();

        View::render('payments/index', [
            'title' => 'Payments',
            'payments' => $payments,
            'page' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ]);
    }

    public function create()
    {
        $this->requireLogin();

        $db = $this->db();
        $paymentError = $this->getPaymentError();

        if (class_exists('BillingCycleService')) {
            BillingCycleService::ensureSchema($db);
        }

        if ($this->isCustomer()) {
            $customerId = $this->getLoggedInCustomerId();

            if ($customerId <= 0) {
                die('Customer account is not linked properly.');
            }

            $stmt = $db->prepare("
                SELECT
                    c.id AS customer_id,
                    c.full_name,
                    c.email,
                    c.phone,
                    c.address,
                    p.name AS plan_name,
                    p.speed,
                    i.id AS invoice_id,
                    i.amount,
                    i.status,
                    i.due_date,
                    i.billing_period_start,
                    i.billing_period_end,
                    i.is_prorated,
                    i.coverage_days,
                    i.created_at
                FROM customers c
                LEFT JOIN subscriptions s
                    ON s.id = (
                        SELECT ss.id
                        FROM subscriptions ss
                        WHERE ss.customer_id = c.id
                        ORDER BY
                            CASE WHEN ss.status = 'ACTIVE' THEN 0 ELSE 1 END,
                            ss.created_at DESC,
                            ss.id DESC
                        LIMIT 1
                    )
                LEFT JOIN plans p ON p.id = s.plan_id
                LEFT JOIN invoices i
                    ON i.id = (
                        SELECT ii.id
                        FROM invoices ii
                        WHERE ii.customer_id = c.id
                          AND ii.status IN ('ISSUED', 'OVERDUE')
                        ORDER BY ii.id DESC
                        LIMIT 1
                    )
                WHERE c.id = ?
                LIMIT 1
            ");
            $stmt->execute([$customerId]);
            $invoice = $stmt->fetch();

            $summary = [
                'balance_due' => 0,
                'unpaid_count' => 0,
                'latest_due_date' => '-',
                'total_paid' => 0
            ];

            $stmt = $db->prepare("
                SELECT
                    COALESCE(SUM(amount), 0) AS balance_due,
                    COUNT(*) AS unpaid_count,
                    MAX(due_date) AS latest_due_date
                FROM invoices
                WHERE customer_id = ?
                  AND status IN ('ISSUED', 'OVERDUE')
            ");
            $stmt->execute([$customerId]);
            $row = $stmt->fetch();

            if ($row) {
                $summary['balance_due'] = (float)($row['balance_due'] ?? 0);
                $summary['unpaid_count'] = (int)($row['unpaid_count'] ?? 0);
                $summary['latest_due_date'] = $row['latest_due_date'] ?: '-';
            }

            $stmt = $db->prepare("
                SELECT COALESCE(SUM(payments.amount), 0) AS total_paid
                FROM payments
                LEFT JOIN invoices ON payments.invoice_id = invoices.id
                WHERE invoices.customer_id = ?
                  AND payments.status = 'VERIFIED'
            ");
            $stmt->execute([$customerId]);
            $row = $stmt->fetch();
            $summary['total_paid'] = (float)($row['total_paid'] ?? 0);

            $stmt = $db->prepare("
                SELECT
                    payments.id,
                    payments.invoice_id,
                    payments.amount,
                    payments.payment_date,
                    payments.method,
                    payments.status,
                    payments.receipt_path,
                    invoices.due_date
                FROM payments
                LEFT JOIN invoices ON payments.invoice_id = invoices.id
                WHERE invoices.customer_id = ?
                ORDER BY payments.id DESC
            ");
            $stmt->execute([$customerId]);
            $history = $stmt->fetchAll();

            $paymentMethods = class_exists('PaymentMethodService')
                ? PaymentMethodService::getAll($db, true)
                : [];

            View::render('payments/customer_portal', [
                'title' => 'My Payment Portal',
                'invoice' => $invoice,
                'summary' => $summary,
                'history' => $history,
                'paymentError' => $paymentError,
                'paymentMethods' => $paymentMethods,
            ]);
            return;
        }

        $stmt = $db->query("
            SELECT
                invoices.id,
                invoices.amount,
                invoices.status,
                customers.full_name AS customer_name
            FROM invoices
            LEFT JOIN customers ON invoices.customer_id = customers.id
            ORDER BY invoices.id DESC
        ");

        $invoices = $stmt->fetchAll();

        $selectedInvoiceId = (int)($_GET['invoice_id'] ?? 0);
        $selectedInvoiceLabel = '';
        $prefillAmount = '';

        if ($selectedInvoiceId > 0) {
            foreach ($invoices as $inv) {
                $invId = (int)($inv['id'] ?? 0);
                if ($invId === $selectedInvoiceId) {
                    $selectedInvoiceLabel = 'Invoice #' . $invId
                        . ' - ' . (string)($inv['customer_name'] ?? '')
                        . ' - Amount: ' . number_format((float)($inv['amount'] ?? 0), 2)
                        . ' - Status: ' . (string)($inv['status'] ?? '');
                    $prefillAmount = (string)((float)($inv['amount'] ?? 0));
                    break;
                }
            }
        }

        View::render('payments/create', [
            'title' => 'Record Payment',
            'invoices' => $invoices,
            'selectedInvoiceId' => $selectedInvoiceId,
            'selectedInvoiceLabel' => $selectedInvoiceLabel,
            'prefillAmount' => $prefillAmount,
            'paymentError' => $paymentError,
        ]);
    }

    public function store()
    {
        $this->requireLogin();

        $db = $this->db();

        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        $submittedAmountRaw = trim((string)($_POST['amount'] ?? ''));
        $paymentDate = trim((string)($_POST['payment_date'] ?? date('Y-m-d')));
        $method = $this->normalizePaymentMethod(trim((string)($_POST['method'] ?? 'GCASH')));
        $status = 'PENDING';
        $receiptPath = null;

        if ($invoiceId <= 0) {
            $this->setPaymentError('Please select a valid invoice.');
            $redirectUrl = '/payments/create';
            if (!$this->isCustomer() && $invoiceId > 0) {
                $redirectUrl .= '?invoice_id=' . $invoiceId;
            }
            $this->redirectTo($redirectUrl);
        }

        if ($submittedAmountRaw === '' || !is_numeric($submittedAmountRaw)) {
            $this->setPaymentError('Amount is required and must be a valid number.');
            $redirectUrl = '/payments/create';
            if (!$this->isCustomer()) {
                $redirectUrl .= '?invoice_id=' . $invoiceId;
            }
            $this->redirectTo($redirectUrl);
        }

        $invoice = $this->getInvoiceRow($db, $invoiceId);

        if (!$invoice) {
            $this->setPaymentError('Invoice not found.');
            $redirectUrl = '/payments/create';
            if (!$this->isCustomer()) {
                $redirectUrl .= '?invoice_id=' . $invoiceId;
            }
            $this->redirectTo($redirectUrl);
        }

        $invoiceAmount = (float)($invoice['amount'] ?? 0);
        $submittedAmount = (float)$submittedAmountRaw;

        if ($invoiceAmount <= 0) {
            $this->setPaymentError('This invoice has an invalid amount.');
            $redirectUrl = '/payments/create';
            if (!$this->isCustomer()) {
                $redirectUrl .= '?invoice_id=' . $invoiceId;
            }
            $this->redirectTo($redirectUrl);
        }

        if ($this->isCustomer()) {
            $customerId = $this->getLoggedInCustomerId();

            if ($customerId <= 0) {
                die('Customer account is not linked properly.');
            }

            if ((int)($invoice['customer_id'] ?? 0) !== $customerId) {
                die('Access denied for this invoice.');
            }

            if (!in_array((string)($invoice['status'] ?? ''), ['ISSUED', 'OVERDUE'], true)) {
                $this->setPaymentError('This invoice is not available for payment.');
                $this->redirectTo('/payments/create');
            }

            $amount = $invoiceAmount;
        } else {
            if ($submittedAmount < $invoiceAmount) {
                $this->setPaymentError(
                    'Payment is insufficient. Required amount is ₱' . number_format($invoiceAmount, 2) . '.'
                );
                $this->redirectTo('/payments/create?invoice_id=' . $invoiceId);
            }

            $amount = $submittedAmount;
        }

        if (isset($_FILES['receipt']) && !empty($_FILES['receipt']['name'])) {
            $uploadError = $_FILES['receipt']['error'] ?? UPLOAD_ERR_NO_FILE;

            if ($uploadError !== UPLOAD_ERR_OK) {
                error_log('Payment receipt upload error code: ' . $uploadError);
                $this->setPaymentError('Receipt upload failed. Please try again.');
                $redirectUrl = $this->isCustomer() ? '/payments/create' : '/payments/create?invoice_id=' . $invoiceId;
                $this->redirectTo($redirectUrl);
            }

            $fileTmp = $_FILES['receipt']['tmp_name'] ?? '';
            $originalName = $_FILES['receipt']['name'] ?? '';

            if ($fileTmp === '' || !is_uploaded_file($fileTmp)) {
                error_log('Payment receipt upload failed: invalid uploaded file.');
                $this->setPaymentError('Invalid uploaded receipt file.');
                $redirectUrl = $this->isCustomer() ? '/payments/create' : '/payments/create?invoice_id=' . $invoiceId;
                $this->redirectTo($redirectUrl);
            }

            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($extension, $allowedExtensions, true)) {
                $this->setPaymentError('Invalid receipt file type. Allowed: jpg, jpeg, png, webp.');
                $redirectUrl = $this->isCustomer() ? '/payments/create' : '/payments/create?invoice_id=' . $invoiceId;
                $this->redirectTo($redirectUrl);
            }

            $uploadDir = __DIR__ . '/../../public/uploads/receipts/';

            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                    error_log('Failed to create upload directory: ' . $uploadDir);
                    $this->setPaymentError('Failed to prepare upload folder.');
                    $redirectUrl = $this->isCustomer() ? '/payments/create' : '/payments/create?invoice_id=' . $invoiceId;
                    $this->redirectTo($redirectUrl);
                }
            }

            if (!is_writable($uploadDir)) {
                error_log('Upload directory is not writable: ' . $uploadDir);
                $this->setPaymentError('Upload folder is not writable.');
                $redirectUrl = $this->isCustomer() ? '/payments/create' : '/payments/create?invoice_id=' . $invoiceId;
                $this->redirectTo($redirectUrl);
            }

            $safeBaseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $fileName = time() . '_' . uniqid() . '_' . $safeBaseName . '.' . $extension;
            $targetFile = $uploadDir . $fileName;

            if (!move_uploaded_file($fileTmp, $targetFile)) {
                error_log('Failed to move uploaded receipt to: ' . $targetFile);
                $this->setPaymentError('Failed to save uploaded receipt.');
                $redirectUrl = $this->isCustomer() ? '/payments/create' : '/payments/create?invoice_id=' . $invoiceId;
                $this->redirectTo($redirectUrl);
            }

            $receiptPath = '/uploads/receipts/' . $fileName;
        }

        $stmt = $db->prepare("
            INSERT INTO payments (invoice_id, amount, payment_date, method, receipt_path, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $invoiceId,
            $amount,
            $paymentDate,
            $method,
            $receiptPath,
            $status
        ]);

        $paymentId = (int)$db->lastInsertId();

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logSession(
                'Payments',
                'CREATE',
                'Recorded payment ID ' . $paymentId . ' for invoice ID ' . $invoiceId . '.'
            );
        }

        if ($this->isCustomer() && class_exists('EmailAlertService')) {
            EmailAlertService::notifyPaymentSubmitted(
                $db,
                (int)($invoice['customer_id'] ?? 0),
                $invoiceId,
                $paymentId,
                $amount,
                $method
            );
        }

        if ($this->isCustomer()) {
            $this->redirectTo('/payments/create');
        }

        $this->redirectTo('/payments');
    }

    public function verify()
    {
        $this->requireLogin();

        if ($this->isCustomer()) {
            $this->redirectTo('/payments/create');
        }

        $db = $this->db();

        $paymentId = $_POST['payment_id'] ?? null;

        if (!$paymentId) {
            die('Payment ID is required.');
        }

        $stmt = $db->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            die('Payment not found.');
        }

        $stmt = $db->prepare("UPDATE payments SET status = 'VERIFIED' WHERE id = ?");
        $stmt->execute([$paymentId]);

        $stmt = $db->prepare("UPDATE invoices SET status = 'PAID' WHERE id = ?");
        $stmt->execute([$payment['invoice_id']]);

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logSession(
                'Payments',
                'VERIFY',
                'Verified payment ID ' . $paymentId . ' for invoice ID ' . $payment['invoice_id'] . '.'
            );
        }

        $stmt = $db->prepare("
            SELECT invoices.id AS invoice_id, invoices.customer_id, customers.email, customers.full_name
            FROM invoices
            LEFT JOIN customers ON invoices.customer_id = customers.id
            WHERE invoices.id = ?
            LIMIT 1
        ");
        $stmt->execute([$payment['invoice_id']]);
        $invoiceData = $stmt->fetch();

        if ($invoiceData) {
            $customerId = (int)($invoiceData['customer_id'] ?? 0);
            $invoiceId = (int)($invoiceData['invoice_id'] ?? 0);
            $recipientEmail = class_exists('EmailAlertService')
                ? EmailAlertService::resolveCustomerEmail($db, $customerId)
                : trim((string)($invoiceData['email'] ?? ''));
            $customerName = class_exists('EmailAlertService')
                ? EmailAlertService::resolveCustomerName($db, $customerId, (string)($invoiceData['full_name'] ?? 'Customer'))
                : ((string)($invoiceData['full_name'] ?? 'Customer'));

            $amountPaid = isset($payment['amount']) ? number_format((float)$payment['amount'], 2) : '0.00';
            $dateVerified = date('F j, Y');

            $subject = 'Payment Processed — Official Receipt';
            $message = 'Hello ' . $customerName . ', your payment for Invoice #' . $invoiceId . ' has been verified. Your official paid invoice/receipt is now available in your billing portal. Your account is up to date.';

            $stmt = $db->prepare("
                INSERT INTO notifications (customer_id, invoice_id, type, recipient_email, subject, message, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $customerId,
                $invoiceId,
                'PAYMENT_CONFIRMED',
                $recipientEmail,
                $subject,
                $message,
                'PENDING'
            ]);

            $notificationId = $db->lastInsertId();

            if (!empty($recipientEmail) && class_exists('MailService')) {
                $mailSent = false;

                try {
                    $mailService = new MailService();

                    $emailBody = '
                        <div style="max-width:700px;margin:0 auto;font-family:Arial,sans-serif;background:#ffffff;border:1px solid #dbe4f0;">
                            <div style="background:#0f3d91;color:#ffffff;padding:20px 24px;text-align:center;">
                                <h1 style="margin:0;font-size:28px;">FUSIONLINK</h1>
                                <p style="margin:8px 0 0 0;font-size:16px;">Payment Confirmation Notice</p>
                            </div>

                            <div style="padding:32px 24px;color:#1f2937;line-height:1.7;">
                                <p style="margin-top:0;">Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>

                                <p>Your payment has been successfully verified.</p>
                                <p>Thank you for your recent payment.</p>

                                <div style="border:1px solid #d1d5db;background:#f8fafc;padding:18px 20px;margin:24px 0;">
                                    <h2 style="margin:0 0 14px 0;font-size:18px;color:#0f172a;">PAYMENT DETAILS</h2>
                                    <p style="margin:6px 0;"><strong>Invoice Number:</strong> #' . htmlspecialchars($invoiceId, ENT_QUOTES, 'UTF-8') . '</p>
                                    <p style="margin:6px 0;"><strong>Payment Status:</strong> VERIFIED</p>
                                    <p style="margin:6px 0;"><strong>Date Verified:</strong> ' . htmlspecialchars($dateVerified, ENT_QUOTES, 'UTF-8') . '</p>
                                    <p style="margin:6px 0;"><strong>Amount Paid:</strong> PHP ' . htmlspecialchars($amountPaid, ENT_QUOTES, 'UTF-8') . '</p>
                                </div>

                                <p>Your account is now up to date and your internet service remains active.</p>

                                <p>If you have any questions, please contact support.</p>

                                <div style="border-top:1px solid #d1d5db;margin-top:28px;padding-top:18px;color:#374151;">
                                    <p style="margin:0 0 8px 0;"><strong>FUSIONLINK Support Team</strong></p>
                                    <p style="margin:4px 0;">Email: support@ispbillinglite.com</p>
                                    <p style="margin:4px 0;">Phone: +63 900 123 4567</p>
                                </div>

                                <p style="margin-top:24px;">Thank you for choosing ISP Billing Lite.</p>
                            </div>
                        </div>
                    ';

                    $bcc = class_exists('EmailAlertService')
                        ? EmailAlertService::administratorBccList($db, $recipientEmail)
                        : [];
                    $mailSent = $mailService->send(
                        $recipientEmail,
                        $customerName,
                        $subject,
                        $emailBody,
                        $bcc
                    );
                } catch (Throwable $e) {
                    error_log('PaymentController mail error: ' . $e->getMessage());
                    $mailSent = false;
                }

                $stmt = $db->prepare("UPDATE notifications SET status = ? WHERE id = ?");
                $stmt->execute([
                    $mailSent ? 'SENT' : 'FAILED',
                    $notificationId
                ]);
            }
        }

        $this->redirectTo('/payments');
    }

    public function reject()
    {
        $this->requireLogin();

        if ($this->isCustomer()) {
            $this->redirectTo('/payments/create');
        }

        $db = $this->db();

        $paymentId = $_POST['payment_id'] ?? null;

        if (!$paymentId) {
            die('Payment ID is required.');
        }

        $stmt = $db->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            die('Payment not found.');
        }

        $stmt = $db->prepare("UPDATE payments SET status = 'REJECTED' WHERE id = ?");
        $stmt->execute([$paymentId]);

        $stmt = $db->prepare("UPDATE invoices SET status = 'ISSUED' WHERE id = ?");
        $stmt->execute([$payment['invoice_id']]);

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logSession(
                'Payments',
                'REJECT',
                'Rejected payment ID ' . $paymentId . ' for invoice ID ' . $payment['invoice_id'] . '.'
            );
        }

        $stmt = $db->prepare("
            SELECT invoices.id AS invoice_id, invoices.customer_id, customers.email, customers.full_name
            FROM invoices
            LEFT JOIN customers ON invoices.customer_id = customers.id
            WHERE invoices.id = ?
            LIMIT 1
        ");
        $stmt->execute([$payment['invoice_id']]);
        $invoiceData = $stmt->fetch();

        if ($invoiceData) {
            $customerId = (int)($invoiceData['customer_id'] ?? 0);
            $invoiceId = (int)($invoiceData['invoice_id'] ?? 0);
            $recipientEmail = class_exists('EmailAlertService')
                ? EmailAlertService::resolveCustomerEmail($db, $customerId)
                : trim((string)($invoiceData['email'] ?? ''));
            $customerName = class_exists('EmailAlertService')
                ? EmailAlertService::resolveCustomerName($db, $customerId, (string)($invoiceData['full_name'] ?? 'Customer'))
                : ((string)($invoiceData['full_name'] ?? 'Customer'));

            $dateReviewed = date('F j, Y');

            $subject = 'Payment Unprocessed';
            $message = 'Hello ' . $customerName . ', unfortunately your payment for Invoice #' . $invoiceId . ' could not be verified. Please check your payment receipt and submit again.';

            $stmt = $db->prepare("
                INSERT INTO notifications (customer_id, invoice_id, type, recipient_email, subject, message, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $customerId,
                $invoiceId,
                'PAYMENT_REJECTED',
                $recipientEmail,
                $subject,
                $message,
                'PENDING'
            ]);

            $notificationId = $db->lastInsertId();

            if (!empty($recipientEmail) && class_exists('MailService')) {
                $mailSent = false;

                try {
                    $mailService = new MailService();

                    $emailBody = '
                        <div style="max-width:700px;margin:0 auto;font-family:Arial,sans-serif;background:#ffffff;border:1px solid #dbe4f0;">
                            <div style="background:#0f3d91;color:#ffffff;padding:20px 24px;text-align:center;">
                                <h1 style="margin:0;font-size:28px;">ISP BILLING LITE</h1>
                                <p style="margin:8px 0 0 0;font-size:16px;">Payment Verification Failed</p>
                            </div>

                            <div style="padding:32px 24px;color:#1f2937;line-height:1.7;">
                                <p style="margin-top:0;">Hello ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>

                                <p>Unfortunately, your payment verification was not successful.</p>

                                <div style="border:1px solid #d1d5db;background:#f8fafc;padding:18px 20px;margin:24px 0;">
                                    <h2 style="margin:0 0 14px 0;font-size:18px;color:#0f172a;">PAYMENT DETAILS</h2>
                                    <p style="margin:6px 0;"><strong>Invoice Number:</strong> #' . htmlspecialchars($invoiceId, ENT_QUOTES, 'UTF-8') . '</p>
                                    <p style="margin:6px 0;"><strong>Payment Status:</strong> REJECTED</p>
                                    <p style="margin:6px 0;"><strong>Date Reviewed:</strong> ' . htmlspecialchars($dateReviewed, ENT_QUOTES, 'UTF-8') . '</p>
                                </div>

                                <p><strong>Possible reasons:</strong></p>
                                <ul style="padding-left:20px;margin-top:8px;">
                                    <li>The receipt image is unclear</li>
                                    <li>Payment details do not match the invoice</li>
                                </ul>

                                <p>Please submit your payment again or contact customer support for assistance.</p>

                                <div style="border-top:1px solid #d1d5db;margin-top:28px;padding-top:18px;color:#374151;">
                                    <p style="margin:0 0 8px 0;"><strong>ISP Billing Lite Support Team</strong></p>
                                    <p style="margin:4px 0;">Email: support@ispbillinglite.com</p>
                                    <p style="margin:4px 0;">Phone: +63 900 123 4567</p>
                                </div>

                                <p style="margin-top:24px;">Thank you for choosing FUSIONLINK.</p>
                            </div>
                        </div>
                    ';

                    $bcc = class_exists('EmailAlertService')
                        ? EmailAlertService::administratorBccList($db, $recipientEmail)
                        : [];
                    $mailSent = $mailService->send(
                        $recipientEmail,
                        $customerName,
                        $subject,
                        $emailBody,
                        $bcc
                    );
                } catch (Throwable $e) {
                    error_log('PaymentController reject mail error: ' . $e->getMessage());
                    $mailSent = false;
                }

                $stmt = $db->prepare("UPDATE notifications SET status = ? WHERE id = ?");
                $stmt->execute([
                    $mailSent ? 'SENT' : 'FAILED',
                    $notificationId
                ]);
            }
        }

        $this->redirectTo('/payments');
    }

    public function revenueReport()
    {
        $this->requireLogin();

        if ($this->isCustomer()) {
            $this->redirectTo('/payments/create');
        }

        $db = $this->db();

        $startDate = trim((string)($_GET['start_date'] ?? ''));
        $endDate = trim((string)($_GET['end_date'] ?? ''));
        $sortStatus = strtoupper(trim((string)($_GET['sort_status'] ?? '')));
        $search = trim((string)($_GET['search'] ?? ''));
        $page = (int)($_GET['page'] ?? 1);
        $export = trim((string)($_GET['export'] ?? ''));

        if ($page < 1) {
            $page = 1;
        }

        if ($startDate === '') {
            $startDate = date('Y-m-01');
        }

        if ($endDate === '') {
            $endDate = date('Y-m-d');
        }

        if ($startDate > $endDate) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        $allowedSort = ['', 'PAID', 'UNPAID', 'OVERDUE', 'ISSUED'];
        if (!in_array($sortStatus, $allowedSort, true)) {
            $sortStatus = '';
        }

        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $dateFrom = $startDate . ' 00:00:00';
        $dateTo = $endDate . ' 23:59:59';

        $kpi = [
            'overall_payment' => 0,
            'paid_customers_total_payment' => 0,
            'unpaid_customers_total_payment' => 0,
        ];

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(payments.amount), 0) AS overall_payment
            FROM payments
            WHERE payments.status = 'VERIFIED'
              AND payments.payment_date BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $row = $stmt->fetch();
        $kpi['overall_payment'] = (float)($row['overall_payment'] ?? 0);

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(invoices.amount), 0) AS paid_customers_total_payment
            FROM invoices
            WHERE invoices.status = 'PAID'
              AND invoices.created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $row = $stmt->fetch();
        $kpi['paid_customers_total_payment'] = (float)($row['paid_customers_total_payment'] ?? 0);

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(invoices.amount), 0) AS unpaid_customers_total_payment
            FROM invoices
            WHERE invoices.status IN ('ISSUED', 'OVERDUE')
              AND invoices.created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $row = $stmt->fetch();
        $kpi['unpaid_customers_total_payment'] = (float)($row['unpaid_customers_total_payment'] ?? 0);

        $whereSql = " WHERE invoices.created_at BETWEEN ? AND ? ";
        $params = [$dateFrom, $dateTo];

        if ($sortStatus === 'UNPAID') {
            $whereSql .= " AND invoices.status IN ('ISSUED', 'OVERDUE') ";
        } elseif (in_array($sortStatus, ['PAID', 'ISSUED', 'OVERDUE'], true)) {
            $whereSql .= " AND invoices.status = ? ";
            $params[] = $sortStatus;
        }

        if ($search !== '') {
            $whereSql .= "
                AND (
                    CAST(invoices.id AS CHAR) LIKE ?
                    OR customers.full_name LIKE ?
                    OR CAST(invoices.amount AS CHAR) LIKE ?
                    OR invoices.status LIKE ?
                    OR invoices.due_date LIKE ?
                    OR invoices.created_at LIKE ?
                )
            ";
            $searchLike = '%' . $search . '%';
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $countSql = "
            SELECT COUNT(*) AS total_rows
            FROM invoices
            LEFT JOIN customers ON invoices.customer_id = customers.id
            {$whereSql}
        ";
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        $totalRows = (int)($row['total_rows'] ?? 0);
        $totalPages = max(1, (int)ceil($totalRows / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        if ($export === 'csv') {
            $exportSql = "
                SELECT
                    invoices.id,
                    customers.full_name AS customer_name,
                    invoices.amount,
                    invoices.due_date,
                    invoices.status,
                    invoices.created_at
                FROM invoices
                LEFT JOIN customers ON invoices.customer_id = customers.id
                {$whereSql}
                ORDER BY invoices.id DESC
            ";
            $stmt = $db->prepare($exportSql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=reports-' . $startDate . '-to-' . $endDate . '.csv');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Invoice #', 'Customer', 'Amount', 'Due Date', 'Status', 'Created At']);

            foreach ($rows as $r) {
                fputcsv($output, [
                    $r['id'] ?? '',
                    $r['customer_name'] ?? '',
                    $r['amount'] ?? '',
                    $r['due_date'] ?? '',
                    $r['status'] ?? '',
                    $r['created_at'] ?? '',
                ]);
            }

            fclose($output);
            exit;
        }

        $listSql = "
            SELECT
                invoices.id,
                customers.full_name AS customer_name,
                invoices.amount,
                invoices.due_date,
                invoices.status,
                invoices.created_at
            FROM invoices
            LEFT JOIN customers ON invoices.customer_id = customers.id
            {$whereSql}
            ORDER BY invoices.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $db->prepare($listSql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        View::render('reports/revenue', [
            'title' => 'Reports',
            'kpi' => $kpi,
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sortStatus' => $sortStatus,
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
        ]);
    }
}
