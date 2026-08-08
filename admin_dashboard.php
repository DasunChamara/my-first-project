<?php
include 'db.php';
session_start();

// 1. ආරක්ෂාව පරීක්ෂාව - පරිශීලකයා Admin කෙනෙක්දැයි බැලීම
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$msg = "";
$msg_class = "";

// ====== 2. POST REQUEST: අලුත් පරිශීලකයෙක් (Teacher/Student) එකතු කිරීම ======
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); 
    $role = $conn->real_escape_string($_POST['role']);
    
    // (A) මුලින්ම ප්‍රධාන users ටේබල් එකට දත්ත ඇතුළත් කිරීම
    $user_sql = "INSERT INTO users (email, password, role) VALUES ('$email', '$password', '$role')";
    
    if ($conn->query($user_sql) === TRUE) {
        $new_user_id = $conn->insert_id; 
        $sub_table_success = true;
        
        // (B) එකතු කළේ Teacher කෙනෙක් නම්
        if ($role === 'teacher') {
            $teacher_id = $conn->real_escape_string(trim($_POST['teacher_id']));
            $full_name = $conn->real_escape_string(trim($_POST['full_name']));
            $phone = $conn->real_escape_string(trim($_POST['phone']));
            $is_visiting = $conn->real_escape_string($_POST['is_visiting']);
            
            $teacher_sql = "INSERT INTO teacher_details (teacher_id, user_id, full_name, phone, is_visiting) 
                            VALUES ('$teacher_id', '$new_user_id', '$full_name', '$phone', '$is_visiting')";
            
            if (!$conn->query($teacher_sql)) {
                $sub_table_success = false;
                $msg = "ගුරු විස්තර ඇතුළත් කිරීමේදී දෝෂයක් සිදු විය (සමහරවිට Teacher ID එක දැනටමත් පද්ධතියේ තිබිය හැක): " . $conn->error;
                $msg_class = "alert-danger";
                // ප්‍රධාන users එකට වැටුන දත්තය අයින් කිරීම (Rollback)
                $conn->query("DELETE FROM users WHERE id = '$new_user_id'");
            }
        }
        
        // (C) එකතු කළේ Student කෙනෙක් නම්
        if ($role === 'student' && $sub_table_success) {
            $index_no = $conn->real_escape_string(trim($_POST['index_no']));
            $student_name = $conn->real_escape_string(trim($_POST['student_name']));
            
            $student_sql = "INSERT INTO student_details (index_no, user_id, full_name, initial_name) 
                            VALUES ('$index_no', '$new_user_id', '$student_name', '$student_name')";
            
            if (!$conn->query($student_sql)) {
                $sub_table_success = false;
                $msg = "ශිෂ්‍ය විස්තර ඇතුළත් කිරීමේදී දෝෂයක් සිදු විය (සමහරවිට Index No එක දැනටමත් තිබිය හැක): " . $conn->error;
                $msg_class = "alert-danger";
                $conn->query("DELETE FROM users WHERE id = '$new_user_id'");
            }
        }

        if ($sub_table_success) {
            $msg = "නව පරිශීලකයා සාර්ථකව පද්ධතියට ඇතුළත් කරන ලදී!";
            $msg_class = "alert-success";
        }
    } else {
        $msg = "ප්‍රධාන පරිශීලක ගිණුම සෑදීමේදී දෝෂයකි (සමහරවිට Email එක දැනටමත් තිබිය හැක): " . $conn->error;
        $msg_class = "alert-danger";
    }
}

// ====== 3. DATA FETCHING (ආරක්ෂිතව දත්ත ලබා ගැනීම) ======
$count_teachers = 0;
$count_students = 0;
$count_assignments = 0;

$teachers_q = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='teacher'");
if($teachers_q) { $count_teachers = $teachers_q->fetch_assoc()['total']; }

$students_q = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='student'");
if($students_q) { $count_students = $students_q->fetch_assoc()['total']; }

$assignments_q = $conn->query("SELECT COUNT(*) AS total FROM assignments");
if($assignments_q) { $count_assignments = $assignments_q->fetch_assoc()['total']; }

$all_users = $conn->query("SELECT id, email, role FROM users WHERE role != 'admin' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Control Panel</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .btn-logout { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #34495e; }
        .stat-card.blue { border-left-color: #3498db; }
        .stat-card.green { border-left-color: #2ecc71; }
        .stat-card h2 { margin: 0; font-size: 32px; color: #2c3e50; }
        .stat-card p { margin: 5px 0 0 0; color: #7f8c8d; font-weight: bold; }

        .dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .dashboard-grid { grid-template-columns: 1fr; } .stats-grid { grid-template-columns: 1fr; } }
        
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #444; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #dbdbdb; }
        th, td { padding: 10px; text-align: left; font-size: 14px; }
        th { background-color: #f8f9fa; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-teacher { background: #e8f4fd; color: #1d8cf8; }
        .badge-student { background: #e6f9ee; color: #00c851; }

        .btn-primary { background: #2c3e50; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; }
        .btn-primary:hover { background: #1a252f; }
        
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        
        #teacher_fields, #student_fields { display: none; background: #f9f9f9; padding: 15px; border-radius: 6px; border: 1px dashed #ccc; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>පද්ධති පරිපාලක පැනලය (Admin Panel)</h1>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>

    <?php if($msg != ""): ?>
        <div class="alert <?php echo $msg_class; ?>"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card blue">
            <h2><?php echo $count_teachers; ?></h2>
            <p>මුළු ගුරුවරුන් සංඛ්‍යාව</p>
        </div>
        <div class="stat-card green">
            <h2><?php echo $count_students; ?></h2>
            <p>මුළු සිසුන් සංඛ්‍යාව</p>
        </div>
        <div class="stat-card">
            <h2><?php echo $count_assignments; ?></h2>
            <p>මුළු Assignments ගණන</p>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <h3>නව පරිශීලකයෙකු පද්ධතියට එක් කිරීම</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label>පරිශීලක භූමිකාව (Role):</label>
                    <select name="role" id="user_role" onchange="toggleFields()" required>
                        <option value="">-- තෝරන්න --</option>
                        <option value="teacher">Teacher (ගුරු)</option>
                        <option value="student">Student (ශිෂ්‍ය)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>ਈමේල් ලිපිනය (Email):</label>
                    <input type="email" name="email" placeholder="example@mail.com" required>
                </div>

                <div class="form-group">
                    <label>මුරපදය (Password):</label>
                    <input type="password" name="password" placeholder="******" required>
                </div>

                <div id="teacher_fields">
                    <h4>ගුරුවරයාගේ පුද්ගලික විස්තර</h4>
                    <div class="form-group">
                        <label>ටීචර් ID (Teacher ID):</label>
                        <input type="text" name="teacher_id" placeholder="T001">
                    </div>
                    <div class="form-group">
                        <label>සම්පූර්ණ නම (Full Name):</label>
                        <input type="text" name="full_name" placeholder="Prof. Sunil Perera">
                    </div>
                    <div class="form-group">
                        <label>දුරකථන අංකය (Phone):</label>
                        <input type="text" name="phone" placeholder="07XXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>Visiting Lecturer ද?</label>
                        <select name="is_visiting">
                            <option value="No">No (Full Time)</option>
                            <option value="Yes">Yes (Visiting)</option>
                        </select>
                    </div>
                </div>

                <div id="student_fields">
                    <h4>ශිෂ්‍යයාගේ පුද්ගලික විස්තර</h4>
                    <div class="form-group">
                        <label>Index අංකය (Index No):</label>
                        <input type="text" name="index_no" placeholder="ST12345">
                    </div>
                    <div class="form-group">
                        <label>ශිෂ්‍යයාගේ නම (Name):</label>
                        <input type="text" name="student_name" placeholder="A.B. Silva">
                    </div>
                </div>

                <br>
                <button type="submit" name="add_user" class="btn-primary">පරිශීලකයා සුරකින්න (Register)</button>
            </form>
        </div>

        <div class="card">
            <h3>දැනට පද්ධතියේ සිටින පරිශීලකයින්</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($all_users && $all_users->num_rows > 0): ?>
                        <?php while($user = $all_users->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if($user['role'] == 'teacher'): ?>
                                        <span class="badge badge-teacher">Teacher</span>
                                    <?php else: ?>
                                        <span class="badge badge-student">Student</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;">පද්ධතියේ වෙනත් පරිශීලකයන් කිසිවෙකු නැත.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleFields() {
            var role = document.getElementById("user_role").value;
            var teacherFields = document.getElementById("teacher_fields");
            var studentFields = document.getElementById("student_fields");

            if (role === "teacher") {
                teacherFields.style.display = "block";
                studentFields.style.display = "none";
            } else if (role === "student") {
                studentFields.style.display = "block";
                teacherFields.style.display = "none";
            } else {
                teacherFields.style.display = "none";
                studentFields.style.display = "none";
            }
        }
    </script>
</body>
</html>