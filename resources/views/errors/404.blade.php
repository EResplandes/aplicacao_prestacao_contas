@php
    $code = 404;
    $title = 'Página não encontrada';
    $heading = 'Não encontramos o conteúdo que você procurava.';
    $description = 'A rota solicitada não existe mais, foi alterada ou não está disponível para este ambiente.';
    $message = 'Use a navegação principal para retornar ao fluxo correto do painel ou faça uma nova busca dentro do sistema.';
@endphp

@include('errors.minimal')
