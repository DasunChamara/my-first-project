<?php
include 'db.php';
session_start();

// 1. ආරක්ෂාව සඳහා ශිෂ්‍යයෙක්දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// URL එකෙන් Assignment ID එක ලබා ගැනීම
if (!isset($_GET['id'])) {
    header("Location: student_dashboard.php");
    exit();
}
$assignment_id = $conn->real_escape_string($_GET['id']);

// 2. අදාළ Assignment එකේ විස්තර ලබා ගැනීම
$assignment_sql = "SELECT * FROM assignments WHERE id = '$assignment_id'";
$assignment_result = $conn->query($assignment_sql);
$assignment = $assignment_result->fetch_assoc();

if (!$assignment) {
    echo "කණගාටුයි! එවැනි Assignment එකක් සොයාගත නොහැක.";
    exit();
}

// ඩෙඩ්ලයින් එක පහු වෙලාද කියා නැවතත් පරීක්ෂා කිරීම
$current_date = date('Y-m-d');
if ($current_date > $assignment['deadline_date']) {
    echo "කණගාටුයි! මෙම Assignment එක සඳහා ඇති කාලය අවසන් වී ඇත.";
    exit();
}

$msg = "";
$msg_class = "";

// 3. File Upload Logic එක ක්‍රියාත්මක වීම
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_btn'])) {
    
    $target_dir = "uploads/"; 
    $file_name = basename($_FILES["assignment_file"]["name"]);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // අලුත් File Name එකක් සෑදීම
    $new_file_name = "Sub_" . $assignment_id . "_" . $user_id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $new_file_name;

    // ⚡ වෙනස් කළ කොටස: PDF, DOC සහ DOCX කියන වර්ග 3ම ඇතුළත් කර ඇත
    $allowed_extensions = array("pdf", "doc", "docx");

    if (!in_array($file_ext, $allowed_extensions)) {
        $msg = "කණගාටුයි! PDF, DOC හෝ DOCX Files පමණක් Upload කිරීමට අවසර ඇත.";
        $msg_class = "alert-danger";
    } 
    // File Size එක 5MB වලට වඩා වැඩිදැයි බැලීම
    elseif ($_FILES["assignment_file"]["size"] > 5242880) {
        $msg = "කණගාටුයි! File එකේ ප්‍රමාණය 5MB වලට වඩා අඩු විය යුතුය.";
        $msg_class = "alert-danger";
    } 
    // කිසිදු ප්‍රශ්නයක් නැත්නම් File එක Server එකට සේව් කිරීම
    else {
        if (move_uploaded_file($_FILES["assignment_file"]["tmp_name"], $target_file)) {
            
            // Database එකට දත්ත ඇතුළත් කිරීම
            $insert_sql = "INSERT INTO submissions (assignment_id, student_id, file_name) 
                           VALUES ('$assignment_id', '$user_id', '$new_file_name')";
            
            if ($conn->query($insert_sql) === TRUE) {
                $msg = "Assignment එක සාර්ථකව Upload කරන ලදී!";
                $msg_class = "alert-success";
                header("Refresh:2; url=student_dashboard.php");
            } else {
                $msg = "Database එකට ඇතුළත් කිරීමේදී දෝෂයක් ඇති විය: " . $conn->error;
                $msg_class = "alert-danger";
            }
        } else {
            $msg = "කණගාටුයි! File එක Upload කිරීමේදී සාමාන්‍ය දෝෂයක් සිදු විය.";
            $msg_class = "alert-danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment - System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 40px; display: flex; justify-content: center; }
        .upload-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h2 { color: #2c3e50; margin-top: 0; font-size: 22px; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
        .info { background: #eef2f3; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; color: #555; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        .form-group input[type="file"] { width: 100%; padding: 10px; border: 1px dashed #3498db; background: #f8fafc; border-radius: 5px; box-sizing: border-box; }
        .btn-submit { background: #3498db; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; font-size: 15px; }
        .btn-submit:hover { background: #2980b9; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #7f8c8d; text-decoration: none; font-size: 14px; }
        .btn-back:hover { color: #333; }
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-size: 14px; font-weight: bold; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

<div class="upload-card">
    <h2>Assignment එක bhara dima (File Upload)</h2>
    
    <div class="info">
        <strong>මාතෘකාව:</strong> <?php echo htmlspecialchars($assignment['title']); ?><br>
        <strong>අවසාන දිනය:</strong> <?php echo htmlspecialchars($assignment['deadline_date']); ?><br>
        <span style="color: #e74c3c;">* අවසර ඇත්තේ PDF, DOC හෝ DOCX ගොනු සඳහා පමණි (Max: 5MB).</span>
    </div>

    <?php if($msg != ""): ?>
        <div class="alert <?php echo $msg_class; ?>"><?php echo $msg; ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>ඔබගේ File එක තෝරන්න (PDF / Word):</label>
            <input type="file" name="assignment_file" accept=".pdf, .doc, .docx" required>
        </div>
        <button type="submit" name="upload_btn" class="btn-submit">පද්ධතියට ඇතුළත් කරන්න (Upload)</button>
        <a href="student_dashboard.php" class="btn-back">ආපසු Dashboard වෙත යන්න</a>
    </form>
</div>

</body>
</html>