<?php
require_once 'config.php';

if (isset($_GET['task_id']) && !empty($_GET['task_id'])) {
    $task_id = $_GET['task_id'];

    $stmt = $pdo->prepare("DELETE FROM `task` WHERE `task_id` = :task_id");
    $stmt->execute(['task_id' => $task_id]);

    header("Location: index.php");
    exit();
}
?>
