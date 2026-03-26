@php
    $code = 500;
    $title = 'Erro interno do servidor';
    $heading = 'Encontramos uma falha inesperada.';
    $description = 'Nossa equipe pode analisar esse incidente com base nos logs e na trilha de auditoria do sistema.';
    $message = 'Tente novamente em alguns instantes. Se o problema persistir, registre o horário da ocorrência e acione o suporte técnico.';
@endphp

@include('errors.minimal')
