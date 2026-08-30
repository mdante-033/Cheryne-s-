<?php declare(strict_types=1);

use function App\Helpers\csrf_field;
use function App\Helpers\e;
use function App\Helpers\url;

$token = $token ?? (string) ($_GET['token'] ?? '');
?>

<section class="container py-4" style="max-width: 450px;">
    <div class="form-panel p-3">
        <h1 class="text-center mb-2">Create New Password</h1>
        <form action="<?=  e(url('/auth/reset-password')) ?>" method="POST">
           <?= csrf_field() ?>
           <input type="hidden" name="token" value="<?= e($token) ?>">

           <div class="mb-2">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" class="w-100" style="min-height:2.5rem; padding:0.5rem; border:1px solid var(--line); border-radius:8px;" required>
           </div>

           <button type="submit" class="btn btn-primary w-100">Update Password</button>
        </form> 
    </div>
</section>