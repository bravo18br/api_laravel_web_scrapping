<!DOCTYPE html>
<html>

<head>
    <title>{{ $titulo }}</title>
</head>

<body>
    <h1>O site {{ $nome }} foi alterado</h1>
    <p>URL: {{ $url }}</p>
    <h1>Notificador WPP Connect</h1>
    <p>Status: {{ $statusWPP }}</p>

    @if(isset($qrcodepath))
    <p>Escaneie o QRCode para logar:</p>
    <img src="{{ $message->embed($qrcodepath) }}" alt="QR Code">
    @endif

</body>

</html>