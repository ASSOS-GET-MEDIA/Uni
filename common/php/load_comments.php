<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['post_id'])) {
    $postId = $_GET['post_id'];

    $stmt = $conn->prepare("
        SELECT c.id, c.content, c.created_at, u.username, u.pp, u.at 
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ? ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($comments);
}
?>