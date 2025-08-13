<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    // Check if user already liked the post
    $check = $conn->prepare("SELECT * FROM tbl_forum_likes WHERE post_id = :post_id AND user_id = :user_id");
    $check->execute([':post_id' => $post_id, ':user_id' => $user_id]);

    if ($check->rowCount() === 0) {
        // Insert like
        $stmt = $conn->prepare("INSERT INTO tbl_forum_likes (post_id, user_id) VALUES (:post_id, :user_id)");
        $stmt->execute([':post_id' => $post_id, ':user_id' => $user_id]);
    } else {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM tbl_forum_likes WHERE post_id = :post_id AND user_id = :user_id");
        $stmt->execute([':post_id' => $post_id, ':user_id' => $user_id]);
    }

    header('Location: forum.php');
    exit();
}
?>
