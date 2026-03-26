@php
    $code = 419;
    $title = 'Sessão expirada';
    $heading = 'Sua sessão expirou antes de concluir a ação.';
    $description = 'Por segurança, precisamos renovar a autenticação antes de prosseguir.';
    $message = 'Volte ao login ou retorne à página anterior para reenviar a operação com uma sessão válida.';
@endphp

@include('errors.minimal')
