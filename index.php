<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Plantofia</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;600;900&display=swap" rel="stylesheet" />
  <style>
    :root {
      --green: #26422f;
      --gray: #444;
      --white: #ffffff;
      --light: #f7f7f7;
      --accent: #9bcd7a; /* Accent color for highlights */
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
      scroll-behavior: smooth;
    }

    body {
      background-color: var(--white);
      color: var(--green);
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem 3rem;
      position: sticky;
      top: 0;
      background-color: var(--white);
      z-index: 100;
      border-bottom: 1px solid #eee;
    }

    nav {
      display: flex;
      gap: 2rem;
      flex-wrap: wrap;
    }

    nav a {
      text-decoration: none;
      color: var(--green);
      font-weight: 600;
      text-transform: uppercase;
    }

    .menu-icon {
      display: flex;
      flex-direction: column;
      gap: 4px;
      cursor: pointer;
    }

    .menu-icon span {
      width: 25px;
      height: 3px;
      background: var(--green);
      display: block;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      top: 100%;
      right: 2rem;
      background-color: white;
      border: 1px solid #ccc;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
      padding: 1rem;
      z-index: 1000;
    }

    .dropdown-menu a {
      display: block;
      padding: 0.5rem 1rem;
      text-decoration: none;
      color: var(--green);
    }

    .dropdown-menu a:hover {
      background-color: #f4f4f4;
    }

    /* Hero section - first part */
    .hero-top {
      display: grid;
      grid-template-columns: 1fr 1fr;
      align-items: center;
      padding: 5rem 3rem;
      min-height: 100vh;
    }

    .hero-left h1 {
      font-size: 8rem;
      font-weight: 900;
      line-height: 1.2;
    }

    .hero-left p {
      margin-top: 1rem;
      color: var(--gray);
      font-size: 1.1rem;
    }

    .hero-left button {
      margin-top: 1.5rem;
      padding: 0.7rem 1.5rem;
      background: none;
      border: 2px solid var(--green);
      color: var(--green);
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
    }

    .hero-left button:hover {
      background: var(--green);
      color: var(--white);
    }

    .hero-right {
      position: relative;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100%;
    }

    .hero-right img {
      width: 80%;
      height: auto;
      object-fit: cover;
      z-index: 2;
    }

    .hero-right .year {
      font-size: 8rem;
      font-weight: 900;
      color: rgba(0, 0, 0, 0.05);
      position: absolute;
      right: 10%;
      top: 20%;
      z-index: 1;
    }

    /* About Us Section */
    .about-us {
      padding: 5rem 3rem;
      text-align: center;
      background-color: #ffffff; 
    }

    .about-us h2 {
      font-size: 8rem;
      font-weight: 900;
      margin-bottom: 1rem;
      color: var(--accent);
    }

    .about-us p {
      font-size: 1.3rem;
      max-width: 800px;
      margin: 0 auto 2rem;
      color: var(--gray);
    }

    .about-us button {
      background: none;
      border: 2px solid var(--green);
      color: var(--green);
      padding: 0.7rem 1.5rem;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
    }

    .about-us button:hover {
      background: var(--green);
      color: var(--white);
    }

    /* Middle image section */
    .middle-image {
      height: 100vh;
      background-image: url('indexplant.jpg'); /* Replace with your image */
      background-size: cover;
      background-position: center;
      animation: fadein 1.5s ease-in-out;
    }

    @keyframes fadein {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    footer {
      background: var(--light);
      padding: 5rem 2rem;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-around;
      gap: 3rem;
    }

    footer div {
      min-width: 200px;
    }

    footer h4 {
      text-transform: uppercase;
      font-size: 1rem;
      margin-bottom: 1rem;
      font-weight: 600;
    }

    footer ul {
      list-style: none;
      padding: 0;
    }

    footer ul li {
      margin-bottom: 0.5rem;
      font-size: 1rem;
    }

    footer ul li a {
      color: var(--green);
      text-decoration: none;
    }

    footer ul li a:hover {
      text-decoration: underline;
    }

    footer small {
      width: 100%;
      text-align: center;
      margin-top: 2rem;
      display: block;
      color: #999;
      font-size: 1rem;
    }

    @media (max-width: 768px) {
      .hero-top {
        grid-template-columns: 1fr;
        text-align: center;
      }

      .hero-right .year {
        font-size: 5rem;
      }

      .hero-right img {
        width: 100%;
      }

      .middle-image {
        height: 50vh;
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
    <div class="menu-icon" onclick="toggleMenu()">
      <span></span>
      <span></span>
      <span></span>
    </div>

   <div class="dropdown-menu" id="dropdownMenu">
  <?php if (isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'admin'): ?>
    <a href="admin_dashboard.php">Dashboard</a>
  <?php else: ?>
    <a href="user_dashboard.php">Dashboard</a>
  <?php endif; ?>
  <a href="logout.php">Logout</a>
</div>

  </header>

  <section class="hero-top">
    <div class="hero-left">
      <h1>plants speak <br><strong>we listen.</strong></h1>
      <p>Plants remind us that growth takes time.</p>
      <button onclick="document.getElementById('scrollTarget').scrollIntoView({ behavior: 'smooth' });">About us</button>
    </div>
    <div class="hero-right">
      <img src="plant-image.png" alt="Plant in pot" />
      <div class="year">25</div>
    </div>
  </section>

  <section class="about-us" id="scrollTarget">
    <h2>About Us</h2>
    <p>At Plantofia, we believe in the transformative power of nature. We are more than just a brand; we are a community that listens to what plants have to say. Growth takes time, and we are here to nurture it with you. Through dedication, care, and a focus on sustainability, we aim to build a greener tomorrow, today.</p>
<button onclick="window.location.href='about.html';">Learn More</button>
  </section>

  <section class="middle-image"></section>

  <footer>
    <div>
      <h4>Social</h4>
      <ul>
        <li><a href="https://www.instagram.com/fakrur_radzi_/">Instagram</a></li>
        <li><a href="#">Facebook</a></li>
        <li><a href="https://www.tiktok.com/@novoc4in3?is_from_webapp=1&sender_device=pc">TikTok</a></li>
      </ul>
    </div>
    <div>
  <h4>Contact</h4>
  <ul>
    <li><a href="mailto:a193635@siswa.ukm.edu.my">Email us for any inquiries</a></li>
  </ul>
</div>
    <div>
      <h4>Company</h4>
      <ul>
        <li><a href="about.html">About Us</a></li>
      </ul>
    </div>
    <small>© 2025 Plantofia. All rights reserved.</small>
  </footer>

  <script>
    function toggleMenu() {
      const dropdown = document.getElementById("dropdownMenu");
      dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    document.addEventListener("click", function (event) {
      const dropdown = document.getElementById("dropdownMenu");
      const icon = document.querySelector(".menu-icon");
      if (!dropdown.contains(event.target) && !icon.contains(event.target)) {
        dropdown.style.display = "none";
      }
    });
  </script>
</body>
</html>
