<?php
// Start session for user authentication
session_start();
require_once 'config.php'; // Database configuration

// Check if user is logged in, redirect to login if not
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    // Fetch user avatar filename from database
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Set avatar path - use default if user has no avatar or file doesn't exist
    if ($user && !empty($user['avatar']) && file_exists('uploads/' . $user['avatar'])) {
        $avatarPath = 'uploads/' . $user['avatar'];
    } else {
        $avatarPath = 'uploads/default.png'; // Fallback to default avatar
    }
} else {
    // Not logged in - redirect to login page
    header('Location: login.php');
    exit();
}

// Handle AJAX requests for updating task notes
if (isset($_POST['action']) && $_POST['action'] === 'update_notes') {
    header('Content-Type: application/json');
    
    // Validate required parameters
    if (isset($_POST['task_id']) && isset($_POST['notes'])) {
        $task_id = $_POST['task_id'];
        $notes = $_POST['notes'];
        
        // Update notes for specific task belonging to current user
        $stmt = $pdo->prepare("UPDATE `task` SET `notes` = :notes WHERE `task_id` = :task_id AND `username` = :username");
        $result = $stmt->execute([
            'notes' => $notes,
            'task_id' => $task_id,
            'username' => $username
        ]);
        
        // Return JSON response
        echo json_encode(['success' => $result]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    }
    exit();
}

// Handle form submission for adding new tasks
if (isset($_POST['add'])) {
    // Validate all required fields are present
    if (!empty($_POST['task']) && !empty($_POST['task_date']) && !empty($_POST['priority'])) {
        $task = $_POST['task'];
        $task_date = $_POST['task_date'];
        $priority = $_POST['priority'];

        // Insert new task with default 'Pending' status
        $stmt = $pdo->prepare("INSERT INTO `task` (`task`, `status`, `task_date`, `priority`, `username`, `notes`) VALUES (:task, 'Pending', :task_date, :priority, :username, '')");
        $stmt->execute([
            'task' => $task,
            'task_date' => $task_date,
            'priority' => $priority,
            'username' => $username
        ]);

        // Redirect to avoid form resubmission on page refresh
        header('Location: index.php');
        exit();
    }
}

// Handle search and filter parameters from GET request
$searchTask = $_GET['search_task'] ?? '';
$filterPriority = $_GET['filter_priority'] ?? '';

// Build dynamic SQL query based on filters
$sql = "SELECT * FROM `task` WHERE `username` = :username"; // Base query - only show current user's tasks
$params = ['username' => $username];

// Add search condition if search term provided
if ($searchTask !== '') {
    $sql .= " AND `task` LIKE :task";
    $params['task'] = "%" . $searchTask . "%"; // Wildcard search
}

// Add priority filter if valid priority selected
if ($filterPriority !== '' && in_array($filterPriority, ['Low', 'Medium', 'High'])) {
    $sql .= " AND `priority` = :priority";
    $params['priority'] = $filterPriority;
}

// Order tasks by ID (oldest first)
$sql .= " ORDER BY `task_id` ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fetchingtasks = $stmt->fetchAll(); // Get all matching tasks
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Todo List</title>
    <!-- Bootstrap CSS for styling -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: url('./assets/background.gif') center center / cover no-repeat fixed;
            min-height: 100vh;
            color: white;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                        radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            pointer-events: none;
        }

        .mask-custom {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            border-radius: 2em;
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            position: relative;
            overflow: hidden;
        }

        .mask-custom::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }

        .input-area input,
        .input-area select {
            border-radius: 1.5em;
            padding: 15px 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            height: 50px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .input-area input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .input-area input:focus,
        .input-area select:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 0 3px rgba(126, 64, 246, 0.3);
            transform: translateY(-2px);
        }

        .input-area button.btn {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            border-radius: 1.5em;
            color: white;
            border: none;
            font-weight: 600;
            min-width: 100px;
            padding: 0 30px;
            height: 50px;
            box-shadow: 0 4px 15px rgba(86, 171, 47, 0.3);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-area button.btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(86, 171, 47, 0.4);
        }

        table {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 1em;
            overflow: hidden;
        }

        table th {
            background: linear-gradient(135deg, rgba(126, 64, 246, 0.3) 0%, rgba(180, 64, 246, 0.2) 100%);
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 20px;
            color: rgba(255, 255, 255, 0.95);
        }

        table td {
            padding: 20px;
            background: rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            vertical-align: middle;
        }

        table tr:hover td {
            background: rgba(255, 255, 255, 0.08);
            transform: scale(1.01);
        }

        .priority-low {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .priority-medium {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .priority-high {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .bg-warning {
            background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%) !important;
            color: #1a1a1a !important;
            padding: 6px 14px;
            border-radius: 15px;
            font-weight: 600;
        }

        .bg-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
            padding: 6px 14px;
            border-radius: 15px;
            font-weight: 600;
        }

        .bg-secondary {
            background: linear-gradient(135deg, #536976 0%, #292e49 100%) !important;
            padding: 6px 14px;
            border-radius: 15px;
            font-weight: 600;
        }

        .notes-toggle {
            cursor: pointer;
            color: #667eea;
            margin-right: 10px;
            transition: all 0.3s ease;
            font-size: 18px;
        }

        .notes-toggle:hover {
            color: #764ba2;
            transform: scale(1.2);
        }

        .notes-toggle.expanded {
            transform: rotate(90deg);
        }

        .notes-toggle.has-notes {
            color: #38ef7d !important;
        }

        .notes-row {
            display: none;
            background: rgba(0, 0, 0, 0.3);
        }

        .notes-row.show {
            display: table-row;
        }

        .notes-content {
            padding: 20px 30px;
        }

        .notes-textarea {
            width: 100%;
            min-height: 100px;
            background: rgba(255, 255, 255, 0.08) !important;
            border: 2px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px;
            color: #ffffff !important;
            padding: 15px;
            resize: vertical;
            font-family: inherit;
            font-size: 15px;
            font-weight: 400;
            transition: all 0.3s ease;
        }

        .notes-textarea::placeholder {
            color: rgba(255, 255, 255, 0.4) !important;
        }

        .notes-textarea:focus {
            outline: none !important;
            border-color: rgba(102, 126, 234, 0.5) !important;
            background: rgba(255, 255, 255, 0.12) !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2) !important;
        }

        .notes-indicator {
            font-size: 0.9em;
            margin-left: 8px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .save-status {
            font-size: 0.85em;
            margin-top: 8px;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-weight: 500;
        }

        .save-status.show {
            opacity: 1;
        }

        .save-status.saving {
            color: #ffd200;
        }

        .save-status.saved {
            color: #38ef7d;
        }

        .save-status.error {
            color: #ff4b2b;
        }

        .text-info {
            color: #667eea !important;
            transition: all 0.3s ease;
        }

        .text-info:hover {
            color: #764ba2 !important;
            transform: scale(1.2);
        }

        .text-success {
            color: #38ef7d !important;
            transition: all 0.3s ease;
        }

        .text-success:hover {
            color: #11998e !important;
            transform: scale(1.2);
        }

        .text-warning {
            color: #ffd200 !important;
            transition: all 0.3s ease;
        }

        .text-warning:hover {
            color: #f7971e !important;
            transform: scale(1.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 1.5em;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #536976 0%, #292e49 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 1.5em;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            border: none;
            padding: 8px 20px;
            border-radius: 1.5em;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 65, 108, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 65, 108, 0.4);
        }
    </style>
</head>
<body>
<section class="vh-100">
    <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-md-12 col-xl-10">

                <!-- User profile section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <!-- Profile picture + username display -->
                    <div class="d-flex align-items-center gap-2 fw-semibold" style="font-size: 1.1rem;">
                        <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Profile Picture"
                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid white;">
                        <span style="background: rgba(255, 255, 255, 0.9); color: #333333; padding: 6px 12px; border-radius: 15px; font-weight: 600;">
                            <?php echo htmlspecialchars($username); ?>
                        </span>
                    </div>
                    <!-- Logout button -->
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>

                <!-- Main content card -->
                <div class="card mask-custom">
                    <div class="card-body p-4 text-white">
                        <!-- Header Section -->
                        <div class="text-center pt-3 pb-2">
                            <img src="https://i.gifer.com/origin/f5/f5baef4b6b6677020ab8d091ef78a3bc_w200.gif"
                                 alt="Check" width="60">
                            <h2 class="my-4">📅 Todo List</h2>
                        </div>

                        <!-- Add Task Form -->
                        <div class="input-area mb-4">
                            <form method="POST" class="d-flex gap-3 justify-content-center flex-wrap">
                                <input type="text" name="task" placeholder="Task name..." required 
                                       style="width: 300px;">
                                <input type="date" name="task_date" required style="width: 200px;">
                                <select name="priority" required style="width: 150px;">
                                    <option value="Low">Low Priority</option>
                                    <option value="Medium" selected>Medium Priority</option>
                                    <option value="High">High Priority</option>
                                </select>
                                <button class="btn" name="add">
                                    <i class="fa-solid fa-plus"></i> Add
                                </button>
                            </form>
                        </div>

                        <!-- Search / Filter Form -->
                        <form method="GET" class="d-flex gap-3 justify-content-center flex-wrap mb-4">
                            <input type="text" name="search_task" placeholder="Search task..." 
                                   value="<?php echo htmlspecialchars($searchTask); ?>" style="width: 300px;">
                            <select name="filter_priority" style="width: 150px;">
                                <option value="">All Priorities</option>
                                <option value="Low" <?php if ($filterPriority == 'Low') echo 'selected'; ?>>Low</option>
                                <option value="Medium" <?php if ($filterPriority == 'Medium') echo 'selected'; ?>>Medium</option>
                                <option value="High" <?php if ($filterPriority == 'High') echo 'selected'; ?>>High</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="index.php" class="btn btn-secondary">Reset</a>
                        </form>

                        <!-- Tasks Table -->
                        <table class="table text-white mb-0">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Task</th>
                                <th scope="col">Date</th>
                                <th scope="col">Priority</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $count = 1;
                            if (count($fetchingtasks) > 0) {
                                // Loop through each task
                                foreach ($fetchingtasks as $fetch) {
                                    // Determine status badge color based on task status
                                    $statusClass = '';
                                    $statusText = $fetch['status'];

                                    switch ($statusText) {
                                        case 'Pending':
                                            $statusClass = 'bg-warning'; // Yellow for pending
                                            break;
                                        case 'Done':
                                            $statusClass = 'bg-success'; // Green for completed
                                            break;
                                        default:
                                            $statusClass = 'bg-secondary'; // Gray for other
                                    }

                                    // Determine priority badge class based on priority level
                                    $priorityClass = '';
                                    switch ($fetch['priority']) {
                                        case 'Low':
                                            $priorityClass = 'priority-low badge';
                                            break;
                                        case 'Medium':
                                            $priorityClass = 'priority-medium badge';
                                            break;
                                        case 'High':
                                            $priorityClass = 'priority-high badge';
                                            break;
                                        default:
                                            $priorityClass = 'badge bg-secondary';
                                    }
                                    
                                    // Check if task has notes
                                    $hasNotes = !empty($fetch['notes']);
                                    ?>
                                    <tr class="fw-normal" id="task-row-<?php echo $fetch['task_id']; ?>">
                                        <th><?php echo $count++; ?></th>
                                        <td class="align-middle task-text">
                                            <?php echo htmlspecialchars($fetch['task']); ?>
                                            <?php if ($hasNotes): ?>
                                                <!-- Show note indicator if task has notes -->
                                                <span class="notes-indicator" title="Has notes">📝</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <?php echo date("M j, Y", strtotime($fetch['task_date'])); ?>
                                        </td>
                                        <td class="align-middle">
                                            <span class="<?php echo $priorityClass; ?>">
                                                <?php echo htmlspecialchars($fetch['priority']); ?>
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td class="align-middle">
                                            <!-- Notes toggle button (chevron icon) -->
                                            <i class="fas fa-chevron-right notes-toggle <?php echo $hasNotes ? 'has-notes' : ''; ?>" 
                                               data-task-id="<?php echo $fetch['task_id']; ?>" 
                                               title="Toggle notes"></i>
                                            
                                            <!-- Edit task link -->
                                            <a href="edit.php?task_id=<?php echo $fetch['task_id']; ?>" 
                                               class="text-decoration-none me-3" title="Edit Task">
                                                <i class="fas fa-edit fa-lg text-info"></i>
                                            </a>

                                            <!-- Mark as done link (only show if not already done) -->
                                            <?php if ($fetch['status'] != "Done") { ?>
                                                <a href="update.php?task_id=<?php echo $fetch['task_id']; ?>" 
                                                   class="text-decoration-none me-3" title="Mark as Done">
                                                    <i class="fas fa-check fa-lg text-success"></i>
                                                </a>
                                            <?php } ?>

                                            <!-- Delete task link -->
                                            <a href="delete.php?task_id=<?php echo $fetch['task_id']; ?>" 
                                               class="text-decoration-none" title="Delete Task">
                                                <i class="fas fa-trash-alt fa-lg text-warning"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    
                                    <!-- Notes row (hidden by default, shown when chevron clicked) -->
                                    <tr class="notes-row" id="notes-row-<?php echo $fetch['task_id']; ?>">
                                        <td colspan="6">
                                            <div class="notes-content">
                                                <textarea class="notes-textarea" 
                                                          data-task-id="<?php echo $fetch['task_id']; ?>"
                                                          placeholder="Add your notes here..."><?php echo htmlspecialchars($fetch['notes'] ?? ''); ?></textarea>
                                                <div class="save-status" id="save-status-<?php echo $fetch['task_id']; ?>"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                // Show message when no tasks found
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center text-white-50">No tasks found.</td>
                                </tr>
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>

                    </div> 
                </div>

            </div>
        </div>
    </div>
</section>

<!-- Font Awesome for icons -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle notes toggle functionality
    document.querySelectorAll('.notes-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            const notesRow = document.getElementById(`notes-row-${taskId}`);
            const isExpanded = notesRow.classList.contains('show');
            
            if (isExpanded) {
                // Collapse notes section
                notesRow.classList.remove('show');
                this.classList.remove('expanded');
            } else {
                // Expand notes section
                notesRow.classList.add('show');
                this.classList.add('expanded');
                // Focus on textarea for better UX
                const textarea = notesRow.querySelector('.notes-textarea');
                setTimeout(() => textarea.focus(), 100);
            }
        });
    });
    
    // Handle auto-saving notes with debounce
    let saveTimeouts = {}; // Store timeouts for each textarea
    
    document.querySelectorAll('.notes-textarea').forEach(function(textarea) {
        textarea.addEventListener('input', function() {
            const taskId = this.dataset.taskId;
            const notes = this.value;
            const statusDiv = document.getElementById(`save-status-${taskId}`);
            const toggle = document.querySelector(`[data-task-id="${taskId}"].notes-toggle`);
            const indicator = document.querySelector(`#task-row-${taskId} .notes-indicator`);
            
            // Clear existing timeout to debounce saves
            if (saveTimeouts[taskId]) {
                clearTimeout(saveTimeouts[taskId]);
            }
            
            // Show saving status immediately
            statusDiv.textContent = 'Saving...';
            statusDiv.className = 'save-status show saving';
            
            // Debounce the save operation (wait 1 second after user stops typing)
            saveTimeouts[taskId] = setTimeout(function() {
                // Prepare form data for AJAX request
                const formData = new FormData();
                formData.append('action', 'update_notes');
                formData.append('task_id', taskId);
                formData.append('notes', notes);
                
                // Send AJAX request to save notes
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        statusDiv.textContent = 'Saved';
                        statusDiv.className = 'save-status show saved';
                        
                        // Update visual indicators based on whether notes exist
                        if (notes.trim()) {
                            // Add green color to chevron
                            toggle.classList.add('has-notes');
                            // Add note indicator emoji if not already present
                            if (!indicator) {
                                const taskCell = document.querySelector(`#task-row-${taskId} .task-text`);
                                const newIndicator = document.createElement('span');
                                newIndicator.className = 'notes-indicator';
                                newIndicator.title = 'Has notes';
                                newIndicator.textContent = '📝';
                                taskCell.appendChild(newIndicator);
                            }
                        } else {
                            // Remove indicators if notes are empty
                            toggle.classList.remove('has-notes');
                            if (indicator) {
                                indicator.remove();
                            }
                        }
                    } else {
                        // Show error message
                        statusDiv.textContent = 'Error saving';
                        statusDiv.className = 'save-status show error';
                    }
                    
                    // Hide status message after 2 seconds
                    setTimeout(() => {
                        statusDiv.classList.remove('show');
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    statusDiv.textContent = 'Error saving';
                    statusDiv.className = 'save-status show error';
                    
                    setTimeout(() => {
                        statusDiv.classList.remove('show');
                    }, 2000);
                });
            }, 1000); // 1 second debounce delay
        });
    });
});
</script>
</body>
</html>