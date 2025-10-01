<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - Bail Mobilite</title>
    <style>
        body {
            font-family: 'Figtree', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f3f4f6;
            color: #374151;
            text-align: center;
            padding: 20px;
        }
        .offline-container {
            max-width: 500px;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .offline-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            color: #374151;
            margin-bottom: 16px;
        }
        p {
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .refresh-btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .refresh-btn:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">✈️</div>
        <h1>You're Offline</h1>
        <p>Don't worry, you can still access previously visited pages. Please check your internet connection and try again.</p>
        <button class="refresh-btn" onclick="window.location.reload()">Try Again</button>
    </div>
</body>
</html>