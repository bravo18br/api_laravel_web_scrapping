<!DOCTYPE html>
<html>

<head>
    <title>Monitora Sites API</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css">
</head>

<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
    <script>
        const ui = SwaggerUIBundle({
            url: '/swagger.yaml',
            dom_id: '#swagger-ui'
        });
    </script>
</body>

</html>