<?php
session_start();
include 'database.php';

// Restrict access
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle user level update
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user_id'], $_POST['new_user_level'])) {
    $update_id = intval($_POST['update_user_id']);
    $new_level = $_POST['new_user_level'] === 'admin' ? 'admin' : 'normal';
    $stmt = $conn->prepare("UPDATE tbl_user SET user_level = ? WHERE fld_user_num = ?");
    $stmt->execute([$new_level, $update_id]);
    $successMessage = "User role updated successfully!";
}

// Get profile picture
$stmt = $conn->prepare("SELECT profile_picture FROM tbl_user WHERE fld_user_num = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);
$profile_picture = isset($userData['profile_picture']) ? $userData['profile_picture'] : null;

// Count total users
$totalUsers = $conn->query("SELECT COUNT(*) FROM tbl_user")->fetchColumn();

// Count total forum posts
$totalPosts = $conn->query("SELECT COUNT(*) FROM tbl_forum")->fetchColumn();

// Count total programs
$totalPrograms = $conn->query("SELECT COUNT(*) FROM tbl_wecare")->fetchColumn();

// Fetch all users for display
$stmt = $conn->query("SELECT fld_user_num, fld_user_fname, fld_user_lname, username, fld_user_email, fld_user_phone, user_level FROM tbl_user ORDER BY fld_user_num ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<style>
:root {
  --dark-green: #26422f;
  --light-green: #62826b;
  --white: #ffffff;
  --light-neutral: #f5f5f7;
}
body {
  background-color: var(--light-neutral);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  margin: 0;
  color: var(--dark-green);
}
header {
  background-color: var(--white);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  position: relative;
}
nav a {
  color: var(--dark-green);
  text-decoration: none;
  margin-right: 20px;
  font-weight: 500;
}
.menu-icon {
  cursor: pointer;
  display: flex;
  flex-direction: column;
}
.menu-icon span {
  width: 25px;
  height: 3px;
  background-color: var(--dark-green);
  margin: 3px 0;
  transition: 0.3s ease;
}
.dropdown-menu {
  opacity: 0;
  transform: translateY(-10px);
  pointer-events: none;
  position: absolute;
  right: 20px;
  top: 60px;
  background-color: var(--white);
  border-radius: 5px;
  box-shadow: 0 0 10px rgba(0,0,0,0.2);
  transition: all 0.3s ease;
  z-index: 10;
}
.dropdown-menu.show {
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
}
.dropdown-menu a {
  display: block;
  padding: 10px 20px;
  text-decoration: none;
  color: var(--dark-green);
}
.dropdown-menu a:hover {
  background-color: #f0f0f0;
}
.container {
  max-width: 1000px;
  margin: 40px auto;
  background: var(--white);
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 10px 20px rgba(0,0,0,0.05);
}
.profile-pic {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--dark-green);
  margin-bottom: 10px;
}
.summary {
  display: flex;
  justify-content: space-around;
  flex-wrap: wrap;
  margin-top: 30px;
}
.card {
  background: var(--white);
  padding: 20px;
  border-radius: 10px;
  width: 200px;
  margin: 10px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
  text-align: center;
}
.card h3 {
  margin: 0;
  font-size: 18px;
}
.card p {
  font-size: 24px;
  font-weight: bold;
  margin: 10px 0 0;
  color: var(--light-green);
}
h2, h3 { margin-top: 0; }
table {
  width: 100%;
  margin-top: 30px;
  border-collapse: collapse;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
th, td {
  padding: 12px;
  border-bottom: 1px solid #e0e0e5;
  text-align: left;
}
th {
  background-color: #f9f9f9;
}
.btn {
  margin-top: 20px;
  display: inline-block;
  padding: 10px 20px;
  background-color: var(--light-green);
  color: var(--white);
  text-decoration: none;
  border-radius: 5px;
}
.btn:hover {
  background-color: #4f6f5a;
}
.save-btn {
  background-color: var(--light-green);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 6px 14px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}
.save-btn:hover {
  background-color: #4f6f5a;
  transform: translateY(-1px);
}
.alert-success {
  background-color: #d1f5d3;
  color: #276738;
  padding: 12px 20px;
  border: 1px solid #a0e7a4;
  border-radius: 8px;
  margin-bottom: 20px;
  text-align: center;
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
    <span></span><span></span><span></span>
  </div>
  <div class="dropdown-menu" id="dropdownMenu">
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="logout.php">Logout</a>
  </div>
</header>

<div class="container">
  <?php if ($profile_picture): ?>
    <img src="uploads/<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture" class="profile-pic">
  <?php else: ?>
    <img src="default-profile.png" alt="No Profile" class="profile-pic">
  <?php endif; ?>

  <h2>Welcome, Admin <?= htmlspecialchars($username) ?>!</h2>
  <p>This is your admin dashboard.</p>

  <?php if (!empty($successMessage)): ?>
    <div class="alert-success"><?= htmlspecialchars($successMessage) ?></div>
  <?php endif; ?>

  <div class="summary">
    <div class="card">
      <h3>Total Users</h3>
      <p><?= $totalUsers ?></p>
    </div>
    <div class="card">
      <h3>Forum Posts</h3>
      <p><?= $totalPosts ?></p>
    </div>
    <div class="card">
      <h3>Programs</h3>
      <p><?= $totalPrograms ?></p>
    </div>
  </div>

  <h3>All Users</h3>
  <table>
    <tr>
      <th>ID</th>
      <th>Username</th>
      <th>Full Name</th>
      <th>Email</th>
      <th>Phone</th>
      <th>Role</th>
    </tr>
    <?php foreach ($users as $user): ?>
      <tr>
        <td><?= htmlspecialchars($user['fld_user_num']) ?></td>
        <td><?= htmlspecialchars($user['username']) ?></td>
        <td><?= htmlspecialchars($user['fld_user_fname'] . ' ' . $user['fld_user_lname']) ?></td>
        <td><?= htmlspecialchars($user['fld_user_email']) ?></td>
        <td><?= htmlspecialchars($user['fld_user_phone']) ?></td>
        <td>
          <form method="POST" action="admin_dashboard.php" style="display:flex;gap:5px;align-items:center;">
            <input type="hidden" name="update_user_id" value="<?= $user['fld_user_num'] ?>">
            <select name="new_user_level" style="padding: 6px; border-radius: 6px; border: 1px solid #ccc;">
              <option value="normal" <?= $user['user_level'] === 'normal' ? 'selected' : '' ?>>normal</option>
              <option value="admin" <?= $user['user_level'] === 'admin' ? 'selected' : '' ?>>admin</option>
            </select>
            <button class="save-btn" type="submit">Save</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <a href="edit_profile.php" class="btn">Edit Profile</a>
</div>

<script>
function toggleMenu() {
  const menu = document.getElementById("dropdownMenu");
  menu.classList.toggle("show");
}
window.onclick = function(event) {
  if (!event.target.closest('.menu-icon')) {
    document.getElementById("dropdownMenu").classList.remove("show");
  }
};
</script>

</body>
</html>
