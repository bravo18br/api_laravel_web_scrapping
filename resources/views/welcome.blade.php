<!DOCTYPE html>
<html>

<head>
    <title>Monitora Sites API</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css">
</head>

<body>
    <h1>Monitora Sites API</h1>
    <p><a href="https://github.com/bravo18br/api_laravel_web_scrapping">https://github.com/bravo18br/api_laravel_web_scrapping</a></p>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
    <script>
        const ui = SwaggerUIBundle({
            url: 'swagger.yaml',
            dom_id: '#swagger-ui'
        });
    </script>
</body>

</html>