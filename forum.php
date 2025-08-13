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
$upload_message = "";

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    $postIdToDelete = $_POST['delete_post_id'];
    $stmt = $conn->prepare("SELECT * FROM tbl_forum WHERE post_id = ?");
    $stmt->execute([$postIdToDelete]);
    $postToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_level === 'admin' || $postToDelete['user_id'] == $user_id) {
        if (!empty($postToDelete['post_image']) && file_exists($postToDelete['post_image'])) {
            unlink($postToDelete['post_image']);
        }
        $conn->prepare("DELETE FROM tbl_forum_likes WHERE post_id = ?")->execute([$postIdToDelete]);
        $conn->prepare("DELETE FROM tbl_forum_comments WHERE post_id = ?")->execute([$postIdToDelete]);
        $conn->prepare("DELETE FROM tbl_forum WHERE post_id = ?")->execute([$postIdToDelete]);
        $upload_message = "✅ Post deleted successfully.";
    } else {
        $upload_message = "❌ You don't have permission to delete this post.";
    }
}

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = $_POST['content'];
    $imagePath = null;

    file_put_contents('debug_post_length.txt', "Content length: " . strlen($content));

    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
        $imgName = basename($_FILES['post_image']['name']);
        $targetDir = "uploads/";
        $targetFile = $targetDir . time() . "_" . $imgName;

        if (move_uploaded_file($_FILES['post_image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        } else {
            $upload_message = "❌ Gagal memuat naik imej.";
        }
    }

    $stmt = $conn->prepare("INSERT INTO tbl_forum (user_id, username, content, post_image) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $username, $content, $imagePath])) {
        $upload_message = "✅ Siaran berjaya dihantar.";
    } else {
        $upload_message = "❌ Gagal menghantar siaran.";
    }
}

// Fetch posts
$stmt = $conn->prepare("SELECT * FROM tbl_forum ORDER BY created_at DESC");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Likes
$likeStmt = $conn->prepare("SELECT post_id, COUNT(*) AS like_count FROM tbl_forum_likes GROUP BY post_id");
$likeStmt->execute();
$likes = [];
foreach ($likeStmt as $row) {
    $likes[$row['post_id']] = $row['like_count'];
}

// Comments
$commentStmt = $conn->prepare("SELECT fc.*, u.username FROM tbl_forum_comments fc JOIN tbl_user u ON fc.user_id = u.fld_user_num ORDER BY fc.created_at ASC");
$commentStmt->execute();
$comments = [];
foreach ($commentStmt as $row) {
    $comments[$row['post_id']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forum Komuniti</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root { --green-dark: #26422f; --white: #ffffff; --cream: #f3f5e9; }
body { margin: 0; font-family: Arial, sans-serif; background-color: var(--white); color: var(--green-dark); }
header { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 3rem; position: sticky; top: 0; background-color: var(--white); border-bottom: 1px solid #eee; }
nav { display: flex; gap: 2rem; flex-wrap: wrap; }
nav a { text-decoration: none; color: var(--green-dark); font-weight: 600; text-transform: uppercase; }
nav a:hover { color: #1a2a1d; }
.menu-icon { display: none; flex-direction: column; gap: 4px; cursor: pointer; }
.menu-icon span { width: 25px; height: 3px; background: var(--green-dark); display: block; border-radius: 2px; }
.dropdown-menu { display: none; position: absolute; top: 100%; right: 3rem; background: var(--white); border: 1px solid #ccc; box-shadow: 0 4px 10px rgba(0,0,0,0.05); padding: 1rem; }
.dropdown-menu a { display: block; padding: 0.5rem 1rem; text-decoration: none; color: var(--green-dark); }
.dropdown-menu a:hover { background: #f4f4f4; }
@media (max-width: 768px) { nav { display: none; } .menu-icon { display: flex; } }
.post-form { background: var(--white); padding: 20px; max-width: 800px; margin: 30px auto; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
textarea { width: 100%; height: 100px; padding: 10px; font-size: 16px; border-radius: 8px; border: 1px solid #ccc; margin-bottom: 10px; resize: vertical; }
button { padding: 12px 20px; border-radius: 8px; background: var(--green-dark); color: var(--white); border: none; cursor: pointer; font-size: 16px; }
button:hover { background: #62826b; }
.post-box { background: var(--white); padding: 20px; margin: 15px auto; max-width: 800px; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
.post-box img { width: 100%; max-height: 300px; object-fit: contain; border-radius: 8px; margin-top: 10px; }
.like-btn, .comment-btn { background: none; border: none; color: var(--green-dark); cursor: pointer; font-size: 16px; padding: 8px 16px; border-radius: 8px; }
.like-btn:hover, .comment-btn:hover { background: #f3f5e9; }
.message { text-align: center; color: green; font-weight: bold; }
.error { text-align: center; color: red; font-weight: bold; }
.comment-box { background: #fff; margin-top: 5px; padding: 8px; border-left: 2px solid var(--green-dark); }
.delete-btn { color: red; background: none; border: none; float: right; cursor: pointer; font-size: 14px; }
.toggle-btn { background: none; color: #007bff; cursor: pointer; border: none; font-size: 14px; margin-top: 5px; }
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

<script>
function toggleMenu() {
  const dropdown = document.getElementById("dropdownMenu");
  dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}
document.addEventListener("click", function(event) {
  const dropdown = document.getElementById("dropdownMenu");
  const icon = document.querySelector(".menu-icon");
  if (!dropdown.contains(event.target) && !icon.contains(event.target)) {
    dropdown.style.display = "none";
  }
});
function toggleContent(button) {
  const postBox = button.closest('.post-box');
  const preview = postBox.querySelector('.preview-content');
  const full = postBox.querySelector('.full-content');

  if (full.style.display === "none") {
    preview.style.display = "none";
    full.style.display = "block";
    button.textContent = "See less";
  } else {
    preview.style.display = "block";
    full.style.display = "none";
    button.textContent = "See more";
  }
}
</script>

<?php if ($upload_message): ?>
 <p class="<?= strpos($upload_message, '❌') !== false ? 'error' : 'message' ?>"> <?= $upload_message ?> </p>
<?php endif; ?>

<form class="post-form" action="forum.php" method="POST" enctype="multipart/form-data">
  <input type="hidden" name="MAX_FILE_SIZE" value="64000000"><!-- 64MB -->
  <textarea name="content" rows="3" placeholder="Apa yang anda fikirkan hari ini?" required></textarea><br><br>
  <input type="file" name="post_image" accept="image/*"><br><br>
  <button type="submit">Post</button>
</form>

<?php foreach ($posts as $post): ?>
  <div class="post-box">
    <strong>@<?= htmlspecialchars($post['username']) ?></strong>
    • <?= date("d/m/Y h:i A", strtotime($post['created_at'])) ?>
    <?php if ($user_level === 'admin' || $post['user_id'] == $user_id): ?>
      <form action="forum.php" method="POST" style="display:inline; float:right;" onsubmit="return confirm('Delete this post?')">
        <input type="hidden" name="delete_post_id" value="<?= $post['post_id'] ?>">
        <button type="submit" class="delete-btn">🗑️ Delete</button>
      </form>
    <?php endif; ?>

    <!-- Preview content -->
    <p class="preview-content"><?= nl2br(htmlspecialchars(mb_strimwidth($post['content'], 0, 300))) ?>...</p>

    <!-- Full content -->
    <p class="full-content" style="display:none;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>

    <!-- Toggle button if content is long -->
    <?php if (strlen($post['content']) > 300): ?>
      <button class="toggle-btn" onclick="toggleContent(this)">See more</button>
    <?php endif; ?>

    <?php if (!empty($post['post_image'])): ?>
      <img src="<?= htmlspecialchars($post['post_image']) ?>" alt="Image">
    <?php endif; ?>
    <div>
      <form action="like_post.php" method="POST" style="display:inline;">
        <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
        <button class="like-btn" type="submit">❤️ Like (<?= isset($likes[$post['post_id']]) ? $likes[$post['post_id']] : 0 ?>
)</button>
      </form>
      <form action="comment_post.php" method="POST" style="display:inline;">
        <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
        <input type="text" name="comment" placeholder="Add a comment..." required>
        <button class="comment-btn" type="submit">💬 Comment</button>
      </form>
    </div>
    <?php if (!empty($comments[$post['post_id']])): ?>
      <?php foreach ($comments[$post['post_id']] as $c): ?>
        <div class="comment-box">
          <strong>@<?= htmlspecialchars($c['username']) ?></strong>: <?= htmlspecialchars($c['comment_text']) ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

</body>
</html>
