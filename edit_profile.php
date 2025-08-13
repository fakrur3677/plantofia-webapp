<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'database.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM tbl_user WHERE fld_user_num = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $fname = $_POST['fld_user_fname'];
    $lname = $_POST['fld_user_lname'];
    $email = $_POST['fld_user_email'];
    $phone = $_POST['fld_user_phone'];
    $gender = $_POST['fld_user_gender'];
    $newPassword = $_POST['new_password'];
    $hashedPassword = !empty($newPassword) ? password_hash($newPassword, PASSWORD_DEFAULT) : $user['password'];

    // Handle profile picture upload
    $profilePicName = $user['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['profile_picture']['tmp_name'];
        $original_name = basename($_FILES['profile_picture']['name']);
        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
        $new_filename = uniqid() . "." . $ext;
        move_uploaded_file($tmp_name, "uploads/" . $new_filename);
        $profilePicName = $new_filename;
    }

    // Update user data
    $update = $conn->prepare("UPDATE tbl_user SET username = ?, password = ?, profile_picture = ?, fld_user_fname = ?, fld_user_lname = ?, fld_user_email = ?, fld_user_phone = ?, fld_user_gender = ? WHERE fld_user_num = ?");
    if ($update->execute([$username, $hashedPassword, $profilePicName, $fname, $lname, $email, $phone, $gender, $user_id])) {
        $_SESSION['username'] = $username;
        $success = "Profile updated successfully.";
        // Refresh user data
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Update failed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile</title>
<style>
  body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background-color: #f5f5f7;
    color: #26422f;
    padding: 40px;
  }
  .container {
    max-width: 600px;
    background: #ffffff;
    padding: 40px;
    border-radius: 20px;
    margin: auto;
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
  }
  label {
    font-weight: 500;
    display: block;
    margin-top: 15px;
  }
  input[type="text"], input[type="password"], input[type="email"], input[type="file"], select {
    width: 100%;
    padding: 10px;
    margin-top: 6px;
    border: 1px solid #ccc;
    border-radius: 10px;
  }
  button {
    margin-top: 20px;
    padding: 12px 20px;
    background-color: #26422f;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
  }
  .message {
    margin-top: 10px;
    font-weight: bold;
  }
  img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin-top: 10px;
  }
</style>
</head>
<body>
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


  <div class="container">
    <h2>Edit Profile</h2>

    <?php if ($success): ?>
      <p class="message" style="color: green;"><?= $success ?></p>
    <?php elseif ($error): ?>
      <p class="message" style="color: red;"><?= $error ?></p>
    <?php endif; ?>

    <form action="edit_profile.php" method="post" enctype="multipart/form-data">
      <label>Username</label>
      <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

      <label>First Name</label>
      <input type="text" name="fld_user_fname" value="<?= htmlspecialchars($user['fld_user_fname']) ?>" required>

      <label>Last Name</label>
      <input type="text" name="fld_user_lname" value="<?= htmlspecialchars($user['fld_user_lname']) ?>" required>

      <label>Email</label>
      <input type="email" name="fld_user_email" value="<?= htmlspecialchars($user['fld_user_email']) ?>" required>

      <label>Phone</label>
      <input type="text" name="fld_user_phone" value="<?= htmlspecialchars($user['fld_user_phone']) ?>">

      <label>Gender</label>
      <select name="fld_user_gender" required>
        <option value="Male" <?= $user['fld_user_gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= $user['fld_user_gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
        <option value="Other" <?= $user['fld_user_gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
      </select>

      <label>New Password <small>(leave blank to keep current)</small></label>
      <input type="password" name="new_password">

      <label>Profile Picture</label>
      <input type="file" name="profile_picture">
      <?php if ($user['profile_picture']): ?>
        <img src="uploads/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Current Picture">
      <?php endif; ?>

      <button type="submit">Save Changes</button>
    </form>
  </div>
</body>
</html>
