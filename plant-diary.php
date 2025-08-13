<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'database.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_level = $_SESSION['user_level'];
$message = ""; // Initialize the $message variable to avoid the undefined variable error

// Handle Add Plant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_plant'])) {
    $plant_name = $_POST['plant_name'];
    $image_path = null;

    if (isset($_FILES['plant_image']) && $_FILES['plant_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir);

        $filename = time() . "_" . basename($_FILES['plant_image']['name']);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['plant_image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        } else {
            $message = "❌ Gagal memuat naik gambar.";
        }
    }

    // Insert plant data into the database
    $stmt = $conn->prepare("INSERT INTO tbl_user_plants (fld_user_num, plant_name, plant_image) VALUES (?, ?, ?)");
    if ($stmt->execute([$user_id, $plant_name, $image_path])) {
        $message = "✅ Tumbuhan berjaya ditambah!";
    } else {
        $message = "❌ Gagal menyimpan tumbuhan.";
    }
}

// Handle Delete Plant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_plant'])) {
    $plant_id = $_POST['plant_id'];

    // Delete image file if exists
    $stmtGetImage = $conn->prepare("SELECT plant_image FROM tbl_user_plants WHERE plant_id = ? AND fld_user_num = ?");
    $stmtGetImage->execute([$plant_id, $user_id]);
    $plant = $stmtGetImage->fetch(PDO::FETCH_ASSOC);
    if ($plant && $plant['plant_image'] && file_exists($plant['plant_image'])) {
        unlink($plant['plant_image']);
    }

    // Delete plant record
    $stmtDelete = $conn->prepare("DELETE FROM tbl_user_plants WHERE plant_id = ? AND fld_user_num = ?");
    if ($stmtDelete->execute([$plant_id, $user_id])) {
        $message = "✅ Tumbuhan berjaya dipadam!";
    } else {
        $message = "❌ Gagal memadam tumbuhan.";
    }
}

// Handle Edit Plant Name
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_plant'])) {
    $plant_id = $_POST['plant_id'];
    $new_name = trim($_POST['new_plant_name']);

    if ($new_name === '') {
        $message = "❌ Nama tumbuhan tidak boleh kosong.";
    } else {
        $stmtEdit = $conn->prepare("UPDATE tbl_user_plants SET plant_name = ? WHERE plant_id = ? AND fld_user_num = ?");
        if ($stmtEdit->execute([$new_name, $plant_id, $user_id])) {
            $message = "✅ Nama tumbuhan berjaya dikemaskini!";
        } else {
            $message = "❌ Gagal mengemaskini nama tumbuhan.";
        }
    }
}

// Fetch updated plant list
$stmt = $conn->prepare("SELECT * FROM tbl_user_plants WHERE fld_user_num = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$plants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch shops for simple listing
$shop_search = isset($_GET['shop_search']) ? $_GET['shop_search'] : '';
$likeSearch = "%$shop_search%";

if ($shop_search) {
    $stmtShops = $conn->prepare("
        SELECT * FROM tbl_shops 
        WHERE 
            state = ? 
            OR shop_name LIKE ? 
            OR city LIKE ? 
        ORDER BY shop_name ASC
    ");
    $stmtShops->execute([$shop_search, $likeSearch, $likeSearch]);
} else {
    $stmtShops = $conn->prepare("SELECT * FROM tbl_shops ORDER BY shop_name ASC");
    $stmtShops->execute();
}
$shops = $stmtShops->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Plant Diary</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet" />
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

    /* === NAVBAR & HAMBURGER (from your index.php) === */
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

    /* === Rest of Apple style CSS === */

    main {
      margin-top: 100px;
      padding: 4rem 10vw;
      max-width: 100vw;
      display: flex;
      flex-direction: column;
      gap: 3rem;
    }

    h2 {
      font-weight: 800;
      font-size: clamp(2.5rem, 8vw, 4rem);
      color: var(--dark-green);
      user-select: none;
    }

    /* Plant Cards Grid */
    .plants {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 2.5rem;
    }

    @media (max-width: 600px) {
      .plants {
        grid-template-columns: 1fr !important;
      }
    }

    .plant-card {
      background: var(--white);
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(38, 66, 47, 0.1);
      text-align: center;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      transition: box-shadow 0.3s ease, transform 0.3s ease;
      user-select: none;
    }

    .plant-card:hover {
      box-shadow: 0 12px 30px rgba(38, 66, 47, 0.15);
      transform: translateY(-6px);
    }

    .plant-card img {
      width: 100%;
      height: 220px;
      object-fit: cover;
      border-radius: 14px;
      margin-bottom: 1rem;
      user-select: none;
    }

    .plant-card strong {
      font-size: 1.6rem;
      margin-bottom: 1rem;
      font-weight: 700;
      color: var(--dark-green);
    }

    .plant-card p, .plant-card small {
      font-size: 1rem;
      color: #37512c;
      margin-bottom: 0.7rem;
      white-space: pre-wrap;
    }

    .plant-card button, .cream-btn {
      background-color: var(--dark-green);
      color: var(--white);
      padding: 0.75rem 1.75rem;
      border: none;
      border-radius: 50px;
      cursor: pointer;
      font-weight: 700;
      font-size: 1rem;
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      margin-top: auto;
      align-self: center;
      user-select: none;
      box-shadow: 0 6px 15px rgba(38, 66, 47, 0.3);
    }

    .plant-card button:hover, .cream-btn:hover {
      background-color: #1f381e;
      box-shadow: 0 8px 20px rgba(31, 56, 30, 0.6);
    }

    form {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }

    form input[type="text"] {
      flex-grow: 1;
      min-width: 220px;
      padding: 0.75rem 1rem;
      border-radius: 50px;
      border: 1.5px solid #ccc;
      font-size: 1rem;
      transition: border-color 0.3s ease;
      font-family: var(--primary-font);
      color: var(--dark-green);
    }

    form input[type="text"]:focus {
      outline: none;
      border-color: var(--dark-green);
    }

    form button[type="submit"] {
      background-color: var(--dark-green);
      color: var(--white);
      padding: 0.75rem 2rem;
      border-radius: 50px;
      border: none;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      user-select: none;
      box-shadow: 0 6px 15px rgba(38, 66, 47, 0.3);
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }

    form button[type="submit"]:hover {
      background-color: #1f381e;
      box-shadow: 0 8px 20px rgba(31, 56, 30, 0.6);
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 9999;
      justify-content: center;
      align-items: center;
      padding: 1rem;
    }

    .modal-content {
      background: var(--white);
      color: var(--dark-green);
      border-radius: 24px;
      padding: 2.5rem 3rem;
      max-width: 460px;
      width: 100%;
      box-shadow: 0 10px 30px rgba(38, 66, 47, 0.3);
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      position: relative;
      font-weight: 400;
      user-select: none;
    }

    .modal-content h3 {
      font-weight: 800;
      font-size: 1.75rem;
      user-select: text;
    }

   
    .modal-content input[type="text"], .modal-content input[type="file"], .modal-content button {
      width: 100%;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      border-radius: 50px;
      border: 1.5px solid #ccc;
      font-family: var(--primary-font);
      color: var(--dark-green);
      transition: border-color 0.3s ease;
      font-weight: 400;
      user-select: text;
    }

    .modal-content input[type="text"]:focus, .modal-content input[type="file"]:focus {
      outline: none;
      border-color: var(--dark-green);
    }

    .modal-content button {
      background-color: var(--dark-green);
      color: var(--white);
      border: none;
      cursor: pointer;
      font-weight: 700;
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      box-shadow: 0 8px 20px rgba(38, 66, 47, 0.5);
      user-select: none;
    }

    .modal-content button:hover {
      background-color: #1f381e;
      box-shadow: 0 10px 25px rgba(31, 56, 30, 0.8);
    }

    @media (max-width: 600px) {
      main {
        padding: 3rem 5vw;
      }
      .plants {
        grid-template-columns: 1fr !important;
      }
      form input[type="text"] {
        min-width: 100%;
      }
      form button[type="submit"] {
        width: 100%;
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

<main class="container">
  <h2>Plant Diary</h2>

  <!-- Plant Search Form -->
  <form action="plant-facts.php" method="GET" style="margin: 30px 0;">
    <input type="text" name="search" placeholder="Search plant facts..." />
    <button type="submit">Search</button>
  </form>

 <?php if ($message): ?>
  <div class="success-message" role="alert" aria-live="polite"> <?php echo htmlspecialchars($message); ?> </div>
<?php endif; ?>

<button onclick="openAddModal()" class="cream-btn" style="font-size: 1rem; padding: 12px 28px; margin-bottom: 40px;">+ Add New Plant</button>

<div class="plants" aria-live="polite">
  <?php foreach ($plants as $plant): ?>
    <div class="plant-card">
      <a href="plant-entry.php?id=<?php echo $plant['plant_id']; ?>" aria-label="View details for <?php echo htmlspecialchars($plant['plant_name']); ?>">
        <img src="<?php echo htmlspecialchars($plant['plant_image']); ?>" alt="<?php echo htmlspecialchars($plant['plant_name']); ?>" />
      </a>
      <strong><?php echo htmlspecialchars($plant['plant_name']); ?></strong>

      <button onclick="openEditModal(<?php echo $plant['plant_id']; ?>, '<?php echo htmlspecialchars(addslashes($plant['plant_name'])); ?>')" type="button" class="cream-btn" aria-label="Edit <?php echo htmlspecialchars($plant['plant_name']); ?>">Edit</button>

      <form method="POST" onsubmit="return confirm('Anda pasti mahu memadam tumbuhan ini?');" style="margin-top: 12px;">
        <input type="hidden" name="plant_id" value="<?php echo $plant['plant_id']; ?>" />
        <button type="submit" name="delete_plant" class="cream-btn" style="background-color:#f44336; color:#fff;" aria-label="Delete <?php echo htmlspecialchars($plant['plant_name']); ?>">Delete</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>

<hr aria-hidden="true" />

<h2>Senarai Kedai Tanaman & Berkebun</h2>

<!-- Shop Search Form -->
<form method="GET" novalidate style="margin-bottom: 40px;">
  <input
    type="text"
    name="shop_search"
    value="<?php echo htmlspecialchars($shop_search); ?>"
    placeholder="Cari kedai mengikut nama atau kawasan..."
    autocomplete="off"
  />
  <button type="submit" aria-label="Search shops">Cari</button>
</form>

<div class="plants" aria-live="polite">
  <?php if (count($shops) === 0): ?>
    <p>Tiada kedai ditemui untuk carian ini.</p>
  <?php else: ?>
    <?php foreach ($shops as $shop): ?>
  <?php
    $shopQuery = urlencode($shop['shop_name'] . ' ' . $shop['address'] . ' ' . $shop['city'] . ' ' . $shop['postcode']);
    $mapsUrl = "https://www.google.com/maps/search/?api=1&query=$shopQuery";
  ?>
  <a href="<?= $mapsUrl ?>" target="_blank" style="text-decoration: none; color: inherit;">
    <div class="plant-card" style="min-height: auto;">
      <strong><?= htmlspecialchars($shop['shop_name']) ?></strong><br />
      <small><?= htmlspecialchars($shop['address']) ?>, <?= htmlspecialchars($shop['city']) ?> <?= htmlspecialchars($shop['postcode']) ?></small>
      <p><strong>State:</strong> <?= htmlspecialchars($shop['state']) ?></p>
      <p><?= nl2br(htmlspecialchars($shop['description'])) ?></p>
      <?php if ($shop['phone']): ?>
        <p><strong>Telefon:</strong> <?= htmlspecialchars($shop['phone']) ?></p>
      <?php endif; ?>
      <?php if ($shop['email']): ?>
        <p><strong>Email:</strong> <?= htmlspecialchars($shop['email']) ?></p>
      <?php endif; ?>
    </div>
  </a>
<?php endforeach; ?>
  <?php endif; ?>
</div>

  <!-- Add Plant Modal -->
  <div id="addPlantModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="addPlantTitle" aria-describedby="addPlantDesc">
    <div class="modal-content">
      <h3 id="addPlantTitle">Tambah Tumbuhan</h3>
      <p id="addPlantDesc" style="color: var(--dark-green); font-weight: 400; font-size: 0.95rem;">Sila masukkan nama tumbuhan dan gambar.</p>
      <form method="POST" enctype="multipart/form-data">
        <input type="text" name="plant_name" placeholder="Nama Tumbuhan" required autocomplete="off" />
        <input type="file" name="plant_image" accept="image/*" required />
        <button type="submit" name="add_plant" class="cream-btn">Simpan</button>
        <button type="button" onclick="closeAddModal()" class="cream-btn" style="background-color:#aaa; color:#333; margin-top: 10px;">Batal</button>
      </form>
    </div>
  </div>

  <!-- Edit Plant Modal -->
  <div id="editPlantModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="editPlantTitle" aria-describedby="editPlantDesc">
    <div class="modal-content">
      <button class="close-btn" onclick="closeEditModal()" aria-label="Close edit plant modal">×</button>
      <h3 id="editPlantTitle">Kemaskini Nama Tumbuhan</h3>
      <p id="editPlantDesc" style="color: var(--dark-green); font-weight: 400; font-size: 0.95rem;">Edit nama tumbuhan di bawah.</p>
      <form method="POST" id="editPlantForm">
        <input type="hidden" name="plant_id" id="editPlantId" />
        <input type="text" name="new_plant_name" id="editPlantName" placeholder="Nama Tumbuhan" required autocomplete="off" />
        <button type="submit" name="edit_plant" class="cream-btn">Simpan</button>
        <button type="button" onclick="closeEditModal()" class="cream-btn" style="background-color:#aaa; color:#333; margin-top: 10px;">Batal</button>
      </form>
    </div>
  </div>
</main>

<script>
  // Hamburger menu toggle from index.php style
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

  // Modal open/close functions for Add Plant
  function openAddModal() {
    document.getElementById('addPlantModal').style.display = 'flex';
  }

  function closeAddModal() {
    document.getElementById('addPlantModal').style.display = 'none';
  }

  // Modal open/close functions for Edit Plant
  function openEditModal(plantId, plantName) {
    document.getElementById('editPlantId').value = plantId;
    document.getElementById('editPlantName').value = plantName;
    document.getElementById('editPlantModal').style.display = 'flex';
  }

  function closeEditModal() {
    document.getElementById('editPlantModal').style.display = 'none';
  }

  // Close modals if clicking outside modal content
  window.addEventListener('click', function(event) {
    const addModal = document.getElementById('addPlantModal');
    const editModal = document.getElementById('editPlantModal');

    if (event.target === addModal) {
      closeAddModal();
    }
    if (event.target === editModal) {
      closeEditModal();
    }
  });
</script>
</body>
</html>
