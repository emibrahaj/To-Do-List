<?php
require_once 'config.php';

if (isset($_POST['add'])) {
    if (!empty($_POST['task'])) {
        $task = $_POST['task'];

        $stmt = $pdo->prepare("INSERT INTO `task` (`task`, `status`) VALUES (:task, 'Pending')");
        $stmt->execute(['task' => $task]);

        header('Location: index.php');
        exit();
    }
}
?>
