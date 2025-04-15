<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Wacthwoord reset geaccepteerd</title>
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
            background-color: #10B981;
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
            background-color: #10B981;
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
            <h1>Wachtwoord reset geaccepteerd</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Uw verzoek tot het resetten van uw wachtwoord is goedgekeurd door ons team, klik hieronder om uw wachtwoord aan te passen:</p>
            <div style="text-align: center;">
                <a href="{{ route('password.reset.form', $resetRequest->token) }}" class="button">Reset je wachtwoord</a>
            </div>
            <p>Als u dit niet was, contacteer ons dan direct.</p>
        </div>
        <div class="footer">
            <p>Deze email is geautomatiseerd, gelieve niet te reageren.</p>
        </div>
    </div>
</body>
</html>
