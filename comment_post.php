<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    $comment = trim($_POST['comment']);

    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO tbl_forum_comments (post_id, user_id, comment_text) VALUES (:post_id, :user_id, :comment)");
        $stmt->execute([
            ':post_id' => $post_id,
            ':user_id' => $user_id,
            ':comment' => $comment
        ]);
    }
}

header("Location: forum.php");
exit();
?>