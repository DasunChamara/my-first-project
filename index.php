<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Submission System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #333;
        }
        .welcome-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            text-align: center;
            max-width: 450px;
            width: 100%;
        }
        h1 { margin-bottom: 10px; color: #2c3e50; font-size: 28px; }
        p { color: #7f8c8d; margin-bottom: 30px; font-size: 16px; }
        .btn-group { display: flex; gap: 15px; justify-content: center; }
        .btn {
            flex: 1;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-login { background-color: #3498db; color: white; }
        .btn-login:hover { background-color: #2980b9; }
        .btn-register { background-color: #2ecc71; color: white; }
        .btn-register:hover { background-color: #27ae60; }
    </style>
</head>
<body>

<div class="welcome-container">
    <h1>Assignment Portal</h1>
    <p>පද්ධතියට ඇතුළු වීම සඳහා පහත බොත්තම් භාවිතා කරන්න.</p>
    <div class="btn-group">
        <a href="login.php" class="btn btn-login">Log In</a>
        <a href="register.php" class="btn btn-register">Register</a>
    </div>
</div>

</body>
</html>