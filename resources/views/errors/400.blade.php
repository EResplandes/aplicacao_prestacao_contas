@php
    $code = 400;
    $title = 'Solicitação inválida';
    $heading = 'Não foi possível concluir esta operação.';
    $description = 'Recebemos uma solicitação fora do formato esperado. Revise os dados enviados e tente novamente.';
    $message = 'Esse retorno normalmente acontece quando faltam parâmetros, há campos inconsistentes ou a requisição foi interrompida antes de chegar completa ao servidor.';
@endphp

@include('errors.minimal')
