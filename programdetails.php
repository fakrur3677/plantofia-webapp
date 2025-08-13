<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'database.php';

// Check if program ID is given
if (!isset($_GET['id'])) {
  echo "Program ID is missing.";
  exit();
}

$program_id = intval($_GET['id']);

// Fetch program details
$stmt = $conn->prepare("SELECT * FROM tbl_wecare WHERE program_id = :id");
$stmt->bindParam(':id', $program_id);
$stmt->execute();
$program = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$program) {
  echo "Program not found.";
  exit();
}

// Handle registration
if (isset($_POST['register'])) {
  if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in to register.');</script>";
  } else {
    $user_id = $_SESSION['user_id'];

    // Check for duplicate registration
    $check = $conn->prepare("SELECT * FROM tbl_registration WHERE user_id = ? AND program_id = ?");
    $check->execute([$user_id, $program_id]);

    if ($check->rowCount() > 0) {
      echo "<script>alert('You have already registered for this program.');</script>";
    } else {
      $register = $conn->prepare("INSERT INTO tbl_registration (user_id, program_id) VALUES (?, ?)");
      $register->execute([$user_id, $program_id]);
      echo "<script>alert('Successfully registered for this program!');</script>";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Program Details - Plants Speak</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet" />
  <style>
    :root {
      --dark-green: #26422f; /* Dark Green for text */
      --light-neutral: #F9F9F9; /* Light Neutral Background */
      --white: #ffffff;
      --primary-font: 'Inter', sans-serif;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
    }

    body {
      background-color: var(--light-neutral); /* Light neutral background */
      color: var(--dark-green); /* Dark green for text */
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      padding: 0 20px;
    }

    .container {
      max-width: 700px;
      width: 100%;
      background-color: var(--white);
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      margin-top: 50px;
    }

    h1 {
      font-size: 28px;
      color: var(--dark-green);
      font-weight: 800;
      margin-bottom: 20px;
    }

    p {
      font-size: 16px;
      line-height: 1.6;
      margin-bottom: 10px;
    }

    .back-link, .register-btn {
      display: inline-block;
      margin-top: 20px;
      text-decoration: none;
      background-color: var(--dark-green);
      color: var(--white);
      padding: 10px 16px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      margin-right: 15px;
    }

    .back-link:hover, .register-btn:hover {
      background-color: #1d3b2a; /* Darker green on hover */
    }

    .message, .error {
      text-align: center;
      font-weight: bold;
    }

    .message {
      color: green;
    }

    .error {
      color: red;
    }

    img {
      max-width: 100%;
      border-radius: 8px;
      margin-top: 10px;
    }

    /* Navbar */
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      background-color: var(--white); /* White navbar */
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Subtle shadow */
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 100; /* Keep it on top */
    }

    nav {
      display: flex;
      gap: 2rem;
    }

    nav a {
      text-decoration: none;
      color: var(--dark-green); /* Dark green font */
      font-weight: 500;
    }

    nav a:hover {
      color: #1d3b2a; /* Darker green on hover */
    }

    nav a[href="we-care.php"] {
      font-weight: 800;
      border-bottom: 2px solid var(--dark-green); /* Highlight active page */
    }

    /* Hamburger Menu */
    .menu-icon {
      display: block;
      width: 30px;
      height: 25px;
      position: absolute;
      right: 20px; /* Right align the hamburger menu */
      cursor: pointer;
    }

    .menu-icon span {
      position: absolute;
      height: 4px;
      background-color: var(--dark-green);
      width: 100%;
      left: 0;
      transition: 0.3s;
    }

    .menu-icon span:nth-child(1) { top: 0; }
    .menu-icon span:nth-child(2) { top: 10px; }
    .menu-icon span:nth-child(3) { top: 20px; }

    .dropdown-menu {
      display: none;
      position: absolute;
      top: 60px;
      right: 20px;
      background-color: var(--white);
      padding: 1rem;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.2);
      z-index: 10;
    }

    .dropdown-menu a {
      display: block;
      color: var(--dark-green);
      text-decoration: none;
      margin-bottom: 10px;
      padding: 8px 20px;
    }

    .dropdown-menu a:hover {
      background-color: #f3f3f3;
    }

    .show {
      display: block;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      nav {
        display: none; /* Hide nav items for small screens */
        flex-direction: column;
        gap: 1rem;
      }

      nav a {
        padding: 0.5rem 0;
      }

      .menu-icon {
        display: block; /* Show hamburger icon on small screens */
      }

      .dropdown-menu {
        position: absolute;
        top: 60px;
        left: 0;
        width: 100%;
        padding: 1rem;
      }

      .dropdown-menu a {
        text-align: center;
        width: 100%;
        padding: 1rem;
      }
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

  <div class="menu-icon" onclick="toggleMenu()" aria-label="Toggle menu" role="button" tabindex="0">
    <span></span><span></span><span></span>
  </div>
  <div class="dropdown-menu" id="dropdownMenu">
    <a href="user_dashboard.php">Dashboard</a>
    <a href="logout.php">Logout</a>
  </div>
</header>

  <div class="container">
    <h1><?php echo htmlspecialchars($program['program_title']); ?></h1>
    <p><strong>Date:</strong> <?php echo date("d/m/Y", strtotime($program['program_date'])); ?></p>
    <p><strong>Time:</strong> <?php echo htmlspecialchars($program['program_time']); ?></p>
    <p><strong>Location:</strong> <?php echo htmlspecialchars($program['program_location']); ?></p>
    <p><strong>Organizer:</strong> <?php echo htmlspecialchars($program['organizer']); ?></p>
    <p><strong>Participant Limit:</strong> <?php echo (int)$program['participant_limit']; ?></p>
    <p><strong>Instagram:</strong> <?php echo htmlspecialchars($program['instagram_handle']); ?></p>
    <p><strong>Facebook:</strong> <?php echo htmlspecialchars($program['facebook_handle']); ?></p>
    <p><strong>SDGs Involved:</strong> <?php echo htmlspecialchars($program['sdg']); ?></p>
    <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($program['program_description'])); ?></p>

    <a href="we-care.php" class="back-link">⬅ Back to Program List</a>

    <?php if (isset($_SESSION['user_id'])): ?>
      <form method="POST" onsubmit="return confirm('Are you sure you want to register for this program?');">
        <input type="hidden" name="program_id" value="<?php echo $program_id; ?>">
        <button type="submit" name="register" class="register-btn">Register</button>
      </form>
    <?php endif; ?>
  </div>

  <script>
    function toggleMenu() {
      var menu = document.getElementById("dropdownMenu");
      menu.classList.toggle("show");
    }

    window.onclick = function(event) {
      if (!event.target.closest('.menu-icon') && !event.target.closest('.dropdown-menu')) {
        document.getElementById("dropdownMenu").classList.remove("show");
      }
    };
  </script>

</body>
</html>
