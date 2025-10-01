<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Offline - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 20px;
        }

        .offline-container {
            max-width: 400px;
            width: 100%;
        }

        .offline-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            opacity: 0.8;
        }

        .offline-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .offline-message {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .retry-button {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            min-height: 44px;
            min-width: 120px;
        }

        .retry-button:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .retry-button:active {
            transform: translateY(0);
        }

        .connection-status {
            margin-top: 20px;
            font-size: 14px;
            opacity: 0.7;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.7;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .offline-features {
            margin-top: 40px;
            text-align: left;
        }

        .offline-features h3 {
            font-size: 18px;
            margin-bottom: 15px;
            text-align: center;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            padding: 8px 0;
            font-size: 14px;
            opacity: 0.8;
            display: flex;
            align-items: center;
        }

        .feature-list li::before {
            content: '✓';
            margin-right: 10px;
            color: #4ade80;
            font-weight: bold;
        }

        @media (max-width: 480px) {
            .offline-title {
                font-size: 24px;
            }
            
            .offline-message {
                font-size: 14px;
            }
            
            .retry-button {
                width: 100%;
                padding: 16px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon pulse">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM11 17l-5-5 1.41-1.41L11 14.17l7.59-7.59L20 8l-9 9z"/>
            </svg>
        </div>
        
        <h1 class="offline-title">You're Offline</h1>
        
        <p class="offline-message">
            No internet connection detected. Some features may be limited, but you can still access cached content.
        </p>
        
        <button class="retry-button" onclick="checkConnection()">
            Try Again
        </button>
        
        <div class="connection-status" id="connectionStatus">
            Checking connection...
        </div>

        <div class="offline-features">
            <h3>Available Offline</h3>
            <ul class="feature-list">
                <li>View cached property data</li>
                <li>Access recent mission details</li>
                <li>Review completed checklists</li>
                <li>Browse saved reports</li>
            </ul>
        </div>
    </div>

    <script>
        let isOnline = navigator.onLine;
        let retryAttempts = 0;
        const maxRetryAttempts = 3;

        function updateConnectionStatus() {
            const statusElement = document.getElementById('connectionStatus');
            const retryButton = document.querySelector('.retry-button');
            
            if (navigator.onLine) {
                statusElement.textContent = 'Connection restored! Redirecting...';
                statusElement.style.color = '#4ade80';
                retryButton.textContent = 'Redirecting...';
                retryButton.disabled = true;
                
                // Redirect to the main app after a short delay
                setTimeout(() => {
                    window.location.href = '/';
                }, 1500);
            } else {
                statusElement.textContent = 'Still offline. Please check your connection.';
                statusElement.style.color = 'rgba(255, 255, 255, 0.7)';
            }
        }

        function checkConnection() {
            const retryButton = document.querySelector('.retry-button');
            const statusElement = document.getElementById('connectionStatus');
            
            retryButton.textContent = 'Checking...';
            retryButton.disabled = true;
            statusElement.textContent = 'Checking connection...';
            
            // Try to fetch a small resource to test connectivity
            fetch('/ping', { 
                method: 'HEAD',
                cache: 'no-cache',
                mode: 'no-cors'
            })
            .then(() => {
                // Connection successful
                statusElement.textContent = 'Connection restored! Redirecting...';
                statusElement.style.color = '#4ade80';
                retryButton.textContent = 'Redirecting...';
                
                setTimeout(() => {
                    window.location.href = '/';
                }, 1000);
            })
            .catch(() => {
                // Still offline
                retryAttempts++;
                retryButton.disabled = false;
                retryButton.textContent = 'Try Again';
                
                if (retryAttempts >= maxRetryAttempts) {
                    statusElement.textContent = 'Connection failed. Please check your network settings.';
                    statusElement.style.color = '#f87171';
                } else {
                    statusElement.textContent = 'Still offline. Please check your connection.';
                    statusElement.style.color = 'rgba(255, 255, 255, 0.7)';
                }
            });
        }

        // Listen for online/offline events
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);

        // Initial connection check
        setTimeout(updateConnectionStatus, 1000);

        // Auto-retry every 30 seconds
        setInterval(() => {
            if (!navigator.onLine && retryAttempts < maxRetryAttempts) {
                checkConnection();
            }
        }, 30000);

        // Handle back button
        window.addEventListener('popstate', function(event) {
            if (navigator.onLine) {
                window.location.href = '/';
            }
        });
    </script>
</body>
</html>