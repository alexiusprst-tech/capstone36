<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $page = collect(config('pages.routes'))->firstWhere('name', $request->route()->getName());
        abort_unless($page && isset($page['component']), 404);

        return Inertia::render($page['component'], [
            'navigation' => [
                'name' => $page['name'],
                'title' => $page['title'],
                'role' => $page['role'],
                'params' => $request->route()->parameters(),
            ],
        ]);
    }
}
