<?php
include 'db.php';
session_start();

// 1. පරිශීලකයා ලොග් වී නැත්නම් හෝ ශිෂ්‍යයෙක් නොවෙයි නම් ලොගින් පිටුවට හරවා යැවීම
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // මෙහිදී session එකේ එන id එක student_id ලෙස පාවිච්චි කරයි

// 2. ශිෂ්‍යයාගේ පෞද්ගලික විස්තර Database එකෙන් ලබා ගැනීම
$student_sql = "SELECT * FROM student_details WHERE user_id = '$user_id'";
$student_result = $conn->query($student_sql);
$student = $student_result->fetch_assoc();

// 3. Profile එක Update කිරීමට අදාළ Logic එක
$profile_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $address = $conn->real_escape_string(trim($_POST['address']));
    $whatsapp_no = $conn->real_escape_string(trim($_POST['whatsapp_no']));
    
    $update_sql = "UPDATE student_details SET address='$address', whatsapp_no='$whatsapp_no' WHERE user_id='$user_id'";
    if ($conn->query($update_sql) === TRUE) {
        $profile_msg = "විස්තර සාර්ථකව යාවත්කාලීන (Update) කරන ලදී!";
        header("Refresh:1");
    } else {
        $profile_msg = "දෝෂයකි: " . $conn->error;
    }
}

// 4. ඔයාගේ Database එකේ තියෙන Columns වලට අනුව හදපු Query එක (s.student_id ලෙස වෙනස් කර ඇත)
$assignments_sql = "SELECT a.id AS assignment_id, a.title, a.deadline_date, 
                    s.id AS submission_id, s.marks, s.comments 
                    FROM assignments a 
                    LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = '$user_id'";
$assignments_result = $conn->query($assignments_sql);
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Assignment System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .btn-logout { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn-logout:hover { background: #c0392b; }
        
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .dashboard-grid { grid-template-columns: 1fr; } }
        
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h3 { margin-top: 0; color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #dbdbdb; }
        th, td { padding: 12px; text-align: left; }
        th { background-color: #f8f9fa; color: #333; }
        
        .status-badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .pending { background: #ffeaa7; color: #b7791f; }
        .submitted { background: #badc58; color: #6ab04c; }
        .missing { background: #ff7675; color: #fff; }
        
        .btn-action { background: #3498db; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 13px; }
        .btn-action:hover { background: #2980b9; }
        .btn-disabled { background: #bdc3c7; color: #7f8c8d; padding: 6px 12px; border-radius: 4px; font-size: 13px; text-decoration: none; pointer-events: none; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #666; font-weight: 600; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn-update { background: #2ecc71; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; }
        .btn-update:hover { background: #27ae60; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h1>ශිෂ්‍ය පාලක පැනලය (Student Dashboard)</h1>
            <small>ආයුබෝවන්, <?php echo htmlspecialchars($student['initial_name'] ?? $_SESSION['email']); ?> !</small>
        </div>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>

    <div class="dashboard-grid">
        
        <div class="main-content">
            <div class="card">
                <h3>නවතම Assignment සහ භාරදීම් (View & Submit Assignments)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Assignment මාතෘකාව</th>
                            <th>අවසාන දිනය (Deadline)</th>
                            <th>තත්ත්වය (Status)</th>
                            <th>ක්‍රියාව (Action)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $current_date = date('Y-m-d');
                        if ($assignments_result && $assignments_result->num_rows > 0): 
                            while($row = $assignments_result->fetch_assoc()): 
                                $deadline = $row['deadline_date'];
                                // සබ්මිෂන් ටේබල් එකේ ID එකක් තිබේ නම් එය භාර දී ඇත
                                $is_submitted = !is_null($row['submission_id']); 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo $deadline; ?></td>
                                <td>
                                    <?php
                                    if ($is_submitted) {
                                        echo '<span class="status-badge submitted">භාර දී ඇත</span>';
                                    } elseif ($current_date > $deadline && !$is_submitted) {
                                        echo '<span class="status-badge missing">Missing (පසුගිය දිනය)</span>';
                                    } else {
                                        echo '<span class="status-badge pending">භාර දී නැත</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($is_submitted): ?>
                                        <span style="color: #7f8c8d; font-size:13px;">සම්පූර්ණයි</span>
                                    <?php elseif ($current_date > $deadline && !$is_submitted): ?>
                                        <a class="btn-disabled">නොහැක (Time Out)</a>
                                    <?php else: ?>
                                        <a href="submit_assignment.php?id=<?php echo $row['assignment_id']; ?>" class="btn-action">File Upload</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <tr><td colspan="4" style="text-align:center;">තවමත් කිසිදු Assignment එකක් පද්ධතියට ඇතුළත් කර නැත.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3>ප්‍රතිඵල සහ ගුරු අදහස් (View Grades & Comments)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Assignment මාතෘකාව</th>
                            <th>ලබාගත් ලකුණු</th>
                            <th>ගුරු අදහස් (Lecturer Comments)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if($assignments_result) { $assignments_result->data_seek(0); }
                        $has_grades = false;
                        
                        if ($assignments_result && $assignments_result->num_rows > 0):
                            while($row = $assignments_result->fetch_assoc()):
                                $is_submitted = !is_null($row['submission_id']);
                                // භාර දී ඇති සහ ලකුණු දමා ඇති ඒවා පමණක් පෙන්වයි
                                if($is_submitted && !is_null($row['marks'])):
                                    $has_grades = true;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td style="font-weight: bold; color: #2ecc71;"><?php echo htmlspecialchars($row['marks']); ?>%</td>
                                <td><?php echo htmlspecialchars($row['comments'] ?? 'අදහස් දක්වා නැත.'); ?></td>
                            </tr>
                        <?php 
                                endif;
                            endwhile;
                        endif; 
                        
                        if(!$has_grades):
                        ?>
                            <tr><td colspan="3" style="text-align:center;">ලකුණු ලබාදුන් Assignment කිසිවක් තවමත් නොමැත.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sidebar">
            <div class="card">
                <h3>මගේ විස්තර (Profile Management)</h3>
                <?php if($profile_msg != ""): ?>
                    <div class="alert-success"><?php echo $profile_msg; ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label>ශිෂ්‍ය අංකය (Index No)</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['index_no'] ?? ''); ?>" disabled style="background: #eee;">
                    </div>
                    <div class="form-group">
                        <label>සම්පූර්ණ නම</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['full_name'] ?? ''); ?>" disabled style="background: #eee;">
                    </div>
                    <div class="form-group">
                        <label>ලිපිනය (Address)</label>
                        <textarea name="address" rows="3" required><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>WhatsApp Number</label>
                        <input type="text" name="whatsapp_no" value="<?php echo htmlspecialchars($student['whatsapp_no'] ?? ''); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn-update">විස්තර යාවත්කාලීන කරන්න</button>
                </form>
            </div>
        </div>

    </div>
</body>
</html>