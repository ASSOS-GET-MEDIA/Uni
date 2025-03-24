<?php
    include 'db.php';

    $id = $_POST['post_id'];

    $sql = "DELETE FROM post WHERE id = $id";
    $result = $conn->query($sql);

    if($result){
        echo json_encode(['status' => 'success']);
    }else{
        echo json_encode(['status' => 'error']);
    }

    $conn->close();
?>