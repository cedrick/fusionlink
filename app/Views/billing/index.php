<?php $title = 'Billing List'; ?>

<style>
  .billing-page {
    padding: 32px;
  }

  .billing-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 28px;
    box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
    border: 1px solid #e5e7eb;
  }

  .billing-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
  }

  .billing-header h1 {
    margin: 0;
    font-size: 32px;
    color: #0f172a;
  }

  .billing-subtext {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 14px;
  }

  .billing-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
  }

  .billing-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 18px;
    border-radius: 12px;
    background: #0f172a;
    color: #fff;
    text-decoration: none;
    font-weight: 700;
    font-size: 14px;
  }

  .billing-btn:hover {
    opacity: 0.92;
  }

  .billing-table-wrap {
    overflow-x: auto;
  }

  .billing-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1200px;
  }

  .billing-table thead th {
    background: #f8fafc;
    color: #0f172a;
    font-size: 14px;
    text-align: left;
    padding: 14px 12px;
    border-bottom: 1px solid #e5e7eb;
  }

  .billing-table tbody td {
    padding: 14px 12px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
    color: #111827;
    vertical-align: top;
  }

  .billing-table tbody tr:hover {
    background: #f9fbff;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
  }

  .badge-paid {
    background: #dcfce7;
    color: #166534;
  }

  .badge-issued {
    background: #dbeafe;
    color: #1d4ed8;
  }

  .badge-overdue {
    background: #fee2e2;
    color: #b91c1c;
  }

  .badge-draft {
    background: #e5e7eb;
    color: #374151;
  }

  .badge-pending {
    background: #fef3c7;
    color: #92400e;
  }

  .badge-verified {
    background: #dcfce7;
    color: #166534;
  }

  .badge-rejected {
    background: #fee2e2;
    color: #b91c1c;
  }

  .muted {
    color: #6b7280;
  }

  .amount {
    font-weight: 700;
  }

  .no-records {
    text-align: center;
    padding: 30px 20px;
    color: #6b7280;
    font-weight: 600;
  }

  @media (max-width: 768px) {
    .billing-page {
      padding: 18px;
    }

    .billing-card {
      padding: 18px;
      border-radius: 18px;
    }

    .billing-header h1 {
      font-size: 26px;
    }
  }
</style>

<div class="billing-page">
  <div class="billing-card">
    <div class="billing-header">
      <div>
        <h1>Billing List</h1>
        <p class="billing-subtext">Monitor monthly internet billing, payment status, and advance payments.</p>
      </div>

      <div class="billing-actions">
        <a href="<?= url('/dashboard') ?>" class="billing-btn">Back to Dashboard</a>
        <a href="<?= url('/invoices') ?>" class="billing-btn">View Invoices</a>
      </div>
    </div>

    <div class="billing-table-wrap">
      <table class="billing-table">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Plan</th>
            <th>Speed</th>
            <th>Monthly Fee</th>
            <th>Invoice Amount</th>
            <th>Due Date</th>
            <th>Invoice Status</th>
            <th>Payment Status</th>
            <th>Paid Amount</th>
            <th>Payment Date</th>
            <th>Phone</th>
            <th>Email</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($billings)): ?>
            <?php foreach ($billings as $row): ?>
              <?php
                $invoiceStatus = strtoupper((string)($row['invoice_status'] ?? 'DRAFT'));
                $paymentStatus = strtoupper((string)($row['payment_status'] ?? 'PENDING'));
                $paidAmount    = (float)($row['paid_amount'] ?? 0);
                $invoiceAmount = (float)($row['amount'] ?? 0);
                $monthlyFee    = (float)($row['price'] ?? 0);

                $invoiceBadgeClass = 'badge-draft';
                if ($invoiceStatus === 'PAID') $invoiceBadgeClass = 'badge-paid';
                elseif ($invoiceStatus === 'ISSUED') $invoiceBadgeClass = 'badge-issued';
                elseif ($invoiceStatus === 'OVERDUE') $invoiceBadgeClass = 'badge-overdue';

                $paymentBadgeClass = 'badge-pending';
                if ($paymentStatus === 'VERIFIED') $paymentBadgeClass = 'badge-verified';
                elseif ($paymentStatus === 'REJECTED') $paymentBadgeClass = 'badge-rejected';

                $paymentLabel = $paymentStatus;
                if ($paidAmount > $invoiceAmount && $invoiceAmount > 0) {
                    $paymentLabel = 'ADVANCE PAYMENT';
                    $paymentBadgeClass = 'badge-verified';
                }
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($row['full_name'] ?? '-') ?></strong></td>
                <td><?= htmlspecialchars($row['plan_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['speed'] ?? '-') ?></td>
                <td class="amount">₱<?= number_format($monthlyFee, 2) ?></td>
                <td class="amount">₱<?= number_format($invoiceAmount, 2) ?></td>
                <td><?= htmlspecialchars($row['due_date'] ?? '-') ?></td>
                <td>
                  <span class="badge <?= $invoiceBadgeClass ?>">
                    <?= htmlspecialchars($invoiceStatus ?: 'DRAFT') ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $paymentBadgeClass ?>">
                    <?= htmlspecialchars($paymentLabel ?: 'PENDING') ?>
                  </span>
                </td>
                <td class="amount">₱<?= number_format($paidAmount, 2) ?></td>
                <td><?= htmlspecialchars($row['payment_date'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="12" class="no-records">No billing records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
