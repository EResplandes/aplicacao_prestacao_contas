<?php

namespace App\Http\Middleware;

use App\Support\AdminPanel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPanelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            AdminPanel::canAccess($request->user()),
            403,
            'Acesso ao painel administrativo não autorizado para este perfil.',
        );

        return $next($request);
    }
}
