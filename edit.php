<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['task_id'])) {
    header('Location: index.php');
    exit();
}

$task_id = (int)$_GET['task_id'];

// Fetch task details
$stmt = $pdo->prepare("SELECT * FROM task WHERE task_id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_task = trim($_POST['task']);
    $new_task_date = $_POST['task_date'];
    $new_status = $_POST['status'];

    if ($new_task === '' || $new_task_date === '') {
        $error = 'Please fill in all required fields.';
    } else {
        $update = $pdo->prepare("UPDATE task SET task = ?, task_date = ?, status = ? WHERE task_id = ?");
        $update->execute([$new_task, $new_task_date, $new_status, $task_id]);

        header('Location: index.php');
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Task</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Edit Task</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="mt-4">
        <div class="mb-3">
            <label for="task" class="form-label">Task Name</label>
            <input type="text" name="task" id="task" class="form-control" required
                   value="<?php echo htmlspecialchars($task['task']); ?>">
        </div>

        <div class="mb-3">
            <label for="task_date" class="form-label">Date</label>
            <input type="date" name="task_date" id="task_date" class="form-control" required
                   value="<?php echo htmlspecialchars($task['task_date']); ?>">
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="Pending" <?php if ($task['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                <option value="Done" <?php if ($task['status'] == 'Done') echo 'selected'; ?>>Done</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>
</body>
</html>
