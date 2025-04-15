<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #f9f9f9;
        }
        .footer {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #888;
        }
        .button {
            display: inline-block;
            background-color: #4F46E5;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Wachtwoord Reset Aanvraag</h1>
        </div>
        <div class="content">
            <p>Hallo Admin,</p>
            <p>Er is een verzoek ingediend om een wachtwoord te resetten.</p>
            <p>Volg de link om deze te accepteren of af te wijzen.</p>
            <a href="http://127.0.0.1:8000/admin/password-reset-requests" class="button">Zie verzoeken</a>
        </div>
        <div class="footer">
            <p>Deze email is geautomatiseerd, gelieve niet te reageren.</p>
        </div>
    </div>
</body>
</html>