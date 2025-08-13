<?php
session_start();
include 'database.php';

if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Plant CRUD
if (isset($_POST['add_plant'])) {
    $imagePath = null;
    if (isset($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imgName = basename($_FILES['image']['name']);
        $targetDir = "uploads/";
        $targetFile = $targetDir . time() . "_" . $imgName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }
    $stmt = $conn->prepare("INSERT INTO tbl_plant_facts (plant_name, scientific_name, description, light_requirements, watering_needs, soil_type, `usage`, fun_fact, image_path) VALUES (:name, :sci, :desc, :light, :water, :soil, :usage, :fun, :image)");
    $stmt->execute([
        ':name' => $_POST['plant_name'],
        ':sci' => $_POST['scientific_name'],
        ':desc' => $_POST['description'],
        ':light' => $_POST['light_requirements'],
        ':water' => $_POST['watering_needs'],
        ':soil' => $_POST['soil_type'],
        ':usage' => $_POST['usage'],
        ':fun' => $_POST['fun_fact'],
        ':image' => $imagePath
    ]);
    header('Location: plant_diary_crud.php?success=1');
    exit();
}

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM tbl_plant_facts WHERE fact_id = :id");
    $stmt->execute([':id' => $edit_id]);
    $edit_plant = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_POST['update_plant'])) {
    $imagePath = $_POST['current_image'];
    if (isset($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        if ($imagePath && file_exists($imagePath)) unlink($imagePath);
        $imgName = basename($_FILES['image']['name']);
        $targetDir = "uploads/";
        $targetFile = $targetDir . time() . "_" . $imgName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }
    $stmt = $conn->prepare("UPDATE tbl_plant_facts SET plant_name=:name, scientific_name=:sci, description=:desc, light_requirements=:light, watering_needs=:water, soil_type=:soil, `usage`=:usage, fun_fact=:fun, image_path=:image WHERE fact_id=:id");
    $stmt->execute([
        ':name' => $_POST['plant_name'],
        ':sci' => $_POST['scientific_name'],
        ':desc' => $_POST['description'],
        ':light' => $_POST['light_requirements'],
        ':water' => $_POST['watering_needs'],
        ':soil' => $_POST['soil_type'],
        ':usage' => $_POST['usage'],
        ':fun' => $_POST['fun_fact'],
        ':image' => $imagePath,
        ':id' => $_POST['fact_id']
    ]);
    header('Location: plant_diary_crud.php?updated=1');
    exit();
}

if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("SELECT image_path FROM tbl_plant_facts WHERE fact_id = :id");
    $stmt->execute([':id' => $_GET['delete']]);
    $plant = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($plant && $plant['image_path'] && file_exists($plant['image_path'])) unlink($plant['image_path']);
    $stmt = $conn->prepare("DELETE FROM tbl_plant_facts WHERE fact_id = :id");
    $stmt->execute([':id' => $_GET['delete']]);
    header('Location: plant_diary_crud.php?deleted=1');
    exit();
}
$plants = $conn->query("SELECT * FROM tbl_plant_facts ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Shop CRUD
if (isset($_POST['add_shop'])) {
    $stmt = $conn->prepare("INSERT INTO tbl_shops (shop_name, description, address, city, postcode, phone, email, latitude, longitude) VALUES (:name, :desc, :addr, :city, :postcode, :phone, :email, :lat, :lng)");
    $stmt->execute([
        ':name' => $_POST['shop_name'],
        ':desc' => $_POST['description'],
        ':addr' => $_POST['address'],
        ':city' => $_POST['city'],
        ':postcode' => $_POST['postcode'],
        ':phone' => $_POST['phone'],
        ':email' => $_POST['email'],
        ':lat' => $_POST['latitude'],
        ':lng' => $_POST['longitude']
    ]);
    header('Location: plant_diary_crud.php?shop_added=1');
    exit();
}

if (isset($_GET['edit_shop'])) {
    $shop_id = $_GET['edit_shop'];
    $stmt = $conn->prepare("SELECT * FROM tbl_shops WHERE shop_id=:id");
    $stmt->execute([':id' => $shop_id]);
    $edit_shop = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_POST['update_shop'])) {
    $stmt = $conn->prepare("UPDATE tbl_shops SET shop_name=:name, description=:desc, address=:addr, city=:city, postcode=:postcode, phone=:phone, email=:email, latitude=:lat, longitude=:lng WHERE shop_id=:id");
    $stmt->execute([
        ':name' => $_POST['shop_name'],
        ':desc' => $_POST['description'],
        ':addr' => $_POST['address'],
        ':city' => $_POST['city'],
        ':postcode' => $_POST['postcode'],
        ':phone' => $_POST['phone'],
        ':email' => $_POST['email'],
        ':lat' => $_POST['latitude'],
        ':lng' => $_POST['longitude'],
        ':id' => $_POST['shop_id']
    ]);
    header('Location: plant_diary_crud.php?shop_updated=1');
    exit();
}

if (isset($_GET['delete_shop'])) {
    $stmt = $conn->prepare("DELETE FROM tbl_shops WHERE shop_id = :id");
    $stmt->execute([':id' => $_GET['delete_shop']]);
    header('Location: plant_diary_crud.php?shop_deleted=1');
    exit();
}

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_shops = $conn->query("SELECT COUNT(*) FROM tbl_shops")->fetchColumn();
$total_pages = ceil($total_shops / $limit);

$stmt = $conn->prepare("SELECT * FROM tbl_shops ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Plant Diary & Shop Management</title>
<style>
:root { --green-dark: #26422f; --white: #ffffff; }
body { margin: 0; font-family: Arial, sans-serif; background: #f5f5f7; color: var(--green-dark); }
header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; background: var(--white); border-bottom: 1px solid #eee; }
nav { display: flex; gap: 2rem; flex-wrap: wrap; }
nav a { text-decoration: none; color: var(--green-dark); font-weight: 600; text-transform: uppercase; }
.container { max-width: 1000px; margin: 30px auto; background: var(--white); padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
h2, h3 { margin-top: 0; }
input, textarea { width: 100%; margin-bottom: 10px; padding: 10px; border-radius: 5px; border: 1px solid #ccc; }
button, .btn { background: var(--green-dark); color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; display: inline-block; }
button:hover, .btn:hover { background: #3b6140; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
th { background: #f0f0f0; }
img { width: 60px; border-radius: 5px; }
</style>
</head>
<body>

<header>
  <nav>
    <a href="index.php">Home</a>
    <a href="we-care_crud.php">We Care CRUD</a>
    <a href="forum.php">Forum Komuniti</a>
    <a href="plant_diary_crud.php">Plant Diary CRUD</a>
  </nav>
</header>

<div class="container">
  <h2>Plant Diary CRUD</h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="text" name="plant_name" placeholder="Plant Name" value="<?= isset($edit_plant) ? htmlspecialchars($edit_plant['plant_name']) : ''; ?>" required>
    <input type="text" name="scientific_name" placeholder="Scientific Name" value="<?= isset($edit_plant) ? htmlspecialchars($edit_plant['scientific_name']) : ''; ?>" required>
    <textarea name="description" placeholder="Description" required><?= isset($edit_plant) ? htmlspecialchars($edit_plant['description']) : ''; ?></textarea>
    <input type="text" name="light_requirements" placeholder="Light Requirements" value="<?= isset($edit_plant) ? htmlspecialchars($edit_plant['light_requirements']) : ''; ?>">
    <input type="text" name="watering_needs" placeholder="Watering Needs" value="<?= isset($edit_plant) ? htmlspecialchars($edit_plant['watering_needs']) : ''; ?>">
    <input type="text" name="soil_type" placeholder="Soil Type" value="<?= isset($edit_plant) ? htmlspecialchars($edit_plant['soil_type']) : ''; ?>">
    <input type="text" name="usage" placeholder="Usage" value="<?= isset($edit_plant) ? htmlspecialchars($edit_plant['usage']) : ''; ?>">
    <input type="text" name="fun_fact" placeholder="Fun Fact" value="<?= isset($edit_plant) ? htmlspecialchars($edit_plant['fun_fact']) : ''; ?>">
    <input type="file" name="image" accept="image/*">
    <?php if (isset($edit_plant)): ?>
      <input type="hidden" name="current_image" value="<?= htmlspecialchars($edit_plant['image_path']); ?>">
      <input type="hidden" name="fact_id" value="<?= htmlspecialchars($edit_plant['fact_id']); ?>">
      <button type="submit" name="update_plant">Update Plant</button>
    <?php else: ?>
      <button type="submit" name="add_plant">Add Plant</button>
    <?php endif; ?>
  </form>

  <h3>All Plants</h3>
  <table>
    <tr><th>ID</th><th>Plant</th><th>Scientific</th><th>Light</th><th>Water</th><th>Soil</th><th>Image</th><th>Actions</th></tr>
    <?php foreach ($plants as $p): ?>
    <tr>
      <td><?= $p['fact_id']; ?></td>
      <td><?= htmlspecialchars($p['plant_name']); ?></td>
      <td><?= htmlspecialchars($p['scientific_name']); ?></td>
      <td><?= htmlspecialchars($p['light_requirements']); ?></td>
      <td><?= htmlspecialchars($p['watering_needs']); ?></td>
      <td><?= htmlspecialchars($p['soil_type']); ?></td>
      <td><img src="<?= htmlspecialchars($p['image_path']); ?>" alt=""></td>
      <td><a href="?edit=<?= $p['fact_id']; ?>" class="btn">Edit</a> <a href="?delete=<?= $p['fact_id']; ?>" class="btn" onclick="return confirm('Are you sure?');">Delete</a></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <h2>Shop Management</h2>
  <form method="POST">
    <input type="text" name="shop_name" placeholder="Shop Name" value="<?= isset($edit_shop) ? htmlspecialchars($edit_shop['shop_name']) : ''; ?>" required>
    <input type="text" name="description" placeholder="Description" value="<?= isset($edit_shop) ? htmlspecialchars($edit_shop['description']) : ''; ?>">
    <input type="text" name="address" placeholder="Address" value="<?= isset($edit_shop) ? htmlspecialchars($edit_shop['address']) : ''; ?>">
    <input type="text" name="city" placeholder="City" value="<?= isset($edit_shop) ? htmlspecialchars($edit_shop['city']) : ''; ?>">
    <input type="text" name="postcode" placeholder="Postcode" value="<?= isset($edit_shop) ? htmlspecialchars($edit_shop['postcode']) : ''; ?>">
    <input type="text" name="phone" placeholder="Phone" value="<?= isset($edit_shop) ? htmlspecialchars($edit_shop['phone']) : ''; ?>">
    <input type="email" name="email" placeholder="Email" value="<?= isset($edit_shop) ? htmlspecialchars($edit_shop['email']) : ''; ?>">
    <input type="text" name="latitude" placeholder="Latitude" value="<?= isset($edit_shop) ? htmlspecialchars($edit_shop['latitude']) : ''; ?>">
    <input type="text" name="longitude" placeholder="Longitude" value="<?= isset($edit_shop) ? htmlspecialchars($edit_shop['longitude']) : ''; ?>">
    <?php if (isset($edit_shop)): ?>
      <input type="hidden" name="shop_id" value="<?= htmlspecialchars($edit_shop['shop_id']); ?>">
      <button type="submit" name="update_shop">Update Shop</button>
    <?php else: ?>
      <button type="submit" name="add_shop">Add Shop</button>
    <?php endif; ?>
  </form>

  <table>
    <tr><th>ID</th><th>Name</th><th>City</th><th>Phone</th><th>Actions</th></tr>
    <?php foreach ($shops as $s): ?>
    <tr>
      <td><?= $s['shop_id']; ?></td>
      <td><?= htmlspecialchars($s['shop_name']); ?></td>
      <td><?= htmlspecialchars($s['city']); ?></td>
      <td><?= htmlspecialchars($s['phone']); ?></td>
      <td><a href="?edit_shop=<?= $s['shop_id']; ?>" class="btn">Edit</a> <a href="?delete_shop=<?= $s['shop_id']; ?>" class="btn" onclick="return confirm('Are you sure?');">Delete</a></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <div>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <a href="?page=<?= $i ?>" class="btn"><?= $i ?></a>
    <?php endfor; ?>
  </div>
</div>
</body>
</html>
