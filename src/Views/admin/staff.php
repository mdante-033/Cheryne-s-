<?php
declare(strict_types=1);

use function App\Helpers\csrf_field;
use function App\Helpers\e;
use function App\Helpers\money;
use function App\Helpers\url;

$workers = $workers ?? [];
$attendance = $attendance ?? [];
$payments = $payments ?? [];
?>
<section class="admin-shell container">
    <div class="admin-topbar">
        <div><p class="eyebrow">People operations</p><h1 class="admin-topbar__heading">Staff, Attendance &amp; Payroll</h1></div>
        <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/admin')) ?>">Back to dashboard</a>
    </div>

    <div class="admin-two-col">
        <form class="form-panel" action="<?= e(url('/admin/staff')) ?>" method="post">
            <h2>Add worker</h2><?= csrf_field() ?>
            <label>Name <input name="name" maxlength="120" required></label>
            <label>Role <input name="role" maxlength="80" placeholder="e.g. Cook" required></label>
            <label>Phone <input name="phone" maxlength="30"></label>
            <label>Pay rate <input type="number" name="pay_rate" min="0" step="0.01" required></label>
            <button class="btn btn-primary" type="submit">Add worker</button>
        </form>
        <section class="admin-panel"><h2>Workers</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Role</th><th>Phone</th><th>Pay rate</th><th>Status</th></tr></thead><tbody>
            <?php if (!$workers): ?><tr><td colspan="5">No workers added yet.</td></tr><?php endif; ?>
            <?php foreach ($workers as $worker): ?><tr><td><?= e($worker['name']) ?></td><td><?= e($worker['role']) ?></td><td><?= e($worker['phone'] ?? '') ?></td><td><?= e(money($worker['pay_rate'])) ?></td><td><?= filter_var($worker['active'], FILTER_VALIDATE_BOOLEAN) ? 'Active' : 'Inactive' ?></td></tr><?php endforeach; ?>
        </tbody></table></div></section>
    </div>

    <div class="admin-two-col">
        <form class="form-panel" action="<?= e(url('/admin/staff/attendance')) ?>" method="post">
            <h2>Record attendance</h2><?= csrf_field() ?>
            <label>Worker <select name="staff_id" required><option value="">Select worker</option><?php foreach ($workers as $worker): ?><option value="<?= e($worker['id']) ?>"><?= e($worker['name']) ?></option><?php endforeach; ?></select></label>
            <label>Date <input type="date" name="work_date" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label>Status <select name="status" required><option value="present">Present</option><option value="absent">Absent</option><option value="leave">Leave</option></select></label>
            <label>Notes <textarea name="notes" maxlength="500" rows="3"></textarea></label>
            <button class="btn btn-primary" type="submit">Save attendance</button>
        </form>
        <section class="admin-panel"><h2>Recent attendance</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Date</th><th>Worker</th><th>Status</th><th>Notes</th></tr></thead><tbody>
            <?php if (!$attendance): ?><tr><td colspan="4">No attendance records yet.</td></tr><?php endif; ?>
            <?php foreach ($attendance as $entry): ?><tr><td><?= e($entry['work_date']) ?></td><td><?= e($entry['name']) ?></td><td><?= e(ucfirst($entry['status'])) ?></td><td><?= e($entry['notes'] ?? '') ?></td></tr><?php endforeach; ?>
        </tbody></table></div></section>
    </div>

    <div class="admin-two-col">
        <form class="form-panel" action="<?= e(url('/admin/staff/payments')) ?>" method="post">
            <h2>Record worker payment</h2><p class="text-muted">This records a payment. Complete the actual transfer through the selected provider.</p><?= csrf_field() ?>
            <label>Worker <select name="staff_id" required><option value="">Select worker</option><?php foreach ($workers as $worker): ?><option value="<?= e($worker['id']) ?>"><?= e($worker['name']) ?></option><?php endforeach; ?></select></label>
            <label>Amount <input type="number" name="amount" min="0.01" step="0.01" required></label>
            <label>Paid on <input type="date" name="paid_on" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label>Pay period start <input type="date" name="period_start" required></label>
            <label>Pay period end <input type="date" name="period_end" required></label>
            <label>Method <select name="method" required><option value="mpesa">M-Pesa</option><option value="bank">Bank</option><option value="cash">Cash</option></select></label>
            <label>Reference <input name="reference" maxlength="160"></label>
            <label>Notes <textarea name="notes" maxlength="500" rows="3"></textarea></label>
            <button class="btn btn-primary" type="submit">Record payment</button>
        </form>
        <section class="admin-panel"><h2>Payment history</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Paid on</th><th>Worker</th><th>Amount</th><th>Period</th><th>Method</th><th>Reference</th></tr></thead><tbody>
            <?php if (!$payments): ?><tr><td colspan="6">No worker payments recorded yet.</td></tr><?php endif; ?>
            <?php foreach ($payments as $payment): ?><tr><td><?= e($payment['paid_on']) ?></td><td><?= e($payment['name']) ?></td><td><?= e(money($payment['amount'])) ?></td><td><?= e($payment['period_start']) ?> to <?= e($payment['period_end']) ?></td><td><?= e(strtoupper($payment['method'])) ?></td><td><?= e($payment['reference'] ?? '') ?></td></tr><?php endforeach; ?>
        </tbody></table></div></section>
    </div>
</section>
