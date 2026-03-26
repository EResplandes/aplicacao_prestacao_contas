<?php

namespace App\Http\Middleware;

use App\Support\AdminPanel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPanelSectionAccess
{
    public function handle(Request $request, Closure $next, string $section): Response
    {
        abort_unless(
            AdminPanel::canAccessSection($request->user(), $section),
            403,
            'Seu perfil não possui acesso a esta área do painel.',
        );

        return $next($request);
    }
}
