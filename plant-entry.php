<?php
session_start();
include 'database.php';

if (!isset($_SESSION['user_num'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_num'];
$plant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch plant that belongs to the logged-in user
$stmt = $conn->prepare("SELECT * FROM tbl_user_plants WHERE plant_id = ? AND fld_user_num = ?");
$stmt->execute(array($plant_id, $user_id));
$plant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plant) {
    echo "Plant not found or access denied.";
    exit;
}

// Fetch diary entries
$entriesStmt = $conn->prepare("SELECT * FROM tbl_plant_diary WHERE plant_id = ? ORDER BY created_at DESC");
$entriesStmt->execute(array($plant_id));
$entries = $entriesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Plant Diary Entry</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --dark-green: #26422f;
      --white: #ffffff;
      --primary-font: 'Inter', sans-serif;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: var(--primary-font);
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    body {
      background-color: var(--white);
      color: var(--dark-green);
      min-height: 100vh;
      line-height: 1.6;
      font-weight: 300;
      font-size: 18px;
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
      color: var(--dark-green);
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
      background: var(--dark-green);
      display: block;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      top: 100%;
      right: 3rem;
      background-color: var(--white);
      border: 1px solid #ccc;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
      padding: 1rem;
      z-index: 1000;
    }

    .dropdown-menu a {
      display: block;
      padding: 0.5rem 1rem;
      text-decoration: none;
      color: var(--dark-green);
    }

    .dropdown-menu a:hover {
      background-color: #f4f4f4;
    }

    @media (max-width: 768px) {
      nav {
        display: none;
      }

      .menu-icon {
        display: flex;
      }
    }

    main {
      margin-top: 80px;
      padding: 4rem 10vw;
      max-width: 100vw;
      display: flex;
      flex-direction: column;
      gap: 4rem;
    }

    .plant-header {
      display: flex;
      flex-direction: column;
      max-width: 100%;
      gap: 2rem;
    }

    .plant-name {
      font-weight: 800;
      font-size: clamp(3rem, 8vw, 6rem);
      line-height: 1.1;
      letter-spacing: -0.02em;
      text-align: left;
      max-width: 100%;
      color: var(--dark-green);
      user-select: none;
    }

    .plant-image {
      width: 100%;
      max-height: 500px;
      object-fit: contain;
      border-radius: 20px;
      box-shadow: 0 15px 30px rgba(38, 66, 47, 0.2);
      user-select: none;
    }

    .entries {
      display: flex;
      flex-direction: column;
      gap: 2.5rem;
      max-width: 700px;
      color: var(--dark-green);
      font-weight: 400;
      font-size: 1.125rem;
    }

    .entry {
      border-left: 4px solid var(--dark-green);
      padding-left: 1rem;
      background: #f9f9f9;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(38, 66, 47, 0.1);
      user-select: text;
    }

    .entry strong {
      display: block;
      font-weight: 700;
      margin-bottom: 0.4rem;
      font-size: 1rem;
      color: var(--dark-green);
    }

    .entry p {
      margin-bottom: 0.3rem;
      white-space: pre-wrap;
      color: #37512c;
    }

    .add-note-btn {
      width: fit-content;
      padding: 1rem 3rem;
      font-weight: 700;
      font-size: 1.25rem;
      background: var(--dark-green);
      color: var(--white);
      border: none;
      border-radius: 50px;
      cursor: pointer;
      box-shadow: 0 8px 20px rgba(38, 66, 47, 0.4);
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      user-select: none;
      align-self: flex-start;
    }

    .add-note-btn:hover {
      background: #1f381e;
      box-shadow: 0 12px 30px rgba(31, 56, 30, 0.7);
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      inset: 0;
      background-color: rgba(0, 0, 0, 0.6);
      justify-content: center;
      align-items: center;
      padding: 1rem;
    }

    .modal-content {
      background: var(--white);
      color: var(--dark-green);
      border-radius: 24px;
      padding: 3rem 3.5rem;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 12px 30px rgba(38, 66, 47, 0.25);
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      position: relative;
      font-weight: 400;
    }

    .close-btn {
      position: absolute;
      top: 18px;
      right: 20px;
      background: none;
      border: none;
      font-size: 2.5rem;
      font-weight: 900;
      cursor: pointer;
      color: var(--dark-green);
      user-select: none;
      line-height: 1;
      padding: 0;
      margin: 0;
    }

    form input[type="text"],
    form textarea {
      padding: 14px 18px;
      border-radius: 14px;
      border: 1.5px solid #ccc;
      font-size: 1rem;
      resize: vertical;
      font-family: var(--primary-font);
      color: var(--dark-green);
      font-weight: 400;
      transition: border-color 0.3s ease;
    }

    form input[type="text"]:focus,
    form textarea:focus {
      border-color: var(--dark-green);
      outline: none;
    }

    form textarea {
      min-height: 100px;
    }

    .save-btn {
      background: var(--dark-green);
      color: var(--white);
      font-weight: 800;
      padding: 1rem 0;
      border: none;
      border-radius: 50px;
      cursor: pointer;
      font-size: 1.25rem;
      box-shadow: 0 10px 25px rgba(38, 66, 47, 0.45);
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      user-select: none;
    }

    .save-btn:hover {
      background: #1f381e;
      box-shadow: 0 14px 38px rgba(31, 56, 30, 0.75);
    }

    @media (max-width: 600px) {
      main {
        padding: 3rem 5vw;
      }
      .plant-name {
        font-size: clamp(2.5rem, 10vw, 4.5rem);
      }
      .add-note-btn {
        font-size: 1.1rem;
        padding: 0.9rem 2.5rem;
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
    <span></span>
    <span></span>
    <span></span>
  </div>

  <div class="dropdown-menu" id="dropdownMenu">
    <a href="user_dashboard.php">Dashboard</a>
    <a href="logout.php">Logout</a>
  </div>
</header>

<main>
  <section class="plant-header">
    <h1 class="plant-name"><?php echo htmlspecialchars($plant['plant_name']); ?></h1>
    <img class="plant-image" src="<?php echo htmlspecialchars($plant['plant_image']); ?>" alt="Plant Image" />
  </section>

  <section class="entries">
    <?php foreach ($entries as $entry): ?>
      <article class="entry">
        <strong><?php echo date("d M Y", strtotime($entry['created_at'])); ?></strong>
        <p><strong>Aktiviti:</strong> <?php echo htmlspecialchars($entry['activity']); ?></p>
        <p><strong>Pemerhatian:</strong> <?php echo htmlspecialchars($entry['observation']); ?></p>
        <p><strong>Nota:</strong> <?php echo htmlspecialchars($entry['note']); ?></p>
      </article>
    <?php endforeach; ?>
  </section>

  <button class="add-note-btn" onclick="openModal()">+ Add New Note</button>
</main>

<!-- Modal -->
<div class="modal" id="noteModal">
  <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <button class="close-btn" onclick="closeModal()" aria-label="Close modal">×</button>
    <form id="noteForm" onsubmit="return false;">
      <input type="hidden" name="plant_id" value="<?php echo $plant['plant_id']; ?>" />
      <input type="text" name="activity" placeholder="Aktiviti ..." required autocomplete="off" />
      <input type="text" name="observation" placeholder="Pemerhatian ..." required autocomplete="off" />
      <textarea name="note" rows="3" placeholder="Nota ..." required></textarea>
      <button class="save-btn" type="submit">Save</button>
    </form>
  </div>
</div>

<script>
  function toggleMenu() {
    var dropdown = document.getElementById("dropdownMenu");
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
  }

  document.addEventListener("click", function (event) {
    var dropdown = document.getElementById("dropdownMenu");
    var icon = document.querySelector(".menu-icon");
    if (!dropdown.contains(event.target) && !icon.contains(event.target)) {
      dropdown.style.display = "none";
    }
  });

  function openModal() {
    document.getElementById("noteModal").style.display = "flex";
  }

  function closeModal() {
    document.getElementById("noteModal").style.display = "none";
  }

  window.addEventListener('click', function(event) {
    var modal = document.getElementById('noteModal');
    if (event.target === modal) {
      closeModal();
    }
  });

  // AJAX form submission with SweetAlert2
  var noteForm = document.getElementById("noteForm");

  noteForm.addEventListener("submit", function (e) {
    e.preventDefault();
    var formData = new FormData(noteForm);

    // For older browsers, you might need to use XMLHttpRequest instead of fetch
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "save_plant_note.php", true);
    
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          if (data.success) {
            Swal.fire({
              icon: "success",
              title: "Berjaya!",
              text: data.message,
              showConfirmButton: false,
              timer: 2000
            });
            noteForm.reset();
            closeModal();
            setTimeout(function() {
              location.reload();
            }, 2000);
          } else {
            Swal.fire({
              icon: "error",
              title: "Ralat",
              text: data.message
            });
          }
        } catch (error) {
          console.error(error);
          Swal.fire({
            icon: "error",
            title: "Oops!",
            text: "Something went wrong."
          });
        }
      }
    };
    
    xhr.send(formData);
  });
</script>

</body>
</html>