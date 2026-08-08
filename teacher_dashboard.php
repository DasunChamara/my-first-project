<?php
include 'db.php';
session_start();

// 1. ආරක්ෂාව පරීක්ෂාව
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_class = "";

// 2. ගුරුවරයාගේ විස්තර ලබා ගැනීම
$teacher_sql = "SELECT * FROM teacher_details WHERE user_id = '$user_id'";
$teacher_result = $conn->query($teacher_sql);
$teacher = $teacher_result->fetch_assoc();


// ====== 3. POST REQUESTS පාලනය කිරීම ======

// ⚡ (A) වෙනස් කළ කොටස: ටීචර් File එකක් සමඟ Assignment එකක් එකතු කිරීම
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_assignment'])) {
    $title = $conn->real_escape_string(trim($_POST['title']));
    $deadline_date = $conn->real_escape_string($_POST['deadline_date']);
    
    $uploaded_file_name = "";
    
    // ටීචර් ෆයිල් එකක් තෝරා ඇත්නම් පමණක් එය අප්ලෝඩ් කිරීම
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == 0) {
        $target_dir = "uploads/";
        $raw_file_name = basename($_FILES["assignment_file"]["name"]);
        $file_ext = strtolower(pathinfo($raw_file_name, PATHINFO_EXTENSION));
        
        // ටීචර්ට දාන්න පුළුවන් File වර්ග (PDF, DOC, DOCX, PPTX)
        $allowed_types = array("pdf", "doc", "docx", "jpg", "png", "zip");
        
        if (in_array($file_ext, $allowed_types)) {
            // ටීචර්ගේ ෆයිල් එකට අද්විතීය නමක් දීම
            $uploaded_file_name = "Task_" . time() . "_" . rand(100,999) . "." . $file_ext;
            $target_file = $target_dir . $uploaded_file_name;
            
            if (!move_uploaded_file($_FILES["assignment_file"]["tmp_name"], $target_file)) {
                $msg = "කණගාටුයි! ෆයිල් එක Upload වීමේ දෝෂයක් ඇති විය.";
                $msg_class = "alert-danger";
            }
        } else {
            $msg = "අනුමත නොකරන ලද ෆයිල් වර්ගයකි! (PDF, Word, Zip පමණි)";
            $msg_class = "alert-danger";
        }
    }

    // කිසිදු දෝෂයක් නැත්නම් හෝ ෆයිල් එක සාර්ථක නම් දත්ත ඇතුළත් කිරීම
    if ($msg == "") {
        $ins_sql = "INSERT INTO assignments (title, deadline_date, assignment_file) VALUES ('$title', '$deadline_date', '$uploaded_file_name')";
        if ($conn->query($ins_sql) === TRUE) {
            $msg = "නව Assignment එක සාර්ථකව පද්ධතියට ඇතුළත් කරන ලදී!";
            $msg_class = "alert-success";
        } else {
            $msg = "දෝෂයකි: " . $conn->error;
            $msg_class = "alert-danger";
        }
    }
}

// (B) සිසුන්ගේ පිළිතුරු වලට ලකුණු සහ අදහස් ලබා දීම
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['grade_submission'])) {
    $submission_id = $conn->real_escape_string($_POST['submission_id']);
    $marks = $conn->real_escape_string($_POST['marks']);
    $comments = $conn->real_escape_string(trim($_POST['comments']));
    
    $grade_sql = "UPDATE submissions SET marks='$marks', comments='$comments' WHERE id='$submission_id'";
    if ($conn->query($grade_sql) === TRUE) {
        $msg = "ලකුණු සහ ප්‍රතිචාර සාර්ථකව යාවත්කාලීන කරන ලදී!";
        $msg_class = "alert-success";
    } else {
        $msg = "ලකුණු ඇතුළත් කිරීමේදී දෝෂයක් සිදු විය: " . $conn->error;
        $msg_class = "alert-danger";
    }
}

// (C) Profile Update කිරීම
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $is_visiting = $conn->real_escape_string($_POST['is_visiting']); 
    
    $up_profile_sql = "UPDATE teacher_details SET phone='$phone', is_visiting='$is_visiting' WHERE user_id='$user_id'";
    if ($conn->query($up_profile_sql) === TRUE) {
        $msg = "ඔබගේ විස්තර සාර්ථකව වෙනස් කරන ලදී!";
        $msg_class = "alert-success";
        header("Refresh:1"); 
    } else {
        $msg = "විස්තර වෙනස් කිරීමේදී දෝෂයක් සිදු විය: " . $conn->error;
        $msg_class = "alert-danger";
    }
}

// ====== 4. DATA FETCHING ======
$all_assignments = $conn->query("SELECT * FROM assignments ORDER BY id DESC");

$submissions_sql = "SELECT s.id AS submission_id, a.title AS assignment_title, s.file_name, s.marks, s.comments, sd.initial_name, sd.index_no 
                    FROM submissions s
                    JOIN assignments a ON s.assignment_id = a.id
                    JOIN student_details sd ON s.student_id = sd.user_id
                    ORDER BY s.id DESC";
$all_submissions = $conn->query($submissions_sql);
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Assignment System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .btn-logout { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        @media (max-width: 992px) { .dashboard-grid { grid-template-columns: 1fr; } }
        
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h3 { margin-top: 0; color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        table, th, td { border: 1px solid #dbdbdb; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f8f9fa; }
        
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #444; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        
        .btn-primary { background: #3498db; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-primary:hover { background: #2980b9; }
        .btn-download { background: #2ecc71; color: white; padding: 4px 8px; text-decoration: none; border-radius: 4px; font-size: 12px; }
        
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        
        .inline-grade-form { display: flex; gap: 5px; align-items: center; }
        .inline-grade-form input[type="number"] { width: 60px; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h1>ගුරු පාලක පැනලය (Teacher Dashboard)</h1>
            <small>ආයුබෝවන්, ලෙක්චරර් තුමනි! (<?php echo htmlspecialchars($teacher['full_name'] ?? $_SESSION['email']); ?>)</small>
        </div>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>

    <?php if($msg != ""): ?>
        <div class="alert <?php echo $msg_class; ?>"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="dashboard-grid">
        
        <div class="main-content">
            <div class="card">
                <h3>සිසුන් එවූ පිළිතුරු සහ ලකුණු ලබා දීම (Submissions & Grading)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>සිසුවා (Index)</th>
                            <th>Assignment එක</th>
                            <th>ගොනුව (File)</th>
                            <th>ලකුණු සහ අදහස් (Grade & Feedback)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($all_submissions && $all_submissions->num_rows > 0): ?>
                            <?php while($sub = $all_submissions->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($sub['initial_name']); ?></strong><br>
                                        <small style="color:#7f8c8d;"><?php echo htmlspecialchars($sub['index_no']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($sub['assignment_title']); ?></td>
                                    <td>
                                        <a href="uploads/<?php echo $sub['file_name']; ?>" class="btn-download" download>Download</a>
                                    </td>
                                    <td>
                                        <form action="" method="POST" class="inline-grade-form">
                                            <input type="hidden" name="submission_id" value="<?php echo $sub['submission_id']; ?>">
                                            <input type="number" name="marks" value="<?php echo htmlspecialchars($sub['marks'] ?? ''); ?>" placeholder="Marks" max="100" min="0" required>
                                            <input type="text" name="comments" value="<?php echo htmlspecialchars($sub['comments'] ?? ''); ?>" placeholder="Comments...">
                                            <button type="submit" name="grade_submission" class="btn-primary" style="padding: 5px 10px; font-size:12px;">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">සිසුන් විසින් මෙතෙක් කිසිදු පිළිතුරක් එවා නොමැත.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3>දැනට පද්ධතියේ ඇති Assignments ලැයිස්තුව</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Assignment මාතෘකාව</th>
                            <th>අවසාන දිනය (Deadline)</th>
                            <th>ටීචර්ගේ ගොනුව (Resource)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($all_assignments && $all_assignments->num_rows > 0): ?>
                            <?php while($asg = $all_assignments->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $asg['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($asg['title']); ?></strong></td>
                                    <td><?php echo $asg['deadline_date']; ?></td>
                                    <td>
                                        <?php if(!empty($asg['assignment_file'])): ?>
                                            <a href="uploads/<?php echo $asg['assignment_file']; ?>" style="color:#2ecc71; text-decoration:none; font-weight:bold;" download>View Attachment</a>
                                        <?php else: ?>
                                            <span style="color:#7f8c8d;">No File</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">කිසිදු Assignment එකක් තවම සාදා නැත.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sidebar">
            <div class="card">
                <h3>නව Assignment එකක් එකතු කිරීම</h3>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Assignment මාතෘකාව (Title):</label>
                        <input type="text" name="title" placeholder="උදා: Assignment 01 - PHP" required>
                    </div>
                    <div class="form-group">
                        <label>භාර දිය යුතු අවසාන දිනය (Deadline):</label>
                        <input type="date" name="deadline_date" required>
                    </div>
                    <div class="form-group">
                        <label>අදාළ නිබන්ධනය/ප්‍රශ්න පත්‍රය (Optional):</label>
                        <input type="file" name="assignment_file" accept=".pdf, .doc, .docx, .zip">
                    </div>
                    <button type="submit" name="create_assignment" class="btn-primary" style="width:100%;">පද්ධතියට ඇතුළත් කරන්න (Post)</button>
                </form>
            </div>

            <div class="card">
                <h3>මගේ විස්තර (Profile Management)</h3>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>ටීචර් ID (Teacher ID):</label>
                        <input type="text" value="<?php echo htmlspecialchars($teacher['teacher_id'] ?? ''); ?>" disabled style="background:#eee;">
                    </div>
                    <div class="form-group">
                        <label>සම්පූර්ණ නම (Full Name):</label>
                        <input type="text" value="<?php echo htmlspecialchars($teacher['full_name'] ?? ''); ?>" disabled style="background:#eee;">
                    </div>
                    <div class="form-group">
                        <label>දුරකථන අංකය (Phone):</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($teacher['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>සේවා තත්ත්වය (Is Visiting Lecturer?):</label>
                        <select name="is_visiting">
                            <option value="Yes" <?php if(($teacher['is_visiting'] ?? '') == 'Yes') echo 'selected'; ?>>Yes (Visiting)</option>
                            <option value="No" <?php if(($teacher['is_visiting'] ?? '') == 'No') echo 'selected'; ?>>No (Full Time)</option>
                        </select>
                    </div>
                    <button type="submit" name="update_profile" class="btn-primary" style="width:100%; background:#2ecc71;">විස්තර Update කරන්න</button>
                </form>
            </div>
        </div>

    </div>

</body>
</html>