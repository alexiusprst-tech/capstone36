<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccess
{
    private const INTERNAL = ['MARKETING', 'WAREHOUSE', 'FINANCE', 'ADMINISTRASI', 'DIREKTUR'];

    public function handle(Request $request, Closure $next): Response
    {
        $role = strtoupper((string) $request->user()?->role);
        $read = in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        $module = $request->segment(2);
        $roles = match ($module) {
            'customers' => $read ? self::INTERNAL : ['MARKETING'],
            'products' => $read ? self::INTERNAL : ['WAREHOUSE'],
            'inventory' => $read ? self::INTERNAL : [],
            'suppliers' => $read ? [...self::INTERNAL, 'SUPPLIER'] : ['FINANCE'],
            'warehouse' => $read ? self::INTERNAL : ['WAREHOUSE'],
            'purchases' => $read ? [...self::INTERNAL, 'SUPPLIER'] : (
                $request->is('api/purchases/*/receive') ? ['WAREHOUSE'] : (
                    $request->is('api/purchases/*/supplier-progress') ? ['WAREHOUSE', 'SUPPLIER', 'FINANCE'] : ['FINANCE']
                )
            ),
            'spj' => $read ? self::INTERNAL : ['ADMINISTRASI'],
            'document-templates' => $read ? self::INTERNAL : ['ADMINISTRASI'],
            'documents' => $read ? self::INTERNAL : ['ADMINISTRASI', 'MARKETING', 'FINANCE'],
            'orders' => $request->is('api/orders/*/invoice/delivery') ? ['FINANCE'] : null,
            // These controllers enforce their more specific permissions themselves.
            'auth', 'admin' => null,
            default => [],
        };
        abort_if($roles !== null && ! in_array($role, $roles, true), 403, 'Peran Anda tidak berhak melakukan tindakan ini.');

        return $next($request);
    }
}
