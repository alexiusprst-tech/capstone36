<?php

require __DIR__.'/../../backend/vendor/autoload.php';
$app = require __DIR__.'/../../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (! $app->environment('testing') || config('database.default') !== 'sqlite') {
    throw new RuntimeException('UI fixtures require the isolated SQLite testing environment.');
}

foreach (['MARKETING', 'DIREKTUR', 'ADMINISTRASI', 'WAREHOUSE', 'FINANCE', 'CLIENT', 'SUPPLIER'] as $role) {
    App\Models\User::create([
        'name' => 'UI '.$role,
        'email' => strtolower($role).'@example.test',
        'password' => 'ui-test-password',
        'role' => $role,
    ]);
}

// Explicit live suites keep their real API tokens while navigating with an
// isolated web session that represents the same role and supplier email.
foreach (['T22_LIVE_CONTEXT', 'PO_LIVE_CONTEXT'] as $variable) {
    $contextPath = getenv($variable);
    if (! $contextPath || ! is_file($contextPath)) {
        continue;
    }
    $context = json_decode(file_get_contents($contextPath), true, flags: JSON_THROW_ON_ERROR);
    foreach ($context['users'] ?? [] as $account) {
        $user = $account['user'];
        App\Models\User::updateOrCreate(['email' => $user['email']], [
            'name' => $user['name'], 'role' => $user['role'], 'password' => 'ui-test-password',
        ]);
    }
}
