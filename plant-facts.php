<?php
include 'database.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch matching plant facts
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM tbl_plant_facts WHERE plant_name LIKE ?");
    $stmt->execute(["%$search%"]);
} else {
    $stmt = $conn->query("SELECT * FROM tbl_plant_facts ORDER BY created_at DESC");
}
$plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Plant Knowledge | Plantofia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet" />
  <style>
    :root {
      --dark-green: #26422f;
      --light-green: #9bcc7a;
      --white: #ffffff;
      --gray-light: #f5f5f7;
      --gray-dark: #3a3a3a;
      --font-primary: 'Inter', sans-serif;
      --transition: 0.3s ease;
    }

    /* Reset */
    * {
      margin: 0; padding: 0; box-sizing: border-box;
      font-family: var(--font-primary);
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    body {
      background: var(--gray-light);
      color: var(--gray-dark);
      min-height: 100vh;
      font-weight: 400;
      font-size: 18px;
      line-height: 1.6;
    }

    a {
      color: var(--dark-green);
      text-decoration: none;
      transition: color var(--transition);
    }
    a:hover {
      color: var(--light-green);
    }

    /* Navbar */
    header {
      position: sticky;
      top: 0;
      left: 0;
      width: 100%;
      background: var(--white);
      box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
      padding: 1rem 3rem;
      z-index: 1000;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .brand {
      font-weight: 800;
      font-size: 1.5rem;
      color: var(--dark-green);
      user-select: none;
    }

    nav {
      display: flex;
      gap: 2.5rem;
      align-items: center;
    }

    nav a {
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      font-size: 0.95rem;
      user-select: none;
    }

    nav a:hover {
      color: var(--light-green);
    }

    /* Hamburger */
    .menu-icon {
      display: none;
      flex-direction: column;
      gap: 5px;
      cursor: pointer;
      user-select: none;
    }

    .menu-icon span {
      width: 28px;
      height: 3px;
      background-color: var(--dark-green);
      border-radius: 2px;
      transition: all 0.3s ease;
    }

    /* Dropdown menu */
    .dropdown-menu {
      display: none;
      position: absolute;
      top: 70px;
      right: 3rem;
      background: var(--white);
      box-shadow: 0 10px 30px rgb(0 0 0 / 0.1);
      border-radius: 10px;
      overflow: hidden;
      user-select: none;
      width: 180px;
      flex-direction: column;
    }

    .dropdown-menu a {
      padding: 15px 20px;
      border-bottom: 1px solid #eee;
      font-weight: 600;
      font-size: 1rem;
      color: var(--dark-green);
    }

    .dropdown-menu a:last-child {
      border-bottom: none;
    }

    .dropdown-menu a:hover {
      background-color: var(--light-green);
      color: var(--white);
    }

    /* Hamburger active animation */
    .menu-icon.active span:nth-child(1) {
      transform: rotate(45deg) translate(5px, 5px);
    }

    .menu-icon.active span:nth-child(2) {
      opacity: 0;
    }

    .menu-icon.active span:nth-child(3) {
      transform: rotate(-45deg) translate(5px, -5px);
    }

    /* Responsive */
    @media (max-width: 768px) {
      nav {
        display: none;
      }

      .menu-icon {
        display: flex;
      }

      .dropdown-menu {
        display: flex;
        flex-direction: column;
      }
    }

    /* Main Container */
    main {
      max-width: 1100px;
      margin: 3rem auto 5rem;
      padding: 0 2rem;
    }

    h2 {
      font-weight: 800;
      font-size: clamp(2.5rem, 6vw, 3.5rem);
      color: var(--dark-green);
      margin-bottom: 2rem;
      user-select: none;
    }

    /* Search Box */
    form.search-box {
      display: flex;
      max-width: 480px;
      margin-bottom: 2.5rem;
      border-radius: 50px;
      overflow: hidden;
      box-shadow: 0 6px 20px rgb(0 0 0 / 0.1);
      background: var(--white);
      transition: box-shadow 0.3s ease;
    }

    form.search-box:focus-within {
      box-shadow: 0 6px 30px var(--light-green);
    }

    form.search-box input[type="text"] {
      flex-grow: 1;
      border: none;
      padding: 15px 20px;
      font-size: 1rem;
      font-weight: 400;
      color: var(--dark-green);
      font-family: var(--font-primary);
      user-select: text;
    }

    form.search-box input[type="text"]::placeholder {
      color: #a3b18a;
      font-weight: 400;
    }

    form.search-box input[type="text"]:focus {
      outline: none;
    }

    form.search-box button {
      background: var(--dark-green);
      border: none;
      color: var(--white);
      font-weight: 700;
      padding: 0 2rem;
      cursor: pointer;
      transition: background-color 0.3s ease;
      font-size: 1rem;
      user-select: none;
    }

    form.search-box button:hover {
      background-color: #1f381e;
    }

    /* Plant Cards */
    .plant-card {
      background: var(--white);
      border-radius: 20px;
      box-shadow: 0 20px 40px rgb(38 66 47 / 0.1);
      display: flex;
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      cursor: pointer;
    }

    .plant-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 35px 70px rgb(38 66 47 / 0.15);
    }

    .plant-card img {
      width: 250px;
      object-fit: cover;
      user-select: none;
      flex-shrink: 0;
      border-top-left-radius: 20px;
      border-bottom-left-radius: 20px;
      transition: transform 0.3s ease;
    }

    .plant-card:hover img {
      transform: scale(1.05);
    }

    .plant-info {
      padding: 2rem 2.5rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      flex-grow: 1;
      color: var(--dark-green);
      user-select: text;
    }

    .plant-info h3 {
      font-weight: 800;
      font-size: 1.8rem;
      margin-bottom: 0.2rem;
      letter-spacing: 0.03em;
    }

    .plant-info h3 small {
      font-weight: 400;
      font-size: 1.1rem;
      color: #7a8c6e;
      margin-left: 0.7rem;
    }

    .plant-info p {
      font-size: 1rem;
      margin: 0.7rem 0;
      color: #37512c;
      line-height: 1.5;
    }

    .fun-fact {
      background: var(--light-green);
      color: var(--white);
      padding: 1rem 1.5rem;
      border-radius: 15px;
      font-weight: 600;
      font-size: 1rem;
      max-width: max-content;
      box-shadow: 0 8px 20px rgb(155 205 122 / 0.5);
      user-select: text;
      margin-top: 1rem;
      transition: background-color 0.3s ease;
    }

    .fun-fact:hover {
      background-color: #b3d67c;
      box-shadow: 0 12px 28px rgb(179 214 124 / 0.7);
    }

    /* Responsive */
    @media (max-width: 768px) {
      main {
        padding: 2rem 1.5rem;
      }

      .plant-card {
        flex-direction: column;
        border-radius: 20px;
      }

      .plant-card img {
        width: 100%;
        border-radius: 20px 20px 0 0;
      }

      .plant-info {
        padding: 1.5rem 1.8rem;
      }
    }

    @media (max-width: 400px) {
      form.search-box {
        flex-direction: column;
        gap: 0.75rem;
      }
      form.search-box button {
        padding: 1rem 0;
        width: 100%;
      }
    }
  </style>
</head>
<body>

<header>
  <div class="brand"><a href="index.php">🌿 Plantofia</a></div>

  <div class="menu-icon" aria-label="Toggle menu" role="button" tabindex="0" onclick="toggleMenu()" onkeydown="if(event.key==='Enter'||event.key===' ') toggleMenu()">
    <span></span>
    <span></span>
    <span></span>
  </div>

  <nav class="links" id="navbarLinks">
    <a href="index.php">Home</a>
    <a href="we-care.php">We Care</a>
    <a href="forum.php">Forum Komuniti</a>
    <a href="plant-diary.php">Plant Diary</a>
  </nav>
</header>

<main>
  <h2>Plant Knowledge</h2>

  <form method="GET" class="search-box" role="search" aria-label="Search for plants">
    <input type="text" name="search" placeholder="Search for a plant..." value="<?= htmlspecialchars($search) ?>" aria-label="Search input" autocomplete="off" />
    <button type="submit" aria-label="Search button">Search</button>
  </form>

  <?php if (count($plants) === 0): ?>
    <p>No plant found.</p>
  <?php else: ?>
    <?php foreach ($plants as $plant): ?>
  <article class="plant-card" tabindex="0" role="article" aria-label="Details for <?= htmlspecialchars($plant['plant_name']) ?>">
    <?php if (!empty($plant['image_path']) && file_exists($plant['image_path'])): ?>
      <img src="<?= htmlspecialchars($plant['image_path']) ?>" alt="<?= htmlspecialchars($plant['plant_name']) ?>" loading="lazy" />
    <?php else: ?>
      <img src="default-plant-image.jpg" alt="No image available" loading="lazy" />
    <?php endif; ?>
    <div class="plant-info">
      <h3><?= htmlspecialchars($plant['plant_name']) ?> <small>(<?= htmlspecialchars($plant['scientific_name']) ?>)</small></h3>
      <p><?= nl2br(htmlspecialchars($plant['description'])) ?></p>
      <p><strong>Cahaya:</strong> <?= htmlspecialchars($plant['light_requirements']) ?></p>
      <p><strong>Siram:</strong> <?= htmlspecialchars($plant['watering_needs']) ?></p>
      <p><strong>Tanah:</strong> <?= htmlspecialchars($plant['soil_type']) ?></p>
      <p><strong>Penggunaan:</strong> <?= nl2br(htmlspecialchars($plant['usage'])) ?></p>
      <?php if (!empty($plant['fun_fact'])): ?>
        <div class="fun-fact" tabindex="0" aria-label="Fun fact"><?= htmlspecialchars($plant['fun_fact']) ?></div>
      <?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>

  <?php endif; ?>
</main>

<script>
  const menuIcon = document.querySelector(".menu-icon");
  const navbarLinks = document.getElementById("navbarLinks");

  function toggleMenu() {
    navbarLinks.classList.toggle("active");
    menuIcon.classList.toggle("active");
  }

  // Close menu if click outside
  document.addEventListener("click", (e) => {
    if (!navbarLinks.contains(e.target) && !menuIcon.contains(e.target)) {
      navbarLinks.classList.remove("active");
      menuIcon.classList.remove("active");
    }
  });
</script>

</body>
</html>
