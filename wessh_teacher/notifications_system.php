<?php
// Self-contained PHP Notification System with SQLite and TailwindCSS

// Database setup using SQLite
$dbFile = 'notifications.db';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    is_seen BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Add sample data if table is empty
$stmt = $db->query("SELECT COUNT(*) FROM notifications");
if ($stmt->fetchColumn() == 0) {
    $samples = [
        ['New Enrollment Submitted', 'John Doe has submitted a new enrollment application for review.', 0],
        ['Review Required', 'Jane Smith\'s document is incomplete. Please check and notify.', 0],
        ['Application Approved', 'Bob Johnson\'s enrollment has been approved.', 1],
        ['Reminder: Pending Review', 'Alice Brown\'s application is still pending. Action needed.', 0],
    ];
    $insert = $db->prepare("INSERT INTO notifications (title, message, is_seen) VALUES (?, ?, ?)");
    foreach ($samples as $sample) {
        $insert->execute($sample);
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;

    if ($action === 'mark_read' && $id > 0) {
        $stmt = $db->prepare("UPDATE notifications SET is_seen = 1 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'mark_all_read') {
        $db->exec("UPDATE notifications SET is_seen = 1");
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}

// Fetch notifications and unseen count
$stmt = $db->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unseenCount = $db->query("SELECT COUNT(*) FROM notifications WHERE is_seen = 0")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Notifications</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles for dashboard-like appearance */
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }

        .topbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">
    <!-- Topbar with Notifications Dropdown -->
    <nav class="topbar bg-white shadow-md px-4 py-3 flex justify-between items-center">
        <div class="flex items-center">
            <h1 class="text-xl font-semibold text-gray-800">Teacher Dashboard</h1>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Notifications Button -->
            <div class="relative">
                <button id="notificationsBtn"
                    class="relative inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-bell mr-1"></i>
                    Notifications
                    <?php if ($unseenCount > 0): ?>
                        <span
                            class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo $unseenCount; ?></span>
                    <?php endif; ?>
                </button>
            </div>
            <!-- User Profile -->
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-700">Teacher</span>
                <i class="fas fa-user-circle text-gray-500"></i>
            </div>
        </div>
    </nav>

    <!-- Notifications Dropdown -->
    <div id="notificationsDropdown"
        class="hidden fixed right-4 top-16 w-96 bg-white rounded-lg shadow-xl border z-50 max-h-96 overflow-y-auto">
        <!-- Mark All as Read -->
        <div class="p-4 border-b">
            <button id="markAllRead" class="text-sm text-indigo-600 hover:text-indigo-500 font-medium">Mark all as
                read</button>
        </div>
        <!-- Notifications List -->
        <div id="notificationsList">
            <?php foreach ($notifications as $notif): ?>
                <div
                    class="p-4 border-b last:border-b-0 <?php echo $notif['is_seen'] ? 'bg-white' : 'bg-blue-50'; ?> hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($notif['title']); ?>
                            </h4>
                            <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($notif['message']); ?></p>
                            <span
                                class="text-xs text-gray-500"><?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?></span>
                        </div>
                        <?php if (!$notif['is_seen']): ?>
                            <button onclick="markAsRead(<?php echo $notif['id']; ?>)"
                                class="ml-2 text-xs bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700">Mark as
                                read</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($notifications)): ?>
            <div class="p-4 text-center text-gray-500">No notifications yet.</div>
        <?php endif; ?>
    </div>

    <!-- Main Content (Placeholder for Dashboard) -->
    <main class="p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Welcome to Your Dashboard</h2>
        <p class="text-gray-600">Notifications are managed in the top-right dropdown.</p>
    </main>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Toggle dropdown
        document.getElementById('notificationsBtn').addEventListener('click', function () {
            const dropdown = document.getElementById('notificationsDropdown');
            dropdown.classList.toggle('hidden');
        });

        // Close dropdown on outside click
        document.addEventListener('click', function (e) {
            const btn = document.getElementById('notificationsBtn');
            const dropdown = document.getElementById('notificationsDropdown');
            if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Mark as read
        function markAsRead(id) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_read&id=' + id
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const notif = document.querySelector(`[onclick="markAsRead(${id})"]`).closest('div');
                        notif.classList.remove('bg-blue-50');
                        notif.classList.add('bg-white');
                        const btn = notif.querySelector('button');
                        btn.remove();
                        // Update badge if visible
                        const badge = document.querySelector('.h-5.w-5');
                        if (badge) {
                            let count = parseInt(badge.textContent) - 1;
                            if (count > 0) {
                                badge.textContent = count;
                            } else {
                                badge.remove();
                            }
                        }
                    }
                });
        }

        // Mark all as read
        document.getElementById('markAllRead').addEventListener('click', function () {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_all_read'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.bg-blue-50').forEach(el => {
                            el.classList.remove('bg-blue-50');
                            el.classList.add('bg-white');
                            const btn = el.querySelector('button');
                            if (btn) btn.remove();
                        });
                        const badge = document.querySelector('.h-5.w-5');
                        if (badge) badge.remove();
                    }
                });
        });
    </script>
</body>

</html>