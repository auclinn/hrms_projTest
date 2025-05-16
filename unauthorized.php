<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Forbidden</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f8f8f8;
            color: #333;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            padding: 40px 60px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 { color: #d32f2f; }
    </style>
</head>
<body>
    <div class="container">
        <h1>403 Forbidden</h1>
        <p>You are not authorized to access this page.</p>
        <p><a href="javascript:history.back()">Go Back</a></p>
    </div>
</body>
</html>