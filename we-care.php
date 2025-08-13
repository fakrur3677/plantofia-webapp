<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'database.php'; // Make sure this file defines $conn as a PDO object!
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>We Care - Plants Speak</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;600;800&display=swap" rel="stylesheet" />
<style>
  :root { --dark-green: #26422f; --light-neutral: #f9f9f9; --white: #ffffff; --primary-font: 'Inter', sans-serif; }
  * { margin: 0; padding: 0; box-sizing: border-box; font-family: var(--primary-font); }
  body { background-color: var(--light-neutral); color: var(--dark-green); min-height: 100vh; }
  header { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 3rem; position: sticky; top: 0; background-color: var(--white); z-index: 100; border-bottom: 1px solid #eee; }
  nav { display: flex; gap: 2rem; flex-wrap: wrap; }
  nav a { text-decoration: none; color: var(--dark-green); font-weight: 600; text-transform: uppercase; user-select: none; transition: color 0.3s ease; }
  nav a:hover, nav a.active { color: #1f381e; font-weight: 800; border-bottom: 2px solid var(--dark-green); padding-bottom: 0.2rem; }
  .menu-icon { display: none; flex-direction: column; gap: 5px; cursor: pointer; user-select: none; }
  .menu-icon span { width: 25px; height: 3px; background: var(--dark-green); border-radius: 2px; transition: 0.3s ease; }
  .dropdown-menu { display: none; position: absolute; top: 60px; right: 3rem; background-color: var(--white); border: 1px solid #ccc; box-shadow: 0 4px 10px rgba(0,0,0,0.05); padding: 1rem; z-index: 1000; border-radius: 12px; min-width: 140px; }
  .dropdown-menu a { display: block; padding: 0.5rem 1rem; text-decoration: none; color: var(--dark-green); border-radius: 8px; transition: background-color 0.2s ease; }
  .dropdown-menu a:hover { background-color: var(--light-neutral); }
  @media (max-width: 768px) { nav { display: none; } .menu-icon { display: flex; } }
  .hero { background-color: var(--white); padding: 2rem 2rem 1rem; text-align: center; user-select: none; }
  .hero h1 { font-size: clamp(2.5rem, 6vw, 3.5rem); color: var(--dark-green); font-weight: 800; }
  .event-container { display: flex; flex-direction: column; gap: 24px; padding: 2rem 3rem 4rem; max-width: 960px; margin: 0 auto 4rem auto; }
  .event-card { background-color: var(--white); color: var(--dark-green); border-radius: 16px; padding: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; box-shadow: 0 4px 8px rgba(38,66,47,0.1); transition: box-shadow 0.3s ease; }
  .event-card:hover { box-shadow: 0 8px 20px rgba(38,66,47,0.2); }
  .event-info { display: flex; align-items: center; gap: 24px; flex: 1; min-width: 240px; }
  .event-icon { width: 64px; height: 64px; background-color: var(--light-neutral); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 36px; user-select: none; }
  .event-text h3 { font-size: 1.25rem; font-weight: 800; margin-bottom: 0.25rem; }
  .event-text p { font-size: 1rem; color: #37512c; user-select: text; }
  .btn { background-color: var(--dark-green); border: none; padding: 12px 24px; border-radius: 30px; font-weight: 700; color: var(--white); cursor: pointer; text-decoration: none; user-select: none; transition: background-color 0.3s ease, box-shadow 0.3s ease; box-shadow: 0 8px 20px rgba(38,66,47,0.4); }
  .btn:hover { background-color: #1f381e; box-shadow: 0 12px 30px rgba(31,56,30,0.7); }
  @media (max-width: 768px) { .event-card { flex-direction: column; align-items: flex-start; gap: 12px; } .btn { align-self: flex-end; } }
  .carousel-container { position: relative; max-width: 900px; margin: 3rem auto; overflow: hidden; }
  .carousel-track { display: flex; align-items: center; transition: transform 0.5s ease; }
  .carousel-slide { flex: 0 0 33.3333%; opacity: 0.6; transform: scale(0.9); transition: transform 0.5s ease, opacity 0.5s ease; display: flex; justify-content: center; align-items: center; }
  .carousel-slide.active { opacity: 1; transform: scale(1); }
  .carousel-slide img { width: 95%; height: auto; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
  .carousel-button { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.7); border: none; color: var(--dark-green); font-size: 1.5rem; padding: 0.5rem 0.7rem; cursor: pointer; z-index: 1; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: background 0.3s ease, box-shadow 0.3s ease; }
  .carousel-button:hover { background: rgba(255,255,255,0.9); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
  .carousel-button.prev { left: 0.5rem; } .carousel-button.next { right: 0.5rem; }
  .carousel-button svg { width: 18px; height: 18px; }
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

<!-- We Care at the top -->
<section class="hero">
  <h1>We Care</h1>
</section>

<!-- Title above carousel -->
<h2 style="text-align: center; margin-top: 2rem; color: var(--dark-green); font-weight: 800;">Past Collaboration Program</h2>

<!-- Carousel -->
<section class="carousel-container">
  <button class="carousel-button prev" aria-label="Previous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
  <div class="carousel-track">
    <div class="carousel-slide"><img src="uploads/mybumi1.jpg" alt="Past Collab 1"></div>
    <div class="carousel-slide"><img src="uploads/mybumi2.jpg" alt="Past Collab 2"></div>
    <div class="carousel-slide"><img src="uploads/mybumi3.jpg" alt="Past Collab 3"></div>
    <div class="carousel-slide"><img src="uploads/mybumi4.jpg" alt="Past Collab 4"></div>
    <div class="carousel-slide"><img src="uploads/mybumi5.jpg" alt="Past Collab 5"></div>
    <div class="carousel-slide"><img src="uploads/mybumi6.jpg" alt="Past Collab 5"></div>
    <div class="carousel-slide"><img src="uploads/mybumi7.jpg" alt="Past Collab 5"></div>
    <div class="carousel-slide"><img src="uploads/mybumi8.jpg" alt="Past Collab 5"></div>
  </div>
  <button class="carousel-button next" aria-label="Next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
</section>

<!-- Motivational message -->
<section class="hero">
  <p style="font-size: 1.2rem; color: #37512c; margin-top: 1rem;">
    Join our mission to make a difference by participating in the programs below!
  </p>
</section>

<!-- Events -->
<section class="event-container">
<?php
$stmt = $conn->query("SELECT * FROM tbl_wecare ORDER BY program_date ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  echo '<div class="event-card"><div class="event-info"><div class="event-icon">🌿</div><div class="event-text"><h3>' . htmlspecialchars($row['program_title']) . '</h3><p>' . date("d/m/Y", strtotime($row['program_date'])) . ' – ' . htmlspecialchars($row['program_location']) . '</p></div></div><a href="programdetails.php?id=' . $row['program_id'] . '" class="btn">See More</a></div>';
}
?>
</section>

<script>
function toggleMenu() {
  const menu = document.getElementById('dropdownMenu');
  menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
}
document.addEventListener('click', function(event) {
  const menu = document.getElementById('dropdownMenu');
  const icon = document.querySelector('.menu-icon');
  if (!menu.contains(event.target) && !icon.contains(event.target)) {
    menu.style.display = 'none';
  }
});
// Smooth Carousel logic
const track = document.querySelector('.carousel-track');
const originalSlides = Array.from(track.children);
const prevButton = document.querySelector('.carousel-button.prev');
const nextButton = document.querySelector('.carousel-button.next');
const firstClone = originalSlides[0].cloneNode(true);
const lastClone = originalSlides[originalSlides.length - 1].cloneNode(true);
track.appendChild(firstClone);
track.insertBefore(lastClone, originalSlides[0]);
let slides = Array.from(track.children);
let currentIndex = 1;
const slideWidth = slides[0].getBoundingClientRect().width;
track.style.transform = `translateX(-${slideWidth * currentIndex}px)`;
function updateCarousel() {
  slides = Array.from(track.children);
  slides.forEach((slide, index) => {
    slide.classList.remove('active');
    if (index === currentIndex) {
      slide.classList.add('active');
    }
  });
  track.style.transition = 'transform 0.5s ease';
  track.style.transform = `translateX(-${slideWidth * currentIndex}px)`;
}
nextButton.addEventListener('click', () => {
  if (currentIndex >= slides.length - 1) return;
  currentIndex++;
  updateCarousel();
  track.addEventListener('transitionend', () => {
    if (currentIndex === slides.length - 1) {
      track.style.transition = 'none';
      currentIndex = 1;
      track.style.transform = `translateX(-${slideWidth * currentIndex}px)`;
    }
  }, { once: true });
});
prevButton.addEventListener('click', () => {
  if (currentIndex <= 0) return;
  currentIndex--;
  updateCarousel();
  track.addEventListener('transitionend', () => {
    if (currentIndex === 0) {
      track.style.transition = 'none';
      currentIndex = slides.length - 2;
      track.style.transform = `translateX(-${slideWidth * currentIndex}px)`;
    }
  }, { once: true });
});
updateCarousel();
</script>
</body>
</html>
