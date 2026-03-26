<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Caixa Pulse Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <div class="page-shell">
        <div class="content-wrap">
            <div class="login-stage">
                <section class="info-panel">
                    <div class="brand">
                        <span class="brand-kicker">Sistema</span>
                        <h1 class="brand-title">Caixa Pulse</h1>
                        <p class="brand-subtitle">Acesso operacional e controle central do ciclo de solicitação, aprovação, liberação, gastos e prestação de contas.</p>
                    </div>

                    <div class="journey-list">
                        <div class="journey-item">
                            <div class="journey-id">01</div>
                            <div class="journey-copy">
                                <strong>Operação centralizada</strong>
                                <span>Solicitações, liberações, comprovantes, ajustes e prestação de contas em uma única base operacional.</span>
                            </div>
                        </div>

                        <div class="journey-item">
                            <div class="journey-id">02</div>
                            <div class="journey-copy">
                                <strong>Aprovação e liberação contínuas</strong>
                                <span>Fluxo gerencial e financeiro com trilha completa de decisões, status e registro de depósito.</span>
                            </div>
                        </div>

                        <div class="journey-item">
                            <div class="journey-id">03</div>
                            <div class="journey-copy">
                                <strong>Conformidade inteligente</strong>
                                <span>Alertas de fraude, divergências de OCR, auditoria detalhada e acompanhamento dos caixas em aberto.</span>
                            </div>
                        </div>

                        <div class="journey-item">
                            <div class="journey-id">04</div>
                            <div class="journey-copy">
                                <strong>Operação omnicanal</strong>
                                <span>Painel administrativo, API versionada e aplicativo mobile com sincronização offline no mesmo domínio funcional.</span>
                            </div>
                        </div>

                        <div class="journey-item">
                            <div class="journey-id">05</div>
                            <div class="journey-copy">
                                <strong>Escala com governança</strong>
                                <span>Perfis, permissões, centros de custo, departamentos e regras de alçada preparados para crescimento.</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="form-panel">
                    <div class="form-card">
                        <span class="form-kicker">Login</span>
                        <h2 class="form-title">Acesse sua conta</h2>
                        <p class="form-subtitle">Use seu e-mail corporativo para acessar o painel administrativo do Caixa Pulse com segurança e rastreabilidade.</p>

                        @if ($errors->any())
                            <div class="alert is-error" role="alert" aria-live="polite">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        @if (session('status'))
                            <div class="alert" role="status" aria-live="polite">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.store', absolute: false) }}">
                            @csrf

                            <div class="form-grid">
                                <div class="field">
                                    <label for="email">E-mail corporativo</label>
                                    <div class="input-shell">
                                        <span class="icon-box" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M4 7.75A2.75 2.75 0 0 1 6.75 5h10.5A2.75 2.75 0 0 1 20 7.75v8.5A2.75 2.75 0 0 1 17.25 19H6.75A2.75 2.75 0 0 1 4 16.25v-8.5Z" stroke="currentColor" stroke-width="1.8"/>
                                                <path d="m5.5 7 6.5 5 6.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                        <input
                                            id="email"
                                            type="email"
                                            name="email"
                                            autocomplete="email"
                                            placeholder="Digite seu e-mail corporativo"
                                            required
                                            autofocus
                                        >
                                    </div>
                                </div>

                                <div class="field">
                                    <label for="password">Senha</label>
                                    <div class="input-shell">
                                        <span class="icon-box" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M7.75 11V8.75a4.25 4.25 0 1 1 8.5 0V11" stroke="currentColor" stroke-width="1.8"/>
                                                <rect x="5.5" y="11" width="13" height="8.5" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                            </svg>
                                        </span>
                                        <input
                                            id="password"
                                            type="password"
                                            name="password"
                                            autocomplete="current-password"
                                            placeholder="Digite sua senha"
                                            required
                                        >
                                        <button class="password-action" type="button" data-password-toggle data-show-label="Mostrar" data-hide-label="Ocultar">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" data-password-icon>
                                                <path d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                <circle cx="12" cy="12" r="3.25" stroke="currentColor" stroke-width="1.8"/>
                                            </svg>
                                            <span data-password-label>Mostrar</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-meta">
                                <label class="checkbox" for="remember">
                                    <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                                    <span>Lembrar acesso</span>
                                </label>
                                <span>Acesso corporativo</span>
                            </div>

                            <button class="submit-button" type="submit">
                                <span>Entrar no sistema</span>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 12h14M13 5l7 7-7 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </form>

                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const toggleButton = document.querySelector('[data-password-toggle]');
            const passwordInput = document.getElementById('password');
            const label = document.querySelector('[data-password-label]');
            const icon = document.querySelector('[data-password-icon]');

            if (!toggleButton || !passwordInput || !label || !icon) {
                return;
            }

            const eyeIcon = `
                <path d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="3.25" stroke="currentColor" stroke-width="1.8"/>
            `;
            const eyeOffIcon = `
                <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M10.58 5.79A10.5 10.5 0 0 1 12 5.75c6.25 0 9.75 6.25 9.75 6.25a18.9 18.9 0 0 1-3.12 3.74M6.4 6.39C3.96 8.08 2.25 12 2.25 12S5.75 18.25 12 18.25a9.8 9.8 0 0 0 2.76-.38" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9.88 9.88A3 3 0 0 0 14.12 14.12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            `;

            toggleButton.addEventListener('click', () => {
                const isPassword = passwordInput.getAttribute('type') === 'password';

                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                label.textContent = isPassword
                    ? toggleButton.dataset.hideLabel || 'Ocultar'
                    : toggleButton.dataset.showLabel || 'Mostrar';
                icon.innerHTML = isPassword ? eyeOffIcon : eyeIcon;
            });
        })();
    </script>
</body>
</html>

