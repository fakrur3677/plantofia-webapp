<?php
session_start();
include 'database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE username = :username");
    $stmt->execute([':username' => $username]);
    if ($stmt->rowCount() > 0) {
        $error = "Username already exists.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO tbl_user (fld_user_fname, fld_user_lname, username, password, user_level, fld_user_gender, fld_user_phone, fld_user_email)
                                 VALUES (:fname, :lname, :username, :password, 'normal', :gender, :phone, :email)");
        $insert->execute([
            ':fname' => $fname,
            ':lname' => $lname,
            ':username' => $username,
            ':password' => $hashed,
            ':gender' => $gender,
            ':phone' => $phone,
            ':email' => $email
        ]);

        echo "
<script>
  alert('Registration successful! Please log in.');
  window.location.href = 'login.php';
</script>
";
exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - Plantofia</title>
<style>
:root {
  --bg-color: #ffffff;
  --primary-text: #1d1d1f;
  --accent-color: #0071e3;
  --border-color: #d2d2d7;
}

body {
  background-color: var(--bg-color);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  margin: 0;
  padding: 0;
  color: var(--primary-text);
}

.container {
  max-width: 400px;
  margin: 80px auto;
  padding: 40px;
  background: var(--bg-color);
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}

h2 {
  font-weight: 600;
  text-align: center;
  margin-bottom: 30px;
  font-size: 1.8rem;
}

form {
  display: flex;
  flex-direction: column;
}

input, select {
  font-size: 1rem;
  padding: 12px 14px;
  margin: 8px 0;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  background: var(--bg-color);
  color: var(--primary-text);
  outline: none;
  transition: border-color 0.3s;
}

input:focus, select:focus {
  border-color: var(--accent-color);
}

button {
  margin-top: 20px;
  background-color: var(--accent-color);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 14px;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.3s;
}

button:hover {
  background-color: #005bb5;
}

.error {
  color: red;
  text-align: center;
  margin-bottom: 10px;
  font-size: 0.95rem;
}

a {
  text-align: center;
  display: block;
  margin-top: 20px;
  color: var(--accent-color);
  text-decoration: none;
  font-size: 0.95rem;
}

a:hover {
  text-decoration: underline;
}

@media (max-width: 480px) {
  .container {
    margin: 40px 20px;
    padding: 20px;
  }
}
</style>
</head>
<body>
<div class="container">
  <h2>Create Your Account</h2>
  <?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
    <input type="text" name="fname" placeholder="First Name" required>
    <input type="text" name="lname" placeholder="Last Name" required>
    <input type="text" name="username" placeholder="Username" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="phone" placeholder="Phone Number">
    <select name="gender" required>
      <option value="">Select Gender</option>
      <option value="Male">Male</option>
      <option value="Female">Female</option>
    </select>
    <input type="password" name="password" placeholder="Password" required>
    <input type="password" name="confirm" placeholder="Confirm Password" required>
    <button type="submit">Register</button>
  </form>
  <a href="login.php">Already have an account? Login here</a>
</div>
</body>
</html>
