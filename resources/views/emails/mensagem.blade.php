<!DOCTYPE html>
<html>
<head>
    <title>{{ $titulo }}</title>
</head>
<body>
    <h1>{{ $titulo }}</h1>
    <p>Nome: {{ $nome }}</p>
    <p>Status WPP: {{ $statusWPP['status'] }}</p>

    @if(isset($qrcodepath))
        <p>QR Code:</p>
        <img src="{{ $message->embed($qrcodepath) }}" alt="QR Code">
    @endif

    <p>URL: {{ $url }}</p>
</body>
</html>
