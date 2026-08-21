<?php
declare(strict_types=1);

use function App\Helpers\csrf_field;
use function App\Helpers\e;
use function App\Helpers\url;

$values = [];
foreach (($settings ?? []) as $setting) { $values[(string) $setting['setting_key']] = (string) $setting['setting_value']; }
?>
<section class="admin-shell container">
    <div class="admin-topbar"><div><p class="eyebrow">Configuration</p><h1 class="admin-topbar__heading">Settings</h1></div><a class="btn btn-sm btn-outline-dark" href="<?= e(url('/admin')) ?>">Back to dashboard</a></div>
    <form class="form-panel admin-settings-form" action="<?= e(url('/admin/settings')) ?>" method="post"><h2>Business settings</h2><p class="text-muted">These values help the team keep customer-facing information consistent.</p><?= csrf_field() ?><label>Business name <input name="business_name" maxlength="255" value="<?= e($values['business_name'] ?? '') ?>"></label><label>Business phone <input name="business_phone" maxlength="255" value="<?= e($values['business_phone'] ?? '') ?>"></label><label>Business location <input name="business_location" maxlength="255" value="<?= e($values['business_location'] ?? '') ?>"></label><label>Low-stock alert note <input name="low_stock_alerts" maxlength="255" value="<?= e($values['low_stock_alerts'] ?? '') ?>"></label><button class="btn btn-primary" type="submit">Save settings</button></form>
</section>
