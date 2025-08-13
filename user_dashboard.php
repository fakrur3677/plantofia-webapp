<?php
session_start();

if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] !== 'normal') {
    header('Location: login.php');
    exit();
}

include 'database.php';

$user_id = $_SESSION['user_num'];
$username = $_SESSION['username'];

// Handle deletion if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_program_id'])) {
    $delete_program_id = $_POST['delete_program_id'];

    // Delete registration only if it belongs to this user
    $deleteStmt = $conn->prepare("DELETE FROM tbl_registration WHERE user_id = ? AND program_id = ?");
    $deleteStmt->execute([$user_id, $delete_program_id]);

    // Redirect to prevent form resubmission
    header("Location: user_dashboard.php");
    exit();
}

// Fetch profile picture from correct column
$stmt = $conn->prepare("SELECT profile_picture FROM tbl_user WHERE fld_user_num = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profile_pic = isset($user['profile_picture']) ? $user['profile_picture'] : null;

// Fetch registered programs
$sql = "SELECT w.* FROM tbl_registration r 
        JOIN tbl_wecare w ON r.program_id = w.program_id 
        WHERE r.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$registeredPrograms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch forum posts (with image path directly from database)
$forumStmt = $conn->prepare("SELECT post_id, content, post_image, created_at FROM tbl_forum WHERE user_id = ? ORDER BY created_at DESC");
$forumStmt->execute([$user_id]);
$posts = $forumStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard</title>
  <style>
    :root {
      --dark-green: #26422f; /* Dark Green for text */
      --light-neutral: #F9F9F9; /* Light Neutral Background */
      --white: #ffffff;
      --primary-font: 'Poppins', sans-serif;
    }
    body {
      background-color: var(--light-neutral);
      font-family: var(--primary-font);
      margin: 0;
      padding: 0;
      color: var(--dark-green);
    }
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem;
      background-color: var(--white); /* White navbar */
      position: relative;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Subtle shadow for navbar */
    }
    nav {
      display: flex;
      gap: 1.5rem;
    }
    nav a {
      color: var(--dark-green); /* Dark green font */
      text-decoration: none;
      font-weight: 500;
    }
    .menu-icon {
      display: block;
      width: 25px;
      height: 20px;
      position: relative;
      cursor: pointer;
    }
    .menu-icon span {
      position: absolute;
      height: 3px;
      background-color: var(--dark-green); /* Dark green for menu icon */
      width: 100%;
      left: 0;
      transition: 0.3s;
    }
    .menu-icon span:nth-child(1) { top: 0; }
    .menu-icon span:nth-child(2) { top: 8px; }
    .menu-icon span:nth-child(3) { top: 16px; }

    .dropdown-menu {
      display: none;
      position: absolute;
      top: 70px;
      right: 30px;
      background-color: var(--white); /* White dropdown */
      padding: 1rem;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.2);
      z-index: 10;
    }
    .dropdown-menu a {
      display: block;
      color: var(--dark-green); /* Dark green font for dropdown */
      text-decoration: none;
      margin-bottom: 10px;
    }

    .profile-wrapper {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 900px;
      margin: 30px auto 0;
      padding: 0 20px;
    }
    .profile-left h2 {
      color: var(--dark-green); /* Dark green for title */
      margin-bottom: 10px;
      font-size: 2rem;
    }
    .profile-pic {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--dark-green); /* Dark green border */
    }
    .edit-btn {
      background: var(--dark-green); /* Dark green for button */
      color: var(--white);
      padding: 10px 18px;
      border-radius: 6px;
      text-decoration: none;
      display: inline-block;
      margin-top: 15px;
      transition: background-color 0.3s;
    }
    .edit-btn:hover {
      background-color: var(--white); /* White hover background */
      color: var(--dark-green);
    }

    .box {
      background: var(--white); /* White background for content boxes */
      padding: 25px;
      border-radius: 12px;
      max-width: 900px;
      margin: 30px auto;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Soft shadow for content boxes */
    }

    h3 {
      color: var(--dark-green); /* Dark green for heading */
      margin-bottom: 10px;
      font-size: 1.8rem;
    }

    .program-card, .forum-card {
      background: var(--white); /* White for program and forum cards */
      color: var(--dark-green); /* Dark green text */
      padding: 15px;
      border-radius: 8px;
      margin-top: 15px;
      transition: transform 0.3s;
      border: 1px solid var(--light-neutral); /* Light neutral border */
      position: relative;
    }
    .program-card:hover, .forum-card:hover {
      transform: translateY(-5px);
    }

    .forum-card img {
      max-width: 200px;
      margin-top: 10px;
      border-radius: 6px;
    }

    .forum-card small {
      display: block;
      margin-top: 6px;
      color: #e0e0e0;
    }

    /* Delete button styling */
    .delete-btn {
      background: #c0392b;
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      position: absolute;
      top: 15px;
      right: 15px;
      transition: background-color 0.3s;
    }
    .delete-btn:hover {
      background-color: #e74c3c;
    }
  </style>
</head>
<body>
  <header>
 <nav>
  <a href="index.php">Home</a>
  <?php if (isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'admin'): ?>
    <a href="we-care_crud.php">We Care CRUD</a>
  <?php else: ?>
    <a href="we-care.php">We Care</a>
  <?php endif; ?>
  <a href="forum.php">Forum Komuniti</a>
  <?php if (isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'admin'): ?>
    <a href="plant_diary_crud.php">Plant Diary CRUD</a>
  <?php else: ?>
    <a href="plant-diary.php">Plant Diary</a>
  <?php endif; ?>
</nav>


    <div class="menu-icon" onclick="toggleMenu()">
      <span></span>
      <span></span>
      <span></span>
    </div>
    <div class="dropdown-menu" id="dropdownMenu">
      <a href="user_dashboard.php">Dashboard</a>
      <a href="logout.php">Logout</a>
    </div>
  </header>

  <div class="profile-wrapper">
    <div class="profile-left">
      <h2>Welcome, <?= htmlspecialchars($username); ?>!</h2>
      <a class="edit-btn" href="edit_profile.php">Edit Profile</a>
    </div>
    <?php if ($profile_pic): ?>
      <img class="profile-pic" src="uploads/<?= htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
    <?php else: ?>
      <img class="profile-pic" src="default-user.jpg" alt="No Profile Picture">
    <?php endif; ?>
  </div>

  <div class="box">
    <h3>Your Registered Programs</h3>
    <?php if (count($registeredPrograms) > 0): ?>
      <?php foreach ($registeredPrograms as $program): ?>
        <div class="program-card">
          <h3><?= htmlspecialchars($program['program_title']) ?></h3>
          <p><strong>Date:</strong> <?= date("d/m/Y", strtotime($program['program_date'])) ?></p>
          <p><strong>Location:</strong> <?= htmlspecialchars($program['program_location']) ?></p>

          <!-- Delete form -->
          <form method="post" onsubmit="return confirm('Are you sure you want to delete this registration?');">
            <input type="hidden" name="delete_program_id" value="<?= htmlspecialchars($program['program_id']) ?>">
            <button type="submit" class="delete-btn">Delete</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>You haven't registered for any programs yet.</p>
    <?php endif; ?>
  </div>

  <div class="box">
    <h3>Your Forum Posts</h3>
    <?php if ($posts): ?>
      <?php foreach ($posts as $post): ?>
        <div class="forum-card">
          <p><?= htmlspecialchars($post['content']) ?></p>
          <?php if (!empty($post['post_image'])): ?>
            <img src="<?= htmlspecialchars($post['post_image']) ?>" alt="Post Image" />
          <?php endif; ?>
          <small><?= date('d/m/Y h:i A', strtotime($post['created_at'])) ?></small>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No posts yet.</p>
    <?php endif; ?>
  </div>

  <script>
    function toggleMenu() {
      var menu = document.getElementById('dropdownMenu');
      menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }
    window.onclick = function(event) {
      if (!event.target.closest('.menu-icon')) {
        document.getElementById('dropdownMenu').style.display = 'none';
      }
    };
  </script>
</body>
</html>
