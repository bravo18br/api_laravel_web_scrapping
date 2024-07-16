<!DOCTYPE html>
<html>

<head>
    <title>{{$emailData['titulo']}}</title>
</head>

<body>
    <h1>Notificação</h1>
    <p>O site {{$emailData['nome']}} foi alterado</p>
    <p>URL: {{$emailData['url']}}</p>
    <h1>WPP Connect</h1>
    <p>Status - {{$emailData['statusWPP']}}</p>
</body>

</html>