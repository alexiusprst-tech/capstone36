<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\SessionController;
use App\Http\Middleware\EnsurePageRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (Request $request) => redirect(
    config('pages.homes.'.$request->user()?->role, '/login'),
));

Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:10,1');
Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth');

Route::get('/templates/{filename}', function (string $filename) {
    $cleanFilename = basename($filename);
    $paths = [
        public_path('templates/'.$cleanFilename),
        public_path('templates/data_barang_template.xlsx'),
        public_path('templates/data_barang_template (2).xlsx'),
        base_path('../data_barang_template (2).xlsx'),
        base_path('../data_barang_template.xlsx'),
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return response()->download($path, 'data_barang_template.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }
    }

    abort(404, 'Berkas template data barang tidak ditemukan.');
})->where('filename', '.*');

foreach (config('pages.routes') as $page) {
    if (isset($page['redirect'])) {
        Route::redirect($page['path'], $page['redirect']);

        continue;
    }

    $route = Route::get($page['path'], PageController::class)->name($page['name']);
    if ($page['role']) {
        $route->middleware(['auth', EnsurePageRole::class.':'.$page['role']]);
    }
}
