<?php
session_start();
include 'database.php';

header('Content-Type: application/json');

// Session check
if (!isset($_SESSION['user_num'])) {
    echo json_encode(array('success' => false, 'message' => 'Not logged in'));
    exit;
}

$user_id = $_SESSION['user_num'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $plant_id    = intval(isset($_POST['plant_id']) ? $_POST['plant_id'] : 0);
    $activity    = trim(isset($_POST['activity']) ? $_POST['activity'] : '');
    $observation = trim(isset($_POST['observation']) ? $_POST['observation'] : '');
    $note        = trim(isset($_POST['note']) ? $_POST['note'] : '');

    // Check for completeness
    if (!$plant_id || !$activity || !$observation || !$note) {
        echo json_encode(array('success' => false, 'message' => 'Please fill in all fields.'));
        exit;
    }

    // Check if the plant belongs to the user
    $check = $conn->prepare("SELECT * FROM tbl_user_plants WHERE plant_id = ? AND fld_user_num = ?");
    $check->execute(array($plant_id, $user_id));
    $plant = $check->fetch();

    if (!$plant) {
        echo json_encode(array('success' => false, 'message' => 'Unauthorized'));
        exit;
    }

    // Insert into diary
    $stmt = $conn->prepare("INSERT INTO tbl_plant_diary (plant_id, fld_user_num, activity, observation, note) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(array($plant_id, $user_id, $activity, $observation, $note));

    // Return success and new entry ID with nice modern message
    $entry_id = $conn->lastInsertId();
    echo json_encode(array(
        'success' => true,
        'message' => '✅ Activity "' . htmlspecialchars($activity, ENT_QUOTES) . '" saved successfully to your plant diary!',
        'entry_id' => $entry_id,
        'activity' => htmlspecialchars($activity, ENT_QUOTES),
        'observation' => htmlspecialchars($observation, ENT_QUOTES),
        'note' => htmlspecialchars($note, ENT_QUOTES)
    ));
    exit;
}
?>