<?php
include 'db.php';
$msg = "";
$msg_class = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // SQL Injection වලින් ආරක්ෂා වීමට real_escape_string භාවිත කර ඇත
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $conn->real_escape_string(trim($_POST['password']));
    $role = $conn->real_escape_string($_POST['role']);
    
    $full_name = $conn->real_escape_string(trim($_POST['full_name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));

    // 1. Email එක දැනටමත් පද්ධතියේ තියෙනවද කියා බැලීම
    $check_user = "SELECT * FROM users WHERE email='$email'";
    if ($conn->query($check_user)->num_rows > 0) {
        $msg = "මෙම Email ලිපිනය දැනටමත් ලියාපදිංචි කර ඇත.";
        $msg_class = "error";
    } else {
        // 2. ප්‍රධාන Users Table එකට ඇතුළත් කිරීම
        $sql_user = "INSERT INTO users (email, password, role) VALUES ('$email', '$password', '$role')";
        
        if ($conn->query($sql_user) === TRUE) {
            $last_user_id = $conn->insert_id; 

            // 3. Role එක අනුව අදාළ Table එකට දත්ත ඇතුළත් කිරීම
            if ($role == 'student') {
                $index_no = $conn->real_escape_string(trim($_POST['index_no']));
                $initial_name = $conn->real_escape_string(trim($_POST['initial_name']));
                $address = $conn->real_escape_string(trim($_POST['address']));
                $whatsapp_no = $conn->real_escape_string(trim($_POST['whatsapp_no']));
                $dob = $conn->real_escape_string($_POST['dob']);

                $sql_details = "INSERT INTO student_details (index_no, user_id, full_name, initial_name, address, phone, whatsapp_no, dob) 
                                VALUES ('$index_no', '$last_user_id', '$full_name', '$initial_name', '$address', '$phone', '$whatsapp_no', '$dob')";
            } else {
                $teacher_id = $conn->real_escape_string(trim($_POST['teacher_id']));
                $is_visiting = $conn->real_escape_string($_POST['is_visiting']);

                $sql_details = "INSERT INTO teacher_details (teacher_id, user_id, full_name, phone, is_visiting) 
                                VALUES ('$teacher_id', '$last_user_id', '$full_name', '$phone', '$is_visiting')";
            }

            if ($conn->query($sql_details) === TRUE) {
                $msg = "ලියාපදිංචිය සාර්ථකයි! සුළු මොහොතකින් ඔබව මුල් පිටුවට රැගෙන යනු ඇත...";
                $msg_class = "success";
                header("refresh:2;url=index.php"); 
            } else {
                // දෝෂයක් ආවොත් users table එකට වැටුණු කොටස අයින් කිරීම (Rollback)
                $conn->query("DELETE FROM users WHERE id='$last_user_id'");
                $msg = "විස්තර ඇතුළත් කිරීමේදී දෝෂයක්: " . $conn->error;
                $msg_class = "error";
            }
        } else {
            $msg = "පද්ධති දෝෂයක්: " . $conn->error;
            $msg_class = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Assignment System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #71b7e6, #9b59b6); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px 0; }
        .form-container { background: white; padding: 35px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 450px; }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 25px; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 6px; color: #555; font-weight: 600; }
        .input-group input, .input-group select, .input-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 15px; font-family: inherit; }
        .input-group input:focus, .input-group select:focus, .input-group textarea:focus { border-color: #9b59b6; outline: none; }
        .btn-submit { width: 100%; padding: 12px; background: #2ecc71; border: none; color: white; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; transition: background 0.3s; margin-top: 10px; }
        .btn-submit:hover { background: #27ae60; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #3498db; text-decoration: none; font-size: 14px; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; font-size: 14px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .extra-fields { display: none; }
    </style>
    <script>
        function toggleFields() {
            var role = document.getElementById("role").value;
            var studentFields = document.getElementById("student-fields");
            var teacherFields = document.getElementById("teacher-fields");
            
            if (role === "student") {
                studentFields.style.display = "block";
                teacherFields.style.display = "none";
                document.getElementById("index_no").required = true;
                document.getElementById("initial_name").required = true;
                document.getElementById("teacher_id").required = false;
                document.getElementById("is_visiting").required = false;
            } else if (role === "teacher") {
                studentFields.style.display = "none";
                teacherFields.style.display = "block";
                document.getElementById("index_no").required = false;
                document.getElementById("initial_name").required = false;
                document.getElementById("teacher_id").required = true;
                document.getElementById("is_visiting").required = true;
            } else {
                studentFields.style.display = "none";
                teacherFields.style.display = "none";
            }
        }
    </script>
</head>
<body>

<div class="form-container">
    <h2>නව ගිණුමක් අරඹන්න</h2>
    
    <?php if($msg != ""): ?>
        <div class="alert <?php echo $msg_class; ?>"><?php echo $msg; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="input-group">
            <label>ඔබ කවුද? (Role)</label>
            <select name="role" id="role" onchange="toggleFields()" required>
                <option value="">-- තෝරන්න --</option>
                <option value="student">Student (සිසුවෙක්)</option>
                <option value="teacher">Teacher (ගුරුවරයෙක්)</option>
            </select>
        </div>

        <div class="input-group">
            <label>Email Address (Log වෙන්න සහ පොදුවේ පාවිච්චි වන ලිපිනය)</label>
            <input type="email" name="email" required autocomplete="off">
        </div>
        
        <div class="input-group">
            <label>Password (මුරපදය)</label>
            <input type="password" name="password" required>
        </div>

        <div class="input-group">
            <label>සම්පූර්ණ නම (Full Name)</label>
            <input type="text" name="full_name" required>
        </div>

        <div class="input-group">
            <label>දුරකථන අංකය (Phone)</label>
            <input type="text" name="phone">
        </div>

        <div id="student-fields" class="extra-fields">
            <div class="input-group">
                <label>Index Number (ශිෂ්‍ය අංකය)</label>
                <input type="text" name="index_no" id="index_no">
            </div>
            <div class="input-group">
                <label>Name with Initials (මුලකුරු සමඟ නම)</label>
                <input type="text" name="initial_name" id="initial_name">
            </div>
            <div class="input-group">
                <label>ලිපිනය (Address)</label>
                <textarea name="address" rows="3"></textarea>
            </div>
            <div class="input-group">
                <label>WhatsApp Number</label>
                <input type="text" name="whatsapp_no">
            </div>
            <div class="input-group">
                <label>උපන් දිනය (Date of Birth)</label>
                <input type="date" name="dob">
            </div>
        </div>

        <div id="teacher-fields" class="extra-fields">
            <div class="input-group">
                <label>Teacher ID (ගුරු අංකය)</label>
                <input type="text" name="teacher_id" id="teacher_id">
            </div>
            <div class="input-group">
                <label>Visiting Teacher කෙනෙක්ද?</label>
                <select name="is_visiting" id="is_visiting">
                    <option value="No">No (ස්ථිර ගුරු)</option>
                    <option value="Yes">Yes (බාහිර ගුරු)</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn-submit">Register වන්න</button>
    </form>
    
    <a href="index.php" class="back-link">← ආපසු මුල් පිටුවට</a>
</div>

</body>
</html>