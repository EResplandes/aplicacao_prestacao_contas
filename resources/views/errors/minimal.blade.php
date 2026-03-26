<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Caixa Pulse</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-950 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        <section class="w-full max-w-5xl overflow-hidden rounded-[36px] bg-white shadow-[0_35px_90px_rgba(15,23,42,0.16)]">
            <div class="grid min-h-[640px] lg:grid-cols-[1.02fr_0.98fr]">
                <div class="relative overflow-hidden bg-[linear-gradient(145deg,#1d4ed8_0%,#2563eb_45%,#3b82f6_100%)] px-8 py-10 text-white sm:px-10 lg:px-12 lg:py-14">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.18),transparent_26%),radial-gradient(circle_at_bottom_left,rgba(191,219,254,0.22),transparent_24%)]"></div>
                    <div class="relative flex h-full flex-col justify-between gap-8">
                        <div class="space-y-4">
                            <span class="inline-flex w-fit items-center rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.28em] text-blue-100">
                                Caixa Pulse
                            </span>
                            <div class="space-y-3">
                                <p class="text-sm font-semibold uppercase tracking-[0.32em] text-blue-100/90">
                                    Erro {{ $code }}
                                </p>
                                <h1 class="max-w-md text-4xl font-semibold leading-tight sm:text-5xl">
                                    {{ $title }}
                                </h1>
                                <p class="max-w-xl text-base leading-7 text-blue-100/90 sm:text-lg">
                                    {{ $description }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <article class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-100/75">O que fazer agora</p>
                                <p class="mt-3 text-sm leading-6 text-blue-50/90">
                                    Revise os dados informados, tente novamente em instantes e, se o problema persistir, acione o time responsável.
                                </p>
                            </article>
                            <article class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-100/75">Ambiente seguro</p>
                                <p class="mt-3 text-sm leading-6 text-blue-50/90">
                                    O sistema preservou sua sessão e mantém a trilha de auditoria para apoiar o suporte, caso necessário.
                                </p>
                            </article>
                        </div>
                    </div>
                </div>

                <div class="flex items-center bg-white px-8 py-10 sm:px-10 lg:px-12 lg:py-14">
                    <div class="w-full space-y-8">
                        <div class="space-y-4">
                            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-2xl font-semibold text-blue-700">
                                {{ $code }}
                            </div>
                            <div class="space-y-3">
                                <h2 class="text-2xl font-semibold text-slate-950 sm:text-3xl">
                                    {{ $heading }}
                                </h2>
                                <p class="max-w-xl text-sm leading-7 text-slate-500 sm:text-base">
                                    {{ $message }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 rounded-[28px] border border-slate-200 bg-slate-50 p-5 sm:grid-cols-3">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Status</p>
                                <p class="text-base font-semibold text-slate-950">{{ $title }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Horário</p>
                                <p class="text-base font-semibold text-slate-950">{{ now()->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Canal</p>
                                <p class="text-base font-semibold text-slate-950">
                                    {{ request()->is('api/*') ? 'API' : 'Painel web' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <a
                                href="{{ auth()->check() ? route('admin.dashboard') : route('login') }}"
                                class="inline-flex h-12 items-center justify-center rounded-2xl bg-blue-600 px-5 text-sm font-semibold text-white transition hover:bg-blue-700"
                            >
                                {{ auth()->check() ? 'Voltar ao painel' : 'Ir para o login' }}
                            </a>
                            <a
                                href="{{ url()->previous() !== request()->fullUrl() ? url()->previous() : url('/') }}"
                                class="inline-flex h-12 items-center justify-center rounded-2xl border border-slate-200 px-5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                            >
                                Retornar para a página anterior
                            </a>
                        </div>

                        <p class="text-xs leading-6 text-slate-400">
                            Se o incidente continuar, registre o horário exibido nesta tela e compartilhe com o suporte.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
