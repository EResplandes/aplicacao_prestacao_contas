<?php

namespace Tests\Feature\Web;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withExceptionHandling();
        config(['app.debug' => false]);

        Route::middleware('web')->get('/_test/errors/400', fn () => abort(400));
        Route::middleware('web')->get('/_test/errors/403', fn () => abort(403));
        Route::middleware('web')->get('/_test/errors/500', function () {
            throw new RuntimeException('Falha controlada para teste.');
        });
    }

    public function test_bad_request_page_uses_custom_error_view(): void
    {
        $this->get('/_test/errors/400')
            ->assertStatus(400)
            ->assertSee('Solicitação inválida')
            ->assertSee('Caixa Pulse');
    }

    public function test_forbidden_page_uses_custom_error_view(): void
    {
        $this->get('/_test/errors/403')
            ->assertStatus(403)
            ->assertSee('Acesso negado')
            ->assertSee('Você não tem permissão');
    }

    public function test_internal_server_error_page_uses_custom_error_view(): void
    {
        $this->get('/_test/errors/500')
            ->assertStatus(500)
            ->assertSee('Erro interno do servidor')
            ->assertSee('Encontramos uma falha inesperada.');
    }
}
