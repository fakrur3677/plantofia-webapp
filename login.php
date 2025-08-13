<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: user_dashboard.php');
    exit();
}

include 'database.php';

$message = ""; // Initialize the $message variable to avoid the undefined variable error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $storedPwd = $user['password'];

        if (password_verify($password, $storedPwd)) {
            $_SESSION['user_id']     = $user['fld_user_num'];
            $_SESSION['user_num']    = $user['fld_user_num'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['user_level']  = $user['user_level'];
            $_SESSION['profile_pic'] = isset($user['profile_picture']) ? $user['profile_picture'] : null;

            if ($user['user_level'] === 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: user_dashboard.php');
            }
            exit();
        } else {
            $message = "❌ Incorrect password.";
        }
    } else {
        $message = "❌ User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login - Plantofia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;600;900&display=swap" rel="stylesheet" />
    <style>
      :root {
        --green: #26422f;
        --white: #ffffff;
      }

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
      }

      body {
        background: url('uploads/plantofia_login.jpg') no-repeat center center fixed;
        background-size: cover;
        color: var(--green);
        display: flex;
        justify-content: flex-end;
        align-items: center;
        height: 100vh;
        padding: 140px;
      }

      .container {
        max-width: 350px;
        background: var(--white);
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      }

      h2 {
        text-align: center;
        color: var(--green);
        font-size: 1.8rem;
        margin-bottom: 30px;
        font-weight: 600;
      }

      input {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border-radius: 10px;
        border: 1px solid #ccc;
        font-size: 1rem;
        background: #f9f9f9;
        transition: 0.3s all;
      }

      input:focus {
        outline: none;
        border-color: var(--green);
        box-shadow: 0 0 0 2px rgba(38,66,47,0.2);
      }

      button {
        width: 100%;
        padding: 12px;
        background-color: var(--green);
        color: var(--white);
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.3s all;
      }

      button:hover {
        background-color: #1a331f;
      }

      .error {
        color: red;
        text-align: center;
        margin-bottom: 10px;
      }

      a {
        text-align: center;
        display: block;
        margin-top: 15px;
        text-decoration: none;
        color: var(--green);
        font-weight: 600;
      }

      a:hover {
        color: #1a331f;
      }
    </style>
</head>
<body>

  <div class="container">
    <h2>Login to Plantofia</h2>
    <?php if (!empty($message)): ?><div class="error"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <form method="POST">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <a href="register.php">New user? Register here</a>
  </div>

</body>
</html>
