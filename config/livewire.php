<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CSP Safe Bundle
    |--------------------------------------------------------------------------
    |
    | O painel administrativo usa uma Content Security Policy mais rígida.
    | Esta flag manda o Livewire servir a build compatível com CSP, evitando
    | o uso de unsafe-eval no navegador.
    |
    */
    'csp_safe' => (bool) env('LIVEWIRE_CSP_SAFE', true),
];
