<?php
declare(strict_types=1);

use function App\Helpers\csrf_field;
use function App\Helpers\e;
use function App\Helpers\url;

/** @var array<string, string> $errors */
$errors = $errors ?? [];

/** @var string $oldEmail */
$oldEmail = $oldEmail ?? '';
?>

<section class="auth-section container py-4">
    <div class="auth-card bg-white border-bottom" style="max-width: 450px; margin: 2rem auto; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        
        <!-- Brand Presentation -->
        <div class="text-center mb-2">
            <img src="<?= e(url('/images/logo.png')) ?>" alt="Cheryne's Hotel Logo" style="height: 60px; width: auto; margin-bottom: 1rem;">
            <h1 style="font-size: 1.75rem; color: var(--ink, #101820); margin-bottom: 0.5rem;">Reset Password</h1>
            <p class="text-muted mb-0">Enter your email address to receive a secure recovery link.</p>
        </div>

        <!-- Password Recovery Form -->
        <form action="<?= e(url('/auth/forgot-password')) ?>" method="POST" style="margin-top: 1.5rem;">
            
            <!-- Essential Anti-Hijacking Security Baseline Token -->
            <?= csrf_field() ?>

            <!-- Email Input Field Group -->
            <div class="form-group mb-2" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <label for="email" style="font-weight: 600; color: var(--ink, #101820);">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?= e($oldEmail) ?>" 
                    placeholder="name@example.com"
                    required 
                    style="width: 100%; min-height: 2.5rem; padding: 0.5rem 0.75rem; border: 1px solid <?= isset($errors['email']) ? '#842029' : 'var(--line, #dbe4e0)' ?>; border-radius: 8px; font: inherit; box-sizing: border-box;"
                    aria-describedby="<?= isset($errors['email']) ? 'email-error' : '' ?>"
                >
                
                <!-- Targeted Field Error Rendering -->
                <?php if (isset($errors['email'])): ?>
                    <small id="email-error" style="color: #842029; font-weight: 500; font-size: 0.875rem;">
                        <?= e($errors['email']) ?>
                    </small>
                <?php endif; ?>
            </div>

            <!-- Operational Form Submission Trigger -->
            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary w-100">
                    Send Recovery Link
                </button>
            </div>
        </form>

        <!-- Auxiliary Back Navigation Gateway -->
        <div class="text-center" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--line, #dbe4e0);">
            <p class="mb-0" style="font-size: 0.95rem;">
                Remember your password? 
                <a href="<?= e(url('/auth/login')) ?>" style="color: var(--teal, #0f766e); font-weight: 600; text-decoration: none;">
                    Back to Login
                </a>
            </p>
        </div>
        
    </div>
</section>
