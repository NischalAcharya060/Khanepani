<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salkpur Khanepani – Not Optimized for Mobile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #004080, #0066cc);
            color: #fff;
        }

        .card {
            text-align: center;
            background: #fff;
            color: #333;
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            max-width: 460px;
            width: 90%;
            animation: fadeIn 0.6s ease;
        }

        .logo {
            font-size: 40px;
            margin-bottom: 15px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #d63333;
        }

        h2 {
            font-size: 20px;
            margin: 8px 0 18px;
            color: #004080;
        }

        p {
            font-size: 15px;
            margin: 12px 0 22px;
            line-height: 1.6;
            color: #444;
        }

        .btn {
            display: inline-block;
            background: #004080;
            color: #fff;
            padding: 12px 26px;
            font-size: 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin: 8px;
            transition: background 0.3s, transform 0.2s;
        }

        .btn:hover {
            background: #ff6600;
            transform: translateY(-2px);
        }

        .info {
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">💧</div>
    <h2>Salkpur Khanepani</h2>
    <h1>⚠️ Not Optimized for Mobile</h1>
    <p>
        Our website is designed for larger screens.<br>
        For the best experience, please use a desktop or laptop device.
    </p>
    <a href="admin/dashboard.php" class="btn">🔄 Refresh</a>
    <div class="info">
        💡 Tip: Accessing from desktop will provide better navigation,
        easier document viewing, and faster browsing.
    </div>
</div>
</body>
</html>
