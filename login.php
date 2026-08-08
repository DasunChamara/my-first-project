<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


include 'db.php';
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email'"; 
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // ⚡ වෙනස් කළ කොටස: Hash කළ මුරපදය නිවැරදිව පරීක්ෂා කිරීම
        if (password_verify($password, $row['password']) || $password == $row['password']) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];

            // Role එක අනුව අදාළ Dashboard එකට යැවීම
            if ($row['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($row['role'] == 'lecturer' || $row['role'] == 'teacher') {
                header("Location: teacher_dashboard.php");
            } elseif ($row['role'] == 'student') {
                header("Location: student_dashboard.php");
            } else {
                $error = "පද්ධතියේ දෝෂයකි! ඔබගේ Role එක හඳුනාගත නොහැක.";
            }
            exit();
        } else {
            $error = "වැරදි මුරපදයක් (Invalid Password)!";
        }
    } else {
        $error = "මෙම ඊමේල් ලිපිනයෙන් පරිශීලකයෙකු නොමැත!";
    }
}
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <title>Login - Assignment System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7f6;
            margin: 0;
            padding: 30px; 
            box-sizing: border-box;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%; 
            max-width: 450px;
            box-sizing: border-box;
        }
        h2 { text-align: center; color: #333; margin-bottom: 30px;}
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; color: #666; font-size: 16px; }
        .input-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 16px; }
        .input-group input:focus { border-color: #2ecc71; outline: none; }
        .btn { width: 100%; padding: 14px; background: #2ecc71; border: none; color: white; font-size: 18px; border-radius: 5px; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #27ae60; }
        .error { color: #e74c3c; background: #fde8e7; padding: 12px; border-radius: 5px; border: 1px solid #f5c6cb; text-align: center; margin-bottom: 20px; font-size: 15px; }
        
        .bottom-links {
            margin-top: 25px;
            text-align: left; 
        }
        .nav-link {
            text-decoration: none;
            color: #777;
            font-size: 15px;
            transition: color 0.3s;
        }
        .nav-link:hover {
            color: #2ecc71;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Log In</h2>
    <?php if($error != "") { echo "<p class='error'>$error</p>"; } ?>
    
    <form action="" method="POST">
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" required autocomplete="off">
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">ඇතුළු වන්න</button>
    </form>

    <div class="bottom-links">
        <a href="index.php" class="nav-link">← මුල් පිටුවට (Home)</a>
    </div>
</div>

</body>
</html>