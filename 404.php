<?php
/**
 * 404 Not Found Error Page
 * ByteLab Olympiad Management System
 */

http_response_code(404);
require_once 'config/db.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container { text-align: center; max-width: 600px; }
        .error-code { font-size: 120px; font-weight: bold; color: white; margin-bottom: 20px; }
        .error-title { font-size: 36px; color: white; margin-bottom: 15px; font-weight: 600; }
        .error-message { font-size: 18px; color: rgba(255,255,255,0.9); margin-bottom: 30px; line-height: 1.6; }
        .suggestion { 
            background: rgba(255,255,255,0.1); 
            padding: 20px; 
            border-radius: 10px; 
            color: white;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .buttons { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }
        .icons { font-size: 60px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icons">🔍</div>
        <div class="error-code">404</div>
        <div class="error-title">Page Not Found</div>
        <div class="error-message">
            Sorry, the page you're looking for doesn't exist or has been moved.
        </div>
        
        <div class="suggestion">
            <strong>What you can do:</strong>
            <ul style="margin: 10px 0; text-align: left; display: inline-block;">
                <li>Check the URL and try again</li>
                <li>Go back to the previous page</li>
                <li>Return to the home page</li>
                <li>Contact support if you think this is an error</li>
            </ul>
        </div>
        
        <div class="buttons">
            <button class="btn" onclick="history.back()">← Go Back</button>
            <a href="index.html" class="btn btn-secondary">🏠 Home Page</a>
        </div>
    </div>
</body>
</html>
