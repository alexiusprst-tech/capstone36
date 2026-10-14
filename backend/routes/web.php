<?php

use Illuminate\Support\Facades\Route;

/**
 * Serve the compiled SPA for CV Alpharo Artha Jaya
 */
if (! function_exists('serveSpa')) {
    function serveSpa()
    {
        $spaPath = public_path('index.html');
        if (file_exists($spaPath)) {
            return response()->file($spaPath, [
                'Content-Type' => 'text/html; charset=utf-8',
            ]);
        }

        return response('Frontend belum di-build. Silakan jalankan `npm run build` di direktori frontend.', 404);
    }
}

// Root entry point
Route::get('/', function () {
    return serveSpa();
});

// SPA catch-all for Vue Router (excludes API and Boost routes)
Route::get('/{any}', function () {
    return serveSpa();
})->where('any', '^(?!api|_boost).*$');
