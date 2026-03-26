@php
    $code = 403;
    $title = 'Acesso negado';
    $heading = 'Você não tem permissão para acessar este recurso.';
    $description = 'A ação foi bloqueada pelas regras de acesso do Caixa Pulse.';
    $message = 'Se você acredita que deveria visualizar esta área, confirme seu perfil de acesso ou solicite a liberação ao administrador do sistema.';
@endphp

@include('errors.minimal')
