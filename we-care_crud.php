<?php
session_start();
include 'database.php';

if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle adding program
if (isset($_POST['add_program'])) {
    $stmt = $conn->prepare("
        INSERT INTO tbl_wecare (program_title, program_date, program_time, program_location, organizer, participant_limit, instagram_handle, facebook_handle, sdg, program_description) 
        VALUES (:title, :date, :time, :location, :organizer, :limit, :instagram, :facebook, :sdg, :desc)
    ");
    $stmt->execute([
        ':title' => $_POST['program_title'],
        ':date' => $_POST['program_date'],
        ':time' => $_POST['program_time'],
        ':location' => $_POST['program_location'],
        ':organizer' => $_POST['organizer'],
        ':limit' => $_POST['participant_limit'],
        ':instagram' => $_POST['instagram_handle'],
        ':facebook' => $_POST['facebook_handle'],
        ':sdg' => $_POST['sdg'],
        ':desc' => $_POST['program_description']
    ]);
    header('Location: we-care_crud.php?success=1');
    exit();
}

// Edit
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM tbl_wecare WHERE program_id = :id");
    $stmt->execute([':id' => $edit_id]);
    $edit_program = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update
if (isset($_POST['update_program'])) {
    $stmt = $conn->prepare("
        UPDATE tbl_wecare 
        SET program_title = :title, program_date = :date, program_time = :time, 
            program_location = :location, organizer = :organizer, participant_limit = :limit, 
            instagram_handle = :instagram, facebook_handle = :facebook, sdg = :sdg, program_description = :desc 
        WHERE program_id = :id
    ");
    $stmt->execute([
        ':title' => $_POST['program_title'],
        ':date' => $_POST['program_date'],
        ':time' => $_POST['program_time'],
        ':location' => $_POST['program_location'],
        ':organizer' => $_POST['organizer'],
        ':limit' => $_POST['participant_limit'],
        ':instagram' => $_POST['instagram_handle'],
        ':facebook' => $_POST['facebook_handle'],
        ':sdg' => $_POST['sdg'],
        ':desc' => $_POST['program_description'],
        ':id' => $_POST['program_id']
    ]);
    header('Location: we-care_crud.php?updated=1');
    exit();
}

// Delete
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM tbl_wecare WHERE program_id = :id");
    $stmt->execute([':id' => $_GET['delete']]);
    header('Location: we-care_crud.php?deleted=1');
    exit();
}

// CSV Export
if (isset($_GET['export_csv'])) {
    $program_id = $_GET['export_csv'];
    $stmt = $conn->prepare("
        SELECT u.username, u.fld_user_email, u.fld_user_phone, r.registered_at
        FROM tbl_registration r
        JOIN tbl_user u ON r.user_id = u.fld_user_num
        WHERE r.program_id = ?
    ");
    $stmt->execute([$program_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="participants_program_' . $program_id . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Username', 'Email', 'Phone', 'Registered At']);
    foreach ($participants as $p) {
        fputcsv($output, [$p['username'], $p['fld_user_email'], $p['fld_user_phone'], $p['registered_at']]);
    }
    fclose($output);
    exit();
}

// Fetch all programs
$stmt = $conn->query("SELECT * FROM tbl_wecare ORDER BY program_date ASC");
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch participants if requested
$participants = [];
if (isset($_GET['view_participants'])) {
    $program_id = $_GET['view_participants'];
    $stmt = $conn->prepare("
        SELECT u.username, u.fld_user_email, u.fld_user_phone, r.registered_at
        FROM tbl_registration r
        JOIN tbl_user u ON r.user_id = u.fld_user_num
        WHERE r.program_id = ?
    ");
    $stmt->execute([$program_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>We Care - Manage Programs</title>
<style>
    body { background-color: #f5f5f7; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; color: #1d1d1f; }
    header { background-color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; }
    nav a { color: #1d1d1f; text-decoration: none; margin-right: 20px; font-weight: 500; }
    .container { max-width: 1000px; margin: 40px auto; background: #ffffff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    input, textarea { width: 100%; margin-bottom: 15px; padding: 12px; border-radius: 12px; border: 1px solid #d0d0d5; }
    button, .btn { background: #0071e3; color: white; border: none; padding: 10px 20px; border-radius: 12px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block; }
    button:hover, .btn:hover { background: #005bb5; }
    table { width: 100%; margin-top: 20px; border-collapse: collapse; border-radius: 12px; overflow: hidden; }
    th, td { padding: 12px; border-bottom: 1px solid #e0e0e5; }
    th { background-color: #f0f0f5; }
    h2, h3 { margin-top: 0; }
    .message { color: green; margin-bottom: 15px; }
</style>
</head>
<body>

<header>
    <nav>
        <a href="index.php">Home</a>
        <a href="we-care_crud.php">We Care CRUD</a>
        <a href="forum.php">Forum Komuniti</a>
        <a href="plant_diary_crud.php">Plant Diary CRUD</a>
    </nav>
    <div>
        <a href="admin_dashboard.php" class="btn" style="background: none; color: #1d1d1f; margin-right:10px;">Dashboard</a>
        <a href="logout.php" class="btn" style="background: none; color: #1d1d1f;">Logout</a>
    </div>
</header>

<div class="container">
    <h2>Manage We Care Programs</h2>

    <?php if (isset($_GET['success'])): ?>
        <p class="message">Program added successfully!</p>
    <?php elseif (isset($_GET['updated'])): ?>
        <p class="message">Program updated successfully!</p>
    <?php elseif (isset($_GET['deleted'])): ?>
        <p class="message">Program deleted successfully!</p>
    <?php endif; ?>

    <h3><?= isset($edit_program) ? 'Edit Program' : 'Add New Program'; ?></h3>
    <form method="POST">
        <input type="text" name="program_title" placeholder="Program Title" value="<?= isset($edit_program) ? htmlspecialchars($edit_program['program_title']) : ''; ?>" required>
        <input type="date" name="program_date" value="<?= isset($edit_program) ? htmlspecialchars($edit_program['program_date']) : ''; ?>" required>
        <input type="text" name="program_time" placeholder="Time" value="<?= isset($edit_program) ? htmlspecialchars($edit_program['program_time']) : ''; ?>" required>
        <input type="text" name="program_location" placeholder="Location" value="<?= isset($edit_program) ? htmlspecialchars($edit_program['program_location']) : ''; ?>" required>
        <input type="text" name="organizer" placeholder="Organizer" value="<?= isset($edit_program) ? htmlspecialchars($edit_program['organizer']) : ''; ?>" required>
        <input type="number" name="participant_limit" placeholder="Participant Limit" value="<?= isset($edit_program) ? (int)$edit_program['participant_limit'] : ''; ?>" required>
        <input type="text" name="instagram_handle" placeholder="Instagram Handle" value="<?= isset($edit_program) ? htmlspecialchars($edit_program['instagram_handle']) : ''; ?>">
        <input type="text" name="facebook_handle" placeholder="Facebook Handle" value="<?= isset($edit_program) ? htmlspecialchars($edit_program['facebook_handle']) : ''; ?>">
        <input type="text" name="sdg" placeholder="SDGs Involved" value="<?= isset($edit_program) ? htmlspecialchars($edit_program['sdg']) : ''; ?>">
        <textarea name="program_description" placeholder="Program Description"><?= isset($edit_program) ? htmlspecialchars($edit_program['program_description']) : ''; ?></textarea>

        <?php if (isset($edit_program)): ?>
            <input type="hidden" name="program_id" value="<?= htmlspecialchars($edit_program['program_id']); ?>">
            <button type="submit" name="update_program">Update Program</button>
        <?php else: ?>
            <button type="submit" name="add_program">Add Program</button>
        <?php endif; ?>
    </form>

    <h3>Program List</h3>
    <table>
        <tr>
            <th>Title</th>
            <th>Date</th>
            <th>Location</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($programs as $program): ?>
        <tr>
            <td><?= htmlspecialchars($program['program_title']); ?></td>
            <td><?= htmlspecialchars($program['program_date']); ?></td>
            <td><?= htmlspecialchars($program['program_location']); ?></td>
            <td>
                <a href="?edit=<?= htmlspecialchars($program['program_id']); ?>">Edit</a> |
                <a href="?delete=<?= htmlspecialchars($program['program_id']); ?>" onclick="return confirm('Are you sure?');">Delete</a> |
                <a href="?view_participants=<?= htmlspecialchars($program['program_id']); ?>">View Participants</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if (!empty($participants)): ?>
    <h3>Participants for Program ID: <?= htmlspecialchars($program_id); ?></h3>
    <a class="btn" href="?export_csv=<?= htmlspecialchars($program_id); ?>">Download CSV</a>
    <table>
        <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Registered At</th>
        </tr>
        <?php foreach ($participants as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['username']); ?></td>
            <td><?= htmlspecialchars($p['fld_user_email']); ?></td>
            <td><?= htmlspecialchars($p['fld_user_phone']); ?></td>
            <td><?= htmlspecialchars($p['registered_at']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php elseif (isset($_GET['view_participants'])): ?>
        <p>No participants yet for this program.</p>
    <?php endif; ?>
</div>

</body>
</html>
